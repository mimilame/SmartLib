<?php
//books.php
	include '../database_connection.php';
	include '../function.php';	
	include '../header.php';
	
		// Get all books first
        $all_books_query = "SELECT * FROM lms_book WHERE book_status = 'Enable' ORDER BY book_id ASC";
        $all_books_stmt = $connect->prepare($all_books_query);
        $all_books_stmt->execute();
        $all_books = $all_books_stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // Default to first book if no book_id
        $book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : ($all_books[0]['book_id'] ?? 0);
        
        // Load current book details
        $book = getBookDetails($connect, $book_id);
        $authors = getBookAuthors($connect, $book_id);
        $borrow_history = getBookBorrowHistory($connect, $book_id);
        $similar_books = getSimilarBooksByCategory($connect, $book['category_id'], $book_id);
        $author_books = getBooksBySameAuthor($connect, $book_id);
        $reviews = getBookReviews($connect, $book_id);
	
	// Get book availability status
	$available_copies = $book['book_no_of_copy'];
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
	
	// Get book cover image
	$book_img = !empty($book['book_img']) ? '../asset/img/' . $book['book_img'] : '../asset/img/book_placeholder.png';
	
	// Format authors as a comma-separated string
	$author_names = array_map(function($author) {
		return $author['author_name'];
	}, $authors);
	$author_string = implode(', ', $author_names);
	
	// Determine book status class for display
	$status_class = $available_copies > 0 ? 'text-success' : 'text-danger';
	$status_text = $available_copies > 0 ? 'Available' : 'Not Available';
	
	// Get borrow frequency
	$query = "SELECT COUNT(*) as borrow_count 
			FROM lms_issue_book 
			WHERE book_id = :book_id";
	
	$statement = $connect->prepare($query);
	$statement->bindParam(':book_id', $book_id, PDO::PARAM_INT);
	$statement->execute();
	$result = $statement->fetch(PDO::FETCH_ASSOC);
	
	$borrow_count = $result['borrow_count'];
?>

<?php
	// Display SweetAlert messages if they exist
	if(!empty($error_message)) {
		echo sweet_alert('error', $error_message);
	}

	if(!empty($success_message)) {
		echo sweet_alert('success', $success_message);
	}
