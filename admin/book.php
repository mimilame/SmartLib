<?php
// book.php

include '../database_connection.php';
include '../function.php';

$message = '';

// Fetch all categories for dropdown lists
$category_query = "SELECT * FROM lms_category ORDER BY category_name ASC";
$category_statement = $connect->prepare($category_query);
$category_statement->execute();
$categories = $category_statement->fetchAll(PDO::FETCH_ASSOC);

// Fetch all racks for dropdown lists
$rack_query = "SELECT DISTINCT book_location_rack FROM lms_book ORDER BY book_location_rack ASC";
$rack_statement = $connect->prepare($rack_query);
$rack_statement->execute();
$racks = $rack_statement->fetchAll(PDO::FETCH_ASSOC);

// Fetch all authors for dropdown lists
$author_query = "SELECT * FROM lms_author ORDER BY author_name ASC";
$author_statement = $connect->prepare($author_query);
$author_statement->execute();
$authors = $author_statement->fetchAll(PDO::FETCH_ASSOC);



// DELETE (Disable/Enable)
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {
    $book_id = $_GET["code"];
    $status = $_GET["status"];

    $data = [
        ':book_status' => $status,
        ':book_id'     => $book_id
    ];

    $query = "
        UPDATE lms_book 
        SET book_status = :book_status 
        WHERE book_id = :book_id
    ";

    $statement = $connect->prepare($query);
    $statement->execute($data);

    header('location:book.php?msg=' . strtolower($status));
    exit;
}

// ADD Book (Form Submit)
if (isset($_POST['add_book'])) {
    $name = trim($_POST['book_name']); // Clean the input
    $category_id = trim($_POST['category_id']);
    $author_ids = isset($_POST['author_ids']) ? $_POST['author_ids'] : [];
    $rack = trim($_POST['book_location_rack']);
    $isbn = trim($_POST['book_isbn_number']);
    $no_of_copies = trim($_POST['book_no_of_copy']);
    $status = trim($_POST['book_status']);

    // Check for duplicate Title or ISBN (case-insensitive comparison)
    $check_query = "
        SELECT COUNT(*) 
        FROM lms_book 
        WHERE LOWER(book_name) = LOWER(:name) OR book_isbn_number = :isbn
    ";

    $statement = $connect->prepare($check_query);
    $statement->execute([
        ':name' => $name,
        ':isbn' => $isbn
    ]);
    $count = $statement->fetchColumn();

    if ($count > 0) {
        // Book title or ISBN already exists
        header('location:book.php?action=add&error=exists');
        exit;
    }

    // If not existing, insert new book
    $date_now = get_date_time($connect); // Get the current date once

    $query = "
        INSERT INTO lms_book 
        (book_name, category_id, book_author, book_location_rack, book_isbn_number, book_no_of_copy, book_status, book_added_on, book_updated_on) 
        VALUES (:name, :category_id, :author, :rack, :isbn, :no_of_copies, :status, :added_on, :updated_on)
    ";
    
    $statement = $connect->prepare($query);
    $statement->execute([
        ':name' => $name,
        ':category_id' => $category_id,
        ':rack' => $rack,
        ':isbn' => $isbn,
        ':no_of_copies' => $no_of_copies,
        ':status' => $status,
        ':added_on' => $date_now,   
        ':updated_on' => $date_now
    ]);
    // Get the new book's ID
    $book_id = $connect->lastInsertId();
        
    // Insert entries into junction table for each author
    if (!empty($author_ids)) {
        $insert_author_query = "
            INSERT INTO lms_book_author (book_id, author_id) 
            VALUES (:book_id, :author_id)
        ";
        
        $author_statement = $connect->prepare($insert_author_query);
        
        foreach ($author_ids as $author_id) {
            $author_statement->execute([
                ':book_id' => $book_id,
                ':author_id' => $author_id
            ]);
        }
    }

    // Commit transaction
    $connect->commit();
        
    header('location:book.php?msg=add');
    exit;
} 



