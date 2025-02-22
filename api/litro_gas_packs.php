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
    $outlet_name = isset($_GET['outlet_name']) ? $_GET['outlet_name'] : '';
    
    $query = "SELECT id, pack_name, quantity, max_retail_price, total_price, 
              outlet_name, stock_quantity, stock_status, created_at, updated_at 
              FROM stock";
    
    if (!empty($outlet_name)) {
        $query .= " WHERE outlet_name = ?";
    }

    $stmt = $conn->prepare($query);
    
    if (!empty($outlet_name)) {
        $stmt->bind_param("s", $outlet_name);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = array();

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['status' => 'success', 'data' => $data]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?> 