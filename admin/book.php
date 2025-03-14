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
if (isset($_POST["add_book"])) {
    $formdata = array();

    // Book Name
    if (empty($_POST["book_name"])) {
        $error .= '<li>Book Name is required</li>';
    } else {
        $formdata['book_name'] = trim($_POST["book_name"]);
    }

    // Book Category
    if (empty($_POST["book_category"])) {
        $error .= '<li>Book Category is required</li>';
    } else {
        $formdata['book_category'] = trim($_POST["book_category"]);
    }

    // Book Author
    if (empty($_POST["book_author"])) {
        $error .= '<li>Book Author is required</li>';
    } else {
        $formdata['book_author'] = trim($_POST["book_author"]);
    }

    // Book Location Rack
    if (empty($_POST["book_location_rack"])) {
        $error .= '<li>Book Location Rack is required</li>';
    } else {
        $formdata['book_location_rack'] = trim($_POST["book_location_rack"]);
    }

    // Book ISBN Number
    if (empty($_POST["book_isbn_number"])) {
        $error .= '<li>Book ISBN Number is required</li>';
    } else {
        $formdata['book_isbn_number'] = trim($_POST["book_isbn_number"]);
    }

    // Book No. of Copy
    if (empty($_POST["book_no_of_copy"])) {
        $error .= '<li>Book No. of Copy is required</li>';
    } else {
        $formdata['book_no_of_copy'] = trim($_POST["book_no_of_copy"]);
    }

    if ($error == '') {
        // Insert book data
        $data = array(
            ':book_category'        => $formdata['book_category'],
            ':book_title'           => $formdata['book_name'],
            ':book_author'          => $formdata['book_author'],
            ':book_rack'            => $formdata['book_location_rack'],
            ':book_isbn_number'     => $formdata['book_isbn_number'],
            ':book_no_of_copy'      => $formdata['book_no_of_copy'],
            ':book_status'          => 'Available',
            ':book_added_on'        => get_date_time($connect),
            ':book_updated_on'      => get_date_time($connect)
        );

        $query = "
            INSERT INTO lms_book 
            (book_category, book_title, book_author, book_rack, book_isbn_number, book_no_of_copy, book_status, book_added_on, book_updated_on) 
            VALUES 
            (:book_category, :book_title, :book_author, :book_rack, :book_isbn_number, :book_no_of_copy, :book_status, :book_added_on, :book_updated_on)
        ";

        $statement = $connect->prepare($query);
        $statement->execute($data);

        header('location:book.php?msg=add');
        exit();
    }
}

// DELETE/STATUS UPDATE
if (isset($_GET["action"], $_GET["code"], $_GET["status"]) && $_GET["action"] == 'delete') {
    $book_id = convert_data($_GET["code"], 'decrypt');
    $status = $_GET["status"];

    $data = array(
        ':book_status'      => $status,
        ':book_updated_on'  => get_date_time($connect),
        ':book_id'          => $book_id
    );

    $query = "
        UPDATE lms_book 
        SET book_status = :book_status, 
            book_updated_on = :book_updated_on 
        WHERE book_id = :book_id
    ";

    $statement = $connect->prepare($query);
    $statement->execute($data);

    header('location:book.php?msg=' . strtolower($status));
    exit();
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
            <div class="card-header"><i class="fas fa-book"></i> Add New Book</div>
            <div class="card-body">
                <form method="post">
                    <?php include 'book_form_fields.php'; ?>
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
                    <div class="col-md-6 text-end">
                        <a href="book.php?action=add" class="btn btn-success btn-sm">Add</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table id="dataTable" class="table table-bordered table-striped display responsive nowrap" style="width:100%">
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
                                    <td><?= htmlspecialchars($row["book_title"]); ?></td>
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
