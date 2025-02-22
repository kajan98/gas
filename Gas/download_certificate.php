<?php
include 'include/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Get certificate path
    $query = "SELECT certificate_path FROM industrial_users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $file_path = $row['certificate_path'];
        
        if (file_exists($file_path)) {
            // Set headers for download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            header('Content-Length: ' . filesize($file_path));
            
            // Output file
            readfile($file_path);
            exit;
        }
    }
}

// If something goes wrong, redirect back
header('Location: industrial_table.php');
?> 