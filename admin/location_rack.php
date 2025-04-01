<?php
// location_rack.php

include '../database_connection.php';
include '../function.php';


$message = ''; // Feedback message


// DELETE (Disable/Enable)
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {
	$category_id = $_GET["code"];
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
    $name = trim($_POST['location_rack_name']); // Clean the input
    $status = $_POST['location_rack_status'];

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
        // Rack already exists
        header('location:location_rack.php?action=add&error=exists');
        exit;
    }

    // If not existing, insert new rack
    $date_now = get_date_time($connect); // Get the current date once

    $query = "
        INSERT INTO lms_location_rack 
        (location_rack_name, location_rack_status, rack_created_on, rack_updated_on) 
        VALUES (:name, :status, :created_on, :updated_on)
    ";
    
    $statement = $connect->prepare($query);
    $statement->execute([
        ':name' => $name,
        ':status' => $status,
        ':created_on' => $date_now,   // Same date for both created and updated
        ':updated_on' => $date_now
    ]);
    
    header('location:location_rack.php?msg=add');
    exit;
}

// EDIT rack (Form Submit)
if (isset($_POST['edit_rack'])) {
    $id = $_POST['location_rack_id'];
    $name = $_POST['location_rack_name'];
    $status = $_POST['location_rack_status'];

    $update_query = "
        UPDATE lms_location_rack 
        SET location_rack_name = :name, 
            location_rack_status = :status,
            rack_updated_on = :updated_on
        WHERE location_rack_id = :id
    ";

    $params = [
        ':name' => $name,
        ':status' => $status,
        ':updated_on' => get_date_time($connect),
        ':id' => $id
    ];

    $statement = $connect->prepare($update_query);
    $statement->execute($params);

    header('location:location_rack.php?msg=edit');
    exit;
}


// SELECT rack
$query = "SELECT * FROM lms_location_rack ORDER BY location_rack_id ASC";
$statement = $connect->prepare($query);
$statement->execute();
$rack = $statement->fetchAll(PDO::FETCH_ASSOC);

include '../header.php';
?>

<main class="container py-4" style="min-height: 700px;">
    <h1 class="my-3">Rack Location Management</h1>

    <?php if (isset($_GET["msg"])): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_GET["msg"]) && $_GET["msg"] == 'disable'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Rack Disabled',
                    text: 'The rack has been successfully disabled.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'enable'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Rack Enabled',
                    text: 'The rack has been successfully enabled.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'add'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Rack Added',
                    text: 'The rack was added successfully!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'edit'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Rack Updated',
                    text: 'The rack was updated successfully!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
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
    <div class="card">
        <div class="card-header"><h5>Add Rack Location</h5></div>
        <div class="card-body">

            <?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
                <div class="alert alert-danger" id="error-alert">
                    Rack location already exists.
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label>Rack Location Name</label>
                    <input type="text" name="location_rack_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Status</label>
                    <select name="location_rack_status" class="form-select">
                        <option value="Enable">Available</option>
                        <option value="Disable">Full</option>
                    </select>
                </div>
                <input type="submit" name="add_rack" class="btn btn-success" value="Add Rack">
                <a href="location_rack.php" class="btn btn-secondary">Cancel</a>
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
?>

<?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
<div class="alert alert-danger">
    Rack location already exists. Please choose another name.
</div>
<?php endif; ?>

<!-- Edit Rack Form -->
<div class="card">
    <div class="card-header"><h5>Edit Rack Location</h5></div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="location_rack_id" value="<?= $rack['location_rack_id'] ?>">
            <div class="mb-3">
                <label>Rack Location Name</label>
                <input type="text" name="location_rack_name" class="form-control" value="<?= $rack['location_rack_name'] ?>" required>
            </div>
            <div class="mb-3">
                <label>Status</label>
                <select name="location_rack_status" class="form-select">
                    <option value="Enable" <?= $rack['location_rack_status'] == 'Enable' ? 'selected' : '' ?>>Available</option>
                    <option value="Disable" <?= $rack['location_rack_status'] == 'Disable' ? 'selected' : '' ?>>Full</option>
                </select>
            </div>
            <input type="submit" name="edit_rack" class="btn btn-primary" value="Update Rack">
            <a href="location_rack.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])): ?>
            <?php
		$id = $_GET['code'];
		$query = "SELECT * FROM lms_location_rack WHERE location_rack_id = :id LIMIT 1";
		$statement = $connect->prepare($query);
		$statement->execute([':id' => $id]);
		$category = $statement->fetch(PDO::FETCH_ASSOC);

		if ($rack): 
	?>


		<!-- View Rack Details -->
		<div class="card">
			<div class="card-header"><h5>View Rack</h5></div>
			<div class="card-body">
				<p><strong>ID:</strong> <?= htmlspecialchars($rack['location_rack_id']) ?></p>
				<p><strong>Location Rack Name:</strong> <?= htmlspecialchars($rack['location_rack_name']) ?></p>
				<p><strong>Status:</strong> <?= htmlspecialchars($rack['location_rack_status']) ?></p>
				<p><strong>Created On:</strong> <?= date('M d, Y h:i A', strtotime($rack['rack_created_on'])) ?></p>
				<p><strong>Updated On:</strong> <?= date('M d, Y h:i A', strtotime($rack['rack_updated_on'])) ?></p>
				<a href="location_rack.php" class="btn btn-secondary">Back</a>
			</div>
		</div>

	<?php 
		else: 
	?>
		<p class="alert alert-danger">Location Rack not found.</p>
		<a href="location_rack.php" class="btn btn-secondary">Back</a>
	<?php 
		endif;
	// END VIEW rack

	else: ?>

    <!-- Rack List -->
