<?php
session_start();
include 'include/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    echo "<script>alert('Access Denied. Please log in as a manager.'); window.location.href = 'index.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch manager details
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $manager = $result->fetch_assoc();
    $manager_name = htmlspecialchars($manager['name']); // Escape special characters
} else {
    $manager_name = "Unknown Manager"; // Fallback if no user is found
}

// Get count of not replied complaints for manager's outlets
$notRepliedCount = 0;
if (isset($_SESSION['user_id'])) {
    $manager_id = $_SESSION['user_id'];
    
    // First get manager's outlets
    $outletQuery = $conn->prepare("
        SELECT id FROM outlets 
        WHERE manager_name = (SELECT name FROM users WHERE id = ?)
    ");
    $outletQuery->bind_param("i", $manager_id);
    $outletQuery->execute();
    $outletResult = $outletQuery->get_result();
    
    $outletIds = [];
    while ($row = $outletResult->fetch_assoc()) {
        $outletIds[] = $row['id'];
    }
    
    // Then count complaints if manager has outlets
    if (!empty($outletIds)) {
        $complaintQuery = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM complaints 
            WHERE outlet_id IN (" . implode(',', $outletIds) . ") 
            AND status = 'not replied'
        ");
        $complaintQuery->execute();
        $countResult = $complaintQuery->get_result();
        $countRow = $countResult->fetch_assoc();
        $notRepliedCount = $countRow['count'];
    }
}
?>

<!-- ========== Left Sidebar Start ========== -->
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<link rel="shortcut icon" href="assets/images/favicon.ico">

<!-- Plugin CSS -->
<link href="assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css" rel="stylesheet" type="text/css" />

<!-- Preloader CSS -->
<link rel="stylesheet" href="assets/css/preloader.min.css" type="text/css" />

<!-- Bootstrap CSS -->
<link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
<!-- Icons CSS -->
<link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
<!-- App CSS -->
<link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />

<!-- Add these lines after your existing CSS links -->
<link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />

<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li>
                    <a href="ManagerDash.php">
                        <i data-feather="home"></i> <!-- Home Icon -->
                        <span data-key="t-dashboard">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="create_stock_request.php">
                        <i data-feather="plus-square"></i> <!-- Stock Request Icon -->
                        <span data-key="t-horizontal">Stock Request</span>
                    </a>
                </li>
                <li>
                    <a href="view_stock_requests.php">
                        <i data-feather="eye"></i> <!-- Monitor Request Icon -->
                        <span data-key="t-horizontal">Request Monitor</span>
                    </a>
                </li>
                
                <li>
                    <a href="complaint_screen.php">
                        <i data-feather="alert-circle"></i> <!-- Changed to complaint/alert icon -->
                        <span data-key="t-horizontal">Complaints</span> <!-- Made plural for better clarity -->
                    </a>
                </li>

                <li>
                    <a href="Deliverymanage.php">
                        <i data-feather="truck"></i> <!-- Changed to complaint/alert icon -->
                        <span data-key="t-horizontal">Consumer Manage Delivery</span> <!-- Made plural for better clarity -->
                    </a>
                </li>
                <li>
                    <a href="industrial_deliverymanage.php">
                        <i data-feather="truck"></i> <!-- Changed to complaint/alert icon -->
                        <span data-key="t-horizontal">Industrial Manage Delivery</span> <!-- Made plural for better clarity -->
                    </a>
                </li>


                <li>
                    <a href="search_delivery.php">
                        <i data-feather="truck"></i> <!-- Changed to complaint/alert icon -->
                        <span data-key="t-horizontal">Search Delivery</span> <!-- Made plural for better clarity -->
                    </a>
                </li>

              

              


            </ul>
        </div>
    </div>
</div>
<!-- Left Sidebar End -->

<div id="layout-wrapper">
    <header id="page-topbar">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- Logo -->
                <div class="navbar-brand-box">
                    <a href="ManagerDash.php" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="assets/images/logo.png" alt="Logo" style="height: 24px;">
                        </span>
                        <span class="logo-lg">
                            <img src="assets/images/logo.png" alt="Logo" style="height: 24px;">
                            <span class="logo-txt">Gas by Gas</span>
                        </span>
                    </a>

                    <a href="ManagerDash.php" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="assets/images/logo.png" alt="Logo" style="height: 24px;">
                        </span>
                        <span class="logo-lg">
                            <img src="assets/images/logo.png" alt="Logo" style="height: 24px;">
                            <span class="logo-txt">Gas by Gas</span>
                        </span>
                    </a>
                </div>

                <button type="button" class="btn btn-sm px-3 font-size-16 header-item" id="vertical-menu-btn">
                    <i class="fa fa-fw fa-bars"></i>
                </button>

                <!-- App Search -->
                <form class="app-search d-none d-lg-block">
                    <div class="position-relative">
                        <input type="text" class="form-control" placeholder="Search...">
                        <button class="btn btn-primary" type="button">
                            <i class="bx bx-search-alt align-middle"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="d-flex">
                <!-- Notifications -->
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn header-item noti-icon position-relative" id="page-header-notifications-dropdown"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i data-feather="bell" class="icon-lg"></i>
                        <?php if ($notRepliedCount > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?= $notRepliedCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                         aria-labelledby="page-header-notifications-dropdown">
                        <div class="p-3">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="m-0">Notifications</h6>
                                </div>
                                <div class="col-auto">
                                    <a href="complaint_screen.php" class="small text-reset text-decoration-underline">
                                        Pending Complaints (<?= $notRepliedCount ?>)
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div data-simplebar style="max-height: 230px;">
                            <?php if ($notRepliedCount > 0): ?>
                                <a href="complaint_screen.php" class="text-reset notification-item">
                                    <div class="d-flex border-bottom p-3">
                                        <div class="flex-shrink-0">
                                            <i data-feather="alert-circle" class="icon-dual text-danger"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">You have <?= $notRepliedCount ?> unreplied complaints</h6>
                                            <div class="text-muted">
                                                <p class="mb-1">Click to view and respond to complaints</p>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php else: ?>
                                <div class="p-3 text-center">
                                    <p class="text-muted mb-0">No pending complaints</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Profile Dropdown -->
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn header-item bg-light-subtle border-start border-end" id="page-header-user-dropdown"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <!-- <img class="rounded-circle header-profile-user" src="assets/images/users/avatar-1.jpg" alt="Header Avatar"> -->
                        <i class="fa fa-user"></i>
                        <span class="d-none d-xl-inline-block ms-1 fw-medium">
                            <?= $manager_name; ?> <!-- Dynamic Username -->
                        </span>
                        <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- Profile Management -->
                        <a class="dropdown-item" href="profile_manager.php">
                            <i class="mdi mdi-face-man font-size-16 align-middle me-1"></i> Profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="confirmLogout();">
                            <i class="mdi mdi-logout font-size-16 align-middle me-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
</div>

<script>
    feather.replace();
</script>

<!-- Add these lines before closing </body> tag or at the bottom of your scripts -->
<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>
<script>
function confirmLogout() {
    Swal.fire({
        title: 'Logout Confirmation',
        text: 'Are you sure you want to logout?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, logout!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'logout.php';
        }
    });
}
</script>
