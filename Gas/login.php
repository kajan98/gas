<?php
session_start();
include 'include/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Query the database
    $stmt = $conn->prepare("SELECT id, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Include SweetAlert2 script
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id']; // Save user ID in session
            $_SESSION['role'] = $user['role']; // Save role in session

            if ($user['role'] === 'admin') {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Successful',
                            text: 'Welcome Admin!'
                        }).then(() => {
                            window.location.href = 'Dashboard.php';
                        });
                    });
                </script>";
            } elseif ($user['role'] === 'manager') {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Successful',
                            text: 'Welcome Manager!'
                        }).then(() => {
                            window.location.href = 'ManagerDash.php';
                        });
                    });
                </script>";
            }
        } else {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: 'Invalid password'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                });
            </script>";
        }
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: 'Invalid email or user does not exist'
                }).then(() => {
                    window.location.href = 'index.php';
                });
            });
        </script>";
    }
}
?>
