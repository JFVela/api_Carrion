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

// Leer ID desde la URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "ID del curso no proporcionado o inválido"]);
    exit;
}
$id = intval($_GET['id']);

// Leer datos del body
$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['nombreCurso'])) {
    http_response_code(400);
    echo json_encode(["error" => "El nombre es requerido"]);
    exit;
}
$nombre = $data['nombreCurso'];

// Conexión
$conn = conexionn::obtenerConexion();
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Conexión fallida: " . $conn->connect_error]);
    exit;
}

$stmt = $conn->prepare("UPDATE cursos SET nombre = ? WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Error preparando consulta: " . $conn->error]);
    exit;
}

$stmt->bind_param("si", $nombre, $id);

if ($stmt->execute()) {
    echo json_encode(["mensaje" => "Curso actualizado correctamente"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Error al actualizar el curso"]);
}

$stmt->close();
$conn->close();
?>
