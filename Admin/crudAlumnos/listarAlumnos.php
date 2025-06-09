<?php
require_once '../../vendor/autoload.php'; // Ruta a autoload, ajusta según tu estructura
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

include '../../conexionn.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


// Manejo de preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(); // Muy importante para que no continúe ejecutando el script
}


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
// Obtener conexión
$conn = conexionn::obtenerConexion();

// Verificar si la conexión fue exitosa
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Conexión fallida: " . $conn->connect_error]);
    exit;
}

// Ejecutar consulta
$sql = "SELECT 
    a.id,
    a.nombre,
    a.apellido1,
    a.apellido2,
    CONCAT(g.nombre, ' - ', n.nombre) AS grado,
    s.nombre AS sede
FROM alumnos a
JOIN grados g ON a.id_grado = g.id
JOIN niveles n ON g.id_nivel = n.id
JOIN sedes s ON a.id_sede = s.id order by id asc";

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

// Cerrar conexión
$conn->close();
