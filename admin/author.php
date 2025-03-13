<?php

//author.php

include '../database_connection.php';

include '../function.php';
include '../header.php';

if(!is_admin_login())
{
	header('location:../admin_login.php');
}

$message = '';

$error = '';

if(isset($_POST["add_author"]))
{
	$formdata = array();

	if(empty($_POST["author_name"]))
	{
		$error .= '<li>Author Name is required</li>';
	}
	else
	{
		$formdata['author_name'] = trim($_POST["author_name"]);
	}

	if($error == '')
	{
		$query = "
		SELECT * FROM lms_author 
        WHERE author_name = '".$formdata['author_name']."'
		";

		$statement = $connect->prepare($query);

		$statement->execute();

		if($statement->rowCount() > 0)
		{
			$error = '<li>Author Name Already Exists</li>';
		}
		else
		{
			$data = array(
				':author_name'			=>	$formdata['author_name'],
				':author_status'		=>	'Enable',
				':author_created_on'	=>	get_date_time($connect)
			);

			$query = "
			INSERT INTO lms_author 
            (author_name, author_status, author_created_on) 
            VALUES (:author_name, :author_status, :author_created_on)
			";

			$statement = $connect->prepare($query);

			$statement->execute($data);

			header('location:author.php?msg=add');
		}
	}
}

if(isset($_POST["edit_author"]))
{
	$formdata = array();

	if(empty($_POST["author_name"]))
	{
		$error .= '<li>Author Name is required</li>';
	}
	else
	{
		$formdata['author_name'] = trim($_POST['author_name']);
	}

	if($error == '')
	{
		$author_id = convert_data($_POST['author_id'], 'decrypt');

		$query = "
		SELECT * FROM lms_author 
        WHERE author_name = '".$formdata['author_name']."' 
        AND author_id != '".$author_id."'
		";

		$statement = $connect->prepare($query);

		$statement->execute();

		if($statement->rowCount() > 0)
		{
			$error = '<li>Author Name Already Exists</li>';
		}
		else
		{
			$data = array(
				':author_name'		=>	$formdata['author_name'],
				':author_updated_on'=>	get_date_time($connect),
				':author_id'		=>	$author_id
			);	

			$query = "
			UPDATE lms_author 
            SET author_name = :author_name, 
            author_updated_on = :author_updated_on  
            WHERE author_id = :author_id
			";

			$statement = $connect->prepare($query);

			$statement->execute($data);

			header('location:author.php?msg=edit');
		}
	}
}

if(isset($_GET["action"], $_GET["code"], $_GET["status"]) && $_GET["action"] == 'delete')
{
	$author_id = $_GET["code"];

	$status = $_GET["status"];

	$data = array(
		':author_status'			=>	$status,
		':author_updated_on'		=>	get_date_time($connect),
		':author_id'				=>	$author_id
	);

	$query = "
	 UPDATE lms_author 
    SET author_status = :author_status, 
    author_updated_on = :author_updated_on 
    WHERE author_id = :author_id
	";

	$statement = $connect->prepare($query);

	$statement->execute($data);

	header('location:author.php?msg='.strtolower($status).'');
}


$query = "
	SELECT * FROM lms_author 
    ORDER BY author_name ASC
";

$statement = $connect->prepare($query);

$statement->execute();



?>

