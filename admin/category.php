<?php

//category.php

include '../database_connection.php';
include '../function.php';

if (!is_admin_login()) {
	header('location:../admin_login.php');
}

$message = '';
$error = '';
$alert = '';

// ADD CATEGORY
if (isset($_POST['add_category'])) {
	$formdata = array();

	if (empty($_POST['category_name'])) {
		$error .= '<li>Category Name is required</li>';
	} else {
		$formdata['category_name'] = trim($_POST['category_name']);
	}

	if ($error == '') {
		$query = "SELECT * FROM lms_category WHERE category_name = :category_name";
		$statement = $connect->prepare($query);
		$statement->execute([':category_name' => $formdata['category_name']]);

		if ($statement->rowCount() > 0) {
			$error = '<li>Category Name Already Exists</li>';
		} else {
			$data = array(
				':category_name' => $formdata['category_name'],
				':category_status' => 'Enable',
				':category_created_on' => get_date_time($connect)
			);

			$query = "INSERT INTO lms_category (category_name, category_status, category_created_on)
					  VALUES (:category_name, :category_status, :category_created_on)";
			$statement = $connect->prepare($query);
			$statement->execute($data);

			set_flash_message('success', 'New Category Added Successfully');
			header('location:category.php?msg=add');
			exit();
		}
	}
}

// EDIT CATEGORY
if (isset($_POST["edit_category"])) {
	$formdata = array();

	if (empty($_POST["category_name"])) {
		$error .= '<li>Category Name is required</li>';
	} else {
		$formdata['category_name'] = trim($_POST['category_name']);
	}

	if ($error == '') {
		$category_id = convert_data($_POST['category_id'], 'decrypt');

		$query = "SELECT * FROM lms_category WHERE category_name = :category_name AND category_id != :category_id";
		$statement = $connect->prepare($query);
		$statement->execute([
			':category_name' => $formdata['category_name'],
			':category_id' => $category_id
		]);

		if ($statement->rowCount() > 0) {
			$error = '<li>Category Name Already Exists</li>';
		} else {
			$data = array(
				':category_name' => $formdata['category_name'],
				':category_updated_on' => get_date_time($connect),
				':category_id' => $category_id
			);

			$query = "UPDATE lms_category 
					  SET category_name = :category_name, category_updated_on = :category_updated_on  
					  WHERE category_id = :category_id";
			$statement = $connect->prepare($query);
			$statement->execute($data);

			set_flash_message('success', 'Category Updated Successfully');
			header('location:category.php?msg=edit');
			exit();
		}
	}
}

// DELETE/ENABLE/DISABLE CATEGORY
if (isset($_GET["action"], $_GET["code"], $_GET["status"]) && $_GET["action"] == 'delete') {
	$category_id = $_GET["code"];
	$status = $_GET["status"];

	$data = array(
		':category_status' => $status,
		':category_updated_on' => get_date_time($connect),
		':category_id' => $category_id
	);

	$query = "UPDATE lms_category 
			  SET category_status = :category_status, category_updated_on = :category_updated_on 
			  WHERE category_id = :category_id";
	$statement = $connect->prepare($query);
	$statement->execute($data);

	$message = ($status == 'Active') ? 'Category Marked as Active' : 'Category Marked as Inactive';
    set_flash_message('success', $message);
	header('location:category.php?msg=' . strtolower($status));
	exit();
}

// =================== FETCH ALL CATEGORY ===================
$query = "SELECT * FROM lms_category ORDER BY category_name ASC";
$statement = $connect->prepare($query);
$statement->execute();
$categories = $statement->fetchAll(PDO::FETCH_ASSOC);

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

