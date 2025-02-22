<?php include 'include/adminside.php'; ?>
<?php include 'include/db.php'; ?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Gas By Gas - Consumer Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
</head>

<body>
    <div id="layout-wrapper">
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0 font-size-18">Consumer Management</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="card-title">Consumer List</h4>
                                    <div class="d-flex gap-2">
                                        <select id="statusFilter" class="form-select w-auto">
                                            <option value="">All Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                        <input type="text" id="nicSearch" class="form-control w-auto" placeholder="Search by NIC">
                                        <input type="text" id="emailSearch" class="form-control w-auto" placeholder="Search by Email">
                                    </div>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered" id="consumerTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>NIC</th>
                                                <th>Address</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = "SELECT * FROM consumer ORDER BY created_at DESC";
                                            $result = $conn->query($query);
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    $statusClass = $row['status'] === 'active' ? 'badge bg-success' : 'badge bg-danger';
                                                    echo "<tr data-status='{$row['status']}' 
                                                             data-nic='{$row['nic']}' 
                                                             data-email='{$row['email']}'>
                                                        <td>{$row['id']}</td>
                                                        <td>{$row['name']}</td>
                                                        <td>{$row['email']}</td>
                                                        <td>{$row['phone']}</td>
                                                        <td>{$row['nic']}</td>
                                                        <td>{$row['address']}</td>
                                                        <td><span class='{$statusClass}'>{$row['status']}</span></td>
                                                        <td>
                                                            <button class='btn btn-sm btn-primary' onclick='updateStatus({$row['id']}, \"{$row['status']}\")'>Update Status</button>
                                                            <button class='btn btn-sm btn-danger' onclick='deleteConsumer({$row['id']})'>Delete</button>
                                                        </td>
                                                    </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='8'>No consumers found</td></tr>";
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

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="statusForm">
                        <input type="hidden" id="userId" name="id">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Combined filter functionality
        function filterTable() {
            const nicSearch = document.getElementById('nicSearch').value.toLowerCase();
            const emailSearch = document.getElementById('emailSearch').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const rows = document.querySelectorAll('#consumerTable tbody tr');
            
            rows.forEach(row => {
                const nic = row.getAttribute('data-nic').toLowerCase();
                const email = row.getAttribute('data-email').toLowerCase();
                const status = row.getAttribute('data-status');
                
                const matchesNic = nic.includes(nicSearch);
                const matchesEmail = email.includes(emailSearch);
                const matchesStatus = statusFilter === '' || status === statusFilter;
                
                row.style.display = matchesNic && matchesEmail && matchesStatus ? '' : 'none';
            });
        }

        // Add event listeners
        document.getElementById('nicSearch').addEventListener('input', filterTable);
        document.getElementById('emailSearch').addEventListener('input', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);

        // Update Status function
        function updateStatus(id, currentStatus) {
            document.getElementById('userId').value = id;
            const statusSelect = document.querySelector('#statusForm select[name="status"]');
            statusSelect.value = currentStatus;
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }

        // Delete Consumer function
        function deleteConsumer(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    
                    fetch('consumer_management.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.status === 'success') {
                            Swal.fire('Deleted!', data.message, 'success')
                            .then(() => location.reload());
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    });
                }
            });
        }

        // Status Form Submit
        document.getElementById('statusForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update_status');
            
            fetch('consumer_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire({
                        title: 'Success',
                        text: data.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        icon: 'error'
                    });
                }
            });
        });
    </script>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html> 