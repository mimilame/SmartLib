<?php
// my_fines.php - Display user's library fines
include '../database_connection.php';
include '../function.php';
include '../header.php';
authenticate_user();

// Get current user details
$user = get_complete_user_details($_SESSION['user_unique_id'] ?? '', $connect);
$base_url = base_url();

// Set default filter or get from query string
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$valid_filters = ['all', 'unpaid', 'paid'];
$filter = in_array($filter, $valid_filters) ? $filter : 'all';

// Build the query based on filter
$query_conditions = "";
$query_params = [':user_id' => $user['id']];

switch ($filter) {
    case 'unpaid':
        $query_conditions = "AND f.fines_status = 'Unpaid'";
        break;
    case 'paid':
        $query_conditions = "AND f.fines_status = 'Paid'";
        break;
    default: // 'all'
        $query_conditions = "";
}

// Get user's fines with filtering
$query = "SELECT f.*, ib.issue_book_id, ib.issue_date, ib.expected_return_date, 
         b.book_id, b.book_name, b.book_isbn_number, b.book_img,
         DATEDIFF(CURRENT_DATE(), ib.expected_return_date) as days_overdue
         FROM lms_fines f
         JOIN lms_issue_book ib ON f.issue_book_id = ib.issue_book_id
         JOIN lms_book b ON ib.book_id = b.book_id
         WHERE ib.user_id = :user_id 
         $query_conditions
         ORDER BY 
         CASE 
             WHEN f.fines_status = 'Unpaid' THEN 1
             ELSE 2
         END,
         f.fines_updated_on DESC";

$statement = $connect->prepare($query);
foreach($query_params as $param => $value) {
    $statement->bindValue($param, $value);
}
$statement->execute();
$fines = $statement->fetchAll(PDO::FETCH_ASSOC);

// Get summary data for the user
$summary_query = "SELECT 
                 COUNT(CASE WHEN f.fines_status = 'Unpaid' THEN 1 END) as unpaid_count,
                 SUM(CASE WHEN f.fines_status = 'Unpaid' THEN f.fines_amount ELSE 0 END) as unpaid_total,
                 COUNT(CASE WHEN f.fines_status = 'Paid' THEN 1 END) as paid_count,
                 SUM(CASE WHEN f.fines_status = 'Paid' THEN f.fines_amount ELSE 0 END) as paid_total
                 FROM lms_fines f
                 JOIN lms_issue_book ib ON f.issue_book_id = ib.issue_book_id
                 WHERE ib.user_id = :user_id";