?>
x
	<div class="container py-5" id="book-detail-container">
		<div class="row">
			<!-- Book Cover and Basic Info Card -->
			<div class="col-lg-4 mb-4">
				<div class="card shadow">
					<div class="book-img-wrapper text-center p-4">
						<img src="<?php echo $book_img; ?>" alt="<?php echo htmlspecialchars($book['book_name']); ?>" class="img-fluid book-cover-large">
					</div>
					<div class="card-body">
						<h1 class="card-title h3 mb-3"><?php echo htmlspecialchars($book['book_name']); ?></h1>
						<p class="card-text">by <strong><?php echo htmlspecialchars($author_string); ?></strong></p>
						
						<div class="d-flex justify-content-between align-items-center mb-3">
							<div class="availability">
								<span class="<?php echo $status_class; ?> fw-bold"><?php echo $status_text; ?></span>
								<small class="d-block text-muted"><?php echo $available_copies; ?> of <?php echo $book['book_no_of_copy']; ?> copies available</small>
							</div>
							<div class="rating">
								<fieldset class="rating book-rate">
									<?php
									// Generate star rating based on borrow count
									$popularity_rating = min(5, ceil($borrow_count / 10));
									for ($i = 5; $i >= 1; $i--) {
										$checked = ($i <= $popularity_rating) ? 'checked' : '';
										echo '<input type="checkbox" id="star-detail-' . $i . '" name="rating" value="' . $i . '" ' . $checked . ' disabled>';
										echo '<label class="full" for="star-detail-' . $i . '"></label>';
									}
									?>
								</fieldset>
								<span class="book-voters"><?php echo $borrow_count; ?> borrows</span>
							</div>
						</div>
						
						<div class="book-actions mt-4">
							<?php if ($available_copies > 0 && isset($_SESSION['user_id'])): ?>
							<a href="issue_book.php?book_id=<?php echo $book_id; ?>" class="btn btn-primary w-100 mb-2">Borrow This Book</a>
							<?php else: ?>
							<button class="btn btn-secondary w-100 mb-2" disabled>Currently Unavailable</button>
							<?php endif; ?>
							<a href="catalog.php" class="btn btn-outline-secondary w-100">Back to Catalog</a>
						</div>
					</div>
				</div>
			</div>
			<div class="mb-4">
                <h3>Select a Book:</h3>
                <div class="btn-group flex-wrap" role="group">
                    <?php foreach ($all_books as $b): ?>
                        <button class="btn btn-outline-primary book-tab <?php echo $b['book_id'] == $book_id ? 'active' : ''; ?>" 
                            data-id="<?php echo $b['book_id']; ?>">
                            <?php echo htmlspecialchars($b['book_name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
			<!-- Book Details -->
			<div class="col-lg-8">
				<div class="card shadow mb-4">
					<div class="card-header bg-primary text-white">
						<h2 class="h4 mb-0">Book Details</h2>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-6">
								<ul class="list-group list-group-flush">
									<li class="list-group-item d-flex justify-content-between">
										<span class="fw-bold">ISBN:</span>
										<span><?php echo htmlspecialchars($book['book_isbn_number']); ?></span>
									</li>
									<li class="list-group-item d-flex justify-content-between">
										<span class="fw-bold">Category:</span>
										<span><?php echo htmlspecialchars($book['category_name']); ?></span>
									</li>
									<li class="list-group-item d-flex justify-content-between">
										<span class="fw-bold">Location:</span>
										<span><?php echo htmlspecialchars($book['book_location_rack']); ?></span>
									</li>
								</ul>
							</div>
							<div class="col-md-6">
								<ul class="list-group list-group-flush">
									<li class="list-group-item d-flex justify-content-between">
										<span class="fw-bold">Published:</span>
										<span><?php echo isset($book['book_published_year']) ? htmlspecialchars($book['book_published_year']) : 'N/A'; ?></span>
									</li>
									<li class="list-group-item d-flex justify-content-between">
										<span class="fw-bold">Publisher:</span>
										<span><?php echo isset($book['book_publisher']) ? htmlspecialchars($book['book_publisher']) : 'N/A'; ?></span>
									</li>
									<li class="list-group-item d-flex justify-content-between">
										<span class="fw-bold">Added on:</span>
										<span><?php echo isset($book['book_added_on']) ? date('F j, Y', strtotime($book['book_added_on'])) : 'N/A'; ?></span>
									</li>
								</ul>
							</div>
						</div>
						
						<div class="book-description mt-4">
							<h3 class="h5 mb-3">Description</h3>
							<p><?php echo isset($book['book_description']) ? nl2br(htmlspecialchars($book['book_description'])) : 'No description available for this book.'; ?></p>
						</div>
					</div>
				</div>
				
				<!-- Authors Info -->
				<?php if (!empty($authors)): ?>
				<div class="card shadow mb-4">
					<div class="card-header bg-info text-white">
						<h2 class="h4 mb-0">About the Author<?php echo count($authors) > 1 ? 's' : ''; ?></h2>
					</div>
					<div class="card-body">
						<div class="row">
							<?php foreach ($authors as $author): ?>
							<div class="col-md-6 mb-3">
								<div class="d-flex align-items-center">
									<?php 
									$author_img = !empty($author['author_profile']) ? '../asset/img/' . $author['author_profile'] : '../asset/img/author.png';
									?>
									<img src="<?php echo $author_img; ?>" alt="<?php echo htmlspecialchars($author['author_name']); ?>" class="author-img me-3" style="width: 60px; height: 60px; border-radius: 50%;">
									<div>
										<h3 class="h5 mb-1"><?php echo htmlspecialchars($author['author_name']); ?></h3>
										<p class="text-muted mb-0 small">
											<?php 
											// Get book count for this author
											$query = "SELECT COUNT(DISTINCT ba.book_id) as book_count 
													FROM lms_book_author ba
													JOIN lms_book b ON ba.book_id = b.book_id
													WHERE ba.author_id = :author_id
													AND b.book_status = 'Enable'";
											$statement = $connect->prepare($query);
											$statement->bindParam(':author_id', $author['author_id'], PDO::PARAM_INT);
											$statement->execute();
											$result = $statement->fetch(PDO::FETCH_ASSOC);
											echo $result['book_count'] . ' books in the library';
											?>
										</p>
									</div>
								</div>
								<?php if (!empty($author['author_about'])): ?>
								<p class="mt-2 small"><?php echo nl2br(htmlspecialchars($author['author_about'])); ?></p>
								<?php endif; ?>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<?php endif; ?>
				
				<!-- Books by Same Author -->
				<?php if (!empty($author_books)): ?>
				<div class="card shadow mb-4">
					<div class="card-header bg-success text-white">
						<h2 class="h4 mb-0">More by <?php echo htmlspecialchars($author_names[0]); ?></h2>
					</div>
					<div class="card-body">
						<div class="row">
							<?php foreach ($author_books as $related_book): ?>
							<?php 
							$related_book_img = !empty($related_book['book_img']) ? '../asset/img/' . $related_book['book_img'] : '../asset/img/book_placeholder.png';
							?>
							<div class="col-6 col-md-4 col-lg-2 mb-3">
								<a href="book_details.php?book_id=<?php echo $related_book['book_id']; ?>" class="text-decoration-none">
									<div class="card h-100 border-0">
										<img src="<?php echo $related_book_img; ?>" alt="<?php echo htmlspecialchars($related_book['book_name']); ?>" class="card-img-top">
										<div class="card-body p-2 text-center">
											<h3 class="card-title h6 small mb-0"><?php echo htmlspecialchars($related_book['book_name']); ?></h3>
										</div>
									</div>
								</a>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<?php endif; ?>
				
				<!-- Similar Books by Category -->
				<?php if (!empty($similar_books)): ?>
				<div class="card shadow mb-4">
					<div class="card-header bg-warning">
						<h2 class="h4 mb-0">Similar Books in <?php echo htmlspecialchars($book['category_name']); ?></h2>
					</div>
					<div class="card-body">
						<div class="row">
							<?php foreach ($similar_books as $similar_book): ?>
							<?php 
							$similar_book_img = !empty($similar_book['book_img']) ? '../asset/img/' . $similar_book['book_img'] : '../asset/img/book_placeholder.png';
							?>
							<div class="col-6 col-md-4 col-lg-2 mb-3">
								<a href="book_details.php?book_id=<?php echo $similar_book['book_id']; ?>" class="text-decoration-none">
									<div class="card h-100 border-0">
										<img src="<?php echo $similar_book_img; ?>" alt="<?php echo htmlspecialchars($similar_book['book_name']); ?>" class="card-img-top">
										<div class="card-body p-2 text-center">
											<h3 class="card-title h6 small mb-0"><?php echo htmlspecialchars($similar_book['book_name']); ?></h3>
										</div>
									</div>
								</a>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<?php endif; ?>
				
				<!-- Borrow History -->
				<?php if (!empty($borrow_history) && isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
				<div class="card shadow mb-4">
					<div class="card-header bg-secondary text-white">
						<h2 class="h4 mb-0">Borrow History</h2>
					</div>
					<div class="card-body">
						<div class="table-responsive">
							<table class="table table-hover">
								<thead>
									<tr>
										<th>User</th>
										<th>Issue Date</th>
										<th>Return Due</th>
										<th>Return Date</th>
										<th>Status</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($borrow_history as $history): ?>
									<tr>
										<td><?php echo htmlspecialchars($history['user_name']); ?></td>
										<td><?php echo date('M d, Y', strtotime($history['issue_date'])); ?></td>
										<td><?php echo date('M d, Y', strtotime($history['expected_return_date'])); ?></td>
										<td><?php echo !empty($history['return_date']) ? date('M d, Y', strtotime($history['return_date'])) : '-'; ?></td>
										<td>
											<?php 
											$status_badge = '';
											switch ($history['issue_book_status']) {
												case 'Issue':
													$status_badge = 'badge bg-info';
													break;
												case 'Return':
													$status_badge = 'badge bg-success';
													break;
												case 'Not Return':
													$status_badge = 'badge bg-danger';
													break;
												case 'Overdue':
													$status_badge = 'badge bg-warning text-dark';
													break;
												default:
													$status_badge = 'badge bg-secondary';
											}
											?>
											<span class="<?php echo $status_badge; ?>"><?php echo $history['issue_book_status']; ?></span>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<?php endif; ?>
				
				<!-- Book Reviews Section -->
				<div class="card shadow mb-4">
					<div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
						<h2 class="h4 mb-0">Reader Reviews</h2>
						<?php if (isset($_SESSION['user_id'])): ?>
						<button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#reviewModal">Add Review</button>
						<?php endif; ?>
					</div>
					<div class="card-body">
						<?php if (!empty($reviews)): ?>
							<?php foreach ($reviews as $review): ?>
							<div class="review-item mb-3 pb-3 border-bottom">
								<div class="d-flex justify-content-between align-items-center mb-2">
									<div>
										<strong><?php echo htmlspecialchars($review['user_name']); ?></strong>
										<small class="text-muted ms-2"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
									</div>
									<div class="review-rating">
										<?php 
										for ($i = 1; $i <= 5; $i++) {
											echo $i <= $review['rating'] ? '★' : '☆';
										}
										?>
									</div>
								</div>
								<p class="mb-0"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
							</div>
							<?php endforeach; ?>
						<?php else: ?>
							<div class="text-center py-4">
								<p class="text-muted mb-0">No reviews yet. Be the first to review this book!</p>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Review Modal -->
<?php if (isset($_SESSION['user_id'])): ?>
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="reviewModalLabel">Review "<?php echo htmlspecialchars($book['book_name']); ?>"</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form action="submit_review.php" method="post">
				<div class="modal-body">
					<input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
					<div class="mb-3">
						<label for="rating" class="form-label">Your Rating</label>
						<div class="rating-input d-flex">
							<?php for ($i = 1; $i <= 5; $i++): ?>
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="radio" name="rating" id="rating<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo $i == 5 ? 'checked' : ''; ?>>
								<label class="form-check-label" for="rating<?php echo $i; ?>"><?php echo $i; ?></label>
							</div>
							<?php endfor; ?>
						</div>
					</div>
					<div class="mb-3">
						<label for="review_text" class="form-label">Your Review</label>
						<textarea class="form-control" id="review_text" name="review_text" rows="4" required></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-primary">Submit Review</button>
				</div>
			</form>
		</div>
	</div>
</div>
<?php endif; ?>

<!-- Custom CSS for enhanced book display -->
<style>
    .book-cover-large {
        max-height: 300px;
        object-fit: contain;
    }

    .author-img {
        object-fit: cover;
    }

    .book-actions {
        border-top: 1px solid #eee;
        padding-top: 1rem;
    }

    .review-rating {
        color: #ffc107;
        letter-spacing: 2px;
    }

    .book-img-wrapper {
        background-color: #f8f9fa;
        border-bottom: 1px solid rgba(0,0,0,.125);
    }

    /* Star rating styles */
    .rating {
        border: none;
        margin-right: 5px;
    }

    .rating > input {
        display: none;
    }

    .rating > label:before {
        content: '★';
        font-size: 1.25em;
        color: #ddd;
    }

    .rating > input:checked ~ label:before,
    .rating > label:hover ~ label:before,
    .rating > label:hover:before {
        color: #ffc107;
    }

    /* Book card hover effects */
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .col-6 .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
</style>

<script>
// Book detail page specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
	// Handle clicking on similar book cards
	const bookCards = document.querySelectorAll('.col-6 a');
	bookCards.forEach(card => {
		card.addEventListener('click', function(e) {
			// Add a simple loading effect if you want
			this.querySelector('.card').classList.add('loading');
		});
	});
	
	// Initialize any tooltips
	if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
		const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
		tooltips.forEach(tooltip => {
			new bootstrap.Tooltip(tooltip);
		});
	}
});
$(document).ready(function() {
		$('.book-tab').on('click', function() {
			const bookId = $(this).data('id');

			// Visually highlight active tab
			$('.book-tab').removeClass('active');
			$(this).addClass('active');

			// Load content via AJAX
			$.ajax({
				url: 'book_details_partial.php', // a new file for partial view
				type: 'GET',
				data: { book_id: bookId },
				success: function(response) {
					$('#book-detail-container').html(response);
				}
			});
		});
	});
</script>

<?php
include '../footer.php';
?>