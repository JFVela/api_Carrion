<?php
require_once '../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
include '../../conexionn.php';


header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Validar JWT
$hdrs = function_exists('apache_request_headers') 
    ? apache_request_headers() 
    : getallheaders();
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

// Conexión
$conn = conexionn::obtenerConexion();
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Conexión fallida"]);
    exit;
}

// Leer filtro nivel (columna `nivel`)
$nivel = null;
if (isset($_GET['nivel'])) {
    $nivel = filter_var($_GET['nivel'], FILTER_VALIDATE_INT);
    if ($nivel === false) {
        http_response_code(400);
        echo json_encode(["error" => "Parámetro nivel inválido"]);
        exit;
    }
}

// Construir consulta dinámicamente
if ($nivel !== null) {
    $sql  = "SELECT id, nombre, nivel, area
               FROM cursos
              WHERE nivel = ?
           ORDER BY nombre";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $nivel);
} else {
    $sql  = "SELECT id, nombre, nivel, area
               FROM cursos
           ORDER BY nivel, nombre";
    $stmt = $conn->prepare($sql);
}

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Error al preparar consulta: " . $conn->error]);
    exit;
}

// Ejecutar
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["error" => "Error al ejecutar consulta"]);
    exit;
}

// Obtener resultados
$res = $stmt->get_result();
$cursos = [];
while ($row = $res->fetch_assoc()) {
    $cursos[] = $row;
}

// Devolver JSON
echo json_encode($cursos);

// Cerrar
$stmt->close();
$conn->close();
?>
