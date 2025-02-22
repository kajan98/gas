<?php
session_start();
include 'include/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: index.php');
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    try {
        $pack_name = $_POST['pack_name'];
        $quantity = $_POST['quantity'];
        $outlet_id = $_POST['outlet_id'];
        $max_retail_price = $_POST['max_retail_price'];
        $total_price = $quantity * $max_retail_price;
        $stock_status = 'Requested'; // Changed to uppercase first letter

        // Get outlet name from outlet_id
        $outlet_name = '';
        foreach ($outletData as $outlet) {
            if ($outlet['id'] == $outlet_id) {
                $outlet_name = $outlet['outlet_name'];
                break;
            }
        }

        // First, check if a record already exists
        $checkQuery = "SELECT id FROM stock WHERE pack_name = ? AND outlet_name = ? AND stock_status = 'Requested'";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("ss", $pack_name, $outlet_name);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            throw new Exception("A request for this pack at this outlet is already pending.");
        }

        $query = "INSERT INTO stock (pack_name, quantity, max_retail_price, total_price, outlet_name, stock_quantity, stock_status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("siddsss", $pack_name, $quantity, $max_retail_price, $total_price, $outlet_name, $quantity, $stock_status);
        
        if (!$stmt->execute()) {
            throw new Exception("Database error: " . $stmt->error);
        }

        $success = true;
        $message = "Stock request created successfully!";
    } catch (Exception $e) {
        $success = false;
        $message = $e->getMessage();
    }
}

// Fetch existing pack names for dropdown
$packQuery = "SELECT DISTINCT pack_name, max_retail_price FROM stock";
$packResult = $conn->query($packQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Stock Request</title>
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
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Create Stock Request</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label class="form-label">Outlet</label>
                                            <select class="form-select" name="outlet_id" required>
                                                <option value="">Select Outlet</option>
                                                <?php foreach ($outletData as $outlet): ?>
                                                    <option value="<?= $outlet['id'] ?>">
                                                        <?= htmlspecialchars($outlet['outlet_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Pack Name</label>
                                            <select class="form-select" name="pack_name" id="pack_name" required>
                                                <option value="">Select Pack</option>
                                                <?php while ($row = $packResult->fetch_assoc()): ?>
                                                    <option value="<?= htmlspecialchars($row['pack_name']) ?>" 
                                                            data-price="<?= $row['max_retail_price'] ?>">
                                                        <?= htmlspecialchars($row['pack_name']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Quantity</label>
                                            <input type="number" class="form-control" name="quantity" id="quantity" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Price (LKR)</label>
                                            <input type="number" class="form-control" name="max_retail_price" id="max_retail_price" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Total Price (LKR)</label>
                                            <input type="number" class="form-control" id="total_price" readonly>
                                        </div>

                                        <div>
                                            <button type="submit" name="submit_request" class="btn btn-primary">Submit Request</button>
                                        </div>
                                    </form>
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
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>

    <?php if (isset($success)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $success ? 'success' : 'error'; ?>',
                title: '<?php echo $success ? 'Success' : 'Error'; ?>',
                text: '<?php echo addslashes($message); ?>',
                confirmButtonColor: '#556ee6'
            }).then((result) => {
                if (<?php echo $success ? 'true' : 'false'; ?>) {
                    window.location.href = 'view_stock_requests.php';
                }
            });
        });
    </script>
    <?php endif; ?>

    <script>
        document.getElementById('pack_name').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            document.getElementById('max_retail_price').value = price;
            calculateTotal();
        });

        document.getElementById('quantity').addEventListener('input', calculateTotal);

        function calculateTotal() {
            const price = document.getElementById('max_retail_price').value;
            const quantity = document.getElementById('quantity').value;
            const total = price * quantity;
            document.getElementById('total_price').value = total.toFixed(2);
        }
    </script>
</body>
</html> 