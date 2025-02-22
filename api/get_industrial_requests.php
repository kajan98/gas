<?php
// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'gasbygas_db';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

header('Content-Type: application/json');

// Validate user_id
if (!isset($_POST['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User ID not provided']);
    exit;
}

$user_id = $_POST['user_id'];

// Prepare and execute query
$query = $conn->prepare("
    SELECT 
        request_id,
        outlet_name,
        pack_name,
        quantity,
        token_id,
        request_order_id,
        status,
        created_at,
        updated_at
    FROM industrial_requests 
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

// Fetch all requests
$requests = [];
while ($row = $result->fetch_assoc()) {
    // Format dates
    $row['created_at'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
    $row['updated_at'] = date('Y-m-d H:i:s', strtotime($row['updated_at']));
    $requests[] = $row;
}

// Return the results
echo json_encode([
    'status' => 'success',
    'data' => $requests
]);

// Close the connection
$conn->close();
?> 