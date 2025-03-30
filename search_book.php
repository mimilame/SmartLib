<?php

//search_book.php

include 'database_connection.php';

include 'function.php';
include 'header.php';

if(!is_user_login())
{
	header('location:user_login.php');
}

$query = "
	SELECT * FROM lms_book 
    WHERE book_status = 'Enable' 
    ORDER BY book_id DESC
";

$statement = $connect->prepare($query);

$statement->execute();




?>

<main class="container py-4" style="min-height: 700px;">

	<h1>Search Book</h1>

	<div class="card mb-4">
		<div class="card-header">
			<div class="row">
				<div class="col col-md-6">
					<i class="fas fa-table me-1"></i> Book List
				</div>
				<div class="col col-md-6">

				</div>
			</div>
		</div>
		<div class="card-body">
			<table id="dataTable" class="table table-bordered table-striped display responsive nowrap py-4 dataTable no-footer dtr-column collapsed " style="width:100%">
				<thead>
					<tr>
						<th></th> 
						<th>Book Name</th>
						<th>ISBN Number</th>
						<th>Category</th>
						<th>Author</th>
						<th>Location Rack</th>
						<th>No. of Copies</th>
						<th>Status</th>
						<th>Added On</th>
					</tr>
				</thead>
				<tbody>
				<?php 

				if($statement->rowCount() > 0)
				{
					foreach($statement->fetchAll() as $row)
					{
						$book_status = '';
						if($row['book_no_of_copy'] > 0)
						{
							$book_status = '<div class="badge bg-success">Available</div>';
						}
						else
						{
							$book_status = '<div class="badge bg-danger">Not Available</div>';
						}
						echo '
							<tr>
								<td></td>
								<td>'.$row["book_name"].'</td>
								<td>'.$row["book_isbn_number"].'</td>
								<td>'.$row["book_category"].'</td>
								<td>'.$row["book_author"].'</td>
								<td>'.$row["book_location_rack"].'</td>
								<td>'.$row["book_no_of_copy"].'</td>
								<td>'.$book_status.'</td>
								<td>'.$row["book_added_on"].'</td>
							</tr>
						';
					}
				}
				else
				{
					echo '
					<tr>
						<td colspan="8" class="text-center">No Data Found</td>
					</tr>
					';
				}

				?>
				</tbody>
			</table>
		</div>
	</div>
</main>
<script>
	
	$(document).ready(function() {	
		$('#dataTable').DataTable().destroy();
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
                { responsivePriority: 1, targets: [0, 1, 2, 9] }, // Control column, ID, Name, Action
                { responsivePriority: 2, targets: [3, 5] },        // Email, Contact
                { responsivePriority: 3, targets: [7] },           // Verification 
                { responsivePriority: 10000, targets: [4, 6, 8] } // Less important columns
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

include 'footer.php';

?>