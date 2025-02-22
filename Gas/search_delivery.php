<?php
// Include the database connection
include 'include/db.php';

// Initialize variables for search criteria
$searchTokenId = '';
$requests = [];

// Handle search form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchTokenId = $_POST['token_id'] ?? '';

    if (!empty($searchTokenId)) {
        // Query for consumer requests
        $consumerQuery = "SELECT 
            'consumer' as request_type,
            request_id, 
            request_order_id, 
            outlet_name, 
            pack_name, 
            quantity, 
            token_id, 
            requested_date, 
            pickup_date, 
            status,
            cylinder_status, 
            payment_status,
            NULL as company_name,
            NULL as company_email
        FROM consumer_requests 
        WHERE token_id = ?";

        // Query for industrial requests - using created_at instead of requested_date
        $industrialQuery = "SELECT 
            'industrial' as request_type,
            ir.request_id, 
            ir.request_order_id, 
            NULL as outlet_name,
            ir.pack_name, 
            ir.quantity, 
            ir.token_id, 
            ir.created_at as requested_date, 
            ir.pickup_date, 
            ir.status,
            NULL as cylinder_status,
            NULL as payment_status,
            iu.company_name,
            iu.company_email
        FROM industrial_requests ir
        JOIN industrial_users iu ON ir.user_id = iu.id
        WHERE ir.token_id = ?";

        // Combine both queries with UNION
        $query = "$consumerQuery UNION $industrialQuery";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $searchTokenId, $searchTokenId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Requests</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <style>
        .search-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        .status-allocated { background-color: #e3f2fd; color: #1976d2; }
        .status-reallocated { background-color: #fff3e0; color: #f57c00; }
        .status-completed { background-color: #e8f5e9; color: #2e7d32; }
        .status-cancelled { background-color: #ffebee; color: #c62828; }
        .status-pending { background-color: #fafafa; color: #616161; }
        .status-received { background-color: #e0f2f1; color: #00796b; }
        .status-paid { background-color: #f3e5f5; color: #7b1fa2; }
        
        .request-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .request-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .info-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }
        .info-value {
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 12px;
        }
        .section-title {
            color: #1a237e;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e3f2fd;
        }
    </style>
</head>

<body>
    <div id="layout-wrapper">
        <?php include 'include/managerside.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Page Title -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between">
                                <h4 class="mb-0 font-size-18">Search Requests</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Search Form -->
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="search-container">
                                <form method="POST" id="searchForm" class="row align-items-center">
                                    <div class="col-md-8">
                                        <label for="token_id" class="form-label">Token ID</label>
                                        <input type="text" class="form-control form-control-lg" 
                                               id="token_id" name="token_id" 
                                               value="<?php echo htmlspecialchars($searchTokenId); ?>" 
                                               placeholder="Enter Token ID to search" required>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">
                                            <i class="fas fa-search me-2"></i>Search
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Search Results -->
                    <?php if (!empty($requests)): ?>
                    <div class="row">
                        <div class="col-lg-12">
                            <?php foreach ($requests as $request): ?>
                            <div class="request-card card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5 class="section-title">
                                                <?php echo ucfirst($request['request_type']); ?> Order Information
                                            </h5>
                                            <div class="mb-4">
                                                <div class="info-label">Request Order ID</div>
                                                <div class="info-value"><?php echo htmlspecialchars($request['request_order_id']); ?></div>
                                                
                                                <div class="info-label">Token ID</div>
                                                <div class="info-value"><?php echo htmlspecialchars($request['token_id']); ?></div>
                                                
                                                <?php if ($request['request_type'] === 'consumer'): ?>
                                                <div class="info-label">Outlet Name</div>
                                                <div class="info-value"><?php echo htmlspecialchars($request['outlet_name']); ?></div>
                                                <?php else: ?>
                                                <div class="info-label">Company Name</div>
                                                <div class="info-value"><?php echo htmlspecialchars($request['company_name']); ?></div>
                                                <div class="info-label">Company Email</div>
                                                <div class="info-value"><?php echo htmlspecialchars($request['company_email']); ?></div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="d-flex flex-wrap gap-2 mb-3">
                                                <div>
                                                    <div class="info-label">Status</div>
                                                    <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                                        <?php echo htmlspecialchars($request['status']); ?>
                                                    </span>
                                                </div>
                                                <?php if ($request['request_type'] === 'consumer'): ?>
                                                <div>
                                                    <div class="info-label">Cylinder Status</div>
                                                    <span class="status-badge status-<?php echo strtolower($request['cylinder_status'] ?? 'pending'); ?>">
                                                        <?php echo htmlspecialchars($request['cylinder_status'] ?? 'pending'); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="info-label">Payment Status</div>
                                                    <span class="status-badge status-<?php echo strtolower($request['payment_status'] ?? 'pending'); ?>">
                                                        <?php echo htmlspecialchars($request['payment_status'] ?? 'pending'); ?>
                                                    </span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h5 class="section-title">Pack Details</h5>
                                            <div class="mb-4">
                                                <div class="info-label">Pack Name</div>
                                                <div class="info-value"><?php echo htmlspecialchars($request['pack_name']); ?></div>
                                                
                                                <div class="info-label">Quantity</div>
                                                <div class="info-value"><?php echo htmlspecialchars($request['quantity']); ?></div>
                                            </div>

                                            <div class="mb-4">
                                                <div class="info-label">Requested Date</div>
                                                <div class="info-value"><?php echo htmlspecialchars($request['requested_date']); ?></div>
                                                
                                                <div class="info-label">Pickup Date</div>
                                                <div class="info-value"><?php echo htmlspecialchars($request['pickup_date']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <div>No requests found for the given Token ID.</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
</body>

</html> 