// EDIT Book
if (isset($_POST['edit_book'])) {
    $id = $_POST['book_id'];
    $name = $_POST['book_name'];
    $category_id = $_POST['category_id'];
    $author_ids = isset($_POST['author_ids']) ? $_POST['author_ids'] : [];
    $rack = $_POST['book_location_rack'];
    $isbn = $_POST['book_isbn_number'];
    $no_of_copies = $_POST['book_no_of_copy'];
    $status = $_POST['book_status'];

    $update_query = "
        UPDATE lms_book 
        SET book_name = :name,
            category_id = :category_id,
            book_location_rack = :rack,
            book_isbn_number = :isbn,  
            book_no_of_copy = :no_of_copies,
            book_status = :status
        WHERE book_id = :id
    ";

    $params = [
        ':name' => $name,
        ':category_id' => $category_id,
        ':author' => $author,
        ':rack' => $rack,
        ':isbn' => $isbn,
        ':no_of_copies' => $no_of_copies,
        ':status' => $status,
        ':id' => $id
    ];

    $statement = $connect->prepare($update_query);
    $statement->execute($params);

    // Delete existing author relationships
    $delete_query = "DELETE FROM lms_book_author WHERE book_id = :book_id";
    $delete_statement = $connect->prepare($delete_query);
    $delete_statement->execute([':book_id' => $id]);
    
    // Insert new author relationships
    if (!empty($author_ids)) {
        $insert_author_query = "
            INSERT INTO lms_book_author (book_id, author_id) 
            VALUES (:book_id, :author_id)
        ";
        
        $author_statement = $connect->prepare($insert_author_query);
        
        foreach ($author_ids as $author_id) {
            $author_statement->execute([
                ':book_id' => $id,
                ':author_id' => $author_id
            ]);
        }
    }
    
    // Commit transaction
    $connect->commit();
    
    header('location:book.php?msg=edit');
    exit;
} 


// List all books with their category names
$query = "
    SELECT b.*, c.category_name, 
           GROUP_CONCAT(a.author_name SEPARATOR ', ') as authors
    FROM lms_book b
    LEFT JOIN lms_category c ON b.category_id = c.category_id
    LEFT JOIN lms_book_author ba ON b.book_id = ba.book_id
    LEFT JOIN lms_author a ON ba.author_id = a.author_id
    GROUP BY b.book_id
    ORDER BY b.book_id ASC
";

$statement = $connect->prepare($query);
$statement->execute();
$books = $statement->fetchAll(PDO::FETCH_ASSOC);


include '../header.php';
?>

