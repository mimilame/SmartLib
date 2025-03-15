<?php
// book.php

include '../database_connection.php';
include '../function.php';

if (!is_admin_login()) {
    header('location:../admin_login.php');
    exit();
}

$message = '';
$error = '';

// ADD BOOK
// ADD BOOK
if (isset($_POST['add_book'])) {
    $formdata = array();
    $error = '';

    // Validate Title
    if (empty($_POST['book_title'])) {
        $error .= '<li>Book Title is required</li>';
    } else {
        $formdata['book_title'] = trim($_POST['book_title']);
    }

    // Validate ISBN
    if (empty($_POST['isbn'])) {
        $error .= '<li>ISBN is required</li>';
    } else {
        $formdata['isbn'] = trim($_POST['isbn']);
    }

    // Validate Edition
    if (empty($_POST['edition'])) {
        $error .= '<li>Edition is required</li>';
    } else {
        $formdata['edition'] = trim($_POST['edition']);
    }

    // Validate Quantity
    if (empty($_POST['quantity'])) {
        $error .= '<li>Quantity is required</li>';
    } else {
        $formdata['quantity'] = trim($_POST['quantity']);
    }

    // Validate Category ID
    if (empty($_POST['category_id'])) {
        $error .= '<li>Category is required</li>';
    } else {
        $formdata['category_id'] = trim($_POST['category_id']);
    }

    // Validate Rack ID
    if (empty($_POST['rack_id'])) {
        $error .= '<li>Rack is required</li>';
    } else {
        $formdata['rack_id'] = trim($_POST['rack_id']);
    }

    // Validate Author ID
    if (empty($_POST['author_id'])) {
        $error .= '<li>Author is required</li>';
    } else {
        $formdata['author_id'] = trim($_POST['author_id']);
    }

    // If no validation error, proceed
    if ($error == '') {

        // Check if ISBN already exists
        $query = "SELECT * FROM lms_book WHERE isbn = :isbn";
        $statement = $connect->prepare($query);
        $statement->execute([':isbn' => $formdata['isbn']]);

        if ($statement->rowCount() > 0) {
            $error = '<li>ISBN Already Exists</li>';
        } else {
            // Handle cover image upload (optional)
            $cover_image = '';
            if (!empty($_FILES['cover_image']['name'])) {
                $file_name = time() . '_' . $_FILES['cover_image']['name'];
                $file_tmp = $_FILES['cover_image']['tmp_name'];
                $file_path = '../uploads/' . $file_name; // Make sure this folder exists!
                
                if (move_uploaded_file($file_tmp, $file_path)) {
                    $cover_image = $file_name;
                } else {
                    $error .= '<li>Failed to upload cover image</li>';
                }
            }

            // If no upload error, insert into DB
            if ($error == '') {
                $data = array(
                    ':book_title'     => $formdata['book_title'],
                    ':isbn'           => $formdata['isbn'],
                    ':edition'        => $formdata['edition'],
                    ':quantity'       => $formdata['quantity'],
                    ':category_id'    => $formdata['category_id'],
                    ':rack_id'        => $formdata['rack_id'],
                    ':author_id'      => $formdata['author_id'],
                    ':cover_image'    => $cover_image,
                    ':status'         => 'Enable',
                    ':created_on'     => get_date_time($connect)
                );

                $query = "INSERT INTO lms_book 
                (book_title, isbn, edition, quantity, category_id, rack_id, author_id, cover_image, status, created_on)
                VALUES 
                (:book_title, :isbn, :edition, :quantity, :category_id, :rack_id, :author_id, :cover_image, :status, :created_on)";

                $statement = $connect->prepare($query);
                $statement->execute($data);

                header('location:book.php?msg=add');
                exit();
            }
        }
    }
}


// FETCH BOOKS LIST
$query = "SELECT * FROM lms_book ORDER BY book_id DESC";
$statement = $connect->prepare($query);
$statement->execute();
$books = $statement->fetchAll();

include '../header.php';
?>

<main class="container py-4" style="min-height: 700px;">
    <h1>Book Management</h1>

    <?php if (isset($_GET["msg"])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            if ($_GET["msg"] == "add") echo "Book added successfully!";
            if ($_GET["msg"] == "edit") echo "Book updated successfully!";
            if ($_GET["msg"] == "enable") echo "Book enabled successfully!";
            if ($_GET["msg"] == "disable") echo "Book disabled successfully!";
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET["action"]) && $_GET["action"] == 'add'): ?>

        <?php if ($error != ''): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="list-unstyled"><?= $error ?></ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
            <div class="row">
            <div class="col col-md-6"><i class="fas fa-book"></i> Add New Book</div>
            <div class="card-body">
                <form method="post">
                <div class="mb-3">
    <label class="form-label">Book Name</label>
    <input type="text" name="book_name" class="form-control" value="<?= isset($formdata['book_name']) ? htmlspecialchars($formdata['book_name']) : ''; ?>" />
