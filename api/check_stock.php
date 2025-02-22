<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

include 'include/db.php';

try {
    // Get parameters from request
    $outlet_name = isset($_GET['outlet_name']) ? $_GET['outlet_name'] : null;
    $pack_name = isset($_GET['pack_name']) ? $_GET['pack_name'] : null;
    
    // Get stock data with all details
    $stock_query = "SELECT id, pack_name, quantity, max_retail_price, total_price, 
                    outlet_name, stock_quantity, stock_status, created_at, updated_at 
                    FROM stock";
    
    // Add WHERE clause if outlet_name and pack_name are provided
    $params = [];
    $where_clauses = [];
    
    if ($outlet_name) {
        $where_clauses[] = "outlet_name = ?";
        $params[] = $outlet_name;
    }
    
    if ($pack_name) {
        $where_clauses[] = "pack_name = ?";
        $params[] = $pack_name;
    }
    
    if (!empty($where_clauses)) {
        $stock_query .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    $stmt = $conn->prepare($stock_query);
    
    // Bind parameters if any
    if (!empty($params)) {
        $types = str_repeat("s", count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $stock_data = $result->fetch_all(MYSQLI_ASSOC);
    
    // Get all unique outlets
    $outlets_query = "SELECT DISTINCT outlet_name FROM stock";
    $outlets = $conn->query($outlets_query)->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'query' => $stock_query,
        'parameters' => [
            'outlet_name' => $outlet_name,
            'pack_name' => $pack_name
        ],
        'outlets' => $outlets,
        'count' => count($stock_data),
        'data' => $stock_data
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 