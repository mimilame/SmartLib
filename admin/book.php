<?php
    // book.php

    include '../database_connection.php';
    include '../function.php';
    include '../header.php';
    authenticate_admin();
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
            ':book_id' => $book_id
        ];
        $query = "UPDATE lms_book SET book_status = :book_status WHERE book_id = :book_id";
        $statement = $connect->prepare($query);
        $statement->execute($data);
        header('location:book.php?msg=' . strtolower($status));
        exit;
    }
    // First, let's create the upload directory if it doesn't exist
    $upload_dir = "../upload/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // ADD Book (Form Submit)
    if (isset($_POST['add_book'])) {
        $name = trim($_POST['book_name']); // Clean the input
        $category_id = trim($_POST['category_id']);
        $author_ids = $_POST['author_ids'];
        $rack = trim($_POST['book_location_rack']);
        $isbn = trim($_POST['book_isbn_number']);
        $no_of_copies = trim($_POST['book_no_of_copy']);
        $status = trim($_POST['book_status']);
        $description = trim($_POST['book_description']); // New description field
        $edition = trim($_POST['book_edition'] ?? '');
        $publisher = trim($_POST['book_publisher'] ?? '');
        $published = isset($_POST['book_published']) ? trim($_POST['book_published']) : null;

        // Default image path
        $book_img = "book_placeholder.png";

        // Handle image upload
        if (isset($_FILES['book_img']) && $_FILES['book_img']['error'] == 0) {
            $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png");
            $filename = $_FILES['book_img']['name'];
            $filetype = $_FILES['book_img']['type'];
            $filesize = $_FILES['book_img']['size'];
            
            // Verify file extension
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if (array_key_exists($ext, $allowed)) {
                // Check file size - 5MB maximum
                $maxsize = 5 * 1024 * 1024;
                if ($filesize < $maxsize) {
                    // Generate unique filename
                    $new_filename = 'book_' . time() . '.' . $ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['book_img']['tmp_name'], $upload_path)) {
                        $book_img =  $new_filename;
                    }
                }
            }
        }

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

        // Get author names for the book_author field
        $author_names = [];
        if (!empty($author_ids)) {
            $author_names_query = "SELECT author_name FROM lms_author WHERE author_id IN (" . implode(',', array_fill(0, count($author_ids), '?')) . ")";
            $author_names_stmt = $connect->prepare($author_names_query);
            
            foreach ($author_ids as $index => $id) {
                $author_names_stmt->bindValue($index + 1, $id);
            }
            
            $author_names_stmt->execute();
            $author_names = $author_names_stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        $author_string = implode(', ', $author_names);

        $query = "
            INSERT INTO lms_book 
            (book_name, category_id, book_author, book_location_rack, book_isbn_number, book_no_of_copy, 
            book_status, book_img, book_description, book_edition, book_publisher, book_published, book_added_on, book_updated_on) 
            VALUES (:name, :category_id, :author, :rack, :isbn, :no_of_copies, :status, :book_img, :description, :edition, :publisher, :published, :added_on, 
            :updated_on )
        ";
        
        $statement = $connect->prepare($query);
        $statement->execute([
            ':name' => $name,
            ':category_id' => $category_id,
            ':author' => $author_string,
            ':rack' => $rack,
            ':isbn' => $isbn,
            ':no_of_copies' => $no_of_copies,
            ':status' => $status,        
            ':book_img' => $book_img,
            ':description' => $description,
            ':edition' => $edition,
            ':publisher' => $publisher,
            ':published' => $published,
            ':added_on' => $date_now,   
            ':updated_on' => $date_now        
        ]);
        
        // Get the new book's ID
        $book_id = $connect->lastInsertId();
        
        // Insert author relationships
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
            
        header('location:book.php?msg=add');
        exit;
    } 

    // EDIT Book
    if (isset($_POST['edit_book'])) {
        $id = $_POST['book_id'];
        $name = $_POST['book_name'];
        $category_id = $_POST['category_id'];
        $author_ids = $_POST['author_ids'];
        $rack = $_POST['book_location_rack'];
        $isbn = $_POST['book_isbn_number'];
        $no_of_copies = $_POST['book_no_of_copy'];
        $status = $_POST['book_status'];
        $description = trim($_POST['book_description']);
        $edition = trim($_POST['book_edition'] ?? '');
        $publisher = trim($_POST['book_publisher'] ?? '');
        $published = isset($_POST['book_published']) ? trim($_POST['book_published']) : null;
        
        // Get current book data including ISBN
        $query = "SELECT * FROM lms_book WHERE book_id = :id";
        $statement = $connect->prepare($query);
        $statement->execute([':id' => $id]);
        $current_book = $statement->fetch(PDO::FETCH_ASSOC);
        $current_isbn = $current_book['book_isbn_number'];
        $book_img = $current_book['book_img'];
        
        // Only check for ISBN conflicts if ISBN is actually changing
        if ($isbn !== $current_isbn) {
            $check_query = "
                SELECT COUNT(*) 
                FROM lms_book 
                WHERE book_isbn_number = :isbn 
                AND book_id != :id
            ";
            
            $statement = $connect->prepare($check_query);
            $statement->execute([
                ':isbn' => $isbn,
                ':id' => $id
            ]);
            $count = $statement->fetchColumn();
            
            if ($count > 0) {
                // ISBN already exists in another book
                header('location:book.php?action=edit&code='.$id.'&error=duplicate_isbn');
                exit;
            }
        }
        
        // Handle image upload
        if (isset($_FILES['book_img']) && $_FILES['book_img']['error'] == 0) {
            $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png");
            $filename = $_FILES['book_img']['name'];
            $filetype = $_FILES['book_img']['type'];
            $filesize = $_FILES['book_img']['size'];
            
            // Verify file extension
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if (array_key_exists($ext, $allowed)) {
                // Check file size - 5MB maximum
                $maxsize = 5 * 1024 * 1024;
                if ($filesize < $maxsize) {
                    // Generate unique filename
                    $new_filename = 'book_' . time() . '.' . $ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['book_img']['tmp_name'], $upload_path)) {
                        // Delete old image if it's not the default
                        if ($book_img != "book_placeholder.png" && file_exists($upload_dir . $book_img)) {
                            unlink($upload_dir . $book_img);
                        }
                        $book_img = $new_filename;
                    }
                }
            }
        }

        // Get author names for the book_author field
        $author_names = [];
        if (!empty($author_ids)) {
            $author_names_query = "SELECT author_name FROM lms_author WHERE author_id IN (" . implode(',', array_fill(0, count($author_ids), '?')) . ")";
            $author_names_stmt = $connect->prepare($author_names_query);
            
            foreach ($author_ids as $index => $id_value) {
                $author_names_stmt->bindValue($index + 1, $id_value);
            }
            
            $author_names_stmt->execute();
            $author_names = $author_names_stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        $author_string = implode(', ', $author_names);
        

        // Build update query without potentially problematic fields
        $update_query = "
            UPDATE lms_book 
            SET book_name = :name,
                category_id = :category_id,
                book_author = :author,
                book_location_rack = :rack, 
                book_no_of_copy = :no_of_copies,
                book_status = :status,            
                book_img = :book_img,
                book_description = :description,
                book_edition = :edition,
                book_publisher = :publisher,
                book_published = :published,
                book_updated_on = :updated_on
        ";
        
        // Only include ISBN in the update if it has changed
        if ($isbn !== $current_isbn) {
            $update_query .= ", book_isbn_number = :isbn";
        }
        
        $update_query .= " WHERE book_id = :id";

        $date_now = get_date_time($connect);
        
        $params = [
            ':name' => $name,
            ':category_id' => $category_id,
            ':author' => $author_string, // This is the key part - storing the author string
            ':rack' => $rack,
            ':no_of_copies' => $no_of_copies,
            ':status' => $status,
            ':book_img' => $book_img,        
            ':description' => $description,
            ':edition' => $edition,
            ':publisher' => $publisher,
            ':published' => $published,
            ':updated_on' => $date_now,
            ':id' => $id
        ];
        
        // Only add ISBN parameter if ISBN is changing
        if ($isbn !== $current_isbn) {
            $params[':isbn'] = $isbn;
        }

        try {
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
            
            header('location:book.php?msg=edit');
            exit;
        } catch (PDOException $e) {
            // Log the error
            error_log('Database error in book.php: ' . $e->getMessage());
            
            // Provide user-friendly error message
            header('location:book.php?action=edit&code='.$id.'&error=db_error&message=' . urlencode($e->getMessage()));
            exit;
        }
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



?>

<div class="container-fluid px-4">
<div class="d-flex justify-content-between align-items-center my-4">
        <h1 class="">Book Management</h1>
    </div>
       
    <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
        <!-- Add Book Form with enhanced design -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Add New Book</h5>
            </div>
            <div class="card-body">

                <?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
                    <div class="alert alert-danger alert-dismissible fade show" id="error-alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        The book title or ISBN number already exists. Please try again with a different one.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Book Title <span class="text-danger">*</span></label>
                                <input type="text" name="book_name" class="form-control" required>
                                <div class="invalid-feedback">Please enter a book title</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Author Dropdown (Only enabled authors) -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Author(s) <span class="text-danger">*</span></label>
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
                                        <div class="invalid-feedback">Please select at least one author</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <!-- Category Dropdown (Only enabled categories) -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
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
                                        <div class="invalid-feedback">Please select a category</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Rack Dropdown (Only enabled racks) -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Location Rack <span class="text-danger">*</span></label>
                                        <select name="book_location_rack" class="form-control select2-rack" required>
                                            <option value="">Select Rack</option>
                                            <?php 
                                            $query = "SELECT location_rack_id, location_rack_name FROM lms_location_rack WHERE location_rack_status = 'Enable' ORDER BY location_rack_name ASC";
                                            $statement = $connect->prepare($query);
                                            $statement->execute();
                                            $racks = $statement->fetchAll(PDO::FETCH_ASSOC);

                                            foreach ($racks as $rack):
                                                $id = isset($rack['location_rack_id']) ? htmlspecialchars($rack['location_rack_id']) : '';
                                                $name = isset($rack['location_rack_name']) ? htmlspecialchars($rack['location_rack_name']) : '';
                                            ?>
                                                <option value="<?= $name ?>"><?= $name ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a rack location</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">ISBN Number</label>
                                        <input type="text" name="book_isbn_number" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">No. of Copy <span class="text-danger">*</span></label>
                                        <input type="number" name="book_no_of_copy" class="form-control" required min="1">
                                        <div class="invalid-feedback">Please enter the number of copies</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Status</label>
                                        <select name="book_status" class="form-select">
                                            <option value="Enable">Active</option>
                                            <option value="Disable">Not Active</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Published</label>
                                        <input type="date" name="book_published" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Book Description</label>
                                <textarea name="book_description" class="form-control" rows="4" placeholder="Enter book description here..."></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Book Cover Image</label>
                                <div class="card">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <img src="../asset/img/book_placeholder.png" id="book-cover-preview" class="img-fluid mb-2" style="max-height: 200px; width: auto;">
                                        </div>
                                        <input type="file" name="book_img" id="book-cover-input" class="form-control" accept="image/jpeg, image/png, image/jpg">
                                        <small class="form-text text-muted">Upload JPG, JPEG or PNG. Max 5MB.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Book Edition</label>
                                            <input type="text" name="book_edition" class="form-control" placeholder="e.g., 2nd Edition">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Publisher</label>
                                            <input type="text" name="book_publisher" class="form-control" placeholder="Publisher name">
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" name="add_book" class="btn btn-success me-2">
                            <i class="bi bi-plus-circle me-1"></i> Add Book
                        </button>
                        <a href="book.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                    </div>
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

        <?php if (isset($_GET['error']) && $_GET['error'] == 'duplicate_isbn'): ?>
            <div class="alert alert-danger alert-dismissible fade show" id="error-alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                The ISBN number already exists in another book. Please use a different ISBN.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] == 'db_error'): ?>
            <div class="alert alert-danger alert-dismissible fade show" id="error-alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Database error occurred. 
                <?php if (isset($_GET['message'])): ?>
                    <br><small>Details: <?= htmlspecialchars($_GET['message']) ?></small>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Book</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Book Title <span class="text-danger">*</span></label>
                                <input type="text" name="book_name" class="form-control" value="<?= htmlspecialchars($book['book_name']) ?>" required>
                                <div class="invalid-feedback">Please enter a book title</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Author(s) <span class="text-danger">*</span></label>
                                        <select name="author_ids[]" class="form-control select2-author" multiple required>
                                            <?php 
                                            // Get current authors for this book
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
                                        <div class="invalid-feedback">Please select at least one author</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                                        <select name="category_id" class="form-control select2-category" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['category_id'] ?>" <?= $category['category_id'] == $book['category_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category['category_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a category</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Rack Location <span class="text-danger">*</span></label>
                                        <select name="book_location_rack" class="form-control select2-rack" required>
                                            <option value="">Select Rack</option>
                                            <?php 
                                            $query = "SELECT location_rack_id, location_rack_name FROM lms_location_rack WHERE location_rack_status = 'Enable' ORDER BY location_rack_name ASC";
                                            $statement = $connect->prepare($query);
                                            $statement->execute();
                                            $racks = $statement->fetchAll(PDO::FETCH_ASSOC);

                                            foreach ($racks as $rack):
                                                $id = isset($rack['location_rack_id']) ? htmlspecialchars($rack['location_rack_id']) : '';
                                                $name = isset($rack['location_rack_name']) ? htmlspecialchars($rack['location_rack_name']) : '';
                                                $selected = ($book['book_location_rack'] === $name) ? 'selected' : '';
                                            ?>
                                                <option value="<?= $name ?>"<?= $selected ?>><?= $name ?></option>
                                            <?php endforeach; ?>
                                            
                                        </select>
                                        <div class="invalid-feedback">Please select a rack location</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">ISBN Number</label>
                                        <input type="text" name="book_isbn_number" class="form-control" value="<?= htmlspecialchars($book['book_isbn_number']) ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">No. of Copy <span class="text-danger">*</span></label>
                                        <input type="number" name="book_no_of_copy" class="form-control" value="<?= htmlspecialchars($book['book_no_of_copy']) ?>" required min="1">
                                        <div class="invalid-feedback">Please enter the number of copies</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Status</label>
                                        <select name="book_status" class="form-select">
                                            <option value="Enable" <?= $book['book_status'] == 'Enable' ? 'selected' : '' ?>>Active</option>
                                            <option value="Disable" <?= $book['book_status'] == 'Disable' ? 'selected' : '' ?>>Not Active</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Published</label>
                                        <input type="date" name="book_published" class="form-control" value="<?= htmlspecialchars($book['book_published'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Book Description</label>
                                <textarea name="book_description" class="form-control" rows="4" placeholder="Enter book description here..."><?= htmlspecialchars($book['book_description'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Book Cover Image</label>
                                <div class="card">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <img src="<?= !empty($book['book_img']) ? '../upload/'. htmlspecialchars($book['book_img']) : '../asset/img/book_placeholder.png' ?>" 
                                            id="book-cover-preview" class="img-fluid mb-2" style="max-height: 200px; width: auto;">
                                        </div>
                                        <input type="file" name="book_img" id="book-cover-input" class="form-control" accept="image/jpeg, image/png, image/jpg">
                                        <small class="form-text text-muted">Upload JPG, JPEG or PNG. Max 5MB.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Book Edition</label>
                                            <input type="text" name="book_edition" class="form-control" value="<?= htmlspecialchars($book['book_edition'] ?? '') ?>" placeholder="e.g., 2nd Edition">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Publisher</label>
                                            <input type="text" name="book_publisher" class="form-control" value="<?= htmlspecialchars($book['book_publisher'] ?? '') ?>" placeholder="Publisher name">
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" name="edit_book" class="btn btn-primary me-2">
                            <i class="bi bi-save me-1"></i> Update Book
                        </button>
                        <a href="book.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

    <?php else:
            echo '<div class="alert alert-danger">Book not found!</div>';
        endif;
    ?>
    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])):
            $id = $_GET['code'];
            // View book details with enhanced query to include description and image
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
                <div class="card shadow">
                <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-book me-2"></i>Book Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Book Image Column -->
                            <div class="col-md-3 text-center mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-body p-2">
                                        <img src="<?= !empty($book['book_img']) ? '../upload/'. htmlspecialchars($book['book_img']) : '../asset/img/book_placeholder.png' ?>" id="book-cover-preview" class="img-fluid mb-2" style="max-height: 200px; width: auto;">
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="book.php?action=edit&code=<?= $book['book_id'] ?>" class="btn btn-primary btn-sm me-2">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <a href="book.php" class="btn btn-secondary btn-sm">
                                        <i class="bi bi-arrow-left"></i> Back
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Book Details Column -->
                            <div class="col-md-9">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($book['book_name']); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p class="mb-2">
                                                    <span class="fw-bold text-secondary"><i class="bi bi-person me-2"></i>Author(s):</span><br>
                                                    <?php
                                                    $authors = explode(', ', $book['authors']);
                                                    foreach ($authors as $author) {
                                                        echo '<span class="badge bg-primary me-1 mb-1">' . htmlspecialchars($author) . '</span>';
                                                    }
                                                    ?>
                                                </p>
                                                
                                                <p class="mb-2">
                                                    <span class="fw-bold text-secondary"><i class="bi bi-tags me-2"></i>Category:</span><br>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($book['category_name']); ?></span>
                                                </p>
                                                <p class="mb-2">
                                                    <span class="fw-bold text-secondary"><i class="bi bi-upc-scan me-2"></i>ISBN:</span><br>
                                                    <?= !empty($book['book_isbn_number']) ? htmlspecialchars($book['book_isbn_number']) : '<em class="text-muted">Not specified</em>'; ?>
                                                </p>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <p class="mb-2">
                                                    <span class="fw-bold text-secondary"><i class="bi bi-geo-alt me-2"></i>Location:</span><br>
                                                    Rack <?= htmlspecialchars($book['book_location_rack']); ?>
                                                </p>
                                                
                                                <p class="mb-2">
                                                    <span class="fw-bold text-secondary"><i class="bi bi-stack me-2"></i>Copies Available:</span><br>
                                                    <span class="badge bg-<?= $book['book_no_of_copy'] > 0 ? 'success' : 'danger' ?>">
                                                        <?= htmlspecialchars($book['book_no_of_copy']); ?>
                                                    </span>
                                                </p>
                                                
                                                <p class="mb-2">
                                                    <span class="fw-bold text-secondary"><i class="bi bi-toggle-on me-2"></i>Status:</span><br>
                                                    <span class="badge bg-<?= $book['book_status'] == 'Enable' ? 'success' : 'danger' ?>">
                                                        <?= $book['book_status'] == 'Enable' ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="row mb-2">
                                                    <div class="fw-bold text-secondary"><i class="bi bi-book me-1"></i>Edition:</div>
                                                    <div class=""><?= !empty($book['book_edition']) ? htmlspecialchars($book['book_edition']) : 'N/A' ?></div>
                                                </div>
                                                <div class="row mb-2">
                                                    <div class="fw-bold text-secondary"><i class="bi bi-building me-1"></i>Publisher:</div>
                                                    <div class=""><?= !empty($book['book_publisher']) ? htmlspecialchars($book['book_publisher']) : 'N/A' ?></div>
                                                </div>
                                                <div class="row mb-2">
                                                    <div class="fw-bold text-secondary"><i class="bi bi-calendar me-1"></i>Published:</div>
                                                    <div class="">
                                                        <?php 
                                                        if (!empty($book['book_published'])) {
                                                            echo date('M d, Y', strtotime($book['book_published']));
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Book Description Card -->
                                <div class="card shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-file-text me-2"></i>Book Description</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($book['book_description'])): ?>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($book['book_description'])); ?></p>
                                        <?php else: ?>
                                            <p class="text-muted fst-italic mb-0">No description available for this book.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Metadata card -->
                                <div class="card shadow-sm mt-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Additional Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-1 small">
                                                    <span class="text-secondary">Added on:</span>
                                                    <?= date('M d, Y H:i:s', strtotime($book['book_added_on'])); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-1 small">
                                                    <span class="text-secondary">Last updated:</span>
                                                    <?= date('M d, Y H:i:s', strtotime($book['book_updated_on'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Book not found!
                </div>
                <a href="book.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Books
                </a>
            <?php endif;?>

    <?php else: ?>
        <!-- This is the default page that lists all books - You can enhance this part too -->
        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] == 'add'): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Book added successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['msg'] == 'edit'): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Book updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['msg'] == 'delete'): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Book deleted successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

	<!-- Book List -->

    <div class="card shadow-sm border-0">
			<div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Book List</h5>                      
                    <?php if (!isset($_GET['action'])): ?>            
                        <a href="book.php?action=add" class="btn btn-sm btn-success">
                            <i class="fas fa-plus-circle me-2"></i>Add New Book
                        </a>
                    <?php endif; ?>
                </div>

            </div>
            <div class="card-body">
                <div class="table-responsive">
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

                                        <td><?= date('M d, Y H:i:s', strtotime($row['book_added_on'])) ?></td>
                                        <td><?= date('M d, Y H:i:s', strtotime($row['book_updated_on'])) ?></td>

                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="book.php?action=view&code=<?= $row['book_id'] ?>" class="btn btn-info btn-sm mb-1">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="book.php?action=edit&code=<?= $row['book_id'] ?>" class="btn btn-primary btn-sm mb-1">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <?php if ($row['book_status'] === 'Enable'): ?>
                                                    <button type="button" class="btn btn-danger btn-sm delete-btn mb-1" 
                                                        data-id="<?= $row['book_id'] ?>" 
                                                        data-status="<?= $row['book_status'] ?>" 
                                                        title="Disable">
                                                        <i class="fa fa-ban"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-success btn-sm delete-btn mb-1" 
                                                        data-id="<?= $row['book_id'] ?>" 
                                                        data-status="<?= $row['book_status'] ?>" 
                                                        title="Enable">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="11" class="text-center">No books found!</td></tr>
                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php endif; ?>

