<?php
// Database configuration
$host = "localhost";      // Database host (usually 'localhost')
$username = "root";       // Database username
$password = "";           // Database password
$dbname = "gasbygas_db"; // Name of your database

// Create a connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");


?>
