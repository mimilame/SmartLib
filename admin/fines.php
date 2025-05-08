<?php
// fines.php

include '../database_connection.php';
include '../function.php';
include '../header.php';
authenticate_admin();
$message = '';


if (isset($_SESSION['fine_added']) && $_SESSION['fine_added'] === true) {
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
        title: 'Fine Added',
        text: 'The fine has been successfully added for the lost book!',
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'OK',
                timer: 2000
            });
        });
    </script>
    ";
    unset($_SESSION['fine_added']);
}

// MARK FINE AS PAID/UNPAID
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'update_fine_status') {
    $fines_id = $_GET["code"];
    $status = $_GET["status"]; // Expected values: 'Paid' or 'Unpaid'

    $data = array(
        ':fines_status' => $status,
        ':fines_id'     => $fines_id
    );

    $query = "
    UPDATE lms_fines 
    SET fines_status = :fines_status,
        fines_updated_on = NOW()
    WHERE fines_id = :fines_id";

    $statement = $connect->prepare($query);
    $statement->execute($data);

    header('location:fines.php?msg=' . strtolower($status));
    exit;
}

// Check for overdue books and automatically create fines
function check_and_create_overdue_fines($connect) {
    // Find books that are overdue but don't have fines records yet
    // This includes books that haven't been returned yet but have passed their expected return date
    $query = "
        SELECT 
            ib.issue_book_id,
            ib.user_id,
            ib.book_id,
            ib.expected_return_date,
            DATEDIFF(CURRENT_DATE, ib.expected_return_date) AS days_late,
            (SELECT fine_rate_per_day FROM lms_setting LIMIT 1) AS fine_rate_per_day
        FROM lms_issue_book AS ib
        LEFT JOIN lms_fines AS f ON ib.issue_book_id = f.issue_book_id
        WHERE 
            ib.expected_return_date < CURRENT_DATE
            AND f.issue_book_id IS NULL
            AND (ib.return_date IS NULL OR ib.issue_book_status = 'Overdue')
    ";
    
    $statement = $connect->prepare($query);
    $statement->execute();
    $overdue_books = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    $fines_created = 0;
    
    foreach ($overdue_books as $book) {
        // Calculate fine amount based on days late and daily rate
        $fine_amount = $book['days_late'] * $book['fine_rate_per_day'];
        
        // Insert new fine record
        $insert_query = "
            INSERT INTO lms_fines 
            (user_id, issue_book_id, fines_amount, fines_status, fines_created_on, fines_updated_on) 
            VALUES 
            (:user_id, :issue_book_id, :fines_amount, 'Unpaid', NOW(), NOW())
        ";
        
        $insert_statement = $connect->prepare($insert_query);
        $insert_statement->bindParam(':user_id', $book['user_id']);
        $insert_statement->bindParam(':issue_book_id', $book['issue_book_id']);
        $insert_statement->bindParam(':fines_amount', $fine_amount);
        
        if ($insert_statement->execute()) {
            $fines_created++;
            
            // Update the issue_book status to 'Overdue'
            $update_status_query = "
                UPDATE lms_issue_book
                SET issue_book_status = 'Overdue'
                WHERE issue_book_id = :issue_book_id
            ";
            
            $update_status_statement = $connect->prepare($update_status_query);
            $update_status_statement->bindParam(':issue_book_id', $book['issue_book_id']);
            $update_status_statement->execute();
        }
    }
    
    // Update existing overdue fines to increase them based on additional days late
    $update_query = "
        UPDATE lms_fines AS f
        JOIN lms_issue_book AS ib ON f.issue_book_id = ib.issue_book_id
        JOIN lms_setting AS s ON 1=1
        SET 
            f.fines_amount = DATEDIFF(CURRENT_DATE, ib.expected_return_date) * s.fine_rate_per_day,
            f.fines_updated_on = NOW()
        WHERE 
            ib.issue_book_status = 'Overdue'
            AND f.fines_status = 'Unpaid'
            AND ib.return_date IS NULL
    ";
    
    $update_statement = $connect->prepare($update_query);
    $update_statement->execute();
    
    return $fines_created;
}

// Run the function to check and create overdue fines
$new_fines_created = check_and_create_overdue_fines($connect);
if ($new_fines_created > 0) {
    $message = $new_fines_created . ' new overdue fine(s) have been automatically created.';
}

//Add Fines

