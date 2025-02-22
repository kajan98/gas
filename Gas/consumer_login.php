

<?php
include 'include/db.php';
header('Content-Type: application/json');

try {
    $nic = $_POST['nic'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($nic) || empty($password)) {
        throw new Exception('NIC and password are required');
    }

    $stmt = $conn->prepare("SELECT * FROM consumer WHERE nic = ?");
    $stmt->bind_param("s", $nic);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            unset($user['password']); // Remove password from response
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => $user
            ]);
        } else {
            throw new Exception('Invalid credentials');
        }
    } else {
        throw new Exception('User not found');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>