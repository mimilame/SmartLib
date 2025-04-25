<?php
    // author.php - Consistent with book.php design
    include 'database_connection.php';
    include 'function.php';
    include 'header.php';
    validate_session();

    // Get all authors
    $all_authors = getAllAuthors($connect);
    $base_url = base_url();    
    // Handle search
    $search_term = isset($_GET['search']) ? $_GET['search'] : '';

    // Get pagination parameters
    $limit = 20; // Number of authors per page
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $limit;

    // Handle sorting
    $sort_options = ['name-asc', 'name-desc', 'popular', 'books-count'];
    $selected_sort = isset($_GET['sort']) && in_array($_GET['sort'], $sort_options) ? $_GET['sort'] : 'name-asc';

    // Get authors based on filters
    if (!empty($search_term)) {
        $authors = searchAuthors($connect, $search_term, $limit, $offset);
        $total_authors = count(searchAuthors($connect, $search_term, 1000000, 0)); // For pagination
    } else {
        $authors = getSortedAuthors($connect, $limit, $offset, $selected_sort);
        $total_authors = countTotalAuthors($connect);
    }

    $total_pages = ceil($total_authors / $limit);

    // Get featured authors
    $featured_authors = getTopAuthorsWithBooks($connect, 5);
?>

<div class="container-fluid py-4 mt-5 px-5">
    <!-- Hero Section - Consistent with book.php -->
    <div class="card bg-dark text-white mb-4 border-0 rounded-3 overflow-hidden">
        <img src="asset/img/library-hero.jpg" class="card-img opacity-50" alt="Library" style="height: 250px; object-fit: cover;">
        <div class="card-img-overlay d-flex flex-column justify-content-center">
            <div class="container">
                <h1 class="display-4 fw-bold">Discover Our Authors</h1>
                <p class="lead">Explore the minds behind your favorite books</p>
                <div class="row g-3 align-items-center mt-2">
                    <div class="col-12 col-md-6">
                        <form action="author.php" method="GET" class="d-flex">
                            <div class="input-group">
                                <input type="text" name="search" id="search-authors" class="form-control form-control-lg" 
                                    placeholder="Search authors by name..." 
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
    <div class="row">
        <!-- Featured Authors Section with infinite carousel -->
        <div class="col-md-6 mt-5">
            <h2 class="mb-4">Featured Authors</h2>
            <div class="featured-authors-carousel">            
                <div class="authors-row">
                    <!-- Original authors section -->
                    <div class="authors-row-section original">
                        <?php 
                        foreach ($featured_authors as $index => $author):
                            $authorImgPath = getAuthorImagePath($author);
                            $authorImgUrl = str_replace('../', $base_url, $authorImgPath);
                            $bookCount = count($author['books']);
                        ?>
                        <div class="featured-author">
                            <div class="author-card card shadow-sm h-100" data-id="<?php echo $author['author_id']; ?>">
                                <div class="position-relative overflow-hidden">
                                    <img src="<?php echo $authorImgUrl; ?>" class="card-img-top author-img w-100 h-100" alt="<?php echo htmlspecialchars($author['author_name']); ?>" >
                                    <?php if ($index < 5): ?>
                                    <span class="author-badge badge bg-warning text-dark">
                                        <i class="bi bi-star-fill"></i> Featured
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="author-name"><?php echo htmlspecialchars($author['author_name']); ?></h5>
                                    <p class="author-books">
                                        <i class="bi bi-book"></i> <?php echo $bookCount; ?> books in collection
                                    </p>
                                    <div class="mt-auto text-end">
                                        <button class="btn btn-primary view-author-btn" data-id="<?php echo $author['author_id']; ?>">
                                            View Profile
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
                        // Calculate number of items and adjust animation for authors carousel
                        const authorItemWidth = document.querySelector('.featured-author').offsetWidth;
                        const authorGapWidth = 20; // Same as gap in CSS
                        const authorItemCount = document.querySelectorAll('.authors-row-section.original .featured-author').length;
                        const authorTotalWidth = (authorItemWidth + authorGapWidth) * authorItemCount;
                        
                        // Update animation for authors carousel
                        document.styleSheets[0].insertRule(`
                            @keyframes authors-scroll {
                                0% { transform: translateX(0); }
                                100% { transform: translateX(-${authorTotalWidth}px); }
                            }
                        `, document.styleSheets[0].cssRules.length);
                    });
                    </script>
                </div>
            </div>
        </div>
        <!-- Trending Authors Section - Using Bootstrap styling -->
        <div class="col-md-6 mt-5">
            <!-- Time Period Tabs - Bootstrap styling -->
            <ul class="nav nav-tabs mb-4" id="authorTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="weekly-tab" data-bs-toggle="tab" data-bs-target="#weekly" type="button" role="tab">This Week</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly" type="button" role="tab">This Month</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="yearly-tab" data-bs-toggle="tab" data-bs-target="#yearly" type="button" role="tab">This Year</button>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content" id="authorTabContent">
                <?php
                $authorTimeStats = getAuthorTimeStats($connect);
                $formattedStats = formatAuthorTimeStats($authorTimeStats);
                
                $periods = ['weekly' => 'weekly', 'monthly' => 'monthly', 'yearly' => 'yearly'];
                
                foreach ($periods as $tabId => $period):
                    $activeClass = $tabId === 'weekly' ? 'show active' : '';
                ?>
                <div class="tab-pane fade <?php echo $activeClass; ?>" id="<?php echo $tabId; ?>" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px">Rank</th>
                                    <th>Author</th>
                                    <th style="width: 120px">Borrows</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($formattedStats[$period] as $index => $author): 
                                    $authorData = ['author_id' => $author['author_id'], 'author_profile' => $author['author_profile'] ?? ''];
                                    $authorImgPath = getAuthorImagePath($authorData);
                                    $authorImgUrl = str_replace('../', $base_url, $authorImgPath);
                                    $rankClass = ($index < 3) ? "bg-" . ($index === 0 ? "warning" : ($index === 1 ? "secondary" : "danger")) : "";
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge rounded-pill <?php echo $rankClass; ?> text-white fs-6">
                                            <?php echo ($index + 1); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $authorImgUrl; ?>" alt="<?php echo htmlspecialchars($author['author_name']); ?>" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div><?php echo htmlspecialchars($author['author_name']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary rounded-pill fs-6">
                                            <?php echo $author['borrow_count']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <!-- Search Results Header -->
    <?php if (!empty($search_term)): ?>
    <div class="alert alert-info">
        <h4>Search Results for: "<?php echo htmlspecialchars($search_term); ?>"</h4>
        <p>Found <?php echo $total_authors; ?> results</p>
        <a href="author.php" class="btn btn-outline-primary">Clear Search</a>
    </div>
    <?php endif; ?>

    <!-- Filters and Sorting - Similar to book.php -->
    <div class="row mb-4">
        <div class="col-md-9">
            <div class="d-flex flex-wrap gap-2">
                <!-- Alphabetical Index -->
                <?php
                $alphabet = range('A', 'Z');
                foreach ($alphabet as $letter):
                ?>
                <a href="author.php?filter=<?php echo $letter; ?><?php echo !empty($selected_sort) ? '&sort='.$selected_sort : ''; ?>" 
                   class="btn <?php echo isset($_GET['filter']) && $_GET['filter'] == $letter ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <?php echo $letter; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-3">
            <form id="sort-form" action="author.php" method="GET">
                <?php if (isset($_GET['filter'])): ?>
                    <input type="hidden" name="filter" value="<?php echo $_GET['filter']; ?>">
                <?php endif; ?>
                <?php if (!empty($search_term)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                <?php endif; ?>
                <select class="form-select" id="sort-authors" name="sort" onchange="document.getElementById('sort-form').submit();">
                    <option value="name-asc" <?php echo $selected_sort == 'name-asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                    <option value="name-desc" <?php echo $selected_sort == 'name-desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                    <option value="popular" <?php echo $selected_sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                    <option value="books-count" <?php echo $selected_sort == 'books-count' ? 'selected' : ''; ?>>Number of Books</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Authors Grid - Similar to book grid -->
    <div class="d-flex flex-wrap justify-content-center gap-3" id="author-grid">
        <?php if (empty($authors)): ?>
            <div class="alert alert-info w-100 text-center">
                <h4>No authors found</h4>
                <p>Try a different search term or filter</p>
            </div>
        <?php else: ?>
            <?php foreach ($authors as $author):             
                $authorImgPath = getAuthorImagePath($author);
                $authorImgUrl = str_replace('../', $base_url, $authorImgPath);
                $bookCount = isset($author['book_count']) ? $author['book_count'] : 0;
                $borrowCount = isset($author['borrow_count']) ? $author['borrow_count'] : 0;
            ?>
            <div class="author-card card shadow-sm" style="width: 220px;" data-id="<?php echo $author['author_id']; ?>">
                <div class="position-relative overflow-hidden">
                    <img src="<?php echo $authorImgUrl; ?>" class="card-img-top author-img" alt="<?php echo htmlspecialchars($author['author_name']); ?>" style="width: 100%;height: 200px; object-fit: cover;">
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?php echo htmlspecialchars($author['author_name']); ?></h5>
                    <div class="author-stats my-2">
                        <p class="card-text text-muted mb-0">
                            <i class="bi bi-book"></i> <?php echo $bookCount; ?> books
                        </p>
                        <p class="card-text text-muted mb-0">
                            <i class="bi bi-graph-up"></i> <?php echo $borrowCount; ?> borrows
                        </p>
                    </div>
                    <div class="mt-auto text-end">
                        <button class="btn btn-primary view-author-btn" data-id="<?php echo $author['author_id']; ?>">
                            View Profile
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Pagination - Same as book.php -->
    <?php if ($total_pages > 1): ?>
    <div class="d-flex justify-content-center mt-5">
        <nav aria-label="Author pagination">
            <ul class="pagination">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="author.php?page=<?php echo $page - 1; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?><?php echo !empty($selected_sort) ? '&sort='.$selected_sort : ''; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="author.php?page=<?php echo $i; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?><?php echo !empty($selected_sort) ? '&sort='.$selected_sort : ''; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="author.php?page=<?php echo $page + 1; ?><?php echo isset($_GET['filter']) ? '&filter=' . $_GET['filter'] : ''; ?><?php echo !empty($selected_sort) ? '&sort='.$selected_sort : ''; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
    
    
</div>

<!-- Author Detail Modal -->
<div class="modal fade" id="authorModal" tabindex="-1" aria-labelledby="authorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="author-detail-container">
                <!-- Author details will be loaded here -->
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
        // Author card click handlers
        const authorCards = document.querySelectorAll('.author-card');
        const viewButtons = document.querySelectorAll('.view-author-btn');
        const authorModal = new bootstrap.Modal(document.getElementById('authorModal'));
        
        function loadAuthorDetails(authorId) {
            // Show loading spinner
            document.getElementById('author-detail-container').innerHTML = `
                <div class="d-flex justify-content-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            // Load author details via AJAX
            fetch(`author_details_partial.php?author_id=${authorId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('author-detail-container').innerHTML = html;
                    
                    // Load author's books
                    const booksBySameAuthorContainer = document.getElementById('books-by-author-container');
                    if (booksBySameAuthorContainer) {
                        fetch(`author_books_partial.php?author_id=${authorId}`)
                            .then(response => response.text())
                            .then(html => {
                                booksBySameAuthorContainer.innerHTML = html;
                                
                                // Add event listeners to book cards
                                const bookCards = booksBySameAuthorContainer.querySelectorAll('.book-card');
                                bookCards.forEach(card => {
                                    card.addEventListener('click', function() {
                                        const bookId = this.dataset.id;
                                        // Load book details - opens in a new modal or replaces the current one
                                        window.location.href = `book.php?book_id=${bookId}`;
                                    });
                                });
                            })
                            .catch(error => {
                                console.error('Error loading author books:', error);
                                booksBySameAuthorContainer.innerHTML = `
                                    <div class="alert alert-warning">
                                        Unable to load author's books.
                                    </div>
                                `;
                            });
                    }
                    
                    // Load similar authors
                    const similarAuthorsContainer = document.getElementById('similar-authors-container');
                    if (similarAuthorsContainer) {
                        fetch(`similar_authors_partial.php?author_id=${authorId}`)
                            .then(response => response.text())
                            .then(html => {
                                similarAuthorsContainer.innerHTML = html;
                                
                                // Add event listeners to related author cards
                                const similarAuthorCards = similarAuthorsContainer.querySelectorAll('.similar-author-card');
                                similarAuthorCards.forEach(card => {
                                    card.addEventListener('click', function() {
                                        const relatedAuthorId = this.dataset.id;
                                        loadAuthorDetails(relatedAuthorId);
                                    });
                                });
                            })
                            .catch(error => {
                                console.error('Error loading similar authors:', error);
                                similarAuthorsContainer.innerHTML = `
                                    <div class="alert alert-warning">
                                        Unable to load similar authors.
                                    </div>
                                `;
                            });
                    }
                })
                .catch(error => {
                    console.error('Error loading author details:', error);
                    document.getElementById('author-detail-container').innerHTML = `
                        <div class="alert alert-danger m-3">
                            Error loading author details. Please try again.
                        </div>
                    `;
                });
        }
        
        // Author card click event
        authorCards.forEach(card => {
            card.addEventListener('click', function() {
                const authorId = this.dataset.id;
                loadAuthorDetails(authorId);
                authorModal.show();
            });
        });
        
        // View button click event (prevent propagation to card)
        viewButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const authorId = this.dataset.id;
                loadAuthorDetails(authorId);
                authorModal.show();
            });
        });
        
        // Real-time search functionality
        const searchInput = document.getElementById('search-authors');
        if (searchInput) {
            // Keep this for real-time filtering of currently displayed authors
            searchInput.addEventListener('keyup', function(e) {
                // Only filter if we're not submitting the form with Enter key
                if (e.key !== 'Enter') {
                    const searchTerm = this.value.toLowerCase().trim();
                    const authorCards = document.querySelectorAll('.author-card');
                    
                    authorCards.forEach(card => {
                        const authorName = card.querySelector('.card-title').textContent.toLowerCase();
                        
                        if (authorName.includes(searchTerm)) {
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
<!-- Author Detail Modal -->
<div class="modal fade" id="authorModal" tabindex="-1" aria-labelledby="authorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="author-detail-container">
                <!-- Author details will be loaded here -->
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
        // Author card click handlers
        const authorCards = document.querySelectorAll('.author-card');
        const authorModal = new bootstrap.Modal(document.getElementById('authorModal'));
        
        function loadAuthorDetails(authorId) {
            // Show loading spinner
            document.getElementById('author-detail-container').innerHTML = `
                <div class="d-flex justify-content-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            // Load author details via AJAX
            fetch(`author_details_partial.php?author_id=${authorId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('author-detail-container').innerHTML = html;
                    
                    // Load author's books
                    const authorBooksContainer = document.getElementById('author-books-container');
                    if (authorBooksContainer) {
                        fetch(`author_books_partial.php?author_id=${authorId}`)
                            .then(response => response.text())
                            .then(html => {
                                authorBooksContainer.innerHTML = html;
                                
                                // Add event listeners to book cards
                                const bookCards = authorBooksContainer.querySelectorAll('.book-card');
                                bookCards.forEach(card => {
                                    card.addEventListener('click', function() {
                                        const bookId = this.dataset.id;
                                        // Open book modal
                                        loadBookDetails(bookId);
                                        // Close author modal first
                                        authorModal.hide();
                                        // Show book modal
                                        const bookModal = new bootstrap.Modal(document.getElementById('bookModal'));
                                        bookModal.show();
                                    });
                                });
                            })
                            .catch(error => {
                                console.error('Error loading author books:', error);
                                authorBooksContainer.innerHTML = `
                                    <div class="alert alert-warning">
                                        Unable to load author's books.
                                    </div>
                                `;
                            });
                    }
                })
                .catch(error => {
                    console.error('Error loading author details:', error);
                    document.getElementById('author-detail-container').innerHTML = `
                        <div class="alert alert-danger m-3">
                            Error loading author details. Please try again.
                        </div>
                    `;
                });
        }
        
        // Author card click event
        authorCards.forEach(card => {
            card.addEventListener('click', function() {
                const authorId = this.dataset.id;
                loadAuthorDetails(authorId);
                authorModal.show();
            });
        });
        
        // Add author ID data attribute to all author cards
        document.querySelectorAll('.author-card').forEach(card => {
            // If the card doesn't have an ID attribute yet, get it from a child element
            if (!card.dataset.id) {
                // Try to extract ID from author name or other means
                // This is a fallback - ideally each card would have data-id set in the PHP
                const authorName = card.querySelector('.author-name').textContent;
                card.dataset.id = authorName.split(' ')[0].toLowerCase() + '_id';
            }
        });
    });
</script>

<?php
include 'footer.php';
?>