<?php
require_once '../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

include '../../conexionn.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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
    JWT::decode($token, new Key($claveSecreta, 'HS256'));
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["error" => "Token inválido"]);
    exit;
}

$sede = $_GET['sede'] ?? null;
$grado = $_GET['grado'] ?? null;
$fechaHoy = date("Y-m-d");

if (!$sede || !$grado) {
    http_response_code(400);
    echo json_encode(["error" => "Sede y grado requeridos"]);
    exit;
}

$conn = conexionn::obtenerConexion();

$sql = " SELECT a.id, a.nombre, a.apellido1, a.apellido2, 
       (SELECT estado FROM asistencia 
        WHERE alumno_id = a.id AND DATE(fecha) = ?
        LIMIT 1) AS estado_asistencia
        FROM alumnos a
        WHERE a.id_sede = ? AND a.id_grado = ? AND a.activo = 1
        ORDER BY a.apellido1, a.apellido2
        ";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $fechaHoy, $sede, $grado);
$stmt->execute();
$result = $stmt->get_result();

$alumnos = [];
while ($row = $result->fetch_assoc()) {
    $alumnos[] = $row;
}
echo json_encode($alumnos);
$stmt->close();
$conn->close();
