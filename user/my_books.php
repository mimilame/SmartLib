<?php
// my_books.php - Display user's borrowed books with filtering options
include '../database_connection.php';
include '../function.php';
include '../header.php';
authenticate_user();

$user_unique_id = $_SESSION['user_unique_id'] ?? '';
$user = get_complete_user_details($user_unique_id, $connect);
$base_url = base_url();

// Set default filter or get from query string
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$valid_filters = ['all', 'current', 'overdue', 'returned'];
$filter = in_array($filter, $valid_filters) ? $filter : 'all';

// Build the query based on filter
$query_conditions = "";
$query_params = [':user_id' => $user['id']]; 

switch ($filter) {
    case 'current':
        $query_conditions = "AND ib.issue_book_status = 'Issue'";
        break;
    case 'overdue':
        $query_conditions = "AND ib.issue_book_status = 'Overdue'";
        break;
    case 'returned':
        $query_conditions = "AND ib.issue_book_status = 'Returned'";
        break;
    default: // 'all'
        $query_conditions = "";
}

// Get user's books with filtering
$query = "SELECT ib.*, b.book_name, b.book_isbn_number, b.book_img, 
         c.category_name, a.author_name, 
         DATEDIFF(ib.expected_return_date, CURRENT_DATE()) as days_remaining,
         CASE 
             WHEN ib.issue_book_status = 'Returned' THEN NULL
             WHEN ib.expected_return_date < CURRENT_DATE() THEN DATEDIFF(CURRENT_DATE(), ib.expected_return_date)
             ELSE NULL
         END as days_overdue,
         f.fines_amount, f.fines_status
         FROM lms_issue_book ib
         JOIN lms_book b ON ib.book_id = b.book_id
         LEFT JOIN lms_category c ON b.category_id = c.category_id
         LEFT JOIN lms_book_author ba ON b.book_id = ba.book_id
         LEFT JOIN lms_author a ON ba.author_id = a.author_id
         LEFT JOIN lms_fines f ON ib.issue_book_id = f.issue_book_id
         WHERE ib.user_id = :user_id 
         $query_conditions
         GROUP BY ib.issue_book_id
         ORDER BY 
         CASE 
             WHEN ib.issue_book_status = 'Overdue' THEN 1
             WHEN ib.issue_book_status = 'Issue' THEN 2
             ELSE 3
         END,
         ib.issue_date DESC";

$statement = $connect->prepare($query);
foreach($query_params as $param => $value) {
    $statement->bindValue($param, $value);
}
$statement->execute();
$books = $statement->fetchAll(PDO::FETCH_ASSOC);
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">My Books</h1>
        <a href="books.php" class="btn btn-primary">Browse Library</a>
    </div>
    
    <!-- Filter Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $filter == 'all' ? 'active' : ''; ?>" href="my_books.php">
                All Books
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter == 'current' ? 'active' : ''; ?>" href="my_books.php?filter=current">
                Currently Borrowed
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter == 'overdue' ? 'active' : ''; ?>" href="my_books.php?filter=overdue">
                <i class="bi bi-exclamation-triangle-fill text-danger"></i> Overdue
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter == 'returned' ? 'active' : ''; ?>" href="my_books.php?filter=returned">
                Return History
            </a>
        </li>
    </ul>
    
    <?php if (empty($books)): ?>
    <div class="alert alert-info">
        <h5><i class="bi bi-info-circle me-2"></i> No books found</h5>
        <p class="mb-0">
            <?php 
            switch ($filter) {
                case 'current':
                    echo "You don't have any books currently borrowed.";
                    break;
                case 'overdue':
                    echo "You don't have any overdue books. Great job keeping track of due dates!";
                    break;
                case 'returned':
                    echo "You haven't returned any books yet.";
                    break;
                default:
                    echo "You haven't borrowed any books yet.";
            }
            ?>
        </p>
    </div>
    <?php else: ?>
    
    <!-- Book Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Book Details</th>
                            <th>Borrowed On</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <?php if ($filter == 'returned'): ?>
                            <th>Returned On</th>
                            <?php else: ?>
                            <th>Time Left</th>
                            <?php endif; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): 
                            $bookImgPath = getBookImagePath($book);
                            $bookImgUrl = str_replace('../', $base_url, $bookImgPath);
                            $status = $book['issue_book_status'];
                            $statusClass = '';
                            $statusBadge = '';
                            
                            switch ($status) {
                                case 'Issue':
                                    $statusClass = 'success';
                                    $statusBadge = 'Active';
                                    break;
                                case 'Overdue':
                                    $statusClass = 'danger';
                                    $statusBadge = 'Overdue';
                                    break;
                                case 'Returned':
                                    $statusClass = 'info';
                                    $statusBadge = 'Returned';
                                    break;
                                default:
                                    $statusClass = 'secondary';
                                    $statusBadge = $status;
                            }
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $bookImgUrl; ?>" class="me-3" alt="<?php echo htmlspecialchars($book['book_name']); ?>" 
                                         style="width: 50px; height: 70px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($book['book_name']); ?></h6>
                                        <div class="small text-muted mb-1">
                                            <?php echo htmlspecialchars($book['author_name'] ?? 'Unknown Author'); ?>
                                        </div>
                                        <div class="small">
                                            <span class="badge bg-secondary">ISBN: <?php echo htmlspecialchars($book['book_isbn_number']); ?></span>
                                            <?php if (!empty($book['category_name'])): ?>
                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($book['category_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($book['issue_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($book['expected_return_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusBadge; ?></span>
                                <?php if ($status == 'Overdue' && isset($book['fines_amount']) && $book['fines_amount'] > 0): ?>
                                <div class="small text-danger mt-1">
                                    $<?php echo number_format($book['fines_amount'], 2); ?> fine
                                    <?php if ($book['fines_status'] == 'Unpaid'): ?>
                                    <span class="badge bg-danger">Unpaid</span>
                                    <?php else: ?>
                                    <span class="badge bg-success">Paid</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <?php if ($status == 'Returned'): ?>
                            <td><?php echo date('M d, Y', strtotime($book['return_date'])); ?></td>
                            <?php else: ?>
                            <td>
                                <?php if ($status == 'Overdue'): ?>
                                <span class="text-danger">
                                    <?php echo abs($book['days_overdue']); ?> day<?php echo abs($book['days_overdue']) > 1 ? 's' : ''; ?> overdue
                                </span>
                                <?php elseif ($book['days_remaining'] <= 0): ?>
                                <span class="text-danger">Due today</span>
                                <?php else: ?>
                                <span class="<?php echo $book['days_remaining'] <= 2 ? 'text-warning' : 'text-success'; ?>">
                                    <?php echo $book['days_remaining']; ?> day<?php echo $book['days_remaining'] > 1 ? 's' : ''; ?> left
                                </span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="books.php?book_id=<?php echo $book['book_id']; ?>" class="btn btn-sm btn-outline-secondary open-modal" data-book-id="<?php echo $book['book_id'];?>">
                                        <i class="bi bi-info-circle"></i> Details
                                    </a>
                                    
                                    <?php if ($status == 'Overdue' && isset($book['fines_amount']) && $book['fines_amount'] > 0 && $book['fines_status'] == 'Unpaid'): ?>
                                    <a href="my_fines.php" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-cash"></i> See Fine
                                    </a>
                                    <?php endif; ?>
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