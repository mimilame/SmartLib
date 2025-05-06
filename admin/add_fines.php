<?php
// add_fine.php

include '../database_connection.php';
include '../function.php';
include '../header.php';
authenticate_admin();

$message = '';

// Function to get issue details - add this if it doesn't exist in function.php
if (!function_exists('getIssueDetails')) {
    function getIssueDetails($connect, $issue_book_id) {
        $query = "
            SELECT i.issue_book_id, i.book_id, i.user_id, i.issue_date, i.expected_return_date, 
                b.book_name, u.user_name, i.issue_book_status
            FROM lms_issue_book i
            JOIN lms_book b ON i.book_id = b.book_id
            JOIN lms_user u ON i.user_id = u.user_id
            WHERE i.issue_book_id = :issue_book_id
        ";
        $statement = $connect->prepare($query);
        $statement->execute([':issue_book_id' => $issue_book_id]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }
}

// Handle AJAX request for issue details
if (isset($_GET['get_issue_details']) && isset($_GET['issue_book_id'])) {
    $issue_book_id = $_GET['issue_book_id'];
    
    // Validate the input
    if (!is_numeric($issue_book_id)) {
        echo json_encode(['error' => 'Invalid issue book ID']);
        exit;
    }
    
    $query = "
        SELECT i.issue_book_id, i.book_id, i.user_id, i.issue_date, i.expected_return_date, 
            b.book_name, u.user_name, i.issue_book_status
        FROM lms_issue_book i
        JOIN lms_book b ON i.book_id = b.book_id
        JOIN lms_user u ON i.user_id = u.user_id
        WHERE i.issue_book_id = :issue_book_id
    ";
    $statement = $connect->prepare($query);
    $statement->execute([':issue_book_id' => $issue_book_id]);
    $issueDetails = $statement->fetch(PDO::FETCH_ASSOC);
    
    // Set content type header
    header('Content-Type: application/json');
    
    echo json_encode($issueDetails);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_fine'])) {
    $issue_book_id = $_POST['issue_book_id'];
    $fines_amount = $_POST['fines_amount'];
    $fines_status = $_POST['fines_status'] ?? 'Unpaid';

    // Basic checks
    if (!empty($issue_book_id) && is_numeric($fines_amount) && $fines_amount > 0) {
        // Check if issue_book_id is really LOST and valid
        $stmt = $connect->prepare("SELECT expected_return_date, user_id, issue_book_status FROM lms_issue_book WHERE issue_book_id = :issue_book_id");
        $stmt->execute([':issue_book_id' => $issue_book_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['issue_book_status'] === 'Lost') {
            $expected_return_date = $row['expected_return_date'];
            $user_id = $row['user_id'];

            // Check for duplicate fine entry
            $checkStmt = $connect->prepare("SELECT COUNT(*) FROM lms_fines WHERE issue_book_id = :issue_book_id");
            $checkStmt->execute([':issue_book_id' => $issue_book_id]);
            $existing = $checkStmt->fetchColumn();

            if ($existing > 0) {
                $message = '<div class="alert alert-warning">A fine already exists for this issue.</div>';
            } else {
                // Insert into fines
                $insert = $connect->prepare("
                    INSERT INTO lms_fines (issue_book_id, fines_amount, expected_return_date, fines_status, user_id)
                    VALUES (:issue_book_id, :fines_amount, :expected_return_date, :fines_status, :user_id)
                ");
                
                if ($insert->execute([
                    ':issue_book_id' => $issue_book_id,
                    ':fines_amount' => $fines_amount,
                    ':expected_return_date' => $expected_return_date,
                    ':fines_status' => $fines_status,
                    ':user_id' => $user_id
                ])) {
                    $_SESSION['fine_added'] = true;
                    header("Location: fines.php");
                    exit();
                } else {
                    $message = '<div class="alert alert-danger">Error adding fine. Please try again.</div>';
                }
            }
        } else {
            $message = '<div class="alert alert-danger">Selected issue is not valid or not marked as Lost.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Please select a valid issue and enter a fine amount greater than 0.</div>';
    }
}

// Fetch lost books that don't already have fines
function getLostBookIssuesWithoutFines($connect) {
    $query = "
        SELECT i.issue_book_id, i.book_id, i.user_id, i.issue_date, i.expected_return_date, 
               b.book_name, u.user_name
        FROM lms_issue_book i
        JOIN lms_book b ON i.book_id = b.book_id
        JOIN lms_user u ON i.user_id = u.user_id
        WHERE i.issue_book_status = 'Lost'
        AND NOT EXISTS (
            SELECT 1 FROM lms_fines f WHERE f.issue_book_id = i.issue_book_id
        )
        ORDER BY i.issue_book_id DESC
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch lost books without fines
$lost_issues = getLostBookIssuesWithoutFines($connect);
$selected_issue = null;

// Get issue details if one is selected
if (isset($_POST['issue_book_id']) && !empty($_POST['issue_book_id'])) {
    $selected_issue = getIssueDetails($connect, $_POST['issue_book_id']);
}
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center my-4">
        <h1 class="">Fines Management</h1>
    </div>

    <!-- Add Fine Form for Lost Book -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Add New Fine</h5>
                <a href="fines.php" class="btn btn-sm btn-light">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <?= $message ?>
            <?php endif; ?>

            <form method="post" class="needs-validation" novalidate>
                <div class="row">
                    <!-- Left Column: Book Selection -->
                    <div class="col-md-6">
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-book me-2"></i>Issue Selection</h6>
                            </div>
                            <div class="card-body">
                                <!-- Fined Book Dropdown -->
                                <div class="mb-3">
                                    <label for="issue_book_id" class="form-label fw-bold">Select Lost Book Issue</label>
                                    <select name="issue_book_id" id="issue_book_id" class="form-select select2" required>
                                        <option value="">Select Issue</option>
                                        <?php foreach ($lost_issues as $issue): ?>
                                            <?php $selected = (isset($_POST['issue_book_id']) && $_POST['issue_book_id'] == $issue['issue_book_id']) ? 'selected' : ''; ?>
                                            <option value="<?= $issue['issue_book_id'] ?>" 
                                                    data-user="<?= htmlspecialchars($issue['user_name']) ?>"
                                                    data-book="<?= htmlspecialchars($issue['book_name']) ?>"
                                                    data-issue-date="<?= htmlspecialchars($issue['issue_date']) ?>"
                                                    data-return-date="<?= htmlspecialchars($issue['expected_return_date']) ?>"
                                                    <?= $selected ?>>
                                                <?= "Issue #{$issue['issue_book_id']} - {$issue['book_name']} ({$issue['user_name']})" ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select an issue entry.</div>
                                </div>

                                <!-- Summary Details -->
                                <div class="mt-4">
                                    <h6 class="fw-bold mb-3">Issue Summary</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th>Book</th>
                                                    <td id="book_name"><?= $selected_issue ? htmlspecialchars($selected_issue['book_name']) : '-' ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Issue Date</th>
                                                    <td id="issue_date"><?= $selected_issue ? htmlspecialchars($selected_issue['issue_date']) : '-' ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Expected Return</th>
                                                    <td id="expected_return_date"><?= $selected_issue ? htmlspecialchars($selected_issue['expected_return_date']) : '-' ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Status</th>
                                                    <td><span class="badge bg-danger" id="status">Lost</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Fine Details -->
                    <div class="col-md-6">
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-money-bill me-2"></i>Fine Details</h6>
                            </div>
                            <div class="card-body">
                                <!-- Fines Input -->
                                <div class="mb-4">
                                    <label for="fines_amount" class="form-label fw-bold">Fine Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" name="fines_amount" id="fines_amount" class="form-control" min="0" step="0.01" required value="<?= isset($_POST['fines_amount']) ? htmlspecialchars($_POST['fines_amount']) : '' ?>">
                                    </div>
                                    <div class="invalid-feedback">Please enter a valid fine amount.</div>
                                </div>

                                <!-- Payment Status Input -->
                                <div class="mb-4">
                                    <label for="fines_status" class="form-label fw-bold">Payment Status</label>
                                    <select name="fines_status" id="fines_status" class="form-select" required>
                                        <option value="Unpaid" <?= (isset($_POST['fines_status']) && $_POST['fines_status'] == 'Unpaid') ? 'selected' : '' ?>>Unpaid</option>
                                        <option value="Paid" <?= (isset($_POST['fines_status']) && $_POST['fines_status'] == 'Paid') ? 'selected' : '' ?>>Paid</option>
                                    </select>
                                    <div class="invalid-feedback">Please select the payment status.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                    <button type="submit" name="save_fine" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Issue Fine
                    </button>
                    <a href="fines.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const issueSelect = document.getElementById('issue_book_id');
    
    // Enable select2 if available
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            placeholder: "Select an issue",
            width: '100%'
        });
        
        // Handle select2 selection
        $('.select2').on('select2:select', function(e) {
            updateIssueSummary(this.value);
        });
    }
    
    // Also handle regular change event for fallback
    issueSelect.addEventListener('change', function() {
        updateIssueSummary(this.value);
    });
    
    // Function to update the issue summary
    function updateIssueSummary(issueBookId) {
        if (!issueBookId) {
            // Clear the fields if no selection
            document.getElementById('user_name').textContent = '-';
            document.getElementById('book_name').textContent = '-';
            document.getElementById('issue_date').textContent = '-';
            document.getElementById('expected_return_date').textContent = '-';
            return;
        }
        
        // Always use AJAX to get the most up-to-date data
        fetch(`add_fine.php?get_issue_details=1&issue_book_id=${issueBookId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data) {
                    document.getElementById('user_name').textContent = data.user_name || '-';
                    document.getElementById('book_name').textContent = data.book_name || '-';
                    document.getElementById('issue_date').textContent = data.issue_date || '-';
                    document.getElementById('expected_return_date').textContent = data.expected_return_date || '-';
                }
            })
            .catch(error => {
                console.error('Error fetching issue details:', error);
                
                // Fallback to data attributes if AJAX fails
                const selectedOption = issueSelect.options[issueSelect.selectedIndex];
                
                if (selectedOption) {
                    document.getElementById('user_name').textContent = selectedOption.dataset.user || '-';
                    document.getElementById('book_name').textContent = selectedOption.dataset.book || '-';
                    document.getElementById('issue_date').textContent = selectedOption.dataset.issueDate || '-';
                    document.getElementById('expected_return_date').textContent = selectedOption.dataset.returnDate || '-';
                }
            });
    }
    
    // Form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
    
    // Trigger initial load if value is pre-selected
    if (issueSelect.value) {
        updateIssueSummary(issueSelect.value);
    }
});
</script>

<?php if (isset($_SESSION['fine_added'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Fine Added',
        text: 'The fine has been successfully added for the lost book!',
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'OK'
    });
</script>
<?php unset($_SESSION['fine_added']); endif; ?>

<?php include '../footer.php'; ?>