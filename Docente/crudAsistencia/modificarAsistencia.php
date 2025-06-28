<?php
require_once '../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

include '../../conexionn.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
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

    $stmtUpdate = $conn->prepare("UPDATE asistencia SET estado = ?, observaciones = ? WHERE alumno_id = ? AND DATE(fecha) = ?");
    $stmtUpdate->bind_param("ssis", $estado, $observacion, $alumnoId, $fecha);
    $stmtUpdate->execute();
    $stmtUpdate->close();
}

echo json_encode(["mensaje" => "Asistencia actualizada correctamente"]);
$conn->close();
