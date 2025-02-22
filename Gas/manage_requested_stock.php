<?php
include 'include/db.php';
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle GET request for fetching stock details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM stock WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stock = $result->fetch_assoc();
    echo json_encode($stock);
    exit;
}

// Handle POST request for updating stock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['action'])) {
        echo json_encode(['status' => 'error', 'message' => 'No action specified']);
        exit;
    }

    $action = $_POST['action'];
    
    if ($action === 'merge_stock') {
        if (!isset($_POST['request_id']) || !isset($_POST['existing_id']) || 
            !isset($_POST['stock_quantity']) || !isset($_POST['max_retail_price']) || !isset($_POST['total_price'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
            exit;
        }

        $requestId = $_POST['request_id'];
        $existingId = $_POST['existing_id'];
        $stockQuantity = floatval($_POST['stock_quantity']);
        $maxRetailPrice = floatval($_POST['max_retail_price']);
        $totalPrice = floatval($_POST['total_price']);

        $conn->begin_transaction();
        try {
            // Update existing stock quantity and total price
            $updateQuery = "UPDATE stock 
                           SET stock_quantity = stock_quantity + ?,
                               total_price = ?
                           WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("ddi", $stockQuantity, $totalPrice, $existingId);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update existing stock');
            }

            // Mark the request as delivered
            $statusQuery = "UPDATE stock SET stock_status = 'delivered' WHERE id = ?";
            $statusStmt = $conn->prepare($statusQuery);
            $statusStmt->bind_param("i", $requestId);
            
            if (!$statusStmt->execute()) {
                throw new Exception('Failed to update request status');
            }

            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Stock merged and delivered successfully']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    } elseif ($action === 'mark_delivered') {
        if (!isset($_POST['id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Missing ID parameter']);
            exit;
        }

        $requestId = $_POST['id'];
        
        try {
            $conn->begin_transaction();
            
            // First, get the requested stock details
            $getRequestQuery = "SELECT pack_name, outlet_name, stock_quantity, max_retail_price 
                              FROM stock WHERE id = ?";
            $requestStmt = $conn->prepare($getRequestQuery);
            $requestStmt->bind_param("i", $requestId);
            $requestStmt->execute();
            $requestedStock = $requestStmt->get_result()->fetch_assoc();
            
            if (!$requestedStock) {
                throw new Exception('Requested stock not found');
            }

            // Check if existing stock exists with same pack name and outlet
            $checkExistingQuery = "SELECT id, stock_quantity, total_price 
                                 FROM stock 
                                 WHERE pack_name = ? 
                                 AND outlet_name = ? 
                                 AND stock_status = 'delivered' 
                                 AND id != ?";
            $checkStmt = $conn->prepare($checkExistingQuery);
            $checkStmt->bind_param("ssi", 
                $requestedStock['pack_name'], 
                $requestedStock['outlet_name'],
                $requestId
            );
            $checkStmt->execute();
            $existingStock = $checkStmt->get_result()->fetch_assoc();

            if ($existingStock) {
                // Update existing stock
                $newQuantity = $existingStock['stock_quantity'] + $requestedStock['stock_quantity'];
                $additionalPrice = $requestedStock['stock_quantity'] * $requestedStock['max_retail_price'];
                $newTotalPrice = $existingStock['total_price'] + $additionalPrice;

                $updateExistingQuery = "UPDATE stock 
                                      SET stock_quantity = ?,
                                          total_price = ? 
                                      WHERE id = ?";
                $updateStmt = $conn->prepare($updateExistingQuery);
                $updateStmt->bind_param("ddi", 
                    $newQuantity,
                    $newTotalPrice,
                    $existingStock['id']
                );
                
                if (!$updateStmt->execute()) {
                    throw new Exception('Failed to update existing stock');
                }

                // Delete the requested stock entry
                $deleteQuery = "DELETE FROM stock WHERE id = ?";
                $deleteStmt = $conn->prepare($deleteQuery);
                $deleteStmt->bind_param("i", $requestId);
                
                if (!$deleteStmt->execute()) {
                    throw new Exception('Failed to delete requested stock');
                }
            } else {
                // Just mark as delivered and update total price
                $totalPrice = $requestedStock['stock_quantity'] * $requestedStock['max_retail_price'];
                $updateQuery = "UPDATE stock 
                              SET stock_status = 'delivered',
                                  total_price = ? 
                              WHERE id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("di", $totalPrice, $requestId);
                
                if (!$updateStmt->execute()) {
                    throw new Exception('Failed to update stock status');
                }
            }

            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Stock delivered successfully']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// If we get here, invalid action
echo json_encode(['status' => 'error', 'message' => 'Invalid action specified']);
?>