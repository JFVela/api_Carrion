<?php
require_once '../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
include '../../conexionn.php';

// CORS (origen restringido)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obtener header Authorization
$headers = function_exists('apache_request_headers')
    ? apache_request_headers()
    : getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
if (!$authHeader || !preg_match('/^Bearer\s+(.+)$/', $authHeader, $m)) {
    http_response_code(401);
    echo json_encode(["error" => "Token no proporcionado"]);
    exit;
}
$token = $m[1];

// Decodificar JWT
$claveSecreta = $_ENV['JWT_KEY'];
try {
    $decoded = JWT::decode($token, new Key($claveSecreta, 'HS256'));
} catch (\Firebase\JWT\ExpiredException $e) {
    http_response_code(401);
    echo json_encode(["error" => "Token expirado"]);
    exit;
} catch (\Firebase\JWT\SignatureInvalidException $e) {
    http_response_code(401);
    echo json_encode(["error" => "Firma inválida"]);
    exit;
} catch (\UnexpectedValueException $e) {
    http_response_code(401);
    echo json_encode(["error" => "Token inválido o malformado"]);
    exit;
}

// Conexión BD
$conn = conexionn::obtenerConexion();
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Conexión fallida: " . $conn->connect_error]);
    exit;
}

// Leer y validar JSON
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["error" => "JSON inválido"]);
    exit;
}

$curso       = trim($input['cursoNombre']  ?? '');
$salon       = trim($input['salonNombre']  ?? '');
$sede        = trim($input['sedeNombre']   ?? '');
$id_profesor = filter_var($input['docenteId'] ?? null, FILTER_VALIDATE_INT);

if (empty($curso) || empty($salon) || empty($sede) || $id_profesor === false) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan campos obligatorios o inválidos"]);
    exit;
}

// Insertar
$sql = "INSERT INTO asignaciones (curso, salon, sede, id_profesor) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Error al preparar la consulta: " . $conn->error]);
    exit;
}
$stmt->bind_param("sssi", $curso, $salon, $sede, $id_profesor);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode([
        "mensaje" => "Asignación insertada correctamente",
        "id"      => $stmt->insert_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Error al insertar: " . $stmt->error]);
}

$stmt->close();
$conn->close();
