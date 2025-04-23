<?php
// book.php - Modern book catalog/grid view
include 'database_connection.php';
include 'function.php';
include 'header.php';
validate_session();
// Use the improved function to get categories
$all_categories = getAllCategories($connect);

// Handle category filter
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';

// Get pagination parameters
$limit = 30; // Number of books per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Use the improved function for paginated books
$all_books = getPaginatedBooks($connect, $limit, $offset, $selected_category ?: null);

// Get total books for pagination
$total_books = countTotalBooks($connect, $selected_category ?: null);
$total_pages = ceil($total_books / $limit);
?>

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

    <!-- Updated Book Grid -->
    <div class="d-flex flex-wrap justify-content-center gap-3" id="book-grid">
        <?php foreach ($all_books as $book):
            $base_url = base_url();
            $bookImgPath = getBookImagePath($book);
            $bookImgUrl = str_replace('../', $base_url, $bookImgPath);
            
            $authors = getBookAuthors($connect, $book['book_id']);
            $author_names = array_column($authors, 'author_name');
            $author_string = implode(', ', $author_names);
            
            $availability = getBookAvailability($connect, $book['book_id'], $book['book_no_of_copy']);
            $is_available = $availability['is_available'];
        ?>
        <div class="book-card book-card-wrapper" data-id="<?php echo $book['book_id']; ?>" data-isbn="<?php echo htmlspecialchars($book['book_isbn_number']); ?>">
            <div class="card book-item shadow-sm h-100">
                <div class="position-relative overflow-hidden">
                    <img src="<?php echo $bookImgUrl; ?>" class="card-img-top book-cover" alt="<?php echo htmlspecialchars($book['book_name']); ?>">
                    <span class="availability-badge <?php echo $is_available ? 'bg-success' : 'bg-danger'; ?>">
                        <?php echo $is_available ? 'Available' : 'Unavailable'; ?>
                    </span>
                    <span class="category-badge">
                        <?php echo htmlspecialchars($book['category_name']); ?>
                    </span>
                </div>
                <div class="card-body d-flex flex-column">
                    <span class="bk-title"><?php echo htmlspecialchars($book['book_name']); ?></span>
                    <p class="book-author">by <?php echo htmlspecialchars($author_string); ?></p>
                    <div class="card-footer-area d-flex justify-content-end align-items-center mt-auto">
                        <button class="btn btn-primary view-book-btn" data-id="<?php echo $book['book_id']; ?>">
                            View Details
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="d-flex justify-content-center mt-5">
        <nav aria-label="Book pagination">
            <ul class="pagination">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="book.php?page=<?php echo $page - 1; ?><?php echo $selected_category ? '&category=' . $selected_category : ''; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="book.php?page=<?php echo $i; ?><?php echo $selected_category ? '&category=' . $selected_category : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="book.php?page=<?php echo $page + 1; ?><?php echo $selected_category ? '&category=' . $selected_category : ''; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
    
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
    
    // Search functionality - improved to also search by ISBN
    const searchInput = document.getElementById('search-books');
    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const bookCards = document.querySelectorAll('.book-card');
        
        bookCards.forEach(card => {
            const bookTitle = card.querySelector('.bk-title').textContent.toLowerCase();
            const bookAuthor = card.querySelector('.book-author').textContent.toLowerCase();

            const bookIsbn = card.dataset.isbn.toLowerCase();
            
            if (bookTitle.includes(searchTerm) || 
                bookAuthor.includes(searchTerm) || 
                bookIsbn.includes(searchTerm)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
    
    // Sort functionality
    const sortSelect = document.getElementById('sort-books');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const sortValue = this.value;
            const bookGrid = document.getElementById('book-grid');
            
            // Fixed: Class selector should match your HTML structure
            const bookCards = Array.from(document.querySelectorAll('.book-card-wrapper'));
            
            if (bookCards.length === 0) {
                console.log('No books found with selector .book-card-wrapper');
                return;
            }
            
            console.log(`Sorting ${bookCards.length} books by: ${sortValue}`);
            
            // Sort books based on selected option
            bookCards.sort((a, b) => {
                // Fixed: Consistent class selectors and comparing appropriate values
                const titleA = a.querySelector('.book-title')?.textContent || '';
                const titleB = b.querySelector('.book-title')?.textContent || '';
                
                // Fixed: Use title for title comparisons (was comparing title to author)
                const authorA = a.querySelector('.book-author')?.textContent || '';
                const authorB = b.querySelector('.book-author')?.textContent || '';
                
                const idA = parseInt(a.dataset.id || '0');
                const idB = parseInt(b.dataset.id || '0');
                
                switch (sortValue) {
                    case 'title-asc':
                        return titleA.localeCompare(titleB);
                    case 'title-desc':
                        return titleB.localeCompare(titleA);
                    case 'newest':
                        return idB - idA;
                    case 'oldest':
                        return idA - idB;
                    case 'popular':
                        // Extract available and total copies for popularity calculation
                        const availableA = parseInt(a.querySelector('.copy-count')?.textContent.match(/(\d+)\/\d+/)?.[1] || '0');
                        const totalA = parseInt(a.querySelector('.copy-count')?.textContent.match(/\d+\/(\d+)/)?.[1] || '1');
                        const availableB = parseInt(b.querySelector('.copy-count')?.textContent.match(/(\d+)\/\d+/)?.[1] || '0');
                        const totalB = parseInt(b.querySelector('.copy-count')?.textContent.match(/\d+\/(\d+)/)?.[1] || '1');
                        
                        // Calculate checkout ratio (higher = more popular)
                        const popularityA = (totalA - availableA) / totalA;
                        const popularityB = (totalB - availableB) / totalB;
                        
                        return popularityB - popularityA;
                    default:
                        return 0;
                }
            });
            
            // Fixed: Clear grid before appending sorted cards
            while (bookGrid.firstChild) {
                bookGrid.removeChild(bookGrid.firstChild);
            }
            
            // Reappend sorted cards
            bookCards.forEach(card => {
                bookGrid.appendChild(card);
            });
            
            console.log('Sorting complete');
        });
    }
});
</script>

<?php include 'footer.php'; ?>