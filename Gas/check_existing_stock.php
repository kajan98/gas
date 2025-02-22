<?php
include 'include/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the pack name and outlet name from the POST request
    $packName = $_POST['pack_name'] ?? '';
    $outletName = $_POST['outlet_name'] ?? '';

    if (empty($packName) || empty($outletName)) {
        echo json_encode([
            'exists' => false,
            'message' => 'Pack name and outlet name are required'
        ]);
        exit;
    }

    // Check if stock exists with the same pack name and outlet name
    $query = "SELECT id, stock_quantity FROM stock 
              WHERE pack_name = ? 
              AND outlet_name = ? 
              AND stock_status = 'delivered'";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $packName, $outletName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stock = $result->fetch_assoc();
        echo json_encode([
            'exists' => true,
            'id' => $stock['id'],
            'current_quantity' => $stock['stock_quantity'],
            'message' => 'Stock entry found'
        ]);
    } else {
        echo json_encode([
            'exists' => false,
            'message' => 'No existing stock found'
        ]);
    }
} else {
    echo json_encode([
        'exists' => false,
        'message' => 'Invalid request method'
    ]);
}
?> 