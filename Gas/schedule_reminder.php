<?php
require_once 'include/db.php';

// Receive the POST data
$data = json_decode(file_get_contents('php://input'), true);

try {
    // Calculate reminder date (one day before pickup)
    $pickupDate = new DateTime($data['pickup_date']);
    $reminderDate = clone $pickupDate;
    $reminderDate->modify('-1 day');
    
    // Format dates
    $pickupDateStr = $pickupDate->format('Y-m-d');
    $reminderDateStr = $reminderDate->format('Y-m-d');
    
    // First check if a reminder already exists for this request_order_id
    $checkQuery = "SELECT id FROM reminders WHERE request_order_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('s', $data['request_order_id']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing reminder
        $row = $result->fetch_assoc();
        $query = "UPDATE reminders SET 
            user_email = ?,
            token_id = ?,
            pickup_date = ?,
            reminder_date = ?,
            status = 'pending'
            WHERE id = ?";
            
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            'ssssi',
            $data['user_email'],
            $data['token_id'],
            $pickupDateStr,
            $reminderDateStr,
            $row['id']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Reminder updated successfully'
            ]);
        } else {
            throw new Exception("Failed to update reminder");
        }
    } else {
        // Insert new reminder
        $query = "INSERT INTO reminders (
            request_order_id,
            user_email,
            token_id,
            pickup_date,
            reminder_date,
            status
        ) VALUES (?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            'sssss',
            $data['request_order_id'],
            $data['user_email'],
            $data['token_id'],
            $pickupDateStr,
            $reminderDateStr
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Reminder scheduled successfully'
            ]);
        } else {
            throw new Exception("Failed to schedule reminder");
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 