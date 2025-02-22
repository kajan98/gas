<?php
include 'include/db.php';

if(isset($_POST['outlet'])) {
    $outlet = $_POST['outlet'];
    
    $query = "SELECT 
        pack_name,
        max_retail_price,
        SUM(CASE WHEN stock_status = 'delivered' THEN stock_quantity ELSE 0 END) as available_stock,
        SUM(stock_quantity * max_retail_price) as total_value,
        MAX(created_at) as last_updated,
        stock_status
    FROM stock 
    WHERE outlet_name = ?
    GROUP BY pack_name, stock_status
    ORDER BY pack_name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $outlet);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $output = "<table class='table table-bordered'>
        <thead>
            <tr>
                <th>Pack Name</th>
                <th>Price (LKR)</th>
                <th>Available Stock</th>
                <th>Total Value (LKR)</th>
                <th>Status</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>";
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $output .= "<tr>
                <td>{$row['pack_name']}</td>
                <td>" . number_format($row['max_retail_price'], 2) . "</td>
                <td>{$row['available_stock']}</td>
                <td>" . number_format($row['total_value'], 2) . "</td>
                <td>{$row['stock_status']}</td>
                <td>" . date('Y-m-d H:i', strtotime($row['last_updated'])) . "</td>
            </tr>";
        }
    } else {
        $output .= "<tr><td colspan='6' class='text-center'>No stock data available for this outlet</td></tr>";
    }
    
    $output .= "</tbody></table>";
    echo $output;
}
?> 