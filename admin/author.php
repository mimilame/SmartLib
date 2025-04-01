<?php
//author.php

include '../database_connection.php';
include '../function.php';


$message = ''; // Feedback message

// DELETE (Disable/Enable)
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {
	$author_id = $_GET["code"];
	$status = $_GET["status"];

	$data = array(
		':author_status' => $status,
		':author_id'     => $author_id
	);

	$query = "
	UPDATE lms_author 
	SET author_status = :author_status 
	WHERE author_id = :author_id";


	$statement = $connect->prepare($query);
	$statement->execute($data);

	header('location:author.php?msg=' . strtolower($status) . '');
	exit;
}

// ADD author (Form Submit)
if (isset($_POST['add_author'])) {
    $name = trim($_POST['author_name']); // Clean the input
    $status = $_POST['author_status'];

    // Check for duplicate (case-insensitive comparison)
    $check_query = "
        SELECT COUNT(*) 
        FROM lms_author 
        WHERE LOWER(author_name) = LOWER(:name)
    ";

    $statement = $connect->prepare($check_query);
    $statement->execute([':name' => $name]);
    $count = $statement->fetchColumn();

    if ($count > 0) {
        // author already exists
        header('location:author.php?action=add&error=exists');
        exit;
    }

    // If not existing, insert new author
    $date_now = get_date_time($connect); // Get the current date once

    $query = "
        INSERT INTO lms_author 
        (author_name, author_status, author_created_on, author_updated_on) 
        VALUES (:name, :status, :created_on, :updated_on)
    ";
    
    $statement = $connect->prepare($query);
    $statement->execute([
        ':name' => $name,
        ':status' => $status,
        ':created_on' => $date_now,   // Same date for both created and updated
        ':updated_on' => $date_now
    ]);
    

    header('location:author.php?msg=add');
    exit;
}


// EDIT author (Form Submit)
if (isset($_POST['edit_author'])) {
	$id = $_POST['author_id'];
	$name = $_POST['author_name'];
	$status = $_POST['author_status'];


    $update_query = "
    UPDATE lms_author 
    SET author_name = :name, 
        author_status = :status,
        author_updated_on = :updated_on
    WHERE author_id = :id
";


    $params = [
    ':name' => $name,
    ':status' => $status,
    ':updated_on' => get_date_time($connect),
    ':id' => $id
	];

	$statement = $connect->prepare($update_query);
    $statement->execute($params);

	header('location:author.php?msg=edit');
	exit;
}



// SELECT author
$query = "SELECT * FROM lms_author ORDER BY author_id DESC";
$statement = $connect->prepare($query);
$statement->execute();
$author = $statement->fetchAll(PDO::FETCH_ASSOC);

include '../header.php';
?>

<main class="container py-4" style="min-height: 700px;">
	<h1 class="my-3">Author Management</h1>

	<?php if (isset($_GET["msg"])): ?>
		<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_GET["msg"]) && $_GET["msg"] == 'disable'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Author Disabled',
                text: 'The author has been successfully disabled.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Done'
            });
        <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'enable'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Author Enabled',
                text: 'The author has been successfully enabled.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Done'
            });
        <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'add'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Author Added',
                text: 'The author was added successfully!',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Done'
            });
        <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'edit'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Author Updated',
                text: 'The author was updated successfully!',
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
		<!-- Add author Form -->
<div class="card">
	<div class="card-header"><h5>Add Author</h5></div>
	<div class="card-body">

		<?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
		<div class="alert alert-danger" id="error-alert">
            Author already exists.
		</div>
		<?php endif; ?>

		<form method="post">
			<div class="mb-3">
				<label>Author Name</label>
				<input type="text" name="author_name" class="form-control" required>
			</div>
			<div class="mb-3">
				<label>Status</label>
				<select name="author_status" class="form-select">
					<option value="Enable">Active</option>
					<option value="Disable">Inactive</option>
				</select>
			</div>
			<input type="submit" name="add_author" class="btn btn-success" value="Add Author">
			<a href="author.php" class="btn btn-secondary">Cancel</a>
		</form>
	</div>
</div>

<?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])): ?>

<?php
$id = $_GET['code'];
$query = "SELECT * FROM lms_author WHERE author_id = :id LIMIT 1";
$statement = $connect->prepare($query);
$statement->execute([':id' => $id]);
$author = $statement->fetch(PDO::FETCH_ASSOC);
?>

<?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
<div class="alert alert-danger">
    Author already exists.
