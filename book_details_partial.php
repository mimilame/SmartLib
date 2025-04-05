<?php
// book_details_partial.php - For loading book details via AJAX
include 'database_connection.php';
include 'function.php';

// Only include database connection and function files
// Don't include header/footer since this is loaded in a modal

$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

if ($book_id <= 0) {
    echo '<div class="alert alert-danger m-3">Invalid book ID.</div>';
    exit;
}

// Get book details
$book_query = "SELECT b.*, c.category_name 
              FROM lms_book b
              LEFT JOIN lms_category c ON b.category_id = c.category_id
              WHERE b.book_id = :book_id";
$book_statement = $connect->prepare($book_query);
$book_statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
$book_statement->execute();
$book = $book_statement->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    echo '<div class="alert alert-danger m-3">Book not found.</div>';
    exit;
}

// Get authors
$author_query = "SELECT a.* FROM lms_author a 
                JOIN lms_book_author ba ON a.author_id = ba.author_id 
                WHERE ba.book_id = :book_id";
$author_statement = $connect->prepare($author_query);
$author_statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
$author_statement->execute();
$authors = $author_statement->fetchAll(PDO::FETCH_ASSOC);

// Get borrow history (only for admins)
$borrow_history = [];
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
    $history_query = "SELECT ib.*, u.user_name
                     FROM lms_issue_book ib
                     JOIN lms_user u ON ib.user_id = u.user_id
                     WHERE ib.book_id = :book_id
                     ORDER BY ib.issue_date DESC";
    $history_statement = $connect->prepare($history_query);
    $history_statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
    $history_statement->execute();
    $borrow_history = $history_statement->fetchAll(PDO::FETCH_ASSOC);
}

// Get similar books by category
$similar_query = "SELECT b.* FROM lms_book b
                 WHERE b.category_id = :category_id
                 AND b.book_id != :book_id
                 AND b.book_status = 'Enable'
                 ORDER BY b.book_id DESC
                 LIMIT 6";
$similar_statement = $connect->prepare($similar_query);
$similar_statement->bindParam(':category_id', $book['category_id'], PDO::PARAM_INT);
$similar_statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
$similar_statement->execute();
$similar_books = $similar_statement->fetchAll(PDO::FETCH_ASSOC);

// Get books by same author
$author_books_query = "SELECT DISTINCT b.* FROM lms_book b
                      JOIN lms_book_author ba ON b.book_id = ba.book_id
                      JOIN lms_book_author ba2 ON ba.author_id = ba2.author_id
                      WHERE ba2.book_id = :book_id
                      AND b.book_id != :book_id
                      AND b.book_status = 'Enable'
                      ORDER BY b.book_id DESC
                      LIMIT 6";
$author_books_statement = $connect->prepare($author_books_query);
$author_books_statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
$author_books_statement->execute();
$author_books = $author_books_statement->fetchAll(PDO::FETCH_ASSOC);

// Get book reviews
/* $reviews_query = "SELECT r.*, u.user_name
                 FROM lms_book_review r
                 JOIN lms_user u ON r.user_id = u.user_id
                 WHERE r.book_id = :book_id
                 ORDER BY r.created_at DESC";
$reviews_statement = $connect->prepare($reviews_query);
$reviews_statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
$reviews_statement->execute();
$reviews = $reviews_statement->fetchAll(PDO::FETCH_ASSOC); */

// Get book availability status
$query = "SELECT COUNT(*) as borrowed_copies 
         FROM lms_issue_book 
         WHERE book_id = :book_id 
         AND (issue_book_status = 'Issue' OR issue_book_status = 'Not Return')";
$statement = $connect->prepare($query);
$statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);

$borrowed_copies = $result['borrowed_copies'];
$available_copies = $book['book_no_of_copy'] - $borrowed_copies;
$is_available = $available_copies > 0;

// Get borrow frequency
$query = "SELECT COUNT(*) as borrow_count 
         FROM lms_issue_book 
         WHERE book_id = :book_id";
$statement = $connect->prepare($query);
$statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
$borrow_count = $result['borrow_count'];

// Format authors as a comma-separated string
$author_names = array_map(function($author) {
    return $author['author_name'];
}, $authors);
$author_string = implode(', ', $author_names);

