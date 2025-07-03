<?php
require_once '../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
include '../../conexionn.php';

// 1. CORS y JSON
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Validar JWT
$hdrs = function_exists('apache_request_headers') ? apache_request_headers() : getallheaders();
$auth = $hdrs['Authorization'] ?? $hdrs['authorization'] ?? null;
if (!$auth || !preg_match('/^Bearer\s+(.+)$/', $auth, $m)) {
    http_response_code(401);
    echo json_encode(["error" => "Token no proporcionado"]);
    exit;
}
$token = $m[1];
try {
    JWT::decode($token, new Key($_ENV['JWT_KEY'], 'HS256'));
} catch (\Throwable $e) {
    http_response_code(401);
    echo json_encode(["error" => "Token inválido o expirado"]);
    exit;
}

// 3. Conexión a la base de datos
$conn = conexionn::obtenerConexion();
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Conexión fallida: {$conn->connect_error}"]);
    exit;
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 4. Leer y decodificar JSON
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["error" => "JSON inválido"]);
    exit;
}

// 5. Validar campos
$id_curso    = filter_var($input['id_curso']    ?? null, FILTER_VALIDATE_INT);
$id_profesor = filter_var($input['id_profesor'] ?? null, FILTER_VALIDATE_INT);
$id_grado    = filter_var($input['id_grado']    ?? null, FILTER_VALIDATE_INT);
$id_aula     = filter_var($input['id_grado']     ?? null, FILTER_VALIDATE_INT);

$fields = [
    'id_curso'    => $id_curso,
    'id_profesor' => $id_profesor,
    'id_grado'    => $id_grado,
    'id_aula'     => $id_aula
];
$errors = [];
foreach ($fields as $name => $val) {
    if ($val === false || $val === null) {
        $errors[] = "$name faltante o inválido";
    }
}
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        "error"  => "Validation failed",
        "fields" => $errors
    ]);
    exit;
}

// 6. Año actual en Lima
date_default_timezone_set('America/Lima');
$anio = (int) date('Y');

try {
    // 7. Transacción e INSERT
    $conn->begin_transaction();

    $sql = "
        INSERT INTO clases
            (curso_id, profesor_id, grado_id, aula_id, anio)
        VALUES (?, ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiiii', $id_curso, $id_profesor, $id_grado, $id_aula, $anio);
    $stmt->execute();

    $nuevoId = $conn->insert_id;
    $conn->commit();

    http_response_code(201);
    echo json_encode([
        "mensaje"  => "Clase creada correctamente",
        "id_clase" => $nuevoId
    ]);

} catch (mysqli_sql_exception $e) {
    // 8. Rollback y manejo de errores
    $conn->rollback();

    if ($e->getCode() === 1062) {
        http_response_code(409);
        echo json_encode([
            "error" => "El grado ya tiene asignado este curso para el año {$anio}"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "error" => "Error en la base de datos: " . $e->getMessage()
        ]);
    }
}

// 9. Cierre de recursos
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    $stmt->close();
}
$conn->close();
