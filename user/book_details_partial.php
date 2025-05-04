<?php
    // book_details_partial.php - For loading book details via AJAX
    include '../database_connection.php';
    include '../function.php';

    // Only include database connection and function files
    // Don't include header/footer since this is loaded in a modal

    $book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

    if ($book_id <= 0) {
        echo '<div class="alert alert-danger m-3">Invalid book ID.</div>';
        exit;
    }

    // Get book details
    $book = getBookDetails($connect, $book_id);

    if (!$book) {
        echo '<div class="alert alert-danger m-3">Book not found.</div>';
        exit;
    }

    // Get authors
    $authors = getBookAuthors($connect, $book_id);

    // Get borrow history (only for admins)
    $borrow_history = [];
    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
        $borrow_history = getBookBorrowHistory($connect, $book_id);
    }

    // Get similar books by category
    $similar_books = getSimilarBooksByCategory($connect, $book['category_id'], $book_id, 6);

    // Get books by same author
    $author_books = getBooksBySameAuthor($connect, $book_id, 6);

    // Get book reviews
    $reviews = getBookReviews($connect, $book_id);

    // Get book availability status
    // Calculate available copies based on total and borrowed
    $book_details = getBookById($connect, $book_id);
    $query = "SELECT COUNT(*) as borrowed_copies 
            FROM lms_issue_book 
            WHERE book_id = :book_id 
            AND (issue_book_status = 'Issue' OR issue_book_status = 'Not Return')";
    $statement = $connect->prepare($query);
    $statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    // Format authors as a comma-separated string
    $author_names = array_map(function($author) {
        return $author['author_name'];
    }, $authors);
    $author_string = implode(', ', $author_names);

    $base_url = base_url();
    $bookImgPath = getBookImagePath($book);
    $bookImgUrl = str_replace('../', $base_url, $bookImgPath);

    // Get book availability status
    $availability = getBookAvailability($connect, $book_id, $book['book_no_of_copy']);
    $borrowed_copies = $availability['borrowed_copies'];
    $available_copies = $availability['available_copies'];
    $is_available = $availability['is_available'];

    // Get borrow frequency
    $borrow_count = getBookBorrowCount($connect, $book_id);
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Book Cover Column (Sticky) -->
        <div class="col-md-4 bg-light">
            <div class="sticky-top" style="top: 1rem;">
                <div class="p-4 text-center">
                    <img src="<?php echo $bookImgUrl; ?>" alt="<?php echo htmlspecialchars($book['book_name']); ?>" class="img-fluid rounded shadow" style="max-height: 400px;">
                    
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
                                            $author_img = !empty($author['author_profile']) ? 'upload/' . $author['author_profile'] : 'asset/img/author.png';
                                            ?>
                                            <img src="<?php echo $author_img; ?>" alt="<?php echo htmlspecialchars($author['author_name']); ?>" class="rounded-circle me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                            <div class="w-100">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($author['author_name']); ?></h5>
                                                    <button class="btn btn-sm btn-outline-primary author-details-link" data-author-id="<?php echo $author['author_id']; ?>">
                                                        <i class="bi bi-info-circle me-1"></i>More Details
                                                    </button>
                                                </div>
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
                            <p class="text-muted">No reviews yet. Be the first to review this book! Visit us and get it today!</p>
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
                                            $similar_book_img = !empty($similar_book['book_img']) ? 'upload/' . $similar_book['book_img'] : 'asset/img/book_placeholder.png';
                                            ?>
                                            <img src="<?php echo $similar_book_img; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($similar_book['book_name']); ?>" style="height: 180px; object-fit: cover;">
                                            <div class="card-body">
                                                <span class="fw-bold"><?php echo htmlspecialchars($similar_book['book_name']); ?></span>
                                                <button class="stretched-link book-details-link btn btn-link p-0 text-decoration-none" data-book-id="<?php echo $similar_book['book_id']; ?>">View Details</button>
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
                                            $author_book_img = !empty($author_book['book_img']) ? 'upload/' . $author_book['book_img'] : 'asset/img/book_placeholder.png';
                                            ?>
                                            <img src="<?php echo $author_book_img; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($author_book['book_name']); ?>" style="height: 180px; object-fit: cover;">
                                            <div class="card-body">
                                                <span class="fw-bold"><?php echo htmlspecialchars($author_book['book_name']); ?></span>
                                                <button class="stretched-link book-details-link btn btn-link p-0 text-decoration-none" data-book-id="<?php echo $author_book['book_id']; ?>">View Details</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // JavaScript to handle clicking on related books and authors
    document.addEventListener('DOMContentLoaded', function() {
        // Book details link handler
        const bookLinks = document.querySelectorAll('.book-details-link');
        bookLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const bookId = this.getAttribute('data-book-id');
                loadBookDetails(bookId);
            });
        });
        
        // Author details link handler
        const authorLinks = document.querySelectorAll('.author-details-link');
        authorLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const authorId = this.getAttribute('data-author-id');
                loadAuthorDetails(authorId);
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
        
        function loadAuthorDetails(authorId) {
            
            fetch('author_details_partial.php?author_id=' + authorId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('book-detail-container').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading author details:', error);
                    document.getElementById('book-detail-container').innerHTML = `
                        <div class="alert alert-danger m-3">
                            Error loading author details. Please try again.
                        </div>
                    `;
                });
            
            // Option 2 (alternative): Redirect to author.php with the author ID
            window.location.href = 'author.php?author_id=' + authorId;
        }
    });
</script>