<main class="container py-4" style="min-height: 700px;">
	<h1>Category Management</h1>

	<?php echo $alert; ?>
	<?php
	if (isset($_GET['action'])) {
		if ($_GET['action'] == 'add') {
	if (isset($_GET['action'])): ?>
	<?php
		if ($_GET['action'] == 'add'):
	?>

		<div class="row">
			<div class="col-md-6">
				<?php
				if ($error != '') {
					echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="list-unstyled">' . $error . '</ul> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
				}
				?>
				<div class="card mb-4">
					<div class="card-header">
						<i class="fas fa-user-plus"></i> Add New Category
					</div>
					<div class="card-body">
						<form method="POST">
							<div class="mb-3">
								<label class="form-label">Category Name</label>
								<input type="text" name="category_name" id="category_name" class="form-control" />
							</div>
							<div class="mt-4 mb-0">
								<input type="submit" name="add_category" value="Add" class="btn btn-success" />
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

	<?php
		} else if ($_GET["action"] == 'edit') {
	<?php  elseif ($_GET["action"] == 'edit'):
			$category_id = convert_data($_GET["code"], 'decrypt');

			if ($category_id > 0) {
				$query = "SELECT * FROM lms_category WHERE category_id = :category_id";
				$statement = $connect->prepare($query);
				$statement->execute([':category_id' => $category_id]);
			$query = "SELECT * FROM lms_category WHERE category_id = :category_id";
			$statement = $connect->prepare($query);
			$statement->execute([':category_id' => $category_id]);

				if ($statement->rowCount() > 0) {
					$category_row = $statement->fetch();
			$category_row = $statement->fetch(PDO::FETCH_ASSOC);
			if ($category_row):	
	?>

					<div class="row">
						<div class="col-md-6">
							<div class="card mb-4">
								<div class="card-header">
									<i class="fas fa-user-edit"></i> Edit Category Details
								</div>
								<div class="card-body">
									<form method="post">
										<div class="mb-3">
											<label class="form-label">Category Name</label>
											<input type="text" name="category_name" class="form-control" value="<?php echo htmlspecialchars($category_row['category_name']); ?>" />
										</div>
										<div class="mt-4 mb-0">
											<input type="hidden" name="category_id" value="<?php echo $_GET['code']; ?>" />
											<input type="submit" name="edit_category" class="btn btn-primary" value="Edit" />
										</div>
									</form>
								</div>
							</div>

						</div>
					</div>

	<?php
				}
			}
		}
	} else {
	?>

		<?php
		if (isset($_GET['msg'])) {
			if ($_GET['msg'] == 'add') {
				echo '<div class="alert alert-success alert-dismissible fade show" role="alert">New Category Added<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
			}
			if ($_GET["msg"] == 'edit') {
				echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Category Data Edited <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
			}
			if ($_GET["msg"] == 'disable') {
				echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Category Status Changed to Disable <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
			}
			if ($_GET['msg'] == 'enable') {
				echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Category Status Changed to Enable <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
			}
		}

		$query = "SELECT * FROM lms_category ORDER BY category_id DESC";
		$statement = $connect->prepare($query);
		$statement->execute();
		?>
	<?php endif; ?>
	<?php endif; ?>

	<?php else: ?>
		<div class="card mb-4">
			<div class="card-header">
				<div class="row">
					<div class="col col-md-6">
						<i class="fas fa-table me-1"></i> Category Management
					</div>
					<div class="col col-md-6">
						<a href="category.php?action=add" class="btn btn-success btn-sm float-end">Add</a>
    					<a href="javascript:void(0);" onclick="openAddModal()" class="btn btn-sm btn-success float-end">Add</a>
					</div>
				</div>
			</div>
			<div class="card-body">

				<table id="dataTable" class="table table-bordered table-striped display responsive nowrap py-4 dataTable no-footer dtr-column collapsed table-active" style="width:100%">
					<thead>
				<table id="dataTable" class="table table-bordered table-striped display responsive nowrap py-4 dataTable no-footer dtr-column collapsed " style="width:100%">
					<thead class="thead-light">
						<tr>
							<th></th>
							<th>ID</th>
							<th>Category Name</th>
							<th>Status</th>
							<th>Created On</th>
							<th>Updated On</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						if ($statement->rowCount() > 0) {
							foreach ($statement->fetchAll() as $row) {
								$category_status = ($row['category_status'] == 'Enable')
									? '<div class="badge bg-success">Enable</div>'
									: '<div class="badge bg-danger">Disable</div>';

								echo '
						<?php if (!empty($categories)): ?>
							<?php foreach ($categories as $row): ?>
								<tr>
									<td>' . htmlspecialchars($row["category_name"]) . '</td>
									<td>' . $category_status . '</td>
									<td>' . htmlspecialchars($row["category_created_on"]) . '</td>
									<td>' . htmlspecialchars($row["category_updated_on"] ?? 'N/A') . '</td>
									<td>
										<div class="badge bg-<?= ($row['category_status'] === 'Enable') ? 'success' : 'danger'; ?>">
											<?= ($row['category_status'] === 'Enable') ? 'Active' : 'Inactive'; ?>
										</div>
									</td>
									<td><?= $row["category_created_on"]; ?></td>
									<td><?= $row["category_updated_on"] ?? 'N/A'; ?></td>
									<td>
										<button name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data(`' . $row["category_id"] . '`, `' . $row["category_status"] . '`)"><i class="fa fa-trash"></i></button>
										</a>
										<button type="button" class="btn btn-<?= ($row['category_status'] === 'Enable') ? 'danger' : 'success'; ?> btn-sm"
											onclick="toggle_status('<?= convert_data($row["category_id"]); ?>', '<?= $row["category_status"]; ?>')">
											<?= ($row['category_status'] === 'Enable') ? 'Deactivate' : 'Activate'; ?>
										</button>
									</td>
								</tr>
								';
							}
						} else {
							echo '
							<tr>
							</tr>
						}
						?>
					</tbody>

				<script>
					function delete_data(code, status) {
						var new_status = (status == 'Enable') ? 'Disable' : 'Enable';

							window.location.href = "category.php?action=delete&code=" + code + "&status=" + new_status;
						}
					}
				</script>

					<div class="modal-dialog modal-dialog-centered">
						<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<form method="post">
							<div class="modal-body">
							<div class="mb-3">
								<label for="edit_category_name" class="form-label">Category Name</label>
								<input type="text" class="form-control" id="edit_category_name" name="category_name" required>
							</div>
							<input type="hidden" id="edit_category_id" name="category_id">
							</div>
							<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" name="edit_category" class="btn btn-primary">Save Changes</button>
							</div>
						</form>
						</div>
					</div>
				</div>
				<!-- Add Modal -->
				<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
					<div class="modal-dialog modal-dialog-centered">
						<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<form method="post">
							<div class="modal-body">
							<div class="mb-3">
								<label for="add_category_name" class="form-label">Category Name</label>
								<input type="text" class="form-control" id="add_category_name" name="category_name" required>
							</div>
							<input type="hidden" id="add_category_id" name="category_id">
							</div>
							<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" name="add_category" class="btn btn-primary">Save Changes</button>
							</div>
						</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php
	}
	?>

