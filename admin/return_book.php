<?php

    // return_book.php

    include '../database_connection.php';
    include '../function.php';
    include '../header.php';
    authenticate_admin();

// Fetch lost books
$lost_books = [];

if (isset($_GET['tab']) && $_GET['tab'] === 'lost') {
    $query = "
        SELECT 
            ib.issue_book_id,
            ib.issue_date,
            ib.return_date,
            ib.user_id,
            ib.book_id,
            ib.issue_book_status,
            ib.expected_return_date,
            b.book_name AS book_name,
            b.book_isbn_number,
            u.user_name AS user_name
        FROM lms_issue_book ib
        JOIN lms_book b ON ib.book_id = b.book_id
        JOIN lms_user u ON ib.user_id = u.user_id
        WHERE ib.issue_book_status = 'Lost'
        ORDER BY ib.issue_book_id DESC
    ";

    $stmt = $connect->prepare($query);
    $stmt->execute();
    $lost_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    if (isset($_POST['edit_return_book'])) {
        $issue_book_id = $_POST['issue_book_id'];
        $user_id = $_POST['user_id'];
        $book_id = $_POST['book_id'];
        $issue_date = $_POST['issue_date'];
        $expected_return_date = $_POST['expected_return_date'];
        $issue_book_status = 'Returned';
        $remarks = $_POST['remarks'] ?? null;
        $book_condition = $_POST['condition_type'];
    
        $raw_return_date = date('Y-m-d H:i:s');
        $date_now = get_date_time($connect);
    
        // Fetch settings
        $settings_query = "SELECT library_hours, fine_rate_per_day, max_fine_per_book FROM lms_setting LIMIT 1";
        $settings_stmt = $connect->query($settings_query);
        $settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);
    
        $library_hours = json_decode($settings['library_hours'], true);
        $fine_per_day = $settings['fine_rate_per_day'] ?? 5;
        $max_fine = $settings['max_fine_per_book'] ?? 50;
    
        // Adjust return date based on library hours
        $adjusted_return_date = new DateTime($raw_return_date);
        $day_of_week = $adjusted_return_date->format('l');
        $return_time = $adjusted_return_date->format('H:i');
    
        $is_after_hours = false;
    
        if (
            !isset($library_hours[$day_of_week]) ||
            !$library_hours[$day_of_week]['open'] ||
            !$library_hours[$day_of_week]['close'] ||
            $return_time > $library_hours[$day_of_week]['close']
        ) {
            $is_after_hours = true;
        }
    
        // Move to next valid open day if after hours or closed
        if ($is_after_hours) {
            do {
                $adjusted_return_date->modify('+1 day');
                $day_of_week = $adjusted_return_date->format('l');
            } while (
                !isset($library_hours[$day_of_week]) ||
                !$library_hours[$day_of_week]['open'] ||
                !$library_hours[$day_of_week]['close']
            );
        }
    
        $final_return_date = $adjusted_return_date->format('Y-m-d H:i:s');
    
        // Update issue book record
        $update_query = "
            UPDATE lms_issue_book 
            SET user_id = :user_id,
                book_id = :book_id,
                issue_date = :issue_date,
                expected_return_date = :expected_return_date,
                return_date = :return_date,
                issue_book_status = :issue_book_status,
                book_condition = :book_condition,
                remarks = :remarks,
                issue_updated_on = :issue_updated_on
            WHERE issue_book_id = :issue_book_id
        ";
    
        $params = [
            ':user_id' => $user_id,
            ':book_id' => $book_id,
            ':issue_date' => $issue_date,
            ':expected_return_date' => $expected_return_date,
            ':return_date' => $final_return_date,
            ':issue_book_status' => $issue_book_status,
            ':book_condition' => $book_condition,
            'remarks' => $remarks,
            ':issue_updated_on' => $date_now,
            ':issue_book_id' => $issue_book_id
        ];
    
        $statement = $connect->prepare($update_query);
        $statement->execute($params);
    
        // Fine calculation
        $days_late = (strtotime($final_return_date) - strtotime($expected_return_date)) / (60 * 60 * 24);
        $days_late = ceil($days_late);
        if ($days_late === 0 && strtotime($final_return_date) > strtotime($expected_return_date)) {
            $days_late = 1;
        }
    
        if ($days_late > 0) {
            $fines_amount = min($days_late * $fine_per_day, $max_fine);
    
            $check_query = "
                SELECT fines_id FROM lms_fines 
                WHERE issue_book_id = :issue_book_id AND user_id = :user_id
            ";
            $statement = $connect->prepare($check_query);
            $statement->execute([':issue_book_id' => $issue_book_id, ':user_id' => $user_id]);
            $existing_fine = $statement->fetch(PDO::FETCH_ASSOC);
    
            if ($existing_fine) {
                $update_query = "
                    UPDATE lms_fines 
                    SET fines_amount = :fines_amount,
                        days_late = :days_late,
                        fines_status = 'Unpaid',
                        fines_updated_on = :updated_on
                    WHERE fines_id = :fines_id
                ";
                $statement = $connect->prepare($update_query);
                $statement->execute([
                    ':fines_amount' => $fines_amount,
                    ':days_late' => $days_late,
                    ':updated_on' => $date_now,
                    ':fines_id' => $existing_fine['fines_id']
                ]);
            } else {
                $insert_query = "
                    INSERT INTO lms_fines 
                        (user_id, issue_book_id, expected_return_date, return_date, days_late, fines_amount, fines_status, fines_created_on, fines_updated_on)
                    VALUES 
                        (:user_id, :issue_book_id, :expected_return_date, :return_date, :days_late, :fines_amount, 'Unpaid', :created_on, :updated_on)
                ";
                $statement = $connect->prepare($insert_query);
                $statement->execute([
                    ':user_id' => $user_id,
                    ':issue_book_id' => $issue_book_id,
                    ':expected_return_date' => $expected_return_date,
                    ':return_date' => $final_return_date,
                    ':days_late' => $days_late,
                    ':fines_amount' => $fines_amount,
                    ':created_on' => $date_now,
                    ':updated_on' => $date_now
                ]);
            }
        }
    
        // Check if the book is marked as lost
        if ($book_condition === 'Lost') {
            // Don't add back to inventory if lost
            // You might want to add code here to charge a replacement fee
        } else {
            // Add back one copy to inventory for non-lost books
            $update_book_query = "
                UPDATE lms_book 
                SET book_no_of_copy = book_no_of_copy + 1 
                WHERE book_id = :book_id
            ";
            $statement = $connect->prepare($update_book_query);
            $statement->execute([':book_id' => $book_id]);
        }
    
        header('location:return_book.php?msg=updated');
        exit;
    }
    
    // Handle marking a book as lost
    if (isset($_POST['mark_as_lost'])) {
        $issue_book_id = $_POST['issue_book_id'];
        $user_id = $_POST['user_id'];
        $book_id = $_POST['book_id'];
        $issue_date = $_POST['issue_date'];
        $expected_return_date = $_POST['expected_return_date'];
        $replacement_cost = $_POST['replacement_cost'] ?? 0;
        $remarks = $_POST['remarks'] ?? null;
        $date_now = get_date_time($connect);
        
        // Update issue book record
        $update_query = "
            UPDATE lms_issue_book 
            SET return_date = :return_date,
                issue_book_status = 'Lost',
                book_condition = 'Lost',
                remarks = :remarks,
                issue_updated_on = :issue_updated_on
            WHERE issue_book_id = :issue_book_id
        ";
        
        $params = [
            ':return_date' => $date_now,
            ':remarks' => $remarks,
            ':issue_updated_on' => $date_now,
            ':issue_book_id' => $issue_book_id
        ];
        
        $statement = $connect->prepare($update_query);
        $statement->execute($params);
        
        // Add a lost book fine/replacement cost
        $check_query = "
            SELECT fines_id FROM lms_fines 
            WHERE issue_book_id = :issue_book_id AND user_id = :user_id
        ";
        $statement = $connect->prepare($check_query);
        $statement->execute([':issue_book_id' => $issue_book_id, ':user_id' => $user_id]);
        $existing_fine = $statement->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_fine) {
            $update_query = "
                UPDATE lms_fines 
                SET fines_amount = :fines_amount,
                    fines_status = 'Unpaid',
                    fines_updated_on = :updated_on,
                    fine_type = 'Replacement'
                WHERE fines_id = :fines_id
            ";
            $statement = $connect->prepare($update_query);
            $statement->execute([
                ':fines_amount' => $replacement_cost,
                ':updated_on' => $date_now,
                ':fines_id' => $existing_fine['fines_id']
            ]);
        } else {
            $insert_query = "
                INSERT INTO lms_fines 
                    (user_id, issue_book_id, expected_return_date, return_date, fines_amount, fines_status, fine_type, fines_created_on, fines_updated_on)
                VALUES 
                    (:user_id, :issue_book_id, :expected_return_date, :return_date, :fines_amount, 'Unpaid', 'Replacement', :created_on, :updated_on)
            ";
            $statement = $connect->prepare($insert_query);
            $statement->execute([
                ':user_id' => $user_id,
                ':issue_book_id' => $issue_book_id,
                ':expected_return_date' => $expected_return_date,
                ':return_date' => $date_now,
                ':fines_amount' => $replacement_cost,
                ':created_on' => $date_now,
                ':updated_on' => $date_now
            ]);
        }
        
        header('location:return_book.php?tab=lost&msg=marked_lost');
        exit;
    }

    // Get current tab
    $current_tab = $_GET['tab'] ?? 'returned';

    // Initialize search parameters
    $search_by = $_GET['search_by'] ?? 'book_id';
    $search_value = $_GET['search_value'] ?? '';
    $filter_condition = $_GET['book_condition'] ?? '';

    // Query for returned books
    function getReturnedBooksQuery($connect, $search_by, $search_value, $filter_condition) {
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
        
        return $query;
    }

    // Query for lost books
    function getLostBooksQuery($connect, $search_by, $search_value) {
        // Base query
        $query = "
            SELECT 
                ib.issue_book_id, 
                b.book_name,
                b.book_isbn_number,
                u.user_name, 
                ib.issue_date, 
                ib.return_date, 
                ib.remarks,
                ib.book_condition
            FROM lms_issue_book AS ib
            LEFT JOIN lms_book AS b ON ib.book_id = b.book_id 
            LEFT JOIN lms_user AS u ON ib.user_id = u.user_id
            WHERE ib.book_condition = 'Lost'
        ";

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

        // Add ordering - always display the most recent first
        $query .= " ORDER BY ib.return_date DESC";
        
        return $query;
    }

    // Execute queries based on current tab
    if ($current_tab === 'returned') {
        $query = getReturnedBooksQuery($connect, $search_by, $search_value, $filter_condition);
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
    } else {
        // For lost books tab
        $query = getLostBooksQuery($connect, $search_by, $search_value);
        $statement = $connect->prepare($query);

        // Bind search value if needed
        if (!empty($search_value)) {
            $statement->bindValue(':search_value', '%' . $search_value . '%', PDO::PARAM_STR);
        }

        $statement->execute();
        $lost_books = $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    // Check for fines
    $fines_query = "
        SELECT issue_book_id, SUM(fines_amount) as fine_amount, fines_status
        FROM lms_fines
        GROUP BY issue_book_id, fines_status
    ";
    $fines_statement = $connect->prepare($fines_query);
    $fines_statement->execute();
    $fines_data = $fines_statement->fetchAll(PDO::FETCH_ASSOC);
    
    // Restructure fines data
    $fines = [];
    foreach ($fines_data as $fine) {
        $fines[$fine['issue_book_id']] = [
            'amount' => $fine['fine_amount'],
            'status' => $fine['fines_status']
        ];
    }
?>

<div class="py-4">
    <div class="d-flex justify-content-between align-items-center my-4">
        <h1 class="">Return/Lost Book Management</h1>
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
                    confirmButtonText: 'Done',
                    timer: 2000
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'marked_lost'): ?>
                Swal.fire({
                    icon: 'warning',
                    title: 'Book Marked as Lost',
                    text: 'The book has been successfully marked as lost and replacement fee has been added.',
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

    <?php if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])): ?>   
        <?php
            $id = $_GET['code'];
            $query = "
                SELECT ib.*, b.book_id, b.book_name, u.user_id, u.user_name 
                FROM lms_issue_book ib
                LEFT JOIN lms_book b ON ib.book_id = b.book_id
                LEFT JOIN lms_user u ON ib.user_id = u.user_id
                WHERE ib.issue_book_id = :id LIMIT 1
            ";
            $statement = $connect->prepare($query);
            $statement->execute([':id' => $id]);
            $issue = $statement->fetch(PDO::FETCH_ASSOC);

            $book_details = getBookDetails($connect, $issue['book_id']);
        ?>

        <?php if ($issue): ?>
        <!-- Edit Return Book Form with Modern UI -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-gradient-primary text-dark py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Book Return</h5>
                            <a href="return_book.php" class="btn btn-sm btn-light">
                                <i class="fas fa-arrow-left me-1"></i>Back to List
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Book Details Section -->
                            <div class="col-md-4">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 text-primary"><i class="fas fa-book me-2"></i>Book Information</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <img src="<?= htmlspecialchars(getBookImagePath($book_details['book_img'])) ?>" 
                                        alt="<?= htmlspecialchars($book_details['book_name']) ?>" 
                                        class="img-fluid mb-3 rounded shadow-sm" style="max-height: 260px;">
                                        
                                        <h5 class="fw-bold"><?= htmlspecialchars($issue['book_name']) ?></h5>
                                        <div class="mt-3 text-start">
                                            <p class="mb-1 small">
                                                <span class="fw-bold text-muted">ISBN:</span> 
                                                <?= htmlspecialchars($book_details['book_isbn_number']) ?>
                                            </p>
                                            <p class="mb-1 small">
                                                <span class="fw-bold text-muted">Category:</span> 
                                                <?= htmlspecialchars($book_details['category_name']) ?>
                                            </p>                                            
                                            <p class="mb-1 small">
                                                <span class="fw-bold text-muted">Location:</span> 
                                                <?= htmlspecialchars($book_details['book_location_rack']) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Return Process Form -->
                            <div class="col-md-8">
                                <form method="POST" class="needs-validation" novalidate>
                                    <input type="hidden" name="issue_book_id" value="<?= $issue['issue_book_id'] ?>">
                                    <input type="hidden" name="book_id" value="<?= $issue['book_id'] ?>">
                                    <input type="hidden" name="user_id" value="<?= $issue['user_id'] ?>">
                                    
                                    <!-- Borrower Information Card -->
                                    <div class="card border-0 shadow-sm mb-4">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0 text-primary"><i class="fas fa-user me-2"></i>Borrower Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Borrower Name</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light">
                                                                <i class="fas fa-user text-primary"></i>
                                                            </span>
                                                            <input type="text" class="form-control" value="<?= htmlspecialchars($issue['user_name']) ?>" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Dates Information Card -->
                                    <div class="card border-0 shadow-sm mb-4">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0 text-primary"><i class="fas fa-calendar-alt me-2"></i>Loan Information</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Issue Date</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light">
                                                                <i class="fas fa-calendar text-primary"></i>
                                                            </span>
                                                            <input type="datetime-local" name="issue_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($issue['issue_date'])) ?>" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Expected Return</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light">
                                                                <i class="fas fa-calendar-alt text-primary"></i>
                                                            </span>
                                                            <input type="datetime-local" name="expected_return_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($issue['expected_return_date'])) ?>" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Return Date</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light">
                                                                <i class="fas fa-calendar-check text-success"></i>
                                                            </span>
                                                            <input type="datetime-local" name="return_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Check for late return and display warning -->
                                            <?php 
                                            $expected_date = new DateTime($issue['expected_return_date']);
                                            $today = new DateTime(date('Y-m-d\TH:i'));
                                            $is_late = $today > $expected_date;
                                            
                                            if ($is_late): 
                                                $interval = $today->diff($expected_date);
                                                $days_late = $interval->days;
                                            ?>
                                            <div class="alert alert-warning d-flex align-items-center" role="alert">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <div>
                                                    This book is <strong><?= $days_late ?> days</strong> overdue. Late fees may apply.
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Book Condition Card -->
                                    <div class="card border-0 shadow-sm mb-4">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0 text-primary"><i class="fas fa-clipboard-check me-2"></i>Return Condition</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <?php
                                                    $condition_options = [
                                                        'Good',
                                                        'Damaged',
                                                        'Missing Pages',
                                                        'Water Damaged',
                                                        'Binding Loose',
                                                        'Lost'
                                                    ];

                                                    $selected_condition = $issue['book_condition'] ?? 'Good';
                                                    ?>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Book Condition</label>
                                                        <select name="condition_type" id="condition_type" class="form-select">
                                                            <?php foreach ($condition_options as $option): ?>
                                                                <option value="<?= $option ?>" <?= $selected_condition === $option ? 'selected' : '' ?>>
                                                                    <?= $option ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3" id="damageNotesContainer" style="display: none;">
                                                        <label class="form-label fw-bold">Damage Notes</label>
                                                        <textarea name="remarks" class="form-control" rows="2" placeholder="Please describe the damage..."><?= htmlspecialchars($issue['remarks'] ?? '') ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-end mt-4">
                                        <button type="submit" name="edit_return_book" class="btn btn-primary">
                                            <i class="fas fa-check-circle me-2"></i>Complete Return
                                        </button>
                                        <a href="return_book.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times-circle me-2"></i>Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Record Not Found',
                        text: 'The returned book record you are looking for does not exist.',
                        confirmButtonText: 'Back to Return Books',
                        confirmButtonColor: '#6c757d',
                        timer: 2000,
                        customClass: {
                            confirmButton: 'btn btn-outline-secondary'
                        },
                        buttonsStyling: false
                    }).then((result) => {
                        window.location.href = 'return_book.php';
                    });
                });
            </script>
  <?php endif; ?>

