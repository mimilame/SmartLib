<?php

//location_rack.php

include '../database_connection.php';

include '../function.php';

if(!is_admin_login())
{
	header('location:../admin_login.php');
}

$message = '';

$error = '';

if(isset($_POST["add_location_rack"]))
{
	$formdata = array();

	if(empty($_POST["location_rack_name"]))
	{
		$error .= '<li>Location Rack Name is required</li>';
	}
	else
	{
		$formdata['location_rack_name'] = trim($_POST["location_rack_name"]);
	}

	if($error == '')
	{
		$query = "
		SELECT * FROM lms_location_rack 
        WHERE location_rack_name = '".$formdata['location_rack_name']."'
		";

		$statement = $connect->prepare($query);

		$statement->execute();

		if($statement->rowCount() > 0)
		{
			$error = '<li>Location Rack Name Already Exists</li>';
		}
		else
		{
			$data = array(
				':location_rack_name'		=>	$formdata['location_rack_name'],
				':location_rack_status'		=>	'Enable',
				':location_rack_created_on'	=>	get_date_time($connect)
			);

			$query = "
			INSERT INTO lms_location_rack 
            (location_rack_name, location_rack_status, location_rack_created_on) 
            VALUES (:location_rack_name, :location_rack_status, :location_rack_created_on)
			";

			$statement = $connect->prepare($query);

			$statement->execute($data);

			header('location:location_rack.php?msg=add');
		}
	}
}

if(isset($_POST["edit_location_rack"]))
{
	$formdata = array();

	if(empty($_POST["location_rack_name"]))
	{
		$error .= '<li>Location Rack Name is required</li>';
	}
	else
	{
		$formdata['location_rack_name'] = trim($_POST["location_rack_name"]);
	}

	if($error == '')
	{
		$location_rack_id = convert_data($_POST["location_rack_id"], 'decrypt');

		$query = "
		SELECT * FROM lms_location_rack 
	        WHERE location_rack_name = '".$formdata['location_rack_name']."' 
	        AND location_rack_id != '".$location_rack_id."'
		";

		$statement = $connect->prepare($query);

		$statement->execute();

		if($statement->rowCount() > 0)
		{
			$error = '<li>Location Rack Name Already Exists</li>';
		}
		else
		{
			$data = array(
				':location_rack_name'		=>	$formdata['location_rack_name'],
				':location_rack_updated_on'	=>	get_date_time($connect),
				':location_rack_id'			=>	$location_rack_id
			);

			$query = "
			UPDATE lms_location_rack 
	            SET location_rack_name = :location_rack_name, 
	            location_rack_updated_on = :location_rack_updated_on  
	            WHERE location_rack_id = :location_rack_id
			";

			$statement = $connect->prepare($query);

			$statement->execute($data);

			header('location:location_rack.php?msg=edit');
		}
	}
}

if(isset($_GET["action"], $_GET["code"], $_GET["status"]) && $_GET["action"]=='delete')
{
	$location_rack_id = $_GET["code"];

	$status = $_GET["status"];

	$data = array(
		':location_rack_status'			=>	$status,
		':location_rack_updated_on'		=>	get_date_time($connect),
		':location_rack_id'				=>	$location_rack_id
	);
	$query = "
	UPDATE lms_location_rack 
    SET location_rack_status = :location_rack_status, 
    location_rack_updated_on = :location_rack_updated_on 
    WHERE location_rack_id = :location_rack_id
	";

	$statement = $connect->prepare($query);

	$statement->execute($data);

	header('location:location_rack.php?msg='.strtolower($status).'');

}


$query = "
	SELECT * FROM lms_location_rack 
    ORDER BY location_rack_name ASC
";

$statement = $connect->prepare($query);

$statement->execute();

include '../header.php';

?>

