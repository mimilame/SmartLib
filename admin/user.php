<?php

//user.php

include '../database_connection.php';
include '../function.php';

if(!is_admin_login()) {
	header('location:../admin_login.php');
}

if(isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {
	$user_id = $_GET["code"];
	$status = $_GET["status"];

	$data = array(
		':user_status'		=>	$status,
		':user_updated_on'	=>	get_date_time($connect),
		':user_id'			=>	$user_id
	);

	$query = "
	UPDATE lms_user 
    SET user_status = :user_status, 
    user_updated_on = :user_updated_on 
    WHERE user_id = :user_id
	";

	$statement = $connect->prepare($query);
	$statement->execute($data);

	header('location:user.php?msg='.strtolower($status).'');
}

$query = "
	SELECT * FROM lms_user 
    ORDER BY user_id DESC
";

$statement = $connect->prepare($query);
$statement->execute();

include '../header.php';

?>

<div class="container-fluid py-4" style="min-height: 700px;">
	<h1 class="my-3">User Management</h1>

    <?php 
 	if(isset($_GET["msg"])) {
 		if($_GET["msg"] == 'disable') {
 			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Category Status Changed to Disable <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
 		}

 		if($_GET["msg"] == 'enable') {
 			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Category Status Changed to Enable <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
 		}
 	}
    ?>

	<div class="card">
		<div class="card-header">
			<h5 class="card-title">User Management</h5>
		</div>
		<div class="card-body">
			<div class="table-responsive">
				<table id="dataTable" class="table table-bordered table-striped display responsive nowrap py-4 dataTable no-footer dtr-column collapsed table-active" style="width:100%">
					<thead class="table-light">
						<tr>
							<th></th>
							<th>User ID</th>
							<th>Name</th>
							<th>Email</th>
							<th>Password</th>
							<th>Contact</th>
							<th>Address</th>
							<th>Verified</th>
							<th>Created</th>
							<th>Updated</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
					<?php 
					if($statement->rowCount() > 0) {
						foreach($statement->fetchAll() as $row) {
							echo '
							<tr>
								<td></td>	
								<td>'.$row["user_unique_id"].'</td>
								<td>'.$row["user_name"].'</td>
								<td>'.$row["user_email_address"].'</td>
								<td>'.$row["user_password"].'</td>
								<td>'.$row["user_contact_no"].'</td>
								<td>'.$row["user_address"].'</td>
								<td>'.$row["user_verification_status"].'</td>
								<td>'.$row["user_created_on"].'</td>
								<td>'.$row["user_updated_on"].'</td>
								<td>
									<button type="button" name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data(`'.$row["user_id"].'`)">
										Delete
									</button>
								</td>
							</tr>';
						}
					} else {
						echo '
						<tr>
							<td colspan="10" class="text-center">No Data Found</td>
						</tr>';
					}
					?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<script>
	function delete_data(code) {
		if(confirm("Are you sure you want to disable this User?")) {
			window.location.href = "user.php?action=delete&code=" + code + "&status=Disable";
		}
	}
</script>

<script>
	$(document).ready(function() {	
        $('#dataTable').DataTable({
            responsive: {
                details: {
                    type: 'column',
                    target: 'tr'
                }
            },
            columnDefs: [
                // Add a column for the expand/collapse button
                {
                    className: 'dtr-control',
                    orderable: false,
                    targets: 0
                },
                // Adjust your priorities based on the new column ordering
                { responsivePriority: 1, targets: [0, 1, 2, 10] }, // Control column, ID, Name, Action
                { responsivePriority: 2, targets: [3, 5] },        // Email, Contact
                { responsivePriority: 3, targets: [7] },           // Verification 
                { responsivePriority: 10000, targets: [4, 6, 8, 9] } // Less important columns
            ],
            order: [[1, 'asc']], // Sort by the second column (ID) instead of first
            autoWidth: false,
            language: {
                emptyTable: "No data available"
            }
        });
    });
</script>

<?php include '../footer.php'; ?>
