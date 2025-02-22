<?php
session_start();
// Include the database connection
include 'include/db.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: index.php');
    exit;
}

$manager_id = $_SESSION['user_id'];

// First get manager's outlet name
$managerQuery = $conn->prepare("SELECT name FROM users WHERE id = ?");
$managerQuery->bind_param("i", $manager_id);
$managerQuery->execute();
$managerResult = $managerQuery->get_result();
$managerName = $managerResult->fetch_assoc()['name'];

// Fetch consumer requests for this manager's outlet
$consumerRequestsQuery = "
    SELECT 
        request_order_id,
        GROUP_CONCAT(request_id) AS request_ids,
        MAX(user_id) AS user_id,
        MAX(outlet_name) AS outlet_name,
        MAX(token_id) AS token_id,
        MAX(requested_date) AS requested_date,
        MAX(pickup_date) AS pickup_date,
        MAX(status) AS status
    FROM 
        consumer_requests
    WHERE 
        outlet_name = (SELECT name FROM outlets WHERE manager_name = ?)
    GROUP BY 
        request_order_id
";

$stmt = $conn->prepare($consumerRequestsQuery);
$stmt->bind_param("s", $managerName);
$stmt->execute();
$consumerRequestsResult = $stmt->get_result();

// Handle form submission for adding, updating, or deleting delivery entries
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        // Add Delivery Entry
        $request_id = $_POST['request_id']; // Get the selected request ID
        $delivery_date = $_POST['delivery_date'];
        $quantity = $_POST['quantity'];
        $status = $_POST['status'];

        // Insert the delivery entry into the database
        $insertQuery = "INSERT INTO deliveries (request_id, delivery_date, quantity, status) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('isis', $request_id, $delivery_date, $quantity, $status);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Delivery entry added successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add delivery entry.']);
        }
        exit;
    } elseif ($action === 'update') {
        // Update Delivery Entry
        $id = $_POST['id'];
        $request_id = $_POST['request_id']; // Get the selected request ID
        $delivery_date = $_POST['delivery_date'];
        $quantity = $_POST['quantity'];
        $status = $_POST['status'];

        $updateQuery = "UPDATE deliveries SET request_id = ?, delivery_date = ?, quantity = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('isisi', $request_id, $delivery_date, $quantity, $status, $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Delivery entry updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update delivery entry.']);
        }
        exit;
    } elseif ($action === 'delete') {
        // Delete Delivery Entry
        $id = $_POST['id'];

        $deleteQuery = "DELETE FROM deliveries WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Delivery entry deleted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete delivery entry.']);
        }
        exit;
    }
}

// Fetch delivery details for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM deliveries WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $delivery = $result->fetch_assoc();
    echo json_encode($delivery);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Management</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet">
</head>

