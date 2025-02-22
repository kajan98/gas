<?php
// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

// Clear any previous output
if (ob_get_level()) ob_end_clean();

// Set JSON header
header('Content-Type: application/json');

// Log request for debugging
file_put_contents('debug.log', print_r($_POST, true), FILE_APPEND);

try {
    require_once 'include/db.php';
    
    // Log incoming data
    error_log("Received POST data: " . print_r($_POST, true));

    // Validate inputs
    if (empty($_POST['request_ids']) || empty($_POST['status']) || 
        empty($_POST['pickup_date']) || empty($_POST['token_id'])) {
        throw new Exception('Missing required fields');
    }

    $requestIds = array_map('trim', explode(',', $_POST['request_ids']));
    $status = trim($_POST['status']);
    $pickupDate = trim($_POST['pickup_date']);
    $tokenId = trim($_POST['token_id']);

    // Start transaction
    $conn->begin_transaction();

    try {
        // First get company details before update
        $companyQuery = "
            SELECT 
                iu.company_name,
                iu.company_email,
                ir.pack_name,
                ir.quantity,
                ir.user_id
            FROM industrial_requests ir
            JOIN industrial_users iu ON ir.user_id = iu.id
            WHERE ir.request_id = ?
            LIMIT 1";
        
        $companyStmt = $conn->prepare($companyQuery);
        if (!$companyStmt) {
            throw new Exception("Failed to prepare company query: " . $conn->error);
        }

        $companyStmt->bind_param('i', $requestIds[0]);
        $companyStmt->execute();
        $companyData = $companyStmt->get_result()->fetch_assoc();

        if (!$companyData) {
            throw new Exception("Company details not found");
        }

        // Now send the email
        require_once 'send_industrial_email.php';
        
        $emailData = [
            'company_email' => $companyData['company_email'],
            'company_name' => $companyData['company_name'],
            'status' => $status,
            'token_id' => $tokenId,
            'pickup_date' => $pickupDate,
            'request_order_id' => $_POST['request_order_id'],
            'order_items' => [[
                'pack_name' => $companyData['pack_name'],
                'quantity' => $companyData['quantity']
            ]]
        ];

        error_log("Attempting to send email with data: " . print_r($emailData, true));
        
        $emailResult = sendIndustrialEmail($emailData);
        if ($emailResult !== true) {
            throw new Exception("Failed to send email: " . $emailResult);
        }

        // After successful email, update the status
        $placeholders = str_repeat('?,', count($requestIds) - 1) . '?';
        $updateQuery = "UPDATE industrial_requests 
                       SET status = ?,
                           token_id = ?,
                           pickup_date = ?,
                           updated_at = NOW()
                       WHERE request_id IN ($placeholders)";

        $params = array_merge([$status, $tokenId, $pickupDate], $requestIds);
        $types = 'sss' . str_repeat('i', count($requestIds));

        $stmt = $conn->prepare($updateQuery);
        if (!$stmt) {
            throw new Exception("Failed to prepare update query: " . $conn->error);
        }

        $stmt->bind_param($types, ...$params);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update status: " . $stmt->error);
        }

        $rowsAffected = $stmt->affected_rows;
        if ($rowsAffected <= 0) {
            throw new Exception("No records were updated");
        }

        // If status is 'allocated', insert reminder
        if ($status === 'allocated') {
            // Calculate reminder date (1 day before pickup)
            $reminderDate = date('Y-m-d', strtotime($pickupDate . ' -1 day'));
            
            $reminderQuery = "INSERT INTO reminders (
                request_order_id, 
                user_email, 
                token_id, 
                pickup_date, 
                reminder_date, 
                status, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
            
            $reminderStmt = $conn->prepare($reminderQuery);
            if (!$reminderStmt) {
                throw new Exception("Failed to prepare reminder query: " . $conn->error);
            }

            $reminderStmt->bind_param(
                'sssss',
                $_POST['request_order_id'],
                $companyData['company_email'],
                $tokenId,
                $pickupDate,
                $reminderDate
            );

            if (!$reminderStmt->execute()) {
                throw new Exception("Failed to insert reminder: " . $reminderStmt->error);
            }
        }

        // If we got here, both email and update were successful
        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Status updated and notifications sent successfully',
            'details' => [
                'email_sent_to' => $companyData['company_email'],
                'records_updated' => $rowsAffected,
                'token_id' => $tokenId,
                'reminder_set' => ($status === 'allocated') ? $reminderDate : null
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in update_industrial_status.php: " . $e->getMessage());
    
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