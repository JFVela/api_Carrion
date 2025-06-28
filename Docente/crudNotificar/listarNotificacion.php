<?php
require_once '../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

include '../../conexionn.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Validar JWT
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

// Conexión
$conn = conexionn::obtenerConexion();
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Conexión fallida: " . $conn->connect_error]);
    exit;
}

// Consulta con JOIN para mostrar nombres legibles
$sql = "SELECT 
    n.id,
    n.mensaje,
    n.hora_notificacion,
    n.emisor_tipo,
    CONCAT(a.nombre, ' ', a.apellido1, ' ', a.apellido2) AS alumno,
    CASE 
        WHEN n.emisor_tipo = 'PROFESOR' THEN (SELECT CONCAT(nombre, ' ', apellido1) FROM profesores WHERE id = n.emisor_id)
        WHEN n.emisor_tipo = 'ADMIN' THEN (SELECT CONCAT(nombre, ' ', apellido1) FROM administradores WHERE id = n.emisor_id)
        ELSE 'Desconocido'
    END AS emisor
FROM notificaciones n
JOIN alumnos a ON n.alumno_id = a.id
ORDER BY n.hora_notificacion DESC";

$resultado = $conn->query($sql);
$datos = [];

if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $datos[] = $fila;
    }
    echo json_encode($datos);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Error en la consulta: " . $conn->error]);
}

$conn->close();
