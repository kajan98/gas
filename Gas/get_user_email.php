<?php
include 'include/db.php';

if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    $query = "SELECT email FROM consumer WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    echo json_encode($user);
}
?> 