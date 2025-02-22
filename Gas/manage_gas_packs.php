<?php
// Include the database connection
include 'include/db.php';

// Handle form submission for adding, updating, or deleting a gas pack
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        // Add Gas Pack
        $litro = $_POST['litro'];
        $pack_name = $_POST['pack_name'];
        $max_retail_price = $_POST['max_retail_price'];

        // Insert the gas pack into the database
        $insertQuery = "INSERT INTO litro_gas_packs (litro, pack_name, max_retail_price) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('ssd', $litro, $pack_name, $max_retail_price);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Gas pack added successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add gas pack.']);
        }
        exit;
    } elseif ($action === 'update') {
        // Update Gas Pack
        $id = $_POST['id'];
        $litro = $_POST['litro'];
        $pack_name = $_POST['pack_name'];
        $max_retail_price = $_POST['max_retail_price'];

        $updateQuery = "UPDATE litro_gas_packs SET litro = ?, pack_name = ?, max_retail_price = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('ssdi', $litro, $pack_name, $max_retail_price, $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Gas pack updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update gas pack.']);
        }
        exit;
    } elseif ($action === 'delete') {
        // Delete Gas Pack
        $id = $_POST['id'];

        $deleteQuery = "DELETE FROM litro_gas_packs WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Gas pack deleted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete gas pack.']);
        }
        exit;
    }
}

// Fetch gas pack details for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM litro_gas_packs WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $gasPack = $result->fetch_assoc();
    echo json_encode($gasPack);
    exit;
}

// If no valid action is provided
echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
exit;
?>