<div class="card mb-4">
    <div class="card-header">
        <div class="row">
            <div class="col col-md-6">
                <i class="fas fa-table me-1"></i> Rack Management
            </div>
            <div class="col col-md-6">
                <a href="location_rack.php?action=add" class="btn btn-success btn-sm float-end">Add Rack</a>
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
                    <th>Created On</th>
                    <th>Updated On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
<?php if (count($rack) > 0): ?>
    <?php foreach ($rack as $row): ?>
        <tr>
            <td><?= $row['location_rack_id'] ?></td>
            <td><?= $row['location_rack_name'] ?></td>
            <td>
                <?= ($row['location_rack_status'] === 'Enable') 
                    ? '<span class="badge bg-success">Available</span>' 
                    : '<span class="badge bg-danger">Full</span>' ?>
            </td>
            <td><?= date('Y-m-d H:i:s', strtotime($row['rack_created_on'])) ?></td>
            <td><?= date('Y-m-d H:i:s', strtotime($row['rack_updated_on'])) ?></td>
            <td class="text-center">
                <a href="location_rack.php?action=view&code=<?= $row['location_rack_id'] ?>" class="btn btn-info btn-sm mb-1">
                    <i class="fa fa-eye"></i>
                </a>
                <a href="location_rack.php?action=edit&code=<?= $row['location_rack_id'] ?>" class="btn btn-primary btn-sm mb-1">
                    <i class="fa fa-edit"></i>
                </a>
                <button type="button" name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data('<?= $row['location_rack_id'] ?>')">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="6" class="text-center">No Data Found</td></tr>
<?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
</main>

<script>
// Function to disable a rack via confirm dialog (basic)
function delete_data(rackId) {
    if (confirm("Are you sure you want to disable this Rack?")) {
        window.location.href = "rack.php?action=delete&code=" + rackId + "&status=Disable";
    }
}

$(document).ready(function() {    
  $('#dataTable').DataTable({
    responsive: true,
    columnDefs: [
      { responsivePriority: 1, targets: [0, 1, 5] },
      { responsivePriority: 2, targets: [2, 3] }
    ],
    order: [[0, 'asc']],
    autoWidth: false,
    language: {
      emptyTable: "No data available"
    },
    
    // Scroll and pagination settings
    scrollY: '400px',       // Vertical scroll
    scrollX: true,          // Horizontal scroll
    scrollCollapse: true,   // Collapse height when less data
    paging: true            // Enable pagination
  });
});

// For deleting alert
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const rackId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const action = (currentStatus === 'Enable') ? 'disable' : 'enable';

            Swal.fire({
                title: `Are you sure you want to ${action} this rack?`,
                text: "This action can be reverted later.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: `Yes, ${action} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `rack.php?action=delete&status=${action === 'disable' ? 'Disable' : 'Enable'}&code=${rackId}`;
                }
            });
        });
    });
});
</script>

<script>
    // Remove query parameters from the URL (optional after showing alert)
    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('error');
        window.history.replaceState({}, document.title, url.pathname + url.search);
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const alertBox = document.getElementById('error-alert');

        if (alertBox) {
            // Display the alert for 3 seconds (3000ms)
            setTimeout(function() {
                // Optional fade-out effect
                alertBox.style.transition = 'opacity 0.5s ease';
                alertBox.style.opacity = '0';

                // Remove the alert after fade (0.5s delay)
                setTimeout(function() {
                    alertBox.remove();
                }, 500);

                // Remove 'error' param from the URL after the alert disappears
                if (window.history.replaceState) {
                    const url = new URL(window.location);
                    url.searchParams.delete('error');
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                }
            }, 3000); // You can change this to 5000 for 5 seconds, etc.
        }
    });
</script>
<?php 

include '../footer.php';

?>