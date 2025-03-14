<?php
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

// SELECT Query
$query = "
	SELECT * FROM lms_user 
    ORDER BY user_id DESC
";

$statement = $connect->prepare($query);
$statement->execute();

// Fetch data
$users = $statement->fetchAll(PDO::FETCH_ASSOC); // Explicitly fetch as associative array

include '../header.php';
?>

<main class="container py-4" style="min-height: 700px;">
	<h1 class="my-3">User Management</h1>

    <?php 
 	if(isset($_GET["msg"])) {
 		if($_GET["msg"] == 'disable') {
 			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">User Status Changed to Disable <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
 		}

 		if($_GET["msg"] == 'enable') {
 			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">User Status Changed to Enable <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
 		}
 	}
    ?>

<div class="card mb-4">
    	<div class="card-header">
    		<div class="row">
    			<div class="col col-md-6">
    				<i class="fas fa-table me-1"></i> User Management
                </div>
                <div class="col col-md-6">
                    <a href="user.php?action=add" class="btn btn-success btn-sm float-end">Add</a>
                </div>
            </div>
        </div>
		<div class="card-body">
    <table id="dataTable" class="table table-bordered table-striped display responsive nowrap py-4 dataTable no-footer collapsed table-active" style="width:100%">
        <thead>
            <tr>
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
            if(count($users) > 0) {
                foreach($users as $row) {
                    // Add a badge to verification status
                    $verified = $row["user_verification_status"] == "Yes" ? 
                        '<span class="badge bg-success">Verified</span>' : 
                        '<span class="badge bg-danger">Not Verified</span>';
                    
                    echo '
                    <tr>
                        <td>'.$row["user_unique_id"].'</td>
                        <td>'.$row["user_name"].'</td>
                        <td>'.$row["user_email_address"].'</td>
                        <td>'.$row["user_password"].'</td>
                        <td>'.$row["user_contact_no"].'</td>
                        <td>'.$row["user_address"].'</td>
                        <td>'.$verified.'</td>
                        <td>'.$row["user_created_on"].'</td>
                        <td>'.$row["user_updated_on"].'</td>
                        <td class="text-center">
                            <a href="user.php?action=view&code='.convert_data($row["user_id"]).'" class="btn btn-info btn-sm mb-1">View</a>
                            <a href="user.php?action=edit&code='.convert_data($row["user_id"]).'" class="btn btn-primary btn-sm mb-1">Edit</a>
                            <button type="button" name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data(`'.convert_data($row["user_id"]).'`)">Delete</button>
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
</main>

<script>
function delete_data(code) {
    if(confirm("Are you sure you want to disable this User?")) {
        window.location.href = "user.php?action=delete&code=" + code + "&status=Disable";
    }
}

$(document).ready(function() {    
    $('#dataTable').DataTable({
        responsive: true,
        columnDefs: [
            { responsivePriority: 1, targets: [0, 1, 9] },  // User ID, Name, Action
            { responsivePriority: 2, targets: [2, 4] },     // Email, Contact
            { responsivePriority: 3, targets: [6] },        // Verified
            { responsivePriority: 10000, targets: [3, 5, 7, 8] }  // Password, Address, Created, Updated
        ],
        order: [[0, 'asc']],  // Sort by User ID
        autoWidth: false,
        language: {
            emptyTable: "No data available"
        }
    });
});
</script>


<?php include '../footer.php'; ?>
