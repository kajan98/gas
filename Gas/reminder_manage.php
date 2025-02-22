<?php
include 'include/db.php';

// Get today's date
$today = date('Y-m-d');

// Fetch today's reminders
$query = "SELECT 
    r.*,
    CASE 
        WHEN r.request_order_id LIKE 'IND%' THEN 'Industrial'
        ELSE 'Consumer'
    END as request_type
FROM reminders r
WHERE r.reminder_date = ?
ORDER BY r.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $today);
$stmt->execute();
$result = $stmt->get_result();
$reminders = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reminders[] = $row;
    }
}

// Handle send all reminders
if (isset($_POST['send_all_reminders'])) {
    require_once 'send_email.php';
    $successCount = 0;
    $failCount = 0;
    
    $conn->begin_transaction();
    
    try {
        foreach ($reminders as $reminder) {
            if ($reminder['status'] === 'pending') {
                $emailData = [
                    'user_email' => $reminder['user_email'],
                    'token_id' => $reminder['token_id'],
                    'pickup_date' => $reminder['pickup_date'],
                    'status' => 'reminder'
                ];

                $emailResult = sendEmail($emailData);
                
                if ($emailResult === true) {
                    $successCount++;
                } else {
                    $failCount++;
                    throw new Exception("Failed to send email to: " . $reminder['user_email']);
                }
            }
        }
        
        if ($failCount === 0 && $successCount > 0) {
            $updateQuery = "UPDATE reminders SET status = 'sent' WHERE reminder_date = ? AND status = 'pending'";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('s', $today);
            $updateStmt->execute();
            
            $conn->commit();
            echo json_encode([
                'status' => 'success',
                'message' => "Successfully sent $successCount reminders and updated their status."
            ]);
        } else {
            throw new Exception("Some emails failed to send. Sent: $successCount, Failed: $failCount");
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Display message from URL parameter if exists
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Reminders</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <style>
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-pending { background-color: #fff3e0; color: #f57c00; }
        .status-sent { background-color: #e8f5e9; color: #2e7d32; }
        .status-failed { background-color: #ffebee; color: #c62828; }
        
        .reminder-date {
            font-weight: 600;
            color: #1a237e;
        }
        .reminder-type {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
        }
    </style>
</head>

<body>
    <div id="layout-wrapper">
        <?php include 'include/adminside.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Page Title and Send All Button -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between">
                                <h4 class="mb-0 font-size-18">Today's Reminders (<?php echo date('d M Y'); ?>)</h4>
                                <button type="button" id="sendAllReminders" class="btn btn-success">
                                    Send All Reminders
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Reminders Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Request Type</th>
                                                    <th>Order ID</th>
                                                    <th>Token ID</th>
                                                    <th>Email</th>
                                                    <th>Pickup Date</th>
                                                    <th>Status</th>
                                                    <th>Created At</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($reminders)): ?>
                                                    <?php foreach ($reminders as $reminder): ?>
                                                        <tr>
                                                            <td>
                                                                <span class="reminder-type">
                                                                    <?php echo htmlspecialchars($reminder['request_type']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($reminder['request_order_id']); ?></td>
                                                            <td><?php echo htmlspecialchars($reminder['token_id']); ?></td>
                                                            <td><?php echo htmlspecialchars($reminder['user_email']); ?></td>
                                                            <td>
                                                                <span class="reminder-date">
                                                                    <?php echo date('d M Y', strtotime($reminder['pickup_date'])); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="status-badge status-<?php echo strtolower($reminder['status']); ?>">
                                                                    <?php echo htmlspecialchars($reminder['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php echo date('d M Y H:i', strtotime($reminder['created_at'])); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center">
                                                            <div class="alert alert-info mb-0">
                                                                No reminders scheduled for today.
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

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Handle Send All Reminders button click
            $('#sendAllReminders').click(function() {
                Swal.fire({
                    title: 'Send Reminders?',
                    text: 'Are you sure you want to send all pending reminders?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#dc3545',
                    confirmButtonText: 'Yes, send them!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        Swal.fire({
                            title: 'Sending Reminders...',
                            html: 'Please wait while we process your request.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Send AJAX request
                        $.post('reminder_manage.php', { send_all_reminders: true }, function(response) {
                            try {
                                const data = JSON.parse(response);
                                if (data.status === 'success') {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: data.message,
                                        icon: 'success',
                                        confirmButtonColor: '#28a745'
                                    }).then(() => {
                                        // Reload page to show updated statuses
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: data.message,
                                        icon: 'error',
                                        confirmButtonColor: '#dc3545'
                                    });
                                }
                            } catch (e) {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'An unexpected error occurred.',
                                    icon: 'error',
                                    confirmButtonColor: '#dc3545'
                                });
                            }
                        }).fail(function() {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to send reminders. Please try again.',
                                icon: 'error',
                                confirmButtonColor: '#dc3545'
                            });
                        });
                    }
                });
            });

            // Show success message if exists in URL
            <?php if (isset($_GET['message'])): ?>
            Swal.fire({
                title: 'Success!',
                text: <?php echo json_encode($_GET['message']); ?>,
                icon: 'success',
                confirmButtonColor: '#28a745'
            });
            <?php endif; ?>
        });
    </script>
</body>

</html> 