<main class="container py-4" style="min-height: 700px;">
	<h1>Author Management</h1>
	<?php 

	if(isset($_GET["action"]))
	{
		if($_GET["action"] == "add")
		{
	?>



    <div class="row">
    	<div class="col-md-6">
    		<?php 

    		if($error != '')
    		{
    			echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="list-unstyled">'.$error.'</ul> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    		}

    		?>
    		<div class="card mb-4">
    			<div class="card-header">
    				<i class="fas fa-user-plus"></i> Add New Author
                </div>
                <div class="card-body">
                	<form method="post">
                		<div class="mb-3">
                			<label class="form-label">Author Name</label>
                			<input type="text" name="author_name" id="author_name" class="form-control" />
                		</div>
                		<div class="mt-4 mb-0">
                			<input type="submit" name="add_author" class="btn btn-success" value="Add" />
                		</div>
                	</form>
                </div>
            </div>
    	</div>
    </div>

	<?php 
		}
		else if($_GET["action"] == 'edit')
		{
			$author_id = convert_data($_GET["code"], 'decrypt');

			if($author_id > 0)
			{
				$query = "
				SELECT * FROM lms_author 
                WHERE author_id = '$author_id'
				";

				$author_result = $connect->query($query);

				foreach($author_result as $author_row)
				{
	?>



    <div class="row">
    	<div class="col-md-6">
    		<div class="card mb-4">
    			<div class="card-header">
    				<i class="fas fa-user-edit"></i> Edit Author Details
    			</div>
    			<div class="card-body">
    				<form method="post">
    					<div class="mb-3">
    						<label class="form-label">Author Name</label>
    						<input type="text" name="author_name" id="author_name" class="form-control" value="<?php echo $author_row['author_name']; ?>" />
    					</div>
    					<div class="mt-4 mb-0">
    						<input type="hidden" name="author_id" value="<?php echo $_GET['code']; ?>" />
    						<input type="submit" name="edit_author" class="btn btn-primary" value="Edit" />
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
	}
	else
	{

	?>

	<?php 

	if(isset($_GET["msg"]))
	{
		if($_GET["msg"] == 'add')
		{
			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">New Author Added<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
		}
		if($_GET['msg'] == 'edit')
		{
			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Author Data Edited <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
		}
		if($_GET["msg"] == 'disable')
		{
			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Author Status Change to Disable <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
		}

		if($_GET["msg"] == 'enable')
		{
			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Author Status Change to Enable <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
		}
	}

	?>
	<div class="card mb-4">
		<div class="card-header">
			<div class="row">
				<div class="col col-md-6">
					<i class="fas fa-table me-1"></i> Author Management
				</div>
				<div class="col col-md-6">
					<a href="author.php?action=add" class="btn btn-success btn-sm float-end">Add</a>
				</div>
			</div>
		</div>
		<div class="card-body">
			<table id="dataTable" class="table table-bordered table-striped display responsive nowrap py-4 dataTable no-footer dtr-column collapsed " style="width:100%">
				<thead>
					<tr>
						<th></th>
						<th>Author Name</th>
						<th>Status</th>
						<th>Created On</th>
						<th>Updated On</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
				<?php
				if($statement->rowCount() > 0)
				{
					foreach($statement->fetchAll() as $row)
					{
						$author_status = '';
						if($row['author_status'] == 'Enable')
						{
							$author_status = '<div class="badge bg-success">Enable</div>';
						}
						else
						{
							$author_status = '<div class="badge bg-danger">Disable</div>';
						}
						
						echo '
						<tr>
							<td></td>
							<td>'.$row["author_name"].'</td>
							<td>'.$author_status.'</td>
							<td>'.$row["author_created_on"].'</td>
							<td>'.$row["author_updated_on"].'</td>
							<td>
								<a href="author.php?action=edit&code='.convert_data($row["author_id"]).'" class="btn btn-sm btn-primary">Edit</a>
								<button type="button" name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data(`'.$row["author_id"].'`, `'.$row["author_status"].'`)">Delete</button>
							</td>
						</tr>
						';
					}
				}
				else
				{
					echo '
					<tr>
						<td colspan="4" class="text-center">No Data Found</td>
					</tr>
					';
				}
				?>
				</tbody>
			</table>
		</div>
	</div>

	<script>

		function delete_data(code, status)
		{
			var new_status = 'Enable';

			if(status == 'Enable')
			{
				new_status = 'Disable';
			}

			if(confirm("Are you sure you want to "+new_status+" this Author?"))
			{
				window.location.href = "author.php?action=delete&code="+code+"&status="+new_status+"";
			}
		}

	</script>

	<?php 

	}

	?>
</main>
<script>
	console.log($('#dataTable tbody tr').length);

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
				{ responsivePriority: 1, targets: 0 }, // Author Name column
				{ responsivePriority: 2, targets: 1 }, // Status column
				{ responsivePriority: 3, targets: 2 }, // Created On column
				{ responsivePriority: 4, targets: 3 }, // Updated On column
				{ responsivePriority: 5, targets: 4 }  
            ],
            order: [[1, 'asc']], // Sort by the second column (ID) instead of first
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