</div>
<?php endif; ?>



		<!-- Edit author Form -->
		<div class="card">
			<div class="card-header"><h5>Edit Author</h5></div>
			<div class="card-body">
				<form method="post">
					<input type="hidden" name="author_id" value="<?= $author['author_id'] ?>">
					<div class="mb-3">
						<label>Author Name</label>
						<input type="text" name="author_name" class="form-control" value="<?= $author['author_name'] ?>" required>
					</div>
					<div class="mb-3">
						<label>Status</label>
						<select name="author_status" class="form-select">
							<option value="Enable" <?= $author['author_status'] == 'Enable' ? 'selected' : '' ?>>Active</option>
							<option value="Disable" <?= $author['author_status'] == 'Disable' ? 'selected' : '' ?>>Inactive</option>
						</select>
					</div>
					<input type="submit" name="edit_author" class="btn btn-primary" value="Update Author">
					<a href="author.php" class="btn btn-secondary">Cancel</a>
				</form>
			</div>
		</div>
        

        <?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])): ?>
            <?php
		$id = $_GET['code'];
		$query = "SELECT * FROM lms_author WHERE author_id = :id LIMIT 1";
		$statement = $connect->prepare($query);
		$statement->execute([':id' => $id]);
		$author = $statement->fetch(PDO::FETCH_ASSOC);

		if ($author): 
	?>

		<!-- View author Details -->
		<div class="card">
			<div class="card-header"><h5>View Author</h5></div>
			<div class="card-body">
				<p><strong>ID:</strong> <?= htmlspecialchars($author['author_id']) ?></p>
				<p><strong>Author Name:</strong> <?= htmlspecialchars($author['author_name']) ?></p>
				<p><strong>Status:</strong> <?= htmlspecialchars($author['author_status']) ?></p>
				<p><strong>Created On:</strong> <?= date('M d, Y h:i A', strtotime($author['author_created_on'])) ?></p>
				<p><strong>Updated On:</strong> <?= date('M d, Y h:i A', strtotime($author['author_updated_on'])) ?></p>
				<a href="author.php" class="btn btn-secondary">Back</a>
			</div>
		</div>

	<?php 
		else: 
	?>
		<p class="alert alert-danger">Author not found.</p>
		<a href="author.php" class="btn btn-secondary">Back</a>
	<?php 
		endif;
	// END VIEW author

	else: ?>

		<!-- author List -->
		<div class="card mb-4">
			<div class="card-header">
				<div class="row">
					<div class="col col-md-6">
						<i class="fas fa-table me-1"></i> Author Management
					</div>
					<div class="col col-md-6">
						<a href="author.php?action=add" class="btn btn-success btn-sm float-end">Add Author</a>
					</div>
				</div>
			</div>

			<div class="card-body">
				<table id="dataTable" class="display nowrap" style="width:100%">
					<thead>
						<tr>
							<th>ID</th>
							<th>Author Name</th>
							<th>Status</th>
                            <th>Created On</th>
                            <th>Updated On</th> 
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
<?php if (count($author) > 0): ?>
    <?php foreach ($author as $row): ?>
        <tr>
            <td><?= $row['author_id'] ?></td>
            <td><?= $row['author_name'] ?></td>

            <td>
                <?= ($row['author_status'] === 'Enable') 
                    ? '<span class="badge bg-success">Active</span>' 
                    : '<span class="badge bg-danger">Inactive</span>' ?>
            </td>
            <td><?= date('Y-m-d H:i:s', strtotime($row['author_created_on'])) ?></td>
			<td><?= date('Y-m-d H:i:s', strtotime($row['author_updated_on'])) ?></td>
 
            <td class="text-center">
                <a href="author.php?action=view&code=<?= $row['author_id'] ?>" class="btn btn-info btn-sm mb-1">
                <i class="fa fa-eye"></i>
                </a>
                <a href="author.php?action=edit&code=<?= $row['author_id'] ?>" class="btn btn-primary btn-sm mb-1">
                    <i class="fa fa-edit"></i>
                </a>
                <button type="button" name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data('<?= $row['author_id'] ?>')">
                    <i class="fa fa-trash"></i>
                </button>
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

	<?php endif; ?>

</main>

<script>
// Function to disable a user via confirm dialog (basic)
function delete_data(userId) {
	if (confirm("Are you sure you want to disable this Author?")) {
		window.location.href = "author.php?action=delete&code=" + userId + "&status=Disable";
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

//For deleting alert
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const authorId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const action = (currentStatus === 'Enable') ? 'disable' : 'enable';

            Swal.fire({
                title: `Are you sure you want to ${action} this author?`,
                text: "This action can be reverted later.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: `Yes, ${action} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `author.php?action=delete&status=${action === 'disable' ? 'Disable' : 'Enable'}&code=${authorId}`;
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

