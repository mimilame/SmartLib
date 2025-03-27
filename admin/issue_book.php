<?php

// issue_book.php

include '../database_connection.php';
include '../function.php';
include '../header.php';

// Check if admin is logged in
if (!is_admin_login()) {
    header('location:../admin_login.php');
    exit;
}

$message = '';

// Fetch all books for dropdown lists
$book_query = "SELECT * FROM lms_book ORDER BY book_name ASC";
$book_statement = $connect->prepare($book_query);
$book_statement->execute();
$books = $book_statement->fetchAll(PDO::FETCH_ASSOC);

// Fetch all students for dropdown lists
$user_query = "SELECT * FROM lms_user ORDER BY user_name ASC";
$user_statement = $connect->prepare($user_query);
$user_statement->execute();
$users = $user_statement->fetchAll(PDO::FETCH_ASSOC);

// DELETE (Disable/Enable)
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {
    $issue_book_id = $_GET["code"];
    $status = $_GET["status"];

    $data = [
        ':issue_book_status' => $status,
        ':issue_book_id'     => $issue_book_id
    ];

    $query = "
        UPDATE lms_issue_book 
        SET issue_book_status = :issue_book_status 
        WHERE issue_book_id = :issue_book_id
    ";

    $statement = $connect->prepare($query);
    $statement->execute($data);

    header('location:issue_book.php?msg=' . strtolower($status));
    exit;
}

// Issue Book
if (isset($_POST['issue_book'])) {
    $book_id = $_POST['book_id'];
    $user_id = $_POST['user_id'];
    $issue_date = $_POST['issue_date'];
    $expected_return_date = $_POST['expected_return_date'];
    $status = $_POST['issue_book_status'];

    $date_now = get_date_time($connect);

    try {
        // Start transaction to ensure data integrity
        $connect->beginTransaction();

        // Check if the book is available
        $query = "SELECT book_no_of_copy FROM lms_book WHERE book_id = :book_id FOR UPDATE";
        $statement = $connect->prepare($query);
        $statement->execute([':book_id' => $book_id]);
        $book = $statement->fetch(PDO::FETCH_ASSOC);

        if ($book && $book['book_no_of_copy'] > 0) {
            // Insert issue record
            $query = "
                INSERT INTO lms_issue_book 
                (book_id, user_id, issue_date, expected_return_date, return_date, issue_book_status, issued_on, issue_updated_on) 
                VALUES (:book_id, :user_id, :issue_date, :expected_return_date, :return_date, :status, :issued_on, :issue_updated_on)
            ";

            $statement = $connect->prepare($query);
            $return_date = NULL; // Add this before executing the statement

$statement->execute([
    ':book_id' => $book_id,
    ':user_id' => $user_id,
    ':issue_date' => $issue_date,
    ':expected_return_date' => $expected_return_date,
    ':return_date' => $return_date, // Now it's defined
    ':status' => $status,
    ':issued_on' => $date_now,
    ':issue_updated_on' => $date_now
]);


            // Decrease book copy count
            $query = "UPDATE lms_book SET book_no_of_copy = book_no_of_copy - 1 WHERE book_id = :book_id";
            $statement = $connect->prepare($query);
            $statement->execute([':book_id' => $book_id]);

            // Commit transaction
            $connect->commit();

            // Redirect to issue book page with success message
            header('Location: issue_book.php?msg=issued_successfully');
            exit;
        } else {
            // Rollback transaction if no stock
            $connect->rollBack();
            header('Location: issue_book.php?msg=out_of_stock');
            exit;
        }
    } catch (Exception $e) {
        // Rollback in case of any error
        $connect->rollBack();
        header('Location: issue_book.php?msg=error');
        exit;
    }
}


// EDIT Issued Book
if (isset($_POST['edit_issue_book'])) {
    $issue_book_id = $_POST['issue_book_id'];
    $book_id = $_POST['book_id'];
    $user_id = $_POST['user_id'];
    $issue_date = $_POST['issue_date'];
    $expected_return_date = $_POST['expected_return_date'];
    $return_date = $_POST['return_date'];
    $issue_book_status = $_POST['issue_book_status'];

    $update_query = "
        UPDATE lms_issue_book 
        SET book_id = :book_id,
            user_id = :user_id,
            issue_date = :issue_date,
            expected_return_date = :expected_return_date,
            return_date = :return_date,
            issue_book_status = :issue_book_status
        WHERE issue_book_id = :issue_book_id
    ";

    $params = [
        ':book_id' => $book_id,
        ':user_id' => $user_id,
        ':issue_date' => $issue_date,
        ':expected_return_date' => $expected_return_date,
        ':return_date' => $return_date,
        ':issue_book_status' => $issue_book_status,
        ':issue_book_id' => $issue_book_id
    ];

    $statement = $connect->prepare($update_query);
    $statement->execute($params);

    header('location:issue_book.php?msg=edit');
    exit;
}

