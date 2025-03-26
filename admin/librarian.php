<?php
//librarian.php

include '../database_connection.php';
include '../function.php';

if (!is_admin_login()) {
	header('location:../admin_login.php');
}

$message = ''; // Feedback message

// DELETE (Disable/Enable)
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {
	$librarian_id = $_GET["code"];
	$status = $_GET["status"];

	$data = array(
		':librarian_status' => $status,
		':librarian_id'     => $librarian_id
	);

	$query = "
	UPDATE lms_librarian 
	SET librarian_status = :librarian_status 
	WHERE librarian_id = :librarian_id";


	$statement = $connect->prepare($query);
	$statement->execute($data);

	header('location:librarian.php?msg=' . strtolower($status) . '');
	exit;
}

// ADD Librarian (Form Submit)
if (isset($_POST['add_librarian'])) {
	$name = $_POST['librarian_name'];
	$email = $_POST['librarian_email'];
	$password = $_POST['librarian_password'];
	$contact = $_POST['librarian_contact_no'];
	$status = $_POST['librarian_status'];

	$query = "
		INSERT INTO lms_librarian 
		(librarian_name, librarian_email, librarian_password, librarian_contact_no, librarian_status, lib_created_on) 
		VALUES (:name, :email, :password, :contact, :status, :created_on)
	";

	$statement = $connect->prepare($query);
	$statement->execute([
		':name' => $name,
		':email' => $email,
		':password' => password_hash($password, PASSWORD_DEFAULT),
		':contact' => $contact,
		':status' => $status,
		':created_on' => get_date_time($connect)
	]);

	header('location:librarian.php?msg=add');
	exit;
}

// EDIT Librarian (Form Submit)
if (isset($_POST['edit_librarian'])) {
	$id = $_POST['librarian_id'];
	$name = $_POST['librarian_name'];
	$email = $_POST['librarian_email'];
	$password = $_POST['librarian_password'];
	$contact = $_POST['librarian_contact_no'];
	$status = $_POST['librarian_status'];

	// Optional password update
	$update_query = "
		UPDATE lms_librarian 
		SET librarian_name = :name, 
		    librarian_email = :email, 
		    librarian_contact_no = :contact, 
		    librarian_status = :status";

	if (!empty($password)) {
		$update_query .= ", librarian_password = :password";
	}

	$update_query .= " WHERE librarian_id = :id";

	$params = [
		':name' => $name,
		':email' => $email,
		':contact' => $contact,
		':status' => $status,
		':id' => $id
	];

	if (!empty($password)) {
		$params[':password'] = password_hash($password, PASSWORD_DEFAULT);
	}

	$statement = $connect->prepare($update_query);
	$statement->execute($params);

	header('location:librarian.php?msg=edit');
	exit;
}



// SELECT Librarians
$query = "SELECT * FROM lms_librarian ORDER BY librarian_id DESC";
$statement = $connect->prepare($query);
$statement->execute();
$librarians = $statement->fetchAll(PDO::FETCH_ASSOC);

include '../header.php';
?>