<?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])): ?> 
        <?php
            $id = $_GET['code'];
            $query = "
                SELECT ib.*, b.book_id, b.book_name, u.user_id, u.user_name 
                FROM lms_issue_book ib
                LEFT JOIN lms_book b ON ib.book_id = b.book_id
                LEFT JOIN lms_user u ON ib.user_id = u.user_id
                WHERE ib.issue_book_id = :id LIMIT 1
            ";
            $statement = $connect->prepare($query);
            $statement->execute([':id' => $id]);
            $issue = $statement->fetch(PDO::FETCH_ASSOC);

            $book_details = getBookDetails($connect, $issue['book_id']);
        ?>

        <?php if ($issue): ?>
            
<!-- View Return Book Form with Modern UI -->
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-gradient-info text-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-eye me-2"></i>View Returned Book Details</h5>
                    <a href="return_book.php" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left me-1"></i>Back to List
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Book Info -->
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-primary"><i class="fas fa-book me-2"></i>Book Information</h6>
                            </div>
                            <div class="card-body text-center">
                                <img src="<?= htmlspecialchars(getBookImagePath($book_details['book_img'])) ?>" 
                                     alt="<?= htmlspecialchars($book_details['book_name']) ?>" 
                                     class="img-fluid mb-3 rounded shadow-sm" style="max-height: 260px;">
                                <h5 class="fw-bold"><?= htmlspecialchars($issue['book_name']) ?></h5>
                                <div class="mt-3 text-start">
                                    <p class="mb-1 small"><span class="fw-bold text-muted">ISBN:</span> <?= htmlspecialchars($book_details['book_isbn_number']) ?></p>
                                    <p class="mb-1 small"><span class="fw-bold text-muted">Category:</span> <?= htmlspecialchars($book_details['category_name']) ?></p>
                                    <p class="mb-1 small"><span class="fw-bold text-muted">Location:</span> <?= htmlspecialchars($book_details['book_location_rack']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Borrower and Return Info -->
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-primary"><i class="fas fa-user me-2"></i>Borrower Information</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>Name:</strong> <?= htmlspecialchars($issue['user_name']) ?></p>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-primary"><i class="fas fa-calendar-alt me-2"></i>Loan Details</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>Issue Date:</strong> <?= date('F d, Y h:i A', strtotime($issue['issue_date'])) ?></p>
                                <p class="mb-2"><strong>Expected Return:</strong> <?= date('F d, Y h:i A', strtotime($issue['expected_return_date'])) ?></p>
                                <p class="mb-2"><strong>Return Date:</strong> <?= date('F d, Y h:i A', strtotime($issue['return_date'])) ?></p>

                                <?php 
                                $expected = new DateTime($issue['expected_return_date']);
                                $returned = new DateTime($issue['return_date']);
                                if ($returned > $expected):
                                    $late_days = $returned->diff($expected)->days;
                                ?>
                                    <div class="alert alert-warning mt-3" role="alert">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        This book was returned <strong><?= $late_days ?> days</strong> late.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-primary"><i class="fas fa-clipboard-check me-2"></i>Return Condition</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>Condition:</strong> <?= htmlspecialchars($issue['book_condition']) ?></p>
                                <?php if (!empty($issue['remarks'])): ?>
                                    <p class="mb-0"><strong>Remarks:</strong> <?= nl2br(htmlspecialchars($issue['remarks'])) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <a href="return_book.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times-circle me-2"></i>Close
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            icon: 'error',
            title: 'Record Not Found',
            text: 'The returned book record you are looking for does not exist.',
            confirmButtonText: 'Back to Return Books',
            confirmButtonColor: '#6c757d',
            timer: 2000,
            customClass: {
                confirmButton: 'btn btn-outline-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            window.location.href = 'return_book.php';
        });
    });
