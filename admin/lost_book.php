<?php

// lost_book.php - For Lost Books

include '../database_connection.php';
include '../function.php';
include '../header.php';
authenticate_admin();


// Fetch lost book record if editing
$issue = [];

if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['code'])) {
    $issue_book_id = $_GET['code'];

    // Fetch the lost book details with fines from the database
    $query = "
        SELECT 
            ib.issue_book_id, 
            ib.book_id, 
            ib.user_id, 
            ib.return_date AS reported_date,
            b.book_name,
            u.user_name,
            f.fines_amount,
            f.fines_status 
        FROM lms_issue_book ib 
        LEFT JOIN lms_book b ON ib.book_id = b.book_id 
        LEFT JOIN lms_user u ON ib.user_id = u.user_id 
        LEFT JOIN lms_fines f ON f.issue_book_id = ib.issue_book_id 
        WHERE ib.issue_book_id = :issue_book_id
    ";

    $stmt = $connect->prepare($query);
    $stmt->execute([':issue_book_id' => $issue_book_id]);
    $issue = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Redirect if record not found
if (!$issue) {
    $_SESSION['error_message'] = 'Lost book record not found';
    header('Location: return_book.php?tab=lost');
    exit();
}

// Process the edit form submission
if (isset($_POST['edit_lost_book'])) {
    $issue_book_id = $_POST['issue_book_id'];
    $book_id = $_POST['book_id'];
    $user_id = $_POST['user_id'];
    $fines_amount = $_POST['fines_amount'] ?? 0;
    $fines_status = $_POST['fines_status'] ?? 'Unpaid';
    $reported_date = $_POST['reported_date'];

    // Update the fines table
    $query = "
        UPDATE lms_fines 
        SET fines_amount = :fines_amount,
            fines_status = :fines_status,
            updated_on = NOW()
        WHERE issue_book_id = :issue_book_id
    ";
    
    $statement = $connect->prepare($query);
    $statement->execute([
        ':fines_amount' => $fines_amount,
        ':fines_status' => $fines_status,
        ':issue_book_id' => $issue_book_id
    ]);

    // Update the return date in the issue_book table
    $query = "
        UPDATE lms_issue_book 
        SET return_date = :reported_date,
            updated_on = NOW()
        WHERE issue_book_id = :issue_book_id
    ";
    
    $statement = $connect->prepare($query);
    $statement->execute([
        ':reported_date' => $reported_date,
        ':issue_book_id' => $issue_book_id
    ]);

    $_SESSION['success_message'] = 'Lost book record has been updated';
    header('Location: return_book.php?tab=lost');
    exit();
}

// Edit a lost book record
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])): ?>   
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
        <!-- Edit Lost Book Form with Modern UI -->
         <div class="py-4">
    <div class="d-flex justify-content-between align-items-center my-4">
        <h1 class="text-dark">Return/Lost Book Management</h1>
    </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-gradient-danger text-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-edit me-2"></i>Edit Lost Book Record</h5>
                            <a href="return_book.php?tab=lost" class="btn btn-sm btn-light">
                                <i class="fas fa-arrow-left me-1"></i>Back to List
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="lost_book.php" class="needs-validation" novalidate>
                            <input type="hidden" name="issue_book_id" value="<?= $issue['issue_book_id'] ?>">
                            <input type="hidden" name="book_id" value="<?= $issue['book_id'] ?>">
                            <input type="hidden" name="user_id" value="<?= $issue['user_id'] ?>">


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
                            
                            <!-- Lost Book Form -->
                            <div class="col-md-8">
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
                                                <div class="col-md-6">
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
                                                <div class="col-md-6">
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
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Reported Lost Date</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light">
                                                                <i class="fas fa-calendar-check text-danger"></i>
                                                            </span>
                                                            <input type="datetime-local" name="reported_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label fw-bold">Status</label>
            <div class="input-group">
                <span class="input-group-text bg-light">
                    <i class="fas fa-calendar-check text-danger"></i>
                </span>
                <select name="status" class="form-select" id="status" onchange="toggleReturnDate()" required>
                    <option value="Lost" selected>Lost</option>
                    <option value="Returned">Returned</option>
                </select>
            </div>
        </div>
    </div>
    <div class="col-md-6" id="returnDateContainer" style="display: none;">
        <div class="mb-3">
            <label class="form-label fw-bold">Return Date</label>
            <div class="input-group">
                <span class="input-group-text bg-light">
                    <i class="fas fa-calendar text-primary"></i>
                </span>
                <input type="datetime-local" name="return_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" readonly>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleReturnDate() {
        const status = document.getElementById('status').value;
        const returnDateContainer = document.getElementById('returnDateContainer');
        if (status === 'Returned') {
            returnDateContainer.style.display = 'block';
        } else {
            returnDateContainer.style.display = 'none';
        }
    }

    // Initial load to ensure correct display
    document.addEventListener('DOMContentLoaded', function() {
        toggleReturnDate();
    });
