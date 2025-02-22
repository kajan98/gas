<?php
session_start();
include 'include/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('Access Denied. Please log in as admin.'); window.location.href = 'index.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch admin details
$stmt = $conn->prepare("SELECT name, email, phone, nic, password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$adminResult = $stmt->get_result();
if ($adminResult->num_rows === 1) {
    $admin = $adminResult->fetch_assoc();
} else {
    echo "<script>alert('Admin not found.'); window.location.href = 'index.php';</script>";
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $nic = trim($_POST['nic']);
    $password = !empty($_POST['password']) ? password_hash(trim($_POST['password']), PASSWORD_DEFAULT) : $admin['password'];

    $updateStmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, nic = ?, password = ? WHERE id = ?");
    $updateStmt->bind_param("sssssi", $name, $email, $phone, $nic, $password, $user_id);

    if ($updateStmt->execute()) {
        $_SESSION['update_status'] = ['type' => 'success', 'message' => 'Your profile has been updated successfully.'];
        header("Location: adminprofile.php?user_id=$user_id");
        exit;
    } else {
        $_SESSION['update_status'] = ['type' => 'error', 'message' => 'An error occurred while updating your profile. Please try again.'];
        header("Location: adminprofile.php?user_id=$user_id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div id="layout-wrapper">
        <?php include 'include/adminside.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <?php if (isset($_SESSION['update_status'])): ?>
                        <script>
                            Swal.fire({
                                icon: '<?= $_SESSION['update_status']['type'] ?>',
                                title: '<?= $_SESSION['update_status']['type'] === 'success' ? 'Profile Updated' : 'Update Failed' ?>',
                                text: '<?= $_SESSION['update_status']['message'] ?>'
                            });
                        </script>
                        <?php unset($_SESSION['update_status']); ?>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Manage Profile</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Name</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone</label>
                                            <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($admin['phone']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="nic" class="form-label">NIC</label>
                                            <input type="text" class="form-control" id="nic" name="nic" value="<?= htmlspecialchars($admin['nic']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password (optional)">
                                                <button type="button" class="btn btn-outline-secondary" id="toggle-password">
                                                    <i class="mdi mdi-eye-outline" id="password-icon"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script src="assets/libs/jquery/jquery.min.js"></script>
                    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
                    <script>
                        // Toggle password visibility
                        document.getElementById('toggle-password').addEventListener('click', function () {
                            const passwordField = document.getElementById('password');
                            const passwordIcon = document.getElementById('password-icon');
                            if (passwordField.type === 'password') {
                                passwordField.type = 'text';
                                passwordIcon.classList.remove('mdi-eye-outline');
                                passwordIcon.classList.add('mdi-eye-off-outline');
                            } else {
                                passwordField.type = 'password';
                                passwordIcon.classList.remove('mdi-eye-off-outline');
                                passwordIcon.classList.add('mdi-eye-outline');
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
