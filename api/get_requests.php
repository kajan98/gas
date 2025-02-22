<?php
// Database connection
$host = 'localhost'; // Change if your database is hosted elsewhere
$username = 'root'; // Your database username
$password = ''; // Your database password
$dbname = 'gasbygas_db'; // Your database name

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

header('Content-Type: application/json');

if (!isset($_POST['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User ID not provided']);
    exit;
}

$user_id = $_POST['user_id'];

$query = $conn->prepare("SELECT * FROM consumer_requests WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

echo json_encode($requests);

// Close the connection
$conn->close();
?> 