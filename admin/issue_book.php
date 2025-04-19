<?php

    // issue_book.php

    include '../database_connection.php';
    include '../function.php';
    include '../header.php';
    authenticate_admin();
    $message = ''; // Feedback message

    // Fetch issued books data
    $query = "SELECT lms_issue_book.issue_book_id, lms_book.book_name, lms_user.user_name, 
                    lms_issue_book.issue_date_time, lms_issue_book.return_date_time, lms_issue_book.issue_book_status 
            FROM lms_issue_book 
            INNER JOIN lms_book ON lms_issue_book.book_id = lms_book.book_id 
            INNER JOIN lms_user ON lms_issue_book.user_id = lms_user.user_id
            ORDER BY lms_issue_book.issue_book_id DESC";


    // Get library settings at the start
    $library_settings = getLibrarySettings($connect);
    $loan_days = $library_settings['loan_days'];
    $max_books_per_user = $library_settings['max_books_per_user'];
    $library_hours = $library_settings['library_hours'];
    $fine_rate_per_day = $library_settings['fine_rate_per_day'];
    $max_fine_per_book = $library_settings['max_fine_per_book'];

    // Check for overdue books at the start of the script
    checkAndUpdateOverdueBooks($connect, $library_settings);

    // Mark as Lost (Instead of Delete) - No change needed
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

    // ISSUE Book (Form Submit) - Modified to use library settings
    if (isset($_POST['issue_book'])) {
        $book_id = trim($_POST['book_id']);
        $user_id = trim($_POST['user_id']);
        $issue_date = trim($_POST['issue_date']);
        $status = trim($_POST['issue_book_status']);
        
        // Calculate expected return date based on issue date and library settings
        $expected_return_date = calculateExpectedReturnDate($issue_date, $loan_days, $library_hours);
        
        // Check for duplicate issued book
        $duplicate_check = "
            SELECT COUNT(*) AS duplicate_count 
            FROM lms_issue_book 
            WHERE book_id = :book_id 
            AND user_id = :user_id 
            AND issue_book_status IN ('Issued', 'Overdue')
        ";
        $stmt = $connect->prepare($duplicate_check);
        $stmt->execute([':book_id' => $book_id, ':user_id' => $user_id]);
        if ($stmt->fetchColumn() > 0) {
            header('location:issue_book.php?action=add&error=duplicate');
            exit;
        }

        // Check user's active issue count
        $limit_check = "
            SELECT COUNT(*) AS count 
            FROM lms_issue_book 
            WHERE user_id = :user_id 
            AND issue_book_status IN ('Issued', 'Overdue')
        ";
        $stmt = $connect->prepare($limit_check);
        $stmt->execute([':user_id' => $user_id]);
        if ($stmt->fetchColumn() >= $max_books_per_user) {
            header('location:issue_book.php?action=add&error=limit');
            exit;
        }

        // Check book availability
        $check_query = "SELECT book_no_of_copy FROM lms_book WHERE book_id = :book_id";
        $stmt = $connect->prepare($check_query);
        $stmt->execute([':book_id' => $book_id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($book && $book['book_no_of_copy'] > 0) {
            $date_now = get_date_time($connect);

            // Insert the issue record
            $insert_query = "
                INSERT INTO lms_issue_book 
                    (book_id, user_id, issue_date, expected_return_date, issue_book_status, issued_on, issue_updated_on) 
                VALUES 
                    (:book_id, :user_id, :issue_date, :expected_return_date, :status, :issued_on, :updated_on)
            ";
            $stmt = $connect->prepare($insert_query);
            $stmt->execute([
                ':book_id' => $book_id,
                ':user_id' => $user_id,
                ':issue_date' => $issue_date,
                ':expected_return_date' => $expected_return_date,
                ':status' => $status,
                ':issued_on' => $date_now,
                ':updated_on' => $date_now
            ]);

            // Decrease the available copies
            $update_book = "
                UPDATE lms_book 
                SET book_no_of_copy = book_no_of_copy - 1, 
                    book_updated_on = :updated_on 
                WHERE book_id = :book_id
            ";
            $stmt = $connect->prepare($update_book);
            $stmt->execute([':updated_on' => $date_now, ':book_id' => $book_id]);

            header('location:issue_book.php?msg=issued');
            exit;
        } else {
            header('location:issue_book.php?action=add&error=no_copy');
            exit;
        }
    }

    // EDIT Issue Book - Modified to handle library hours and fine calculation
    if (isset($_POST['edit_issue_book'])) {
        $issue_book_id = $_POST['issue_book_id'];
        $user_id = $_POST['user_id'];
        $book_id = $_POST['book_id'];
        $issue_date = $_POST['issue_date'];
        $expected_return_date = $_POST['expected_return_date'];
        $issue_book_status = $_POST['issue_book_status'];
        $date_now = get_date_time($connect);
        
        // Handle book condition based on status
        $book_condition = null;
        if ($issue_book_status === 'Returned') {
            if (isset($_POST['book_condition'])) {
                $selected_condition = $_POST['book_condition'];
                if ($selected_condition === 'Others' && isset($_POST['condition_remarks'])) {
                    $book_condition = $_POST['condition_remarks'];
                } else {
                    $book_condition = $selected_condition;
                }
            }
        }
        
        // Set return date if status is "Returned"
        $return_date = null;
        if ($issue_book_status === 'Returned') {
            $return_date_raw = date('Y-m-d H:i:s');
            // Adjust return date if it's after hours or on closed days
            $return_date = adjustReturnDate($return_date_raw, $library_hours);
        }

        // Check for duplicate entry before updating
        $check_duplicate_query = "
            SELECT COUNT(*) as duplicate_count
            FROM lms_issue_book
            WHERE book_id = :book_id 
            AND user_id = :user_id 
            AND issue_book_status IN ('Issued', 'Overdue')
            AND issue_book_id != :issue_book_id
        ";

        $statement = $connect->prepare($check_duplicate_query);
        $statement->execute([
            ':book_id' => $book_id,
            ':user_id' => $user_id,
            ':issue_book_id' => $issue_book_id
        ]);

        $duplicate = $statement->fetch(PDO::FETCH_ASSOC);

        if ($duplicate['duplicate_count'] > 0) {
            // Duplicate found, redirect with error message
            header('location:issue_book.php?action=edit&code=' . $issue_book_id . '&error=duplicate');
            exit;
        }

        // Update the issue record
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

        // Fine calculation logic
        if ($issue_book_status === 'Returned' || $issue_book_status === 'Overdue') {
            $return_date_actual = $return_date ?: date('Y-m-d');
            
            // Calculate fine based on library settings
            $fine_data = calculateFine($expected_return_date, $return_date_actual, $library_settings);
            $days_late = $fine_data['days_late'];
            $fines_amount = $fine_data['fine_amount'];

            if ($days_late > 0) {
                // Check if a fine record already exists
                $check_query = "
                    SELECT fines_id FROM lms_fines 
                    WHERE issue_book_id = :issue_book_id AND user_id = :user_id
                ";
                $statement = $connect->prepare($check_query);
                $statement->execute([':issue_book_id' => $issue_book_id, ':user_id' => $user_id]);
                $existing_fine = $statement->fetch(PDO::FETCH_ASSOC);

                if ($existing_fine) {
                    // Update existing fine record
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
                    // Insert new fine record
                    $insert_query = "
                        INSERT INTO lms_fines (user_id, issue_book_id, expected_return_date, return_date, days_late, fines_amount, fines_status, fines_created_on)
                        VALUES (:user_id, :issue_book_id, :expected_return_date, :return_date, :days_late, :fines_amount, 'Unpaid', NOW())
                    ";
                    $statement = $connect->prepare($insert_query);
                    $statement->execute([
                        ':user_id' => $user_id,
                        ':issue_book_id' => $issue_book_id,
                        ':expected_return_date' => $expected_return_date,
                        ':return_date' => $return_date_actual,
                        ':days_late' => $days_late,
                        ':fines_amount' => $fines_amount
                    ]);
                }
            }
        }

        // Automatically update the number of copies in lms_book table if status is changed
        if ($issue_book_status === 'Returned') {
            $update_book_query = "
                UPDATE lms_book 
                SET book_no_of_copy = book_no_of_copy + 1,
                    book_updated_on = NOW()
                WHERE book_id = :book_id
            ";
            $statement = $connect->prepare($update_book_query);
            $statement->execute([':book_id' => $book_id]);
        } elseif ($issue_book_status === 'Issued') {
            $update_book_query = "
                UPDATE lms_book 
                SET book_no_of_copy = book_no_of_copy - 1,
                    book_updated_on = NOW()
                WHERE book_id = :book_id AND book_no_of_copy > 0
            ";
            $statement = $connect->prepare($update_book_query);
            $statement->execute([':book_id' => $book_id]);
        }

        header('location:issue_book.php?msg=edit');
        exit;
    }

    // Fetch all issued books with user and book details - EXCLUDE RETURNED BOOKS
    $query = "
        SELECT 
            ib.issue_book_id, 
            b.book_name, 
            u.user_name, 
            ib.issue_date, 
            ib.expected_return_date, 
            ib.issue_book_status, 
            ib.issued_on, 
            ib.issue_updated_on 
        FROM lms_issue_book AS ib
        LEFT JOIN lms_book AS b ON ib.book_id = b.book_id 
        LEFT JOIN lms_user AS u ON ib.user_id = u.user_id
        WHERE ib.issue_book_status != 'Returned'
        ORDER BY ib.issue_book_id ASC
    ";

    $statement = $connect->prepare($query);
    $statement->execute();  
    $issue_book = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all book names for dropdown lists
    $book_query = "SELECT book_id, book_name FROM lms_book WHERE book_status = 'Enable' AND book_no_of_copy > 0 ORDER BY book_name ASC";
    $book_statement = $connect->prepare($book_query);
    $book_statement->execute();
    $books = $book_statement->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all user names for dropdown lists
    $user_query = "SELECT user_id, user_name FROM lms_user WHERE user_status = 'Enable' ORDER BY user_name ASC";
    $user_statement = $connect->prepare($user_query);
    $user_statement->execute();
    $users = $user_statement->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center my-4">
        <h1 class="">Issue Book Management</h1>
    </div>

    <?php if (isset($_GET["msg"])): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_GET["msg"]) && $_GET["msg"] == 'lost'): ?>
                Swal.fire({
                    icon: 'warning',
                    title: 'Book Marked as Lost',
                    text: 'The book has been marked as lost!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'issued'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Book Issued',
                    text: 'The book has been successfully issued!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'returned'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Book Returned',
                    text: 'The book has been successfully returned!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'edit'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Record Updated',
                    text: 'The issue record was updated successfully!',
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

        <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
            <!-- Issue Book Form -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Issue New Book</h5>
                        <a href="issue_book.php" class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" id="error-alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php if ($_GET['error'] == 'no_copy'): ?>
                                The selected book is not available for issuing. No copies left.
                            <?php elseif ($_GET['error'] == 'duplicate'): ?>
                                This book is already issued to this borrower.
                            <?php elseif ($_GET['error'] == 'limit'): ?>
                                This borrower already has 2 active books. Cannot issue more books.
                            <?php endif; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-4 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-book me-2"></i>Book Selection</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Book Dropdown -->
                                        <div class="mb-3">
                                            <label for="book_id" class="form-label fw-bold">Select Book</label>
                                            <select name="book_id" id="book_id" class="form-control select2-book" required onchange="this.form.submit()">
                                                <option value="">Select Book</option>
                                                <?php foreach ($books as $book): ?>
                                                    <?php $selected = (isset($_POST['book_id']) && $_POST['book_id'] == $book['book_id']) || 
                                                                (isset($_GET['book_id']) && $_GET['book_id'] == $book['book_id']) ? 'selected' : ''; ?>
                                                    <option value="<?= htmlspecialchars($book['book_id']) ?>" <?= $selected ?>>
                                                        <?= htmlspecialchars($book['book_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">Please select a book.</div>
                                        </div>
                                        
                                        <!-- Book Cover and Details Display -->
                                        <?php
                                        $selected_book = null;
                                        if (isset($_POST['book_id']) || isset($_GET['book_id'])) {
                                            $book_id = $_POST['book_id'] ?? $_GET['book_id'];
                                            $selected_book = getBookDetails($connect, $book_id);
                                        }
                                        ?>
                                        
                                        <?php if ($selected_book): ?>
                                        <div class="card mt-3">
                                            <div class="card-body text-center p-3">
                                                <img src="<?= htmlspecialchars(getBookImagePath($selected_book)) ?>"
                                                    alt="Book Cover"
                                                    class="img-thumbnail mb-3"
                                                    style="max-height: 200px; width: auto;">
                                                <h5 class="card-title mb-1"><?= htmlspecialchars($selected_book['book_name']) ?></h5>
                                                <?php if (!empty($selected_book['category_name'])): ?>
                                                    <span class="badge bg-info text-dark mb-2"><?= htmlspecialchars($selected_book['category_name']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($selected_book['book_isbn_number'])): ?>
                                                    <p class="card-text small text-muted mb-0">ISBN: <?= htmlspecialchars($selected_book['book_isbn_number']) ?></p>
                                                <?php endif; ?>
                                                <?php if (isset($selected_book['book_no_of_copy'])): ?>
                                                    <p class="text-success mt-2 mb-0">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        Available Copies: <?= htmlspecialchars($selected_book['book_no_of_copy']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-4 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-user me-2"></i>Borrower Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Member Dropdown -->
                                        <div class="mb-3">
                                            <label for="user_id" class="form-label fw-bold">Select Borrower</label>
                                            <select name="user_id" id="user_id" class="form-control select2-user" required>
                                                <option value="">Select Member</option>
                                                <?php foreach ($users as $member): ?>
                                                    <?php $selected = (isset($_POST['user_id']) && $_POST['user_id'] == $member['user_id']) ? 'selected' : ''; ?>
                                                    <option value="<?= htmlspecialchars($member['user_id']) ?>" <?= $selected ?>>
                                                        <?= htmlspecialchars($member['user_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">Please select a borrower.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-4 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-calendar-alt me-2"></i>Issue Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="issue_date" class="form-label fw-bold">Issue Date</label>
                                                    <input type="date" id="issue_date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                                    <div class="invalid-feedback">Please select an issue date.</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="expected_return_date" class="form-label fw-bold">Expected Return Date</label>
                                                    <input type="date" id="expected_return_date" name="expected_return_date" class="form-control" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                                                    <div class="invalid-feedback">Please select an expected return date.</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="issue_book_status" class="form-label fw-bold">Status</label>
                                            <select name="issue_book_status" id="issue_book_status" class="form-select">
                                                <option value="Issued">Issued</option>
                                                <option value="Overdue">Overdue</option>
                                                <option value="Lost">Lost</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-end mt-3">
                            <button type="submit" name="issue_book" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Issue Book
                            </button>
                            <a href="issue_book.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
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
                    // Get book details
                    $book = getBookDetails($connect, $issue['book_id']);
            ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Issued Book</h5>
                        <a href="issue_book.php" class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['error']) && $_GET['error'] == 'duplicate'): ?>
                        <div class="alert alert-danger alert-dismissible fade show" id="error-alert">
                            <i class="fas fa-exclamation-circle me-2"></i>This book is already issued to this borrower.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="issue_book_id" value="<?= $issue['issue_book_id'] ?>">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-4 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-book me-2"></i>Book Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Book Dropdown -->
                                        <div class="mb-3">
                                            <label for="book_id" class="form-label fw-bold">Select Book</label>
                                            <select name="book_id" id="book_id" class="form-control select2-book" required onchange="this.form.submit()">
                                                <option value="">Select Book</option>
                                                <?php 
                                                $query = "SELECT book_id, book_name FROM lms_book WHERE book_status = 'Enable' ORDER BY book_name ASC";
                                                $statement = $connect->prepare($query);
                                                $statement->execute();
                                                $books = $statement->fetchAll(PDO::FETCH_ASSOC);

                                                $selected_book_id = isset($_POST['book_id']) ? $_POST['book_id'] : $issue['book_id'];

                                                foreach ($books as $book_item): 
                                                    $selected = $book_item['book_id'] == $selected_book_id ? 'selected' : '';
                                                ?>
                                                    <option value="<?= $book_item['book_id'] ?>" <?= $selected ?>>
                                                        <?= htmlspecialchars($book_item['book_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">Please select a book.</div>
                                        </div>
                                        
                                        <!-- Book Cover Display -->
                                        <?php 
                                        // If book_id was changed in the form, get the new book details
                                        if (isset($_POST['book_id']) && $_POST['book_id'] != $issue['book_id']) {
                                            $book = getBookDetails($connect, $_POST['book_id']);
                                        }
                                        
                                        if (isset($book) && $book): 
                                        ?>
                                        <div class="card mt-3">
                                            <div class="card-body text-center p-3">
                                                <img src="<?= htmlspecialchars(getBookImagePath($book)) ?>" 
                                                    class="img-thumbnail mb-3" 
                                                    style="max-height: 200px;">
                                                <h5 class="card-title mb-1"><?= htmlspecialchars($book['book_name']) ?></h5>
                                                <?php if (!empty($book['category_name'])): ?>
                                                    <span class="badge bg-info text-dark mb-2"><?= htmlspecialchars($book['category_name']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($book['book_isbn_number'])): ?>
                                                    <p class="card-text small text-muted mb-0">ISBN: <?= htmlspecialchars($book['book_isbn_number']) ?></p>
                                                <?php endif; ?>
                                                <?php if (isset($book['book_no_of_copy'])): ?>
                                                    <p class="text-success mt-2 mb-0">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        Available Copies: <?= htmlspecialchars($book['book_no_of_copy']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-4 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-user me-2"></i>Borrower Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- User Dropdown -->
                                        <div class="mb-3">
                                            <label for="user_id" class="form-label fw-bold">Select Borrower</label>
                                            <select name="user_id" id="user_id" class="form-control select2-user" required>
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
                                            <div class="invalid-feedback">Please select a borrower.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-4 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-calendar-alt me-2"></i>Issue Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="issue_date" class="form-label fw-bold">Issue Date</label>
                                                    <input type="date" id="issue_date" name="issue_date" class="form-control" value="<?= $issue['issue_date'] ?>" readonly>
                                                    <small class="text-muted">Issue date cannot be changed</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="expected_return_date" class="form-label fw-bold">Expected Return Date</label>
                                                    <input type="date" id="expected_return_date" name="expected_return_date" class="form-control" value="<?= $issue['expected_return_date'] ?>" required>
                                                    <div class="invalid-feedback">Please enter an expected return date.</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label for="issue_book_status" class="form-label fw-bold">Status</label>
                                                    <select name="issue_book_status" id="issue_book_status" class="form-select" required>
                                                        <option value="Issued" <?= $issue['issue_book_status'] == 'Issued' ? 'selected' : '' ?>>Issued</option>
                                                        <option value="Returned" <?= $issue['issue_book_status'] == 'Returned' ? 'selected' : '' ?>>Returned</option>
                                                        <option value="Overdue" <?= $issue['issue_book_status'] == 'Overdue' ? 'selected' : '' ?>>Overdue</option>
                                                        <option value="Lost" <?= $issue['issue_book_status'] == 'Lost' ? 'selected' : '' ?>>Lost</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Book Condition Fields (Hidden by default, shown when status is "Returned") -->
                                        <div id="condition_fields" class="mb-3" style="display: <?= $issue['issue_book_status'] == 'Returned' ? 'block' : 'none' ?>;">
                                            <label for="book_condition" class="form-label fw-bold">Book Condition</label>
                                            <select name="book_condition" id="book_condition" class="form-select">
                                                <option value="Good" <?= ($issue['book_condition'] ?? '') == 'Good' ? 'selected' : '' ?>>Good</option>
                                                <option value="Damaged" <?= ($issue['book_condition'] ?? '') == 'Damaged' ? 'selected' : '' ?>>Damaged</option>
                                                <option value="Missing Pages" <?= ($issue['book_condition'] ?? '') == 'Missing Pages' ? 'selected' : '' ?>>Missing Pages</option>
                                                <option value="Water Damaged" <?= ($issue['book_condition'] ?? '') == 'Water Damaged' ? 'selected' : '' ?>>Water Damaged</option>
                                                <option value="Binding Loose" <?= ($issue['book_condition'] ?? '') == 'Binding Loose' ? 'selected' : '' ?>>Binding Loose</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-end mt-3">
                            <button type="submit" name="edit_issue_book" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Record
                            </button>
                            <a href="issue_book.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>

                    <!-- JavaScript to show/hide condition fields based on status -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const statusSelect = document.getElementById('issue_book_status');
                            const conditionFields = document.getElementById('condition_fields');
                            
                            statusSelect.addEventListener('change', function() {
                                if (this.value === 'Returned') {
                                    conditionFields.style.display = 'block';
                                } else {
                                    conditionFields.style.display = 'none';
                                }
                            });
                        });
                    </script>
                </div>
            </div>
            <?php endif; ?>

        <?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])): ?>
            <?php
            $id = $_GET['code'];
            $query = "
                SELECT ib.*, 
                    b.book_name, b.book_id, b.book_isbn_number,
                    u.user_name, u.user_email, u.user_contact_no,
                    DATEDIFF(ib.expected_return_date, ib.issue_date) AS loan_days
                FROM lms_issue_book ib
                LEFT JOIN lms_book b ON b.book_id = ib.book_id
                LEFT JOIN lms_user u ON u.user_id = ib.user_id
                WHERE ib.issue_book_id = :id 
                LIMIT 1
            ";
            $statement = $connect->prepare($query);
            $statement->execute([':id' => $id]);
            $issue = $statement->fetch(PDO::FETCH_ASSOC);

            if ($issue): 
                // Get detailed book information for the cover image
                $book = getBookDetails($connect, $issue['book_id']);
            ?>
                <!-- View Issue Book Details -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-eye me-2"></i>View Issued Book Details</h5>
                            <a href="issue_book.php" class="btn btn-sm btn-light">
                                <i class="fas fa-arrow-left me-1"></i>Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-book me-2"></i>Book Information</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <!-- Book Cover Display -->
                                        <?php if (isset($book) && $book): ?>
                                        <div class="mb-3">
                                            <img src="<?= htmlspecialchars(getBookImagePath($book)) ?>" class="img-thumbnail mb-3" style="max-height: 250px;">
                                            <h5 class="card-title mb-1"><?= htmlspecialchars($issue['book_name']) ?></h5>
                                            <?php if (!empty($book['category_name'])): ?>
                                                <span class="badge bg-info text-dark mb-2"><?= htmlspecialchars($book['category_name']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($issue['book_isbn_number'])): ?>
                                                <p class="card-text small text-muted">ISBN: <?= htmlspecialchars($issue['book_isbn_number']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="card shadow-sm mb-4">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-calendar-alt me-2"></i>Date Information</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <h6 class="fw-bold text-muted">Issue Date</h6>
                                                    <p>
                                                        <i class="far fa-calendar me-2"></i>
                                                        <?= date('F d, Y', strtotime($issue['issue_date'])) ?>
                                                    </p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <h6 class="fw-bold text-muted">Expected Return Date</h6>
                                                    <p>
                                                        <i class="far fa-calendar-check me-2"></i>
                                                        <?= date('F d, Y', strtotime($issue['expected_return_date'])) ?>
                                                    </p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <h6 class="fw-bold text-muted">Loan Period</h6>
                                                    <p>
                                                        <i class="far fa-clock me-2"></i>
                                                        <?= $issue['loan_days'] ?> days
                                                    </p>
                                                </div>
                                                
                                                <?php if ($issue['issue_book_status'] == 'Returned' && isset($issue['return_date'])): ?>
                                                <div class="mb-3">
                                                    <h6 class="fw-bold text-muted">Actual Return Date</h6>
                                                    <p>
                                                        <i class="far fa-calendar-alt me-2"></i>
                                                        <?= date('F d, Y', strtotime($issue['return_date'])) ?>
                                                    </p>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($issue['issue_book_status'] == 'Returned' && isset($issue['book_condition'])): ?>
                                        <div class="card shadow-sm">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-clipboard-check me-2"></i>Return Condition</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <h6 class="fw-bold text-muted">Book Condition on Return</h6>
                                                    <?php 
                                                    $condition_class = '';
                                                    $condition_icon = '';
                                                    switch ($issue['book_condition']) {
                                                        case 'Good':
                                                            $condition_class = 'text-success';
                                                            $condition_icon = 'fa-check-circle';
                                                            break;
                                                        case 'Damaged':
                                                        case 'Missing Pages':
                                                        case 'Water Damaged':
                                                        case 'Binding Loose':
                                                            $condition_class = 'text-warning';
                                                            $condition_icon = 'fa-exclamation-triangle';
                                                            break;
                                                    }
                                                    ?>
                                                    <p class="<?= $condition_class ?>">
                                                        <i class="fas <?= $condition_icon ?> me-2"></i>
                                                        <?= htmlspecialchars($issue['book_condition']) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                    </div>
                                    
                                    <div class="col-md-7 mb-3">
                                        <div class="card shadow-sm">
                                            <div class="card-header bg-light">
                                            <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-user me-2"></i>Borrower Information</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <h6 class="fw-bold text-muted">Borrower Name</h6>
                                                    <p class="lead">
                                                        <i class="fas fa-user-circle me-2 text-primary"></i>
                                                        <?= htmlspecialchars($issue['user_name']) ?>
                                                    </p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <h6 class="fw-bold text-muted">Contact Information</h6>
                                                    <p>
                                                        <i class="fas fa-envelope me-2 text-muted"></i>
                                                        <?= htmlspecialchars($issue['user_email']) ?>
                                                    </p>
                                                    <p>
                                                        <i class="fas fa-phone me-2 text-muted"></i>
                                                        <?= htmlspecialchars($issue['user_contact_no']) ?>
                                                    </p>
                                                </div>
                                                
                                                <!-- Borrower Stats - Optional Enhancement -->
                                                <?php
                                                // Get borrower's active book count
                                                $query = "SELECT COUNT(*) AS active_books FROM lms_issue_book 
                                                        WHERE user_id = :user_id AND issue_book_status IN ('Issued', 'Overdue') 
                                                        AND issue_book_id != :current_issue";
                                                $statement = $connect->prepare($query);
                                                $statement->execute([
                                                    ':user_id' => $issue['user_id'],
                                                    ':current_issue' => $issue['issue_book_id']
                                                ]);
                                                $borrower_stats = $statement->fetch(PDO::FETCH_ASSOC);
                                                ?>
                                                
                                                <div class="mb-3">
                                                    <h6 class="fw-bold text-muted">Borrower Stats</h6>
                                                    <p>
                                                        <i class="fas fa-book me-2 text-muted"></i>
                                                        Other Active Books: 
                                                        <span class="badge bg-<?= $borrower_stats['active_books'] > 0 ? 'info' : 'success' ?>">
                                                            <?= $borrower_stats['active_books'] ?>
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">     
                                        <div class="card shadow-sm">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-info-circle me-2"></i>Issue Information</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <h6 class="fw-bold text-muted">Issue ID</h6>
                                                    <p class="lead">#<?= htmlspecialchars($issue['issue_book_id']) ?></p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <h6 class="fw-bold text-muted">Status</h6>
                                                    <p>
                                                        <?php
                                                            $status_class = '';
                                                            $status_icon = '';
                                                            
                                                            switch ($issue['issue_book_status']) {
                                                                case 'Issued':
                                                                    $status_class = 'bg-info';
                                                                    $status_icon = 'fa-bookmark';
                                                                    break;
                                                                case 'Returned':
                                                                    $status_class = 'bg-success';
                                                                    $status_icon = 'fa-check-circle';
                                                                    break;
                                                                case 'Overdue':
                                                                    $status_class = 'bg-warning';
                                                                    $status_icon = 'fa-exclamation-circle';
                                                                    break;
                                                                case 'Lost':
                                                                    $status_class = 'bg-danger';
                                                                    $status_icon = 'fa-times-circle';
                                                                    break;
                                                            }
                                                        ?>
                                                        <span class="badge <?= $status_class ?> fs-6">
                                                            <i class="fas <?= $status_icon ?> me-1"></i>
                                                            <?= $issue['issue_book_status'] ?>
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <a href="issue_book.php?action=edit&code=<?= $issue['issue_book_id'] ?>" class="btn btn-primary me-2">
                                <i class="fas fa-edit me-1"></i>Edit
                            </a>
                            <a href="issue_book.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>Issue book record not found.
                    <a href="issue_book.php" class="btn btn-sm btn-outline-secondary ms-3">Back to Issue Books</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Issued Books List -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i>Active Book Issues</h5>                        
                        <?php if (!isset($_GET['action'])): ?>            
                            <a href="issue_book.php?action=add" class="btn btn-sm btn-success">
                                <i class="fas fa-plus-circle me-2"></i>Issue New Book
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dataTable" class="display nowrap" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Issue ID</th>
                                    <th>Book Name</th>
                                    <th>Borrower Name</th>
                                    <th>Issue Date</th>
                                    <th>Expected Return Date</th>
                                    <th>Issued On</th>
                                    <th>Updated On</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($issue_book as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['issue_book_id']) ?></td>
                                        <td><?= htmlspecialchars($row['book_name']) ?></td>
                                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                                        <td><?= date('M d, Y', strtotime($row['issue_date'])) ?></td>
                                        <td><?= date('M d, Y', strtotime($row['expected_return_date'])) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($row['issued_on'])) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($row['issue_updated_on'])) ?></td>
                                        <td>
                                            <?php if ($row['issue_book_status'] == 'Issued'): ?>
                                                <span class="badge bg-success">Issued</span>
                                            <?php elseif ($row['issue_book_status'] == 'Overdue'): ?>
                                                <span class="badge bg-danger">Overdue</span>
                                            <?php elseif ($row['issue_book_status'] == 'Lost'): ?>
                                                <span class="badge bg-warning text-dark">Lost</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="issue_book.php?action=view&code=<?= $row['issue_book_id'] ?>" class="btn btn-info btn-sm mb-1">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="issue_book.php?action=edit&code=<?= $row['issue_book_id'] ?>" class="btn btn-primary btn-sm mb-1">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm mb-1 mark-lost" data-id="<?= $row['issue_book_id'] ?>" title="Mark as Lost">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

</div>

<script>
    // DataTable initialization
    $(document).ready(function() {
        // If there are no rows in the table except for the empty message row
        if ($('#dataTable tbody tr').length === 1 && $('#dataTable tbody tr td[colspan]').length === 1) {
            // Instead of initializing DataTables, just show the message
            $('#dataTable').addClass('table table-bordered');
        } else {
            // Initialize DataTables only if there's actual data
            const table = $('#dataTable').DataTable({
                responsive: true,
                columnDefs: [
                    { responsivePriority: 1, targets: [0, 1, 2, 3, 4, 7, 8] }, // ID, Book, Actions
                    { responsivePriority: 2, targets: [ 5, 6] }  // User, Condition, Fine
                ],
                order: [[4, 'desc']], // Sort by return date by default
                autoWidth: false,
                language: {
                    emptyTable: "No returned books found"
                },
                scrollY: '500px',
                scrollX: true,
                scrollCollapse: true,
                paging: true,
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
        }
        
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Initialize Select2 for better dropdowns
        $('.select2-book').select2({
            placeholder: "Select a book",
            allowClear: true,
            width: '100%'
        });

        $('.select2-user').select2({
            placeholder: "Select a borrower",
            allowClear: true,
            width: '100%'
        });

        // Set minimum date for issue_date and expected_return_date to today
        const today = new Date().toISOString().split('T')[0];
        
        if (document.getElementById('issue_date')) {
            document.getElementById('issue_date').min = today;
            document.getElementById('issue_date').value = today;
        }


        // Form validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        // Show/hide condition fields based on status
        const issueBookStatusSelect = document.getElementById('issue_book_status');
        const conditionFields = document.getElementById('condition_fields');
        const bookConditionSelect = document.getElementById('book_condition');
        const customConditionDiv = document.getElementById('custom_condition');
        
        if (issueBookStatusSelect && conditionFields) {
            // Initially check status
            if (issueBookStatusSelect.value === 'Returned') {
                conditionFields.style.display = 'block';
            }
            
            issueBookStatusSelect.addEventListener('change', function() {
                if (this.value === 'Returned') {
                    conditionFields.style.display = 'block';
                } else {
                    conditionFields.style.display = 'none';
                    customConditionDiv.style.display = 'none';
                }
            });
        }
        
        if (bookConditionSelect && customConditionDiv) {
            // Initially check condition
            if (bookConditionSelect.value === 'Others') {
                customConditionDiv.style.display = 'block';
            }
            
            bookConditionSelect.addEventListener('change', function() {
                if (this.value === 'Others') {
                    customConditionDiv.style.display = 'block';
                } else {
                    customConditionDiv.style.display = 'none';
                }
            });
        }

        // SweetAlert2 confirmation for marking book as lost
        const markLostButtons = document.querySelectorAll('.mark-lost');
        if (markLostButtons) {
            markLostButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    
                    Swal.fire({
                        title: 'Mark as Lost?',
                        text: "This will mark the book as lost. This action cannot be undone!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, mark as lost!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = `issue_book.php?action=delete&code=${id}`;
                        }
                    });
                });
            });
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const issueDateInput = document.getElementById('issue_date');
        const returnDateInput = document.getElementById('expected_return_date');

        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }

        const today = new Date();
        const tomorrow = new Date();
        tomorrow.setDate(today.getDate() + 1);

        if (issueDateInput) {
            issueDateInput.setAttribute('min', formatDate(today));

            issueDateInput.addEventListener('change', function () {
                const issueDate = new Date(this.value);
                const minReturnDate = new Date(issueDate);
                minReturnDate.setDate(issueDate.getDate() + 1);
                returnDateInput.value = '';
                returnDateInput.setAttribute('min', formatDate(minReturnDate));
            });
        }

        if (returnDateInput && issueDateInput) {
            returnDateInput.addEventListener('change', function () {
                const issueDate = new Date(issueDateInput.value);
                const returnDate = new Date(this.value);

                if (returnDate <= issueDate) {
                    alert("Expected return date must be at least one day after the issue date.");
                    this.value = '';
                }
            });
        }
    });
