<?php
include 'include/db.php';
header('Content-Type: application/json');

if (!isset($_POST['user_id']) || !isset($_POST['outlet_name']) || !isset($_POST['cart_items'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$user_id = $_POST['user_id'];
$outlet_name = $_POST['outlet_name'];
$cart_items = json_decode($_POST['cart_items'], true);

try {
    $conn->begin_transaction();

    // Generate a unique request_order_id for this batch
    $request_order_id = 'REQ' . date('YmdHis') . rand(1000, 9999); // Example format

    foreach ($cart_items as $item) {
        $insertQuery = $conn->prepare("
            INSERT INTO consumer_requests 
            (user_id, outlet_name, pack_name, quantity, request_order_id, token_id, status) 
            VALUES (?, ?, ?, ?, ?, NULL, 'requested')
        ");
        
        $insertQuery->bind_param(
            "issss", 
            $user_id,
            $outlet_name,
            $item['pack_name'],
            $item['quantity'],
            $request_order_id // Use the same request_order_id for all items
        );
        
        $insertQuery->execute();
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Request submitted successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>