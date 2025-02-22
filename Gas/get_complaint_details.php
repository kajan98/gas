<?php
session_start();
include 'include/db.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    exit('Unauthorized access');
}

$manager_id = $_SESSION['user_id'];

if (isset($_POST['complaint_id'])) {
    $complaint_id = intval($_POST['complaint_id']);
    
    // First verify this complaint belongs to manager's outlet
    $verifyQuery = $conn->prepare("
        SELECT c.*, o.name as outlet_name 
        FROM complaints c 
        LEFT JOIN outlets o ON c.outlet_id = o.id 
        WHERE c.id = ? 
        AND o.manager_name = (SELECT name FROM users WHERE id = ?)
    ");
    
    $verifyQuery->bind_param("ii", $complaint_id, $manager_id);
    $verifyQuery->execute();
    $result = $verifyQuery->get_result();
    
    if ($row = $result->fetch_assoc()) {
        ?>
        <div class="p-3">
            <div class="d-flex mb-3">
                <div class="flex-grow-1">
                    <h6 class="mb-1">Customer Details</h6>
                    <p class="text-muted mb-0">
                        Name: <?= htmlspecialchars($row['name']) ?><br>
                        Email: <?= htmlspecialchars($row['email']) ?>
                    </p>
                </div>
                <div class="flex-shrink-0">
                    <span class="badge <?= $row['status'] === 'replied' ? 'bg-success' : 'bg-warning' ?>">
                        <?= ucfirst($row['status']) ?>
                    </span>
                </div>
            </div>

            <div class="mb-3">
                <h6 class="mb-1">Outlet</h6>
                <p class="text-muted mb-0"><?= htmlspecialchars($row['outlet_name']) ?></p>
            </div>

            <div class="mb-3">
                <h6 class="mb-1">Subject</h6>
                <p class="text-muted mb-0"><?= htmlspecialchars($row['subject']) ?></p>
            </div>

            <div class="mb-3">
                <h6 class="mb-1">Message</h6>
                <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($row['message'])) ?></p>
            </div>

            <div class="mb-3">
                <h6 class="mb-1">Date Submitted</h6>
                <p class="text-muted mb-0"><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></p>
            </div>

            <?php if ($row['status'] === 'not replied') { ?>
                <form id="replyForm" class="mt-4">
                    <input type="hidden" name="complaint_id" value="<?= $complaint_id ?>">
                    <div class="mb-3">
                        <label class="form-label">Your Reply</label>
                        <textarea class="form-control" name="reply" rows="4" required 
                                placeholder="Type your response here..."></textarea>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Send Reply</button>
                    </div>
                </form>

                <script>
                $(document).ready(function() {
                    $('#replyForm').on('submit', function(e) {
                        e.preventDefault();
                        
                        // Disable submit button to prevent double submission
                        $(this).find('button[type="submit"]').prop('disabled', true);
                        
                        $.ajax({
                            url: 'submit_reply.php',
                            type: 'POST',
                            data: $(this).serialize(),
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    // Show success message
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Reply sent successfully',
                                        icon: 'success',
                                        confirmButtonColor: '#2ab57d'
                                    }).then((result) => {
                                        // After clicking OK
                                        if (result.isConfirmed || result.isDismissed) {
                                            // Close the modal
                                            $('#complaintModal').modal('hide');
                                            // Reload the page
                                            window.location.reload();
                                        }
                                    });
                                } else {
                                    // Show error message
                                    Swal.fire({
                                        title: 'Error!',
                                        text: response.message || 'Failed to send reply',
                                        icon: 'error',
                                        confirmButtonColor: '#2ab57d'
                                    }).then(() => {
                                        // Re-enable submit button after error
                                        $('#replyForm').find('button[type="submit"]').prop('disabled', false);
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                // Show error message for AJAX failure
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Failed to send reply. Please try again.',
                                    icon: 'error',
                                    confirmButtonColor: '#2ab57d'
                                }).then(() => {
                                    // Re-enable submit button after error
                                    $('#replyForm').find('button[type="submit"]').prop('disabled', false);
                                });
                            }
                        });
                    });
                });
                </script>
            <?php } else { ?>
                <div class="mb-3 border-top pt-3">
                    <h6 class="mb-1">Reply</h6>
                    <p class="text-muted mb-2"><?= nl2br(htmlspecialchars($row['reply_text'])) ?></p>
                    <small class="text-muted">
                        Replied by: <?= htmlspecialchars($row['replied_by']) ?><br>
                        Date: <?= date('Y-m-d H:i', strtotime($row['updated_at'])) ?>
                    </small>
                </div>
            <?php } ?>
        </div>
        <?php
    } else {
        echo '<div class="p-3 text-center text-muted">Complaint not found or access denied.</div>';
    }
}
?> 