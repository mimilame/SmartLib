<?php

// issue_book.php

include '../database_connection.php';
include '../function.php';
include '../header.php';

$message = '';

// Fetch all book names for dropdown lists
$book_query = "SELECT book_id, book_name FROM lms_book ORDER BY book_name ASC";
$book_statement = $connect->prepare($book_query);
$book_statement->execute();
$books = $book_statement->fetchAll(PDO::FETCH_ASSOC);

// Fetch all user names for dropdown lists
$user_query = "SELECT user_id, user_name FROM lms_user ORDER BY user_name ASC";
$user_statement = $connect->prepare($user_query);
$user_statement->execute();
$users = $user_statement->fetchAll(PDO::FETCH_ASSOC);


// Mark as Lost (Instead of Delete)
if (isset($_GET["action"], $_GET['code']) && $_GET["action"] == 'delete') {
    $issue_book_id = $_GET["code"];
    $status = 'Lost'; // Always set status to 'Lost'

    $data = [
        ':issue_book_status' => $status,
        ':issue_book_id'     => $issue_book_id
    ];

    $query = "
        UPDATE lms_issue_book 
        SET issue_book_status = :issue_book_status, issue_updated_on = NOW()
        WHERE issue_book_id = :issue_book_id
    ";

    $statement = $connect->prepare($query);
    $statement->execute($data);

    header('location:issue_book.php?msg=lost');
    exit;
}



// ISSUE Book (Form Submit)
if (isset($_POST['issue_book'])) {
    $book_id = trim($_POST['book_id']);
    $user_id = trim($_POST['user_id']);
    $issue_date = trim($_POST['issue_date']);
    $expected_return_date = trim($_POST['expected_return_date']);
    $status = trim($_POST['issue_book_status']);

    // Check for duplicate entry
    $check_duplicate_query = "
        SELECT COUNT(*) as duplicate_count
        FROM lms_issue_book
        WHERE book_id = :book_id AND user_id = :user_id AND issue_book_status = 'Issued'
    ";

    $statement = $connect->prepare($check_duplicate_query);
    $statement->execute([
        ':book_id' => $book_id,
        ':user_id' => $user_id
    ]);

    $duplicate = $statement->fetch(PDO::FETCH_ASSOC);

    if ($duplicate['duplicate_count'] > 0) {
        // Duplicate found, redirect with error message
        header('location:issue_book.php?action=add&error=duplicate');
        exit;
    }

    // Check if the book has available copies
    $check_query = "
        SELECT book_no_of_copy 
        FROM lms_book 
        WHERE book_id = :book_id
    ";

    $statement = $connect->prepare($check_query);
    $statement->execute([':book_id' => $book_id]);
    $book = $statement->fetch(PDO::FETCH_ASSOC);

    if ($book['book_no_of_copy'] > 0) {
        // Book is available, proceed with issuing
        $date_now = get_date_time($connect);

        // Insert issue record
        $insert_query = "
            INSERT INTO lms_issue_book 
            (book_id, user_id, issue_date, expected_return_date, issue_book_status, issued_on, issue_updated_on) 
            VALUES (:book_id, :user_id, :issue_date, :expected_return_date, :issue_book_status, :issued_on, :updated_on)
        ";

        $statement = $connect->prepare($insert_query);
        $statement->execute([
            ':book_id' => $book_id,
            ':user_id' => $user_id,
            ':issue_date' => $issue_date,
            ':expected_return_date' => $expected_return_date,
            ':issue_book_status' => $status,
            ':issued_on' => $date_now,
            ':updated_on' => $date_now
        ]);

        // Decrease the number of copies
        $update_query = "
            UPDATE lms_book 
            SET book_no_of_copy = book_no_of_copy - 1, book_updated_on = :updated_on 
            WHERE book_id = :book_id
        ";

        $statement = $connect->prepare($update_query);
        $statement->execute([
            ':updated_on' => $date_now,
            ':book_id' => $book_id
        ]);

        header('location:issue_book.php?msg=issued');
        exit;
    } else {
        // Book not available
        header('location:issue_book.php?action=add&error=no_copy');
        exit;
    }
}




