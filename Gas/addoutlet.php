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

                    <!-- Outlet Form -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Add/Update Outlet</h4>
                                </div>
                                <div class="card-body">
                                    <form id="outletForm">
                                        <input type="hidden" name="id" id="id">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Outlet Name</label>
                                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter outlet name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="location" class="form-label">Location</label>
                                            <input type="text" class="form-control" id="location" name="location" placeholder="Enter location" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="manager_name" class="form-label">Manager</label>
                                            <select class="form-control" id="manager_name" name="manager_name" required>
                                                <option value="">Select Manager</option>
                                                <?php foreach ($managers as $manager) : ?>
                                                    <option value="<?= $manager ?>"><?= $manager ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="contact_number" class="form-label">Contact Number</label>
                                            <input type="text" class="form-control" id="contact_number" name="contact_number" placeholder="Enter contact number" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-control" id="status" name="status" required>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Outlet Table -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Outlet List</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Outlet Name</th>
                                                <th>Location</th>
                                                <th>Manager</th>
                                                <th>Contact Number</th>
                                                <th>Email</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="outletTable">
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
                                                        <td>
                                                            <button class='btn btn-sm btn-warning' onclick='editOutlet({$row['id']})' data-bs-toggle='modal' data-bs-target='#editModal'>Edit</button>
                                                            <button class='btn btn-sm btn-danger' onclick='deleteOutlet({$row['id']})'>Delete</button>
                                                        </td>
                                                    </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='8'>No outlets found</td></tr>";
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
