<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once '../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

include '../../conexionn.php';

// Headers CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit();
}

try {
    // Validar token JWT
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(["error" => "Token no proporcionado"]);
        exit();
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $claveSecreta = $_ENV['JWT_KEY'];

    try {
        $decoded = JWT::decode($token, new Key($claveSecreta, 'HS256'));
    } catch (\Firebase\JWT\ExpiredException $e) {
        http_response_code(401);
        echo json_encode(["error" => "Token expirado"]);
        exit();
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["error" => "Token inválido"]);
        exit();
    }

    // Leer datos del JSON recibido
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    // Log para debugging
    error_log("Datos recibidos: " . $input);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON inválido: " . json_last_error_msg());
    }

    // Datos para el correo
    $email = $data['email'] ?? '';
    $subject = $data['subject'] ?? 'Notificación';
    $message = $data['message'] ?? 'Mensaje vacío';

    // Datos para la base de datos
    $alumno_id = $data['alumno_id'] ?? '';
    $emisor_tipo = $data['emisor_tipo'] ?? '';
    $emisor_id = $data['emisor_id'] ?? '';

    // Validación de datos del correo
    if (empty($email)) {
        throw new Exception("El email es requerido");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email inválido");
    }

    if (empty($subject)) {
        throw new Exception("El asunto es requerido");
    }

    if (empty($message)) {
        throw new Exception("El mensaje es requerido");
    }

    // Validación de datos para la BD
    if (empty($alumno_id)) {
        throw new Exception("El ID del alumno es requerido");
    }

    if (empty($emisor_tipo)) {
        throw new Exception("El tipo de emisor es requerido");
    }

    if (empty($emisor_id)) {
        throw new Exception("El ID del emisor es requerido");
    }

    // PASO 1: Enviar el correo
    $mail = new PHPMailer(true);

    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'u21209622@gmail.com';
    $mail->Password = 'eiggslgbtcbrxrkp';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Configuración adicional
    $mail->SMTPDebug = 0;
    $mail->Debugoutput = 'error_log';

    // Remitente y destinatario
    $mail->setFrom('u21209622@gmail.com', 'Sistema Notificaciones');
    $mail->addAddress($email);

    // Contenido
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = nl2br(htmlspecialchars($message));
    $mail->CharSet = 'UTF-8';

    // Enviar el correo
    $mail->send();

    // PASO 2: Si el correo se envió exitosamente, guardar en la base de datos
    $conn = conexionn::obtenerConexion();

    // Preparar la consulta (sin asunto ni fecha_envio)
    $stmt = $conn->prepare("INSERT INTO notificaciones (alumno_id, emisor_tipo, emisor_id, mensaje) VALUES (?, ?, ?, ?)");

    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }

    // Convertir tipos
    $alumno_id = intval($alumno_id);
    $emisor_id = intval($emisor_id);
    $emisor_tipo = $conn->real_escape_string($emisor_tipo);
    $mensaje_bd = $conn->real_escape_string($message);

    // Bind parameters (sin asunto)
    $stmt->bind_param("isis", $alumno_id, $emisor_tipo, $emisor_id, $mensaje_bd);

    // Ejecutar la consulta
    if (!$stmt->execute()) {
        throw new Exception("Error al insertar en la base de datos: " . $stmt->error);
    }

    $notificacion_id = $conn->insert_id;
    $stmt->close();
    $conn->close();

    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Correo enviado y notificación guardada correctamente",
        "email" => $email,
        "notificacion_id" => $notificacion_id
    ]);
} catch (Exception $e) {
    // Log del error
    error_log("Error al procesar notificación: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error al procesar la notificación",
        "details" => $e->getMessage()
    ]);
}
