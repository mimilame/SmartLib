<?php
// location_rack.php

include '../database_connection.php';
include '../function.php';

require_once('library_map_component.php');

$message = ''; 

// DELETE (Disable/Enable)
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {
    $location_rack_id = $_GET["code"];
    $status = $_GET["status"];

    $data = array(
        ':location_rack_status' => $status,
        ':location_rack_id'     => $location_rack_id
    );

    $query = "
    UPDATE lms_location_rack 
    SET location_rack_status = :location_rack_status 
    WHERE location_rack_id = :location_rack_id";

    $statement = $connect->prepare($query);
    $statement->execute($data);

    header('location:location_rack.php?msg=' . strtolower($status) . '');
    exit;
}

// ADD rack (Form Submit)
if (isset($_POST['add_rack'])) {
    $name = trim($_POST['location_rack_name']);
    $status = $_POST['location_rack_status'];
    $position_x = isset($_POST['position_x']) ? (int)$_POST['position_x'] : 0;
    $position_y = isset($_POST['position_y']) ? (int)$_POST['position_y'] : 0;

    // Check for duplicate (case-insensitive comparison)
    $check_query = "
        SELECT COUNT(*) 
        FROM lms_location_rack 
        WHERE LOWER(location_rack_name) = LOWER(:name)
    ";

    $statement = $connect->prepare($check_query);
    $statement->execute([':name' => $name]);
    $count = $statement->fetchColumn();

    if ($count > 0) {
        header('location:location_rack.php?action=add&error=exists');
        exit;
    }

    $date_now = get_date_time($connect);

    $query = "
        INSERT INTO lms_location_rack 
        (location_rack_name, location_rack_status, rack_created_on, rack_updated_on, position_x, position_y) 
        VALUES (:name, :status, :created_on, :updated_on, :position_x, :position_y)
    ";
    
    $statement = $connect->prepare($query);
    $statement->execute([
        ':name' => $name,
        ':status' => $status,
        ':created_on' => $date_now,
        ':updated_on' => $date_now,
        ':position_x' => $position_x,
        ':position_y' => $position_y
    ]);
    
    header('location:location_rack.php?msg=add');
    exit;
}

// EDIT rack (Form Submit)
if (isset($_POST['edit_rack'])) {
    $id = $_POST['location_rack_id'];
    $name = $_POST['location_rack_name'];
    $status = $_POST['location_rack_status'];
    $position_x = isset($_POST['position_x']) ? (int)$_POST['position_x'] : 0;
    $position_y = isset($_POST['position_y']) ? (int)$_POST['position_y'] : 0;

    $update_query = "
        UPDATE lms_location_rack 
        SET location_rack_name = :name, 
            location_rack_status = :status,
            rack_updated_on = :updated_on,
            position_x = :position_x,
            position_y = :position_y
        WHERE location_rack_id = :id
    ";

    $params = [
        ':name' => $name,
        ':status' => $status,
        ':updated_on' => get_date_time($connect),
        ':id' => $id,
        ':position_x' => $position_x,
        ':position_y' => $position_y
    ];

    $statement = $connect->prepare($update_query);
    $statement->execute($params);

    header('location:location_rack.php?msg=edit');
    exit;
}

// SELECT all racks for use in the map visualization
$query = "SELECT * FROM lms_location_rack ORDER BY location_rack_id ASC";
$statement = $connect->prepare($query);
$statement->execute();
$all_racks = $statement->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT * FROM lms_library_features WHERE feature_status = 'Enable' ORDER BY feature_id ASC";
$statement = $connect->prepare($query);
$statement->execute();
$library_features = $statement->fetchAll(PDO::FETCH_ASSOC);

include '../header.php';
?>