</script>
<?php endif; ?>


    <?php else: ?>
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $current_tab === 'returned' ? 'active bg-light' : '' ?>" href="return_book.php?tab=returned">
                    <i class="fas fa-check-circle me-2 text-success"></i>Returned Books
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_tab === 'lost' ? 'active bg-light' : '' ?>" href="return_book.php?tab=lost">
                    <i class="fas fa-times-circle me-2 text-danger"></i>Lost Books
                </a>
            </li>
        </ul>

        <!-- Search and Filter Section -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="tab" value="<?= $current_tab ?>">
                    
                    <div class="col-md-3">
                        <label class="form-label">Search By</label>
                        <select name="search_by" class="form-select">
                            <option value="book_id" <?= $search_by === 'book_id' ? 'selected' : '' ?>>Book ID</option>
                            <option value="book_name" <?= $search_by === 'book_name' ? 'selected' : '' ?>>Book Name</option>
                            <option value="user_name" <?= $search_by === 'user_name' ? 'selected' : '' ?>>User Name</option>
                            <option value="issue_book_id" <?= $search_by === 'issue_book_id' ? 'selected' : '' ?>>Issue ID</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Search Value</label>
                        <div class="input-group">
                            <input type="text" name="search_value" class="form-control" placeholder="Enter search term..." value="<?= htmlspecialchars($search_value) ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                            <a href="return_book.php?tab=<?= $current_tab ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-1"></i>Reset
                            </a>
                        </div>
                    </div>
                    
                    <?php if ($current_tab === 'returned'): ?>
                    <div class="col-md-3">
                        <label class="form-label">Filter by Condition</label>
                        <select name="book_condition" class="form-select">
                            <option value="">All Conditions</option>
                            <option value="Good" <?= $filter_condition === 'Good' ? 'selected' : '' ?>>Good</option>
                            <option value="Damaged" <?= $filter_condition === 'Damaged' ? 'selected' : '' ?>>Damaged</option>
                            <option value="Missing Pages" <?= $filter_condition === 'Missing Pages' ? 'selected' : '' ?>>Missing Pages</option>
                            <option value="Water Damaged" <?= $filter_condition === 'Water Damaged' ? 'selected' : '' ?>>Water Damaged</option>
                            <option value="Binding Loose" <?= $filter_condition === 'Binding Loose' ? 'selected' : '' ?>>Binding Loose</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if ($current_tab === 'returned'): ?>
            <!-- Returned Books Table -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light py-3">
                    <h5 class="mb-0"><i class="fas fa-book-reader me-2 text-primary"></i>Returned Book Records</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($returned_books)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No returned books found with the given criteria.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Issue ID</th>
                                        <th>Book Name</th>
                                        <th>Borrowed By</th>
                                        <th>Issue Date</th>
                                        <th>Return Date</th>
                                        <th>Condition</th>
                                        <th>Fine</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($returned_books as $book): ?>
                                        <tr>
                                            <td><?= $book['issue_book_id'] ?></td>
                                            <td><?= htmlspecialchars($book['book_name']) ?></td>
                                            <td><?= htmlspecialchars($book['user_name']) ?></td>
                                            <td><?= date('M d, Y', strtotime($book['issue_date'])) ?></td>
                                            <td><?= date('M d, Y', strtotime($book['return_date'])) ?></td>
                                            <td>
                                                <?php
                                                $badge_class = 'bg-success';
                                                switch ($book['book_condition']) {
                                                    case 'Damaged':
                                                    case 'Missing Pages':
                                                    case 'Water Damaged':
                                                    case 'Binding Loose':
                                                        $badge_class = 'bg-warning';
                                                        break;
                                                    case 'Lost':
                                                        $badge_class = 'bg-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($book['book_condition']) ?></span>
                                            </td>
                                            <td>
                                                <?php if (isset($fines[$book['issue_book_id']])): ?>
                                                    <span class="badge <?= $fines[$book['issue_book_id']]['status'] === 'Paid' ? 'bg-success' : 'bg-danger' ?>">
                                                    ₱<?= number_format($fines[$book['issue_book_id']]['amount'], 2) ?>
                                                        (<?= $fines[$book['issue_book_id']]['status'] ?>)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No Fine</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="return_book.php?code=<?= $book['issue_book_id'] ?>" class="btn btn-info btn-sm" title= "View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="return_book.php?action=edit&code=<?= $book['issue_book_id'] ?>" class="btn btn-primary btn-sm" title="Edit Return">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Lost Books Table -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light py-3">
                    <h5 class="mb-0"><i class="fas fa-times-circle me-2 text-danger"></i>Lost Book Records</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($lost_books)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No lost books found with the given criteria.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Issue ID</th>
                                        <th>Book Name</th>
                                        <th>ISBN</th>
                                        <th>Borrowed By</th>
                                        <th>Issue Date</th>
                                        <th>Reported Date</th>
                                        <th>Replacement Fine</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lost_books as $book): ?>
                                        <tr>
                                            <td><?= $book['issue_book_id'] ?></td>
                                            <td><?= htmlspecialchars($book['book_name']) ?></td>
                                            <td><?= htmlspecialchars($book['book_isbn_number']) ?></td>
                                            <td><?= htmlspecialchars($book['user_name']) ?></td>
                                            <td><?= date('M d, Y', strtotime($book['issue_date'])) ?></td>
                                            <td><?= date('M d, Y', strtotime($book['return_date'])) ?></td>
                                            <td>
                                                <?php if (isset($fines[$book['issue_book_id']])): ?>
                                                    <span class="badge <?= $fines[$book['issue_book_id']]['status'] === 'Paid' ? 'bg-success' : 'bg-danger' ?>">
                                                        $<?= number_format($fines[$book['issue_book_id']]['amount'], 2) ?>
                                                        (<?= $fines[$book['issue_book_id']]['status'] ?>)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Not Set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view_issue_book_details.php?code=<?= $book['issue_book_id'] ?>" class="btn btn-outline-primary" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (!isset($fines[$book['issue_book_id']]) || $fines[$book['issue_book_id']]['status'] !== 'Paid'): ?>
                                                    <button type="button" class="btn btn-outline-danger set-replacement-fee" data-issue-id="<?= $book['issue_book_id'] ?>" title="Set Replacement Fee">
                                                        <i class="fas fa-dollar-sign"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Set Replacement Fee Modal -->
