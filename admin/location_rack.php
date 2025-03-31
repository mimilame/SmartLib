<?php
// location_rack.php

include '../database_connection.php';
include '../function.php';


$message = '';
$error = '';
$alert = '';

// =================== ADD LOCATION RACK ===================
if (isset($_POST["add_location_rack"])) {
    $formdata = [];

    if (empty($_POST["location_rack_name"])) {
        $error .= '<li>Location Rack Name is required</li>';
    } else {
        $formdata['location_rack_name'] = trim($_POST["location_rack_name"]);
    }

    if ($error == '') {
        // Check if the location rack name already exists
        $query = "SELECT * FROM lms_location_rack WHERE location_rack_name = :location_rack_name";
        $statement = $connect->prepare($query);
        $statement->execute([':location_rack_name' => $formdata['location_rack_name']]);

        if ($statement->rowCount() > 0) {
            $error = '<li>Location Rack Name Already Exists</li>';
        } else {
            // Insert the new location rack
            $data = [
                ':location_rack_name' => $formdata['location_rack_name'],
                ':location_rack_status' => 'Enable',
                ':rack_created_on' => get_date_time($connect)
            ];

            $query = "INSERT INTO lms_location_rack (location_rack_name, location_rack_status, rack_created_on)
                      VALUES (:location_rack_name, :location_rack_status, :rack_created_on)";
            $statement = $connect->prepare($query);
            $statement->execute($data);

            set_flash_message('success', 'New Location Rack Added Successfully');
            header('location:location_rack.php?msg=add');
            exit;
        }
    }
}

// =================== EDIT LOCATION RACK ===================
if (isset($_POST["edit_location_rack"])) {
    $formdata = [];

    if (empty($_POST["location_rack_name"])) {
        $error .= '<li>Location Rack Name is required</li>';
    } else {
        $formdata['location_rack_name'] = trim($_POST["location_rack_name"]);
    }

    if ($error == '') {
        $location_rack_id = convert_data($_POST["location_rack_id"], 'decrypt');

        $query = "SELECT * FROM lms_location_rack 
                  WHERE location_rack_name = :location_rack_name 
                  AND location_rack_id != :location_rack_id";
        $statement = $connect->prepare($query);
        $statement->execute([
            ':location_rack_name' => $formdata['location_rack_name'],
            ':location_rack_id' => $location_rack_id
        ]);

        if ($statement->rowCount() > 0) {
            $error = '<li>Location Rack Name Already Exists</li>';
        } else {
            $data = [
                ':location_rack_name' => $formdata['location_rack_name'],
                ':rack_updated_on' => get_date_time($connect),
                ':location_rack_id' => $location_rack_id
            ];

            $query = "UPDATE lms_location_rack 
                      SET location_rack_name = :location_rack_name, 
                          rack_updated_on = :rack_updated_on  
                      WHERE location_rack_id = :location_rack_id";
            $statement = $connect->prepare($query);
            $statement->execute($data);
            set_flash_message('success', 'Location Rack Updated Successfully');
            header('location:location_rack.php?msg=edit');
            exit;
        }
    }
}

// =================== DELETE/STATUS TOGGLE ===================
if (isset($_GET["action"], $_GET["code"], $_GET["status"]) && $_GET["action"] == 'delete') {
    $location_rack_id = convert_data($_GET["code"], 'decrypt');
    $status = $_GET["status"];

    $data = [
        ':location_rack_status' => $status,
        ':rack_updated_on' => get_date_time($connect),
        ':location_rack_id' => $location_rack_id
    ];

    $query = "UPDATE lms_location_rack 
              SET location_rack_status = :location_rack_status, 
                  rack_updated_on = :rack_updated_on 
              WHERE location_rack_id = :location_rack_id";
    $statement = $connect->prepare($query);
    $statement->execute($data);
    $message = ($status == 'Enable') ? 'Location Rack Marked as Available' : 'Location Rack Marked as Full';
    set_flash_message('success', $message);
    header('location:location_rack.php?msg=' . strtolower($status));
    exit;
}

// =================== FETCH ALL LOCATION RACKS ===================
$query = "SELECT * FROM lms_location_rack ORDER BY location_rack_name ASC";
$statement = $connect->prepare($query);
$statement->execute();
$location_racks = $statement->fetchAll(PDO::FETCH_ASSOC);

include '../header.php';

// Check for flash messages
$success_message = get_flash_message('success');
if($success_message != '') {
    $alert = sweet_alert('success', $success_message);
}

// For form validation errors
if($error != '') {
    $alert_message = str_replace('<li>', '', $error);
    $alert_message = str_replace('</li>', '', $alert_message);
    $alert = sweet_alert('error', $alert_message);
}

?>

