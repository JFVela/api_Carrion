<?php
require_once '../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

include '../../conexionn.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["error" => "Token no proporcionado"]);
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
$claveSecreta = $_ENV['JWT_KEY'];

try {
    JWT::decode($token, new Key($claveSecreta, 'HS256'));
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["error" => "Token inválido"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$fecha = date("Y-m-d");
$conn = conexionn::obtenerConexion();

foreach ($data as $asistencia) {
    $alumnoId = $asistencia['id'];
    $estado = $asistencia['estado'];
    $observacion = $asistencia['observaciones'] ?? null;

    // Verificar si ya existe asistencia para el alumno hoy
    $stmtCheck = $conn->prepare("SELECT id FROM asistencia WHERE alumno_id = ? AND DATE(fecha) = ?");
    if (!$stmtCheck) {
        echo json_encode(["error" => "Error al preparar verificación: " . $conn->error]);
        exit;
    }
    $stmtCheck->bind_param("is", $alumnoId, $fecha);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {
        $stmtCheck->close();
        continue;
    }

    $stmtCheck->close();

    // Insertar asistencia
    $stmtInsert = $conn->prepare("INSERT INTO asistencia (alumno_id, fecha, estado, observaciones) VALUES (?, NOW(), ?, ?)");
    if (!$stmtInsert) {
        echo json_encode(["error" => "Error al preparar inserción: " . $conn->error]);
        exit;
    }

    $stmtInsert->bind_param("iss", $alumnoId, $estado, $observacion);
    $stmtInsert->execute();
    $stmtInsert->close();
}

echo json_encode(["mensaje" => "Asistencia guardada correctamente"]);
$conn->close();
?>
