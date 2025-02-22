<?php
session_start();
include 'include/db.php';

// Check if user is logged in and is a manager
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

// Get array of outlet IDs
$outletIds = array_map(function($outlet) {
    return $outlet['id'];
}, $outletData);

include 'include/managerside.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complaints Management</title>
    <!-- Include your CSS files -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <!-- Page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">Complaints Management</h4>
                        </div>
                    </div>
                </div>

                <!-- Complaints table -->
                <div class="row">
                    <!-- Not Replied Complaints -->
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Pending Complaints</h4>
                                <div class="table-responsive">
                                    <table class="table table-centered table-nowrap mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Subject</th>
                                                <th>Outlet</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Fetch not replied complaints
                                            if (!empty($outletIds)) {
                                                $query = "SELECT c.*, o.name as outlet_name 
                                                         FROM complaints c 
                                                         LEFT JOIN outlets o ON c.outlet_id = o.id 
                                                         WHERE c.outlet_id IN (" . implode(',', $outletIds) . ")
                                                         AND c.status = 'not replied'
                                                         ORDER BY c.created_at DESC";
                                                $result = $conn->query($query);

                                                if ($result->num_rows > 0) {
                                                    while ($row = $result->fetch_assoc()) {
                                            ?>
                                                        <tr>
                                                            <td><?= $row['id'] ?></td>
                                                            <td><?= htmlspecialchars($row['name']) ?></td>
                                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                                            <td><?= htmlspecialchars($row['subject']) ?></td>
                                                            <td><?= htmlspecialchars($row['outlet_name']) ?></td>
                                                            <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                                                            <td><span class="badge bg-warning">Not Replied</span></td>
                                                            <td>
                                                                <button type="button" 
                                                                        class="btn btn-primary btn-sm"
                                                                        onclick="viewComplaint(<?= $row['id'] ?>)">
                                                                    View & Reply
                                                                </button>
                                                            </td>
                                                        </tr>
                                            <?php 
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="8" class="text-center">No pending complaints</td></tr>';
                                                }
                                            } 
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Replied Complaints -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Resolved Complaints</h4>
                                <div class="table-responsive">
                                    <table class="table table-centered table-nowrap mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Subject</th>
                                                <th>Outlet</th>
                                                <th>Replied By</th>
                                                <th>Reply Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Fetch replied complaints
                                            if (!empty($outletIds)) {
                                                $query = "SELECT c.*, o.name as outlet_name 
                                                         FROM complaints c 
                                                         LEFT JOIN outlets o ON c.outlet_id = o.id 
                                                         WHERE c.outlet_id IN (" . implode(',', $outletIds) . ")
                                                         AND c.status = 'replied'
                                                         ORDER BY c.updated_at DESC";
                                                $result = $conn->query($query);

                                                if ($result->num_rows > 0) {
                                                    while ($row = $result->fetch_assoc()) {
                                            ?>
                                                        <tr>
                                                            <td><?= $row['id'] ?></td>
                                                            <td><?= htmlspecialchars($row['name']) ?></td>
                                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                                            <td><?= htmlspecialchars($row['subject']) ?></td>
                                                            <td><?= htmlspecialchars($row['outlet_name']) ?></td>
                                                            <td><?= htmlspecialchars($row['replied_by']) ?></td>
                                                            <td><?= date('Y-m-d H:i', strtotime($row['updated_at'])) ?></td>
                                                            <td>
                                                                <button type="button" 
                                                                        class="btn btn-info btn-sm"
                                                                        onclick="viewComplaint(<?= $row['id'] ?>)">
                                                                    View Details
                                                                </button>
                                                            </td>
                                                        </tr>
                                            <?php 
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="8" class="text-center">No resolved complaints</td></tr>';
                                                }
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

    <!-- View Complaint Modal -->
    <div class="modal fade" id="complaintModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complaint Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="complaintDetails">
                    <!-- Complaint details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Include your JS files -->
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>

    <script>
        function viewComplaint(id) {
            $.ajax({
                url: 'get_complaint_details.php',
                type: 'POST',
                data: { complaint_id: id },
                success: function(response) {
                    $('#complaintDetails').html(response);
                    $('#complaintModal').modal('show');
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error fetching complaint details',
                        icon: 'error',
                        confirmButtonColor: '#2ab57d'
                    });
                }
            });
        }

        // Global form submission handler
        $(document).on('submit', '#replyForm', function(e) {
            e.preventDefault();
            
            // Disable submit button
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true);
            
            $.ajax({
                url: 'submit_reply.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Close modal first
                        $('#complaintModal').modal('hide');
                        
                        // Show success message
                        Swal.fire({
                            title: 'Success!',
                            text: 'Reply sent successfully',
                            icon: 'success',
                            confirmButtonColor: '#2ab57d'
                        }).then((result) => {
                            // Reload page after clicking OK
                            window.location.reload();
                        });
                    } else {
                        // Show error message
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Failed to send reply',
                            icon: 'error',
                            confirmButtonColor: '#2ab57d'
                        });
                        submitBtn.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    // Show error message
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to send reply. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#2ab57d'
                    });
                    submitBtn.prop('disabled', false);
                }
            });
        });

        // Clear modal content when hidden
        $('#complaintModal').on('hidden.bs.modal', function () {
            $('#complaintDetails').html('');
        });
    </script>
</body>
</html> 