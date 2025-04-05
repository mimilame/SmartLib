<?php
// book.php - Modern book catalog/grid view
include 'database_connection.php';
include 'function.php';
include 'header.php';

// Get all books
$query = "SELECT b.*, c.category_name 
          FROM lms_book b 
          LEFT JOIN lms_category c ON b.category_id = c.category_id 
          WHERE b.book_status = 'Enable' 
          ORDER BY b.book_id DESC";
$statement = $connect->prepare($query);
$statement->execute();
$all_books = $statement->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for filter
$category_query = "SELECT * FROM lms_category WHERE category_status = 'Enable' ORDER BY category_name ASC";
$category_statement = $connect->prepare($category_query);
$category_statement->execute();
$all_categories = $category_statement->fetchAll(PDO::FETCH_ASSOC);

// Handle category filter
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';
?>
<div class="custom-bg">
<div class="container-fluid py-4 mt-5 px-5">
    <!-- Hero Section -->
    <div class="card bg-dark text-white mb-4 border-0 rounded-3 overflow-hidden">
        <img src="asset/img/library-hero.jpg" class="card-img opacity-50" alt="Library" style="height: 250px; object-fit: cover;">
        <div class="card-img-overlay d-flex flex-column justify-content-center">
            <div class="container">
                <h1 class="display-4 fw-bold">Library Catalog</h1>
                <p class="lead">Discover our collection of books and resources</p>
                <div class="row g-3 align-items-center mt-2">
                    <div class="col-12 col-md-6">
                        <div class="input-group">
                            <input type="text" id="search-books" class="form-control form-control-lg" placeholder="Search books by title, author, or ISBN...">
                            <button class="btn btn-primary" type="button">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="row mb-4">
        <div class="col-md-9">
            <div class="d-flex flex-wrap gap-2">
                <a href="book.php" class="btn <?php echo empty($selected_category) ? 'btn-primary' : 'btn-outline-primary'; ?>">All Books</a>
                <?php foreach ($all_categories as $category): ?>
                    <a href="book.php?category=<?php echo $category['category_id']; ?>" 
                       class="btn <?php echo $selected_category == $category['category_id'] ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="sort-books">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="title-asc">Title (A-Z)</option>
                <option value="title-desc">Title (Z-A)</option>
                <option value="popular">Most Popular</option>
            </select>
        </div>
    </div>

    <!-- Book Grid -->
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-4 gap-5" id="book-grid">
        <?php foreach ($all_books as $book): 
            // Skip books not in selected category if filter is active
            if (!empty($selected_category) && $book['category_id'] != $selected_category) continue;
            
            // Get book cover image
            $book_img = !empty($book['book_img']) ? 'asset/img/' . $book['book_img'] : 'asset/img/book_placeholder.png';
            
            // Get authors
            $author_query = "SELECT a.author_name FROM lms_author a 
                            JOIN lms_book_author ba ON a.author_id = ba.author_id 
                            WHERE ba.book_id = :book_id";
            $author_statement = $connect->prepare($author_query);
            $author_statement->bindParam(':book_id', $book['book_id'], PDO::PARAM_INT);
            $author_statement->execute();
            $authors = $author_statement->fetchAll(PDO::FETCH_ASSOC);
            
            $author_names = array_column($authors, 'author_name');
            $author_string = implode(', ', $author_names);
            
            // Check availability
            $query = "SELECT COUNT(*) as borrowed_copies 
                    FROM lms_issue_book 
                    WHERE book_id = :book_id 
                    AND (issue_book_status = 'Issue' OR issue_book_status = 'Not Return')";
            $statement = $connect->prepare($query);
            $statement->bindParam(':book_id', $book['book_id'], PDO::PARAM_INT);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            
            $borrowed_copies = $result['borrowed_copies'];
            $available_copies = $book['book_no_of_copy'] - $borrowed_copies;
            $is_available = $available_copies > 0;
        ?>
        <div class="col book-card" data-id="<?php echo $book['book_id']; ?>">
            <div class="card h-100 book-item shadow-sm">
                <div class="position-relative">
                    <img src="<?php echo $book_img; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($book['book_name']); ?>" style="height: 220px; object-fit: cover;">
                    <div class="position-absolute top-0 start-0 m-2">
                        <span class="badge <?php echo $is_available ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $is_available ? 'Available' : 'Unavailable'; ?>
                        </span>
                    </div>
                    <div class="position-absolute top-0 end-0 m-2">
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($book['category_name']); ?></span>
                    </div>
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-truncate"><?php echo htmlspecialchars($book['book_name']); ?></h5>
                    <p class="card-text text-muted small text-truncate">by <?php echo htmlspecialchars($author_string); ?></p>
                    <div class="mt-auto pt-2 d-flex justify-content-between align-items-center">
                        <button class="btn btn-sm btn-primary view-book-btn" data-id="<?php echo $book['book_id']; ?>">
                            View Details
                        </button>
                        <div class="text-end small">
                            <i class="bi bi-book me-1"></i><?php echo $available_copies; ?>/<?php echo $book['book_no_of_copy']; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="p-5 mt-5 mb-5"></div>