// EDIT Issue Book
if (isset($_POST['edit_issue_book'])) {
    $issue_book_id = $_POST['issue_book_id'];
    $user_id = $_POST['user_id'];
    $book_id = $_POST['book_id'];
    $issue_date = $_POST['issue_date'];
    $expected_return_date = $_POST['expected_return_date'];
    $return_date = $_POST['return_date'];
    $issue_book_status = $_POST['issue_book_status']; // New status input (Issued, Returned, Lost, Damaged)
    $date_now = get_date_time($connect);

    // Update the issue record
    $update_query = "
        UPDATE lms_issue_book 
        SET user_id = :user_id,
            book_id = :book_id,
            issue_date = :issue_date,
            expected_return_date = :expected_return_date,
            return_date = :return_date,
            issue_book_status = :issue_book_status,
            issue_updated_on = :issue_updated_on
        WHERE issue_book_id = :issue_book_id
    ";

    $params = [
        ':user_id' => $user_id,
        ':book_id' => $book_id,
        ':issue_date' => $issue_date,
        ':expected_return_date' => $expected_return_date,
        ':return_date' => $return_date,
        ':issue_book_status' => $issue_book_status,
        ':issue_updated_on' => $date_now,
        ':issue_book_id' => $issue_book_id
    ];

    $statement = $connect->prepare($update_query);
    $statement->execute($params);

    // Automatically update the number of copies in lms_book table if status is changed
    if ($issue_book_status === 'Returned') {
        $update_book_query = "
            UPDATE lms_book 
            SET book_no_of_copy = book_no_of_copy + 1 
            WHERE book_id = :book_id
        ";
        $statement = $connect->prepare($update_book_query);
        $statement->execute([':book_id' => $book_id]);
    } elseif ($issue_book_status === 'Issued') {
        $update_book_query = "
            UPDATE lms_book 
            SET book_no_of_copy = book_no_of_copy - 1 
            WHERE book_id = :book_id AND book_no_of_copy > 0
        ";
        $statement = $connect->prepare($update_book_query);
        $statement->execute([':book_id' => $book_id]);
    }

    header('location:issue_book.php?msg=edit');
    exit;
}

// Fetch all issued books with user and book details
$query = "
    SELECT 
        ib.issue_book_id, 
        b.book_name, 
        u.user_name, 
        ib.issue_date, 
        ib.expected_return_date, 
        ib.return_date, 
        ib.book_fines,
        ib.issue_book_status, 
        ib.issued_on, 
        ib.issue_updated_on 
    FROM lms_issue_book AS ib
    LEFT JOIN lms_book AS b ON ib.book_id = b.book_id 
    LEFT JOIN lms_user AS u ON ib.user_id = u.user_id
    ORDER BY ib.issue_book_id ASC
";

$statement = $connect->prepare($query);
$statement->execute();
$issue_book = $statement->fetchAll(PDO::FETCH_ASSOC);

?>

<main class="container py-4" style="min-height: 700px;">
    <h1 class="my-3">Issue Book Management</h1>


    <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
    <!-- Issue Book Form -->
    <div class="card">
        <div class="card-header"><h5>Issue Book</h5></div>
        <div class="card-body">

            <?php if (isset($_GET['error']) && $_GET['error'] == 'no_copy'): ?>
                <div class="alert alert-danger" id="error-alert">
                    The selected book is not available for issuing. No copies left.
                </div>
            <?php endif; ?>

            <form method="post">

                <!-- Book Dropdown (Only enabled books with copies available) -->
                <div class="mb-3">
                    <label>Select Book</label>
                    <select name="book_id" class="form-control select2-book" required>
                        <option value="">Select Book</option>
                        <?php 
                        $query = "SELECT book_id, book_name FROM lms_book WHERE book_status = 'Enable' AND book_no_of_copy > 0 ORDER BY book_name ASC";
                        $statement = $connect->prepare($query);
                        $statement->execute();
                        $books = $statement->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($books as $book): ?>
                            <option value="<?= htmlspecialchars($book['book_id']) ?>"><?= htmlspecialchars($book['book_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Member Dropdown (Only enabled members) -->
                <div class="mb-3">
                    <label>Select User</label>
                    <select name="user_id" class="form-control select2-user" required>
                        <option value="">Select Member</option>
                        <?php 
                        $query = "SELECT user_id, user_name FROM lms_user WHERE user_status = 'Enable' ORDER BY user_name ASC";
                        $statement = $connect->prepare($query);
                        $statement->execute();
                        $members = $statement->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($members as $member): ?>
                            <option value="<?= htmlspecialchars($member['user_id']) ?>"><?= htmlspecialchars($member['user_name']) ?></option>
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
                        <option value="Lost">Lost</option>
                    </select>
                </div>

                <input type="submit" name="issue_book" class="btn btn-success" value="Issue Book">
                <a href="issue_book.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>


    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])): ?>   

