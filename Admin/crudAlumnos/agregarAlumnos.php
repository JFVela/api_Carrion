<?php
require_once '../../vendor/autoload.php'; // Ruta a autoload, ajusta según tu estructura
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
    exit(); // Muy importante para que no continúe ejecutando el script
}
$key = $_ENV['JWT_KEY'];

// 🔒 Validar token JWT
$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["error" => "Token no proporcionado"]);
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
$claveSecreta = $_ENV['JWT_KEY']; // Usa la misma que usas al generarlo

try {
    $decoded = JWT::decode($token, new Key($claveSecreta, 'HS256'));

    // ✅ El token es válido, continúa
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
    !empty($data['grado']) &&
    !empty($data['sede']) &&
    !empty($data['correo'])
) {
    $dni = $conn->real_escape_string($data['dni']);
    $nombre = $conn->real_escape_string($data['nombre']);
    $apellido1 = $conn->real_escape_string($data['apellido1']);
    $apellido2 = $conn->real_escape_string($data['apellido2']);
    $id_grado = $conn->real_escape_string($data['grado']);
    $id_sede = $conn->real_escape_string($data['sede']);
    $correo = $conn->real_escape_string($data['correo']);

    $passwordDefault = password_hash($dni, PASSWORD_DEFAULT);
    
    $conn->begin_transaction();

    try {
        // Insertar usuario
        $stmtUser = $conn->prepare("INSERT INTO usuarios (usuario, contrasenia,id_rol) VALUES (?, ?,3)");
        if (!$stmtUser)
            throw new Exception("Error preparando consulta usuarios: " . $conn->error);

        $stmtUser->bind_param("ss", $dni, $passwordDefault);
        if (!$stmtUser->execute())
            throw new Exception("Error ejecutando consulta usuarios: " . $stmtUser->error);

        $id_usuario = $conn->insert_id;
        $stmtUser->close();

        // Insertar alumno (ahora con correo)
        $stmtAlumno = $conn->prepare("INSERT INTO alumnos (nombre, apellido1, apellido2, id_usuario, id_grado, id_sede, dni, correo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmtAlumno)
            throw new Exception("Error preparando consulta alumnos: " . $conn->error);

        $stmtAlumno->bind_param("sssiiiss", $nombre, $apellido1, $apellido2, $id_usuario, $id_grado, $id_sede, $dni, $correo);
        if (!$stmtAlumno->execute())
            throw new Exception("Error ejecutando consulta alumnos: " . $stmtAlumno->error);

        $stmtAlumno->close();

        $conn->commit();

        echo json_encode([
            'mensaje' => 'Alumno y usuario creados correctamente',
            'username' => $dni,
            'id_usuario' => $id_usuario
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
$conn->close();
