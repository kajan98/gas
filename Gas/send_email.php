<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';

function sendEmail($data) {
    if (empty($data['user_email'])) {
        return "Email address is empty";
    }

    if (!filter_var($data['user_email'], FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format";
    }

    try {
        $mail = new PHPMailer(true);

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
        $mail->setFrom('gagbygas@gmail.com', 'Gas Delivery System');
        $mail->addAddress($data['user_email']);

        // Content
        $mail->isHTML(true);
        
        // Set subject and content based on status
        switch($data['status']) {
            case 'allocated':
                $mail->Subject = 'Gas Cylinder Delivery Allocated';
                $statusMessage = "Your gas cylinder delivery has been allocated and is ready for pickup.";
                $actionRequired = "Please come to hand over the empty cylinder and payment.";
                break;
                
            case 'reallocated':
                $mail->Subject = 'Gas Cylinder Delivery Reallocated';
                $statusMessage = "Your gas cylinder delivery has been reallocated to a new time slot.";
                $actionRequired = "Please note the new pickup date has been updated as per your request.";
                break;
                
            case 'completed':
                $mail->Subject = 'Gas Cylinder Delivery Completed';
                $statusMessage = "Your gas cylinder delivery has been completed. Thank you for your business!";
                $actionRequired = "We hope you are satisfied with our service. Please visit us again!";
                break;
                
            case 'cancelled':
                $mail->Subject = 'Gas Cylinder Delivery Cancelled';
                $statusMessage = "Your gas cylinder delivery has been cancelled.";
                $actionRequired = "please note that your request has been cancelled";
                break;
                
            case 'reminder':
                $mail->Subject = 'Reminder: Gas Cylinder Pickup Tomorrow';
                $statusMessage = "This is a friendly reminder that your gas cylinder pickup is scheduled for tomorrow.";
                $actionRequired = "Please ensure you visit our outlet tomorrow to complete your gas cylinder pickup.";
                break;
                
            default:
                $mail->Subject = 'Gas Cylinder Delivery Update';
                $statusMessage = "There has been an update to your gas cylinder delivery.";
                $actionRequired = "Please check your delivery status in your account.";
        }

      

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2 style='color: #2c3e50;'>{$mail->Subject}</h2>
                <div style='background-color: #f9f9f9; padding: 15px; border-radius: 5px;'>
                    <p style='color: #34495e;'><strong>{$statusMessage}</strong></p>
                    <p><strong>Token ID:</strong> {$data['token_id']}</p>
                    <p><strong>Pickup Date:</strong> {$data['pickup_date']}</p>
                   
                    
                    <p style='margin-top: 15px; color: #e67e22;'><strong>Action Required:</strong> {$actionRequired}</p>
                </div>
                <p style='color: #7f8c8d; margin-top: 20px;'>Thank you for choosing our service!</p>
                <p style='font-size: 12px; color: #95a5a6;'>If you have any questions, please contact our support team.</p>
            </div>
        ";

        // Plain text version
        $mail->AltBody = "
            {$mail->Subject}
            
            {$statusMessage}
            
            Token ID: {$data['token_id']}
            Pickup Date: {$data['pickup_date']}
           

        
            
            Action Required: {$actionRequired}
            
            Thank you for choosing our service!
            
            If you have any questions, please contact our support team.
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

// Handle incoming POST request
$postData = json_decode(file_get_contents('php://input'), true);
if ($postData) {
    $result = sendEmail($postData);
    if ($result === true) {
        echo json_encode(['status' => 'success', 'message' => 'Email sent successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $result]);
    }
}
?> 