<main class="container py-4" style="min-height: 700px;">
    <h1 class="my-3">Book Management</h1>

    <?php if (isset($_GET["msg"])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                <?php if ($_GET["msg"] == 'disable'): ?>
                    Swal.fire('Book Disabled', 'The book has been successfully disabled.', 'success');
                <?php elseif ($_GET["msg"] == 'enable'): ?>
                    Swal.fire('Book Enabled', 'The book has been successfully enabled.', 'success');
                <?php elseif ($_GET["msg"] == 'add'): ?>
                    Swal.fire('Book Added', 'The book was added successfully!', 'success');
                <?php elseif ($_GET["msg"] == 'edit'): ?>
                    Swal.fire('Book Updated', 'The book was updated successfully!', 'success');
                <?php endif; ?>

                if (window.history.replaceState) {
                    const url = new URL(window.location);
                    url.searchParams.delete('msg');
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                }
            });
        </script>
    <?php endif; ?>
    
    <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
    <!-- Add Book Form -->
    <div class="card">
        <div class="card-header"><h5>Add Book</h5></div>
        <div class="card-body">

            <?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
                <div class="alert alert-danger" id="error-alert">
                    The book title or ISBN number already exists. Please try again with a different one.
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label>Book Title</label>
                    <input type="text" name="book_name" class="form-control" required>
                </div>

                <!-- Author Dropdown (Only enabled authors) -->
                <div class="mb-3">
                    <label>Author(s)</label>
                    <select name="author_ids[]" class="form-control select2-author" multiple required>
                        <?php 
                        $query = "SELECT author_id, author_name FROM lms_author WHERE author_status = 'Enable' ORDER BY author_name ASC";
                        $statement = $connect->prepare($query);
                        $statement->execute();
                        $authors = $statement->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($authors as $author): ?>
                            <option value="<?= $author['author_id'] ?>"><?= htmlspecialchars($author['author_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Category Dropdown (Only enabled categories) -->
                <div class="mb-3">
                    <label>Category</label>
                    <select name="category_id" class="form-control select2-category" required>
                        <option value="">Select Category</option>
                        <?php 
                        $query = "SELECT category_id, category_name FROM lms_category WHERE category_status = 'Enable' ORDER BY category_name ASC";
                        $statement = $connect->prepare($query);
                        $statement->execute();
                        $categories = $statement->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($categories as $category): ?>
                            <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Rack Dropdown (Only enabled racks) -->
                <div class="mb-3">
                    <label>Location Rack</label>
                    <select name="book_location_rack" class="form-control select2-rack" required>
                        <option value="">Select Rack</option>
                        <?php 
                        $query = "SELECT location_rack_id, location_rack_name FROM lms_location_rack WHERE location_rack_status = 'Enable' ORDER BY location_rack_name ASC";
                        $statement = $connect->prepare($query);
                        $statement->execute();
                        $racks = $statement->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($racks as $rack): ?>
                            <option value="<?= htmlspecialchars($rack['location_rack_id']) ?>"><?= htmlspecialchars($rack['location_rack_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label>ISBN Number</label>
                    <input type="text" name="book_isbn_number" class="form-control">
                </div>
                
                <div class="mb-3">
                    <label>No. of Copy</label>
                    <input type="number" name="book_no_of_copy" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label>Status</label>
                    <select name="book_status" class="form-select">
                        <option value="Enable">Active</option>
                        <option value="Disable">Not Active</option>
                    </select>
                </div>

                <input type="submit" name="add_book" class="btn btn-success" value="Add Book">
                <a href="book.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>


	<?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])): ?>   
	
	<?php
		$id = $_GET['code'];
        $query = "SELECT * FROM lms_book WHERE book_id = :id LIMIT 1";
        $statement = $connect->prepare($query);
        $statement->execute([':id' => $id]);
        $book = $statement->fetch(PDO::FETCH_ASSOC);
        if ($book):
    ?>

    <div class="card">
        <div class="card-header"><h5>Edit Book</h5></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                <div class="mb-3">
                    <label>Book Name</label>
                    <input type="text" name="book_name" class="form-control" value="<?= htmlspecialchars($book['book_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label>Category</label>
                    <select name="category_id" class="form-control select2-category" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['category_id'] ?>" <?= $category['category_id'] == $book['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Author</label>
                    <select name="author_ids[]" class="form-control select2-author" multiple required>
                        <option value="">Select Author</option>
                        <?php // Get current authors for this book
                            $author_query = "
                                SELECT author_id 
                                FROM lms_book_author 
                                WHERE book_id = :book_id
                            ";
                            $author_statement = $connect->prepare($author_query);
                            $author_statement->execute([':book_id' => $id]);
                            $current_authors = $author_statement->fetchAll(PDO::FETCH_COLUMN);
                            foreach ($authors as $author): ?>
                            <option value="<?= $author['author_id'] ?>" 
                                <?= in_array($author['author_id'], $current_authors) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($author['author_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Rack Location</label>
                    <select name="book_location_rack" class="form-control select2-rack" required>
                        <option value="">Select Rack</option>
                        <?php foreach ($racks as $rack): ?>
                            <option value="<?= htmlspecialchars($rack['book_location_rack']) ?>" <?= $rack['book_location_rack'] == $book['book_location_rack'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rack['book_location_rack']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

				<div class="mb-3">
						<label>ISBN Number</label>
						<input type="text" name="book_isbn_number" class="form-control" value="<?= $book['book_isbn_number'] ?>" >
				</div>
                <div class="mb-3">
                    <label>No. of Copy</label>
                    <input type="number" name="book_no_of_copy" class="form-control" value="<?= $book['book_no_of_copy'] ?>" required>
                </div>
                <div class="mb-3">
                    <label>Status</label>
                    <select name="book_status" class="form-control" required>
                        <option value="Enable" <?= $book['book_status'] == 'Enable' ? 'selected' : '' ?>>Enable</option>
                        <option value="Disable" <?= $book['book_status'] == 'Disable' ? 'selected' : '' ?>>Disable</option>
                    </select>
                </div>
                <button type="submit" name="edit_book" class="btn btn-primary">Update Book</button>
                <a href="book.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

    <?php
        else:
            echo '<div class="alert alert-danger">Book not found!</div>';
        endif;

    elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])):
        $id = $_GET['code'];
        // View book details
            $query = "
            SELECT b.*, c.category_name,
                GROUP_CONCAT(a.author_name SEPARATOR ', ') as authors
            FROM lms_book b
            LEFT JOIN lms_category c ON b.category_id = c.category_id
            LEFT JOIN lms_book_author ba ON b.book_id = ba.book_id
            LEFT JOIN lms_author a ON ba.author_id = a.author_id
            WHERE b.book_id = :id
            GROUP BY b.book_id
            LIMIT 1
            ";
        $statement = $connect->prepare($query);
        $statement->execute([':id' => $id]);
        $book = $statement->fetch(PDO::FETCH_ASSOC);

        if ($book): ?>
            <div class="card">
                <div class="card-header"><h5>View Book</h5></div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($book['book_name']); ?></p>
                    <p><strong>Author(s):</strong> <?= htmlspecialchars($book['authors']); ?></p>
                    <p><strong>Category:</strong> <?= htmlspecialchars($book['category_name']); ?></p>
                    <p><strong>Location Rack:</strong> <?= htmlspecialchars($book['book_location_rack']); ?></p>
					<p><strong>ISBN Number:</strong> <?= htmlspecialchars($book['book_isbn_number']); ?></p>
                    <p><strong>No. of Copy:</strong> <?= htmlspecialchars($book['book_no_of_copy']); ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($book['book_status']); ?></p>
                    <a href="book.php" class="btn btn-secondary">Back</a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">Book not found!</div>
        <?php endif;

    else: ?>

	<!-- Book List -->

        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col col-md-6">
                        <i class="fas fa-table me-1"></i> Book Management
                    </div>
                    <div class="col col-md-6">
                        <a href="book.php?action=add" class="btn btn-success btn-sm float-end">Add Book</a>
                    </div>
                </div>
            </div>
            <div class="card-body" style="overflow-x: auto;">
                <table id="dataTable" class="display nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Book ID</th>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Category</th>
							<th>Location Rack</th>
                            <th>ISBN Number</th>
                            <th>No of Copy</th>
                            <th>Status</th>
                            <th>Added On</th>
							<th>Updated On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($books)): ?>
                            <?php foreach ($books as $row): ?>
                                <tr>
                                    <td><?= $row['book_id'] ?></td>
                                    <td><?= htmlspecialchars($row['book_name']) ?></td>
                                    <td><?= htmlspecialchars($row['authors']) ?></td>
                                    <td><?= htmlspecialchars($row['category_name']) ?></td>
									<td><?= htmlspecialchars($row['book_location_rack']) ?></td>
                                    <td><?= htmlspecialchars($row['book_isbn_number']) ?></td>
                                    <td><?= htmlspecialchars($row['book_no_of_copy']) ?></td>

									<td>
              						  <?= ($row['book_status'] === 'Enable') 
                 					   ? '<span class="badge bg-success">Active</span>' 
                  					  : '<span class="badge bg-danger">Disabled</span>' ?>
          							</td>

            						<td><?= date('Y-m-d H:i:s', strtotime($row['book_added_on'])) ?></td>
									<td><?= date('Y-m-d H:i:s', strtotime($row['book_updated_on'])) ?></td>

                                    <td class="text-center">
                                        <a href="book.php?action=view&code=<?= $row['book_id'] ?>" class="btn btn-info btn-sm mb-1">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="book.php?action=edit&code=<?= $row['book_id'] ?>" class="btn btn-primary btn-sm mb-1">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm delete-btn"
                                                data-id="<?= $row['book_id'] ?>"
                                                data-status="<?= $row['book_status'] ?>">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center">No books found!</td></tr>
                        <?php endif; ?>

                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

