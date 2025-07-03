<?php
require_once '../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
include '../../conexionn.php';

// 1. Cabeceras CORS y JSON
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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

// 4. Consulta con alias id_nombre_nivel y salon
$sql = "
SELECT
  c.id                    AS id_clase,
  c.profesor_id           AS id_profesor,
  p.nombre                AS nombre_profesor,
  c.curso_id              AS id_curso,
  cu.nombre               AS nombre_curso,
  a.sede_id               AS id_sede,
  s.nombre                AS nombre_sede,
  c.grado_id              AS id_grado,
  g.nombre                AS nombre_grado,
  g.id_nivel              AS id_nivel,
  n.nombre                AS nombre_nivel,
  CONCAT(g.nombre, ' - ', n.nombre) AS salon,
  CONCAT(p.nombre, ' ', p.apellido1) AS nombres_profe

FROM clases c
LEFT JOIN profesores p ON p.id      = c.profesor_id
LEFT JOIN cursos     cu ON cu.id     = c.curso_id
LEFT JOIN aulas      a  ON a.id      = c.aula_id
LEFT JOIN sedes      s  ON s.id      = a.sede_id
LEFT JOIN grados     g  ON g.id      = c.grado_id
LEFT JOIN niveles    n  ON n.id      = g.id_nivel
ORDER BY c.id ASC
";

// 5. Ejecutar consulta
$result = $conn->query($sql);
if ($result === false) {
    http_response_code(500);
    echo json_encode(["error" => "Error en la consulta: " . $conn->error]);
    exit;
}

// 6. Construir array de salida
$lista = [];
while ($row = $result->fetch_assoc()) {
    $lista[] = $row;
}

// 7. Devolver JSON
echo json_encode($lista);

// 8. Cerrar conexión
$result->free();
$conn->close();
?>