// Get book cover image
$book_img = !empty($book['book_img']) ? 'asset/img/' . $book['book_img'] : 'asset/img/book_placeholder.png';
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Book Cover Column (Sticky) -->
        <div class="col-md-4 bg-light">
            <div class="sticky-top" style="top: 1rem;">
                <div class="p-4 text-center">
                    <img src="<?php echo $book_img; ?>" alt="<?php echo htmlspecialchars($book['book_name']); ?>" class="img-fluid rounded shadow" style="max-height: 400px;">
                    
                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge <?php echo $is_available ? 'bg-success' : 'bg-danger'; ?> p-2">
                                <i class="bi <?php echo $is_available ? 'bi-check-circle' : 'bi-x-circle'; ?>"></i> 
                                <?php echo $is_available ? 'Available' : 'Unavailable'; ?>
                            </span>
                            <span class="badge bg-info p-2">
                                <i class="bi bi-book"></i> <?php echo $available_copies; ?>/<?php echo $book['book_no_of_copy']; ?> copies
                            </span>
                        </div>
                        
                        <?php if ($is_available && isset($_SESSION['user_id'])): ?>
                        <a href="issue_book.php?book_id=<?php echo $book_id; ?>" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-journal-arrow-down"></i> Borrow This Book
                        </a>
                        <?php else: ?>
                        <button class="btn btn-secondary w-100 mb-2" disabled>
                            <i class="bi bi-lock"></i> Currently Unavailable
                        </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-outline-primary w-100" data-bs-dismiss="modal">
                            <i class="bi bi-arrow-left"></i> Back to Catalog
                        </button>
                    </div>
                    
                    <hr>
                    
                    <div class="text-start">
                        <h6 class="fw-bold">Book Information</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                <span>ISBN:</span>
                                <span class="text-muted"><?php echo htmlspecialchars($book['book_isbn_number']); ?></span>
                            </li>
                            <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                <span>Category:</span>
                                <span class="text-muted"><?php echo htmlspecialchars($book['category_name']); ?></span>
                            </li>
                            <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                <span>Publisher:</span>
                                <span class="text-muted"><?php echo isset($book['book_publisher']) ? htmlspecialchars($book['book_publisher']) : 'N/A'; ?></span>
                            </li>
                            <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                <span>Published:</span>
                                <span class="text-muted"><?php echo isset($book['book_published_year']) ? htmlspecialchars($book['book_published_year']) : 'N/A'; ?></span>
                            </li>
                            <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                <span>Location:</span>
                                <span class="text-muted"><?php echo htmlspecialchars($book['book_location_rack']); ?></span>
                            </li>
                            <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between">
                                <span>Added on:</span>
                                <span class="text-muted"><?php echo isset($book['book_added_on']) ? date('F j, Y', strtotime($book['book_added_on'])) : 'N/A'; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Book Details Column (Scrollable) -->
        <div class="col-md-8">
            <div class="p-4">
                <h1 class="display-6 fw-bold mb-1"><?php echo htmlspecialchars($book['book_name']); ?></h1>
                <p class="text-muted mb-4">by <strong><?php echo htmlspecialchars($author_string); ?></strong></p>
                
                <!-- Book tabs navigation -->
                <ul class="nav nav-tabs mb-4" id="bookDetailsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="true">
                            Description
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="authors-tab" data-bs-toggle="tab" data-bs-target="#authors" type="button" role="tab" aria-controls="authors" aria-selected="false">
                            Authors
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab" aria-controls="reviews" aria-selected="false">
                            Reviews
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="more-books-tab" data-bs-toggle="tab" data-bs-target="#more-books" type="button" role="tab" aria-controls="more-books" aria-selected="false">
                            Related Books
                        </button>
                    </li>
                    <?php if (!empty($borrow_history) && isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false">
                            Borrow History
                        </button>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Book tabs content -->
                <div class="tab-content" id="bookDetailsTabsContent">
                    <!-- Description Tab -->
                    <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                        <div class="mb-4">
                            <?php if (!empty($book['book_description'])): ?>
                                <p class="lead"><?php echo nl2br(htmlspecialchars($book['book_description'])); ?></p>
                            <?php else: ?>
                                <p class="text-muted">No description available for this book.</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Popularity stats -->
                        <div class="card bg-light border-0 mt-4">
                            <div class="card-body">
                                <h5 class="card-title">Book Popularity</h5>
                                <div class="row">
                                    <div class="col-md-4 text-center border-end">
                                        <h3><?php echo $borrow_count; ?></h3>
                                        <p class="text-muted">Total Borrows</p>
                                    </div>
                                    <div class="col-md-4 text-center border-end">
                                        <h3><?php echo count($reviews); ?></h3>
                                        <p class="text-muted">Reviews</p>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="rating-display">
                                            <?php 
                                            $avg_rating = 0;
                                            if (!empty($reviews)) {
                                                $total_rating = array_sum(array_column($reviews, 'rating'));
                                                $avg_rating = round($total_rating / count($reviews), 1);
                                            }
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo '<i class="bi ' . ($i <= round($avg_rating) ? 'bi-star-fill' : 'bi-star') . ' text-warning"></i>';
                                            }
                                            ?>
                                            <span class="ms-2"><?php echo $avg_rating; ?>/5</span>
                                        </div>
                                        <p class="text-muted">Average Rating</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Authors Tab -->
                    <div class="tab-pane fade" id="authors" role="tabpanel" aria-labelledby="authors-tab">
                        <?php if (!empty($authors)): ?>
                            <?php foreach ($authors as $author): ?>
                                <div class="card mb-4 border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <?php 
                                            $author_img = !empty($author['author_profile']) ? 'asset/img/' . $author['author_profile'] : 'asset/img/author.png';
                                            ?>
                                            <img src="<?php echo $author_img; ?>" alt="<?php echo htmlspecialchars($author['author_name']); ?>" class="rounded-circle me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                            <div>
                                                <h5 class="card-title"><?php echo htmlspecialchars($author['author_name']); ?></h5>
                                                <?php if (!empty($author['author_bio'])): ?>
                                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($author['author_bio'])); ?></p>
                                                <?php else: ?>
                                                    <p class="text-muted">No biography available for this author.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No author information available for this book.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Reviews Tab -->
                    <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
                        <!-- Add review form (for logged-in users) -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="card mb-4 border-0 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title">Write a Review</h5>
                                    <form id="review-form" action="add_review.php" method="post">
                                        <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                                        <div class="mb-3">
                                            <label for="rating" class="form-label">Your Rating</label>
                                            <div class="rating-input">
                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                <input type="radio" name="rating" id="rating-<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo ($i == 5) ? 'checked' : ''; ?>>
                                                <label for="rating-<?php echo $i; ?>"><i class="bi bi-star-fill"></i></label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="review-text" class="form-label">Your Review</label>
                                            <textarea class="form-control" id="review-text" name="review_text" rows="4" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Submit Review</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Existing reviews -->
                        <?php if (!empty($reviews)): ?>
                            <h5 class="mb-3">Reader Reviews</h5>
                            <?php foreach ($reviews as $review): ?>
                                <div class="card mb-3 border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="card-subtitle"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                                            <small class="text-muted"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></small>
                                        </div>
                                        <div class="rating-display mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi <?php echo ($i <= $review['rating']) ? 'bi-star-fill' : 'bi-star'; ?> text-warning"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <p class="card-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No reviews yet. Be the first to review this book!</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Related Books Tab -->
                    <div class="tab-pane fade" id="more-books" role="tabpanel" aria-labelledby="more-books-tab">
                        <!-- Similar books by category -->
                        <?php if (!empty($similar_books)): ?>
                            <h5 class="mb-3">More from <?php echo htmlspecialchars($book['category_name']); ?> Category</h5>
                            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4">
                                <?php foreach($similar_books as $similar_book): ?>
                                    <div class="col">
                                        <div class="card h-100 border-0 shadow-sm">
                                            <?php 
                                            $similar_book_img = !empty($similar_book['book_img']) ? 'asset/img/' . $similar_book['book_img'] : 'asset/img/book_placeholder.png';
                                            ?>
                                            <img src="<?php echo $similar_book_img; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($similar_book['book_name']); ?>" style="height: 180px; object-fit: cover;">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($similar_book['book_name']); ?></h6>
                                                <a href="#" class="stretched-link book-details-link" data-book-id="<?php echo $similar_book['book_id']; ?>"></a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Books by same author -->
                        <?php if (!empty($author_books)): ?>
                            <h5 class="mb-3">More by <?php echo htmlspecialchars($author_string); ?></h5>
                            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                                <?php foreach($author_books as $author_book): ?>
                                    <div class="col">
                                        <div class="card h-100 border-0 shadow-sm">
                                            <?php 
                                            $author_book_img = !empty($author_book['book_img']) ? 'asset/img/' . $author_book['book_img'] : 'asset/img/book_placeholder.png';
                                            ?>
                                            <img src="<?php echo $author_book_img; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($author_book['book_name']); ?>" style="height: 180px; object-fit: cover;">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($author_book['book_name']); ?></h6>
                                                <a href="#" class="stretched-link book-details-link" data-book-id="<?php echo $author_book['book_id']; ?>"></a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Borrow History Tab (Admin only) -->
                    <?php if (!empty($borrow_history) && isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
                    <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                        <h5 class="mb-3">Borrow History</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Issue Date</th>
                                        <th>Return Date</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($borrow_history as $history): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($history['user_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($history['issue_date'])); ?></td>
                                            <td><?php echo !empty($history['return_date']) ? date('M d, Y', strtotime($history['return_date'])) : 'Not returned'; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($history['expected_return_date'])); ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($history['issue_book_status']) {
                                                    case 'Issue':
                                                        $status_class = 'bg-warning';
                                                        break;
                                                    case 'Return':
                                                        $status_class = 'bg-success';
                                                        break;
                                                    case 'Not Return':
                                                        $status_class = 'bg-danger';
                                                        break;
                                                    default:
                                                        $status_class = 'bg-secondary';
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo $history['issue_book_status']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript to handle clicking on related books
document.addEventListener('DOMContentLoaded', function() {
    const bookLinks = document.querySelectorAll('.book-details-link');
    bookLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const bookId = this.getAttribute('data-book-id');
            loadBookDetails(bookId);
        });
    });
    
    function loadBookDetails(bookId) {
        // Fetch book details via AJAX and update the modal content
        fetch('book_details_partial.php?book_id=' + bookId)
            .then(response => response.text())
            .then(html => {
                document.querySelector('.modal-body').innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading book details:', error);
            });
    }
});
</script>