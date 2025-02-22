<?php
include 'include/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
ob_clean();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get and sanitize input
        $business_registration_number = $_POST['business_registration_number'] ?? '';
        $company_name = $_POST['company_name'] ?? '';
        $company_email = $_POST['company_email'] ?? '';
        $phone_number = $_POST['phone_number'] ?? '';
        $id = $_POST['id'] ?? '';
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';

        // Validate required fields
        if (empty($id) || empty($company_name) || empty($company_email) || empty($phone_number)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'All fields are required'
            ]);
            exit;
        }

        // If password change is requested
        if (!empty($current_password) && !empty($new_password)) {
            // Verify current password
            $verifyQuery = "SELECT password FROM industrial_users WHERE id = ?";
            $verifyStmt = $conn->prepare($verifyQuery);
            $verifyStmt->bind_param("i", $id);
            $verifyStmt->execute();
            $result = $verifyStmt->get_result();
            $userData = $result->fetch_assoc();

            if (!password_verify($current_password, $userData['password'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Current password is incorrect'
                ]);
                exit;
            }

            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update query with password
            $query = "UPDATE industrial_users SET 
                      company_name = ?, 
                      company_email = ?, 
                      phone_number = ?,
                      password = ?
                      WHERE id = ?";
                      
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssi", $company_name, $company_email, $phone_number, $hashed_password, $id);
        } else {
            // Update query without password
            $query = "UPDATE industrial_users SET 
                      company_name = ?, 
                      company_email = ?, 
                      phone_number = ? 
                      WHERE id = ?";
                      
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $company_name, $company_email, $phone_number, $id);
        }

        $stmt->execute();

        if ($stmt->affected_rows > 0 || $stmt->errno === 0) {
            // Get updated user data
            $selectQuery = "SELECT id, company_name, company_email, phone_number, business_registration_number FROM industrial_users WHERE id = ?";
            $selectStmt = $conn->prepare($selectQuery);
            $selectStmt->bind_param("i", $id);
            $selectStmt->execute();
            $result = $selectStmt->get_result();
            $userData = $result->fetch_assoc();

            echo json_encode([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'user' => $userData
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update profile'
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