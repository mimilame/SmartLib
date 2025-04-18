<?php
// fines_action.php

include '../database_connection.php';
include '../function.php';
include '../header.php';

$message = '';

// Fetch library settings for fine calculation
$query = "
    SELECT library_total_book_issue_day, library_one_day_fine
    FROM lms_setting
    LIMIT 1
";
$statement = $connect->prepare($query);
$statement->execute();
$library_setting = $statement->fetch(PDO::FETCH_ASSOC);

// Get fine rate per day from library settings
$fine_rate_per_day = $library_setting['library_one_day_fine'];
$max_days_allowed = $library_setting['library_total_book_issue_day'];

// EDIT fine (Form Submit)
if (isset($_POST['edit_fine'])) {
    $fines_id = $_POST['fines_id'];
    $user_id = $_POST['user_id'];
    $issue_book_id = $_POST['issue_book_id'];
    $expected_return_date = $_POST['expected_return_date'];
    $return_date = $_POST['return_date'];
    
    // Dynamically calculate days late and fine amount
    if (!empty($return_date) && !empty($expected_return_date)) {
        $date1 = new DateTime($expected_return_date);
        $date2 = new DateTime($return_date);
        $interval = $date1->diff($date2);
        $days_late = max(0, $interval->days);
        
        // Calculate fine amount using the library setting rate
        $fines_amount = $days_late * $fine_rate_per_day;
    } else {
        $days_late = $_POST['days_late'];
        $fines_amount = $_POST['fines_amount'];
    }
    
    $fines_status = $_POST['fines_status'];

    $update_query = "
        UPDATE lms_fines 
        SET user_id = :user_id,
            issue_book_id = :issue_book_id,
            expected_return_date = :expected_return_date,
            return_date = :return_date,
            days_late = :days_late,
            fines_amount = :fines_amount,
            fines_status = :fines_status,
            fines_updated_on = :updated_on
        WHERE fines_id = :fines_id
    ";

    $params = [
        ':user_id' => $user_id,
        ':issue_book_id' => $issue_book_id,
        ':expected_return_date' => $expected_return_date,
        ':return_date' => $return_date,
        ':days_late' => $days_late,
        ':fines_amount' => $fines_amount,
        ':fines_status' => $fines_status,
        ':updated_on' => get_date_time($connect),
        ':fines_id' => $fines_id
    ];

    $statement = $connect->prepare($update_query);
    $statement->execute($params);

    header('location:fines.php?msg=edit');
    exit;
}
?>

