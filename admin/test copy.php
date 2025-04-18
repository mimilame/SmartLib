<?php

    // test%20copy.php

    include '../database_connection.php';
    include '../function.php';
    include '../header.php';
    authenticate_admin();

    if (isset($_POST['edit_return_book'])) {
        $issue_book_id = $_POST['issue_book_id'];
        $user_id = $_POST['user_id'];
        $book_id = $_POST['book_id'];
        $issue_date = $_POST['issue_date'];
        $expected_return_date = $_POST['expected_return_date'];
        $issue_book_status = 'Returned'; // Force status to Returned
        
        // Get condition directly from the dropdown
        $book_condition = $_POST['condition_type'];
        
        $return_date = date('Y-m-d'); // Set to today's date
        $date_now = get_date_time($connect);

        // Update the return record
        $update_query = "
            UPDATE lms_issue_book 
            SET user_id = :user_id,
                book_id = :book_id,
                issue_date = :issue_date,
                expected_return_date = :expected_return_date,
                return_date = :return_date,
                issue_book_status = :issue_book_status,
                book_condition = :book_condition,
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
            ':book_condition' => $book_condition,
            ':issue_updated_on' => $date_now,
            ':issue_book_id' => $issue_book_id
        ];

        $statement = $connect->prepare($update_query);
        $statement->execute($params);

        // Check if book is returned late and insert/update fine
        $days_late = (strtotime($return_date) - strtotime($expected_return_date)) / (60 * 60 * 24);

        if ($days_late > 0) {
            $fine_per_day = 5;
            $fines_amount = $days_late * $fine_per_day;

            // Check for existing fine
            $check_query = "
                SELECT fines_id FROM lms_fines 
                WHERE issue_book_id = :issue_book_id AND user_id = :user_id
            ";
            $statement = $connect->prepare($check_query);
            $statement->execute([':issue_book_id' => $issue_book_id, ':user_id' => $user_id]);
            $existing_fine = $statement->fetch(PDO::FETCH_ASSOC);

            if ($existing_fine) {
                // Update existing fine
                $update_query = "
                    UPDATE lms_fines 
                    SET fines_amount = :fines_amount,
                        days_late = :days_late,
                        fines_status = 'Unpaid',
                        fines_updated_on = NOW()
                    WHERE fines_id = :fines_id
                ";
                $statement = $connect->prepare($update_query);
                $statement->execute([
                    ':fines_amount' => $fines_amount,
                    ':days_late' => $days_late,
                    ':fines_id' => $existing_fine['fines_id']
                ]);
            } else {
                // Insert new fine
                $insert_query = "
                    INSERT INTO lms_fines (user_id, issue_book_id, expected_return_date, return_date, days_late, fines_amount, fines_status, fines_created_on)
                    VALUES (:user_id, :issue_book_id, :expected_return_date, :return_date, :days_late, :fines_amount, 'Unpaid', NOW())
                ";
                $statement = $connect->prepare($insert_query);
                $statement->execute([
                    ':user_id' => $user_id,
                    ':issue_book_id' => $issue_book_id,
                    ':expected_return_date' => $expected_return_date,
                    ':return_date' => $return_date,
                    ':days_late' => $days_late,
                    ':fines_amount' => $fines_amount
                ]);
            }
        }

        // Add back one copy to the book inventory
        $update_book_query = "
            UPDATE lms_book 
            SET book_no_of_copy = book_no_of_copy + 1 
            WHERE book_id = :book_id
        ";
        $statement = $connect->prepare($update_book_query);
        $statement->execute([':book_id' => $book_id]);

        // Redirect after success
        header('location:test%20copy.php?msg=updated');
        exit;
    }

    // Initialize search parameters
    $search_by = $_GET['search_by'] ?? 'book_id';
    $search_value = $_GET['search_value'] ?? '';
    $filter_condition = $_GET['book_condition'] ?? '';

    // Base query
    $query = "
        SELECT 
            ib.issue_book_id, 
            b.book_name,
            u.user_name, 
            ib.issue_date, 
            ib.return_date, 
            ib.issue_book_status,
            ib.book_condition
        FROM lms_issue_book AS ib
        LEFT JOIN lms_book AS b ON ib.book_id = b.book_id 
        LEFT JOIN lms_user AS u ON ib.user_id = u.user_id
        WHERE ib.issue_book_status = 'Returned'
    ";

    // Add condition filter if selected
    if (!empty($filter_condition)) {
        $query .= " AND ib.book_condition = :book_condition";
    }

    // Add search condition if provided
    if (!empty($search_value)) {
        switch ($search_by) {
            case 'book_id':
                $query .= " AND b.book_id LIKE :search_value";
                break;
            case 'book_name':
                $query .= " AND b.book_name LIKE :search_value";
                break;
            case 'user_name':
                $query .= " AND u.user_name LIKE :search_value";
                break;
            case 'issue_book_id':
                $query .= " AND ib.issue_book_id LIKE :search_value";
                break;
        }
    }

    // Add ordering - always display the newest returns first
    $query .= " ORDER BY ib.return_date DESC";

    $statement = $connect->prepare($query);

    // Bind search value if needed
    if (!empty($search_value)) {
        $statement->bindValue(':search_value', '%' . $search_value . '%', PDO::PARAM_STR);
    }

    // Bind condition filter if needed
    if (!empty($filter_condition)) {
        $statement->bindValue(':book_condition', $filter_condition, PDO::PARAM_STR);
    }

    $statement->execute();
    $returned_books = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Check for fines
    $fines_query = "
        SELECT issue_book_id, SUM(fines_amount) as fine_amount
        FROM lms_fines
        GROUP BY issue_book_id
    ";
    $fines_statement = $connect->prepare($fines_query);
    $fines_statement->execute();
    $fines = $fines_statement->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center my-4">
        <h1 class="">Return Book Management
        </h1>
    </div>

    <?php if (isset($_GET["msg"])): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_GET["msg"]) && $_GET["msg"] == 'updated'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Book Returned',
                    text: 'The book has been successfully returned.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
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

    <?php if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])): ?>   
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
            if ($issue):
        ?>

        <!-- Edit Return Book Form -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-edit me-2"></i>Process Book Return</h5>
                    <a href="test%20copy.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="issue_book_id" value="<?= $issue['issue_book_id'] ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <!-- Book Info -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Book</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-book text-primary"></i>
                                    </span>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($issue['book_name']) ?>" readonly>
                                </div>
                                <input type="hidden" name="book_id" value="<?= $issue['book_id'] ?>">
                            </div>

                            <!-- User -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Borrower</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-user text-primary"></i>
                                    </span>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($issue['user_name']) ?>" readonly>
                                </div>
                                <input type="hidden" name="user_id" value="<?= $issue['user_id'] ?>">
                            </div>

                            <!-- Issue Date -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Issue Date</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-calendar text-primary"></i>
                                    </span>
                                    <input type="date" name="issue_date" class="form-control" value="<?= $issue['issue_date'] ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Expected Return Date -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Expected Return Date</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-calendar-alt text-primary"></i>
                                    </span>
                                    <input type="date" name="expected_return_date" class="form-control" value="<?= $issue['expected_return_date'] ?>" readonly>
                                </div>
                            </div>

                            <!-- Return Date -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Return Date</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-calendar-check text-success"></i>
                                    </span>
                                    <input type="date" name="return_date" class="form-control" value="<?= date('Y-m-d') ?>" readonly>
                                </div>
                            </div>

                            <!-- Book Condition -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Book Condition</label>
                                <select name="condition_type" class="form-select">
                                    <option value="Good">Good</option>
                                    <option value="Damaged">Damaged</option>
                                    <option value="Missing Pages">Missing Pages</option>
                                    <option value="Water Damaged">Water Damaged</option>
                                    <option value="Binding Loose">Binding Loose</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-end mt-3">
                        <button type="submit" name="edit_return_book" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i>Complete Return
                        </button>
                        <a href="test%20copy.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php
            else:
                echo '<div class="alert alert-danger">Returned book record not found!</div>';
            endif;
        ?>

    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])): ?>
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

            // Check if there are fines for this issue
            $fine_query = "
                SELECT SUM(fines_amount) as total_fine, fines_status
                FROM lms_fines 
                WHERE issue_book_id = :issue_book_id
                GROUP BY fines_status
            ";
            $fine_statement = $connect->prepare($fine_query);
            $fine_statement->execute([':issue_book_id' => $id]);
            $fine_info = $fine_statement->fetch(PDO::FETCH_ASSOC);

            if ($issue): ?>
                <!-- View Return Details -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-eye me-2"></i>View Return Details</h5>
                            <a href="test%20copy.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="fw-bold text-muted">Book Name</h6>
                                    <p class="lead"><?= htmlspecialchars($issue['book_name']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <h6 class="fw-bold text-muted">Borrower Name</h6>
                                    <p class="lead"><?= htmlspecialchars($issue['user_name']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <h6 class="fw-bold text-muted">Issue Date</h6>
                                    <p class="lead"><?= date('F d, Y', strtotime($issue['issue_date'])); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="fw-bold text-muted">Expected Return Date</h6>
                                    <p class="lead"><?= date('F d, Y', strtotime($issue['expected_return_date'])); ?></p>
                                </div>
                                <div class="mb-3">
                                    <h6 class="fw-bold text-muted">Return Date</h6>
                                    <p class="lead"><?= date('F d, Y', strtotime($issue['return_date'])); ?></p>
                                </div>
                                <div class="mb-3">
                                    <h6 class="fw-bold text-muted">Book Condition</h6>
                                    <p>
                                        <?php
                                        $condition = htmlspecialchars($issue['book_condition'] ?? 'Not specified');
                                        $condition_class = 'bg-success';
                                        if (in_array($condition, ['Damaged', 'Missing Pages', 'Water Damaged', 'Binding Loose'])) {
                                            $condition_class = 'bg-warning';
                                        } elseif ($condition === 'Lost') {
                                            $condition_class = 'bg-danger';
                                        }
                                        ?>
                                        <span class="badge <?= $condition_class ?> fs-6"><?= $condition ?></span>
                                    </p>
                                </div>
                                
                                <?php if ($fine_info): ?>
                                <div class="mb-3">
                                    <h6 class="fw-bold text-muted">Fine</h6>
                                    <p>
                                        <span class="badge bg-danger fs-6">₱<?= number_format($fine_info['total_fine'], 2); ?></span>
                                        <span class="ms-2 badge <?= $fine_info['fines_status'] === 'Paid' ? 'bg-success' : 'bg-warning' ?> fs-6">
                                            <?= htmlspecialchars($fine_info['fines_status']); ?>
                                        </span>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <a href="test%20copy.php?action=edit&code=<?= $issue['issue_book_id'] ?>" class="btn btn-primary me-2">
                                <i class="fas fa-edit me-1"></i>Edit
                            </a>
                            <a href="test%20copy.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back to List
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>Return record not found.
                    <a href="test%20copy.php" class="btn btn-sm btn-outline-secondary ms-3">Back to Return Books</a>
                </div>
            <?php endif; ?>

    <?php else: ?>

    <!-- Search Section -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="fas fa-search me-2"></i>Search Returned Books</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="search_by" class="form-label fw-bold">Search By</label>
                    <select name="search_by" id="search_by" class="form-select">
                        <option value="book_name" <?= $search_by == 'book_name' ? 'selected' : '' ?>>Book Name</option>
                        <option value="user_name" <?= $search_by == 'user_name' ? 'selected' : '' ?>>Borrower Name</option>
                        <option value="issue_book_id" <?= $search_by == 'issue_book_id' ? 'selected' : '' ?>>Issue ID</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="search_value" class="form-label fw-bold">Search Value</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-search text-primary"></i>
                        </span>
                        <input type="text" name="search_value" id="search_value" class="form-control" 
                               placeholder="Enter search value" value="<?= htmlspecialchars($search_value) ?>">
                    </div>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>Search
                    </button>
                </div>
                
                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-between mt-2">
                        <div>
                            <a href="test%20copy.php" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-1"></i>Reset
                            </a>
                        </div>
                        <div class="condition-filter-container d-flex align-items-center">
                            <label for="conditionFilter" class="me-3 mb-0">Filter by Condition:</label>
                            <select id="conditionFilter" class="form-select" style="width: auto;" onchange="window.location.href='?book_condition='+this.value">
                                <option value="">All Conditions</option>
                                <option value="Good" <?= $filter_condition === 'Good' ? 'selected' : '' ?>>Good</option>
                                <option value="Damaged" <?= $filter_condition === 'Damaged' ? 'selected' : '' ?>>Damaged</option>
                                <option value="Missing Pages" <?= $filter_condition === 'Missing Pages' ? 'selected' : '' ?>>Missing Pages</option>
                                <option value="Water Damaged" <?= $filter_condition === 'Water Damaged' ? 'selected' : '' ?>>Water Damaged</option>
                                <option value="Binding Loose" <?= $filter_condition === 'Binding Loose' ? 'selected' : '' ?>>Binding Loose</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Recent Returns Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-history me-2"></i>Recent Returns</h5>
                <span class="badge bg-primary"><?= count($returned_books) ?> Books</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTable" class="display nowrap">
                    <thead>
                        <tr>
                            <th>Issue ID</th>
                            <th>Book Name</th>
                            <th>Borrower Name</th>
                            <th>Issue Date</th>
                            <th>Return Date</th>
                            <th>Condition</th>
                            <th>Fine</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($returned_books)): ?>
                            <?php foreach ($returned_books as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['issue_book_id']) ?></td>
                                    <td>
                                        <span class="fw-bold"><?= htmlspecialchars($row['book_name']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['issue_date'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['return_date'])) ?></td>
                                    <td>
                                        <?php
                                            $condition = $row['book_condition'];
                                            $badge_class = 'bg-success';
                                            if (in_array($condition, ['Damaged', 'Missing Pages', 'Water Damaged', 'Binding Loose'])) {
                                                $badge_class = 'bg-warning text-dark';
                                            } elseif ($condition === 'Lost') {
                                                $badge_class = 'bg-danger';
                                            }
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($condition) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                            $fine_amount = isset($fines[$row['issue_book_id']]) ? $fines[$row['issue_book_id']] : 0;
                                            $fine_badge = $fine_amount > 0 ? 'bg-danger' : 'bg-success';
                                        ?>
                                        <span class="badge <?= $fine_badge ?>">₱<?= number_format($fine_amount, 2); ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="test%20copy.php?action=view&code=<?= $row['issue_book_id'] ?>" class="btn btn-info">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="test%20copy.php?action=edit&code=<?= $row['issue_book_id'] ?>" class="btn btn-primary">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-3">
                                    <div class="text-center p-4">
                                        <i class="fas fa-search fa-2x text-muted mb-3"></i>
                                        <h6>No returned books found</h6>
                                        <p class="text-muted">Try adjusting your search or filter to find what you're looking for.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

$(document).ready(function() {
    // Initialize DataTables with all settings
    const table = $('#dataTable').DataTable({
        responsive: true,
        columnDefs: [
            { responsivePriority: 1, targets: [0, 1, 7] },
            { responsivePriority: 2, targets: [2, 5, 6] }
        ],
        order: [[4, 'desc']],
        autoWidth: false,
        language: {
            emptyTable: "No returned books found"
        },
        scrollY: '500px',
        scrollX: true,
        scrollCollapse: true,
        paging: true,
        searching: false,
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


    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Handle condition type dropdown
    $('select[name="condition_type"]').on('change', function() {
        const condition = $(this).val();
        const damagedConditions = ['Damaged', 'Lost', 'Missing Pages', 'Water Damaged', 'Binding Loose'];
        
        if (damagedConditions.includes(condition)) {
            if (!confirm(`Are you sure this book is in ${condition} condition? Additional charges may apply.`)) {
                $(this).val('Good');
            }
        }
    });
});
</script>

<?php include '../footer.php'; ?>