</main>

<script>
	$(document).ready(function() {
		$('#dataTable').DataTable({
			responsive: {
				details: {
					type: 'column',
					target: 'tr'

	<script>
		function toggle_status(code, status) {
			let newStatus = status === 'Enable' ? 'Disable' : 'Enable';
			let statusText = status === 'Enable' ? 'mark as Inactive' : 'mark as Active';
			
			Swal.fire({
				title: 'Are you sure?',
				text: "You want to " + statusText + " this category?",
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Yes, ' + statusText + '!'
			}).then((result) => {
				if (result.isConfirmed) {
					window.location.href = "category.php?action=delete&code=" + code + "&status=" + newStatus;
				}
			columnDefs: [{
					className: 'dtr-control',
					orderable: false,
					targets: 0
				},
				{
					responsivePriority: 1,
					targets: 1
				},
				{
					responsivePriority: 2,
					targets: 2
				},
				{
					responsivePriority: 3,
					targets: 3
				},
				{
					responsivePriority: 4,
					targets: 4
		// Function to open edit modal
		function openEditModal(id, name) {
			document.getElementById('edit_category_id').value = id;
			document.getElementById('edit_category_name').value = name;
			
			// Show the modal
			const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
			editModal.show();
		}
		// Function to open add modal
		function openAddModal() {
			document.getElementById('add_category_id').value = '';
			document.getElementById('add_category_name').value = '';
			
			// Show the modal
			const addModal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
			addModal.show();
		}

		$(document).ready(function() {
			$('#dataTable').DataTable({
				responsive: {
					details: {
						type: 'column',
						target: 'tr'
					}
				},
					responsivePriority: 5,
					targets: 5
						orderable: false,
						targets: 0
					},
					{
						responsivePriority: 1,
						targets: 1
					},
					{
						responsivePriority: 2,
						targets: 2
					},
					{
						responsivePriority: 3,
						targets: 3
					},
					{
						responsivePriority: 4,
						targets: 4
					},
					{
						responsivePriority: 5,
						targets: 5
					}
				],
				order: [
					[1, 'asc']
				],
				autoWidth: false,
				language: {
					emptyTable: "No data available"
				}
			order: [
				[1, 'asc']
			],
			autoWidth: false,
			language: {
				emptyTable: "No data available"
			}
		});
</script>
</main>


<?php
include '../footer.php';
?>
