<?php
session_start();
include 'include/db.php';

// Check if manager is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    echo "<script>alert('Access Denied. Please log in as a manager.'); window.location.href = 'index.php';</script>";
    exit;
}

$manager_id = $_SESSION['user_id'];

// Fetch outlets allocated to the manager
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

// Fetch stock details for outlets managed by the current manager
$stockDetailsQuery = $conn->prepare("
    SELECT 
        stock.outlet_name, 
        stock.pack_name, 
        CASE 
            WHEN stock.stock_status = 'delivered' THEN stock.stock_quantity 
            ELSE 0 
        END as stock_quantity,
        stock.max_retail_price, 
        CASE 
            WHEN stock.stock_status = 'delivered' THEN (stock.stock_quantity * stock.max_retail_price)
            ELSE 0 
        END AS total_value 
    FROM stock 
    INNER JOIN outlets ON stock.outlet_name = outlets.name 
    WHERE outlets.manager_name = (SELECT name FROM users WHERE id = ?)
    ORDER BY stock.outlet_name, stock.pack_name
");
$stockDetailsQuery->bind_param("i", $manager_id);
$stockDetailsQuery->execute();
$stockDetailsResult = $stockDetailsQuery->get_result();

$stockDetails = [];
$totalStock = 0;
$totalValue = 0;
$outletTotals = []; // To store totals for each outlet
while ($row = $stockDetailsResult->fetch_assoc()) {
    // Only add to stockDetails if there's actual quantity (delivered status)
    if ($row['stock_quantity'] > 0) {
        $stockDetails[$row['outlet_name']][] = $row;
    }
    $totalStock += $row['stock_quantity'];
    $totalValue += $row['total_value'];

    // Calculate totals for each outlet
    if (!isset($outletTotals[$row['outlet_name']])) {
        $outletTotals[$row['outlet_name']] = ['quantity' => 0, 'value' => 0];
    }
    $outletTotals[$row['outlet_name']]['quantity'] += $row['stock_quantity'];
    $outletTotals[$row['outlet_name']]['value'] += $row['total_value'];
}

// Prepare data for charts
$chartLabels = [];
$chartQuantities = [];
$chartValues = [];

foreach ($outletTotals as $outletName => $totals) {
    $chartLabels[] = $outletName;
    $chartQuantities[] = $totals['quantity'];
    $chartValues[] = $totals['value'];
}

// Convert to JSON for JavaScript
$chartLabelsJSON = json_encode($chartLabels);
$chartQuantitiesJSON = json_encode($chartQuantities);
$chartValuesJSON = json_encode($chartValues);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Manager Dashboard - Gas by Gas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include 'include/managerside.php'; ?>

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <!-- Start Page Title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 font-size-18">Manager Dashboard</h4>
                        </div>
                    </div>
                </div>
                <!-- End Page Title -->

                <!-- Total Stock Overview -->
                <div class="row">
                    <div class="col-xl-6 col-md-6">
                        <div class="card card-h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Total Stock Quantity</span>
                                        <h4 class="mb-3"><?= $totalStock ?></h4>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-box text-danger" style="font-size: 40px;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6 col-md-6">
                        <div class="card card-h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Total Stock Value (LKR)</span>
                                        <h4 class="mb-3">LKR <?= number_format($totalValue, 2) ?></h4>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-money-bill-wave text-primary" style="font-size: 40px;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Total Stock Overview -->

                <!-- Stock Details Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Stock Details for Allocated Outlets</h4>
                            </div>
                            <div class="card-body">
                                <?php foreach ($stockDetails as $outletName => $stocks): ?>
                                    <h5 class="mt-4"><?= htmlspecialchars($outletName) ?></h5> 
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Pack Name</th>
                                                <th>Stock Quantity</th>
                                                <th>Max Retail Price (LKR)</th>
                                                <th>Total Value (LKR)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stocks as $stock): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($stock['pack_name']) ?></td>
                                                    <td><?= $stock['stock_quantity'] ?></td>
                                                    <td>LKR <?= number_format($stock['max_retail_price'], 2) ?></td>
                                                    <td>LKR <?= number_format($stock['total_value'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr>
                                                <td><strong>Total</strong></td>
                                                <td><strong><?= $outletTotals[$outletName]['quantity'] ?></strong></td>
                                                <td></td>
                                                <td><strong>LKR <?= number_format($outletTotals[$outletName]['value'], 2) ?></strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Stock Details Table -->

                <!-- Add Chart Section after the Stock Details Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Stock Overview Charts</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <canvas id="quantityChart"></canvas>
                                    </div>
                                    <div class="col-md-6">
                                        <canvas id="valueChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/feather-icons/feather.min.js"></script>
    <script src="assets/js/app.js"></script>

    <!-- Add this script before the closing body tag -->
    <script>
        // Quantity Chart
        new Chart(document.getElementById('quantityChart'), {
            type: 'bar',
            data: {
                labels: <?= $chartLabelsJSON ?>,
                datasets: [{
                    label: 'Stock Quantity by Outlet',
                    data: <?= $chartQuantitiesJSON ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Quantity'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Stock Quantity Distribution'
                    }
                }
            }
        });

        // Value Chart
        new Chart(document.getElementById('valueChart'), {
            type: 'bar',
            data: {
                labels: <?= $chartLabelsJSON ?>,
                datasets: [{
                    label: 'Stock Value by Outlet (LKR)',
                    data: <?= $chartValuesJSON ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Value (LKR)'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Stock Value Distribution'
                    }
                }
            }
        });
    </script>
</body>

</html>
