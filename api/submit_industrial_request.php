<?php
// Display errors in API response (for debugging only, remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'gasbygas_db';

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Retrieve POST data (Handles both JSON and form data)
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    $data = $_POST; // Fallback to standard POST if JSON is not used
}

// Validate required input fields
if (!isset($data['user_id']) || !isset($data['outlet_name']) || !isset($data['cart_items'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// Log received input
error_log("Received Data: " . print_r($data, true));

$user_id = intval($data['user_id']);
$outlet_name = trim($data['outlet_name']);
$cart_items_raw = trim($data['cart_items']);

try {
    $conn->begin_transaction();

    // Generate request_order_id FIRST
    $request_order_id = 'IND' . date('YmdHis') . rand(1000, 9999);
    
    // Get and decode the JSON cart items
    $cart_items_json = $_POST['cart_items'];
    error_log("Received cart items JSON: " . $cart_items_json);
    
    $cart_items = json_decode($cart_items_json, true);
    if (!$cart_items || !is_array($cart_items)) {
        throw new Exception("Invalid cart items format");
    }

    error_log("Decoded cart items: " . print_r($cart_items, true));
    
    $processed_items = 0;
    
    foreach ($cart_items as $item) {
        // Extract pack_name and quantity from the item
        $pack_name = $item['pack_name'];
        $quantity = intval($item['quantity']);
        
        error_log("Processing item - Pack: $pack_name, Quantity: $quantity");
        
        $stmt = $conn->prepare("INSERT INTO industrial_requests 
            (user_id, outlet_name, pack_name, quantity, request_order_id, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW(), NOW())");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("issis", $user_id, $outlet_name, $pack_name, $quantity, $request_order_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $processed_items++;
        $stmt->close();
    }

    if ($processed_items === 0) {
        throw new Exception("No items were processed");
    }

    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => "Successfully processed $processed_items items",
        'request_order_id' => $request_order_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error in industrial request: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Transaction failed: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