<?php
    $id = $_GET['code'];
    $query = "SELECT * FROM lms_issue_book WHERE issue_book_id = :id LIMIT 1";
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

            <!-- Book Dropdown -->
            <div class="mb-3">
                <label>Select Book</label>
                <select name="book_id" class="form-control select2-book" required>
                    <option value="">Select Book</option>
                    <?php 
                    $query = "SELECT book_id, book_name FROM lms_book WHERE book_status = 'Enable' ORDER BY book_name ASC";
                    $statement = $connect->prepare($query);
                    $statement->execute();
                    $books = $statement->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($books as $book): ?>
                        <option value="<?= $book['book_id'] ?>" <?= $book['book_id'] == $issue['book_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($book['book_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- User Dropdown -->
            <div class="mb-3">
                <label>Select User</label>
                <select name="user_id" class="form-control select2-member" required>
                    <option value="">Select User</option>
                    <?php 
                    $query = "SELECT user_id, user_name FROM lms_user WHERE user_status = 'Enable' ORDER BY user_name ASC";
                    $statement = $connect->prepare($query);
                    $statement->execute();
                    $members = $statement->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($members as $member): ?>
                        <option value="<?= $member['user_id'] ?>" <?= $member['user_id'] == $issue['user_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($member['user_name']) ?>
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
                <input type="date" name="return_date" class="form-control" value="<?= $issue['return_date'] ?>">
            </div>
            
            <div class="mb-3">
                <label>Status</label>
                <select name="issue_book_status" class="form-control" required>
                    <option value="Issued" <?= $issue['issue_book_status'] == 'Issued' ? 'selected' : '' ?>>Issued</option>
                    <option value="Returned" <?= $issue['issue_book_status'] == 'Returned' ? 'selected' : '' ?>>Returned</option>
                    <option value="Overdue" <?= $issue['issue_book_status'] == 'Overdue' ? 'selected' : '' ?>>Overdue</option>
                    <option value="Lost" <?= $issue['issue_book_status'] == 'Lost' ? 'selected' : '' ?>>Lost</option>
                </select>
            </div>
            
            <button type="submit" name="edit_issue_book" class="btn btn-primary">Update Issue</button>
            <a href="issue_book.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php
    else:
        echo '<div class="alert alert-danger">Issue not found!</div>';
    endif;


    elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])): ?>
        <?php
            $id = $_GET['code'];
            $query = "
                SELECT ib.*, b.book_name, u.user_name
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
                    <div class="card-header"><h5>View Issue Book</h5></div>
                    <div class="card-body">
                        <p><strong>Book Name:</strong> <?= htmlspecialchars($issue['book_name']); ?></p>
                        <p><strong>Issued To:</strong> <?= htmlspecialchars($issue['user_name']); ?></p>
                        <p><strong>Issue Date:</strong> <?= htmlspecialchars($issue['issue_date']); ?></p>
                        <p><strong>Expected Return Date:</strong> <?= htmlspecialchars($issue['expected_return_date']); ?></p>
                        <p><strong>Return Date:</strong> <?= htmlspecialchars($issue['return_date']); ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($issue['issue_book_status']); ?></p>
                        <a href="issue_book.php" class="btn btn-secondary">Back</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">Issue record not found!</div>
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
                    <th>User Name</th>
                    <th>Issue Date</th>
                    <th>Expected Return Date</th>
                    <th>Return Date</th>
                    <th>Book Fines</th>
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
                            <td><?= htmlspecialchars($row['book_fines']) ?></td>
                            <td>
                                <?php
                                    switch ($row['issue_book_status']) {
                                        case 'Issued':
                                            echo '<span class="badge bg-warning">Issued</span>';
                                            break;
                                        case 'Returned':
                                            echo '<span class="badge bg-success">Returned</span>';
                                            break;
                                        case 'Overdue':
                                            echo '<span class="badge bg-danger">Overdue</span>';
                                            break;
                                        case 'Lost':
                                            echo '<span class="badge bg-secondary">Lost</span>';
                                            break;
                                    }
                                ?>

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
                                                data-id="<?= $row['issue_book_id'] ?>"
                                                data-status="<?= $row['issue_book_status'] ?>">
                                            <i class="fa fa-trash"></i>
                                        </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="11" class="text-center">No issued books found!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
