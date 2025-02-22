<?php
include 'include/db.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid request'];

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_status':
            try {
                $id = $_POST['id'];
                $status = $_POST['status'];
                
                $query = "UPDATE consumer SET status = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("si", $status, $id);
                
                if ($stmt->execute()) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Status updated successfully'
                    ];
                } else {
                    throw new Exception("Failed to update status");
                }
            } catch (Exception $e) {
                $response = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
            break;

        case 'delete':
            try {
                $id = $_POST['id'];
                
                $query = "DELETE FROM consumer WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Consumer deleted successfully'
                    ];
                } else {
                    throw new Exception("Failed to delete consumer");
                }
            } catch (Exception $e) {
                $response = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
            break;
    }
}

echo json_encode($response);
?> 