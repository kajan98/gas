<!-- ========== Left Sidebar Start ========== -->
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<link rel="shortcut icon" href="assets/images/favicon.ico">

<?php
session_start();
include 'include/db.php';

// Fetch pending industrial users count
$pendingQuery = "SELECT COUNT(*) as pending_count FROM industrial_users WHERE status = 'pending'";
$pendingResult = $conn->query($pendingQuery);
$pendingCount = $pendingResult->fetch_assoc()['pending_count'];

// Fetch user details
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['name'] = $user['name'];
    } else {
        $_SESSION['name'] = "Unknown User";
    }
} else {
    $_SESSION['name'] = "Guest";
}

// Add this at the top of the file to get requested stock count
$stockQuery = "SELECT COUNT(*) as requestCount FROM stock WHERE stock_status = 'requested'";
$stockResult = $conn->query($stockQuery);
$stockRow = $stockResult->fetch_assoc();
$requestedStockCount = $stockRow['requestCount'];

// Get total notification count (industrial + stock requests)
$totalNotifications = $pendingCount + $requestedStockCount;
?>


<!-- plugin css -->
<link href="assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css" rel="stylesheet" type="text/css" />

<!-- preloader css -->
<link rel="stylesheet" href="assets/css/preloader.min.css" type="text/css" />

<!-- Bootstrap Css -->
<link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
<!-- Icons Css -->
<link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
<!-- App Css-->
<link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
<link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />

<!-- Add these lines before closing </body> tag or at the bottom of your scripts -->
<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>