</main>

<script>
//For deleting alert
// Fix your JavaScript code like this:
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            // Change librarianId to bookId to match the variable used in the redirect
            const bookId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const action = (currentStatus === 'Enable') ? 'disable' : 'enable';
            
            Swal.fire({
                title: `Are you sure you want to ${action} this book?`,
                text: "This action can be reverted later.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: `Yes, ${action} it!`,
                timer: 2000
            }).then((result) => {
                if (result.isConfirmed) {
                    // This line remains the same
                    window.location.href = `book.php?action=delete&status=${action === 'disable' ? 'Disable' : 'Enable'}&code=${bookId}`;
                }
            });
        });
    });
});

$(document).ready(function () {
    const table = $('#dataTable').DataTable({
        responsive: true,
        scrollX: true,
        scrollY: '500px',
        scrollCollapse: true,
        autoWidth: false,
        fixedHeader: true,
        stateSave: true,
        paging: true,
        info: true,
        searching: true,
        order: [[0, 'asc']],
        language: {
            emptyTable: "No books found in the table."
        },

        columnDefs: [
            { responsivePriority: 1, targets: [0, 1, 2, 3, 6, 7, 10]}, // Book ID, Title, Actions
            { responsivePriority: 2, targets: [4, 5, 8, 9] } 
        ],

        drawCallback: function () {
            setTimeout(() => {
                table.columns.adjust().responsive.recalc();
            }, 100);
        }
    });

    // Adjust columns on window resize
    $(window).on('resize', function () {
        table.columns.adjust().responsive.recalc();
    });

    // Final adjustment after full load/render
    setTimeout(() => {
        table.columns.adjust().responsive.recalc();
    }, 300);
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


<script>
    // Preview image before upload
    document.getElementById('book-cover-input').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('book-cover-preview').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Form validation
    (function () {
        'use strict'
        const forms = document.querySelectorAll('.needs-validation')
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })();
    
</script>

<?php 

include '../footer.php';

?>
