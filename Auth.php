<?php
require 'vendor/autoload.php';
require_once 'conexionn.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
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


        $payload = [
            'iat'=>time(),
            'exp' => time() + (60 * 60),
            'data'=>[
 "usuario" => $user['usuario'],
             
                "id" => $user['id'],
                "rol"=>$user['id_rol']
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