<!-- =================== FRONTEND =================== -->
<main class="container py-4" style="min-height: 700px;">
    <h1>Location Rack Management</h1>
    <!-- Display the Sweet Alert if it exists -->
    <?php echo $alert; ?>
    <?php if (isset($_GET["action"])): ?>

        <!-- ADD LOCATION RACK -->
        <?php if ($_GET["action"] == 'add'): ?>
            <!-- ADD FORM -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-user-plus"></i> Add New Location Rack</div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label">Location Rack Name</label>
                                    <input type="text" name="location_rack_name" class="form-control" />
                                </div>
                                <div class="mt-4 mb-0">
                                    <input type="submit" name="add_location_rack" class="btn btn-success" value="Add" />
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <!-- EDIT LOCATION RACK -->
        <?php elseif ($_GET["action"] == 'edit'):
            $location_rack_id = convert_data($_GET["code"], 'decrypt');

            $query = "SELECT * FROM lms_location_rack WHERE location_rack_id = :location_rack_id";
            $statement = $connect->prepare($query);
            $statement->execute([':location_rack_id' => $location_rack_id]);

            $location_rack_row = $statement->fetch(PDO::FETCH_ASSOC);
            if ($location_rack_row): ?>

                <!-- EDIT FORM -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header"><i class="fas fa-user-edit"></i> Edit Location Rack Details</div>
                            <div class="card-body">
                                <form method="post">
                                    <div class="mb-3">
                                        <label class="form-label">Location Rack Name</label>
                                        <input type="text" name="location_rack_name" class="form-control" value="<?= htmlspecialchars($location_rack_row["location_rack_name"]); ?>" />
                                    </div>
                                    <div class="mt-4 mb-0">
                                        <input type="hidden" name="location_rack_id" value="<?= $_GET['code']; ?>" />
                                        <input type="submit" name="edit_location_rack" class="btn btn-primary" value="Edit" />
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif;
        endif; ?>

    <?php else: ?>
        <!-- LOCATION RACKS TABLE -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-table me-1"></i> Location Rack List</span>
                <a href="location_rack.php?action=add" class="btn btn-success btn-sm">Add Rack</a>
            </div>
            <div class="card-body">
                <table id="dataTable" class="table table-bordered table-striped display responsive nowrap py-4 dataTable no-footer dtr-column collapsed " style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Location Rack Name</th>
                            <th>Status</th>
                            <th>Created On</th>
                            <th>Updated On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($location_racks)): ?>
                            <?php foreach ($location_racks as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['location_rack_id']); ?></td>
                                    <td><?= htmlspecialchars($row["location_rack_name"]); ?></td>
                                    <td>
                                        <div class="badge bg-<?= ($row['location_rack_status'] === 'Enable') ? 'success' : 'danger'; ?>">
                                            <?= ($row['location_rack_status'] === 'Enable') ? 'Available' : 'Full'; ?>
                                        </div>
                                    </td>
                                    <td><?= $row["rack_created_on"]; ?></td>
                                    <td><?= $row["rack_updated_on"] ?? 'N/A'; ?></td>
                                    <td>
                                        <a href="javascript:void(0);" onclick="openEditModal('<?= convert_data($row["location_rack_id"]); ?>', '<?= htmlspecialchars($row["location_rack_name"]); ?>')" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>
                                        <button type="button" class="btn btn-<?= ($row['location_rack_status'] === 'Enable') ? 'danger' : 'success'; ?> btn-sm"
                                            onclick="toggle_status('<?= convert_data($row["location_rack_id"]); ?>', '<?= $row["location_rack_status"]; ?>')">
                                            <?= ($row['location_rack_status'] === 'Enable') ? 'Mark as Full' : 'Mark as Available'; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No Location Racks Found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editLocationRackModal" tabindex="-1" aria-labelledby="editLocationRackModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLocationRackModalLabel">Edit Location Rack</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_location_rack_name" class="form-label">Location Rack Name</label>
                        <input type="text" class="form-control" id="edit_location_rack_name" name="location_rack_name" required>
                    </div>
                    <input type="hidden" id="edit_location_rack_id" name="location_rack_id">
                    </div>
                    <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_location_rack" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <script>
            function toggle_status(code, status) {
                let newStatus = status === 'Enable' ? 'Disable' : 'Enable';
                let statusText = status === 'Enable' ? 'mark as Full' : 'mark as Available';
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to " + statusText + " this location rack?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, ' + statusText + '!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "location_rack.php?action=delete&code=" + code + "&status=" + newStatus;
                    }
                });
            }

            // Function to open edit modal
            function openEditModal(id, name) {
                document.getElementById('edit_location_rack_id').value = id;
                document.getElementById('edit_location_rack_name').value = name;
                
                // Show the modal
                const editModal = new bootstrap.Modal(document.getElementById('editLocationRackModal'));
                editModal.show();
            }

            $(document).ready(function () {
    $('#dataTable').DataTable({
        responsive: {
            details: {
                type: 'column',
                target: 'tr'
            }
        },
        scrollY:        '400px',
        scrollX:        true,
        scrollCollapse: true,
        paging:         true,
        columnDefs: [
            { className: 'dtr-control', orderable: false, targets: 0 },
            { responsivePriority: 1, targets: 1 },
            { responsivePriority: 2, targets: 2 },
            { responsivePriority: 3, targets: 3 },
            { responsivePriority: 4, targets: 4 },
            { responsivePriority: 5, targets: 5 }
        ],
        order: [[1, 'asc']]
    });


            });
        </script>

    <?php endif; ?>
</main>


<?php include '../footer.php'; ?>
