<?php
include 'include/db.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        // Add User
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $nic = $_POST['nic'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password
        $status = $_POST['status'];
        $role = $_POST['role'];

        $sql = "INSERT INTO users (name, email, phone, nic, password, status, role) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $name, $email, $phone, $nic, $password, $status, $role);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'User added successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        $stmt->close();
    } elseif ($action === 'update') {
        // Update User
        $id = $_POST['id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $nic = $_POST['nic'];
        $status = $_POST['status'];
        $role = $_POST['role'];

        $sql = "UPDATE users SET name = ?, email = ?, phone = ?, nic = ?, status = ?, role = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $name, $email, $phone, $nic, $status, $role, $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'User updated successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        // Delete User
        $id = $_POST['id'];

        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'User deleted successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        $stmt->close();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id']) && $_GET['action'] === 'get') {
        // Fetch User by ID
        $id = $_GET['id'];

        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode($result->fetch_assoc());
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User not found!']);
        }
        $stmt->close();
    }
}

$conn->close();
?>
