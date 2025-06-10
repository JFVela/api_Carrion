<?php
require_once './vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
include './conexionn.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$conn = conexionn::obtenerConexion();

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Validación de token
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

// Obtener filtro de nivel desde la query
$nivel = isset($_GET['id_nivel']) ? intval($_GET['id_nivel']) : null;

if ($nivel === 1 || $nivel === 2) {
    // Solo grados según el nivel (primaria o secundaria)
    $stmt = $conn->prepare("SELECT id, id_nivel, nombre FROM grados WHERE id_nivel = ?");
    $stmt->bind_param("i", $nivel);
} else {
    // Todos los grados si no se pasa parámetro válido
    $stmt = $conn->prepare("SELECT id, id_nivel, nombre FROM grados");
}

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $grados = [];

    while ($row = $result->fetch_assoc()) {
        $grados[] = $row;
    }

    echo json_encode($grados);
} else {
    echo json_encode(["error" => "Error al obtener los grados: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>