<div class="modal fade" id="replacementFeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-circle me-2"></i>Set Replacement Fee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="replacementFeeForm">
                <div class="modal-body">
                    <input type="hidden" name="issue_book_id" id="modal_issue_id">
                    <input type="hidden" name="user_id" id="modal_user_id">
                    <input type="hidden" name="book_id" id="modal_book_id">
                    <input type="hidden" name="issue_date" id="modal_issue_date">
                    <input type="hidden" name="expected_return_date" id="modal_expected_return_date">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        Setting a replacement fee will mark this book as permanently lost and charge the borrower.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Replacement Cost ($)</label>
                        <input type="number" name="replacement_cost" class="form-control" min="0" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Additional Notes</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Any additional information about this lost book..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="mark_as_lost" class="btn btn-danger">Confirm & Apply Fee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show damage notes field when condition is not "Good"
    const conditionSelect = document.getElementById('condition_type');
    const damageNotesContainer = document.getElementById('damageNotesContainer');
    
    if (conditionSelect && damageNotesContainer) {
        function toggleDamageNotes() {
            damageNotesContainer.style.display = 
                conditionSelect.value === 'Good' ? 'none' : 'block';
        }
        
        toggleDamageNotes(); // Initial check
        conditionSelect.addEventListener('change', toggleDamageNotes);
    }
    
    // Set replacement fee modal functionality
    const replacementButtons = document.querySelectorAll('.set-replacement-fee');
    if (replacementButtons.length > 0) {
        replacementButtons.forEach(button => {
            button.addEventListener('click', function() {
                const issueId = this.getAttribute('data-issue-id');
                document.getElementById('modal_issue_id').value = issueId;
                
                // Fetch user and book details via AJAX
                fetch('get_issue_details.php?issue_id=' + issueId)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('modal_user_id').value = data.user_id;
                        document.getElementById('modal_book_id').value = data.book_id;
                        document.getElementById('modal_issue_date').value = data.issue_date;
                        document.getElementById('modal_expected_return_date').value = data.expected_return_date;
                        
                        // Show the modal
                        const modal = new bootstrap.Modal(document.getElementById('replacementFeeModal'));
                        modal.show();
                    })
                    .catch(error => {
                        console.error('Error fetching issue details:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load issue details. Please try again.'
                        });
                    });
            });
        });
    }
});
</script>

<?php include '../footer.php'; ?>