<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <!-- Dashboard -->
                <li>
                    <a href="Dashboard.php?user_id=<?php echo $_SESSION['user_id']; ?>">
                        <i data-feather="home"></i> <!-- Home Icon -->
                        <span data-key="t-dashboard">Dashboard</span>
                    </a>
                </li>

                <!-- Manage Users -->
                <li>
                    <a href="adduser.php">
                        <i data-feather="user-plus"></i> <!-- Add User Icon -->
                        <span data-key="t-dashboard">Manage Users</span>
                    </a>
                </li>

                <!-- View Users -->
                <li>
                    <a href="usertable.php?user_id=<?php echo $_SESSION['user_id']; ?>">
                        <i data-feather="users"></i> <!-- View Users Icon -->
                        <span data-key="t-buttons">View Users</span>
                    </a>
                </li>

                <!-- Manage Outlets -->
                <li>
                    <a href="addoutlet.php?user_id=<?php echo $_SESSION['user_id']; ?>">
                        <i data-feather="map-pin"></i> <!-- Manage Outlets Icon -->
                        <span data-key="t-alerts">Manage Outlets</span>
                    </a>
                </li>

                <!-- View Outlets -->
                <li>
                    <a href="outlettable.php?user_id=<?php echo $_SESSION['user_id']; ?>">
                        <i data-feather="layers"></i> <!-- View Outlets Icon -->
                        <span data-key="t-buttons">View Outlets</span>
                    </a>
                </li>

                <!-- Litro Packs -->
                <li>
                    <a href="litro_gas_packs.php?user_id=<?php echo $_SESSION['user_id']; ?>">
                        <i data-feather="package"></i> <!-- Gas Pack Icon -->
                        <span data-key="t-horizontal">Gas Packs</span>
                    </a>
                </li>

                <li>
                    <a href="stock.php?user_id=<?php echo $_SESSION['user_id']; ?>">
                        <i data-feather="plus-square"></i> <!-- Add Stock Icon -->
                        <span data-key="t-horizontal">Add Stock</span>
                    </a>
                </li>

                <li>
                    <a href="requested_stock.php?user_id=<?php echo $_SESSION['user_id']; ?>">
                        <i data-feather="archive"></i> <!-- Requested Stock Icon -->
                        <span data-key="t-horizontal">Requested Stock</span>
                    </a>
                </li>
                <li>
                    <a href="reminder_manage.php">
                        <i data-feather="bell"></i> <!-- Changed to complaint/alert icon -->
                        <span data-key="t-horizontal">Reminder Manage</span> <!-- Made plural for better clarity -->
                    </a>
                </li>

                <li>
                    <a href="consumer_table.php?user_id=<?php echo $_SESSION['user_id']; ?>">
                        <i data-feather="user"></i> <!-- Consumer Icon -->
                        <span data-key="t-horizontal">Consumer</span>
                    </a>
                </li>

                <li>
                    <a href="industrial_table.php?user_id=<?php echo $_SESSION['user_id']; ?>">
                        <i data-feather="users"></i> <!-- Industrial User Icon -->
                        <span data-key="t-horizontal">Industrial User</span>
                    </a>
                </li>

                <li>
                    <a href="stock_report.php?user_id=<?php echo $_SESSION['user_id']; ?>">
                        <i data-feather="file-text"></i> <!-- Stock Report Icon -->
                        <span data-key="t-horizontal">Stock Report</span>
                    </a>
                </li>

                <li>
                    <a href="industrial_report.php?user_id=<?php echo $_SESSION['user_id']; ?>">
                        <i data-feather="file-text"></i> <!-- Industrial Report Icon -->
                        <span data-key="t-horizontal">Industrial Report</span>
                    </a>
                </li>
               
                <li>
                    <a href="consumer_report.php?user_id=<?php echo $_SESSION['user_id']; ?>">
                        <i data-feather="file-text"></i> 
                        <span data-key="t-horizontal">Consumer Report</span>
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
                <!-- LOGO -->
                <div class="navbar-brand-box">
                    <a href="Dashboard.php?user_id=<?php echo $_SESSION['user_id']; ?>" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="assets/images/logo.png" alt="Logo" style="height: 40px;">
                        </span>
                        <span class="logo-lg">
                            <img src="assets/images/logo.png" alt="Logo" style="height: 40px;">
                            <span class="logo-txt">Gas by Gas</span>
                        </span>
                    </a>

                    <a href="Dashboard.php?user_id=<?php echo $_SESSION['user_id']; ?>" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="assets/images/logo.png" alt="Logo" style="height: 40px;">
                        </span>
                        <span class="logo-lg">
                            <img src="assets/images/logo.png" alt="Logo" style="height: 40px;">
                            <span class="logo-txt">Gas by Gas</span>
                        </span>
                    </a>
                </div>

                <button type="button" class="btn btn-sm px-3 font-size-16 header-item" id="vertical-menu-btn">
                    <i class="fa fa-fw fa-bars"></i>
                </button>

                <!-- App Search-->
                <form class="app-search d-none d-lg-block">
                    <div class="position-relative">
                        <input type="text" class="form-control" placeholder="Search...">
                        <button class="btn btn-primary" type="button"><i class="bx bx-search-alt align-middle"></i></button>
                    </div>
                </form>
            </div>

            <div class="d-flex align-items-center" style="gap: 25px;">
                <div class="dropdown d-inline-block d-lg-none ms-2">
                    <button type="button" class="btn header-item" id="page-header-search-dropdown"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i data-feather="search" class="icon-lg"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                         aria-labelledby="page-header-search-dropdown">
                        <form class="p-3">
                            <div class="form-group m-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Search ..." aria-label="Search Result">
                                    <button class="btn btn-primary" type="submit"><i class="mdi mdi-magnify"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="dropdown">
                    <button type="button" class="btn header-item position-relative" id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-bell <?php echo $totalNotifications > 0 ? 'bell-shake' : ''; ?>" 
                           style="font-size: 20px;"></i>
                        <?php if ($totalNotifications > 0): ?>
                            <span class="badge rounded-circle bg-danger position-absolute" 
                                  style="top: -3px; right: -8px; font-size: 10px; padding: 4px 6px; min-width: 18px;">
                                <?php echo $totalNotifications; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-notifications-dropdown">
                        <div class="p-3">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="m-0">Notifications</h6>
                                </div>
                            </div>
                        </div>
                        <div data-simplebar style="max-height: 230px;">
                            <?php if ($pendingCount > 0 || $requestedStockCount > 0): ?>
                                <?php if ($pendingCount > 0): ?>
                                    <a href="industrial_table.php" class="text-reset notification-item">
                                        <div class="d-flex border-bottom p-3">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-industry text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">New Industrial User Registration<?php echo $pendingCount > 1 ? 's' : ''; ?></h6>
                                                <div class="font-size-13 text-muted">
                                                    <p class="mb-1">You have <?php echo $pendingCount; ?> pending industrial user<?php echo $pendingCount > 1 ? 's' : ''; ?> to review</p>
                                                    <p class="mb-0"><span class="fw-medium">Click to view</span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($requestedStockCount > 0): ?>
                                    <a href="requested_stock.php" class="text-reset notification-item">
                                        <div class="d-flex border-bottom p-3">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-boxes text-warning"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">New Stock Request<?php echo $requestedStockCount > 1 ? 's' : ''; ?></h6>
                                                <div class="font-size-13 text-muted">
                                                    <p class="mb-1">You have <?php echo $requestedStockCount; ?> new stock request<?php echo $requestedStockCount > 1 ? 's' : ''; ?> to review</p>
                                                    <p class="mb-0"><span class="fw-medium">Click to view</span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="p-3">
                                    <p class="text-center text-muted mb-0">No new notifications</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-2 border-top d-grid">
                            <a class="btn btn-sm btn-link font-size-14 text-center" href="javascript:void(0)">
                                <i class="mdi mdi-arrow-right-circle me-1"></i> <span>View More</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="dropdown">
                    <button type="button" class="btn header-item" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user me-2"></i>
                        <span>admin</span>
                        <i class="fas fa-chevron-down ms-2"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="adminprofile.php?user_id=<?php echo $_SESSION['user_id']; ?>">
                            <i class="mdi mdi mdi-face-man font-size-16 align-middle me-1"></i> Profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="javascript:void(0);" onclick="confirmLogout();">
                            <i class="mdi mdi-logout font-size-16 align-middle me-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <script>
            feather.replace();
        </script>
    </header>
</div>

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

<style>
.header-item {
    padding: 0;
    background: transparent;
    border: none;
    color: #495057;
}

.header-item:hover, 
.header-item:focus {
    color: #000;
    background: transparent;
}

.dropdown-menu {
    margin-top: 10px;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.d-flex.align-items-center.gap-3 {
    margin-right: 15px;
}

/* Bell shake animation */
@keyframes bell-shake {
    0% { transform: rotate(0); }
    15% { transform: rotate(5deg); }
    30% { transform: rotate(-5deg); }
    45% { transform: rotate(4deg); }
    60% { transform: rotate(-4deg); }
    75% { transform: rotate(2deg); }
    85% { transform: rotate(-2deg); }
    92% { transform: rotate(1deg); }
    100% { transform: rotate(0); }
}

.bell-shake {
    animation: bell-shake 2s infinite;
    transform-origin: top center;
}

/* Ensure the bell icon container doesn't move */
.header-item {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    position: relative;
    min-width: 24px;
    min-height: 24px;
}

/* Improved badge positioning */
.badge {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
}
</style>
