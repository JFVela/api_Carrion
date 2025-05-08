<?php
include 'conexion.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Recibe los datos en formato JSON desde el frontend
$data = json_decode(file_get_contents("php://input"), true);

// Validar que sea un array y tenga datos
if (!is_array($data) || empty($data)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos o vacíos.']);
    exit;
}

$respuestas = [];

foreach ($data as $registro) {
    $id = $registro["alumno_id"];
    $estado = $registro["estado"];
    $observacion = $registro["observaciones"];

    // Verifica si ya existe un registro hoy para el alumno
    $verificar = $conn->prepare("SELECT id FROM asistencia WHERE alumno_id = ? AND fecha = CURDATE()");
    if (!$verificar) {
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit;
    }
    $verificar->bind_param("i", $id);
    $verificar->execute();
    $verificar->store_result();

    if ($verificar->num_rows > 0) {
        // Ya existe, actualizar
        $update = $conn->prepare("UPDATE asistencia SET estado = ?, observaciones = ? WHERE alumno_id = ? AND fecha = CURDATE()");
        if (!$update) {
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit;
        }
        // Aquí se corrige agregando los tipos de datos ("ssi": string, string, integer)
        $update->bind_param("ssi", $estado, $observacion, $id);
        $exito = $update->execute();

        $respuestas[] = [
            "alumno_id" => $id,
            "accion"     => "actualizado",
            "exito"      => $exito,
            "error"      => $update->error
        ];
    } else {
        // No existe, insertar
        $insert = $conn->prepare("INSERT INTO asistencia (alumno_id, estado, observaciones, fecha) VALUES (?, ?, ?, CURDATE())");
        if (!$insert) {
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit;
        }
        $insert->bind_param("iss", $id, $estado, $observacion);
        $exito = $insert->execute();

        $respuestas[] = [
            "alumno_id" => $id,
            "accion"     => "insertado",
            "exito"      => $exito,
            "error"      => $insert->error
        ];
    }
}

echo json_encode(['success' => true, 'resultados' => $respuestas]);
