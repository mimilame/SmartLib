<?php
include '../database_connection.php';
include '../function.php';

if(!is_admin_login()) {
    header('location:../admin_login.php');
}

// Delete (Disable) Logic
if(isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {
    $rack_id = $_GET["code"];
    $status = $_GET["status"];

    $data = array(
        ':location_rack_status'    => $status,
        ':location_rack_updated_on' => get_date_time($connect),
        ':location_rack_id'        => $rack_id
    );

    $query = "
    UPDATE lms_location_rack 
    SET location_rack_status = :location_rack_status, 
        location_rack_updated_on = :location_rack_updated_on 
    WHERE location_rack_id = :location_rack_id
    ";

    $statement = $connect->prepare($query);
    $statement->execute($data);

    header('location:rack.php?msg='.strtolower($status).'');
}

// ADD Rack Logic
if(isset($_GET["action"]) && $_GET["action"] == 'add') {
    // Check if form is submitted
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rack_name = trim($_POST['rack_name']);

        if($rack_name == '') {
            $error = 'Rack Name is required!';
        } else {
            // Insert query
            $data = array(
                ':location_rack_name'      => $rack_name,
                ':location_rack_status'    => 'Enable',
                ':location_rack_created_on'=> get_date_time($connect),
                ':location_rack_updated_on'=> get_date_time($connect)
            );

            $query = "
            INSERT INTO lms_location_rack 
            (location_rack_name, location_rack_status, location_rack_created_on, location_rack_updated_on)
            VALUES (:location_rack_name, :location_rack_status, :location_rack_created_on, :location_rack_updated_on)
            ";

            $statement = $connect->prepare($query);
            $statement->execute($data);

            header('location:rack.php?msg=addsuccess');
        }
    }

    include '../header.php';
    ?>

    <main class="container py-4" style="min-height: 700px;">
        <h1 class="my-3">Add New Location Rack</h1>

        <?php 
        if(isset($error)) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'.$error.'
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        }
        ?>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="rack_name" class="form-label">Rack Name <span class="text-danger">*</span></label>
                        <input type="text" name="rack_name" id="rack_name" class="form-control" required>
                    </div>

                    <div class="text-end">
                        <a href="rack.php" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-success">Save Rack</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include '../footer.php'; ?>
    <?php
    exit; // Prevents rendering the rack list if in add mode
}

// SELECT Query for all racks
$query = "
    SELECT * FROM lms_location_rack 
    ORDER BY location_rack_id DESC
";

$statement = $connect->prepare($query);
$statement->execute();

// Fetch data
$racks = $statement->fetchAll(PDO::FETCH_ASSOC);

include '../header.php';
?>

<main class="container py-4" style="min-height: 700px;">
    <h1 class="my-3">Location Rack Management</h1>

    <?php 
    if(isset($_GET["msg"])) {
        if($_GET["msg"] == 'disable') {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Rack Status Changed to Disable <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }

        if($_GET["msg"] == 'enable') {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Rack Status Changed to Enable <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }

        if($_GET["msg"] == 'addsuccess') {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">New Rack Added Successfully <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
    }
    ?>

    <div class="card mb-4">
        <div class="card-header">
            <div class="row">
                <div class="col col-md-6">
                    <i class="fas fa-table me-1"></i> Location Rack List
                </div>
                <div class="col col-md-6">
                    <a href="rack.php?action=add" class="btn btn-success btn-sm float-end">Add Rack</a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table id="dataTable" class="table table-bordered table-striped display responsive nowrap py-4 dataTable no-footer collapsed table-active" style="width:100%">
                <thead>
                    <tr>
                        <th style="width: 50px;">Rack ID</th>
                        <th>Rack Name</th>
                        <th>Rack Status</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(count($racks) > 0) {
                        foreach($racks as $row) {
                            // Status badge
                            $status_badge = $row["location_rack_status"] == "Enable" ? 
                                '<span class="badge bg-success">Available</span>' : 
                                '<span class="badge bg-danger">Not Available</span>';

                            echo '
                            <tr>
                                <td>'.$row["location_rack_id"].'</td>
                                <td>'.$row["location_rack_name"].'</td>
                                <td>'.$status_badge.'</td>
                                <td>'.$row["location_rack_created_on"].'</td>
                                <td>'.$row["location_rack_updated_on"].'</td>
                                <td class="text-center">
                                    <a href="rack.php?action=view&code='.convert_data($row["location_rack_id"]).'" class="btn btn-info btn-sm mb-1">View</a>
                                    <a href="rack.php?action=edit&code='.convert_data($row["location_rack_id"]).'" class="btn btn-primary btn-sm mb-1">Edit</a>
                                    <button type="button" name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data(`'.convert_data($row["location_rack_id"]).'`)">Delete</button>
                                </td>
                            </tr>';
                        }
                    } else {
                        echo '
                        <tr>
                            <td colspan="6" class="text-center">No Data Found</td>
                        </tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
function delete_data(code) {
    if(confirm("Are you sure you want to disable this Rack?")) {
        window.location.href = "rack.php?action=delete&code=" + code + "&status=Disable";
    }
}

$(document).ready(function() {    
    $('#dataTable').DataTable({
        responsive: true,
        columnDefs: [
            { responsivePriority: 1, targets: [0, 1, 5] },
            { responsivePriority: 2, targets: [2] },
            { responsivePriority: 10000, targets: [3, 4] }
        ],
        order: [[0, 'asc']],
        autoWidth: false,
        language: {
            emptyTable: "No data available"
        }
    });
});
</script>

<?php include '../footer.php'; ?>
