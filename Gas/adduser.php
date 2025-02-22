<link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Gas By Gas - User Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">

    <!-- Bootstrap Css -->
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
</head>

<body>
    <div id="layout-wrapper">

        <?php include 'include/adminside.php'; ?>
        <?php include 'include/db.php'; ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">User Management</h4>
                            </div>
                        </div>
                    </div>

                    <!-- User Form -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Add User</h4>
                                </div>
                                <div class="card-body">
                                    <form id="userForm">
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" class="form-control" name="name" id="name" placeholder="Enter Name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" id="email" placeholder="Enter Email" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Phone</label>
                                            <input type="text" class="form-control" name="phone" id="phone" placeholder="Enter Phone" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">NIC</label>
                                            <input type="text" class="form-control" name="nic" id="nic" placeholder="Enter NIC" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Password</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" name="password" id="password" placeholder="Enter Password" required>
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('password', this)">
                                                    Show
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-control" name="status" id="status" required>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                                <option value="pending">Pending</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Role</label>
                                            <select class="form-control" name="role" id="role" required>
                                                <option value="manager">Manager</option>
                                                <option value="admin">Admin</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Table -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">User List</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>NIC</th>
                                                <th>Role</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="userTable">
                                            <?php
                                            $query = "SELECT * FROM users";
                                            $result = $conn->query($query);
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    echo "<tr>
                                                        <td>{$row['id']}</td>
                                                        <td>{$row['name']}</td>
                                                        <td>{$row['email']}</td>
                                                        <td>{$row['phone']}</td>
                                                        <td>{$row['nic']}</td>
                                                        <td>{$row['role']}</td>
                                                        <td>{$row['status']}</td>
                                                        <td>
                                                            <button class='btn btn-sm btn-warning' onclick='editUser({$row['id']})'>Edit</button>
                                                            <button class='btn btn-sm btn-danger' onclick='deleteUser({$row['id']})'>Delete</button>
                                                        </td>
                                                    </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='8'>No users found</td></tr>";
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

    <!-- Update User Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateForm">
                        <input type="hidden" name="id" id="updateId">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" id="updateName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="updateEmail" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" id="updatePhone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">NIC</label>
                            <input type="text" class="form-control" name="nic" id="updateNic" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="updatePassword">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('updatePassword', this)">
                                    Show
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" id="updateStatus" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-control" name="role" id="updateRole" required>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Show and Hide Password -->
    <script>
        function togglePasswordVisibility(fieldId, button) {
            const passwordField = document.getElementById(fieldId);
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                button.textContent = 'Hide';
            } else {
                passwordField.type = 'password';
                button.textContent = 'Show';
            }
        }

        // Add User
        document.getElementById('userForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add');
            fetch('user_management.php', {
                method: 'POST',
                body: formData,
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Success',
                            text: data.message,
                            icon: 'success',
                            timer: 2000, // 3 seconds delay
                            showConfirmButton: false
                        });
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message,
                            icon: 'error',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
        });

        // Edit User
        function editUser(id) {
            fetch(`user_management.php?id=${id}&action=get`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('updateId').value = data.id;
                    document.getElementById('updateName').value = data.name;
                    document.getElementById('updateEmail').value = data.email;
                    document.getElementById('updatePhone').value = data.phone;
                    document.getElementById('updateNic').value = data.nic;
                    document.getElementById('updateStatus').value = data.status;
                    document.getElementById('updateRole').value = data.role;
                    const updateModal = new bootstrap.Modal(document.getElementById('updateModal'));
                    updateModal.show();
                });
        }

        // Update User
        document.getElementById('updateForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update');
            fetch('user_management.php', {
                method: 'POST',
                body: formData,
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Success',
                            text: data.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message,
                            icon: 'error',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
        });

        // Delete User
        function deleteUser(id) {
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
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    fetch('user_management.php', {
                        method: 'POST',
                        body: formData,
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: data.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                setTimeout(() => location.reload(), 2000);
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: data.message,
                                    icon: 'error',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                        });
                }
            });
        }
    </script>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>

</html>
