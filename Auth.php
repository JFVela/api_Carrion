<?php
require 'vendor/autoload.php';
require_once 'conexionn.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");
// Manejo de preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(); // Muy importante para que no continúe ejecutando el script
}




$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$key = $_ENV['JWT_KEY'];

// Obtener los datos del cuerpo de la petición (JSON)
$input = json_decode(file_get_contents("php://input"), true);

//verificamos que los valores existan
if (!isset($input['usuario']) || !isset($input['password'])) {
    echo json_encode(["error" => "Faltan campos requeridos"]);
    exit;
}

//almacenamos por separado el usuario y contraseña
$usuario = $input['usuario'];
$password = $input['password'];
//obtenemos una conexion
$conn = conexionn::obtenerConexion();


// Consulta segura usando prepared statements
$stmt = $conn->prepare("SELECT id, usuario,contrasenia,id_rol FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['contrasenia'])) {

        if ($user['id_rol'] == 1) {
            $stmt1 = $conn->prepare("SELECT a.nombre, a.apellido1, a.apellido2, r.nombre AS rol FROM administradores a JOIN usuarios u ON a.id_usuario = u.id JOIN roles r ON u.id_rol = r.id WHERE u.usuario = ?");
            $stmt1->bind_param("s", $usuario);
            $stmt1->execute();
            $result1 = $stmt1->get_result();
            $datos = $result1->fetch_assoc();
        } else if ($user['id_rol'] == 2) {
            $stmt1 = $conn->prepare("SELECT a.nombre, a.apellido1, a.apellido2, r.nombre AS rol FROM profesores  a JOIN usuarios u ON a.id_usuario = u.id JOIN roles r ON u.id_rol = r.id WHERE u.usuario = ?");
            $stmt1->bind_param("s", $usuario);
            $stmt1->execute();
            $result1 = $stmt1->get_result();
            $datos = $result1->fetch_assoc();
        } else {
            $stmt1 = $conn->prepare("SELECT a.nombre, a.apellido1, a.apellido2, r.nombre AS rol FROM alumnos a JOIN usuarios u ON a.id_usuario = u.id JOIN roles r ON u.id_rol = r.id WHERE u.usuario = ?");
            $stmt1->bind_param("s", $usuario);
            $stmt1->execute();
            $result1 = $stmt1->get_result();
            $datos = $result1->fetch_assoc();
        }


        $payload = [
            'iat' => time(),
            'exp' => time() + (60 * 60),
            'data' => [
                "usuario" => $user['usuario'],
                "nombre" => $datos['nombre'],
                "apellido1" => $datos['apellido1'],
                "apellido2" => $datos['apellido2'],
                "rol" => $datos['rol']
            ]
        ];

        //creamos el jwt
        $jwt = JWT::encode($payload, $key, 'HS256');

        echo json_encode([
            "token" => $jwt,
            "message" => "Autenticación exitosa"
        ], JSON_UNESCAPED_UNICODE);


    } else {
        echo json_encode(["success" => false, "error" => "Contraseña incorrecta"], JSON_UNESCAPED_UNICODE);
    }

} else {
    echo json_encode(["success" => false, "error" => "Usuario no encontrado"]);
}