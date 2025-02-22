<?php
include 'include/db.php';
require 'vendor/autoload.php'; // For TCPDF
use TCPDF as TCPDF;

// Function to generate PDF
function generatePDF($reportData, $reportTitle) {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Gas by Gas');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Stock Report');
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    
    // Add title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $reportTitle, 0, 1, 'C');
    $pdf->Ln(10);
    
    // Add table headers
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(40, 7, 'Pack Name', 1);
    $pdf->Cell(30, 7, 'Price (LKR)', 1);
    $pdf->Cell(30, 7, 'Quantity', 1);
    $pdf->Cell(40, 7, 'Total (LKR)', 1);
    $pdf->Cell(30, 7, 'Status', 1);
    $pdf->Ln();
    
    // Add table data
    $pdf->SetFont('helvetica', '', 10);
    foreach($reportData as $row) {
        $pdf->Cell(40, 6, $row['pack_name'], 1);
        $pdf->Cell(30, 6, number_format($row['max_retail_price'], 2), 1);
        $pdf->Cell(30, 6, $row['stock_quantity'], 1);
        $pdf->Cell(40, 6, number_format($row['total_price'], 2), 1);
        $pdf->Cell(30, 6, $row['stock_status'], 1);
        $pdf->Ln();
    }
    
    return $pdf->Output('stock_report.pdf', 'S');
}

// Handle PDF generation request
if(isset($_POST['generate_pdf'])) {
    $outlet = $_POST['outlet_name'];
    $query = "SELECT *, (stock_quantity * max_retail_price) as total_price 
              FROM stock 
              WHERE outlet_name = ?
              ORDER BY pack_name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $outlet);
    $stmt->execute();
    $result = $stmt->get_result();
    $reportData = $result->fetch_all(MYSQLI_ASSOC);
    
    $reportTitle = "Stock Report - $outlet";
    $pdfContent = generatePDF($reportData, $reportTitle);
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="stock_report.pdf"');
    echo $pdfContent;
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Reports</title>
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
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Stock Reports</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Outlet Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Generate Report</h5>
                                    <form method="post" id="reportForm">
                                        <div class="mb-3">
                                            <label class="form-label">Select Outlet</label>
                                            <select class="form-select" name="outlet_name" id="outlet_select" required>
                                                <option value="">Choose Outlet</option>
                                                <?php
                                                $outletQuery = "SELECT DISTINCT outlet_name FROM stock ORDER BY outlet_name";
                                                $outletResult = $conn->query($outletQuery);
                                                while($row = $outletResult->fetch_assoc()) {
                                                    echo "<option value='{$row['outlet_name']}'>{$row['outlet_name']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <button type="submit" name="generate_pdf" class="btn btn-primary">
                                            Generate PDF Report
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Summary Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Stock Summary</h5>
                                    <div id="stockDetails" class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Outlet Name</th>
                                                    <th>Pack Name</th>
                                                    <th>Available Stock</th>
                                                    <th>Total Value (LKR)</th>
                                                    <th>Last Updated</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $summaryQuery = "SELECT 
                                                    outlet_name,
                                                    pack_name,
                                                    SUM(CASE WHEN stock_status = 'delivered' THEN stock_quantity ELSE 0 END) as available_stock,
                                                    SUM(stock_quantity * max_retail_price) as total_value,
                                                    MAX(created_at) as last_updated
                                                FROM stock
                                                GROUP BY outlet_name, pack_name
                                                ORDER BY outlet_name, pack_name";
                                                
                                                $summaryResult = $conn->query($summaryQuery);
                                                while($row = $summaryResult->fetch_assoc()) {
                                                    echo "<tr class='stock-row' data-outlet='{$row['outlet_name']}'>
                                                        <td>{$row['outlet_name']}</td>
                                                        <td>{$row['pack_name']}</td>
                                                        <td>{$row['available_stock']}</td>
                                                        <td>" . number_format($row['total_value'], 2) . "</td>
                                                        <td>" . date('Y-m-d H:i', strtotime($row['last_updated'])) . "</td>
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
    </div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Dynamic table update when outlet is selected
            $('#outlet_select').change(function() {
                const selectedOutlet = $(this).val();
                
                if(selectedOutlet) {
                    // Filter table rows
                    $('.stock-row').each(function() {
                        const rowOutlet = $(this).data('outlet');
                        if(selectedOutlet === '') {
                            $(this).show(); // Show all rows if no outlet selected
                        } else {
                            $(this).toggle(rowOutlet === selectedOutlet); // Show/hide based on selection
                        }
                    });

                    // Update detailed stock information
                    $.ajax({
                        url: 'get_outlet_stock.php',
                        method: 'POST',
                        data: { outlet: selectedOutlet },
                        success: function(response) {
                            $('#stockDetails').html(response);
                        },
                        error: function(xhr, status, error) {
                            console.error('Ajax error:', error);
                            Swal.fire('Error', 'Failed to fetch stock details', 'error');
                        }
                    });
                } else {
                    // Show all rows if no outlet is selected
                    $('.stock-row').show();
                }
            });
        });
    </script>
</body>
</html> 