<?php
require_once '../../vendor/autoload.php';
include '../../conexionn.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
$conn = conexionn::obtenerConexion();

// Preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificación del token
$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["error" => "Token no proporcionado"]);
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
$claveSecreta = $_ENV['JWT_KEY']; // Asegúrate que esté definido

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

// Procesar solicitud PATCH
if ($_SERVER["REQUEST_METHOD"] === "PATCH") {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data["id"] ?? null;

    if (!$id) {
        echo json_encode(["error" => "ID del alumno no proporcionado"]);
        exit;
    }

    // Verificar si el alumno existe
    $check = $conn->prepare("SELECT activo FROM alumnos WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["error" => "Alumno no encontrado"]);
        exit;
    }

    $row = $result->fetch_assoc();
    if ($row['activo'] == 0) {
        echo json_encode(["mensaje" => "El alumno ya está desactivado"]);
        exit;
    }

    // Desactivar al alumno (soft delete)
    $stmt = $conn->prepare("UPDATE alumnos SET activo = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["mensaje" => "Alumno desactivado correctamente"]);
    } else {
        echo json_encode(["error" => "No se pudo desactivar el alumno: " . $stmt->error]);
    }

    $stmt->close();
    $check->close();
}

$conn->close();
