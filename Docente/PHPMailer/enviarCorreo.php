<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Headers CORS - ESTO ES IMPORTANTE
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
    // Leer datos del JSON recibido
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    // Log para debugging
    error_log("Datos recibidos: " . $input);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON inválido: " . json_last_error_msg());
    }

    $email = $data['email'] ?? '';
    $subject = $data['subject'] ?? 'Notificación';
    $message = $data['message'] ?? 'Mensaje vacío';

    // Validación más robusta
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

    $mail = new PHPMailer(true);

    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'u21209622@gmail.com';
    $mail->Password = 'eiggslgbtcbrxrkp';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Configuración adicional para debugging
    $mail->SMTPDebug = 0; // Cambiar a 2 para debugging
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

    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Correo enviado correctamente",
        "email" => $email,
        "subject" => $subject
    ]);
} catch (Exception $e) {
    // Log del error
    error_log("Error al enviar correo: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error al enviar el correo",
        "details" => $e->getMessage()
    ]);
}