</main>

<script>
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
                title: `Are you sure you want to ${action} this book?`,
                text: "This action can be reverted later.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: `Yes, ${action} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `book.php?action=delete&status=${action === 'disable' ? 'Disable' : 'Enable'}&code=${bookId}`;
                }
            });
        });
    });
});

    $(document).ready(function() {
  $('#dataTable').DataTable({
    responsive: true,       // Enable responsiveness
    scrollX: true,          // Enable horizontal scrolling for many columns
    scrollY: '400px',       // Optional: Sets vertical scroll area (adjust height as needed)
    scrollCollapse: true,   // Collapse table height if fewer records
    autoWidth: false,
	info: true,       // Disable auto column width (we can define custom widths if needed)
    paging: true,           // Enable pagination
    order: [[0, 'asc']],    // Default sort by the first column (book_id)

    columnDefs: [
      { responsivePriority: 1, targets: 0 },  // book_id
      { responsivePriority: 2, targets: 1 },  // category_id
      { responsivePriority: 3, targets: 4 },  // book_name
      { responsivePriority: 4, targets: 2 },  // book_author
      { responsivePriority: 5, targets: 5 },  // book_isbn_number
      // Less important columns will hide first on smaller screens
    ],

    language: {
      emptyTable: "No books found in the table."
    }
  });
});