</script>

                                        </div>
                                    </div>
                                    
                    
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-end mt-4">
                                        <button type="submit" name="edit_lost_book" class="btn btn-primary">
                                            <i class="fas fa-check-circle me-2"></i>Update Record
                                        </button>
                                        <a href="return_book.php?tab=lost" class="btn btn-outline-secondary">
                                            <i class="fas fa-times-circle me-2"></i>Cancel
                                        </a>
                                    </div>
                            </div>
                        </div>
                        </form>
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
                        text: 'The lost book record you are looking for does not exist.',
                        confirmButtonText: 'Back to Lost Books',
                        confirmButtonColor: '#6c757d',
                        timer: 2000,
                        customClass: {
                            confirmButton: 'btn btn-outline-secondary'
                        },
                        buttonsStyling: false
                    }).then((result) => {
                        window.location.href = 'return_book.php?tab=lost';
                    });
                });
            </script>
        <?php endif; ?>

<?php 
// View lost book details
elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])): ?> 
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
            
        <!-- View Lost Book Form with Modern UI -->
         <div class="py-4">
    <div class="d-flex justify-content-between align-items-center my-4">
        <h1 class="text-black">Return/Lost Book Management</h1>
    </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-gradient-danger text-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-eye me-2"></i>View Lost Book Details</h5>
                            <a href="return_book.php?tab=lost" class="btn btn-sm btn-light">
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

                            <!-- Borrower and Loss Info -->
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
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-2"><strong>Issue Date:</strong> <?= date('F d, Y h:i A', strtotime($issue['issue_date'])) ?></p>
                                                <p class="mb-2"><strong>Status:</strong> <span class="badge bg-danger">Lost</span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 text-primary"><i class="fas fa-money-bill-alt me-2"></i>Fine Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2"><strong>Fine Amount:</strong> 
                                            <?php if (!empty($issue['fines_amount'])): ?>
                                                ₱ <?= number_format($issue['fines_amount'], 2) ?>
                                            <?php else: ?>
                                                No Fine Charged
                                            <?php endif; ?>
                                        </p>
                                        <p class="mb-0"><strong>Payment Status:</strong> 
                                            <?= !empty($issue['fines_status']) ? htmlspecialchars($issue['fines_status']) : 'Unpaid' ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <a href="lost_book.php?action=edit&code=<?= $issue['issue_book_id'] ?>" class="btn btn-primary">
                                        <i class="fas fa-edit me-2"></i>Edit Record
                                    </a>
                                    <a href="return_book.php?tab=lost" class="btn btn-outline-secondary">
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
                    text: 'The lost book record you are looking for does not exist.',
                    confirmButtonText: 'Back to Lost Books',
                    confirmButtonColor: '#6c757d',
                    timer: 2000,
                    customClass: {
                        confirmButton: 'btn btn-outline-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    window.location.href = 'return_book.php?tab=lost';
                });
            });
        </script>
        <?php endif; ?>

<?php endif; ?>

<?php include '../footer.php'; ?>