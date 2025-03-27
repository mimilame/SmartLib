<?php
//user.php

include '../database_connection.php';
include '../function.php';



$message = ''; // Feedback message

// DELETE (Disable/Enable)
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {
	$user_id = $_GET["code"];
	$status = $_GET["status"];

	$data = array(
		':user_status' => $status,
		':user_id'     => $user_id
	);

	$query = "
	UPDATE lms_user
	SET user_status = :user_status 
	WHERE user_id = :user_id";


	$statement = $connect->prepare($query);
	$statement->execute($data);

	header('location:user.php?msg=' . strtolower($status) . '');
	exit;
}

// ADD user (Form Submit)
if (isset($_POST['add_user'])) {
	$name = $_POST['user_name'];
	$email = $_POST['user_email'];
	$password = $_POST['user_password'];
	$unique_id = $_POST['user_unique_id'];
	$contact = $_POST['user_contact_no'];
	$type = $_POST['user_type'];
	$status = $_POST['user_status'];


	$date_now = get_date_time($connect);

	$query = "
		INSERT INTO lms_user 
		(user_name, user_email, user_password, user_unique_id, user_contact_no, user_type user_status, user_created_on, user_updated_on) 
		VALUES (:name, :email, :password, :unique_id :contact, :type :status, :created_on, :updated_on)
	";

	$statement = $connect->prepare($query);
	$statement->execute([
		':name' => $name,
		':email' => $email,
		':password' => password_hash($password, PASSWORD_DEFAULT),
		':unique_id' => $unique_id,
		':contact' => $contact,
		':type' => $type,
		':status' => $status,
		':created_on' => $date_now,
        ':updated_on' => $date_now
	]);

	header('location:user.php?msg=add');
	exit;
}

// EDIT user (Form Submit)
if (isset($_POST['edit_user'])) {
	$id = $_POST['user_id'];
	$name = $_POST['user_name'];
	$email = $_POST['user_email'];
	$password = $_POST['user_password'];
	$unique_id = $_POST['user_unique_id'];
	$contact = $_POST['user_contact_no'];
	$type = $_POST['user_type'];
	$status = $_POST['user_status'];

	// Optional password update
	$update_query = "
		UPDATE lms_user 
		SET user_name = :name, 
		    user_email = :email, 
			user_unique_id = :unique_id,
		    user_contact_no = :contact,
			user_type = :type, 
		    user_status = :status";

	if (!empty($password)) {
		$update_query .= ", user_password = :password";
	}

	$update_query .= " WHERE user_id = :id";

	$params = [
		':name' => $name,
		':email' => $email,
		':unique_id' => $unique_id,
		':contact' => $contact,
		':type' => $type,
		':status' => $status,
		':id' => $id
	];

	if (!empty($password)) {
		$params[':password'] = password_hash($password, PASSWORD_DEFAULT);
	}

	$statement = $connect->prepare($update_query);
	$statement->execute($params);

	header('location:user.php?msg=edit');
	exit;
}



// SELECT User
$query = "SELECT * FROM lms_user ORDER BY user_id DESC";
$statement = $connect->prepare($query);
$statement->execute();
$user = $statement->fetchAll(PDO::FETCH_ASSOC);

include '../header.php';
?>