// List all issued books with book names and user names
$query = "
    SELECT ib.*, b.book_name, u.user_name
    FROM lms_issue_book ib
    LEFT JOIN lms_book b ON ib.book_id = b.book_id
    LEFT JOIN lms_user u ON ib.user_id = u.user_id
    ORDER BY ib.issue_book_id ASC
";

$statement = $connect->prepare($query);
$statement->execute();
$issued_books = $statement->fetchAll(PDO::FETCH_ASSOC);


// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action === 'issue' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $book_id = $_POST['book_id'];
        $user_id = $_POST['user_id'];
        $issue_date = date('Y-m-d H:i:s');
        $expected_return_date = date('Y-m-d H:i:s');
        
        $query = "INSERT INTO lms_issue_book (book_id, user_id, issue_date, expected_return_date, issue_book_status, issued_on, issue_updated_on) 
                  VALUES (:book_id, :user_id, :issue_date, :expected_return_date, :issue_book_status, :issued_on, :issue_updated_on)";
        $stmt = $connect->prepare($query);
        $stmt->execute(['book_id' => $book_id, 'user_id' => $user_id, 'issue_date' => $issue_date, 'expected_return_date' => $expected_return_date]);
        
        header('Location: issue_book.php?msg=issue');
        exit;
    }
    
    if ($action === 'return' && isset($_GET['code'])) {
        $issue_book_id = $_GET['code'];
        $return_date = date('Y-m-d H:i:s');
    
        $query = "UPDATE lms_issue_book SET return_date = :return_date, issue_book_status = 'Returned' WHERE issue_book_id = :issue_book_id";
        $stmt = $connect->prepare($query);
        $stmt->execute(['return_date' => $return_date, 'issue_book_id' => $issue_book_id]);
    
        header('Location: issue_book.php?msg=return');
        exit;
    }
    
    
    if ($action === 'delete' && isset($_GET['code'])) {
        $issue_book_id = $_GET['code'];
    
        // Instead of deleting, update status if soft delete is required
        $query = "UPDATE lms_issue_book SET issue_book_status = 'Deleted' WHERE issue_book_id = :issue_book_id";
        
        $stmt = $connect->prepare($query);
        $stmt->execute([':issue_book_id' => $issue_book_id]);
    
        header('Location: issue_book.php?msg=delete');
        exit;
    }
    
}


$query = "SELECT lms_issue_book.issue_book_id, lms_book.book_name, lms_user.user_name, 
                 lms_issue_book.issue_date, lms_issue_book.expected_return_date, 
                 lms_issue_book.return_date, lms_issue_book.issue_book_status,
                 lms_issue_book.issued_on, lms_issue_book.issue_updated_on  
          FROM lms_issue_book 
          INNER JOIN lms_book ON lms_issue_book.book_id = lms_book.book_id 
          INNER JOIN lms_user ON lms_issue_book.user_id = lms_user.user_id
          ORDER BY lms_issue_book.issue_book_id DESC";

$statement = $connect->prepare($query);
$statement->execute();
$issue_book = $statement->fetchAll(PDO::FETCH_ASSOC);

?>

<main class="container py-4" style="min-height: 700px;">
    <h1 class="my-3">Issue Book Management</h1>

    <?php if (isset($_GET["msg"])): ?>
        <script>
    document.addEventListener('DOMContentLoaded', function () {
        <?php if (isset($_GET["msg"])): ?>
            <?php if ($_GET["msg"] == 'issue'): ?>
                Swal.fire('Book Issued', 'The book has been successfully issued.', 'success');
            <?php elseif ($_GET["msg"] == 'return'): ?>
                Swal.fire('Book Returned', 'The book has been successfully returned.', 'success');
            <?php elseif ($_GET["msg"] == 'delete'): ?>
                Swal.fire('Record Deleted', 'The issued book record has been disabled.', 'success');
            <?php endif; ?>

            if (window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('msg');
                window.history.replaceState({}, document.title, url.pathname + url.search);
            }
        <?php endif; ?>
    });
