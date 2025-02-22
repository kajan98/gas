<?php
include 'include/db.php';

if (isset($_GET['token_id'])) {
    $tokenId = $_GET['token_id'];
    
    // Updated query to only fetch pack_name and quantity
    $query = "SELECT pack_name, quantity FROM consumer_requests WHERE token_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $tokenId);
    $stmt->execute();
    $result = $stmt->get_result();
    $packDetails = $result->fetch_assoc();

    echo json_encode([
        'pack_name' => $packDetails['pack_name'],
        'quantity' => $packDetails['quantity']
    ]);
}
?> 