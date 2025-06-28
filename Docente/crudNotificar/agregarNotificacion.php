<?php
require_once '../../vendor/autoload.php'; // Ajusta si cambia de ubicación
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

// Validar token JWT
$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["error" => "Token no proporcionado"]);
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
$claveSecreta = $_ENV['JWT_KEY'];

try {
    $decoded = JWT::decode($token, new Key($claveSecreta, 'HS256'));
} catch (\Firebase\JWT\ExpiredException $e) {
    http_response_code(401);
    echo json_encode(["error" => "Token expirado"]);
    exit;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["error" => "Token inválido"]);
    exit;
}

// Leer el cuerpo del JSON
$data = json_decode(file_get_contents("php://input"), true);

$conn = conexionn::obtenerConexion();

// Validación de campos
if (
    !empty($data['alumno_id']) &&
    !empty($data['emisor_tipo']) &&
    !empty($data['emisor_id']) &&
    !empty($data['mensaje'])
) {
    $alumno_id = intval($data['alumno_id']);
    $emisor_tipo = $conn->real_escape_string($data['emisor_tipo']);
    $emisor_id = intval($data['emisor_id']);
    $mensaje = $conn->real_escape_string($data['mensaje']);

    $stmt = $conn->prepare("INSERT INTO notificaciones (alumno_id, emisor_tipo, emisor_id, mensaje) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Error al preparar la consulta: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("isis", $alumno_id, $emisor_tipo, $emisor_id, $mensaje);
    if ($stmt->execute()) {
        echo json_encode(["mensaje" => "Notificación registrada correctamente"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al insertar notificación: " . $stmt->error]);
    }

    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(["error" => "Todos los campos son obligatorios"]);
}

$conn->close();
