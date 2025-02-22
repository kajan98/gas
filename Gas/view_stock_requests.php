<?php
session_start();
include 'include/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: index.php');
    exit;
}

$manager_id = $_SESSION['user_id'];

// Fetch outlets allocated to the manager (same as ManagerDash.php)
$outletQuery = $conn->prepare("
    SELECT id, name AS outlet_name, manager_name 
    FROM outlets 
    WHERE manager_name = (SELECT name FROM users WHERE id = ?)
");
$outletQuery->bind_param("i", $manager_id);
$outletQuery->execute();
$outletResult = $outletQuery->get_result();

$outletData = [];
while ($row = $outletResult->fetch_assoc()) {
    $outletData[] = $row;
}

// Get array of outlet names
$outletNames = array_map(function($outlet) {
    return $outlet['outlet_name'];
}, $outletData);

// Fetch stock requests for manager's outlets
$stockQuery = $conn->prepare("
    SELECT 
        s.*,
        CASE 
            WHEN s.stock_status = 'delivered' THEN s.stock_quantity 
            ELSE 0 
        END as delivered_quantity
    FROM stock s
    WHERE s.outlet_name IN ('" . implode("','", $outletNames) . "')
    AND s.stock_status = 'requested'
    ORDER BY s.created_at DESC
");
$stockQuery->execute();
$result = $stockQuery->get_result();

$all_rows = [];
while ($row = $result->fetch_assoc()) {
    $all_rows[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Stock Requests</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <div id="layout-wrapper">
        <?php include 'include/managerside.php'; ?>
        
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Debug Information -->
                    <?php if(isset($debug)): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Debug Information:</h5>
                                    <pre><?php print_r($debug); ?></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Stock Overview -->
                    <!-- <div class="row">
                        <div class="col-xl-4 col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h4 class="mb-0">Total Delivered Stock</h4>
                                            <h2 class="mb-0 mt-2"><?= $total_delivered_quantity ?></h2>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-box text-primary" style="font-size: 2.5rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Pending Stock Requests</h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Pack Name</th>
                                                    <th>Quantity</th>
                                                    <th>Price (LKR)</th>
                                                    <th>Total (LKR)</th>
                                                    <th>Outlet</th>
                                                    <th>Status</th>
                                                    <th>Created At</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($all_rows)): ?>
                                                    <?php foreach ($all_rows as $row): ?>
                                                        <tr>
                                                            <td><?= $row['id'] ?></td>
                                                            <td><?= htmlspecialchars($row['pack_name']) ?></td>
                                                            <td><?= $row['quantity'] ?></td>
                                                            <td>LKR <?= number_format($row['max_retail_price'], 2) ?></td>
                                                            <td>LKR <?= number_format($row['total_price'], 2) ?></td>
                                                            <td><?= htmlspecialchars($row['outlet_name']) ?></td>
                                                            <td>
                                                                <span class="badge bg-warning">
                                                                    Requested
                                                                </span>
                                                            </td>
                                                            <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="8" class="text-center">
                                                            <div class="alert alert-info mb-0">
                                                                <i class="fas fa-store me-2"></i>
                                                                No stock requests from your outlets at this time
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html> 