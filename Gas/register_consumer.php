<?php
include 'include/db.php';
header('Content-Type: application/json');
ob_clean();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $nic = $_POST['nic'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($nic) || empty($password)) {
            throw new Exception('All fields are required');
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO consumer (name, email, phone, address, nic, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $phone, $address, $nic, $hashed_password);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Registration successful']);
        } else {
            throw new Exception('Registration failed');
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>