</script>
<script>
    // Date validation and dynamic calculations for library system
    document.addEventListener("DOMContentLoaded", function() {
        // Get form elements
        const issueDateInput = document.getElementById('issue_date');
        const returnDateInput = document.getElementById('expected_return_date');
        const actualReturnDateInput = document.getElementById('return_date');
        const statusSelect = document.getElementById('issue_book_status');
        const bookConditionContainer = document.getElementById('book-condition-container');
        const finesContainer = document.getElementById('fines-container');
        const daysLateInput = document.getElementById('days_late');
        const finesAmountInput = document.getElementById('fines_amount');
        
        // Library settings (these will be populated from PHP)
        const librarySettings = {
            libraryHours: window.libraryHours || {},
            fineRatePerDay: window.fineRatePerDay || 5,
            maxFinePerBook: window.maxFinePerBook || 500,
            loanDays: window.loanDays || 7
        };
        
        // Format date for input fields
        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }
        
        // Get day name from date
        function getDayName(date) {
            return date.toLocaleDateString('en-US', { weekday: 'long' });
        }
        
        // Check if the library is closed on a specific date
        function isLibraryClosed(date) {
            const dayName = getDayName(date);
            return !librarySettings.libraryHours[dayName] || 
                !librarySettings.libraryHours[dayName].open || 
                !librarySettings.libraryHours[dayName].close;
        }
        
        // Find next open day
        function getNextOpenDay(date) {
            const nextDay = new Date(date);
            nextDay.setDate(nextDay.getDate() + 1);
            
            while (isLibraryClosed(nextDay)) {
                nextDay.setDate(nextDay.getDate() + 1);
            }
            
            return nextDay;
        }
        
        // Calculate expected return date based on issue date
        function calculateExpectedReturnDate(issueDate) {
            if (!issueDate) return null;
            
            const date = new Date(issueDate);
            date.setDate(date.getDate() + librarySettings.loanDays);
            
            // Find next open day if return date falls on closed day
            while (isLibraryClosed(date)) {
                date.setDate(date.getDate() + 1);
            }
            
            return date;
        }
        
        // Calculate fine amount
        function calculateFine() {
            if (!expectedReturnDateInput || !actualReturnDateInput) return;
            
            const expectedDate = new Date(expectedReturnDateInput.value);
            const actualDate = new Date(actualReturnDateInput.value);
            
            if (isNaN(expectedDate.getTime()) || isNaN(actualDate.getTime())) return;
            
            // Check if return is after hours or on closed day
            let adjustedDate = new Date(actualDate);
            const dayName = getDayName(actualDate);
            
            const isAfterHours = 
                isLibraryClosed(actualDate) || 
                (librarySettings.libraryHours[dayName]?.close && 
                actualDate.getHours() >= parseInt(librarySettings.libraryHours[dayName].close.split(':')[0]));
            
            if (isAfterHours) {
                adjustedDate = getNextOpenDay(actualDate);
            }
            
            // Calculate days late
            const timeDiff = adjustedDate.getTime() - expectedDate.getTime();
            let daysLate = Math.max(0, Math.ceil(timeDiff / (1000 * 60 * 60 * 24)));
            
            // Calculate fine amount with cap
            let fineAmount = daysLate * librarySettings.fineRatePerDay;
            fineAmount = Math.min(fineAmount, librarySettings.maxFinePerBook);
            
            // Update form fields
            if (daysLateInput) daysLateInput.value = daysLate;
            if (finesAmountInput) finesAmountInput.value = fineAmount.toFixed(2);
            
            // Show cap message if applicable
            const fineInfo = document.getElementById('fine-info');
            if (fineInfo) {
                if (daysLate * librarySettings.fineRatePerDay > librarySettings.maxFinePerBook) {
                    fineInfo.textContent = `Fine capped at maximum amount: ${librarySettings.maxFinePerBook}`;
                    fineInfo.style.display = 'block';
                } else {
                    fineInfo.style.display = 'none';
                }
            }
            
            return { daysLate, fineAmount };
        }
        
        // Initialize date inputs with validation
        if (issueDateInput) {
            // Set minimum date to today
            const today = new Date();
            issueDateInput.setAttribute('min', formatDate(today));
            
            // Update expected return date when issue date changes
            issueDateInput.addEventListener('change', function() {
                if (!returnDateInput) return;
                
                const issueDate = new Date(this.value);
                if (isNaN(issueDate.getTime())) return;
                
                const expectedReturnDate = calculateExpectedReturnDate(issueDate);
                if (expectedReturnDate) {
                    returnDateInput.value = formatDate(expectedReturnDate);
                }
            });
        }
        
        // Validate that return date is after issue date
        if (returnDateInput && issueDateInput) {
            returnDateInput.addEventListener('change', function() {
                const issueDate = new Date(issueDateInput.value);
                const returnDate = new Date(this.value);
                
                if (isNaN(issueDate.getTime()) || isNaN(returnDate.getTime())) return;
                
                if (returnDate <= issueDate) {
                    alert("Expected return date must be after the issue date");
                    this.value = '';
                }
            });
        }
        
        // Toggle book condition fields based on status
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                const status = this.value;
                
                // Show/hide book condition fields
                if (bookConditionContainer) {
                    bookConditionContainer.style.display = (status === 'Returned') ? 'block' : 'none';
                }
                
                // Show/hide fines when status is Returned or Overdue
                if (finesContainer) {
                    finesContainer.style.display = (status === 'Returned' || status === 'Overdue') ? 'block' : 'none';
                }
                
                // Add return date if status is Returned
                if (status === 'Returned' && actualReturnDateInput) {
                    actualReturnDateInput.value = formatDate(new Date());
                    calculateFine();
                }
            });
        }
        
        // Calculate fine when return date changes
        if (actualReturnDateInput) {
            actualReturnDateInput.addEventListener('change', calculateFine);
        }
        
        // Initial calculation on page load
        if (actualReturnDateInput && expectedReturnDateInput) {
            calculateFine();
        }
        
        // Trigger status change event to initialize display
        if (statusSelect) {
            const event = new Event('change');
            statusSelect.dispatchEvent(event);
        }
    });
</script>

<?php
include '../footer.php';
?>
