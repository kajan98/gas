<?php
include 'include/db.php';
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_POST['action'] === 'update_status') {
    try {
        $id = $_POST['id'];
        $status = $_POST['status'];
        
        // Update status
        $query = "UPDATE industrial_users SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            // If status is approved, send email
            if ($status === 'approved') {
                // Get user details
                $userQuery = "SELECT company_name, company_email, business_registration_number FROM industrial_users WHERE id = ?";
                $userStmt = $conn->prepare($userQuery);
                $userStmt->bind_param("i", $id);
                $userStmt->execute();
                $result = $userStmt->get_result();
                $user = $result->fetch_assoc();

                // Send email
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'gagbygas@gmail.com';
                $mail->Password = 'czzl qfge avup oxmc';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('gagbygas@gmail.com', 'Gas By Gas');
                $mail->addAddress($user['company_email']);
                $mail->isHTML(true);
                $mail->Subject = 'Account Approved - Gas By Gas';

                // Email body
                $emailBody = "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                    <h2 style='color: #0066cc;'>Welcome to Gas By Gas!</h2>
                    <p>Dear {$user['company_name']},</p>
                    
                    <p>We are pleased to inform you that your industrial user registration has been approved!</p>
                    
                    <p>You can now log in to your account using:</p>
                    <ul>
                        <li>Business Registration Number: {$user['business_registration_number']}</li>
                        <li>Your registered password</li>
                    </ul>
                    
                    <p>Visit our platform to start managing your gas cylinder orders and track your usage.</p>
                    
                    <p style='margin-top: 20px;'>Best regards,<br>The Gas By Gas Team</p>
                    
                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666;'>
                        <p>This is an automated message, please do not reply to this email.</p>
                    </div>
                </div>";

                $mail->Body = $emailBody;

                $mail->send();
            }

            $response = [
                'status' => 'success',
                'message' => 'Status updated successfully'
            ];
        } else {
            throw new Exception("Failed to update status");
        }
    } catch (Exception $e) {
        $response = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

echo json_encode($response);
?> 