<?php
require_once '../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

include '../../conexionn.php';

/* ------------------ Cabeceras CORS ------------------ */
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ------------------ 🔒 Validar JWT ------------------ */
$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["error" => "Token no proporcionado"]);
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
try {
    JWT::decode($token, new Key($_ENV['JWT_KEY'], 'HS256'));
} catch (\Firebase\JWT\ExpiredException $e) {
    http_response_code(401);
    echo json_encode(["error" => "Token expirado"]);
    exit;
} catch (Throwable $e) {
    http_response_code(401);
    echo json_encode(["error" => "Token inválido"]);
    exit;
}

/* ------------------ Conexión ------------------ */
$conn = conexionn::obtenerConexion();
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Conexión fallida: {$conn->connect_error}"]);
    exit;
}

/* ------------------ Consulta con JOINs ------------------ */
$sql = "
    SELECT
      c.id            AS clase_id,
      cu.nombre       AS curso,
      p.nombre        AS profesor,
      g.nombre        AS grado,
      a.nombre        AS aula,
      c.anio
    FROM clases c
    INNER JOIN cursos   cu ON c.curso_id   = cu.id
    INNER JOIN profesores p ON c.profesor_id = p.id
    INNER JOIN grados   g  ON c.grado_id    = g.id
    INNER JOIN aulas    a  ON c.aula_id     = a.id
    ORDER BY c.anio desc, c.id
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Error al preparar la consulta: {$conn->error}"]);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

$clases = [];
while ($row = $result->fetch_assoc()) {
    $clases[] = [
        "id"       => $row['clase_id'],
        "profesor" => $row['profesor'],
        "curso"    => $row['curso'],
        "grado"    => $row['grado'],
      //  "aula"     => $row['aula'],
        "anio"     => intval($row['anio']),
    ];
}

echo json_encode([
    "total"  => count($clases),
    "clases" => $clases
]);

$stmt->close();
$conn->close();
