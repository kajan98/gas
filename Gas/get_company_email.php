<?php
include 'include/db.php';
header('Content-Type: application/json');

if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    
    $query = "SELECT company_email FROM industrial_users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['email' => $row['company_email']]);
    } else {
        echo json_encode(['email' => 'Email not found']);
    }
}
?> 