<main class="container py-4" style="min-height: 700px;">
	<h1 class="my-3">User Management</h1>

	<?php if (isset($_GET["msg"])): ?>
		<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_GET["msg"]) && $_GET["msg"] == 'disable'): ?>
            Swal.fire({
                icon: 'success',
                title: 'User Disabled',
                text: 'The user has been successfully disabled.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Done'
            });
        <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'enable'): ?>
            Swal.fire({
                icon: 'success',
                title: 'User Enabled',
                text: 'The user has been successfully enabled.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Done'
            });
        <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'add'): ?>
            Swal.fire({
                icon: 'success',
                title: 'User Added',
                text: 'The user was added successfully!',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Done'
            });
        <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'edit'): ?>
            Swal.fire({
                icon: 'success',
                title: 'User Updated',
                text: 'The user was updated successfully!',
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
		<!-- Add User Form -->
		<div class="card">
			<div class="card-header"><h5>Add User</h5></div>
			<div class="card-body">
				<form method="post">
					<div class="mb-3">
						<label>Name</label>
						<input type="text" name="user_name" class="form-control" required>
					</div>
					<div class="mb-3">
						<label>Email</label>
						<input type="email" name="user_email" class="form-control" required>
					</div>
					<div class="mb-3">
						<label>Password</label>
						<input type="password" name="user_password" class="form-control" required>
					</div>
					<div class="mb-3">
						<label>User Unique ID</label>
						<input type="text" name="user_unique_id" class="form-control" required>
					</div>
					<div class="mb-3">
						<label>Contact</label>
						<input type="text" name="user_contact_no" class="form-control" required>
					</div>
					<div class="mb-3">
						<label>User Type</label>
						<select name="user_type" class="form-select">
							<option value="Student">Student</option>
							<option value="Faculty">Faculty</option>
						</select>
					</div>
					
					<div class="mb-3">
						<label>Status</label>
						<select name="user_status" class="form-select">
							<option value="Enable">Active</option>
							<option value="Disable">Inactive</option>
						</select>
					</div>
					<div class="input-box mb-3">
						<label>User Role:</label>
						<select name="user_role" required>
							<option value="S">Student</option>
							<option value="F">Faculty</option>
						</select>
					</div>
					<input type="submit" name="add_user" class="btn btn-success" value="Add User">
					<a href="user.php" class="btn btn-secondary">Cancel</a>
				</form>
			</div>
		</div>

	<?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])): ?>

		<?php
		$id = $_GET['code'];
		$query = "SELECT * FROM lms_user WHERE user_id = :id LIMIT 1";
		$statement = $connect->prepare($query);
		$statement->execute([':id' => $id]);
		$user = $statement->fetch(PDO::FETCH_ASSOC);
		?>

		<!-- Edit User Form -->
		<div class="card">
			<div class="card-header"><h5>Edit User</h5></div>
			<div class="card-body">
				<form method="post">
					<input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
					<div class="mb-3">
						<label>Name</label>
						<input type="text" name="user_name" class="form-control" value="<?= $user['user_name'] ?>" required>
					</div>
					<div class="mb-3">
						<label>Email</label>
						<input type="email" name="user_email" class="form-control" value="<?= $user['user_email'] ?>" required>
					</div>
					<div class="mb-3">
						<label>New Password (Leave blank to keep current)</label>
						<input type="password" name="user_password" class="form-control">
					</div>
					<div class="mb-3">
						<label> User Unique ID</label>
						<input type="text" name="user_unique_id" class="form-control" required>
					</div>
					<div class="mb-3">
						<label>Contact</label>
						<input type="text" name="user_contact_no" class="form-control" value="<?= $user['user_contact_no'] ?>" required>
					</div>
					<div class="mb-3">
						<label>User Type</label>
						<select name="user_type" class="form-select">
							<option value="none" <?= $user['user_type'] == 'none' ? 'selected' : '' ?>>----------NONE----------</option>
							<option value="Student" <?= $user['user_type'] == 'Student' ? 'selected' : '' ?>>Student</option>
							<option value="Faculty" <?= $user['user_type'] == 'Faculty' ? 'selected' : '' ?>>Faculty</option>
						</select>
					</div>
					<div class="mb-3">
						<label>Status</label>
						<select name="user_status" class="form-select">
							<option value="Enable" <?= $user['user_status'] == 'Enable' ? 'selected' : '' ?>>Active</option>
							<option value="Disable" <?= $user['user_status'] == 'Disable' ? 'selected' : '' ?>>Inactive</option>
						</select>
					</div>
					<input type="submit" name="edit_user" class="btn btn-primary" value="Update User">
					<a href="user.php" class="btn btn-secondary">Cancel</a>
				</form>
			</div>
		</div>
        

        <?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])): ?>
            <?php
		$id = $_GET['code'];
		$query = "SELECT * FROM lms_user WHERE user_id = :id LIMIT 1";
		$statement = $connect->prepare($query);
		$statement->execute([':id' => $id]);
		$user = $statement->fetch(PDO::FETCH_ASSOC);

		if ($user): 
	?>

		<!-- View User Details -->
		<div class="card">
			<div class="card-header"><h5>View User</h5></div>
			<div class="card-body">
				<p><strong>ID:</strong> <?= htmlspecialchars($user['user_id']) ?></p>
				<p><strong>Name:</strong> <?= htmlspecialchars($user['user_name']) ?></p>
				<p><strong>Email:</strong> <?= htmlspecialchars($user['user_email']) ?></p>
				<p><strong>User Unique ID:</strong> <?= htmlspecialchars($user['user_unique_id']) ?></p>
				<p><strong>Contact:</strong> <?= htmlspecialchars($user['user_contact_no']) ?></p>
				<p><strong>User Type:</strong> <?= htmlspecialchars($user['user_type']) ?></p>
				<p><strong>Status:</strong> <?= htmlspecialchars($user['user_status']) ?></p>
				<p><strong>Created On:</strong> <?= date('M d, Y h:i A', strtotime($user['user_created_on'])) ?></p>
				<p><strong>Updated On:</strong> <?= date('M d, Y h:i A', strtotime($user['user_updated_on'])) ?></p>
				<a href="user.php" class="btn btn-secondary">Back</a>
			</div>
		</div>

	<?php 
		else: 
	?>
		<p class="alert alert-danger">User not found.</p>
		<a href="user.php" class="btn btn-secondary">Back</a>
	<?php 
		endif;
	// END VIEW User

	else: ?>

		<!-- User List -->
		<div class="card mb-4">
			<div class="card-header">
				<div class="row">
					<div class="col col-md-6">
						<i class="fas fa-table me-1"></i> User Management
					</div>
					<div class="col col-md-6">
						<a href="user.php?action=add" class="btn btn-success btn-sm float-end">Add User</a>
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
							<th>User Unique ID</th>
							<th>Contact No.</th>
							<th>User Type</th>
							<th>Status</th>
                            <th>Created On</th>
                            <th>Updated On</th> 
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
<?php if (count($user) > 0): ?>
    <?php foreach ($user as $row): ?>
        <tr>
            <td><?= $row['user_id'] ?></td>
            <td><?= $row['user_name'] ?></td>
            <td><?= $row['user_email'] ?></td>
			<td><?= $row['user_unique_id'] ?></td>
            <td><?= $row['user_contact_no'] ?></td>
			<td><?= $row['user_type'] ?></td>
            <td>
                <?= ($row['user_status'] === 'Enable') 
                    ? '<span class="badge bg-success">Active</span>' 
                    : '<span class="badge bg-danger">Disabled</span>' ?>
            </td>
            <td><?= date('Y-m-d H:i:s', strtotime($row['user_created_on'])) ?></td>
			<td><?= date('Y-m-d H:i:s', strtotime($row['user_updated_on'])) ?></td>
 
            <td class="text-center">
                <a href="user.php?action=view&code=<?= $row['user_id'] ?>" class="btn btn-info btn-sm mb-1">
                <i class="fa fa-eye"></i>
                </a>
                <a href="user.php?action=edit&code=<?= $row['user_id'] ?>" class="btn btn-primary btn-sm mb-1">
                    <i class="fa fa-edit"></i>
                </a>
                <button type="button" name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data('<?= $row['user_id'] ?>')">
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
	if (confirm("Are you sure you want to disable this User?")) {
		window.location.href = "user.php?action=delete&code=" + code + "&status=Disable";
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

            const userId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const action = (currentStatus === 'Enable') ? 'disable' : 'enable';

            Swal.fire({
                title: `Are you sure you want to ${action} this user?`,
                text: "This action can be reverted later.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: `Yes, ${action} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `user.php?action=delete&status=${action === 'disable' ? 'Disable' : 'Enable'}&code=${userId}`;
                }
            });
        });
    });
});

</script>



