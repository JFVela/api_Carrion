<?php
require_once '../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
include '../../conexionn.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Validar token
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

// Obtener DNI desde la query string
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "DNI no proporcionado"]);
    exit;
}

$dni = $_GET['id'];

// Obtener datos del body
$data = json_decode(file_get_contents("php://input"), true);
$nombre = $data['nombre'] ?? '';
$apellido1 = $data['apellido1'] ?? '';
$apellido2 = $data['apellido2'] ?? '';
$sede_id = $data['sede'] ?? '';

// Validación
if ($nombre && $apellido1 && $apellido2 && $sede_id) {
    $conn = conexionn::obtenerConexion();

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "Conexión fallida: " . $conn->connect_error]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE control_academico.alumnos 
                            SET nombre = ?, apellido1 = ?, apellido2 = ?, id_sede = ?
                            WHERE id = ?");

    if (!$stmt) {
        echo json_encode(["error" => "Error preparando consulta: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("sssii", $nombre, $apellido1, $apellido2, $sede_id, $dni);

    if ($stmt->execute()) {
        echo json_encode(["mensaje" => "Alumno actualizado correctamente"]);
    } else {
        echo json_encode(["error" => "Error al actualizar: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
}
?>
