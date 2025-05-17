<?php
// review_form.php - Displays the review form via AJAX
include '../database_connection.php';
include '../function.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>You must be logged in to write a review.</div>';
    exit;
}

// Get book ID from request
$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

if ($book_id <= 0) {
    echo '<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>Invalid book ID.</div>';
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check if book exists
$query = "SELECT book_id, book_name FROM lms_book WHERE book_id = :book_id AND book_status = 'Enable'";
$statement = $connect->prepare($query);
$statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
$statement->execute();
$book = $statement->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    echo '<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>Book not found or not available for review.</div>';
    exit;
}

// Check if user has already reviewed this book
$query = "SELECT * FROM lms_book_review WHERE book_id = :book_id AND user_id = :user_id";
$statement = $connect->prepare($query);
$statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
$statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$statement->execute();
$existing_review = $statement->fetch(PDO::FETCH_ASSOC);

if ($existing_review) {
    // Display existing review with status
    $status_class = '';
    $status_text = '';
    
    switch ($existing_review['status']) {
        case 'pending':
            $status_class = 'alert-info';
            $status_text = 'Your review is pending approval.';
            break;
        case 'approved':
            $status_class = 'alert-success';
            $status_text = 'Your review has been approved.';
            break;
        case 'rejected':
            $status_class = 'alert-danger';
            $status_text = 'Your review was not approved.';
            if (!empty($existing_review['remarks'])) {
                $status_text .= ' Reason: ' . htmlspecialchars($existing_review['remarks']);
            }
            break;
        case 'flagged':
            $status_class = 'alert-warning';
            $status_text = 'Your review has been flagged for review.';
            break;
    }
    
    echo '<div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Your Review</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="rating-display">
                        ';
                        for ($i = 1; $i <= 5; $i++) {
                            echo '<i class="bi ' . ($i <= $existing_review['rating'] ? 'bi-star-fill' : 'bi-star') . ' text-warning"></i>';
                        }
                        echo '
                    </div>
                    <small class="text-muted">Submitted on ' . date('F j, Y', strtotime($existing_review['created_at'])) . '</small>
                </div>
                <p class="card-text">' . nl2br(htmlspecialchars($existing_review['review_text'])) . '</p>
                
                <div class="alert ' . $status_class . ' mt-3 mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    ' . $status_text . '
                </div>
            </div>
        </div>';
    exit;
}

// Check if user has borrowed and returned this book
$query = "SELECT issue_book_status FROM lms_issue_book 
          WHERE book_id = :book_id 
          AND user_id = :user_id 
          ORDER BY issue_book_returned_date DESC, issue_book_issue_date DESC
          LIMIT 1";
$statement = $connect->prepare($query);
$statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
$statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$statement->execute();
$issue_result = $statement->fetch(PDO::FETCH_ASSOC);

// If user hasn't borrowed or hasn't returned the book
if (!$issue_result) {
    echo '<div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            You need to borrow this book before you can review it.
          </div>';
    exit;
} elseif ($issue_result['issue_book_status'] != 'Return') {
    echo '<div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            You can write a review after you return this book.
          </div>';
    exit;
}

// User is eligible to submit a review
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Write a Review</h5>
        <span class="badge bg-success">You've read this book</span>
    </div>
    <div class="card-body">
        <form id="review-form" action="submit_review.php" method="post">
            <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
            
            <div class="mb-4">
                <label for="rating" class="form-label fw-bold">Your Rating</label>
                <div class="star-rating">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" id="rating-<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo ($i == 5) ? 'checked' : ''; ?>>
                        <label for="rating-<?php echo $i; ?>"><i class="bi bi-star-fill"></i></label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="review-text" class="form-label fw-bold">Your Review</label>
                <textarea class="form-control" id="review-text" name="review_text" rows="4" placeholder="Share your thoughts about '<?php echo htmlspecialchars($book['book_name']); ?>'" required></textarea>
                <div class="form-text">
                    <i class="bi bi-info-circle me-1"></i> Your review will be visible to other users after approval by a librarian.
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-2"></i>Submit Review
                </button>
                <button type="button" id="cancel-review" class="btn btn-outline-secondary ms-2">
                    <i class="bi bi-x me-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>
</div>
