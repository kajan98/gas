<?php
include 'include/db.php';
header('Content-Type: application/json');
ob_clean();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nic = $_POST['nic'] ?? '';
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';

        $query = "UPDATE consumer SET name = ?, email = ?, phone = ?, address = ? WHERE nic = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssss", $name, $email, $phone, $address, $nic);
        $stmt->execute();

        if ($stmt->affected_rows > 0 || $stmt->errno === 0) {
            // Get updated user data
            $selectQuery = "SELECT * FROM consumer WHERE nic = ?";
            $selectStmt = $conn->prepare($selectQuery);
            $selectStmt->bind_param("s", $nic);
            $selectStmt->execute();
            $result = $selectStmt->get_result();
            $userData = $result->fetch_assoc();

            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $userData
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update profile'
            ]);
        }
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?> 