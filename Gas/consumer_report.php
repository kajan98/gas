<?php
session_start();
require_once 'include/db.php';
require 'vendor/autoload.php'; // For TCPDF
use TCPDF as TCPDF;

// Function to generate PDF
function generatePDF($reportData, $reportTitle) {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Gas by Gas');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Consumer Report');
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    
    // Add title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $reportTitle, 0, 1, 'C');
    $pdf->Ln(10);
    
    // Add table headers
    $pdf->SetFont('helvetica', 'B', 11);

    $pdf->Cell(30, 7, 'Token ID', 1);
    $pdf->Cell(40, 7, 'Consumer', 1);
    $pdf->Cell(30, 7, 'Pack', 1);
    $pdf->Cell(20, 7, 'Qty', 1);
    $pdf->Cell(40, 7, 'Status', 1);
    $pdf->Ln();
    
    // Add table data
    $pdf->SetFont('helvetica', '', 10);
    foreach($reportData as $row) {
      
        $pdf->Cell(30, 6, $row['token_id'], 1);
        $pdf->Cell(40, 6, $row['consumer_name'], 1);
        $pdf->Cell(30, 6, $row['pack_name'], 1);
        $pdf->Cell(20, 6, $row['quantity'], 1);
        $pdf->Cell(40, 6, $row['status'], 1);
        $pdf->Ln();
    }
    
    return $pdf->Output('consumer_report.pdf', 'S');
}

// Fetch data from database with date range
function getConsumerData($conn, $startDate = null, $endDate = null) {
    $query = "SELECT cr.request_order_id, cr.token_id, cr.outlet_name, 
                     cr.pack_name, cr.quantity, cr.requested_date, 
                     cr.pickup_date, cr.status, cr.cylinder_status, 
                     cr.payment_status, c.name as consumer_name, 
                     c.email as consumer_email
              FROM consumer_requests cr
              LEFT JOIN consumer c ON cr.user_id = c.id";

    if ($startDate && $endDate) {
        $query .= " WHERE cr.requested_date BETWEEN ? AND ?";
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
    
    $result = getConsumerData($conn, $startDate, $endDate);
    $reportData = $result->fetch_all(MYSQLI_ASSOC);
    
    $reportTitle = "Consumer Report";
    if ($startDate && $endDate) {
        $reportTitle .= " ($startDate to $endDate)";
    }
    
    $pdfContent = generatePDF($reportData, $reportTitle);
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="consumer_report.pdf"');
    echo $pdfContent;
    exit;
}

// Get data for display in table
$data = getConsumerData($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumer Report</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <style>
        .card {
            border: none;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            padding: 1rem;
        }
        .filter-form {
            padding: 1.5rem;
            background: #fff;
            border-radius: 8px;
        }
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
            text-align: center;
            min-width: 90px;
        }
        .table-container {
            overflow-x: auto;
            margin: 1rem 0;
        }
        .table {
            white-space: nowrap;
            min-width: 800px;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .btn-generate {
            padding: 0.6rem 1.5rem;
            font-weight: 500;
        }
        @media (max-width: 768px) {
            .filter-form .row {
                gap: 1rem;
            }
            .btn-generate {
                width: 100%;
            }
        }
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
                                    <h4 class="card-title mb-0">Consumer Report</h4>
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
                                    <h5 class="mb-0">Consumer Requests</h5>
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
                url: 'get_consumer_data.php',
                method: 'POST',
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                beforeSend: function() {
                    $('#tableContainer').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><div class="mt-2">Loading...</div></div>');
                },
                success: function(response) {
                    $('#tableContainer').html(response);
                    updateTotalRecords();
                },
                error: function() {
                    $('#tableContainer').html('<div class="alert alert-danger">Error loading data</div>');
                }
            });
        }

        // Function to update total records count
        function updateTotalRecords() {
            const rowCount = $('#dataTable tbody tr').length;
            $('#totalRecords').text(`Total Records: ${rowCount}`);
        }

        // Initial load
        loadTableData();

        // Handle date filter changes
        $('#start_date, #end_date').on('change', function() {
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            
            if (startDate && endDate) {
                if (startDate > endDate) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Date Error',
                        text: 'Start date cannot be later than end date'
                    });
                    return;
                }
                loadTableData(startDate, endDate);
            }
        });

        // Handle form submission for PDF generation
        $('#reportForm').on('submit', function(e) {
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            
            if (startDate > endDate) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Date Error',
                    text: 'Start date cannot be later than end date'
                });
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