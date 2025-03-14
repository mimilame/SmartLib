<?php
// location_rack.php

include '../database_connection.php';
include '../function.php';

// Check if admin is logged in
if (!is_admin_login()) {
    header('location:../admin_login.php');
    exit;
}

$message = '';
$error = '';

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
                ':location_rack_created_on' => get_date_time($connect)
            ];

            $query = "INSERT INTO lms_location_rack (location_rack_name, location_rack_status, location_rack_created_on)
                      VALUES (:location_rack_name, :location_rack_status, :location_rack_created_on)";
            $statement = $connect->prepare($query);
            $statement->execute($data);

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
                ':location_rack_updated_on' => get_date_time($connect),
                ':location_rack_id' => $location_rack_id
            ];

            $query = "UPDATE lms_location_rack 
                      SET location_rack_name = :location_rack_name, 
                          location_rack_updated_on = :location_rack_updated_on  
                      WHERE location_rack_id = :location_rack_id";
            $statement = $connect->prepare($query);
            $statement->execute($data);

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
        ':location_rack_updated_on' => get_date_time($connect),
        ':location_rack_id' => $location_rack_id
    ];

    $query = "UPDATE lms_location_rack 
              SET location_rack_status = :location_rack_status, 
                  location_rack_updated_on = :location_rack_updated_on 
              WHERE location_rack_id = :location_rack_id";
    $statement = $connect->prepare($query);
    $statement->execute($data);

    header('location:location_rack.php?msg=' . strtolower($status));
    exit;
}

// =================== FETCH ALL LOCATION RACKS ===================
$query = "SELECT * FROM lms_location_rack ORDER BY location_rack_name ASC";
$statement = $connect->prepare($query);
$statement->execute();
$location_racks = $statement->fetchAll(PDO::FETCH_ASSOC);

include '../header.php';
?>

<!-- =================== FRONTEND =================== -->
<main class="container py-4" style="min-height: 700px;">
    <h1>Location Rack Management</h1>

    <?php if (isset($_GET["action"])): ?>

        <!-- ADD LOCATION RACK -->
        <?php if ($_GET["action"] == 'add'): ?>
            <!-- ADD FORM -->
            <div class="row">
                <div class="col-md-6">
                    <?php if ($error != ''): ?>
                        <div class="alert alert-danger">
                            <ul><?= $error; ?></ul>
                        </div>
                    <?php endif; ?>

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
                        <?php if ($error != ''): ?>
                            <div class="alert alert-danger">
                                <ul><?= $error; ?></ul>
                            </div>
                        <?php endif; ?>

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

        <!-- SUCCESS MESSAGES -->
        <?php if (isset($_GET["msg"])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= ($_GET["msg"] == 'add') ? 'New Location Rack Added' : ''; ?>
                <?= ($_GET["msg"] == 'edit') ? 'Location Rack Updated' : ''; ?>
                <?= ($_GET["msg"] == 'disable') ? 'Location Rack Disabled' : ''; ?>
                <?= ($_GET["msg"] == 'enable') ? 'Location Rack Enabled' : ''; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- LOCATION RACKS TABLE -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-table me-1"></i> Location Rack List</span>
                <a href="location_rack.php?action=add" class="btn btn-success btn-sm">Add</a>
            </div>
            <div class="card-body">
                <table id="dataTable" class="table table-bordered table-striped display responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th></th>
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
                                    <td></td>
                                    <td><?= htmlspecialchars($row["location_rack_name"]); ?></td>
                                    <td>
                                        <div class="badge bg-<?= ($row['location_rack_status'] === 'Enable') ? 'success' : 'danger'; ?>">
                                            <?= $row['location_rack_status']; ?>
                                        </div>
                                    </td>
                                    <td><?= $row["location_rack_created_on"]; ?></td>
                                    <td><?= $row["location_rack_updated_on"] ?? 'N/A'; ?></td>
                                    <td>
                                        <a href="location_rack.php?action=edit&code=<?= convert_data($row["location_rack_id"]); ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="delete_data('<?= convert_data($row["location_rack_id"]); ?>', '<?= $row["location_rack_status"]; ?>')">
                                            <?= $row['location_rack_status'] == 'Enable' ? 'Disable' : 'Enable'; ?>
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

        <script>
            function delete_data(code, status) {
                let new_status = (status === 'Enable') ? 'Disable' : 'Enable';
                if (confirm("Are you sure you want to " + new_status + " this Location Rack?")) {
                    window.location.href = "location_rack.php?action=delete&code=" + code + "&status=" + new_status;
                }
            }

            $(document).ready(function () {
                $('#dataTable').DataTable({
                    responsive: {
                        details: {
                            type: 'column',
                            target: 'tr'
                        }
                    },
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
