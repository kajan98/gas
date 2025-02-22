<?php
require_once 'include/db.php';

$startDate = $_POST['start_date'] ?? null;
$endDate = $_POST['end_date'] ?? null;

$query = "SELECT ir.*, iu.company_name 
          FROM industrial_requests ir
          LEFT JOIN industrial_users iu ON ir.user_id = iu.id";

if ($startDate && $endDate) {
    $query .= " WHERE DATE(ir.created_at) BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
} else {
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();
$totalRecords = $result->num_rows;
?>

<div class="mb-2">
    <strong>Total Records: </strong><?php echo $totalRecords; ?>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover" id="dataTable">
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Company Name</th>
                <th>Pack Name</th>
                <th>Quantity</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()): 
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['request_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['company_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['pack_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo getStatusColor($row['status']); ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </td>
                </tr>
            <?php 
                endwhile;
            } else {
                echo '<tr><td colspan="5" class="text-center">No records found</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

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