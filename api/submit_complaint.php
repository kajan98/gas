<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');


$host = "localhost";
$username = "root";
$password = "";
$dbname = "gasbygas_db";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    $email = $_POST['email'];
    $name = $_POST['name'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $outlet_id = $_POST['outlet_id'];
    $status = $_POST['status'];

    $query = "INSERT INTO complaints (email, name, subject, message, outlet_id, status) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssss", $email, $name, $subject, $message, $outlet_id, $status);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Complaint submitted successfully'
        ]);
    } else {
        throw new Exception("Failed to submit complaint");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 