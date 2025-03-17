<?php

//category.php

include '../database_connection.php';
include '../function.php';

if (!is_admin_login()) {
	header('location:../admin_login.php');
}

$message = '';
$error = '';

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

	header('location:category.php?msg=' . strtolower($status));
	exit();
}

include '../header.php';

?>

<main class="container py-4" style="min-height: 700px;">
	<h1>Category Management</h1>

	<?php
	if (isset($_GET['action'])) {
		if ($_GET['action'] == 'add') {
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
			$category_id = convert_data($_GET["code"], 'decrypt');

			if ($category_id > 0) {
				$query = "SELECT * FROM lms_category WHERE category_id = :category_id";
				$statement = $connect->prepare($query);
				$statement->execute([':category_id' => $category_id]);

				if ($statement->rowCount() > 0) {
					$category_row = $statement->fetch();
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

		<div class="card mb-4">
			<div class="card-header">
				<div class="row">
					<div class="col col-md-6">
						<i class="fas fa-table me-1"></i> Category Management
					</div>
					<div class="col col-md-6">
						<a href="category.php?action=add" class="btn btn-success btn-sm float-end">Add</a>
					</div>
				</div>
			</div>
			<div class="card-body">

				<table id="dataTable" class="table table-bordered table-striped display responsive nowrap py-4 dataTable no-footer dtr-column collapsed table-active" style="width:100%">
					<thead>
						<tr>
							<th></th>
							<th>Category Name</th>
							<th>Status</th>
							<th>Created On</th>
							<th>Updated On</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						<?php
						if ($statement->rowCount() > 0) {
							foreach ($statement->fetchAll() as $row) {
								$category_status = ($row['category_status'] == 'Enable')
									? '<div class="badge bg-success">Enable</div>'
									: '<div class="badge bg-danger">Disable</div>';

								echo '
								<tr>
									<td></td>
									<td>' . htmlspecialchars($row["category_name"]) . '</td>
									<td>' . $category_status . '</td>
									<td>' . htmlspecialchars($row["category_created_on"]) . '</td>
									<td>' . htmlspecialchars($row["category_updated_on"] ?? 'N/A') . '</td>
									<td>
										<a href="category.php?action=edit&code=' . convert_data($row["category_id"]) . '" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>
										<button name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data(`' . $row["category_id"] . '`, `' . $row["category_status"] . '`)"><i class="fa fa-trash"></i></button>
									</td>
								</tr>
								';
							}
						} else {
							echo '
							<tr>
								<td colspan="6" class="text-center">No Data Found</td>
							</tr>
							';
						}
						?>
					</tbody>
				</table>

				<script>
					function delete_data(code, status) {
						var new_status = (status == 'Enable') ? 'Disable' : 'Enable';

						if (confirm("Are you sure you want to " + new_status + " this Category?")) {
							window.location.href = "category.php?action=delete&code=" + code + "&status=" + new_status;
						}
					}
				</script>

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
				}
			},
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
		});
	});
</script>

<?php
include '../footer.php';
?>