<div class="container-fluid py-4" style="min-height: 700px;">
	<h1>Location Rack Management</h1>
	<?php 

	if(isset($_GET["action"]))
	{
		if($_GET["action"] == 'add')
		{
		?>
	

	<div class="row">
		<div class="col-md-6">
			<?php 

			if($error != '')
			{
				echo '
				<div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="list-unstyled">'.$error.'</ul> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
				';
			}

			?>
			<div class="card mb-4">
				<div class="card-header">
					<i class="fas fa-user-plus"></i> Add New Location Rack
                </div>
                <div class="card-body">
                	<form method="post">
                		<div class="mb-3">
                			<label class="form-label">Location Rack Name</label>
                			<input type="text" name="location_rack_name" id="location_rack_name" class="form-control" />
                		</div>
                		<div class="mt-4 mb-0">
                			<input type="submit" name="add_location_rack" class="btn btn-success" value="Add" />
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
			$location_rack_id = convert_data($_GET["code"], 'decrypt');

			if($location_rack_id > 0)
			{
				$query = "
				SELECT * FROM lms_location_rack 
                WHERE location_rack_id = '$location_rack_id'
				";

				$location_rack_result = $connect->query($query);

				foreach($location_rack_result as $location_rack_row)
				{
	?>

    <div class="row">
    	<div class="col-md-6">
    		<div class="card mb-4">
    			<div class="card-header">
    				<i class="fas fa-user-edit"></i> Edit Location Rack Details
                </div>
                <div class="card-body">
                	<form method="post">
                		<div class="mb-3">
                			<label class="form-label">Location Rack Name</label>
                			<input type="text" name="location_rack_name" id="location_rack_name" class="form-control" value="<?php echo $location_rack_row["location_rack_name"]; ?>" />
                		</div>
                		<div class="mt-4 mb-0">
                			<input type="hidden" name="location_rack_id" value="<?php echo $_GET['code']; ?>" />
                			<input type="submit" name="edit_location_rack" class="btn btn-primary" value="Edit" />
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
				echo '<div class="alert alert-success alert-dismissible fade show" role="alert">New Location Rack Added<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
			}

			if($_GET["msg"] == 'edit')
			{
				echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Location Rack Data Edited <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
			}

			if($_GET["msg"] == 'disable')
			{
				echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Location Rack Status Change to Disable <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
			}

			if($_GET["msg"] == 'enable')
			{
				echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Location Rack Status Change to Enable <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
			}
		}

		?>
	<div class="card mb-4">
		<div class="card-header">
			<div class="row">
				<div class="col col-md-6">
					<i class="fas fa-table me-1"></i> Location Rack Management
				</div>
				<div class="col col-md-6">
					<a href="location_rack.php?action=add" class="btn btn-success btn-sm float-end">Add</a>
				</div>
			</div>
		</div>
		<div class="card-body">
		<table id="dataTable" class="table table-bordered table-striped display responsive nowrap py-4 dataTable no-footer dtr-column collapsed table-active" style="width:100%">
				<thead>
					<tr>
						<th></th>
						<th>Location Rack Name</th>
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
						$location_rack_status = '';
						if($row['location_rack_status'] == 'Enable')
						{
							$location_rack_status = '<div class="badge bg-success">Enable</div>';
						}
						else
						{
							$location_rack_status = '<div class="badge bg-danger">Disable</div>';
						}

						echo '
						<tr>
							<td></td>
							<td>'.$row["location_rack_name"].'</td>
							<td>'.$location_rack_status.'</td>
							<td>'.$row["location_rack_created_on"].'</td>
							<td>'.$row["location_rack_updated_on"].'</td>
							<td>
								<a href="location_rack.php?action=edit&code='.convert_data($row["location_rack_id"]).'" class="btn btn-sm btn-primary">Edit</a>
								<button type="button" name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data(`'.$row["location_rack_id"].'`, `'.$row["location_rack_status"].'`)">Delete</button>
							</td>
						</tr>
						';

					}
				}
				else
				{
					echo '
					<tr>
						<td colspan="5" class="text-center">No Data Found</td>
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

			if(confirm("Are you sure you want to "+new_status+" this Category?"))
			{
				window.location.href = "location_rack.php?action=delete&code="+code+"&status="+new_status+""
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
					{ responsivePriority: 1, targets: 0 }, // Control column
					{ responsivePriority: 2, targets: 1 }, // Location Rack Name
					{ responsivePriority: 3, targets: 2 }, // Status
					{ responsivePriority: 4, targets: 3 }, // Created On
					{ responsivePriority: 5, targets: 4 }, // Updated On
					{ responsivePriority: 6, targets: 5 }  // Action
				],
				order: [[1, 'asc']], // Sort by the second column (Location Rack Name)
				autoWidth: false,
				language: {
					emptyTable: "No data available"
				}
			});
		});

</script>

	<?php 

	}

	?>

</div>



<?php 

include '../footer.php';

?>