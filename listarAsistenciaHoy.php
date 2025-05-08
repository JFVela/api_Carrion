<?php
include 'conexion.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
date_default_timezone_set('America/Lima');
$fecha = date('Y-m-d');

$sql = "SELECT a.alumno_id, a.estado, a.observaciones
        FROM asistencia a
        WHERE a.fecha = '$fecha'";


$result = $conn->query($sql);

$datos = [];
while ($fila = $result->fetch_assoc()) {
    $datos[$fila['alumno_id']] = [
        'estado'       => $fila['estado'][0],      // “PUNTUAL” → “P”, etc.
        'observacion'  => $fila['observaciones'],
    ];
}

echo json_encode($datos);
