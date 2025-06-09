<?php
include '../../conexionn.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

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
JOIN sedes s ON a.id_sede = s.id";

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
