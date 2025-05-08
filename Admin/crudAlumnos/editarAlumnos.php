<?php
include '../../conexion.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['id']) && !empty($data['nombre']) && !empty($data['apellido1']) && !empty($data['apellido2'])) {
    $id = $conn->real_escape_string($data['id']);
    $nombre = $conn->real_escape_string($data['nombre']);
    $apellido1 = $conn->real_escape_string($data['apellido1']);
    $apellido2 = $conn->real_escape_string($data['apellido2']);

    $sql = "UPDATE control_academico.alumnos SET nombre='$nombre', apellido1='$apellido1', apellido2='$apellido2' WHERE id='$id'";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["mensaje" => "Alumno actualizado correctamente"]);
    } else {
        echo json_encode(["error" => "Error al actualizar el alumno: " . $conn->error]);
    }
} else {
    echo json_encode(["error" => "Datos incompletos"]);
}

$conn->close();
?>