if (isset($_POST['add_fine'])) {
    $user_id = trim($_POST['user_id']);
    $book_id = trim($_POST['book_id']);
    $amount = trim($_POST['fines_amount']);
    $status = "Unpaid"; // Default status when adding a fine
    $date_now = get_date_time($connect); // Use your existing function to get timestamp

    // Optional: prevent duplicate fine for same book + user
    $check_query = "
        SELECT COUNT(*) FROM lms_fines 
        WHERE user_id = :user_id AND book_id = :book_id
    ";
    $check_stmt = $connect->prepare($check_query);
    $check_stmt->execute([
        ':user_id' => $user_id,
        ':book_id' => $book_id
    ]);

    if ($check_stmt->fetchColumn() > 0) {
        header('location:add_fines.php?action=add&error=exists');
        exit;
    }

    // Insert fine
    $insert_query = "
        INSERT INTO lms_fines (user_id, book_id, fines_amount, fines_status, fines_created_on) 
        VALUES (:user_id, :book_id, :amount, :status, :created_on)
    ";
    $stmt = $connect->prepare($insert_query);
    $stmt->execute([
        ':user_id' => $user_id,
        ':book_id' => $book_id,
        ':amount' => $amount,
        ':status' => $status,
        ':created_on' => $date_now
    ]);

    header('location:fines.php?msg=add');
    exit;
}


// Fetch all fines with user and book details
$query = "
    SELECT 
        f.fines_id,
        u.user_name,                -- This is to get the user's name
        f.user_id,
        ib.issue_book_id,
        ib.expected_return_date,
        ib.return_date,
        ib.issue_book_status,
        DATEDIFF(COALESCE(ib.return_date, CURRENT_DATE), ib.expected_return_date) AS days_late,
        f.fines_amount,
        f.fines_status,
        f.fines_created_on,
        f.fines_updated_on
    FROM lms_fines AS f
    LEFT JOIN lms_issue_book AS ib 
        ON f.issue_book_id = ib.issue_book_id
    LEFT JOIN lms_user AS u 
        ON f.user_id = u.user_id   -- Join to get the user's name
    WHERE ib.issue_book_id IS NOT NULL
    ORDER BY f.fines_id ASC
";


$statement = $connect->prepare($query);
$statement->execute();
$fines = $statement->fetchAll(PDO::FETCH_ASSOC);

// Fetch lost books without fines for the modal
$lost_books_query = "
    SELECT 
        ib.issue_book_id,
        ib.user_id,
        u.user_name,
        b.book_name,
        ib.issue_date,
        ib.expected_return_date,
        ib.issue_book_status
    FROM lms_issue_book AS ib
    LEFT JOIN lms_fines AS f ON ib.issue_book_id = f.issue_book_id
    LEFT JOIN lms_user AS u ON ib.user_id = u.user_id
    LEFT JOIN lms_book AS b ON ib.book_id = b.book_id
    WHERE 
        ib.issue_book_status = 'Lost'
        AND f.issue_book_id IS NULL
    ORDER BY ib.issue_date DESC
";

$lost_books_statement = $connect->prepare($lost_books_query);
$lost_books_statement->execute();
$lost_books = $lost_books_statement->fetchAll(PDO::FETCH_ASSOC);


?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center my-4">
        <h1 class="">Fines Management</h1>
    </div>

<?php if (!empty($message)): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-counter bg-warning shadow-sm hover-shadow translate-hover rounded transition">
            <i class="bi bi-exclamation-circle float-start"></i>
            <span class="count-numbers">
            <?php echo get_currency_symbol($connect) . Count_total_fines_outstanding($connect); ?>
            </span>
            <span class="count-name">Total Outstanding</span>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-counter bg-danger shadow-sm hover-shadow translate-hover rounded transition">
            <i class="bi bi-cash-coin float-start"></i>
            <span class="count-numbers">
            <?php echo get_currency_symbol($connect) . Count_total_fines_received($connect); ?>
            </span>
            <span class="count-name">Collected This Month</span>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card card-counter bg-primary shadow-sm hover-shadow translate-hover rounded transition">
            <i class="bi bi-receipt float-start"></i>
            <span class="count-numbers">
            <?php echo Count_total_fines($connect); ?>
            </span>
            <span class="count-name">Total Fines Issued</span>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card card-counter bg-success shadow-sm hover-shadow translate-hover rounded transition">
            <i class="bi bi-wallet2 float-start"></i>
            <span class="count-numbers">
            <?php echo get_currency_symbol($connect) . Count_fines_paid_today($connect); ?>
            </span>
            <span class="count-name">Fines Paid Today</span>
        </div>
    </div>
