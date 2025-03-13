<?php

//issue_book_details.php

include 'database_connection.php';

include 'function.php';

if(!is_user_login())
{
	header('location:user_login.php');
}

$query = "
	SELECT * FROM lms_issue_book 
	INNER JOIN lms_book 
	ON lms_book.book_isbn_number = lms_issue_book.book_id 
	WHERE lms_issue_book.user_id = '".$_SESSION['user_id']."' 
	ORDER BY lms_issue_book.issue_book_id DESC
";

$statement = $connect->prepare($query);

$statement->execute();

include 'header.php';

?>
<main class="container py-4" style="min-height: 700px;">
	<h1>Issue Book Detail</h1>
	<div class="card mb-4">
		<div class="card-header">
			<div class="row">
				<div class="col col-md-6">
					<i class="fas fa-table me-1"></i> Issue Book Detail
				</div>
				<div class="col col-md-6">
				</div>
			</div>
		</div>
		<div class="card-body">
			<table id="dataTable" class="table table-bordered table-striped display responsive nowrap py-4 dataTable no-footer dtr-column collapsed table-active" style="width:100%">
				<thead>
					<tr>
						<th></th>
						<th>Book ISBN No.</th>
						<th>Book Name</th>
						<th>Issue Date</th>
						<th>Return Date</th>
						<th>Fines</th>
						<th>Status</th>
					</tr>
				</thead>
				
				<tbody>
				<?php 
				if($statement->rowCount() > 0)
				{
					foreach($statement->fetchAll() as $row)
					{
						$status = $row["book_issue_status"];
						if($status == 'Issue')
						{
							$status = '<span class="badge bg-warning">Issue</span>';
						}

						if($status == 'Not Return')
						{
							$status = '<span class="badge bg-danger">Not Return</span>';
						}

						if($status == 'Return')
						{
							$status = '<span class="badge bg-primary">Return</span>';
						}

						echo '
						<tr>
							<td></td>
							<td>'.$row["book_isbn_number"].'</td>
							<td>'.$row["book_name"].'</td>
							<td>'.$row["issue_date_time"].'</td>
							<td>'.$row["return_date_time"].'</td>
							<td>'.get_currency_symbol($connect).$row["book_fines"].'</td>
							<td>'.$status.'</td>
						</tr>
						';
					}
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
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
			columnDefs: [
				// Add a column for the expand/collapse button
				{
					className: 'dtr-control',
					orderable: false,
					targets: 0
				},
				// Adjust priorities based on your actual column count (7 columns total)
				{ responsivePriority: 1, targets: [0, 1, 2] },    // Control column, ISBN, Book Name
				{ responsivePriority: 2, targets: [3, 6] },       // Issue Date, Status
				{ responsivePriority: 3, targets: [5] },          // Fines
				{ responsivePriority: 4, targets: [4] }           // Return Date
			],
			order: [[1, 'asc']], // Sort by the second column (ISBN) by default
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