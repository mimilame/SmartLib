<?php
    // author.php - Consistent with book.php design
    include '../database_connection.php';
    include '../function.php';
    include '../header.php';
    authenticate_user();
    
    // Get pagination parameters
    $limit = 30; // Number of authors per page
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $limit;

    // Handle search
    $search_term = isset($_GET['search']) ? $_GET['search'] : '';

    // Handle sorting
    $sort_options = ['name-asc', 'name-desc', 'popular', 'books-count'];
    $selected_sort = isset($_GET['sort']) && in_array($_GET['sort'], $sort_options) ? $_GET['sort'] : 'name-asc';

    // Handle letter filter
    $letter_filter = isset($_GET['filter']) ? $_GET['filter'] : null;

    // Get authors based on filters
    if (!empty($search_term)) {
        $authors = searchAuthors($connect, $search_term, $limit, $offset, $selected_sort);
        $total_authors = countSearchAuthors($connect, $search_term);
    } elseif ($letter_filter !== null) {
        $authors = getFilteredAuthors($connect, $limit, $offset, $selected_sort, $letter_filter);
        $total_authors = countFilteredAuthors($connect, $letter_filter);
    } else {
        $authors = getAllSortedAuthors($connect, $limit, $offset, $selected_sort);
        $total_authors = countTotalAuthors($connect);
    }

    $total_pages = ceil($total_authors / $limit);
    
    // Featured authors section - Always load these regardless of filters
    $featured_authors = getTopAuthorsWithBooks($connect, 5);
    
    $base_url = base_url();