</div>


<?php if (isset($_GET["msg"])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($_GET["msg"] == 'disable'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Fine Disabled',
                    text: 'The fine has been successfully disabled.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done',
                    timer: 2000
                });
            <?php elseif ($_GET["msg"] == 'enable'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Fine Enabled',
                    text: 'The fine has been successfully enabled.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done',
                    timer: 2000
                });
            <?php elseif ($_GET["msg"] == 'add'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Fine Added',
                    text: 'The fine was added successfully!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done',
                    timer: 2000
                });
            <?php elseif ($_GET["msg"] == 'edit'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Fine Updated',
                    text: 'The fine was updated successfully!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done',
                    timer: 2000
                });
            <?php elseif ($_GET["msg"] == 'delete'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Fine Deleted',
                    text: 'The fine was successfully deleted or marked as deleted.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done',
                    timer: 2000
                });
            <?php elseif ($_GET["msg"] == 'paid'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Fine Paid',
                    text: 'The fine was marked as paid successfully!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done',
                    timer: 2000
                });
            <?php elseif ($_GET["msg"] == 'unpaid'): ?>
                Swal.fire({
                    icon: 'info',
                    title: 'Marked as Unpaid',
                    text: 'The fine has been marked as unpaid.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done',
                    timer: 2000
                });
            <?php endif; ?>

            // Remove ?msg=... from the URL without reloading the page
            if (window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('msg');
                window.history.replaceState({}, document.title, url.pathname + url.search);
            }
        });
    </script>
<?php endif; ?>




<!-- Fines Management -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-table me-2"></i>Fines List</h5>                      
                    <?php if (!isset($_GET['action'])): ?>            
                        <a href="add_fines.php?action=add" class="btn btn-sm btn-success">
                            <i class="fas fa-plus-circle me-2"></i>Add Fines
                        </a>
                    <?php endif; ?>
                </div>

    </div>
    <div class="card-body">
    <div class="table-responsive">
        <table id="dataTable" class="display nowrap">
            <thead>
                <tr>
                    <th>Fine ID</th>
                    <th>Borrower Name</th>
                    <th>Issue Book ID</th>
                    <th>Status</th>
                    <th>Expected Return Date</th>
                    <th>Return Date</th>
                    <th>Days Late</th>
                    <th>Fines Amount</th>
                    <th>Payment Status</th>
                    <th>Created On</th>
                    <th>Updated On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($fines)): ?>
                    <?php foreach ($fines as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['fines_id']) ?></td>
                            <td><?= htmlspecialchars($row['user_name']) ?></td>
                            <td><?= htmlspecialchars($row['issue_book_id']) ?></td>
                            <td>
                                <?php
                                    if ($row['issue_book_status'] === 'Lost') {
                                        echo '<span class="badge bg-warning">Lost</span>';
                                    } elseif ($row['issue_book_status'] === 'Overdue') {
                                        echo '<span class="badge bg-danger">Overdue</span>';
                                    } else {
                                        echo '<span class="badge bg-success">' . htmlspecialchars($row['issue_book_status']) . '</span>';
                                    }
                                ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($row['expected_return_date'])) ?></td>
                            <!-- Return Date -->
                            <td>
                            <?php
    $status = $row['issue_book_status'];
    $return_date = $row['return_date'];

    if ($status === 'Lost' || $status === 'Overdue') {
        echo '<span class="badge bg-secondary">Not Returned</span>';
    } else if (!empty($return_date) && $return_date !== '0000-00-00') {
        echo date('M d, Y', strtotime($return_date));
    } else {
        echo '<span class="badge bg-secondary">Not Returned</span>';
    }
?>


</td>


<!-- Days Late -->
<td>
    <?php
        if (!is_null($row['days_late']) && $row['days_late'] > 0) {
            echo htmlspecialchars($row['days_late']) . ' day(s)';
        } else {
            echo '<span class="text-muted">None</span>';
        }
    ?>
