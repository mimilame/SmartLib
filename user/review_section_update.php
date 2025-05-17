<?php
// review_section_update.php - The updated reviews tab section for book_details_partial.php

// Get reviews for this book
$reviews = getBookReviews($connect, $book_id);

// Check if current user is logged in
$user_logged_in = isset($_SESSION['user_id']);
$user_id = $user_logged_in ? $_SESSION['user_id'] : 0;

// Variable to track if the review form is initially shown
$show_review_form = false;
?>

<!-- Reviews Tab -->
<div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
    <div id="reviews-container">
        <!-- Review form will be loaded here via AJAX when triggered -->
        <div id="review-form-container" class="<?php echo $show_review_form ? '' : 'd-none'; ?>"></div>
        
        <!-- Existing reviews section -->
        <div id="existing-reviews">
            <?php if (!empty($reviews)): ?>
                <h5 class="mb-3">Reader Reviews (<?php echo count($reviews); ?>)</h5>
                <?php foreach ($reviews as $review): ?>
                    <div class="card mb-3 border-0 shadow-sm review-card">
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
                <div class="text-center p-4">
                    <div class="mb-4">
                        <i class="bi bi-chat-square-text" style="font-size: 3rem; color: #ccc;"></i>
                    </div>
                    <h5>No reviews yet.</h5>
                    <p class="text-muted mb-4">Be the first to share your thoughts about this book!</p>
                    <button id="be-first-review" class="btn btn-primary">
                        <i class="bi bi-pencil-square me-2"></i>Write a Review
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($reviews)): ?>
            <!-- Button to add a new review when there are existing reviews -->
            <div class="text-center mt-4 mb-3">
                <button id="add-review-btn" class="btn btn-outline-primary">
                    <i class="bi bi-plus-circle me-2"></i>Add Your Review
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle "Be first to review" button click
    const beFirstButton = document.getElementById('be-first-review');
    if (beFirstButton) {
        beFirstButton.addEventListener('click', function() {
            loadReviewForm();
        });
    }
    
    // Handle "Add Your Review" button click
    const addReviewBtn = document.getElementById('add-review-btn');
    if (addReviewBtn) {
        addReviewBtn.addEventListener('click', function() {
            loadReviewForm();
        });
    }
    
    // Function to load the review form via AJAX
    function loadReviewForm() {
        const reviewFormContainer = document.getElementById('review-form-container');
        const existingReviews = document.getElementById('existing-reviews');
        const addReviewBtn = document.getElementById('add-review-btn');
        
        <?php if (!$user_logged_in): ?>
            // Show login prompt if user is not logged in
            showLoginPrompt();
            return;
        <?php endif; ?>
        
        // Show loading indicator
        reviewFormContainer.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        reviewFormContainer.classList.remove('d-none');
        
        // Hide the "Add Review" button while loading
        if (addReviewBtn) {
            addReviewBtn.classList.add('d-none');
        }
        
        // Load review form via AJAX
        fetch('review_form.php?book_id=<?php echo $book_id; ?>')
            .then(response => response.text())
            .then(html => {
                reviewFormContainer.innerHTML = html;
                
                // Add event listener to cancel button
                const cancelBtn = reviewFormContainer.querySelector('#cancel-review');
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        reviewFormContainer.classList.add('d-none');
                        reviewFormContainer.innerHTML = '';
                        
                        // Show the "Add Review" button again
                        if (addReviewBtn) {
                            addReviewBtn.classList.remove('d-none');
                        }
                    });
                }
                
                // Add event listener to review form submission
                const reviewForm = reviewFormContainer.querySelector('#review-form');
                if (reviewForm) {
                    reviewForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        submitReviewForm(reviewForm);
                    });
                }
                
                // Scroll to the form
                reviewFormContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            })
            .catch(error => {
                console.error('Error loading review form:', error);
                reviewFormContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        An error occurred while loading the review form. Please try again.
                    </div>
                `;
            });
    }
    
    // Function to submit the review form via AJAX
    function submitReviewForm(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        // Disable submit button and show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Submitting...';
        
        fetch('submit_review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            
            if (data.status === 'success') {
                // Show success message
                document.getElementById('review-form-container').innerHTML = `
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        ${data.message}
                    </div>
                `;
                
                // Reload book details after a short delay
                setTimeout(() => {
                    loadBookDetails(<?php echo $book_id; ?>);
                }, 2000);
            } else {
                // Show error message
                const errorMsg = document.createElement('div');
                errorMsg.className = 'alert alert-danger mt-3';
                errorMsg.innerHTML = `<i class="bi bi-exclamation-circle me-2"></i>${data.message}`;
                
                // Add error message before submit button
                const formActions = form.querySelector('.form-actions');
                formActions.parentNode.insertBefore(errorMsg, formActions);
                
                // Remove error message after 5 seconds
                setTimeout(() => {
                    errorMsg.remove();
                }, 5000);
            }
        })
        .catch(error => {
            console.error('Error submitting review:', error);
            
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            
            // Show error message
            const errorMsg = document.createElement('div');
            errorMsg.className = 'alert alert-danger mt-3';
            errorMsg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>An error occurred. Please try again.';
            
            // Add error message before submit button
            const formActions = form.querySelector('.form-actions');
            formActions.parentNode.insertBefore(errorMsg, formActions);
            
            // Remove error message after 5 seconds
            setTimeout(() => {
                errorMsg.remove();
            }, 5000);
        });
    }
    
    // Function to show login prompt
    function showLoginPrompt() {
        const reviewFormContainer = document.getElementById('review-form-container');
        reviewFormContainer.classList.remove('d-none');
        reviewFormContainer.innerHTML = `
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center p-4">
                    <i class="bi bi-lock-fill mb-3" style="font-size: 2rem; color: #6c757d;"></i>
                    <h5>Please log in to review this book</h5>
                    <p class="text-muted mb-4">You need to be logged in to share your thoughts about this book.</p>
                    <a href="login.php" class="btn btn-primary me-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Log In
                    </a>
                    <button id="cancel-login-prompt" class="btn btn-outline-secondary">
                        <i class="bi bi-x me-2"></i>Cancel
                    </button>
                </div>
            </div>
        `;
        
        // Add event listener to cancel button
        const cancelBtn = reviewFormContainer.querySelector('#cancel-login-prompt');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                reviewFormContainer.classList.add('d-none');
                reviewFormContainer.innerHTML = '';
            });
        }
    }
    
    // Function to load book details (used after successful review submission)
    function loadBookDetails(bookId) {
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
