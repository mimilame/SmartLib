<?php

// issue_book.php

include '../database_connection.php';
include '../function.php';
include '../header.php';



$connect = new PDO("mysql:host=localhost;dbname=lms", "root", "");
$connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch issued books data
$query = "SELECT lms_issue_book.issue_book_id, lms_book.book_name, lms_user.user_name, 
                 lms_issue_book.issue_date_time, lms_issue_book.return_date_time, lms_issue_book.issue_book_status 
          FROM lms_issue_book 
          INNER JOIN lms_book ON lms_issue_book.book_id = lms_book.book_id 
          INNER JOIN lms_user ON lms_issue_book.user_id = lms_user.user_id
          ORDER BY lms_issue_book.issue_book_id DESC";


$statement = $connect->prepare($query);
$statement->execute();
$issued_books = $statement->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container py-4" style="min-height: 700px;">
    <h1 class="my-3">Issue Book Management</h1>

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
                    <th>Return Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($issued_books)): ?>
                    <?php foreach ($issued_books as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['issue_book_id']) ?></td>
                            <td><?= htmlspecialchars($row['book_name']) ?></td>
                            <td><?= htmlspecialchars($row['user_name']) ?></td>
                            <td><?= htmlspecialchars($row['issue_date_time']) ?></td>
                            <td><?= htmlspecialchars($row['return_date_time']) ?></td>
                            <td>
                                <?= ($row['issue_book_status'] === 'Returned') 
                                    ? '<span class="badge bg-success">Returned</span>' 
                                     : '<span class="badge bg-warning">Issued</span>' ?>
                            </td>
                            <td class="text-center">
                                <a href="issue_book.php?action=view&code=<?= $row['issue_book_id'] ?>" class="btn btn-info btn-sm mb-1">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="issue_book.php?action=edit&code=<?= $row['issue_book_id'] ?>" class="btn btn-primary btn-sm mb-1">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-danger btn-sm delete-btn"
                                        data-id="<?= $row['issue_id'] ?>">
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
</script>


