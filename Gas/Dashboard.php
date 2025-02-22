<?php
// Include the database connection
include 'include/db.php';

// Fetch counts for dashboard cards
$managerCount = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'manager'")->fetch_assoc()['count'];
$adminCount = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
$outletCount = $conn->query("SELECT COUNT(*) AS count FROM outlets")->fetch_assoc()['count'];
$gasPackCount = $conn->query("SELECT COUNT(*) AS count FROM litro_gas_packs")->fetch_assoc()['count'];
$industrialCount = $conn->query("SELECT COUNT(*) AS count FROM industrial_users")->fetch_assoc()['count'];
$consumerCount = $conn->query("SELECT COUNT(*) AS count FROM consumer")->fetch_assoc()['count'];

// Fetch monthly registration data for current year
$currentYear = date('Y');
$monthlyRegistrationsQuery = "
    SELECT 
        'Industrial' as user_type,
        MONTH(created_at) as month,
        COUNT(*) as count
    FROM industrial_users
    WHERE YEAR(created_at) = ?
    GROUP BY MONTH(created_at)
    UNION ALL
    SELECT 
        'Consumer' as user_type,
        MONTH(created_at) as month,
        COUNT(*) as count
    FROM consumer
    WHERE YEAR(created_at) = ?
    GROUP BY MONTH(created_at)
    ORDER BY month";

$stmt = $conn->prepare($monthlyRegistrationsQuery);
$stmt->bind_param("ii", $currentYear, $currentYear);
$stmt->execute();
$registrationResult = $stmt->get_result();

// Prepare data for the chart
$months = [];
$industrialData = array_fill(0, 12, 0);
$consumerData = array_fill(0, 12, 0);

while ($row = $registrationResult->fetch_assoc()) {
    $monthIndex = (int)$row['month'] - 1;
    if ($row['user_type'] === 'Industrial') {
        $industrialData[$monthIndex] = (int)$row['count'];
    } else {
        $consumerData[$monthIndex] = (int)$row['count'];
    }
}

// Fetch stock data grouped by date, pack name, and outlet
$stockChartQuery = "SELECT DATE(created_at) AS stock_date, outlet_name, pack_name, SUM(stock_quantity) AS total_quantity 
                    FROM stock 
                    WHERE stock_status = 'delivered' 
                    GROUP BY stock_date, outlet_name, pack_name 
                    ORDER BY stock_date ASC";
$stockChartResult = $conn->query($stockChartQuery);

$chartData = [];
while ($row = $stockChartResult->fetch_assoc()) {
    $chartData[] = $row;
}

// Group data by pack name
$packNames = array_unique(array_column($chartData, 'pack_name'));
$groupedData = [];
foreach ($packNames as $packName) {
    $groupedData[$packName] = array_filter($chartData, function ($item) use ($packName) {
        return $item['pack_name'] === $packName;
    });
}

// Prepare data for separate bar charts
$charts = [];
foreach ($groupedData as $packName => $data) {
    $dates = array_unique(array_column($data, 'stock_date'));
    $outlets = array_unique(array_column($data, 'outlet_name'));

    $dataset = [];
    foreach ($outlets as $outlet) {
        $quantities = [];
        foreach ($dates as $date) {
            $entry = array_filter($data, function ($item) use ($date, $outlet) {
                return $item['stock_date'] === $date && $item['outlet_name'] === $outlet;
            });
            $quantities[] = $entry ? array_sum(array_column($entry, 'total_quantity')) : 0;
        }
        $dataset[] = [
            'label' => $outlet,
            'data' => $quantities,
            'backgroundColor' => 'rgba(' . rand(0, 255) . ',' . rand(0, 255) . ',' . rand(0, 255) . ', 0.5)',
            'borderColor' => 'rgba(0, 0, 0, 0.1)',
            'borderWidth' => 1
        ];
    }

    $charts[$packName] = [
        'dates' => $dates,
        'dataset' => $dataset
    ];
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Admin Dashboard - Gas by Gas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include 'include/adminside.php'; ?>

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <!-- Start Page Title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 font-size-18">Dashboard</h4>
                        </div>
                    </div>
                </div>
                <!-- End Page Title -->

                <!-- Dashboard Cards -->
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Managers</span>
                                        <h4 class="mb-3"><?= $managerCount ?></h4>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-user-tie text-primary" style="font-size: 40px;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Admins</span>
                                        <h4 class="mb-3"><?= $adminCount ?></h4>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-user-shield text-success" style="font-size: 40px;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Outlets</span>
                                        <h4 class="mb-3"><?= $outletCount ?></h4>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-store text-warning" style="font-size: 40px;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <span class="text-muted mb-3 lh-1 d-block text-truncate"> Gas Packs</span>
                                        <h4 class="mb-3"><?= $gasPackCount ?></h4>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-box text-danger" style="font-size: 40px;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Industrial Users</span>
                                        <h4 class="mb-3"><?= $industrialCount ?></h4>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-industry text-info" style="font-size: 40px;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <span class="text-muted mb-3 lh-1 d-block text-truncate">Consumers</span>
                                        <h4 class="mb-3"><?= $consumerCount ?></h4>
                                    </div>
                                    <div class="col-4">
                                        <i class="fas fa-users text-primary" style="font-size: 40px;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Dashboard Cards -->

                <!-- User Registration Chart -->
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">User Registrations (<?= $currentYear ?>)</h4>
                            </div>
                            <div class="card-body">
                                <canvas id="userRegistrationChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Separate Bar Charts for Each Pack -->
                <div class="row">
                    <?php $count = 0; ?>
                    <?php foreach ($charts as $packName => $chart): ?>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title"><?= $packName ?> Stock Quantities by Date and Outlet</h4>
                                </div>
                                <div class="card-body">
                                    <canvas id="chart-<?= md5($packName) ?>" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <?php $count++; ?>
                        <?php if ($count % 2 == 0): ?>
                            </div><div class="row">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <script>
                    // User Registration Chart
                    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                                      'July', 'August', 'September', 'October', 'November', 'December'];
                    
                    new Chart(document.getElementById('userRegistrationChart'), {
                        type: 'bar',
                        data: {
                            labels: monthNames,
                            datasets: [
                                {
                                    label: 'Industrial Users',
                                    data: <?= json_encode($industrialData) ?>,
                                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Consumers',
                                    data: <?= json_encode($consumerData) ?>,
                                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Registrations'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top'
                                },
                                title: {
                                    display: true,
                                    text: 'Monthly User Registrations'
                                }
                            }
                        }
                    });

                    <?php foreach ($charts as $packName => $chart): ?>
                        const ctx<?= md5($packName) ?> = document.getElementById('chart-<?= md5($packName) ?>').getContext('2d');
                        new Chart(ctx<?= md5($packName) ?>, {
                            type: 'bar',
                            data: {
                                labels: <?= json_encode($chart['dates']) ?>,
                                datasets: <?= json_encode($chart['dataset']) ?>
                            },
                            options: {
                                responsive: true,
                                barPercentage: 0.2, // Adjust bar size
                                scales: {
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Date'
                                        }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Quantity'
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: true
                                    }
                                }
                            }
                        });
                    <?php endforeach; ?>
                </script>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/feather-icons/feather.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>

</html>