<?php
// Edit Fine Form - Triggered by ?action=edit&code=xxx
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])):
    $id = $_GET['code'];

    // Fetching the fine data with join to get related information
    $query = "
        SELECT f.*, ib.expected_return_date, ib.return_date, u.user_name, b.book_name,
               DATEDIFF(ib.return_date, ib.expected_return_date) AS days_late
        FROM lms_fines AS f
        LEFT JOIN lms_issue_book AS ib ON f.issue_book_id = ib.issue_book_id
        LEFT JOIN lms_user AS u ON f.user_id = u.user_id
        LEFT JOIN lms_book AS b ON ib.book_id = b.book_id
        WHERE f.fines_id = :id 
        LIMIT 1
    ";
    $statement = $connect->prepare($query);
    $statement->execute([':id' => $id]);
    $fine = $statement->fetch(PDO::FETCH_ASSOC);

    // Handling error: Fine already exists
    if (isset($_GET['error']) && $_GET['error'] == 'exists'):
        echo '<div class="alert alert-danger">Fine already exists. Please choose another name.</div>';
    endif;
    ?>

    <!-- Edit Fine Form with modern layout -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Fine</h5>
                    <span class="badge bg-light text-primary">Fine ID: <?= $fine['fines_id'] ?></span>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Fine amount is calculated at <strong><?= get_currency_symbol($connect) . $fine_rate_per_day ?> per day</strong> based on days late.
                    </div>
                    
                    <form method="post" id="fineForm" class="needs-validation" novalidate>
                        <input type="hidden" name="fines_id" value="<?= $fine['fines_id'] ?>">
                        
                        <div class="row">
                            <!-- User Information -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-primary"><i class="fas fa-user me-2"></i>User Details</h6>
                                        <div class="mb-3">
                                            <label class="form-label">User Name</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($fine['user_id']) ?>">
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($fine['user_name'] ?? 'Unknown User') ?>" readonly>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Book Information</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-book"></i></span>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($fine['book_name'] ?? 'Unknown Book') ?>" readonly>
                                            </div>
                                            <div class="form-text">Issue ID: <?= htmlspecialchars($fine['issue_book_id']) ?></div>
                                            <input type="hidden" name="issue_book_id" value="<?= htmlspecialchars($fine['issue_book_id']) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Date Information -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-primary"><i class="fas fa-calendar-alt me-2"></i>Date Information</h6>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Expected Return Date</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                                <input type="date" name="expected_return_date" id="expected_return_date" 
                                                        class="form-control" value="<?= htmlspecialchars($fine['expected_return_date']) ?>" required>
                                            </div>
                                            <div class="form-text">Maximum borrowing period: <?= $max_days_allowed ?> days</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Actual Return Date</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
                                                <input type="date" name="return_date" id="return_date" 
                                                        class="form-control" value="<?= htmlspecialchars($fine['return_date']) ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Fine Calculation -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-primary"><i class="fas fa-calculator me-2"></i>Fine Calculation</h6>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Days Late</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                                <input type="number" name="days_late" id="days_late" 
                                                        class="form-control" value="<?= htmlspecialchars($fine['days_late']) ?>" readonly>
                                                <span class="input-group-text">days</span>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Fine Amount</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-money-bill"></i></span>
                                                <input type="number" name="fines_amount" id="fines_amount" step="0.01"
                                                        class="form-control" value="<?= htmlspecialchars($fine['fines_amount']) ?>" readonly>
                                                <span class="input-group-text"><?= get_currency_symbol($connect) ?></span>
                                            </div>
                                            <div class="form-text">Rate: <?= get_currency_symbol($connect) . $fine_rate_per_day ?> × Days Late</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Status -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-primary"><i class="fas fa-clipboard-check me-2"></i>Payment Status</h6>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Fine Status</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                                <select name="fines_status" class="form-select">
                                                    <option value="Paid" <?= $fine['fines_status'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                                                    <option value="Unpaid" <?= $fine['fines_status'] == 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Last Updated</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-history"></i></span>
                                                <input type="text" class="form-control" 
                                                        value="<?= date('M d, Y h:i A', strtotime($fine['fines_updated_on'])) ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="fines.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                            <div>
                                <button type="reset" class="btn btn-outline-danger me-2">
                                    <i class="fas fa-undo me-2"></i>Reset
                                </button>
                                <button type="submit" name="edit_fine" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Fine
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to calculate days late and fine amount dynamically
        function calculateFine() {
            const expectedReturnDate = new Date(document.getElementById('expected_return_date').value);
            const returnDate = new Date(document.getElementById('return_date').value);
            
            if (!isNaN(expectedReturnDate.getTime()) && !isNaN(returnDate.getTime())) {
                // Calculate difference in days
                const diffTime = returnDate - expectedReturnDate;
                const daysLate = Math.max(0, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
                
                // Update days late field
                document.getElementById('days_late').value = daysLate;
                
                // Calculate fine amount using rate from PHP
                const fineRatePerDay = <?= $fine_rate_per_day ?>;
                const fineAmount = daysLate * fineRatePerDay;
                
                // Update fine amount field
                document.getElementById('fines_amount').value = fineAmount.toFixed(2);
            }
        }
        
        // Add event listeners to recalculate on date changes
        document.getElementById('expected_return_date').addEventListener('change', calculateFine);
        document.getElementById('return_date').addEventListener('change', calculateFine);
        
        // Calculate on page load
        document.addEventListener('DOMContentLoaded', calculateFine);
        
        // Form validation
        (function() {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>

<?php
// View Fine Form - Triggered by ?action=view&code=xxx
elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])):
    $id = $_GET['code'];

    // Fetching the fine data for view with more details
    $query = "
        SELECT f.*, ib.expected_return_date, ib.return_date, b.book_name, b.book_isbn_number,
               u.user_name, u.user_email, u.user_contact_no,
               DATEDIFF(ib.return_date, ib.expected_return_date) AS days_late
        FROM lms_fines AS f
        LEFT JOIN lms_issue_book AS ib ON f.issue_book_id = ib.issue_book_id
        LEFT JOIN lms_user AS u ON f.user_id = u.user_id
        LEFT JOIN lms_book AS b ON ib.book_id = b.book_id
        WHERE f.fines_id = :id
        LIMIT 1
    ";
    $statement = $connect->prepare($query);
    $statement->execute([':id' => $id]);
    $fine = $statement->fetch(PDO::FETCH_ASSOC);

    if ($fine): 
        // Determine status class
        $statusClass = $fine['fines_status'] === 'Paid' ? 'success' : 'danger';
        $statusIcon = $fine['fines_status'] === 'Paid' ? 'check-circle' : 'exclamation-circle';
?>

    <!-- View Fine Details with enhanced information and modern layout -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Fine status banner -->
            <div class="alert alert-<?= $statusClass ?> d-flex align-items-center" role="alert">
                <i class="fas fa-<?= $statusIcon ?> me-2 fa-lg"></i>
                <div>
                    <strong>Fine Status: <?= $fine['fines_status'] ?></strong>
                    <?php if ($fine['fines_status'] === 'Unpaid'): ?>
                        - This fine needs to be collected.
                    <?php else: ?>
                        - Payment has been received.
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Fine Details</h5>
                    <div>
                        <span class="badge bg-light text-primary me-2">Fine ID: <?= $fine['fines_id'] ?></span>
                        <span class="badge bg-<?= $statusClass ?>"><?= $fine['fines_status'] ?></span>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Main Details -->
                        <div class="col-lg-6">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-primary"><i class="fas fa-info-circle me-2"></i>Fine Information</h5>
                                    <hr>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-muted">Fine Amount:</span>
                                        <span class="fs-4 fw-bold"><?= get_currency_symbol($connect) . number_format($fine['fines_amount'], 2) ?></span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Days Late:</span>
                                        <span class="badge bg-warning text-dark fs-6"><?= $fine['days_late'] ?> days</span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Fine Rate:</span>
                                        <span><?= get_currency_symbol($connect) . $fine_rate_per_day ?> per day</span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Created On:</span>
                                        <span><?= date('M d, Y h:i A', strtotime($fine['fines_created_on'])) ?></span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Last Updated:</span>
                                        <span><?= date('M d, Y h:i A', strtotime($fine['fines_updated_on'])) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Information -->
                        <div class="col-lg-6">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-primary"><i class="fas fa-user me-2"></i>User Information</h5>
                                    <hr>
                                    
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary text-white rounded-circle p-2 me-3">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($fine['user_name']) ?></h6>
                                            <small class="text-muted">User ID: <?= htmlspecialchars($fine['user_id']) ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-envelope text-muted me-2"></i>
                                            <span><?= htmlspecialchars($fine['user_email'] ?? 'N/A') ?></span>
                                        </div>
                                        
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-phone text-muted me-2"></i>
                                            <span><?= htmlspecialchars($fine['user_contact_no'] ?? 'N/A') ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Book Information -->
                        <div class="col-lg-6">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-primary"><i class="fas fa-book me-2"></i>Book Information</h5>
                                    <hr>
                                    
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary text-white rounded-circle p-2 me-3">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($fine['book_name'] ?? 'Unknown Book') ?></h6>
                                            <small class="text-muted">ISBN: <?= htmlspecialchars($fine['book_isbn_number'] ?? 'N/A') ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-bookmark text-muted me-2"></i>
                                            <span>Issue ID: <?= htmlspecialchars($fine['issue_book_id']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Date Information -->
                        <div class="col-lg-6">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-primary"><i class="fas fa-calendar-alt me-2"></i>Date Information</h5>
                                    <hr>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-muted">Expected Return:</span>
                                        <span class="badge bg-info text-dark"><?= date('M d, Y', strtotime($fine['expected_return_date'])) ?></span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-muted">Actual Return:</span>
                                        <span class="badge bg-<?= $statusClass ?>"><?= date('M d, Y', strtotime($fine['return_date'])) ?></span>
                                    </div>
                                    
                                    <div class="progress mb-3" style="height: 25px;">
                                        <div class="progress-bar bg-danger" role="progressbar" 
                                                style="width: <?= min(100, $fine['days_late'] * 10) ?>%;" 
                                                aria-valuenow="<?= $fine['days_late'] ?>" aria-valuemin="0" aria-valuemax="30">
                                            <?= $fine['days_late'] ?> Days Late
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="fines.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to List
                        </a>
                        <div>
                            <a href="fines_action.php?action=edit&code=<?= $fine['fines_id'] ?>" class="btn btn-outline-primary me-2">
                                <i class="fas fa-edit me-2"></i>Edit Fine
                            </a>
                            <?php if ($fine['fines_status'] === 'Unpaid'): ?>
                                <button type="button" class="btn btn-success mark-as-paid" data-id="<?= $fine['fines_id'] ?>">
                                    <i class="fas fa-check-circle me-2"></i>Mark as Paid
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary" disabled>
                                    <i class="fas fa-check-circle me-2"></i>Already Paid
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Print Receipt Button -->
            <?php if ($fine['fines_status'] === 'Paid'): ?>
            <div class="text-center mt-4">
                <button class="btn btn-outline-primary" onclick="printReceipt()">
                    <i class="fas fa-print me-2"></i>Print Receipt
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Script for Mark as Paid button with improved UX
        document.addEventListener('DOMContentLoaded', function() {
            const markAsPaidButton = document.querySelector('.mark-as-paid');
            
            if (markAsPaidButton) {
                markAsPaidButton.addEventListener('click', function() {
                    const fineId = this.getAttribute('data-id');
                    
                    Swal.fire({
                        title: 'Confirm Payment',
                        text: "Are you sure you want to mark this fine as paid?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-check me-2"></i>Yes, mark as paid',
                        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancel',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading state
                            Swal.fire({
                                title: 'Processing...',
                                html: 'Updating payment status',
                                timerProgressBar: true,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                            
                            // Create form and submit
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'fines_action.php?action=edit&code=' + fineId;
                            
                            // Add all necessary hidden fields
                            const fieldsToInclude = [
                                { name: 'fines_id', value: '<?= $fine['fines_id'] ?>' },
                                { name: 'user_id', value: '<?= $fine['user_id'] ?>' },
                                { name: 'issue_book_id', value: '<?= $fine['issue_book_id'] ?>' },
                                { name: 'expected_return_date', value: '<?= $fine['expected_return_date'] ?>' },
                                { name: 'return_date', value: '<?= $fine['return_date'] ?>' },
                                { name: 'days_late', value: '<?= $fine['days_late'] ?>' },
                                { name: 'fines_amount', value: '<?= $fine['fines_amount'] ?>' },
                                { name: 'fines_status', value: 'Paid' },
                                { name: 'edit_fine', value: '1' }
                            ];
                            
                            fieldsToInclude.forEach(field => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = field.name;
                                input.value = field.value;
                                form.appendChild(input);
                            });
                            
                            // Append form to body and submit
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                });
            }
        });
        
        // Print receipt function
        function printReceipt() {
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Fine Receipt</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            margin: 0;
                            padding: 20px;
                            color: #333;
                        }
                        .receipt {
                            max-width: 800px;
                            margin: 0 auto;
                            border: 1px solid #ddd;
                            padding: 30px;
                            box-shadow: 0 0 10px rgba(0,0,0,0.1);
                        }
                        .header {
                            text-align: center;
                            margin-bottom: 30px;
                            padding-bottom: 20px;
                            border-bottom: 2px solid #ddd;
                        }
                        .logo {
                            font-size: 24px;
                            font-weight: bold;
                            color: #333;
                            margin-bottom: 10px;
                        }
                        h2 {
                            color: #333;
                            margin-bottom: 5px;
                        }
                        .receipt-id {
                            font-size: 14px;
                            color: #666;
                        }
                        .payment-status {
                            display: inline-block;
                            padding: 5px 15px;
                            background-color: #28a745;
                            color: white;
                            border-radius: 4px;
                            margin-top: 10px;
                        }
                        .section {
                            margin-bottom: 20px;
                        }
                        .section-title {
                            font-weight: bold;
                            margin-bottom: 10px;
                            color: #555;
                            border-bottom: 1px solid #eee;
                            padding-bottom: 5px;
                        }
                        .info-row {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 5px;
                        }
                        .info-label {
                            font-weight: normal;
                            color: #666;
                        }
                        .info-value {
                            font-weight: bold;
                        }
                        .total {
                            font-size: 18px;
                            font-weight: bold;
                            margin-top: 20px;
                            text-align: right;
                            padding-top: 10px;
                            border-top: 2px solid #ddd;
                        }
                        .footer {
                            margin-top: 40px;
                            text-align: center;
                            font-size: 14px;
                            color: #777;
                            padding-top: 20px;
                            border-top: 1px solid #eee;
                        }
                        .signature {
                            margin-top: 60px;
                            display: flex;
                            justify-content: space-between;
                        }
                        .signature-line {
                            width: 200px;
                            border-top: 1px solid #333;
                            margin-top: 10px;
                            text-align: center;
                        }
                        @media print {
                            body {
                                padding: 0;
                            }
                            .print-button {
                                display: none;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="receipt">
                        <div class="header">
                            <div class="logo">Library Management System</div>
                            <h2>Fine Payment Receipt</h2>
                            <div class="receipt-id">Receipt #: F-${Date.now().toString().substr(-6)}</div>
                            <div class="payment-status">PAID</div>
                        </div>
                        
                        <div class="section">
                            <div class="section-title">Fine Details</div>
                            <div class="info-row">
                                <span class="info-label">Fine ID:</span>
                                <span class="info-value">${<?php echo json_encode($fine['fines_id']); ?>}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Amount Paid:</span>
                                <span class="info-value">${<?php echo json_encode(get_currency_symbol($connect)); ?>}${<?php echo json_encode(number_format($fine['fines_amount'], 2)); ?>}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Payment Date:</span>
                                <span class="info-value">${<?php echo json_encode(date('F d, Y', strtotime($fine['fines_updated_on']))); ?>}</span>
                            </div>
                        </div>
                        
                        <div class="section">
                            <div class="section-title">User Information</div>
                            <div class="info-row">
                                <span class="info-label">Name:</span>
                                <span class="info-value">${<?php echo json_encode(htmlspecialchars($fine['user_name'])); ?>}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">User ID:</span>
                                <span class="info-value">${<?php echo json_encode(htmlspecialchars($fine['user_id'])); ?>}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Contact:</span>
                                <span class="info-value">${<?php echo json_encode(htmlspecialchars($fine['user_contact_no'] ?? 'N/A')); ?>}</span>
                            </div>
                        </div>
                        
                        <div class="section">
                            <div class="section-title">Book Information</div>
                            <div class="info-row">
                                <span class="info-label">Book Title:</span>
                                <span class="info-value">${<?php echo json_encode(htmlspecialchars($fine['book_name'] ?? 'Unknown Book')); ?>}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">ISBN:</span>
                                <span class="info-value">${<?php echo json_encode(htmlspecialchars($fine['book_isbn_number'] ?? 'N/A')); ?>}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Issue ID:</span>
                                <span class="info-value">${<?php echo json_encode(htmlspecialchars($fine['issue_book_id'])); ?>}</span>
                            </div>
                        </div>
                        
                        <div class="section">
                            <div class="section-title">Late Return Details</div>
                            <div class="info-row">
                                <span class="info-label">Expected Return Date:</span>
                                <span class="info-value">${<?php echo json_encode(date('F d, Y', strtotime($fine['expected_return_date']))); ?>}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Actual Return Date:</span>
                                <span class="info-value">${<?php echo json_encode(date('F d, Y', strtotime($fine['return_date']))); ?>}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Days Late:</span>
                                <span class="info-value">${<?php echo json_encode($fine['days_late']); ?>} days</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Fine Rate:</span>
                                <span class="info-value">${<?php echo json_encode(get_currency_symbol($connect)); ?>}${<?php echo json_encode($fine_rate_per_day); ?>} per day</span>
                            </div>
                        </div>
                        
                        <div class="total">
                            Total Amount Paid: ${<?php echo json_encode(get_currency_symbol($connect)); ?>}${<?php echo json_encode(number_format($fine['fines_amount'], 2)); ?>}
                        </div>
                        
                        <div class="signature">
                            <div>
                                <div class="signature-line">Librarian Signature</div>
                            </div>
                            <div>
                                <div class="signature-line">User Signature</div>
                            </div>
                        </div>
                        
                        <div class="footer">
                            <p>Thank you for your payment. This is an official receipt for your library fine payment.</p>
                            <p>Printed on: ${new Date().toLocaleString()}</p>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4 print-button">
                        <button onclick="window.print();" class="btn btn-primary">Print Receipt</button>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            setTimeout(function() {
                printWindow.focus();
                // Give time for resources to load then print
                setTimeout(function() { printWindow.print(); }, 500);
            }, 250);
        }
    </script>

<?php 
    else: 
?>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Record Not Found</h5>
                </div>
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-search fa-4x text-muted"></i>
                    </div>
                    <h4>Fine Not Found</h4>
                    <p class="text-muted">The requested fine record does not exist or may have been deleted.</p>
                    <a href="fines.php" class="btn btn-outline-primary mt-3">
                        <i class="fas fa-arrow-left me-2"></i>Back to Fines List
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php 
    endif;
endif;
?>