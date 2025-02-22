<?php
include 'include/db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");


// Initialize response array
$response = array();

try {
    // Check if required fields are present
    if (!isset($_POST['business_registration_number']) || !isset($_POST['password'])) {
        throw new Exception("Missing required fields");
    }

    // Sanitize input
    $business_registration_number = filter_var($_POST['business_registration_number'], FILTER_SANITIZE_STRING);
    $password = $_POST['password'];

    // Prepare SQL query
    $query = "SELECT * FROM industrial_users WHERE business_registration_number = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $business_registration_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Invalid business registration number or password");
    }

    $user = $result->fetch_assoc();

    // Verify password
    if (!password_verify($password, $user['password'])) {
        throw new Exception("Invalid business registration number or password");
    }

    // Check user status
    if ($user['status'] === 'pending') {
        $response['success'] = false;
        $response['pending'] = true;
        $response['message'] = "Your account is pending approval";
    } else if ($user['status'] === 'rejected') {
        $response['success'] = false;
        $response['message'] = "Your registration has been rejected";
    } else if ($user['status'] === 'approved') {
        // Remove sensitive information before sending
        unset($user['password']);
        
        $response['success'] = true;
        $response['message'] = "Login successful";
        $response['user'] = $user;
    } else {
        throw new Exception("Invalid account status");
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?> 