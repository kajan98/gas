<?php
include 'include/db.php';

$userId = $_POST['user_id'];
$newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

$query = "UPDATE consumer SET password = ? WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $newPassword, $userId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Password updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update password'
    ]);
} 