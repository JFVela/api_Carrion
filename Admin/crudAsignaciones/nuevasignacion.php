<?php
require_once '../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
include '../../conexionn.php';

/* Cabeceras CORS */
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* Validar JWT */
$hdrs      = apache_request_headers();
$auth      = $hdrs['Authorization'] ?? $hdrs['authorization'] ?? null;
if (!$auth) {
    http_response_code(401);
    echo json_encode(["error"=>"Token no proporcionado"]);
    exit;
}
$token = str_replace('Bearer ', '', $auth);
try {
    JWT::decode($token, new Key($_ENV['JWT_KEY'], 'HS256'));
} catch (\Throwable $e) {
    http_response_code(401);
    echo json_encode(["error"=>"Token inválido o expirado"]);
    exit;
}

/* Conexión */
$conn = conexionn::obtenerConexion();
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error"=>"Conexión fallida: {$conn->connect_error}"]);
    exit;
}

// Activar excepciones en mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* Leer JSON */
$input       = json_decode(file_get_contents('php://input'), true);
$curso_id    = $input['curso_id']    ?? null;
$profesor_id = $input['profesor_id'] ?? null;
$grado_id    = $input['grado_id']    ?? null;
$aula_id     = $input['aula_id']     ?? null;
$anio        = $input['anio']        ?? null;
if (!$curso_id || !$profesor_id || !$grado_id || !$aula_id || !$anio) {
    http_response_code(400);
    echo json_encode(["error"=>"Faltan campos obligatorios"]);
    exit;
}

try {
    // 1) Iniciar transacción
    $conn->begin_transaction();

    // 2) Intentar INSERT
    $sql = "INSERT INTO clases
              (curso_id, profesor_id, grado_id, aula_id, anio)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiiii',
        $curso_id,
        $profesor_id,
        $grado_id,
        $aula_id,
        $anio
    );
    $stmt->execute();

    // 3) Commit sólo si ejecutó bien
    $conn->commit();

    // 4) Devolver ID generado
    $nuevoId = $conn->insert_id;
    echo json_encode([
        "mensaje"  => "Clase creada correctamente",
        "id_clase" => $nuevoId
    ]);

} catch (mysqli_sql_exception $e) {
    // 5) Rollback para que no se consuma el AUTO_INCREMENT
    $conn->rollback();

    if ($e->getCode() === 1062) {
        http_response_code(409);
        echo json_encode([
            "error" => "Ya existe una asignación para ese curso, grado y año"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "error" => "Error en la base de datos: " . $e->getMessage()
        ]);
    }
}

$stmt->close();
$conn->close();