</script>

<script>
	$(document).ready(function() {
		$('.select2-author').select2({
            placeholder: "Select Author(s)",
            allowClear: true,
            multiple: true
        });
		$('.select2-category').select2({
			placeholder: "Select Category",
			allowClear: true
		});
		$('.select2-rack').select2({
			placeholder: "Select Rack Location",
			allowClear: true
		});
	});
</script>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		const alertBox = document.getElementById('error-alert');

		if (alertBox) {
			// Display the alert for 3 seconds (3000ms)
			setTimeout(function() {
				// Optional fade-out effect
				alertBox.style.transition = 'opacity 0.5s ease';
				alertBox.style.opacity = '0';

				// Remove the alert after fade (0.5s delay)
				setTimeout(function() {
					alertBox.remove();
				}, 500);

				// Remove 'error' param from the URL after the alert disappears
				if (window.history.replaceState) {
					const url = new URL(window.location);
					url.searchParams.delete('error');
					window.history.replaceState({}, document.title, url.pathname + url.search);
				}
			}, 3000); // You can change this to 5000 for 5 seconds, etc.
		}
	});
</script>

<?php if (isset($_GET["msg"])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                <?php if ($_GET["msg"] == 'disable'): ?>
                    Swal.fire('Book Disabled', 'The book has been successfully disabled.', 'success');
                <?php elseif ($_GET["msg"] == 'enable'): ?>
                    Swal.fire('Book Enabled', 'The book has been successfully enabled.', 'success');
                <?php elseif ($_GET["msg"] == 'add'): ?>
                    Swal.fire('Book Added', 'The book was added successfully!', 'success');
                <?php elseif ($_GET["msg"] == 'edit'): ?>
                    Swal.fire('Book Updated', 'The book was updated successfully!', 'success');
                <?php endif; ?>

                if (window.history.replaceState) {
                    const url = new URL(window.location);
                    url.searchParams.delete('msg');
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                }
            });
        </script>
    <?php endif; ?>

    <?php 

include '../footer.php';

?>