?>
    <!-- Hero Section - Consistent with book.php -->
    <div class="card bg-dark text-white mb-4 border-0 rounded-3 overflow-hidden">
        <img src="../asset/img/library-hero.jpg" class="card-img opacity-50" alt="Library" style="height: 250px; object-fit: cover;">
        <div class="card-img-overlay d-flex flex-column justify-content-center">
            <div class="container">
                <h1 class="display-4 fw-bold">Discover Our Authors</h1>
                <p class="lead">Explore the minds behind your favorite books</p>
                <div class="row g-3 align-items-center mt-2">
                    <div class="col-12 col-md-6">
                        <form action="author.php" method="GET" class="d-flex" id="author-search-form">
                            <!-- Preserve any existing filter parameters -->
                            <?php if(!empty($selected_sort) && $selected_sort != 'name-asc'): ?>
                                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($selected_sort); ?>">
                            <?php endif; ?>
                            
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
    <div class="row mb-4">        
        <h2 class="mb-4">Featured Authors</h2>    
        <div class="row">            
            <!-- Featured Authors Section with infinite carousel - Independent of filters -->
            <div class="col-md-8 mt-5">
                <div class="featured-authors-carousel">            
                    <div class="authors-row">
                        <!-- Original authors section -->
                        <div class="featured-row-section original">
                            <?php 
                            foreach ($featured_authors as $index => $author):
                                $bookCount = count($author['books']);
                                $authorImgPath = getAuthorImagePath($author);
                                $authorImgUrl = str_replace('../', $base_url, $authorImgPath);
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
                        <!-- Duplicate authors section -->
                        <div class="featured-row-section clone">
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
                            // Calculate number of items and adjust animation
                            const itemWidth = document.querySelector('.featured-author').offsetWidth;
                            const gapWidth = 20; // Same as gap in CSS
                            const itemCount = document.querySelectorAll('.featured-row-section.original .featured-author').length;
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
            <!-- Trending Authors Section - Using Bootstrap styling -->
            <div class="col-md-4 mt-3">
                <?php
                // Modified function to get all-time stats
                function getAllTimeAuthorStats($connect) {
                    return $connect->query("
                        SELECT 
                            a.author_id,
                            a.author_name,
                            COUNT(ib.issue_book_id) as borrow_count
                        FROM 
                            lms_author a
                        JOIN 
                            lms_book_author ba ON a.author_id = ba.author_id
                        JOIN 
                            lms_book b ON ba.book_id = b.book_id
                        JOIN 
                            lms_issue_book ib ON b.book_id = ib.book_id
                        WHERE 
                            a.author_status = 'Enable'
                        GROUP BY 
                            a.author_id, a.author_name
                        ORDER BY 
                            borrow_count DESC
                        LIMIT 5
                    ")->fetchAll(PDO::FETCH_ASSOC);
                }
                
                $authors = getAllTimeAuthorStats($connect);
                ?>
                
                <!-- Podium Section for Top 3 -->
                <div class="podium-container">
                    <?php if (count($authors) >= 3): ?>
                        <!-- 2nd Place (Left) -->
                        <div class="podium-item">
                            <?php
                            $author = $authors[1];
                            $authorData = ['author_id' => $author['author_id'], 'author_profile' => $author['author_profile'] ?? ''];
                            $authorImgPath = getAuthorImagePath($authorData);
                            $authorImgUrl = str_replace('../', $base_url, $authorImgPath);
                            ?>
                            <img src="<?php echo $authorImgUrl; ?>" alt="<?php echo htmlspecialchars($author['author_name']); ?>" class="author-image">
                            <div class="podium-block silver">
                                <div class="podium-number">2</div>
                            </div>
                            <div class="author-name "><?php echo htmlspecialchars($author['author_name']); ?></div>
                            <div class="borrow-count"><?php echo $author['borrow_count']; ?> borrows</div>
                        </div>
                        
                        <!-- 1st Place (Center) -->
                        <div class="podium-item">
                            <?php
                            $author = $authors[0];
                            $authorData = ['author_id' => $author['author_id'], 'author_profile' => $author['author_profile'] ?? ''];
                            $authorImgPath = getAuthorImagePath($authorData);
                            $authorImgUrl = str_replace('../', $base_url, $authorImgPath);
                            ?>
                            <img src="<?php echo $authorImgUrl; ?>" alt="<?php echo htmlspecialchars($author['author_name']); ?>" class="author-image">
                            <div class="podium-block gold">
                                <div class="podium-number">1</div>
                            </div>
                            <div class="author-name "><?php echo htmlspecialchars($author['author_name']); ?></div>
                            <div class="borrow-count"><?php echo $author['borrow_count']; ?> borrows</div>
                        </div>
                        
                        <!-- 3rd Place (Right) -->
                        <div class="podium-item">
                            <?php
                            $author = $authors[2];
                            $authorData = ['author_id' => $author['author_id'], 'author_profile' => $author['author_profile'] ?? ''];
                            $authorImgPath = getAuthorImagePath($authorData);
                            $authorImgUrl = str_replace('../', $base_url, $authorImgPath);
                            ?>
                            <img src="<?php echo $authorImgUrl; ?>" alt="<?php echo htmlspecialchars($author['author_name']); ?>" class="author-image">
                            <div class="podium-block bronze">
                                <div class="podium-number">3</div>
                            </div>
                            <div class="author-name "><?php echo htmlspecialchars($author['author_name']); ?></div>
                            <div class="borrow-count"><?php echo $author['borrow_count']; ?> borrows</div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">Not enough data to display podium.</div>
                    <?php endif; ?>
                </div>
                
                <!-- Remaining Authors (4th and 5th) -->
                <?php if (count($authors) > 3): ?>
                <div class="row my-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-light">
                                <span class="mb-0">Runner-ups</span>
                            </div>
                            <ul class="list-group list-group-flush">
                                <?php for($i = 3; $i < count($authors); $i++): 
                                    $author = $authors[$i];
                                    $authorData = ['author_id' => $author['author_id'], 'author_profile' => $author['author_profile'] ?? ''];
                                    $authorImgPath = getAuthorImagePath($authorData);
                                    $authorImgUrl = str_replace('../', $base_url, $authorImgPath);
                                ?>
                                <li class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <div class="rank-badge bg-secondary">
                                            <?php echo ($i + 1); ?>
                                        </div>
                                        <img src="<?php echo $authorImgUrl; ?>" alt="<?php echo htmlspecialchars($author['author_name']); ?>" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <?php echo htmlspecialchars($author['author_name']); ?>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php echo $author['borrow_count']; ?> borrows
                                        </span>
                                    </div>
                                </li>
                                <?php endfor; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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

    <!-- Filters and Sorting Controls -->
    <div class="row mb-4">
        <div class="col-md-9">
            <div class="d-flex flex-wrap gap-2">
                <!-- Alphabetical Index -->
                <a href="author.php<?php echo !empty($selected_sort) && $selected_sort != 'name-asc' ? '?sort='.$selected_sort : ''; ?>" 
                   class="btn <?php echo !isset($_GET['filter']) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    All
                </a>
                <?php
                $alphabet = range('A', 'Z');
                foreach ($alphabet as $letter):
                    $isActive = isset($_GET['filter']) && $_GET['filter'] == $letter;
                    $sortParam = !empty($selected_sort) && $selected_sort != 'name-asc' ? '&sort='.$selected_sort : '';
                ?>
                <a href="author.php?filter=<?php echo $letter; ?><?php echo $sortParam; ?>" 
                   class="btn <?php echo $isActive ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <?php echo $letter; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-3">
            <form id="sort-form" action="author.php" method="GET" class="mb-3">
                <!-- Preserve existing filter parameters -->
                <?php if (isset($_GET['filter'])): ?>
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($_GET['filter']); ?>">
                <?php endif; ?>
                <?php if (!empty($search_term)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                <?php endif; ?>
                
                <div class="input-group">
                    <select class="form-select" id="sort-authors" name="sort" onchange="document.getElementById('sort-form').submit();">
                        <option value="name-asc" <?php echo $selected_sort == 'name-asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="name-desc" <?php echo $selected_sort == 'name-desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                        <option value="popular" <?php echo $selected_sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        <option value="books-count" <?php echo $selected_sort == 'books-count' ? 'selected' : ''; ?>>Number of Books</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Authors Grid - Filtered based on search/sort/filter -->
    <div class="row">
        <div class="col-12">
            <h3 class="mb-4"><?php echo !empty($search_term) ? 'Search Results' : (!empty($letter_filter) ? 'Authors Starting with "' . htmlspecialchars($letter_filter) . '"' : 'All Authors'); ?></h3>
        </div>
    </div>
    
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
                    <span class="author-name fw-bold"><?php echo htmlspecialchars($author['author_name']); ?></span>
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
    
    <!-- Pagination with preserved filters -->
    <?php if ($total_pages > 1): ?>
    <div class="d-flex justify-content-center mt-5">
        <nav aria-label="Author pagination">
            <ul class="pagination">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <?php 
                    $prev_params = "page=" . ($page - 1);
                    if (isset($_GET['filter'])) $prev_params .= "&filter=" . htmlspecialchars($_GET['filter']);
                    if (!empty($selected_sort) && $selected_sort != 'name-asc') $prev_params .= "&sort=" . htmlspecialchars($selected_sort);
                    if (!empty($search_term)) $prev_params .= "&search=" . urlencode($search_term);
                    ?>
                    <a class="page-link" href="author.php?<?php echo $prev_params; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php 
                // Show limited pagination numbers with ellipsis
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="author.php?page=1<?php 
                            if (isset($_GET['filter'])) echo "&filter=" . htmlspecialchars($_GET['filter']);
                            if (!empty($selected_sort) && $selected_sort != 'name-asc') echo "&sort=" . htmlspecialchars($selected_sort);
                            if (!empty($search_term)) echo "&search=" . urlencode($search_term);
                        ?>">1</a>
                    </li>
                    <?php if ($start_page > 2): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): 
                    $page_params = "page=" . $i;
                    if (isset($_GET['filter'])) $page_params .= "&filter=" . htmlspecialchars($_GET['filter']);
                    if (!empty($selected_sort) && $selected_sort != 'name-asc') $page_params .= "&sort=" . htmlspecialchars($selected_sort);
                    if (!empty($search_term)) $page_params .= "&search=" . urlencode($search_term);
                ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="author.php?<?php echo $page_params; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="author.php?page=<?php echo $total_pages; ?><?php 
                            if (isset($_GET['filter'])) echo "&filter=" . htmlspecialchars($_GET['filter']);
                            if (!empty($selected_sort) && $selected_sort != 'name-asc') echo "&sort=" . htmlspecialchars($selected_sort);
                            if (!empty($search_term)) echo "&search=" . urlencode($search_term);
                        ?>"><?php echo $total_pages; ?></a>
                    </li>
                <?php endif; ?>
                
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <?php 
                    $next_params = "page=" . ($page + 1);
                    if (isset($_GET['filter'])) $next_params .= "&filter=" . htmlspecialchars($_GET['filter']);
                    if (!empty($selected_sort) && $selected_sort != 'name-asc') $next_params .= "&sort=" . htmlspecialchars($selected_sort);
                    if (!empty($search_term)) $next_params .= "&search=" . urlencode($search_term);
                    ?>
                    <a class="page-link" href="author.php?<?php echo $next_params; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
    


<!-- Author Detail Modal -->
<div class="modal fade" id="authorModal" tabindex="-1" aria-labelledby="authorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
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
                        const authorName = card.querySelector('.author-name').textContent.toLowerCase();
                        
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


<?php
include '../footer.php';
?>