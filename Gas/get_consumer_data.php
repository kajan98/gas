<?php
require_once 'include/db.php';

$startDate = $_POST['start_date'] ?? null;
$endDate = $_POST['end_date'] ?? null;

$query = "SELECT cr.request_order_id, cr.token_id, cr.outlet_name, 
                 cr.pack_name, cr.quantity, cr.requested_date, 
                 cr.pickup_date, cr.status, cr.cylinder_status, 
                 cr.payment_status, c.name as consumer_name, 
                 c.email as consumer_email
          FROM consumer_requests cr
          LEFT JOIN consumer c ON cr.user_id = c.id";

if ($startDate && $endDate) {
    $query .= " WHERE DATE(cr.requested_date) BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
} else {
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<table class="table table-hover" id="dataTable">
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Token ID</th>
            <th>Consumer</th>
            <th>Pack Details</th>
            <th>Request Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['request_order_id']); ?></td>
            <td><?php echo htmlspecialchars($row['token_id']); ?></td>
            <td>
                <div><?php echo htmlspecialchars($row['consumer_name']); ?></div>
                <small class="text-muted"><?php echo htmlspecialchars($row['consumer_email']); ?></small>
            </td>
            <td>
                <div><?php echo htmlspecialchars($row['pack_name']); ?></div>
                <small class="text-muted">Qty: <?php echo htmlspecialchars($row['quantity']); ?></small>
            </td>
            <td>
                <div><?php echo date('d M Y', strtotime($row['requested_date'])); ?></div>
                <small class="text-muted">Pickup: <?php echo $row['pickup_date'] ? date('d M Y', strtotime($row['pickup_date'])) : 'Not set'; ?></small>
            </td>
            <td>
                <div class="mb-1">
                    <span class="status-badge bg-<?php echo getStatusColor($row['status']); ?>">
                        <?php echo htmlspecialchars($row['status']); ?>
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <span class="status-badge bg-<?php echo getStatusColor($row['cylinder_status'] ?? 'pending'); ?>" style="font-size: 0.75rem">
                        <?php echo htmlspecialchars($row['cylinder_status'] ?? 'pending'); ?>
                    </span>
                    <span class="status-badge bg-<?php echo getStatusColor($row['payment_status'] ?? 'pending'); ?>" style="font-size: 0.75rem">
                        <?php echo htmlspecialchars($row['payment_status'] ?? 'pending'); ?>
                    </span>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php
function getStatusColor($status) {
    switch (strtolower($status)) {
        case 'allocated':
            return 'info';
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        case 'received':
            return 'primary';
        case 'paid':
            return 'success';
        case 'pending':
        default:
            return 'warning';
    }
}
?> 