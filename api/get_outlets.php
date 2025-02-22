<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$host = "localhost";
$username = "root";
$password = "";
$dbname = "gasbygas_db";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("SELECT `id`, `name`, `location` FROM `outlets` WHERE `status` = 'active'");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $result]);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn = null;
?> 