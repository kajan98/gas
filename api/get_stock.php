<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$host = "localhost";
$username = "root";
$password = "";
$dbname = "gasbygas_db";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    // Get outlet name if provided
    $outlet_name = isset($_GET['outlet_name']) ? $_GET['outlet_name'] : null;
    
    // Base query
    $query = "SELECT DISTINCT outlet_name FROM stock";
    $outlets = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
    
    // Get stock data
    $stock_query = "SELECT id, pack_name, quantity, max_retail_price, total_price, 
                    outlet_name, stock_quantity, stock_status, created_at, updated_at 
                    FROM stock";
    
    if ($outlet_name) {
        $stock_query .= " WHERE outlet_name = ?";
        $stmt = $conn->prepare($stock_query);
        $stmt->bind_param("s", $outlet_name);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($stock_query);
    }
    
    $stock_data = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'outlets' => $outlets,
        'stock' => $stock_data
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 