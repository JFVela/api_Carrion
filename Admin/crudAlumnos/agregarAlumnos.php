<?php
include '../../conexion.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['nombre']) && !empty($data['apellido1']) && !empty($data['apellido2'])) {
    $nombre = $conn->real_escape_string($data['nombre']);
    $apellido1 = $conn->real_escape_string($data['apellido1']);
    $apellido2 = $conn->real_escape_string($data['apellido2']);

    $sql = "INSERT INTO `control_academico`.`alumnos` (`nombre`, `apellido1`, `apellido2`) VALUES ('$nombre', '$apellido1', '$apellido2')";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["mensaje" => "Alumno agregado correctamente"]);
    } else {
        echo json_encode(["error" => "Error al agregar el alumno: " . $conn->error]);
    }
} else {
    echo json_encode(["error" => "Datos incompletos"]);
}

$conn->close();
?>