<?php
include 'include/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';
$email = $_POST['email'];
$phone = $_POST['phone'];

// Check if email and phone exist in consumer table
$query = "SELECT id, name FROM consumer WHERE email = ? AND phone = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $email, $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Email and phone number do not match our records'
    ]);
    exit;
}

$user = $result->fetch_assoc();

// Generate code with 3 capital letters and 3 numbers
$letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
$numbers = '0123456789';

$code = '';
for ($i = 0; $i < 3; $i++) {
    $code .= $letters[mt_rand(0, strlen($letters) - 1)];
}
for ($i = 0; $i < 3; $i++) {
    $code .= $numbers[mt_rand(0, strlen($numbers) - 1)];
}

// Store code in verification_codes table with 3-minute expiration
$query = "INSERT INTO verification_codes (email, phone, code, expires_at) 
          VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 3 MINUTE))";
$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $email, $phone, $code);
$stmt->execute();

// Send email with verification code
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'gagbygas@gmail.com';
    $mail->Password   = 'czzl qfge avup oxmc';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('gagbygas@gmail.com', 'Gas Management System');
    $mail->addAddress($email, $user['name']);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Verification Code';
    
    $emailBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #1a73e8;'>Password Reset Verification</h2>
        <p>Dear {$user['name']},</p>
        <p>Your verification code is: <strong style='font-size: 24px; color: #1a73e8;'>{$code}</strong></p>
        <p style='color: #d93025;'><strong>This code will expire in 3 minutes.</strong></p>
        <p>If you didn't request this code, please ignore this email.</p>
        <hr>
        <p style='font-size: 12px; color: #666;'>This is an automated message, please do not reply.</p>
    </div>";
    
    $mail->Body = $emailBody;
    $mail->AltBody = "Your verification code is: {$code}\nThis code will expire in 3 minutes.";

    $mail->send();
    
    echo json_encode([
        'success' => true,
        'message' => 'Verification code sent. Code will expire in 3 minutes.',
        'email' => $email
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => "Failed to send verification code. Error: {$mail->ErrorInfo}"
    ]);
}
?> 