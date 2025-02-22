<?php
session_start();
require_once 'include/db.php';
require 'vendor/autoload.php';
use TCPDF as TCPDF;

// Function to generate PDF
function generatePDF($reportData, $reportTitle) {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator('Gas by Gas');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Industrial Report');
    
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $reportTitle, 0, 1, 'C');
    $pdf->Ln(10);
    
    // Add table headers
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(30, 7, 'Request ID', 1);
    $pdf->Cell(40, 7, 'Company', 1);
    $pdf->Cell(30, 7, 'Pack', 1);
    $pdf->Cell(20, 7, 'Qty', 1);
    $pdf->Cell(40, 7, 'Status', 1);
    $pdf->Ln();
    
    // Add table data
    $pdf->SetFont('helvetica', '', 10);
    foreach($reportData as $row) {
        $pdf->Cell(30, 6, $row['request_id'], 1);
        $pdf->Cell(40, 6, $row['company_name'], 1);
        $pdf->Cell(30, 6, $row['pack_name'], 1);
        $pdf->Cell(20, 6, $row['quantity'], 1);
        $pdf->Cell(40, 6, $row['status'], 1);
        $pdf->Ln();
    }
    
    return $pdf->Output('industrial_report.pdf', 'S');
}

// Fetch data from database with date range
function getIndustrialData($conn, $startDate = null, $endDate = null) {
    $query = "SELECT ir.request_id, ir.request_order_id, ir.outlet_name, 
                     ir.pack_name, ir.quantity, ir.token_id, 
                     ir.status, ir.created_at, ir.pickup_date, iu.company_name
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
    return $stmt->get_result();
}

// Handle PDF generation request
if(isset($_POST['generate_pdf'])) {
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    
    $result = getIndustrialData($conn, $startDate, $endDate);
    $reportData = $result->fetch_all(MYSQLI_ASSOC);
    
    $reportTitle = "Industrial Report";
    if ($startDate && $endDate) {
        $reportTitle .= " ($startDate to $endDate)";
    }
    
    $pdfContent = generatePDF($reportData, $reportTitle);
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="industrial_report.pdf"');
    echo $pdfContent;
    exit;
}

// Get data for display in table
$data = getIndustrialData($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Industrial Report</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <style>
        /* ... (keep the same styles as consumer_report.php) ... */
    </style>
</head>
<body>
    <div id="layout-wrapper">
        <?php include 'include/adminside.php'; ?>
        
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Page Title -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-0">Industrial Report</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Generate Report</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" class="filter-form" id="reportForm">
                                        <div class="row align-items-end">
                                            <div class="col-md-4">
                                                <div class="mb-3 mb-md-0">
                                                    <label class="form-label">Start Date</label>
                                                    <input type="date" class="form-control" name="start_date" id="start_date" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3 mb-md-0">
                                                    <label class="form-label">End Date</label>
                                                    <input type="date" class="form-control" name="end_date" id="end_date" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="submit" name="generate_pdf" class="btn btn-primary btn-generate">
                                                    <i class="fas fa-file-pdf me-2"></i>Generate PDF
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Industrial Requests</h5>
                                    <div id="totalRecords" class="text-muted"></div>
                                </div>
                                <div class="card-body">
                                    <div class="table-container" id="tableContainer">
                                        <!-- Table content will be loaded here -->
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
        // Function to load table data
        function loadTableData(startDate = '', endDate = '') {
            $.ajax({
                url: 'get_industrial_data.php',
                type: 'POST',
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    $('#tableContainer').html(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to load data', 'error');
                }
            });
        }

        // Load initial data
        loadTableData();

        // Handle form submission
        $('#reportForm').on('submit', function(e) {
            if (!$(this).find('button[name="generate_pdf"]').is(':focus')) {
                e.preventDefault();
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                loadTableData(startDate, endDate);
            }
        });

        // Date validation
        $('#end_date').on('change', function() {
            var startDate = $('#start_date').val();
            var endDate = $(this).val();
            
            if (startDate && endDate && startDate > endDate) {
                Swal.fire('Error', 'End date must be after start date', 'error');
                $(this).val('');
            }
        });
    });
    </script>
</body>
</html>

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