</div>

<div class="mb-3">
    <label class="form-label">Book Category</label>
    <input type="text" name="book_category" class="form-control" value="<?= isset($formdata['book_category']) ? htmlspecialchars($formdata['book_category']) : ''; ?>" />
</div>

<div class="mb-3">
    <label class="form-label">Book Author</label>
    <input type="text" name="book_author" class="form-control" value="<?= isset($formdata['book_author']) ? htmlspecialchars($formdata['book_author']) : ''; ?>" />
</div>

<div class="mb-3">
    <label class="form-label">Book Location Rack</label>
    <input type="text" name="book_location_rack" class="form-control" value="<?= isset($formdata['book_location_rack']) ? htmlspecialchars($formdata['book_location_rack']) : ''; ?>" />
</div>

<div class="mb-3">
    <label class="form-label">Book ISBN Number</label>
    <input type="text" name="book_isbn_number" class="form-control" value="<?= isset($formdata['book_isbn_number']) ? htmlspecialchars($formdata['book_isbn_number']) : ''; ?>" />
</div>

<div class="mb-3">
    <label class="form-label">Book No. of Copy</label>
    <input type="number" name="book_no_of_copy" class="form-control" value="<?= isset($formdata['book_no_of_copy']) ? htmlspecialchars($formdata['book_no_of_copy']) : ''; ?>" />
</div>

                    <div class="mt-4 mb-3 text-center">
                        <input type="submit" name="add_book" class="btn btn-success" value="Add" />
                    </div>
                </form>
            </div>
        </div>

    <?php elseif (isset($_GET["action"]) && $_GET["action"] == 'edit'): ?>

        <?php
        $book_id = convert_data($_GET["code"], 'decrypt');
        $query = "SELECT * FROM lms_book WHERE book_id = :book_id";
        $statement = $connect->prepare($query);
        $statement->execute([':book_id' => $book_id]);
        $book_result = $statement->fetch(PDO::FETCH_ASSOC);
        ?>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-book"></i> Edit Book Details</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="book_id" value="<?= $book_result['book_id']; ?>">
                    <?php include 'book_form_fields.php'; ?>
                    <div class="mt-4 mb-3 text-center">
                        <input type="submit" name="edit_book" class="btn btn-primary" value="Edit" />
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>

        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <i class="fas fa-table me-1"></i> Book Management
                    </div>
                    <div class="col col-md-6">
                        <a href="book.php?action=add" class="btn btn-success btn-sm float-end">Add</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table id="dataTable" class="table table-bordered table-striped display responsive nowrap py-4 dataTable no-footer dtr-column collapsed table-active" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Book Name</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Rack</th>
                            <th>ISBN</th>
                            <th>No. of Copy</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($books) > 0): ?>
                            <?php foreach ($books as $row): ?>
                                <tr>
                                    <td><?= $row["book_id"]; ?></td>
                                    <td><?= htmlspecialchars($row["book_name"]); ?></td>
                                    <td><?= htmlspecialchars($row["book_author"]); ?></td>
                                    <td><?= htmlspecialchars($row["book_category"]); ?></td>
                                    <td><?= htmlspecialchars($row["book_rack"]); ?></td>
                                    <td><?= htmlspecialchars($row["book_isbn_number"]); ?></td>
                                    <td><?= htmlspecialchars($row["book_no_of_copy"]); ?></td>
                                    <td>
                                        <?= $row["book_status"] === "Available"
                                            ? '<span class="badge bg-success">Available</span>'
                                            : '<span class="badge bg-danger">Not Available</span>'; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="book.php?action=view&code=<?= convert_data($row["book_id"]); ?>" class="btn btn-info btn-sm mb-1">View</a>
                                        <a href="book.php?action=edit&code=<?= convert_data($row["book_id"]); ?>" class="btn btn-primary btn-sm mb-1">Edit</a>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="delete_data('<?= convert_data($row["book_id"]); ?>')">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No Data Found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>
</main>

<script>
function delete_data(code) {
    if (confirm("Are you sure you want to delete this book?")) {
        window.location.href = "book.php?action=delete&code=" + code + "&status=Delete";
    }
}

$(document).ready(function () {
    $('#dataTable').DataTable({
        responsive: true,
        columnDefs: [
            { responsivePriority: 1, targets: [0, 1, 8] },
            { responsivePriority: 2, targets: [2, 4] },
            { responsivePriority: 3, targets: [7] },
            { responsivePriority: 10000, targets: [3, 5, 6] }
        ],
        order: [[0, 'asc']],
        autoWidth: false,
        language: {
            emptyTable: "No books available"
        }
    });
});
</script>

<?php include '../footer.php'; ?>
