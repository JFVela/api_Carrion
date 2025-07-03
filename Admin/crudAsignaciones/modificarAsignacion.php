<?php
require_once '../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
include '../../conexionn.php';

// 1. Cabeceras CORS y JSON
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
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
    echo json_encode(["error" => "Conexión fallida: " . $conn->connect_error]);
    exit;
}

// 4. Obtener ID de clase de la query string
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "ID de clase no proporcionado"]);
    exit;
}
$id_clase = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if ($id_clase === false) {
    http_response_code(400);
    echo json_encode(["error" => "ID de clase inválido"]);
    exit;
}

// 5. Leer JSON de PUT y obtener id_profesor
$input = json_decode(file_get_contents('php://input'), true);
$id_profesor = $input['id_profesor'] ?? null;
$id_profesor = filter_var($id_profesor, FILTER_VALIDATE_INT);
if ($id_profesor === false || $id_profesor <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "ID de profesor inválido o no proporcionado"]);
    exit;
}

// 6. Preparar y ejecutar UPDATE
$stmt = $conn->prepare("UPDATE clases SET profesor_id = ? WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Error al preparar consulta: " . $conn->error]);
    exit;
}
$stmt->bind_param("ii", $id_profesor, $id_clase);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["error" => "Error al ejecutar actualización: " . $stmt->error]);
    exit;
}

// 7. Verificar si se actualizó realmente
if ($stmt->affected_rows === 0) {
    http_response_code(404);
    echo json_encode(["error" => "No existe la clase o no hubo cambio en el profesor"]);
    exit;
}

// 8. Responder con éxito
echo json_encode([
    "mensaje"     => "Profesor actualizado correctamente",
    "id_clase"    => $id_clase,
    "id_profesor" => $id_profesor
]);

// 9. Cerrar recursos
$stmt->close();
$conn->close();
?>