$summary_statement = $connect->prepare($summary_query);
$summary_statement->bindValue(':user_id', $user['id']);
$summary_statement->execute();
$summary = $summary_statement->fetch(PDO::FETCH_ASSOC);
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">My Fines</h1>
        <a href="my_books.php" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to My Books
        </a>
    </div>
    <?php if (($summary['unpaid_count'] ?? 0) > 0): ?>
    <div class="mt-3">
        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-2"></i>
            Please visit the library to pay your fines in person.
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card h-100 border-danger">
                <div class="card-body">
                    <h5 class="card-title text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Unpaid Fines
                    </h5>
                    <div class="d-flex align-items-center">
                        <div class="display-4 me-3"><?php echo get_currency_symbol($connect) . number_format($summary['unpaid_total'] ?? 0, 2); ?></div>
                        <div class="text-muted">
                            <?php echo $summary['unpaid_count'] ?? 0; ?> fine<?php echo ($summary['unpaid_count'] != 1) ? 's' : ''; ?>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 border-success">
                <div class="card-body">
                    <h5 class="card-title text-success">
                        <i class="bi bi-check-circle-fill me-2"></i>Paid Fines History
                    </h5>
                    <div class="d-flex align-items-center">
                        <div class="display-4 me-3"><?php echo get_currency_symbol($connect) . number_format($summary['paid_total'] ?? 0, 2); ?></div>
                        <div class="text-muted">
                            <?php echo $summary['paid_count'] ?? 0; ?> fine<?php echo ($summary['paid_count'] != 1) ? 's' : ''; ?> paid
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $filter == 'all' ? 'active' : ''; ?>" href="my_fines.php">
                All Fines
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter == 'unpaid' ? 'active' : ''; ?>" href="my_fines.php?filter=unpaid">
                <i class="bi bi-exclamation-triangle-fill text-danger"></i> Unpaid
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter == 'paid' ? 'active' : ''; ?>" href="my_fines.php?filter=paid">
                <i class="bi bi-check-circle-fill text-success"></i> Paid
            </a>
        </li>
    </ul>
    
    <?php if (empty($fines)): ?>
    <div class="alert alert-info">
        <h5><i class="bi bi-info-circle me-2"></i> No fines found</h5>
        <p class="mb-0">
            <?php 
            switch ($filter) {
                case 'unpaid':
                    echo "You don't have any unpaid fines.";
                    break;
                case 'paid':
                    echo "You don't have any paid fine history.";
                    break;
                default:
                    echo "You don't have any fines.";
            }
            ?>
        </p>
    </div>
    <?php else: ?>
    
    <!-- Fine Items List -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Book Details</th>
                            <th>Fine Details</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fines as $fine): 
                           $bookImgPath = getBookImagePath($fine);
                           $bookImgUrl = str_replace('../', $base_url, $bookImgPath);
                           $status = $fine['fines_status'];
                           $statusClass = ($status == 'Paid') ? 'success' : 'danger';
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $bookImgUrl; ?>" class="me-3" alt="<?php echo htmlspecialchars($fine['book_name']); ?>" 
                                         style="width: 50px; height: 70px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($fine['book_name']); ?></h6>
                                        <div class="small text-muted">
                                            ISBN: <?php echo htmlspecialchars($fine['book_isbn_number']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>Fine issued on: <?php echo date('M d, Y', strtotime($fine['fines_updated_on'])); ?></div>
                                <?php if ($status == 'Paid' && !empty($fine['fines_updated_on'])): ?>
                                <div class="small text-success">
                                    Paid on: <?php echo date('M d, Y', strtotime($fine['fines_updated_on'])); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($fine['expected_return_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $statusClass; ?>">
                                    <?php echo $status; ?>
                                </span>
                                <?php if ($status == 'Unpaid'): ?>
                                <div class="small text-danger mt-1">
                                    <?php echo $fine['days_overdue']; ?> day<?php echo $fine['days_overdue'] > 1 ? 's' : ''; ?> overdue
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-bold <?php echo $status == 'Unpaid' ? 'text-danger' : ''; ?>">
                                    <?php echo get_currency_symbol($connect) . number_format($fine['fines_amount'], 2); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="books.php?book_id=<?php echo $fine['book_id']; ?>" class="btn btn-sm btn-outline-secondary open-modal" data-book-id="<?php echo $fine['book_id']; ?>">
                                        <i class="bi bi-info-circle"></i> Book Details
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($filter == 'unpaid' && isset($summary['unpaid_count']) && $summary['unpaid_count'] > 0): ?>
            <div class="p-3 bg-light border-top">
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Please visit the library in person to settle your outstanding fines. Our staff will assist you with the payment process.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
        <!-- At the bottom of my_books.php, include the modal definition -->
    <!-- Book Detail Modal -->
    <div class="modal fade" id="bookModal" tabindex="-1" aria-labelledby="bookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="book-detail-container">
                    <!-- Book details will be loaded here -->
                    <div class="d-flex justify-content-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add JavaScript to handle the modal in my_books.php -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add click event listeners to all book detail links with class 'open-modal'
            document.querySelectorAll('.open-modal').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const bookId = this.getAttribute('data-book-id');
                    const bookModal = new bootstrap.Modal(document.getElementById('bookModal'));
                    
                    // Show loading spinner
                    document.getElementById('book-detail-container').innerHTML = `
                        <div class="d-flex justify-content-center p-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    `;
                    
                    // Load book details via AJAX
                    fetch(`book_details_partial.php?book_id=${bookId}`)
                        .then(response => response.text())
                        .then(html => {
                            document.getElementById('book-detail-container').innerHTML = html;
                            
                            // Update URL without reloading the page
                            const newUrl = 'books.php?book_id=' + bookId;
                            window.history.pushState({bookId: bookId}, '', newUrl);
                            
                            // Show the modal
                            bookModal.show();
                            
                            // Add event listeners to links inside the modal content
                            setupModalEventListeners(bookId);
                        })
                        .catch(error => {
                            console.error('Error loading book details:', error);
                            document.getElementById('book-detail-container').innerHTML = `
                                <div class="alert alert-danger m-3">
                                    Error loading book details. Please try again.
                                </div>
                            `;
                            bookModal.show();
                        });
                });
            });
            
            // Function to set up event listeners inside the modal
            function setupModalEventListeners(bookId) {
                // Initialize any JS components inside the modal
                const reviewForm = document.getElementById('review-form');
                if (reviewForm) {
                    reviewForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        // Handle form submission via AJAX
                        const formData = new FormData(this);
                        
                        fetch('submit_review.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Review submitted successfully!');
                                // Refresh reviews section
                                loadBookDetails(bookId);
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error submitting review:', error);
                            alert('Error submitting review. Please try again.');
                        });
                    });
                }
                
                // Book details link handler for related books
                const bookLinks = document.querySelectorAll('.book-details-link');
                bookLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const newBookId = this.getAttribute('data-book-id');
                        loadBookDetails(newBookId);
                        
                        // Update URL without reloading the page
                        const newUrl = 'books.php?book_id=' + newBookId;
                        window.history.pushState({bookId: newBookId}, '', newUrl);
                    });
                });
                
                // Author details link handler
                const authorLinks = document.querySelectorAll('.author-details-link');
                authorLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const authorId = this.getAttribute('data-author-id');
                        // Redirect to author page
                        window.location.href = 'author.php?author_id=' + authorId;
                    });
                });
            }
            
            // Function to load book details
            function loadBookDetails(bookId) {
                // Show loading spinner
                document.getElementById('book-detail-container').innerHTML = `
                    <div class="d-flex justify-content-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;
                
                // Load book details via AJAX
                fetch(`book_details_partial.php?book_id=${bookId}`)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('book-detail-container').innerHTML = html;
                        
                        // Add event listeners to links inside the modal content
                        setupModalEventListeners(bookId);
                    })
                    .catch(error => {
                        console.error('Error loading book details:', error);
                        document.getElementById('book-detail-container').innerHTML = `
                            <div class="alert alert-danger m-3">
                                Error loading book details. Please try again.
                            </div>
                        `;
                    });
            }
            
            // Handle modal close event (reset URL)
            document.getElementById('bookModal').addEventListener('hidden.bs.modal', function () {
                // Reset URL to the page without book_id when modal is closed
                if (window.history.state && window.history.state.bookId) {
                    const currentUrl = window.location.href;
                    const baseUrl = currentUrl.split('?')[0];
                    window.history.pushState({}, '', baseUrl);
                }
            });
        });
    </script>

<?php include '../footer.php';?>