</script>
    <?php endif; ?>

    <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
    <!-- Add Issue Book Form -->
    <div class="card">
        <div class="card-header"><h5>Issue a Book</h5></div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label>Select Book</label>
                    <select name="book_id" class="form-control select2-book" required>
                        <option value="">Select Book</option>
                        <?php foreach ($books as $book): ?>
                            <option value="<?= $book['book_id'] ?>"><?= htmlspecialchars($book['book_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label>Select User</label>
                    <select name="user_id" class="form-control select2-user" required>
                        <option value="">Select User</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['user_id'] ?>"><?= htmlspecialchars($user['user_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div class="mb-3">
                    <label>Issue Date</label>
                    <input type="date" name="issue_date" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Expected Return Date</label>
                    <input type="date" name="expected_return_date" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Status</label>
                    <select name="issue_book_status" class="form-select">
                        <option value="Issued">Issued</option>
                        <option value="Returned">Returned</option>
                        <option value="Overdue">Overdue</option>
                    </select>
                </div>

                <input type="submit" name="issue_book" class="btn btn-success" value="Issue Book">
                <a href="issue_book.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

     <!-- Edit Issue Book Form -->

    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])): ?>

<?php
    $id = $_GET['code'];
    $query = "SELECT ib.*, b.book_name, u.user_name 
              FROM lms_issue_book ib
              LEFT JOIN lms_book b ON ib.book_id = b.book_id
              LEFT JOIN lms_user u ON ib.user_id = u.user_id
              WHERE ib.issue_book_id = :id LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->execute([':id' => $id]);
    $issue = $statement->fetch(PDO::FETCH_ASSOC);
    if ($issue):
?>

<div class="card">
    <div class="card-header"><h5>Edit Issued Book</h5></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="issue_book_id" value="<?= $issue['issue_book_id'] ?>">

            <div class="mb-3">
                <label>Book Name</label>
                <select name="book_id" class="form-control select2-book" required>
                    <option value="">Select Book</option>
                    <?php foreach ($books as $book): ?>
                        <option value="<?= $book['book_id'] ?>" <?= $book['book_id'] == $issue['book_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($book['book_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>User</label>
                <select name="user_id" class="form-control select2-user" required>
                    <option value="">Select User</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['user_id'] ?>" <?= $user['user_id'] == $issue['user_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['user_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>Issue Date</label>
                <input type="date" name="issue_date" class="form-control" value="<?= $issue['issue_date'] ?>" required>
            </div>

            <div class="mb-3">
                <label>Expected Return Date</label>
                <input type="date" name="expected_return_date" class="form-control" value="<?= $issue['expected_return_date'] ?>" required>
            </div>

            <div class="mb-3">
                <label>Return Date</label>
                <input type="date" name="return_date" class="form-control" value="<?= $issue['return_date'] ?>" required>
            </div>

            <div class="mb-3">
                <label>Status</label>
                <select name="issue_book_status" class="form-control" required>
                    <option value="Issued" <?= $issue['issue_book_status'] == 'Issued' ? 'selected' : '' ?>>Issued</option>
                    <option value="Returned" <?= $issue['issue_book_status'] == 'Returned' ? 'selected' : '' ?>>Returned</option>
                    <option value="Overdue" <?= $issue['issue_book_status'] == 'Overdue' ? 'selected' : '' ?>>Overdue</option>
                </select>
            </div>

            <button type="submit" name="edit_issue_book" class="btn btn-primary">Update Issue</button>
            <a href="issue_book.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php
    else:
        echo '<div class="alert alert-danger">Issue record not found!</div>';
    endif;


    elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])):
    $id = $_GET['code'];
    $query = "
        SELECT ib.*, b.book_name, u.user_name, u.user_type 
        FROM lms_issue_book ib
        LEFT JOIN lms_book b ON ib.book_id = b.book_id
        LEFT JOIN lms_user u ON ib.user_id = u.user_id
        WHERE ib.issue_book_id = :id LIMIT 1
    ";
    $statement = $connect->prepare($query);
    $statement->execute([':id' => $id]);
    $issue = $statement->fetch(PDO::FETCH_ASSOC);

    if ($issue): ?>
        <div class="card">
            <div class="card-header"><h5>View Issued Book</h5></div>
            <div class="card-body">
                <p><strong>Book Name:</strong> <?= htmlspecialchars($issue['book_name']); ?></p>
                <p><strong>Issued To:</strong> <?= htmlspecialchars($issue['user_name']); ?> (<?= htmlspecialchars($issue['user_type']); ?>)</p>
                <p><strong>Issue Date:</strong> <?= htmlspecialchars($issue['issue_date']); ?></p>
                <p><strong>Expected Return Date:</strong> <?= htmlspecialchars($issue['expected_return_date']); ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($issue['issue_book_status']); ?></p>
                <a href="issue_book.php" class="btn btn-secondary">Back</a>
            </div>
        </div>
    <?php else: ?>
            <div class="alert alert-danger">Issue record not found!</div>;
    <?php endif;
   
   
    else: ?>