</td>

                            <td><?= get_currency_symbol($connect) . htmlspecialchars($row['fines_amount']) ?></td>
                            <td>
                                <?php
                                    if ($row['fines_status'] === 'Paid') {
                                        echo '<span class="badge bg-success">Paid</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">Unpaid</span>';
                                    }
                                ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($row['fines_created_on'])) ?></td>
                            <td><?= date('M d, Y', strtotime($row['fines_updated_on'])) ?></td>
                            <td>
    <div class="btn-group btn-group-sm">
        <a href="fines_action.php?action=view&code=<?= $row['fines_id'] ?>" class="btn btn-info btn-sm mb-1">
            <i class="fa fa-eye"></i>
        </a>
        <a href="fines_action.php?action=edit&code=<?= $row['fines_id'] ?>" class="btn btn-primary btn-sm mb-1">
            <i class="fa fa-edit"></i>
        </a>
        <?php if ($row['fines_status'] !== 'Paid'): ?>
    <a href="fines.php?action=update_fine_status&status=Paid&code=<?= $row['fines_id'] ?>"
       class="btn btn-success btn-sm mb-1"
       title="Mark as Paid">
        <i class="fa fa-check-circle"></i>
    </a>
<?php else: ?>
    <a href="fines.php?action=update_fine_status&status=Unpaid&code=<?= $row['fines_id'] ?>"
       class="btn btn-danger btn-sm mb-1"
       title="Mark as Unpaid">
        <i class="fa fa-times"></i>
    </a>
<?php endif; ?>


    </div>
</td>

                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    $(document).ready(function() {    
        const table = $('#dataTable').DataTable({
            responsive: true,
            columnDefs: [
            { responsivePriority: 1, targets: [0, 1, 3, 7, 11] },
            { responsivePriority: 2, targets: [2, 8] }
            ],
            order: [[0, 'asc']],
            autoWidth: false,
            language: {
            emptyTable: "No fines available"
            },
            
            // Scroll and pagination settings
            info: true,
            paging: true, 
            scrollY: '500px',       // Vertical scroll
            scrollCollapse: true,   // Collapse height when less data
            searching: false,          // Enable pagination
            fixedHeader: true,
            stateSave: true,
            // Fix alignment issues on draw and responsive changes
            drawCallback: function() {
                setTimeout(() => table.columns.adjust().responsive.recalc(), 100);
            }
        });
        // Handle window resize to maintain column alignment
        $(window).on('resize', function() {
            table.columns.adjust().responsive.recalc();
        });
        
        // Force alignment after a short delay to ensure proper rendering
        setTimeout(() => table.columns.adjust().responsive.recalc(), 300);
    });
</script>

<script>
    // Lost books modal functionality
    $(document).ready(function() {
        // Search functionality for lost books table
        $("#lostBookSearch").on("keyup", function() {
            const value = $(this).val().toLowerCase();
            $("#lostBooksTable tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
        
        // Select lost book functionality
        $(".select-lost-book").click(function() {
            const issueId = $(this).data('issue-id');
            const userId = $(this).data('user-id');
            const userName = $(this).data('user-name');
            const bookName = $(this).data('book-name');
            
            $("#selected_issue_id").val(issueId);
            $("#selected_user_id").val(userId);
            $("#selected_user_name").val(userName);
            $("#selected_book_name").val(bookName);
            
            // Show the form
            $("#addLostFineForm").show();
            
            // Optionally scroll to the form
            $('html, body').animate({
                scrollTop: $("#addLostFineForm").offset().top - 100
            }, 200);
        });

        document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.pay-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const status = this.dataset.status;

            if (status === 'Unpaid') {
                // Add your AJAX or confirmation logic here
                console.log(`Marking fine ID ${id} as Paid`);
            }
        });
    });
});

    });
</script>

<script>
    // Remove query parameters from the URL (optional after showing alert)
    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('error');
        window.history.replaceState({}, document.title, url.pathname + url.search);
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const dataTable = document.getElementById('dataTable').getElementsByTagName('tbody')[0];

        // Function to filter rows based on search and status
        function filterTable() {
            const searchText = searchInput.value.toLowerCase();
            const selectedStatus = statusFilter.value;
            const rows = dataTable.getElementsByTagName('tr');

            for (let row of rows) {
                const userName = row.cells[1].textContent.toLowerCase();
                const issueId = row.cells[2].textContent.toLowerCase();
                const status = row.cells[8].textContent.trim();

                const matchesSearch = userName.includes(searchText) || issueId.includes(searchText);
                const matchesStatus = selectedStatus === 'All' || status === selectedStatus;

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        // Event listeners for filtering
        if (searchInput) {
            searchInput.addEventListener('keyup', filterTable);
        }
        if (statusFilter) {
            statusFilter.addEventListener('change', filterTable);
        }
    });
</script>


<?php
include '../footer.php';
?>
