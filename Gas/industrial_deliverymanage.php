<?php
session_start();
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

// Fetch industrial requests for this manager's outlet
$industrialRequestsQuery = "
    SELECT 
        request_order_id,
        GROUP_CONCAT(request_id) AS request_ids,
        MAX(user_id) AS user_id,
        MAX(outlet_name) AS outlet_name,
        MAX(token_id) AS token_id,
        MAX(created_at) AS requested_date,
        MAX(pickup_date) AS pickup_date,
        MAX(status) AS status
    FROM 
        industrial_requests
    WHERE 
        outlet_name = (SELECT name FROM outlets WHERE manager_name = ?)
    GROUP BY 
        request_order_id
";

$stmt = $conn->prepare($industrialRequestsQuery);
$stmt->bind_param("s", $managerName);
$stmt->execute();
$industrialRequestsResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Industrial Delivery Management</title>
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
                                <h4 class="mb-sm-0 font-size-18">Industrial Delivery Management</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Search and Filter Section -->
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Search and Filter</h4>
                                </div>
                                <div class="card-body">
                                    <form id="searchForm" class="row g-3">
                                        <div class="col-md-4">
                                            <input type="text" class="form-control" id="searchOrderId" placeholder="Enter Order ID">
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-control" id="statusFilter">
                                                <option value="">All Status</option>
                                                <option value="pending">Pending</option>
                                                <option value="allocated">Allocated</option>
                                                <option value="driver picked up">driver picked up</option>
                                                <option value="completed delievery">completed delievery</option>
                                              
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

                    <!-- Industrial Requests Table -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Industrial Requests List</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered" id="industrialRequestsTable">
                                        <thead>
                                            <tr>
                                                <th>Request ID</th>
                                                <th>Order ID</th>
                                                <th>Company ID</th>
                                                <th>Outlet Name</th>
                                                <th>Token ID</th>
                                              
                                                <th>Pickup Date</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($industrialRequestsResult->num_rows > 0) {
                                                while ($row = $industrialRequestsResult->fetch_assoc()) {
                                                    echo "<tr>
                                                        <td>{$row['request_ids']}</td>
                                                        <td>{$row['request_order_id']}</td>
                                                        <td>{$row['user_id']}</td>
                                                        <td>{$row['outlet_name']}</td>
                                                        <td>{$row['token_id']}</td>
                                                        
                                                        <td>{$row['pickup_date']}</td>
                                                        <td>{$row['status']}</td>
                                                        <td>
                                                            <button class='btn btn-sm btn-primary' onclick='openChangeStatusModal(\"{$row['user_id']}\", \"{$row['request_order_id']}\", \"{$row['request_ids']}\")'>Change Status</button>
                                                        </td>
                                                    </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='8'>No industrial requests found</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Change Status Modal -->
                    <div class="modal fade" id="changeStatusModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Update Industrial Request Status</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="changeStatusForm">
                                        <input type="hidden" name="request_order_id" id="request_order_id">
                                        
                                        <div class="mb-3">
                                            <label for="request_ids" class="form-label">Request IDs</label>
                                            <input type="text" class="form-control" id="request_ids" name="request_ids" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="token_id" class="form-label">Token ID</label>
                                            <input type="text" class="form-control" id="token_id" name="token_id" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="company_email" class="form-label">Company Email</label>
                                            <input type="email" class="form-control" id="company_email" name="company_email" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-control" id="status" name="status" required>
                                                <option value="pending">Pending</option>
                                                <option value="allocated">Allocated</option>
                                                <option value="driver picked up">Driver Picked Up</option>
                                                <option value="completed delivery">Completed Delivery</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="pickup_date" class="form-label">Pickup Date</label>
                                            <input type="date" class="form-control" id="pickup_date" name="pickup_date" required>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" onclick="updateStatus()">Update Status</button>
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
        function generateTokenId() {
            const prefix = 'IND';
            const randomNum = Math.floor(1000 + Math.random() * 9000); // Generate a 4-digit number
            return `${prefix}${randomNum}`;
        }

        function openChangeStatusModal(userId, requestOrderId, requestIds) {
            document.getElementById('request_order_id').value = requestOrderId;
            document.getElementById('request_ids').value = requestIds;
            
            // Generate and set token ID
            const tokenId = generateTokenId();
            document.getElementById('token_id').value = tokenId;

            // Fetch company email based on user ID
            fetch(`get_company_email.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('company_email').value = data.email;
                });

            var myModal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
            myModal.show();
        }

        function updateStatus() {
            const form = document.getElementById('changeStatusForm');
            const formData = new FormData(form);

            // Log form data for debugging
            console.log('Form data:', Object.fromEntries(formData));

            Swal.fire({
                title: 'Processing...',
                text: 'Please wait while we update the status',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('update_industrial_status.php', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                console.log('Raw server response:', text); // Debug log

                if (!text.trim()) {
                    throw new Error('Empty response from server');
                }

                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid response format from server');
                }
            })
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        showConfirmButton: true
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to update status');
                }
            })
            .catch(error => {
                console.error('Error details:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'An error occurred while updating the status'
                });
            });
        }

        // Add event listener for form submission
        document.getElementById('changeStatusForm').addEventListener('submit', function(e) {
            e.preventDefault();
            updateStatus();
        });

        function applyFilters() {
            const orderId = document.getElementById('searchOrderId').value.trim().toLowerCase();
            const status = document.getElementById('statusFilter').value.toLowerCase();
            const table = document.getElementById('industrialRequestsTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const orderIdCell = row.cells[1];
                const statusCell = row.cells[6];
                
                if (orderIdCell && statusCell) {
                    const orderIdText = orderIdCell.textContent.toLowerCase();
                    const statusText = statusCell.textContent.toLowerCase();
                    
                    const orderIdMatch = orderId === '' || orderIdText.includes(orderId);
                    const statusMatch = status === '' || statusText === status;

                    row.style.display = (orderIdMatch && statusMatch) ? '' : 'none';
                }
            }
        }

        function resetFilters() {
            document.getElementById('searchOrderId').value = '';
            document.getElementById('statusFilter').value = '';
            const table = document.getElementById('industrialRequestsTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                rows[i].style.display = '';
            }
        }

        // Event listeners
        document.getElementById('searchOrderId').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyFilters();
            }
        });

        document.getElementById('statusFilter').addEventListener('change', applyFilters);
    </script>
</body>
</html> 