<!-- Issue Book List -->
<div class="card mb-4">
    <div class="card-header">
        <div class="row">
            <div class="col-md-6">
                <i class="fas fa-table me-1"></i> Issue Book Management
            </div>
            <div class="col-md-6">
                <a href="issue_book.php?action=add" class="btn btn-success btn-sm float-end">Issue New Book</a>
            </div>
        </div>
    </div>
    <div class="card-body" style="overflow-x: auto;">
        <table id="dataTable" class="display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>Issue ID</th>
                    <th>Book Name</th>
                    <th>Student Name</th>
                    <th>Issue Date</th>
                    <th>Expected Return Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                    <th>Issued On</th>
                    <th>Updated On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($issue_book)): ?>
                    <?php foreach ($issue_book as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['issue_book_id']) ?></td>
                            <td><?= htmlspecialchars($row['book_name']) ?></td>
                            <td><?= htmlspecialchars($row['user_name']) ?></td>
                            <td><?= htmlspecialchars($row['issue_date']) ?></td>
                            <td><?= htmlspecialchars($row['expected_return_date']) ?></td>
                            <td><?= htmlspecialchars($row['return_date']) ?></td>
                            <td>
    <?php
        $currentDate = date('Y-m-d'); // Get today's date
        $expectedReturnDate = $row['expected_return_date']; 
        $returnDate = $row['return_date']; 
        
        if (!empty($returnDate)) {
            echo '<span class="badge bg-success">Returned</span>'; // Book is returned
        } elseif ($currentDate > $expectedReturnDate) {
            echo '<span class="badge bg-danger">Overdue</span>'; // Book is overdue
        } else {
            echo '<span class="badge bg-warning">Issued</span>'; // Book is still issued
        }
    ?>
</td>
                            
                            <td><?= date('Y-m-d H:i:s', strtotime($row['issued_on'])) ?></td>
							<td><?= date('Y-m-d H:i:s', strtotime($row['issue_updated_on'])) ?></td>

                            <td class="text-center">
                                <a href="issue_book.php?action=view&code=<?= $row['issue_book_id'] ?>" class="btn btn-info btn-sm mb-1">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="issue_book.php?action=edit&code=<?= $row['issue_book_id'] ?>" class="btn btn-primary btn-sm mb-1">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-danger btn-sm delete-btn"
                                        data-id="<?= $row['issue_book_id'] ?>">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No issued books found!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
</main>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete button event listener
    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        const issueId = $(this).attr('data-id');

        Swal.fire({
            title: `Are you sure you want to delete this record?`,
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: `Yes, delete it!`
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `issue_book.php?action=delete&code=${issueId}`;
            }
        });
    });

    // Initialize DataTable
    $('#dataTable').DataTable({
        responsive: true,
        scrollX: true,
        scrollY: '400px',
        scrollCollapse: true,
        autoWidth: false,
        paging: true,
        order: [[0, 'asc']],
        columnDefs: [
            { responsivePriority: 1, targets: 0 },
            { responsivePriority: 2, targets: 1 },
            { responsivePriority: 3, targets: 2 },
            { responsivePriority: 4, targets: 3 }
        ],
        language: { emptyTable: "No issued books found." }
    });

    // Initialize Select2 for better dropdown selection
    $(document).ready(function() {
        $('.select2-user').select2({
            placeholder: "Select a User",
            allowClear: true
        });

        $('.select2-book').select2({
            placeholder: "Select a Book",
            allowClear: true
        });
    });
});
</script>

<script>
document.getElementById('expected_days').addEventListener('input', function () {
    let days = parseInt(this.value);
    if (days > 0) {
        let today = new Date();
        today.setDate(today.getDate() + days); // Add number of days to today's date
        document.getElementById('expected_return_date').value = today.toISOString().split('T')[0];
    } else {
        document.getElementById('expected_return_date').value = ''; // Clear if input is invalid
    }
});
</script>





































































































