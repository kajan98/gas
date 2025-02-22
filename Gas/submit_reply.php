<?php
session_start();
include 'include/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure PHPMailer is properly included

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

if (isset($_POST['complaint_id']) && isset($_POST['reply'])) {
    $complaint_id = intval($_POST['complaint_id']);
    $reply = trim($_POST['reply']);
    $manager_id = $_SESSION['user_id'];

    try {
        // Get manager's name
        $managerQuery = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $managerQuery->bind_param("i", $manager_id);
        $managerQuery->execute();
        $managerResult = $managerQuery->get_result();
        $managerData = $managerResult->fetch_assoc();
        $replied_by = $managerData['name'];

        // Get complaint details
        $complaintQuery = $conn->prepare("
            SELECT c.*, o.name as outlet_name 
            FROM complaints c
            LEFT JOIN outlets o ON c.outlet_id = o.id
            WHERE c.id = ?
        ");
        $complaintQuery->bind_param("i", $complaint_id);
        $complaintQuery->execute();
        $complaintResult = $complaintQuery->get_result();
        $complaintData = $complaintResult->fetch_assoc();

        // Begin transaction
        $conn->begin_transaction();

        // Update complaint with reply
        $updateQuery = $conn->prepare("
            UPDATE complaints 
            SET status = 'replied',
                reply_text = ?,
                replied_by = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $updateQuery->bind_param("ssi", $reply, $replied_by, $complaint_id);
        $updateQuery->execute();

        // Prepare and send email
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'gagbygas@gmail.com';
            $mail->Password = 'czzl qfge avup oxmc';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('gagbygas@gmail.com', 'Gas By Gas');
            $mail->addAddress($complaintData['email'], $complaintData['name']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "Reply to your complaint - " . $complaintData['subject'];
            
            $emailBody = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Response to Your Complaint</h2>
                    </div>
                    <div class='content'>
                        <p>Dear " . htmlspecialchars($complaintData['name']) . ",</p>
                        
                        <p>Thank you for bringing this matter to our attention. This is in response to your complaint regarding:</p>
                        <p><strong>Subject:</strong> " . htmlspecialchars($complaintData['subject']) . "</p>
                        <p><strong>Outlet:</strong> " . htmlspecialchars($complaintData['outlet_name']) . "</p>
                        
                        <p><strong>Our Response:</strong></p>
                        <p>" . nl2br(htmlspecialchars($reply)) . "</p>
                        
                        <p>If you have any further questions or concerns, please don't hesitate to contact us.</p>
                        
                        <p>Best regards,<br>
                        " . htmlspecialchars($replied_by) . "<br>
                        Manager, " . htmlspecialchars($complaintData['outlet_name']) . "</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated response. Please do not reply to this email.</p>
                    </div>
                </div>
            </body>
            </html>";

            $mail->Body = $emailBody;

            // Send email
            $mail->send();
            
            // If email sent successfully, commit transaction
            $conn->commit();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Reply sent successfully and email notification sent'
            ]);

        } catch (Exception $e) {
            // If email fails, rollback transaction
            $conn->rollback();
            throw new Exception("Failed to send email: " . $mail->ErrorInfo);
        }

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields'
    ]);
}
?> 