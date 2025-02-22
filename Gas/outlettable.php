<?php
// Include the database connection
include 'include/db.php';

// Handle form submission for adding, updating, or deleting an outlet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        // Add Outlet
        $name = $_POST['name'];
        $location = $_POST['location'];
        $manager_name = $_POST['manager_name'];
        $contact_number = $_POST['contact_number'];
        $email = $_POST['email'];
        $status = $_POST['status'];

        // Check if email is already in use
        $checkEmailQuery = "SELECT id FROM outlets WHERE email = ?";
        $stmt = $conn->prepare($checkEmailQuery);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email is already in use.']);
            exit;
        }

        // Insert the outlet into the database
        $insertQuery = "INSERT INTO outlets (name, location, manager_name, contact_number, email, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('ssssss', $name, $location, $manager_name, $contact_number, $email, $status);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Outlet added successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add outlet.']);
        }
        exit;
    } elseif ($action === 'update') {
        // Update Outlet
        $id = $_POST['id'];
        $name = $_POST['name'];
        $location = $_POST['location'];
        $manager_name = $_POST['manager_name'];
        $contact_number = $_POST['contact_number'];
        $email = $_POST['email'];
        $status = $_POST['status'];

        $updateQuery = "UPDATE outlets SET name = ?, location = ?, manager_name = ?, contact_number = ?, email = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('ssssssi', $name, $location, $manager_name, $contact_number, $email, $status, $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Outlet updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update outlet.']);
        }
        exit;
    } elseif ($action === 'delete') {
        // Delete Outlet
        $id = $_POST['id'];

        $deleteQuery = "DELETE FROM outlets WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Outlet deleted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete outlet.']);
        }
        exit;
    }
}

// Fetch managers for the dropdown
$managersQuery = "SELECT name FROM users WHERE role = 'manager'";
$managersResult = $conn->query($managersQuery);
$managers = [];
if ($managersResult->num_rows > 0) {
    while ($row = $managersResult->fetch_assoc()) {
        $managers[] = $row['name'];
    }
}

// Fetch outlet details for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM outlets WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $outlet = $result->fetch_assoc();
    echo json_encode($outlet);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outlet Management</title>
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
                                <h4 class="mb-sm-0 font-size-18">Outlet Management</h4>
                            </div>
                        </div>
                    </div>

                

                   <!-- Outlet Table with Filters -->
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">Outlet List</h4>
                <!-- Search and Filters -->
                <div class="d-flex gap-">
                    <input type="text" id="searchInput" class="form-control w-50" placeholder="Search All Columns">
                    <select id="statusFilter" class="form-control">
                        <option value="">Filter by Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <select id="managerFilter" class="form-control">
                        <option value="">Filter by Manager</option>
                        <?php
                        // Fetch managers for the dropdown
                        $managersQuery = "SELECT DISTINCT manager_name FROM outlets";
                        $managersResult = $conn->query($managersQuery);
                        if ($managersResult->num_rows > 0) {
                            while ($managerRow = $managersResult->fetch_assoc()) {
                                echo "<option value='{$managerRow['manager_name']}'>{$managerRow['manager_name']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered" id="outletTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Outlet Name</th>
                            <th>Location</th>
                            <th>Manager</th>
                            <th>Contact Number</th>
                            <th>Email</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM outlets";
                        $result = $conn->query($query);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['name']}</td>
                                    <td>{$row['location']}</td>
                                    <td>{$row['manager_name']}</td>
                                    <td>{$row['contact_number']}</td>
                                    <td>{$row['email']}</td>
                                    <td>{$row['status']}</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>No outlets found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Search and Filters -->
<script>
    document.getElementById('searchInput').addEventListener('input', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#outletTable tbody tr');

        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(filter) ? '' : 'none';
        });
    });

    document.getElementById('statusFilter').addEventListener('change', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#outletTable tbody tr');

        rows.forEach(row => {
            const status = row.cells[6].textContent.toLowerCase();
            row.style.display = !filter || status === filter ? '' : 'none';
        });
    });

    document.getElementById('managerFilter').addEventListener('change', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#outletTable tbody tr');

        rows.forEach(row => {
            const manager = row.cells[3].textContent.toLowerCase();
            row.style.display = !filter || manager === filter ? '' : 'none';
        });
    });
</script>


                </div>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>
    <script>
        // Add or Update Outlet
        $('#outletForm').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();
            const action = $('#id').val() ? 'update' : 'add';
            $.post('addoutlet.php', formData + `&action=${action}`, function(response) {
                const res = JSON.parse(response);
                if (res.status === 'success') {
                    Swal.fire('Success', res.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        });

        // Edit Outlet
        function editOutlet(id) {
            $.get(`addoutlet.php?id=${id}`, function(response) {
                const outlet = JSON.parse(response);
                $('#id').val(outlet.id);
                $('#name').val(outlet.name);
                $('#location').val(outlet.location);
                $('#manager_name').val(outlet.manager_name);
                $('#contact_number').val(outlet.contact_number);
                $('#email').val(outlet.email);
                $('#status').val(outlet.status);
            });
        }

        // Delete Outlet
        function deleteOutlet(id) {
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
                    $.post('addoutlet.php', { id: id, action: 'delete' }, function(response) {
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
