<?php
// Initial setup
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Ensure clean output
ob_start();
header('Content-Type: application/json');

try {
    // Load database
    require_once 'include/db.php';
    
    // Log incoming data
    error_log("POST Data: " . print_r($_POST, true));

    // Validate required fields
    if (!isset($_POST['request_order_id'], $_POST['token_id'], $_POST['pickup_date'], $_POST['status'])) {
        throw new Exception('Missing required fields');
    }

    // Sanitize inputs
    $requestOrderId = trim($_POST['request_order_id']);
    $tokenId = trim($_POST['token_id']);
    $pickupDate = trim($_POST['pickup_date']);
    $status = trim($_POST['status']);

    // Start transaction
    $conn->begin_transaction();

    // Prepare the base update query
    $updateFields = "token_id = ?, pickup_date = ?, status = ?";
    $params = [$tokenId, $pickupDate, $status];
    $types = "sss";

    // If status is completed, add cylinder_status and payment_status to the update
    if ($status === 'completed') {
        $cylinderStatus = isset($_POST['cylinder_status']) ? trim($_POST['cylinder_status']) : 'pending';
        $paymentStatus = isset($_POST['payment_status']) ? trim($_POST['payment_status']) : 'pending';
        
        $updateFields .= ", cylinder_status = ?, payment_status = ?";
        $params[] = $cylinderStatus;
        $params[] = $paymentStatus;
        $types .= "ss";
    }

    // Add the WHERE clause parameter
    $params[] = $requestOrderId;
    $types .= "s";

    // Construct the final update query
    $updateQuery = "UPDATE consumer_requests SET {$updateFields} WHERE request_order_id = ?";

    $stmt = $conn->prepare($updateQuery);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $rowsAffected = $stmt->affected_rows;

    // Get user email and other details for notification
    $userIdQuery = "SELECT user_id FROM consumer_requests WHERE request_order_id = ? LIMIT 1";
    $userIdStmt = $conn->prepare($userIdQuery);
    $userIdStmt->bind_param('s', $requestOrderId);
    $userIdStmt->execute();
    $userIdResult = $userIdStmt->get_result();
    $userId = $userIdResult->fetch_assoc()['user_id'];

    // Get email using user_id
    $emailQuery = "SELECT email FROM consumer WHERE id = ?";
    $emailStmt = $conn->prepare($emailQuery);
    $emailStmt->bind_param('i', $userId);
    $emailStmt->execute();
    $emailResult = $emailStmt->get_result();
    $userEmail = $emailResult->fetch_assoc()['email'];

    // Get pack details
    $packQuery = "SELECT pack_name, quantity FROM consumer_requests WHERE request_order_id = ?";
    $packStmt = $conn->prepare($packQuery);
    $packStmt->bind_param('s', $requestOrderId);
    $packStmt->execute();
    $packResult = $packStmt->get_result();
    
    $packDetails = [];
    while ($row = $packResult->fetch_assoc()) {
        $packDetails[] = [
            'pack_name' => $row['pack_name'],
            'quantity' => $row['quantity']
        ];
    }

    // Send email notification
    if (!empty($userEmail)) {
        require_once 'send_email.php';
        
        if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format: $userEmail");
        }

        $emailData = [
            'user_email' => $userEmail,
            'token_id' => $tokenId,
            'pickup_date' => $pickupDate,
            'pack_details' => $packDetails,
            'status' => $status
        ];

        // Add cylinder and payment status to email data if status is completed
        if ($status === 'completed') {
            $emailData['cylinder_status'] = $cylinderStatus;
            $emailData['payment_status'] = $paymentStatus;
        }

        $emailResult = sendEmail($emailData);
        if ($emailResult !== true) {
            throw new Exception("Failed to send email: $emailResult");
        }
    }

    // Commit transaction
    $conn->commit();

    // Clear any output and send response
    ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Status updated successfully',
        'rows_affected' => $rowsAffected
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("Error in update_status.php: " . $e->getMessage());
    
    // Rollback if needed
    if (isset($conn) && !$conn->connect_error) {
        $conn->rollback();
    }

    // Clear any output and send error response
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Close connection
if (isset($conn)) {
    $conn->close();
}
?>