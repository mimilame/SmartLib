<?php
    // book.php - Modern book catalog/grid view with optimized functions
    include 'database_connection.php';
    include 'function.php';
    include 'header.php';
    validate_session();

    // Use the improved function to get categories
    $all_categories = getAllCategories($connect);

    // Handle category filter
    $selected_category = isset($_GET['category']) ? $_GET['category'] : '';

    // Handle search
    $search_term = isset($_GET['search']) ? $_GET['search'] : '';

    // Get pagination parameters
    $limit = 30; // Number of books per page
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $limit;

    // Handle sorting
    $sort_options = ['newest', 'oldest', 'title-asc', 'title-desc', 'popular', 'rating'];
    $selected_sort = isset($_GET['sort']) && in_array($_GET['sort'], $sort_options) ? $_GET['sort'] : 'newest';

    // Get books based on whether we're searching or browsing
    if (!empty($search_term)) {
        $all_books = searchBooks($connect, $search_term, $limit, $offset);
        $total_books = count(searchBooks($connect, $search_term, 1000000, 0)); // Get total count for pagination
    } else {
        // Use the optimized function for sorted books
        $all_books = getSortedBooks($connect, $limit, $offset, $selected_category ?: null, $selected_sort);
        
        // Get total books for pagination (reuse existing function)
        $total_books = countTotalBooks($connect, $selected_category ?: null);
    }

    $total_pages = ceil($total_books / $limit);

    // Get featured books for the carousel
    $featured_books = getFeaturedBooks($connect, 8);
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
                        <form action="book.php" method="GET" class="d-flex">
                            <div class="input-group">
                                <input type="text" name="search" id="search-books" class="form-control form-control-lg" 
                                    placeholder="Search books by title, author, or ISBN..." 
                                    value="<?php echo htmlspecialchars($search_term); ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Books Carousel (Only show if not searching) -->
    <?php if (empty($search_term)): ?>
    <div class="mb-5">
        <h2 class="mb-4">Featured Books</h2>
        <div class="featured-books-carousel">            
            <div class="featured-row">
                <!-- Original books section -->
                <div class="featured-row-section original">
                    <?php 
                    $counter = 0;
                    foreach ($featured_books as $book):
                        $base_url = base_url();
                        $bookImgPath = getBookImagePath($book);
                        $bookImgUrl = str_replace('../', $base_url, $bookImgPath);
                        $author_string = $book['authors'] ? implode(', ', array_column($book['authors'], 'author_name')) : 'Unknown';
                        $counter++;
                    ?>
                    <div class="featured-book">
                        <div class="card book-item shadow-sm h-100">
                            <div class="position-relative overflow-hidden">
                                <img src="<?php echo $bookImgUrl; ?>" class="card-img-top book-cover" alt="<?php echo htmlspecialchars($book['book_name']); ?>">
                                <span class="featured-badge bg-warning text-dark">
                                    <i class="bi bi-star-fill"></i> Featured
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
                
                <!-- Duplicate section for infinite scroll effect -->
                <div class="featured-row-section clone">
                    <?php 
                    foreach ($featured_books as $book):
                        $base_url = base_url();
                        $bookImgPath = getBookImagePath($book);
                        $bookImgUrl = str_replace('../', $base_url, $bookImgPath);
                        $author_string = $book['authors'] ? implode(', ', array_column($book['authors'], 'author_name')) : 'Unknown';
                    ?>
                    <div class="featured-book">
                        <div class="card book-item shadow-sm h-100">
                            <div class="position-relative overflow-hidden">
                                <img src="<?php echo $bookImgUrl; ?>" class="card-img-top book-cover" alt="<?php echo htmlspecialchars($book['book_name']); ?>">
                                <span class="featured-badge bg-warning text-dark">
                                    <i class="bi bi-star-fill"></i> Featured
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
                
                <!-- JS to update animation based on actual content -->
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Calculate number of items and adjust animation
                    const itemWidth = document.querySelector('.featured-book').offsetWidth;
                    const gapWidth = 20; // Same as gap in CSS
                    const itemCount = document.querySelectorAll('.featured-row-section.original .featured-book').length;
                    const totalWidth = (itemWidth + gapWidth) * itemCount;
                    
                    // Update animation
                    document.styleSheets[0].insertRule(`
                        @keyframes scroll-left {
                            0% { transform: translateX(0); }
                            100% { transform: translateX(-${totalWidth}px); }
                        }
                    `, document.styleSheets[0].cssRules.length);
                });
                </script>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Search Results Header -->
    <?php if (!empty($search_term)): ?>
    <div class="alert alert-info">
        <h4>Search Results for: "<?php echo htmlspecialchars($search_term); ?>"</h4>
        <p>Found <?php echo $total_books; ?> results</p>
        <a href="book.php" class="btn btn-outline-primary">Clear Search</a>
    </div>
    <?php endif; ?>

    <!-- Filters Section -->
    <div class="row mb-4">
        <div class="col-md-9">
            <div class="d-flex flex-wrap gap-2">
                <a href="book.php<?php echo !empty($selected_sort) ? '?sort='.$selected_sort : ''; ?>" 
                   class="btn <?php echo empty($selected_category) ? 'btn-primary' : 'btn-outline-primary'; ?>">All Books</a>
                <?php foreach ($all_categories as $category): ?>
                    <a href="book.php?category=<?php echo $category['category_id']; ?><?php echo !empty($selected_sort) ? '&sort='.$selected_sort : ''; ?>" 
                       class="btn <?php echo $selected_category == $category['category_id'] ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-3">
            <form id="sort-form" action="book.php" method="GET">
                <?php if (!empty($selected_category)): ?>
                    <input type="hidden" name="category" value="<?php echo $selected_category; ?>">
                <?php endif; ?>
                <?php if (!empty($search_term)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                <?php endif; ?>
                <select class="form-select" id="sort-books" name="sort" onchange="document.getElementById('sort-form').submit();">
                    <option value="newest" <?php echo $selected_sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $selected_sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="title-asc" <?php echo $selected_sort == 'title-asc' ? 'selected' : ''; ?>>Title (A-Z)</option>
                    <option value="title-desc" <?php echo $selected_sort == 'title-desc' ? 'selected' : ''; ?>>Title (Z-A)</option>
                    <option value="popular" <?php echo $selected_sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                    <option value="rating" <?php echo $selected_sort == 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Updated Book Grid -->
    <div class="d-flex flex-wrap justify-content-center gap-3" id="book-grid">
        <?php if (empty($all_books)): ?>
            <div class="alert w-100 text-center">
                <h4>No books found</h4>
                <p>Try a different search term or browse by category</p>
            </div>
        <?php else: ?>
            <?php foreach ($all_books as $book):
                $base_url = base_url();
                $bookImgPath = getBookImagePath($book);
                $bookImgUrl = str_replace('../', $base_url, $bookImgPath);
                
                // Use the author information directly from the book data
                $author_string = isset($book['authors']) ? $book['authors'] : 
                                (isset($book['authors_array']) ? implode(', ', array_column($book['authors_array'], 'author_name')) : '');
                
                
                // Get rating information
                $avg_rating = isset($book['avg_rating']) ? round($book['avg_rating'], 1) : 0;
                $review_count = isset($book['review_count']) ? $book['review_count'] : 0;
            ?>
            <div class="book-card book-card-wrapper" data-id="<?php echo $book['book_id']; ?>" data-isbn="<?php echo htmlspecialchars($book['book_isbn_number']); ?>">
                <div class="card book-item shadow-sm h-100">
                    <div class="position-relative overflow-hidden">
                        <img src="<?php echo $bookImgUrl; ?>" class="card-img-top book-cover" alt="<?php echo htmlspecialchars($book['book_name']); ?>">
                    </div>
                    <div class="card-body d-flex flex-column">
                        <span class="bk-title "><?php echo htmlspecialchars($book['book_name']); ?></span>
                        <p class="book-author">by <?php echo htmlspecialchars($author_string); ?></p>
                        
                        <?php if ($avg_rating > 0): ?>
                        <div class="book-rating mb-2">
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $avg_rating): ?>
                                        <i class="bi bi-star-fill text-warning"></i>
                                    <?php elseif ($i <= $avg_rating + 0.5): ?>
                                        <i class="bi bi-star-half text-warning"></i>
                                    <?php else: ?>
                                        <i class="bi bi-star text-warning"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span class="rating-count text-muted">(<?php echo $review_count; ?>)</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="copy-count text-muted small mb-2">
                            <?php 
                                $available = isset($book['availability']['available_count']) ? $book['availability']['available_count'] : 0;
                                $total = isset($book['book_no_of_copy']) ? $book['book_no_of_copy'] : 0;
                                echo "$available/$total copies available";
                            ?>
                        </div>
                        
                        <div class="card-footer-area d-flex justify-content-end align-items-center mt-auto">
                            <button class="btn btn-primary view-book-btn" data-id="<?php echo $book['book_id']; ?>">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="d-flex justify-content-center mt-5">
        <nav aria-label="Book pagination">
            <ul class="pagination">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="book.php?page=<?php echo $page - 1; ?><?php echo $selected_category ? '&category=' . $selected_category : ''; ?><?php echo !empty($selected_sort) ? '&sort='.$selected_sort : ''; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="book.php?page=<?php echo $i; ?><?php echo $selected_category ? '&category=' . $selected_category : ''; ?><?php echo !empty($selected_sort) ? '&sort='.$selected_sort : ''; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="book.php?page=<?php echo $page + 1; ?><?php echo $selected_category ? '&category=' . $selected_category : ''; ?><?php echo !empty($selected_sort) ? '&sort='.$selected_sort : ''; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" aria-label="Next">
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
                    
                    // Load related books
                    const relatedBooksContainer = document.getElementById('related-books-container');
                    if (relatedBooksContainer) {
                        fetch(`related_books_partial.php?book_id=${bookId}`)
                            .then(response => response.text())
                            .then(html => {
                                relatedBooksContainer.innerHTML = html;
                                
                                // Add event listeners to related book cards
                                const relatedBookCards = relatedBooksContainer.querySelectorAll('.related-book-card');
                                relatedBookCards.forEach(card => {
                                    card.addEventListener('click', function() {
                                        const relatedBookId = this.dataset.id;
                                        loadBookDetails(relatedBookId);
                                    });
                                });
                            })
                            .catch(error => {
                                console.error('Error loading related books:', error);
                                relatedBooksContainer.innerHTML = `
                                    <div class="alert alert-warning">
                                        Unable to load related books.
                                    </div>
                                `;
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
        
        // Real-time search functionality
        const searchInput = document.getElementById('search-books');
        if (searchInput) {
            // Keep this for real-time filtering of currently displayed books
            searchInput.addEventListener('keyup', function(e) {
                // Only filter if we're not submitting the form with Enter key
                if (e.key !== 'Enter') {
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
                }
            });
        }
    });
</script>


<?php include 'footer.php'; ?>