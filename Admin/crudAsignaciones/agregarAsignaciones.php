<?php
require_once '../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

include '../../conexionn.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejo de preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 🔒 Validar token JWT
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

// Conexión a base de datos
$conn = conexionn::obtenerConexion();
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Conexión fallida: " . $conn->connect_error]);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$input = json_decode(file_get_contents("php://input"), true);

$curso = $input['cursoNombre'] ?? null;
$salon = $input['salonNombre'] ?? null;
$sede = $input['sedeNombre'] ?? null;
$id_profesor = $input['docenteId'] ?? null;

if (!$curso || !$salon || !$sede || !$id_profesor) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan campos obligatorios"]);
    exit;
}

// Insertar asignación
$sql = "INSERT INTO asignaciones (curso, salon, sede, id_profesor) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Error al preparar la consulta: " . $conn->error]);
    exit;
}

$stmt->bind_param("sssi", $curso, $salon, $sede, $id_profesor);

if ($stmt->execute()) {
    echo json_encode(["mensaje" => "Asignación insertada correctamente", "id" => $stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Error al insertar: " . $stmt->error]);
}

$stmt->close();
$conn->close();
