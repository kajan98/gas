<?php
// Include the database connection
include 'include/db.php';

// Check the request method and perform corresponding actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        // Add Stock Entry
        $pack_name = $_POST['pack_name'];
        $max_retail_price = $_POST['max_retail_price'];
        $outlet_name = $_POST['outlet_name'];
        $stock_quantity = $_POST['stock_quantity'];
        $stock_status = 'delivered'; // Set default status to 'delivered'

        // Check if the stock entry already exists for the same outlet and pack
        $checkQuery = "SELECT * FROM stock WHERE pack_name = ? AND outlet_name = ? AND stock_status = 'delivered'";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param('ss', $pack_name, $outlet_name);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            // Stock entry exists, update it
            $existingStock = $checkResult->fetch_assoc();
            $newQuantity = $existingStock['stock_quantity'] + $stock_quantity;
            $newTotalPrice = $newQuantity * $max_retail_price;

            $updateQuery = "UPDATE stock SET 
                stock_quantity = ?, 
                total_price = ?, 
                max_retail_price = ?
                WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('iddi', 
                $newQuantity, 
                $newTotalPrice, 
                $max_retail_price,
                $existingStock['id']
            );

            if ($updateStmt->execute()) {
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Stock quantity updated successfully.'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Failed to update stock quantity.'
                ]);
            }
        } else {
            // Calculate total price for new entry
            $total_price = $stock_quantity * $max_retail_price;

            // Insert new stock entry
            $insertQuery = "INSERT INTO stock (
                pack_name, 
                max_retail_price, 
                outlet_name, 
                stock_quantity, 
                total_price,
                stock_status
            ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param('sdsids', 
                $pack_name, 
                $max_retail_price, 
                $outlet_name, 
                $stock_quantity, 
                $total_price,
                $stock_status
            );

            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Stock entry added successfully.'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Failed to add stock entry.'
                ]);
            }
        }
        exit;
    } elseif ($action === 'update') {
        // Update Stock Entry
        $id = $_POST['id'];
        $pack_name = $_POST['pack_name'];
        $max_retail_price = $_POST['max_retail_price'];
        $outlet_name = $_POST['outlet_name'];
        $stock_quantity = $_POST['stock_quantity'];
        $total_price = $stock_quantity * $max_retail_price;

        // Check if another entry exists with same pack and outlet (excluding current ID)
        $checkQuery = "SELECT * FROM stock WHERE pack_name = ? AND outlet_name = ? AND stock_status = 'delivered' AND id != ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param('ssi', $pack_name, $outlet_name, $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            // Merge with existing entry
            $existingStock = $checkResult->fetch_assoc();
            $newQuantity = $existingStock['stock_quantity'] + $stock_quantity;
            $newTotalPrice = $newQuantity * $max_retail_price;

            // Update existing entry and delete current one
            $updateQuery = "UPDATE stock SET 
                stock_quantity = ?, 
                total_price = ?, 
                max_retail_price = ?
                WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('iddi', 
                $newQuantity, 
                $newTotalPrice, 
                $max_retail_price,
                $existingStock['id']
            );

            if ($updateStmt->execute()) {
                // Delete the current entry as it's been merged
                $deleteQuery = "DELETE FROM stock WHERE id = ?";
                $deleteStmt = $conn->prepare($deleteQuery);
                $deleteStmt->bind_param('i', $id);
                $deleteStmt->execute();

                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Stock entries merged successfully.'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Failed to merge stock entries.'
                ]);
            }
        } else {
            // Regular update
            $updateQuery = "UPDATE stock SET 
                pack_name = ?, 
                max_retail_price = ?, 
                outlet_name = ?, 
                stock_quantity = ?, 
                total_price = ?
                WHERE id = ?";
            
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param('sdsidi', 
                $pack_name, 
                $max_retail_price, 
                $outlet_name, 
                $stock_quantity, 
                $total_price,
                $id
            );

            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Stock entry updated successfully.'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Failed to update stock entry.'
                ]);
            }
        }
        exit;
    } elseif ($action === 'delete') {
        // Delete a stock entry
        $id = $_POST['id'];

        // Delete query
        $deleteQuery = "DELETE FROM stock WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Stock entry deleted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete stock entry.']);
        }
        exit;
    }
}

// Fetch stock details for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];

    // Select query
    $query = "SELECT * FROM stock WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stock = $result->fetch_assoc();

    echo json_encode($stock);
    exit;
}

// Handle invalid access
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;
?>
