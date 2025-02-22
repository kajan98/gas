<?php
// Include the database connection
include 'include/db.php';

// Handle form submission for adding, updating, or deleting a gas pack
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        // Add Gas Pack
        $litro = $_POST['litro'];
        $pack_name = $_POST['pack_name'];
        $max_retail_price = $_POST['max_retail_price'];

        // Insert the gas pack into the database
        $insertQuery = "INSERT INTO litro_gas_packs (litro, pack_name, max_retail_price) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('ssd', $litro, $pack_name, $max_retail_price);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Gas pack added successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add gas pack.']);
        }
        exit;
    } elseif ($action === 'update') {
        // Update Gas Pack
        $id = $_POST['id'];
        $litro = $_POST['litro'];
        $pack_name = $_POST['pack_name'];
        $max_retail_price = $_POST['max_retail_price'];

        $updateQuery = "UPDATE litro_gas_packs SET litro = ?, pack_name = ?, max_retail_price = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('ssdi', $litro, $pack_name, $max_retail_price, $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Gas pack updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update gas pack.']);
        }
        exit;
    } elseif ($action === 'delete') {
        // Delete Gas Pack
        $id = $_POST['id'];

        $deleteQuery = "DELETE FROM litro_gas_packs WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Gas pack deleted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete gas pack.']);
        }
        exit;
    }
}

// Fetch gas pack details for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM litro_gas_packs WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $gasPack = $result->fetch_assoc();
    echo json_encode($gasPack);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gas Pack Management</title>
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
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">Gas Pack Management</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Gas Pack Form -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Add/Update Gas Pack</h4>
                                </div>
                                <div class="card-body">
                                    <form id="gasPackForm">
                                        <input type="hidden" name="id" id="id">
                                        <div class="mb-3">
                                            <label for="litro" class="form-label">Litre</label>
                                            <input type="text" class="form-control" id="litre" name="litre" placeholder="Enter litre size (e.g., 12.5kg)" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="pack_name" class="form-label">Pack Name</label>
                                            <input type="text" class="form-control" id="pack_name" name="pack_name" placeholder="Enter pack name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="max_retail_price" class="form-label">Maximum Retail Price</label>
                                            <input type="number" step="0.01" class="form-control" id="max_retail_price" name="max_retail_price" placeholder="Enter price" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                 
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">Gas Pack List</h4>
                <!-- Search and Filters -->
                <div class="d-flex gap-4">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by Litre or Pack Name">
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered" id="gasPackTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Litre</th>
                            <th>Pack Name</th>
                            <th>Maximum Retail Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM litro_gas_packs";
                        $result = $conn->query($query);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['litro']}</td>
                                    <td>{$row['pack_name']}</td>
                                    <td>LKR {$row['max_retail_price']}</td>
                                    <td>
                                        <button class='btn btn-sm btn-warning' onclick='editGasPack({$row['id']})'>Edit</button>
                                        <button class='btn btn-sm btn-danger' onclick='deleteGasPack({$row['id']})'>Delete</button>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No gas packs found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<script>
    // Search Functionality
    document.getElementById('searchInput').addEventListener('input', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#gasPackTable tbody tr');

        rows.forEach(row => {
            const litro = row.cells[1].textContent.toLowerCase();
            const packName = row.cells[2].textContent.toLowerCase();
            row.style.display = (litro.includes(filter) || packName.includes(filter)) ? '' : 'none';
        });
    });
</script>


                </div>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script>
        // Add or Update Gas Pack
        $('#gasPackForm').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();
            const action = $('#id').val() ? 'update' : 'add';
            $.post('manage_gas_packs.php', formData + `&action=${action}`, function(response) {
                const res = JSON.parse(response);
                if (res.status === 'success') {
                    Swal.fire('Success', res.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        });

        // Edit Gas Pack
        function editGasPack(id) {
            $.get(`manage_gas_packs.php?id=${id}`, function(response) {
                const gasPack = JSON.parse(response);
                $('#id').val(gasPack.id);
                $('#litro').val(gasPack.litro);
                $('#pack_name').val(gasPack.pack_name);
                $('#max_retail_price').val(gasPack.max_retail_price);
            });
        }

        // Delete Gas Pack
        function deleteGasPack(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('manage_gas_packs.php', { id: id, action: 'delete' }, function(response) {
                        const res = JSON.parse(response);
                        if (res.status === 'success') {
                            Swal.fire('Deleted!', res.message, 'success');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>

</html>
