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

// Procesar entrada
$data = json_decode(file_get_contents("php://input"), true);
$conn = conexionn::obtenerConexion();

// Validar que estén todos los campos requeridos
if (!empty($data['nombreCurso']) && !empty($data['nivel']) && !empty($data['area'])) {
    $nombre = $conn->real_escape_string($data['nombreCurso']);
    $nivel = $conn->real_escape_string($data['nivel']);
    $area = $conn->real_escape_string($data['area']);

    try {
        $stmt = $conn->prepare("INSERT INTO cursos (nombre, nivel, area) VALUES (?, ?, ?)");
        if (!$stmt)
            throw new Exception("Error preparando consulta: " . $conn->error);

        $stmt->bind_param("sss", $nombre, $nivel, $area);
        if (!$stmt->execute())
            throw new Exception("Error ejecutando consulta: " . $stmt->error);

        $id_curso = $conn->insert_id;
        $stmt->close();

        echo json_encode([
            'mensaje' => 'Curso creado correctamente',
            'id_curso' => $id_curso
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan campos requeridos (nombre, nivel, area)']);
}

$conn->close();
?>