<div class="container-fluid px-4">
    <h1 class="my-4"><i class="fas fa-warehouse me-2"></i>Rack Location Management</h1>

    <?php if (isset($_GET["msg"])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php if (isset($_GET["msg"]) && $_GET["msg"] == 'disable'): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Rack Disabled',
                        text: 'The rack has been successfully disabled.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Done',
                        timer: 2000,
                        timerProgressBar: true
                    });
                <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'enable'): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Rack Enabled',
                        text: 'The rack has been successfully enabled.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Done',
                        timer: 2000,
                        timerProgressBar: true
                    });
                <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'add'): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Rack Added',
                        text: 'The rack was added successfully!',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Done',
                        timer: 2000,
                        timerProgressBar: true
                    });
                <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'edit'): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Rack Updated',
                        text: 'The rack was updated successfully!',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Done',
                        timer: 2000,
                        timerProgressBar: true
                    });
                <?php endif; ?>

                // Remove ?msg=... from the URL without reloading the page
                if (window.history.replaceState) {
                    const url = new URL(window.location);
                    url.searchParams.delete('msg');
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                }
            });
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
        <!-- Add Rack Form -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add Rack Location</h5>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
                    <div class="alert alert-danger alert-dismissible fade show" id="error-alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> Rack location already exists.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="post" class="row g-3">
                    <div class="col-8 mt-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Rack Position on Map</h6>
                            </div>
                            <div class="card-body">
                                <?php 
                                // Render the library map component in 'add' mode with all existing racks
                                $new_rack = ['location_rack_name' => 'New Rack'];
                                renderLibraryMap('add', $new_rack, $all_racks, 400, $library_features); 
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="col-md-11">
                            <label for="location_rack_name" class="form-label">Rack Location Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input type="text" id="location_rack_name" name="location_rack_name" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="col-md-9">
                            <label for="location_rack_status" class="form-label">Status</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                                <select name="location_rack_status" id="location_rack_status" class="form-select">
                                    <option value="Enable">Available</option>
                                    <option value="Disable">Full</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-4 d-flex justify-content-end">
                        <button type="submit" name="add_rack" class="btn btn-success me-2">
                            <i class="fas fa-save me-2"></i>Add Rack
                        </button>
                        <a href="location_rack.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])): ?>
        <?php
        $id = $_GET['code'];
        $query = "SELECT * FROM lms_location_rack WHERE location_rack_id = :id LIMIT 1";
        $statement = $connect->prepare($query);
        $statement->execute([':id' => $id]);
        $rack = $statement->fetch(PDO::FETCH_ASSOC);
        
        // Default positions if not set in the database
        $position_x = isset($rack['position_x']) ? $rack['position_x'] : 0;
        $position_y = isset($rack['position_y']) ? $rack['position_y'] : 0;
        ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i> Rack location already exists. Please choose another name.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Edit Rack Form -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Rack Location</h5>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="location_rack_id" value="<?= $rack['location_rack_id'] ?>">
                    <div class="col-8 mt-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Rack Position on Map</h6>
                            </div>
                            <div class="card-body">
                                <?php 
                                // Render the library map component in 'edit' mode with all racks
                                renderLibraryMap('edit', $rack, $all_racks, 400, $library_features); 
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="col-md-11">
                            <label for="location_rack_name" class="form-label">Rack Location Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input type="text" id="location_rack_name" name="location_rack_name" class="form-control" value="<?= htmlspecialchars($rack['location_rack_name']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-9">
                            <label for="location_rack_status" class="form-label">Status</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                                <select name="location_rack_status" id="location_rack_status" class="form-select">
                                    <option value="Enable" <?= $rack['location_rack_status'] == 'Enable' ? 'selected' : '' ?>>Available</option>
                                    <option value="Disable" <?= $rack['location_rack_status'] == 'Disable' ? 'selected' : '' ?>>Full</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-4 d-flex justify-content-end">
                        <button type="submit" name="edit_rack" class="btn btn-primary me-2">
                            <i class="fas fa-save me-2"></i>Update Rack
                        </button>
                        <a href="location_rack.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])): ?>
        <?php
            $id = $_GET['code'];
            $query = "SELECT * FROM lms_location_rack WHERE location_rack_id = :id LIMIT 1";
            $statement = $connect->prepare($query);
            $statement->execute([':id' => $id]);
            $rack = $statement->fetch(PDO::FETCH_ASSOC);
            
            // Default positions if not set in the database
            $position_x = isset($rack['position_x']) ? $rack['position_x'] : 0;
            $position_y = isset($rack['position_y']) ? $rack['position_y'] : 0;

            if ($rack): 
        ?>
        <!-- View Rack Details -->
        <div class="row">
            <div class="col-md-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Rack Location Map</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        // Render the library map component in 'view' mode with all racks
                        renderLibraryMap('view', $rack, $all_racks, 400, $library_features); 
                        ?>
                        
                        <div class="card mt-3">
                            <div class="card-body bg-light">
                                <h6 class="card-title"><i class="fas fa-route me-2"></i>How to Find This Rack</h6>
                                <p class="card-text mb-1">
                                    <small>
                                        <i class="fas fa-arrow-right me-1 text-success"></i>
                                        Enter the library through the main entrance.
                                    </small>
                                </p>
                                <?php
                                $position_x = isset($rack['position_x']) ? $rack['position_x'] : 0;
                                $position_y = isset($rack['position_y']) ? $rack['position_y'] : 0;
                                ?>
                                <p class="card-text mb-1">
                                    <small>
                                        <i class="fas fa-arrow-right me-1 text-success"></i>
                                        <?php if($position_x < 100): ?>
                                            Turn left and proceed to the west section.
                                        <?php elseif($position_x < 200): ?>
                                            Go straight to the center section.
                                        <?php else: ?>
                                            Turn right and proceed to the east section.
                                        <?php endif; ?>
                                    </small>
                                </p>
                                <p class="card-text mb-0">
                                    <small>
                                        <i class="fas fa-arrow-right me-1 text-success"></i>
                                        <?php if($position_y < 100): ?>
                                            The rack is located in the front area.
                                        <?php elseif($position_y < 200): ?>
                                            The rack is located in the middle area.
                                        <?php else: ?>
                                            The rack is located in the back area near the reading section.
                                        <?php endif; ?>
                                    </small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Rack Details</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <tbody>
                                <tr>
                                    <th width="30%"><i class="fas fa-hashtag me-2"></i>ID</th>
                                    <td><?= htmlspecialchars($rack['location_rack_id']) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-tag me-2"></i>Rack Name</th>
                                    <td><?= htmlspecialchars($rack['location_rack_name']) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-toggle-on me-2"></i>Status</th>
                                    <td>
                                        <?php if($rack['location_rack_status'] === 'Enable'): ?>
                                            <span class="badge bg-success">Available</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Full</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-calendar-plus me-2"></i>Created On</th>
                                    <td><?= date('M d, Y h:i A', strtotime($rack['rack_created_on'])) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-calendar-check me-2"></i>Updated On</th>
                                    <td><?= date('M d, Y h:i A', strtotime($rack['rack_updated_on'])) ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="mt-3 d-flex justify-content-end">
                            <a href="location_rack.php" class="btn btn-secondary me-2">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                            <a href="location_rack.php?action=edit&code=<?= $rack['location_rack_id'] ?>" class="btn btn-primary me-2">
                                <i class="fas fa-edit me-2"></i>Edit
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i> Location Rack not found.
        </div>
        <a href="location_rack.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
        <?php endif; ?>

    <?php else: ?>
        <!-- Rack List -->
        <div class="row mb-4">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-map me-2 text-primary"></i>Library Rack Map</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        // Render the library map component in 'list' mode with all racks
                        renderLibraryMap('list', null, $all_racks, 500, $library_features); 
                        ?>
                        
                        <!-- Legend card -->
                        <div class="card mt-3">
                            <div class="card-body">
                                <div class="row">
                                    <h6 class="mb-2">Map Legend</h6>
                                    <div class="col-md-4">                                        
                                        <div class="d-flex align-items-center mb-1">
                                            <div class="bg-success text-white d-flex justify-content-center align-items-center me-2" style="width: 30px; height: 30px; border-radius: 4px;">
                                                <i class="fas fa-archive"></i>
                                            </div>
                                            <span class="fs-6">Available Rack</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-1">
                                            <div class="bg-danger text-white d-flex justify-content-center align-items-center me-2" style="width: 30px; height: 30px; border-radius: 4px;">
                                                <i class="fas fa-archive"></i>
                                            </div>
                                            <span class="fs-6">Full Rack</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center mb-1">
                                            <div class="bg-secondary text-white d-flex justify-content-center align-items-center me-2" style="width: 30px; height: 30px; border-radius: 4px;">
                                                <i class="fas fa-door-open"></i>
                                            </div>
                                            <span class="fs-6">Library Entrance</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-1">
                                            <div class="bg-warning text-dark d-flex justify-content-center align-items-center me-2" style="width: 30px; height: 30px; border-radius: 4px;">
                                                <i class="fas fa-book-reader"></i>
                                            </div>
                                            <span class="fs-6">Reading Area</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">                                        
                                        <div class="d-flex align-items-center mb-1">
                                            <div class="bg-dark-subtle text-dark d-flex justify-content-center align-items-center me-2" style="width: 30px; height: 30px; border-radius: 4px;">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <span class="fs-6">Staff</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-1">
                                            <div class="bg-info-subtle text-dark d-flex justify-content-center align-items-center me-2" style="width: 30px; height: 30px; border-radius: 4px;">
                                                <i class="fas fa-desktop"></i>
                                            </div>
                                            <span class="fs-6">Computer Desks</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0"><i class="fas fa-table me-2 text-primary"></i>Rack Management</h5>
                            </div>
                            <div class="col text-end">
                                <a href="location_rack.php?action=add" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus me-1"></i> Add Rack
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <table id="dataTable" class="display nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Rack Name</th>
                                    <th>Status</th>
                                    <th>Updated On</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (count($all_racks) > 0): ?>
                                <?php foreach ($all_racks as $row): ?>
                                <tr>
                                    <td><?= $row['location_rack_id'] ?></td>
                                    <td>
                                        <span class="d-flex align-items-center">
                                            <i class="fas fa-archive me-2 text-secondary"></i>
                                            <?= htmlspecialchars($row['location_rack_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($row['location_rack_status'] === 'Enable'): ?>
                                            <span class="badge bg-success">Available</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Full</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('Y-m-d H:i', strtotime($row['rack_updated_on'])) ?></td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="location_rack.php?action=view&code=<?= $row['location_rack_id'] ?>" class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="location_rack.php?action=edit&code=<?= $row['location_rack_id'] ?>" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Edit Rack">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-<?= ($row['location_rack_status'] === 'Enable') ? 'danger' : 'success' ?> btn-sm toggle-status-btn" 
                                                    data-id="<?= $row['location_rack_id'] ?>" 
                                                    data-status="<?= $row['location_rack_status'] ?>" 
                                                    data-bs-toggle="tooltip" 
                                                    title="<?= ($row['location_rack_status'] === 'Enable') ? 'Mark as Full' : 'Mark as Available' ?>">
                                                <i class="fa <?= ($row['location_rack_status'] === 'Enable') ? 'fa-times' : 'fa-check' ?>"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center">No Data Found</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// DataTable initialization - ensure jQuery and DataTables are loaded
$(document).ready(function() {
    $('#dataTable').DataTable({
        responsive: true,
        columnDefs: [
            { responsivePriority: 1, targets: 1 },  // Rack Name (most important)
            { responsivePriority: 2, targets: 4 },  // Action buttons
            { responsivePriority: 3, targets: 0 },  // ID
            { responsivePriority: 4, targets: 2 },  // Status
            { 
                targets: [4], // Action column
                orderable: false,
                searchable: false,
                className: 'no-wrap' // Prevent button wrapping
            },
            { 
                targets: [3], // Updated On
                responsivePriority: 5, // Least important
                type: 'date' // Better sorting for dates
            }
        ],
        order: [[0, 'asc']], // Default sort by ID
        language: {
            emptyTable: "No racks available"
        },
        scrollY: '400px',
        scrollX: true, // Added for horizontal responsiveness
        scrollCollapse: true,
        paging: true,
        fixedHeader: true, // Optional: keeps headers visible while scrolling
        dom: '<"top"lf>rt<"bottom"ip>', // Custom control layout
        initComplete: function() {
            // Initialize Bootstrap tooltips
            $('[data-bs-toggle="tooltip"]').tooltip({
                trigger: 'hover'
            });
        }
    });
});

// Toggle status buttons
document.addEventListener('DOMContentLoaded', function() {
    // Check if SweetAlert is available
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert is not loaded');
        return;
    }
    
    // Get all toggle buttons
    const toggleButtons = document.querySelectorAll('.toggle-status-btn');
    
    // Attach click event to each button
    toggleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default button behavior
            
            const rackId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const newStatus = currentStatus === 'Enable' ? 'Disable' : 'Enable';
            const actionText = currentStatus === 'Enable' ? 'mark as full' : 'mark as available';
            
            // Show SweetAlert confirmation
            Swal.fire({
                title: `Are you sure?`,
                text: `Do you want to ${actionText} this rack?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: currentStatus === 'Enable' ? '#dc3545' : '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${actionText}!`
            }).then((result) => {
                if (result.isConfirmed) {
                    // Navigate to the action URL
                    window.location.href = `location_rack.php?action=delete&code=${rackId}&status=${newStatus}`;
                }
            });
        });
    });
});
</script>

<?php
include '../footer.php';
?>