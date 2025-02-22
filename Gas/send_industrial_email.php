<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';

function sendIndustrialEmail($emailData) {
    if (empty($emailData['company_email'])) {
        return "Email address is empty";
    }

    if (!filter_var($emailData['company_email'], FILTER_VALIDATE_EMAIL)) {
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
        $mail->setFrom('gagbygas@gmail.com', 'Industrial Gas Delivery System');
        $mail->addAddress($emailData['company_email'], $emailData['company_name']);

        // Content
        $mail->isHTML(true);
        
        // Set subject and content based on status
        switch($emailData['status']) {
            case 'allocated':
                $mail->Subject = 'Industrial Gas Order Allocated';
                $statusMessage = "Your industrial gas order has been allocated and is ready for processing.";
                $actionRequired = "Please note your Token ID and Pickup Date for future reference.";
                break;
                
            case 'driver picked up':
                $mail->Subject = 'Industrial Gas Order Picked Up by Driver';
                $statusMessage = "Your industrial gas order has been picked up by our delivery driver.";
                $actionRequired = "Please keep the token ID for reference when receiving the delivery.";
                break;
                
            case 'completed':
                $mail->Subject = 'Industrial Gas Order Completed';
                $statusMessage = "Your industrial gas order has been completed successfully.";
                $actionRequired = "Thank you for your business. We look forward to serving you again!";
                break;
                
            case 'cancelled':
                $mail->Subject = 'Industrial Gas Order Cancelled';
                $statusMessage = "Your industrial gas order has been cancelled.";
                $actionRequired = "If you did not request this cancellation, please contact our support team.";
                break;
                
            default:
                $mail->Subject = 'Industrial Gas Order Status Update';
                $statusMessage = "There has been an update to your industrial gas order.";
                $actionRequired = "Please check your order status in your account.";
        }

     

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2 style='color: #2c3e50;'>{$mail->Subject}</h2>
                <div style='background-color: #f9f9f9; padding: 15px; border-radius: 5px;'>
                    <p style='color: #34495e;'><strong>{$statusMessage}</strong></p>
                    <p><strong>Order ID:</strong> {$emailData['request_order_id']}</p>
                    <p><strong>Token ID:</strong> {$emailData['token_id']}</p>
                    <p><strong>Pickup Date:</strong> {$emailData['pickup_date']}</p>
                    
                   
                    <p style='margin-top: 15px; color: #e67e22;'><strong>Action Required:</strong> {$actionRequired}</p>
                </div>
                <p style='color: #7f8c8d; margin-top: 20px;'>Thank you for choosing our industrial gas service!</p>
                <p style='font-size: 12px; color: #95a5a6;'>If you have any questions, please contact our support team.</p>
            </div>
        ";

        // Plain text version
        $mail->AltBody = "
            {$mail->Subject}
            
            {$statusMessage}
            
            Order ID: {$emailData['request_order_id']}
            Token ID: {$emailData['token_id']}
            Pickup Date: {$emailData['pickup_date']}
            
            Action Required: {$actionRequired}
            
            Thank you for choosing our industrial gas service!
            
            If you have any questions, please contact our support team.
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?> 