<main class="container py-4" style="min-height: 700px;">
	<h1 class="my-3">Librarian Management</h1>

	<?php if (isset($_GET["msg"])): ?>
		<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_GET["msg"]) && $_GET["msg"] == 'disable'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Librarian Disabled',
                text: 'The librarian has been successfully disabled.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Done'
            });
        <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'enable'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Librarian Enabled',
                text: 'The librarian has been successfully enabled.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Done'
            });
        <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'add'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Librarian Added',
                text: 'The librarian was added successfully!',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Done'
            });
        <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'edit'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Librarian Updated',
                text: 'The librarian was updated successfully!',
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
		<!-- Add Librarian Form -->
		<div class="card">
			<div class="card-header"><h5>Add Librarian</h5></div>
			<div class="card-body">
				<form method="post">
					<div class="mb-3">
						<label>Name</label>
						<input type="text" name="librarian_name" class="form-control" required>
					</div>
					<div class="mb-3">
						<label>Email</label>
						<input type="email" name="librarian_email" class="form-control" required>
					</div>
					<div class="mb-3">
						<label>Password</label>
						<input type="password" name="librarian_password" class="form-control" required>
					</div>
					<div class="mb-3">
						<label>Contact</label>
						<input type="text" name="librarian_contact_no" class="form-control" required>
					</div>
					<div class="mb-3">
						<label>Status</label>
						<select name="librarian_status" class="form-select">
							<option value="Enable">Active</option>
							<option value="Disable">Not Active</option>
						</select>
					</div>
					<input type="submit" name="add_librarian" class="btn btn-success" value="Add Librarian">
					<a href="librarian.php" class="btn btn-secondary">Cancel</a>
				</form>
			</div>
		</div>

	<?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])): ?>

		<?php
		$id = $_GET['code'];
		$query = "SELECT * FROM lms_librarian WHERE librarian_id = :id LIMIT 1";
		$statement = $connect->prepare($query);
		$statement->execute([':id' => $id]);
		$librarians = $statement->fetch(PDO::FETCH_ASSOC);
		?>

		<!-- Edit Librarian Form -->
		<div class="card">
			<div class="card-header"><h5>Edit Librarian</h5></div>
			<div class="card-body">
				<form method="post">
					<input type="hidden" name="librarian_id" value="<?= $librarians['librarian_id'] ?>">
					<div class="mb-3">
						<label>Name</label>
						<input type="text" name="librarian_name" class="form-control" value="<?= $librarians['librarian_name'] ?>" required>
					</div>
					<div class="mb-3">
						<label>Email</label>
						<input type="email" name="librarian_email" class="form-control" value="<?= $librarians['librarian_email'] ?>" required>
					</div>
					<div class="mb-3">
						<label>New Password (Leave blank to keep current)</label>
						<input type="password" name="librarian_password" class="form-control">
					</div>
					<div class="mb-3">
						<label>Contact</label>
						<input type="text" name="librarian_contact_no" class="form-control" value="<?= $librarians['librarian_contact_no'] ?>" required>
					</div>
					<div class="mb-3">
						<label>Status</label>
						<select name="librarian_status" class="form-select">
							<option value="Enable" <?= $librarians['librarian_status'] == 'Enable' ? 'selected' : '' ?>>Enable</option>
							<option value="Disable" <?= $librarians['librarian_status'] == 'Disable' ? 'selected' : '' ?>>Disable</option>
						</select>
					</div>
					<input type="submit" name="edit_librarian" class="btn btn-primary" value="Update Librarian">
					<a href="librarian.php" class="btn btn-secondary">Cancel</a>
				</form>
			</div>
		</div>
        

        <?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])): ?>
            <?php
		$id = $_GET['code'];
		$query = "SELECT * FROM lms_librarian WHERE librarian_id = :id LIMIT 1";
		$statement = $connect->prepare($query);
		$statement->execute([':id' => $id]);
		$librarian = $statement->fetch(PDO::FETCH_ASSOC);

		if ($librarian): 
	?>

		<!-- View Librarian Details -->
		<div class="card">
			<div class="card-header"><h5>View Librarian</h5></div>
			<div class="card-body">
				<p><strong>ID:</strong> <?= htmlspecialchars($librarian['librarian_id']) ?></p>
				<p><strong>Name:</strong> <?= htmlspecialchars($librarian['librarian_name']) ?></p>
				<p><strong>Email:</strong> <?= htmlspecialchars($librarian['librarian_email']) ?></p>
				<p><strong>Contact:</strong> <?= htmlspecialchars($librarian['librarian_contact_no']) ?></p>
				<p><strong>Status:</strong> <?= htmlspecialchars($librarian['librarian_status']) ?></p>
				<p><strong>Created On:</strong> <?= date('M d, Y h:i A', strtotime($librarian['lib_created_on'])) ?></p>
				<p><strong>Updated On:</strong> <?= date('M d, Y h:i A', strtotime($librarian['lib_updated_on'])) ?></p>
				<a href="librarian.php" class="btn btn-secondary">Back</a>
			</div>
		</div>

	<?php 
		else: 
	?>
		<p class="alert alert-danger">Librarian not found.</p>
		<a href="librarian.php" class="btn btn-secondary">Back</a>
	<?php 
		endif;
	// END VIEW LIBRARIAN

	else: ?>

		<!-- Librarian List -->
		<div class="card mb-4">
			<div class="card-header">
				<div class="row">
					<div class="col col-md-6">
						<i class="fas fa-table me-1"></i> Librarian Management
					</div>
					<div class="col col-md-6">
						<a href="librarian.php?action=add" class="btn btn-success btn-sm float-end">Add Librarian</a>
					</div>
				</div>
			</div>

			<div class="card-body">
				<table id="dataTable" class="display nowrap" style="width:100%">
					<thead>
						<tr>
							<th>ID</th>
							<th>Name</th>
							<th>Email</th>
							<th>Contact No.</th>
							<th>Status</th>
                            <th>Created On</th>
                            <th>Updated On</th> 
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
<?php if (count($librarians) > 0): ?>
    <?php foreach ($librarians as $row): ?>
        <tr>
            <td><?= $row['librarian_id'] ?></td>
            <td><?= $row['librarian_name'] ?></td>
            <td><?= $row['librarian_email'] ?></td>
            <td><?= $row['librarian_contact_no'] ?></td>
            <td>
                <?= ($row['librarian_status'] === 'Enable') 
                    ? '<span class="badge bg-success">Active</span>' 
                    : '<span class="badge bg-danger">Disabled</span>' ?>
            </td>
            <td><?= date('Y-m-d H:i:s', strtotime($row['lib_created_on'])) ?></td>
			<td><?= date('Y-m-d H:i:s', strtotime($row['lib_updated_on'])) ?></td>
 
            <td class="text-center">
                <a href="librarian.php?action=view&code=<?= $row['librarian_id'] ?>" class="btn btn-info btn-sm mb-1">
                <i class="fa fa-eye"></i>
                </a>
                <a href="librarian.php?action=edit&code=<?= $row['librarian_id'] ?>" class="btn btn-primary btn-sm mb-1">
                    <i class="fa fa-edit"></i>
                </a>
                <button type="button" name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data('<?= $row['librarian_id'] ?>')">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="8" class="text-center">No Data Found</td></tr>
<?php endif; ?>

</tbody>

				</table>
			</div>
		</div>

	<?php endif; ?>

</main>

<script>
function delete_data(code) {
	if (confirm("Are you sure you want to disable this Librarian?")) {
		window.location.href = "librarian.php?action=delete&code=" + code + "&status=Disable";
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
    
    // Scroll settings combined here
    scrollY: '400px',     // Vertical scrollbar height
    scrollX: true,        // Enable horizontal scrolling
    scrollCollapse: true, // Collapse the table height when fewer records
    paging: true          // Enable pagination
  });
});


//For deleting alert
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const librarianId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const action = (currentStatus === 'Enable') ? 'disable' : 'enable';

            Swal.fire({
                title: `Are you sure you want to ${action} this librarian?`,
                text: "This action can be reverted later.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: `Yes, ${action} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `librarian.php?action=delete&status=${action === 'disable' ? 'Disable' : 'Enable'}&code=${librarianId}`;
                }
            });
        });
    });
});

</script>



