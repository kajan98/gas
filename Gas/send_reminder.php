<?php
// Add at the start of the file
error_log("Reminder script started at: " . date('Y-m-d H:i:s'));

require_once 'include/db.php';
require_once 'send_email.php';

// This script should be run daily via cron job
$today = date('Y-m-d');
error_log("Checking reminders for: " . $today);

// Get all pending reminders for today
$query = "SELECT * FROM reminders WHERE reminder_date = ? AND status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $today);
$stmt->execute();
$result = $stmt->get_result();

$reminderCount = $result->num_rows;
error_log("Found {$reminderCount} pending reminders");

while ($reminder = $result->fetch_assoc()) {
    error_log("Processing reminder for order: " . $reminder['request_order_id']);
    
    // Prepare email data
    $emailData = [
        'user_email' => $reminder['user_email'],
        'token_id' => $reminder['token_id'],
        'pickup_date' => $reminder['pickup_date'],
        'status' => 'reminder',
        'pack_details' => [] // Add pack details if needed
    ];
    
    // Send reminder email
    $emailResult = sendEmail($emailData);
    
    // Update reminder status
    $updateQuery = "UPDATE reminders SET status = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $status = $emailResult === true ? 'sent' : 'failed';
    $updateStmt->bind_param('si', $status, $reminder['id']);
    $updateStmt->execute();
    
    error_log("Reminder processed with status: " . $status);
}

error_log("Reminder script completed at: " . date('Y-m-d H:i:s'));
$conn->close();
?> 