</main>

<!-- JavaScript -->
<script>
// For deleting alert
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const issueId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const action = (currentStatus === 'Enable') ? 'disable' : 'enable';

            Swal.fire({
                title: `Are you sure you want to ${action} this issue record?`,
                text: "This action can be reverted later.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: `Yes, ${action} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `issue_book.php?action=delete&status=${action === 'disable' ? 'Disable' : 'Enable'}&code=${issueId}`;
                }
            });
        });
    });
});

// DataTable Initialization
$(document).ready(function() {
  $('#dataTable').DataTable({
    responsive: true,
    scrollX: true,
    scrollY: '400px',
    scrollCollapse: true,
    autoWidth: false,
    info: true,
    paging: true,
    order: [[0, 'asc']],

    columnDefs: [
      { responsivePriority: 1, targets: 0 },
      { responsivePriority: 2, targets: 1 },
      { responsivePriority: 3, targets: 2 },
      { responsivePriority: 4, targets: 3 },
      { responsivePriority: 5, targets: 4 }
    ],

    language: {
      emptyTable: "No issue records found in the table."
    }
  });
});

// Select2 Initialization for Dropdowns
$(document).ready(function() {
  $('.select2-user').select2({
    placeholder: "Select User",
    allowClear: true
  });
  $('.select2-book').select2({
    placeholder: "Select Book",
    allowClear: true
  });
});

// Alert Box Handling
document.addEventListener('DOMContentLoaded', function() {
  const alertBox = document.getElementById('error-alert');

  if (alertBox) {
    setTimeout(function() {
      alertBox.style.transition = 'opacity 0.5s ease';
      alertBox.style.opacity = '0';

      setTimeout(function() {
        alertBox.remove();
      }, 500);

      if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('error');
        window.history.replaceState({}, document.title, url.pathname + url.search);
      }
    }, 3000);
  }
});
</script>

<?php if (isset($_GET["msg"])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let message = "";
            let title = "";
            let icon = "";

            switch ("<?= $_GET["msg"] ?>") {
                case "issued":
                    title = "Book Issued";
                    message = "The book has been successfully issued!";
                    icon = "success";
                    break;
                case "returned":
                    title = "Book Returned";
                    message = "The book has been successfully returned!";
                    icon = "success";
                    break;
                case "add":
                    title = "Record Added";
                    message = "The issue record was added successfully!";
                    icon = "success";
                    break;
                case "edit":
                    title = "Record Updated";
                    message = "The issue record was updated successfully!";
                    icon = "success";
                    break;
                case "lost":
                    title = "Book Marked as Lost";
                    message = "The book has been marked as lost!";
                    icon = "warning";
                    break;
                case "damaged":
                    title = "Book Marked as Damaged";
                    message = "The book has been marked as damaged!";
                    icon = "error";
                    break;
            }

            if (title && message && icon) {
                Swal.fire({
                    title: title,
                    text: message,
                    icon: icon,
                    confirmButtonText: 'OK'
                });
            }

            if (window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('msg');
                window.history.replaceState({}, document.title, url.pathname + url.search);
            }
        });
    </script>
<?php endif; ?>
