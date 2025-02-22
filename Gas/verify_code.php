<?php
include 'include/db.php';

$email = $_POST['email'];
$phone = $_POST['phone'];
$code = $_POST['code'];

// Verify code and check expiration
$query = "SELECT id FROM verification_codes 
          WHERE email = ? 
          AND phone = ? 
          AND code = ? 
          AND expires_at > NOW() 
          AND is_used = 0";
$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $email, $phone, $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Check if code exists but expired
    $query = "SELECT expires_at FROM verification_codes 
              WHERE email = ? AND phone = ? AND code = ? AND is_used = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $email, $phone, $code);
    $stmt->execute();
    $expiredResult = $stmt->get_result();
    
    if ($expiredResult->num_rows > 0) {
        $row = $expiredResult->fetch_assoc();
        if ($row['expires_at'] < date('Y-m-d H:i:s')) {
            echo json_encode([
                'success' => false,
                'message' => 'Verification code has expired. Please request a new code.'
            ]);
            exit;
        }
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Invalid verification code'
    ]);
    exit;
}

// Get user ID
$query = "SELECT id FROM consumer WHERE email = ? AND phone = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $email, $phone);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Mark code as used
$query = "UPDATE verification_codes SET is_used = 1 WHERE email = ? AND code = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $email, $code);
$stmt->execute();

echo json_encode([
    'success' => true,
    'user_id' => $user['id']
]);
?> 