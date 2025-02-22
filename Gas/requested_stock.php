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
        $stock_status = $_POST['stock_status'];

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
        $stock_status = $_POST['stock_status'];

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
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = "SELECT *, (stock_quantity * max_retail_price) AS total_price FROM stock";
                                            $result = $conn->query($query);
                                            if ($result->num_rows > 0) {
                                                $hasRequestedStock = false;
                                                while ($row = $result->fetch_assoc()) {
                                                    // Skip rows where stock_status is 'delivered'
                                                    if ($row['stock_status'] === 'delivered') {
                                                        continue;
                                                    }
                                                    $hasRequestedStock = true;
                                                    echo "<tr>
                                                        <td>{$row['id']}</td>
                                                        <td>{$row['pack_name']}</td>
                                                        <td>LKR {$row['max_retail_price']}</td>
                                                        <td>{$row['outlet_name']}</td>
                                                        <td>{$row['stock_quantity']}</td>
                                                        <td>LKR {$row['total_price']}</td>
                                                        <td>{$row['stock_status']}</td>
                                                        <td>
                                                            <button class='btn btn-sm btn-success' onclick='deliverStock({$row['id']})'>Deliver</button>
                                                            <button class='btn btn-sm btn-danger' onclick='deleteStock({$row['id']})'>Delete</button>
                                                        </td>
                                                    </tr>";
                                                }
                                                if (!$hasRequestedStock) {
                                                    echo "<tr>
                                                        <td colspan='8' class='text-center'>
                                                            <div class='alert alert-info mb-0'>
                                                                <i class='fas fa-store me-2'></i>
                                                                No stock requests from the outlets at this time
                                                            </div>
                                                        </td>
                                                    </tr>";
                                                }
                                            } else {
                                                echo "<tr>
                                                    <td colspan='8' class='text-center'>
                                                        <div class='alert alert-info mb-0'>
                                                            <i class='fas fa-store me-2'></i>
                                                            No stock requests from the outlets at this time
                                                        </div>
                                                    </td>
                                                </tr>";
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

    <div class="modal fade" id="editStockModal" tabindex="-1" aria-labelledby="editStockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStockModalLabel">Edit Stock Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="stockForm">
                        <input type="hidden" id="id" name="id">
                        <div class="mb-3">
                            <label for="pack_name" class="form-label">Pack Name</label>
                            <input type="text" class="form-control" id="pack_name" name="pack_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="max_retail_price" class="form-label">Max Retail Price</label>
                            <input type="number" step="0.01" class="form-control" id="max_retail_price" name="max_retail_price" required>
                        </div>
                        <div class="mb-3">
                            <label for="outlet_name" class="form-label">Outlet Name</label>
                            <input type="text" class="form-control" id="outlet_name" name="outlet_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="stock_quantity" class="form-label">Stock Quantity</label>
                            <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required>
                        </div>
                        <div class="mb-3">
                            <label for="stock_status" class="form-label">Status</label>
                            <select class="form-select" id="stock_status" name="stock_status" required>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveChanges">Save changes</button>
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
            $.post('manage_requested_stock.php', formData + `&action=${action}`, function(response) {
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
        function openEditModal(id) {
            $.get(`manage_requested_stock.php?id=${id}`, function(response) {
                try {
                    // Check if response is already an object
                    const stock = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    // Fill the modal form with stock data
                    $('#id').val(stock.id);
                    $('#pack_name').val(stock.pack_name);
                    $('#max_retail_price').val(stock.max_retail_price);
                    $('#outlet_name').val(stock.outlet_name);
                    $('#stock_quantity').val(stock.stock_quantity);
                    $('#stock_status').val(stock.stock_status);
                    
                    // Show the modal
                    $('#editStockModal').modal('show');
                } catch (e) {
                    console.error('Error handling response:', e);
                    console.log('Response:', response);
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX request failed:', textStatus, errorThrown);
                Swal.fire('Error', 'Failed to fetch stock details', 'error');
            });
        }

        // Save changes to stock entry
        $('#saveChanges').on('click', function() {
            const formData = new FormData($('#stockForm')[0]);
            formData.append('action', 'update');

            // Convert FormData to object for easier handling
            const data = {};
            formData.forEach((value, key) => data[key] = value);

            // If status is being changed to delivered, check for existing stock
            if (data.stock_status === 'delivered') {
                $.ajax({
                    url: 'check_existing_stock.php',
                    method: 'POST',
                    data: {
                        pack_name: data.pack_name,
                        outlet_name: data.outlet_name
                    },
                    success: function(response) {
                        const res = JSON.parse(response);
                        if (res.exists) {
                            // Existing stock found, update it
                            $.ajax({
                                url: 'manage_requested_stock.php',
                                method: 'POST',
                                data: {
                                    action: 'merge_stock',
                                    existing_id: res.id,
                                    request_id: data.id,
                                    pack_name: data.pack_name,
                                    outlet_name: data.outlet_name,
                                    stock_quantity: data.stock_quantity,
                                    max_retail_price: data.max_retail_price
                                },
                                success: function(mergeResponse) {
                                    const mergeRes = JSON.parse(mergeResponse);
                                    if (mergeRes.status === 'success') {
                                        Swal.fire('Success', 'Stock merged successfully', 'success');
                                        setTimeout(() => location.reload(), 2000);
                                    } else {
                                        Swal.fire('Error', mergeRes.message, 'error');
                                    }
                                }
                            });
                        } else {
                            // No existing stock, proceed with normal update
                            updateStock(formData);
                        }
                    }
                });
            } else {
                // Not being delivered, proceed with normal update
                updateStock(formData);
            }
        });

        function updateStock(formData) {
            $.ajax({
                url: 'req',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        Swal.fire('Success', res.message, 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
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
                    $.post('manage_requested_stock.php', { id: id, action: 'delete' }, function(response) {
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

        // Update the AJAX settings to specify the expected data type
        $.ajaxSetup({
            dataType: 'json',
            contentType: 'application/json'
        });

        // Make sure your modal HTML structure matches the form fields
        const modalHtml = `
        <div class="modal fade" id="editStockModal" tabindex="-1" aria-labelledby="editStockModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editStockModalLabel">Edit Stock Entry</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="stockForm">
                            <input type="hidden" id="id" name="id">
                            <div class="mb-3">
                                <label for="pack_name" class="form-label">Pack Name</label>
                                <input type="text" class="form-control" id="pack_name" name="pack_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="max_retail_price" class="form-label">Max Retail Price</label>
                                <input type="number" step="0.01" class="form-control" id="max_retail_price" name="max_retail_price" required>
                            </div>
                            <div class="mb-3">
                                <label for="outlet_name" class="form-label">Outlet Name</label>
                                <input type="text" class="form-control" id="outlet_name" name="outlet_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required>
                            </div>
                            <div class="mb-3">
                                <label for="stock_status" class="form-label">Status</label>
                                <select class="form-select" id="stock_status" name="stock_status" required>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="saveChanges">Save changes</button>
                    </div>
                </div>
            </div>
        </div>`;

        // Add modal to the document if it doesn't exist
        if (!document.getElementById('editStockModal')) {
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        function deliverStock(id) {
            // First fetch the stock details
            $.ajax({
                url: `manage_requested_stock.php?id=${id}`,
                method: 'GET',
                success: function(stock) {
                    // Check if stock exists with same pack name and outlet
                    $.ajax({
                        url: 'check_existing_stock.php',
                        method: 'POST',
                        data: {
                            pack_name: stock.pack_name,
                            outlet_name: stock.outlet_name
                        },
                        success: function(response) {
                            if (response.exists) {
                                // Show confirmation with current quantity
                                const newQuantity = parseInt(response.current_quantity) + parseInt(stock.stock_quantity);
                                Swal.fire({
                                    title: 'Stock Already Exists',
                                    html: `Current stock quantity: ${response.current_quantity}<br>
                                          New stock to add: ${stock.stock_quantity}<br>
                                          Total after merge: ${newQuantity}`,
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: 'Yes, Update Stock',
                                    cancelButtonText: 'Cancel'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Use FormData to ensure proper data transmission
                                        const formData = new FormData();
                                        formData.append('action', 'merge_stock');
                                        formData.append('request_id', id);
                                        formData.append('existing_id', response.id);
                                        formData.append('stock_quantity', stock.stock_quantity);
                                        formData.append('max_retail_price', stock.max_retail_price);

                                        $.ajax({
                                            url: 'manage_requested_stock.php',
                                            method: 'POST',
                                            data: formData,
                                            processData: false,
                                            contentType: false,
                                            success: function(mergeResponse) {
                                                if (mergeResponse.status === 'success') {
                                                    Swal.fire('Success', 'Stock merged successfully', 'success')
                                                    .then(() => {
                                                        location.reload();
                                                    });
                                                } else {
                                                    Swal.fire('Error', mergeResponse.message || 'Failed to merge stock', 'error');
                                                }
                                            },
                                            error: function(xhr, status, error) {
                                                console.error('Error:', error);
                                                Swal.fire('Error', 'Failed to merge stock', 'error');
                                            }
                                        });
                                    }
                                });
                            } else {
                                // No existing stock, mark as delivered
                                const formData = new FormData();
                                formData.append('action', 'mark_delivered');
                                formData.append('id', id);

                                $.ajax({
                                    url: 'manage_requested_stock.php',
                                    method: 'POST',
                                    data: formData,
                                    processData: false,
                                    contentType: false,
                                    success: function(response) {
                                        if (response.status === 'success') {
                                            Swal.fire('Success', 'Stock delivered successfully', 'success')
                                            .then(() => {
                                                location.reload();
                                            });
                                        } else {
                                            Swal.fire('Error', response.message || 'Failed to deliver stock', 'error');
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('Error:', error);
                                        Swal.fire('Error', 'Failed to deliver stock', 'error');
                                    }
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            Swal.fire('Error', 'Failed to check existing stock', 'error');
                        }
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to fetch stock details', 'error');
                }
            });
        }

        // Add this function to handle debugging
        function logAjaxError(xhr, status, error) {
            console.log('XHR:', xhr);
            console.log('Status:', status);
            console.log('Error:', error);
            console.log('Response:', xhr.responseText);
        }
    </script>
</body>

</html>
