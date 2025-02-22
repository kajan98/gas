<?php
include 'include/db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");


// Initialize response array
$response = array();

try {
    // Check if all required fields are present
    $required_fields = [
        'company_name',
        'business_registration_number',
        'phone_number',
        'company_email',
        'password'
    ];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize input data
    $company_name = filter_var($_POST['company_name'], FILTER_SANITIZE_STRING);
    $business_registration_number = filter_var($_POST['business_registration_number'], FILTER_SANITIZE_STRING);
    $phone_number = filter_var($_POST['phone_number'], FILTER_SANITIZE_STRING);
    $company_email = filter_var($_POST['company_email'], FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Validate email format
    if (!filter_var($company_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Check if email already exists
    $check_email_query = "SELECT id FROM industrial_users WHERE company_email = ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param("s", $company_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Email already registered");
    }

    // Handle file upload
    if (!isset($_FILES['business_registration_certificate'])) {
        throw new Exception("Business registration certificate is required");
    }

    $file = $_FILES['business_registration_certificate'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_error = $file['error'];

    // Validate file
    if ($file_error !== UPLOAD_ERR_OK) {
        throw new Exception("File upload failed");
    }

    // Generate unique filename
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $new_filename = uniqid() . '.' . $file_extension;
    $upload_path = 'uploads/certificates/' . $new_filename;

    // Create directory if it doesn't exist
    if (!file_exists('uploads/certificates/')) {
        mkdir('uploads/certificates/', 0777, true);
    }

    // Move uploaded file
    if (!move_uploaded_file($file_tmp, $upload_path)) {
        throw new Exception("Failed to save file");
    }

    // Insert user data into database
    $insert_query = "INSERT INTO industrial_users (
        company_name, 
        business_registration_number, 
        phone_number, 
        company_email, 
        password, 
        certificate_path,
        status,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";

    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param(
        "ssssss",
        $company_name,
        $business_registration_number,
        $phone_number,
        $company_email,
        $password,
        $upload_path
    );

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Registration successful";
    } else {
        throw new Exception("Database error: " . $stmt->error);
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?> 