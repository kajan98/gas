<?php
include 'include/db.php';
header('Content-Type: application/json');
ob_clean();

try {
    // Your database connection code here
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nic = $_POST['nic'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';

        // Query to verify user details
        $query = "SELECT * FROM consumer WHERE nic = ? AND email = ? AND phone = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $nic, $email, $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo json_encode([
                'success' => true,
                'email' => $user['email'],
                'name' => $user['name'],
                'nic' => $user['nic'],
                'password' => $user['password']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid credentials or user not found'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request method'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?> 