<body>
    <div id="layout-wrapper">
        <?php include 'include/managerside.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">Delivery Management</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Add status filter dropdown to the search form -->
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Search and Filter</h4>
                                </div>
                                <div class="card-body">
                                    <form id="searchForm" class="row g-3">
                                        <div class="col-md-4">
                                            <input type="text" class="form-control" id="searchTokenId" placeholder="Enter Token ID">
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-control" id="statusFilter">
                                                <option value="">All Status</option>
                                                <option value="allocated">Allocated</option>
                                                <option value="reallocated">Reallocated</option>
                                                <option value="completed">Completed</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-primary" onclick="applyFilters()">Search</button>
                                            <button type="button" class="btn btn-secondary" onclick="resetFilters()">Reset</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Consumer Requests Table -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="card-title">Consumer Requests List</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered" id="consumerRequestsTable">
                                        <thead>
                                            <tr>
                                                <th>Request ID</th>
                                                <th>Order ID</th>
                                                <th>User ID</th>
                                                <th>Outlet Name</th>
                                                <th>Token ID</th>
                                                <th>Requested Date</th>
                                                <th>Pickup Date</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($consumerRequestsResult->num_rows > 0) {
                                                while ($row = $consumerRequestsResult->fetch_assoc()) {
                                                    echo "<tr>
                                                        <td>{$row['request_ids']}</td>
                                                        <td>{$row['request_order_id']}</td>
                                                        <td>{$row['user_id']}</td>
                                                        <td>{$row['outlet_name']}</td>
                                                        <td>{$row['token_id']}</td>
                                                        <td>{$row['requested_date']}</td>
                                                        <td>{$row['pickup_date']}</td>
                                                        <td>{$row['status']}</td>
                                                        <td>
                                                            <button class='btn btn-sm btn-primary' onclick='openChangeStatusModal({$row['user_id']}, \"{$row['request_order_id']}\", \"{$row['request_ids']}\")'>Change Status</button>
                                                        </td>
                                                    </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='9'>No consumer requests found</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Change Status Modal -->
                    <div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="changeStatusModalLabel">Change Status</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="changeStatusForm">
                                        <input type="hidden" name="request_order_id" id="request_order_id">
                                        
                                        <div class="mb-3">
                                            <label for="token_id" class="form-label">Token ID</label>
                                            <input type="text" class="form-control" id="token_id" name="token_id" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="request_id" class="form-label">Request ID</label>
                                            <input type="text" class="form-control" id="request_id" name="request_id" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="user_email" class="form-label">User Email</label>
                                            <input type="email" class="form-control" id="user_email" name="user_email" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-control" id="status" name="status" required onchange="handleStatusChange()">
                                                <option value="allocated">Allocated</option>
                                                <option value="reallocated">Reallocated</option>
                                                <option value="completed">Completed</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="pickup_date" class="form-label" id="dateLabel">Pickup Date</label>
                                            <input type="date" class="form-control" id="pickup_date" name="pickup_date" required>
                                        </div>

                                        <!-- Additional fields for completed status -->
                                        <div id="completedStatusFields" style="display: none;">
                                            <div class="mb-3">
                                                <label for="cylinder_status" class="form-label">Cylinder Status</label>
                                                <select class="form-control" id="cylinder_status" name="cylinder_status">
                                                    <option value="pending">Pending</option>
                                                    <option value="received">Received</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="payment_status" class="form-label">Payment Status</label>
                                                <select class="form-control" id="payment_status" name="payment_status">
                                                    <option value="pending">Pending</option>
                                                    <option value="paid">Paid</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Update Status</button>
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
    <script>
        // Add or Update Delivery Entry
        $('#deliveryForm').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();
            const action = $('#id').val() ? 'update' : 'add';
            $.post('deliverymanage.php', formData + `&action=${action}`, function(response) {
                const res = JSON.parse(response);
                if (res.status === 'success') {
                    Swal.fire('Success', res.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        });

        // Edit Delivery Entry
        function editDelivery(id) {
            $.get(`deliverymanage.php?id=${id}`, function(response) {
                const delivery = JSON.parse(response);
                $('#id').val(delivery.id);
                $('#request_id').val(delivery.request_id);
                $('#delivery_date').val(delivery.delivery_date);
                $('#quantity').val(delivery.quantity);
                $('#status').val(delivery.status);
            });
        }

        // Delete Delivery Entry
        function deleteDelivery(id) {
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
                    $.post('deliverymanage.php', { id: id, action: 'delete' }, function(response) {
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

        function generateTokenId() {
            const prefix = 'GS';
            const randomNum = Math.floor(1000 + Math.random() * 9000); // Generate a 4-digit number
            return `${prefix}${randomNum}`;
        }

        function openChangeStatusModal(userId, requestOrderId, requestId) {
            // Generate a new token ID
            const tokenId = generateTokenId();

            // Set the values in the modal
            document.getElementById('request_order_id').value = requestOrderId;
            document.getElementById('token_id').value = tokenId;
            document.getElementById('request_id').value = requestId;

            // Fetch user email based on user ID
            fetch(`get_user_email.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('user_email').value = data.email;
                });

            // Show the modal
            var myModal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
            myModal.show();
        }

        // Update the form submission handler
        document.getElementById('changeStatusForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const status = document.getElementById('status').value;
            
            // Show loading state
            Swal.fire({
                title: 'Updating...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // First update the status
            fetch('update_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())  // First get the raw response
            .then(text => {
                try {
                    return JSON.parse(text);  // Try to parse as JSON
                } catch (e) {
                    console.error('Server response:', text);  // Log the actual response
                    throw new Error('Invalid server response');
                }
            })
            .then(data => {
                // After successful status update, schedule the reminder if needed
                if (status === 'allocated' || status === 'reallocated') {
                    const reminderData = {
                        user_email: document.getElementById('user_email').value,
                        token_id: document.getElementById('token_id').value,
                        pickup_date: document.getElementById('pickup_date').value,
                        request_order_id: document.getElementById('request_order_id').value
                    };
                    
                    return fetch('schedule_reminder.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(reminderData)
                    })
                    .then(response => response.text())  // First get the raw response
                    .then(text => {
                        try {
                            return JSON.parse(text);  // Try to parse as JSON
                        } catch (e) {
                            console.error('Reminder server response:', text);  // Log the actual response
                            throw new Error('Invalid reminder server response');
                        }
                    });
                }
                return data;
            })
            .then(data => {
                console.log('Success:', data);
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Status updated successfully',
                    showConfirmButton: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('changeStatusModal'));
                        modal.hide();
                        location.reload();
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'An error occurred while updating the status'
                });
            });
        });

        function handleStatusChange() {
            const status = document.getElementById('status').value;
            const completedFields = document.getElementById('completedStatusFields');
            const dateLabel = document.getElementById('dateLabel');
            
            if (status === 'completed') {
                completedFields.style.display = 'block';
                dateLabel.textContent = 'Completed Date';
            } else {
                completedFields.style.display = 'none';
                dateLabel.textContent = 'Pickup Date';
            }
        }

        function applyFilters() {
            const tokenId = document.getElementById('searchTokenId').value.trim().toLowerCase();
            const status = document.getElementById('statusFilter').value.toLowerCase();
            const table = document.getElementById('consumerRequestsTable');
            const rows = table.getElementsByTagName('tr');

            // Skip header row
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const tokenCell = row.cells[4]; // Token ID column
                const statusCell = row.cells[7]; // Status column
                
                if (tokenCell && statusCell) {
                    const tokenText = tokenCell.textContent.toLowerCase() || '';
                    const statusText = statusCell.textContent.toLowerCase() || '';
                    
                    const tokenMatch = tokenId === '' || tokenText.includes(tokenId);
                    const statusMatch = status === '' || statusText === status;

                    if (tokenMatch && statusMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            }
        }

        function resetFilters() {
            document.getElementById('searchTokenId').value = '';
            document.getElementById('statusFilter').value = '';
            const table = document.getElementById('consumerRequestsTable');
            const rows = table.getElementsByTagName('tr');
            
            // Show all rows
            for (let i = 1; i < rows.length; i++) {
                rows[i].style.display = '';
            }
        }

        // Add event listener for enter key on search input
        document.getElementById('searchTokenId').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyFilters();
            }
        });

        // Add event listener for status filter change
        document.getElementById('statusFilter').addEventListener('change', function() {
            applyFilters();
        });
    </script>
</body>

</html>