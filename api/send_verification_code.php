<?php
include 'include/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $code = $_POST['code'];
        
        // Set expiration to 3 minutes from now
        $created_at = date('Y-m-d H:i:s');
        $expires_at = date('Y-m-d H:i:s', strtotime('+3 minutes'));

        // Insert verification code
        $query = "INSERT INTO verification_codes (email, phone, code, created_at, expires_at, is_used) 
                 VALUES (?, ?, ?, ?, ?, 0)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssss", $email, $phone, $code, $created_at, $expires_at);
        
        if ($stmt->execute()) {
            // Send email
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'gagbygas@gmail.com'; // Your Gmail
            $mail->Password = 'czzl qfge avup oxmc'; // Your Gmail password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('gagbygas@gmail.com', 'Gas Delivery System');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Verification Code';
            $mail->Body = "Your verification code is: <b>$code</b><br>This code will expire in 3 minutes.";

            if ($mail->send()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Verification code sent successfully. Code will expire in 3 minutes.'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to send email'
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to save verification code'
            ]);
        }
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?> 