</div>

<!-- Book Detail Modal -->
<div class="modal fade" id="bookModal" tabindex="-1" aria-labelledby="bookModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
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

<!-- Custom Styling -->
<style>
    .book-item {
        transition: all 0.3s ease;
        border: none;
    }
    
    .book-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    
    .book-card {
        cursor: pointer;
        height: auto !important;
    }
    
    .btn-primary {
        background-color: #4361ee;
        border-color: #4361ee;
    }
    
    .btn-outline-primary {
        color: #4361ee;
        border-color: #4361ee;
    }
    
    .btn-outline-primary:hover {
        background-color: #4361ee;
        border-color: #4361ee;
    }
    
    .badge.bg-success {
        background-color: #2ecc71 !important;
    }
    
    .badge.bg-danger {
        background-color: #e74c3c !important;
    }
    
    .modal-xl {
        max-width: 1200px;
    }
    
    /* Fix for tabs */
    .nav-tabs .nav-link.active {
        font-weight: 600;
        color: #4361ee;
        border-bottom: 2px solid #4361ee;
        border-top: none;
        border-left: none;
        border-right: none;
        background: transparent;
    }
    
    .nav-tabs .nav-link {
        border: none;
        border-bottom: 2px solid transparent;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Book card click handlers
    const bookCards = document.querySelectorAll('.book-card');
    const viewButtons = document.querySelectorAll('.view-book-btn');
    const bookModal = new bootstrap.Modal(document.getElementById('bookModal'));
    
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
                
                // Initialize any JS components inside the modal
                const reviewForm = document.getElementById('review-form');
                if (reviewForm) {
                    reviewForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        // Handle form submission via AJAX
                        // ...
                    });
                }
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
    
    // Book card click event
    bookCards.forEach(card => {
        card.addEventListener('click', function() {
            const bookId = this.dataset.id;
            loadBookDetails(bookId);
            bookModal.show();
        });
    });
    
    // View button click event (prevent propagation to card)
    viewButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const bookId = this.dataset.id;
            loadBookDetails(bookId);
            bookModal.show();
        });
    });
    
    // Search functionality
    const searchInput = document.getElementById('search-books');
    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const bookCards = document.querySelectorAll('.book-card');
        
        bookCards.forEach(card => {
            const bookTitle = card.querySelector('.card-title').textContent.toLowerCase();
            const bookAuthor = card.querySelector('.card-text').textContent.toLowerCase();
            
            if (bookTitle.includes(searchTerm) || bookAuthor.includes(searchTerm)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
    
    // Sort functionality
    const sortSelect = document.getElementById('sort-books');
    sortSelect.addEventListener('change', function() {
        const sortValue = this.value;
        const bookGrid = document.getElementById('book-grid');
        const bookCards = Array.from(document.querySelectorAll('.book-card'));
        
        // Sort books based on selected option
        bookCards.sort((a, b) => {
            const titleA = a.querySelector('.card-title').textContent;
            const titleB = b.querySelector('.card-title').textContent;
            
            switch (sortValue) {
                case 'title-asc':
                    return titleA.localeCompare(titleB);
                case 'title-desc':
                    return titleB.localeCompare(titleA);
                case 'newest':
                    return parseInt(b.dataset.id) - parseInt(a.dataset.id);
                case 'oldest':
                    return parseInt(a.dataset.id) - parseInt(b.dataset.id);
                // Add other sort options as needed
                default:
                    return 0;
            }
        });
        
        // Reappend sorted cards
        bookCards.forEach(card => {
            bookGrid.appendChild(card);
        });
    });
});
</script>

<?php include 'footer.php'; ?>