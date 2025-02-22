<?php
// Include the database connection
include 'include/db.php';

// Handle form submission for adding, updating, or deleting stock entries
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        // Add Stock Entry
        $pack_name = $_POST['pack_name'];
        $max_retail_price = $_POST['max_retail_price'];
        $outlet_name = $_POST['outlet_name'];
        $stock_quantity = $_POST['stock_quantity'];
       

           // Set stock status to 'delivered' by default
           $stock_status = 'delivered';

        // Insert the stock entry into the database
        $insertQuery = "INSERT INTO stock (pack_name, max_retail_price, outlet_name, stock_quantity, stock_status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('sdsis', $pack_name, $max_retail_price, $outlet_name, $stock_quantity, $stock_status);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Stock entry added successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add stock entry.']);
        }
        exit;
    } elseif ($action === 'update') {
        // Update Stock Entry
        $id = $_POST['id'];
        $pack_name = $_POST['pack_name'];
        $max_retail_price = $_POST['max_retail_price'];
        $outlet_name = $_POST['outlet_name'];
        $stock_quantity = $_POST['stock_quantity'];

        // Set stock status to 'delivered' by default
        $stock_status = 'delivered';

        $updateQuery = "UPDATE stock SET pack_name = ?, max_retail_price = ?, outlet_name = ?, stock_quantity = ?, stock_status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('sdsisi', $pack_name, $max_retail_price, $outlet_name, $stock_quantity, $stock_status, $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Stock entry updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update stock entry.']);
        }
        exit;
    } elseif ($action === 'delete') {
        // Delete Stock Entry
        $id = $_POST['id'];

        $deleteQuery = "DELETE FROM stock WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Stock entry deleted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete stock entry.']);
        }
        exit;
    }
}

// Fetch stock details for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM stock WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stock = $result->fetch_assoc();
    echo json_encode($stock);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet">
</head>

<body>
    <div id="layout-wrapper">
        <?php include 'include/adminside.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">Stock Management</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Form -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Add/Update Stock Entry</h4>
                                </div>
                                <div class="card-body">
                                    <form id="stockForm">
                                        <input type="hidden" name="id" id="id">
                                        <div class="mb-3">
                                            <label for="pack_name" class="form-label">Pack Name</label>
                                            <select class="form-control" id="pack_name" name="pack_name" required>
                                                <option value="">Select Pack</option>
                                                <?php
                                                $packsQuery = "SELECT pack_name, max_retail_price FROM litro_gas_packs";
                                                $packsResult = $conn->query($packsQuery);
                                                if ($packsResult->num_rows > 0) {
                                                    while ($row = $packsResult->fetch_assoc()) {
                                                        echo "<option data-price='{$row['max_retail_price']}' value='{$row['pack_name']}'>{$row['pack_name']}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="max_retail_price" class="form-label">Max Retail Price</label>
                                            <input type="number" step="0.01" class="form-control" id="max_retail_price" name="max_retail_price" readonly required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="outlet_name" class="form-label">Outlet Name</label>
                                            <select class="form-control" id="outlet_name" name="outlet_name" required>
                                                <option value="">Select Outlet</option>
                                                <?php
                                                $outletsQuery = "SELECT name FROM outlets";
                                                $outletsResult = $conn->query($outletsQuery);
                                                if ($outletsResult->num_rows > 0) {
                                                    while ($row = $outletsResult->fetch_assoc()) {
                                                        echo "<option value='{$row['name']}'>{$row['name']}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                            <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="total_price" class="form-label">Total Price</label>
                                            <input type="number" step="0.01" class="form-control" id="total_price" name="total_price" readonly>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Table -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="card-title">Stock List</h4>
                                    <div class="d-flex gap-4">
                                        <input type="text" id="searchInput" class="form-control" placeholder="Search by Pack Name or Outlet">
                                    </div>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered" id="stockTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Pack Name</th>
                                                <th>Max Retail Price</th>
                                                <th>Outlet Name</th>
                                                <th>Stock Quantity</th>
                                                <th>Total Price</th>
                                           
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = "SELECT *, (stock_quantity * max_retail_price) AS total_price FROM stock";
                                            $result = $conn->query($query);
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    echo "<tr>
                                                        <td>{$row['id']}</td>
                                                        <td>{$row['pack_name']}</td>
                                                        <td>LKR {$row['max_retail_price']}</td>
                                                        <td>{$row['outlet_name']}</td>
                                                        <td>{$row['stock_quantity']}</td>
                                                        <td>LKR {$row['total_price']}</td>
                                                    
                                                        <td>
                                                            <button class='btn btn-sm btn-warning' onclick='editStock({$row['id']})'>Edit</button>
                                                            <button class='btn btn-sm btn-danger' onclick='deleteStock({$row['id']})'>Delete</button>
                                                        </td>
                                                    </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='8'>No stock entries found</td></tr>";
                                            }
                                            ?>
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

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>
    <script>
        // Add or Update Stock Entry
        $('#stockForm').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();
            const action = $('#id').val() ? 'update' : 'add';
            $.post('manage_stock.php', formData + `&action=${action}`, function(response) {
                const res = JSON.parse(response);
                if (res.status === 'success') {
                    Swal.fire('Success', res.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        });

        // Edit Stock Entry
        function editStock(id) {
            $.get(`manage_stock.php?id=${id}`, function(response) {
                const stock = JSON.parse(response);
                $('#id').val(stock.id);
                $('#pack_name').val(stock.pack_name).change();
                $('#max_retail_price').val(stock.max_retail_price);
                $('#outlet_name').val(stock.outlet_name);
                $('#stock_quantity').val(stock.stock_quantity);
                $('#total_price').val(stock.stock_quantity * stock.max_retail_price);
            });
        }

        // Delete Stock Entry
        function deleteStock(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('manage_stock.php', { id: id, action: 'delete' }, function(response) {
                        const res = JSON.parse(response);
                        if (res.status === 'success') {
                            Swal.fire('Deleted!', res.message, 'success');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    });
                }
            });
        }

        // Update Max Retail Price and Calculate Total Price
        $('#pack_name').on('change', function() {
            const price = $(this).find(':selected').data('price');
            $('#max_retail_price').val(price);
            calculateTotalPrice();
        });

        $('#stock_quantity').on('input', function() {
            calculateTotalPrice();
        });

        function calculateTotalPrice() {
            const quantity = parseFloat($('#stock_quantity').val()) || 0;
            const price = parseFloat($('#max_retail_price').val()) || 0;
            $('#total_price').val((quantity * price).toFixed(2));
        }

        // Search Functionality
        document.getElementById('searchInput').addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#stockTable tbody tr');

            rows.forEach(row => {
                const packName = row.cells[1].textContent.toLowerCase();
                const outletName = row.cells[3].textContent.toLowerCase();
                row.style.display = (packName.includes(filter) || outletName.includes(filter)) ? '' : 'none';
            });
        });
    </script>
</body>

</html>
