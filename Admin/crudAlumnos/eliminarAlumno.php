<?php
include '../../conexion.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Responder OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data["id"] ?? null;
    $force = !empty($data["force"]);

    if (!$id) {
        echo json_encode(["error" => "ID no proporcionado"]);
        exit;
    }

    // Intento borrar directamente
    $query = $conn->prepare("DELETE FROM alumnos WHERE id = ?");
    $query->bind_param("i", $id);

    if ($query->execute()) {
        echo json_encode(["mensaje" => "Alumno eliminado correctamente"]);
        exit;
    }

    // Si falla, ¿por qué falla?
    $errno = $query->errno;
    if ($errno == 1451 && !$force) {
        // Error de FK constraint y no se pidió fuerza: devolvemos code específico
        echo json_encode([
            "error" => "No se puede eliminar porque el alumno está siendo referenciado en otras tablas.",
            "code"  => 1451
        ]);
        exit;
    }

    if ($errno == 1451 && $force) {
        // Borrar asistencia
        $del1 = $conn->prepare("DELETE FROM asistencia WHERE alumno_id = ?");
        $del1->bind_param("i", $id);
        $del1->execute();

        // Borrar alertas
        $del2 = $conn->prepare("DELETE FROM alertas WHERE alumno_id = ?");
        $del2->bind_param("i", $id);
        $del2->execute();

        // ...y cualquier otra tabla hija que tengas...

        // Ahora sí eliminamos el alumno
        $query2 = $conn->prepare("DELETE FROM alumnos WHERE id = ?");
        $query2->bind_param("i", $id);
        if ($query2->execute()) {
            echo json_encode([
                "mensaje" => "Alumno y todas sus referencias fueron eliminados correctamente (FORZADO)."
            ]);
        } else {
            echo json_encode([
                "error" => "Error al eliminar el alumno incluso tras forzar: " . $query2->error
            ]);
        }
        exit;
    }

    // Otro tipo de error
    echo json_encode(["error" => "No se pudo eliminar el alumno: (" . $errno . ") " . $query->error]);
}

$conn->close();
