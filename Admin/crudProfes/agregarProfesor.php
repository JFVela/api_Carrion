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

$data = json_decode(file_get_contents("php://input"), true);
$conn = conexionn::obtenerConexion();

if (
    !empty($data['dni']) &&
    !empty($data['nombre']) &&
    !empty($data['apellido1']) &&
    !empty($data['apellido2']) &&
    !empty($data['id_sede']) // Asegúrate de que se envíe id_sede
) {
    $dni = $conn->real_escape_string($data['dni']);
    $nombre = $conn->real_escape_string($data['nombre']);
    $apellido1 = $conn->real_escape_string($data['apellido1']);
    $apellido2 = $conn->real_escape_string($data['apellido2']);
    $id_sede = (int) $data['id_sede'];

    // Contraseña por defecto ya hasheada
    $passwordDefault = '$2y$10$YriYbf5Fay0rB/AcN9DkbujmtQo3uPqEPzhbbhUMiSqZvzFx07jdW';

    $conn->begin_transaction();

    try {
        // Insertar usuario (rol 2 = profesor)
        $stmtUser = $conn->prepare("INSERT INTO usuarios (usuario, contrasenia, id_rol) VALUES (?, ?, 2)");
        if (!$stmtUser)
            throw new Exception("Error preparando consulta usuarios: " . $conn->error);

        $stmtUser->bind_param("ss", $dni, $passwordDefault);
        if (!$stmtUser->execute())
            throw new Exception("Error ejecutando consulta usuarios: " . $stmtUser->error);

        $id_usuario = $conn->insert_id;
        $stmtUser->close();

        // Insertar profesor con id_sede
        $stmtProfesor = $conn->prepare("INSERT INTO profesores (dni, nombre, apellido1, apellido2, id_sede, id_usuario) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmtProfesor)
            throw new Exception("Error preparando consulta profesores: " . $conn->error);

        $stmtProfesor->bind_param("ssssii", $dni, $nombre, $apellido1, $apellido2, $id_sede, $id_usuario);
        if (!$stmtProfesor->execute())
            throw new Exception("Error ejecutando consulta profesores: " . $stmtProfesor->error);

        $stmtProfesor->close();
        $conn->commit();

        echo json_encode([
            'mensaje' => 'Profesor y usuario creados correctamente',
            'id_usuario' => $id_usuario
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan datos requeridos (dni, nombre, apellido1, apellido2, id_sede)']);
}

$conn->close();
?>
