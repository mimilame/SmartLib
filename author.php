<?php
	include 'database_connection.php';
	include 'function.php';	
	include 'header.php';
	validate_session();
?>
<style>
    :root {
        --primary-color: #3d5a80;
        --secondary-color: #98c1d9;
        --accent-color: #ee6c4d;
        --dark-color: #293241;
        --light-color: #e0fbfc;
        --gray-color: #6c757d;
        --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        --transition: all 0.3s ease;
    }
    
    /* Base Styles */
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f8f9fa;
        color: var(--dark-color);
        line-height: 1.6;
    }
    
    .container-fluid {
        max-width: 1400px;
        margin: 0 auto;
    }
    
    h2 {
        font-weight: 700;
        margin-bottom: 1.5rem;
        position: relative;
        display: inline-block;
    }
    
    h2::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 0;
        width: 60px;
        height: 4px;
        background: var(--accent-color);
        border-radius: 2px;
    }
    
    .section {
        margin-bottom: 4rem;
        position: relative;
    }
    
    /* Hero Section */
    .hero-section {
        position: relative;
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 3rem;
        height: 300px;
        box-shadow: var(--shadow);
    }
    
    .hero-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 1;
        filter: brightness(0.7);
        transition: var(--transition);
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 0 3rem;
    }
    
    .hero-title {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        color: white;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .hero-subtitle {
        font-size: 1.2rem;
        color: white;
        margin-bottom: 1.5rem;
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }
    
    .search-container {
        max-width: 600px;
        position: relative;
    }
    
    .search-input {
        width: 100%;
        padding: 1rem 1.5rem;
        border-radius: 50px;
        border: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        font-size: 1rem;
        transition: var(--transition);
    }
    
    .search-input:focus {
        outline: none;
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    
    .search-btn {
        position: absolute;
        right: 8px;
        top: 8px;
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
        border: none;
        background: var(--primary-color);
        color: white;
        font-weight: 600;
        transition: var(--transition);
    }
    
    .search-btn:hover {
        background: var(--dark-color);
        transform: translateY(-2px);
    }
    
    /* Author Grid */
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    .see-all {
        font-weight: 600;
        color: var(--primary-color);
        text-decoration: none;
        transition: var(--transition);
    }
    
    .see-all:hover {
        color: var(--accent-color);
    }
    
    .author-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-top: 1.5rem;
    }
    
    .author-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: var(--transition);
        position: relative;
    }
    
    .author-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }
    
    .author-img-container {
        height: 240px;
        overflow: hidden;
        position: relative;
    }
    
    .author-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: var(--transition);
    }
    
    .author-card:hover .author-img {
        transform: scale(1.05);
    }
    
    .author-info {
        padding: 1.2rem;
        position: relative;
    }
    
    .author-name {
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        color: var(--dark-color);
    }
    
    .author-stats {
        color: var(--gray-color);
        font-size: 0.9rem;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .book-count {
        color: var(--accent-color);
        font-weight: 700;
    }
    
    .borrow-count {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .borrow-count i {
        color: var(--primary-color);
    }
    
    .author-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: var(--accent-color);
        color: white;
        padding: 0.4rem 0.8rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        z-index: 10;
    }
    
    /* Tabs for Time Periods */
    .tabs-container {
        position: relative;
        margin-bottom: 2rem;
    }
    
    .tabs {
        display: flex;
        margin-bottom: 2rem;
        border-bottom: 1px solid #eee;
        justify-content: center;
    }
    
    .tab {
        padding: 1rem 1.5rem;
        cursor: pointer;
        border: none;
        background: none;
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-color);
        position: relative;
        transition: var(--transition);
    }
    
    .tab:hover {
        color: var(--primary-color);
    }
    
    .tab.active {
        color: var(--accent-color);
    }
    
    .tab.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 50%;
        transform: translateX(-50%);
        width: 40px;
        height: 3px;
        background: var(--accent-color);
        border-radius: 3px 3px 0 0;
    }
    
    /* Top Authors Table */
    .author-table-container {
        background: white;
        border-radius: 16px;
        box-shadow: var(--shadow);
        overflow: hidden;
    }
    
    .author-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .author-table th, .author-table td {
        padding: 1rem 1.5rem;
        text-align: left;
    }
    
    .author-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: var(--dark-color);
    }
    
    .author-table tr {
        border-bottom: 1px solid #eee;
        transition: var(--transition);
    }
    
    .author-table tr:last-child {
        border-bottom: none;
    }
    
    .author-table tr:hover {
        background-color: #f8f9fa;
    }
    
    .rank {
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: var(--light-color);
        color: var(--dark-color);
    }
    
    .rank-1 {
        background: #ffd700;
        color: #333;
    }
    
    .rank-2 {
        background: #c0c0c0;
        color: #333;
    }
    
    .rank-3 {
        background: #cd7f32;
        color: white;
    }
    
    .author-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 1rem;
    }
    
    .table-author-name {
        font-weight: 600;
        color: var(--dark-color);
    }
    
    .borrow-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.8rem;
        border-radius: 50px;
        background: var(--light-color);
        color: var(--primary-color);
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    /* Author's Book Section */
    .author-showcase {
        margin: 3rem 0;
    }
    
    .author-detail {
        display: flex;
        margin-bottom: 2rem;
        padding: 2rem;
        border-radius: 16px;
        background: white;
        box-shadow: var(--shadow);
    }
    
    .author-detail-img-container {
        flex-shrink: 0;
        margin-right: 2rem;
    }
    
    .author-detail-img {
        width: 150px;
        height: 150px;
        border-radius: 16px;
        object-fit: cover;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .author-detail-info {
        flex: 1;
    }
    
    .author-detail-name {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--dark-color);
    }
    
    .author-detail-meta {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1rem;
        color: var(--gray-color);
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .meta-item i {
        color: var(--accent-color);
    }
    
    .meta-value {
        font-weight: 600;
        color: var(--primary-color);
    }
    
    .book-list-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 1.5rem 0 1rem;
    }
    
    .book-list-title {
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--dark-color);
    }
    
    .book-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1.5rem;
    }
    
    .book-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        transition: var(--transition);
        border: 1px solid #eee;
    }
    
    .book-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    }
    
    .book-img-container {
        height: 240px;
        overflow: hidden;
        position: relative;
    }
    
    .book-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: var(--transition);
    }
    
    .book-card:hover .book-img {
        transform: scale(1.05);
    }
    
    .book-info {
        padding: 1.2rem;
    }
    
    .book-title {
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 0.5rem;
        color: var(--dark-color);
    }
    
    .book-meta {
        display: flex;
        justify-content: space-between;
        font-size: 0.85rem;
        margin-top: 0.8rem;
    }
    
    .availability {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .available {
        color: #38b000;
    }
    
    .unavailable {
        color: #d90429;
    }
    
    .borrow-count {
        color: var(--gray-color);
    }
    
    /* Alphabetical Index */
    .alpha-index {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 2rem;
        justify-content: center;
    }
    
    .alpha-index a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: white;
        color: var(--dark-color);
        text-decoration: none;
        font-weight: 600;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        transition: var(--transition);
    }
    
    .alpha-index a:hover, .alpha-index a.active {
        background: var(--primary-color);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(61, 90, 128, 0.3);
    }
    
    .alpha-section {
        margin-top: 2rem;
    }
    
    .alpha-header {
        position: relative;
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #eee;
    }
    
    /* Animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease forwards;
    }
    
    /* Responsive Design */
    @media (max-width: 992px) {
        .author-grid {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }
        
        .book-list {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        }
        
        .author-detail {
            flex-direction: column;
        }
        
        .author-detail-img-container {
            margin-right: 0;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: center;
        }
    }
    
    @media (max-width: 768px) {
        .hero-title {
            font-size: 2rem;
        }
        
        .author-grid {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        }
        
        .book-list {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        }
        
        .alpha-index a {
            width: 30px;
            height: 30px;
            font-size: 0.9rem;
        }
        
        .author-detail {
            padding: 1.5rem;
        }
    }
    
    @media (max-width: 576px) {
        .hero-content {
            padding: 0 1.5rem;
        }
        
        .author-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        }
        
        .book-list {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        }
        
        .tabs {
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }
        
        .tab {
            padding: 0.8rem 1rem;
            white-space: nowrap;
        }
        
        .author-table th, .author-table td {
            padding: 0.8rem;
        }
    }
    
    /* Dark mode */
    @media (prefers-color-scheme: dark) {
        :root {
            --primary-color: #98c1d9;
            --dark-color: #f8f9fa;
            --light-color: #293241;
            --gray-color: #adb5bd;
        }
        
        body {
            background-color: #121212;
            color: var(--dark-color);
        }
        
        .author-card, .book-card, .author-detail, .author-table-container {
            background: #1e1e1e;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        
        .book-card {
            border-color: #333;
        }
        
        .author-table th {
            background-color: #252525;
        }
        
        .author-table tr:hover {
            background-color: #252525;
        }
        
        .search-input {
            background: #333;
            color: white;
        }
        
        .alpha-index a {
            background: #1e1e1e;
            color: #f8f9fa;
        }
    }
</style>

<!-- Main Content -->
<div class="container-fluid py-4 mt-5 px-4">
    <!-- Hero Section -->
    <div class="hero-section">
        <img src="asset/img/library-hero.jpg" class="hero-bg" alt="Library">
        <div class="hero-content">
            <h1 class="hero-title">Discover Our Authors</h1>
            <p class="hero-subtitle">Explore the minds behind your favorite books</p>
            <div class="search-container">
                <input type="text" id="search-input" class="search-input" placeholder="Search for an author...">
                <button class="search-btn" id="search-button">
                    <i class="bi bi-search me-2"></i> Search
                </button>
            </div>
        </div>
    </div>
    
    <!-- Featured Authors Section -->
    <div class="section">
        <div class="section-header">
            <h2 class="text-dark">Featured Authors</h2>
            <a href="#all-authors" class="see-all">View All <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="author-grid">
            <?php
            $topAuthors = getTopAuthorsWithBooks($connect, 8);
            foreach ($topAuthors as $index => $author) {
                $authorImgPath = getAuthorImagePath($author);
                $bookCount = count($author['books']);
                $featuredBadge = ($index < 3) ? '<span class="author-badge">Featured</span>' : '';
            ?>
            <div class="author-card fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s">
                <?php echo $featuredBadge; ?>
                <div class="author-img-container">
                    <img src="<?php echo htmlspecialchars($authorImgPath); ?>" alt="<?php echo htmlspecialchars($author['author_name']); ?>" class="author-img">
                </div>
                <div class="author-info">
                    <div class="author-name"><?php echo htmlspecialchars($author['author_name']); ?></div>
                    <div class="author-stats">
                        <div><span class="book-count"><?php echo $bookCount; ?></span> books in collection</div>
                        <div class="borrow-count">
                            <i class="bi bi-book"></i> <?php echo $author['book_count']; ?> books
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
    
    <?php
    $active_tab = $_GET['tab'] ?? 'transactions';
    $active_period = $_GET['period'] ?? 'weekly'; // Default period
    ?>

    <div class="section">
        <h2 class="text-dark">Trending Authors</h2>

        <div class="tabs-container">
            <!-- Time Period Tabs -->
            <div class="tabs mb-3">
                <?php foreach (['weekly', 'monthly', 'yearly'] as $period): ?>
                    <a href="report.php?period=<?= $period ?>" 
                    class="tab btn <?= $active_period === $period ? 'btn-primary text-white' : 'btn-outline-secondary' ?>">
                        <?= ucfirst($period === 'weekly' ? 'This Week' : ($period === 'monthly' ? 'This Month' : 'This Year')) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Author Statistics -->
            <?php
            $authorTimeStats = getAuthorTimeStats($connect);
            $formattedStats = formatAuthorTimeStats($authorTimeStats);

            foreach (['weekly', 'monthly', 'yearly'] as $period) {
                $activeClass = $period === $active_period ? 'active' : 'd-none';
                echo "<div class='time-stats $activeClass' id='{$period}-stats'>";
                echo "<div class='author-table-container'>";
                echo "<table class='author-table'>";
                echo "<thead><tr><th width='80'>Rank</th><th>Author</th><th>Borrows</th></tr></thead><tbody>";

                foreach ($formattedStats[$period] as $index => $author) {
                    $authorData = ['author_id' => $author['author_id'], 'author_profile' => $author['author_profile'] ?? ''];
                    $authorImgPath = getAuthorImagePath($authorData);
                    $rankClass = ($index < 3) ? "rank-" . ($index + 1) : "";

                    echo "<tr>";
                    echo "<td><div class='rank $rankClass'>" . ($index + 1) . "</div></td>";
                    echo "<td>
                            <div class='d-flex align-items-center'>
                                <img src='" . htmlspecialchars($authorImgPath) . "' alt='" . htmlspecialchars($author['author_name']) . "' class='author-avatar'>
                                <span class='table-author-name'>" . htmlspecialchars($author['author_name']) . "</span>
                            </div>
                        </td>";
                    echo "<td><div class='borrow-chip'>" . $author['borrow_count'] . "</div></td>";
                    echo "</tr>";
                }

                echo "</tbody></table></div></div>";
            }
            ?>
        </div>
    </div>

    
    <!-- Top Author's Books Section -->
    <div class="section author-showcase">
        <h2 class="text-dark">Popular Authors' Works</h2>
        
        <?php
        $authorTopBooks = getAuthorTopBooks($connect);
        $authorBooksMap = groupAuthorTopBooks($authorTopBooks);
        
        // Group by author and time period
        $authorPeriodBooks = [];
        foreach ($authorTopBooks as $book) {
            $key = $book['author_id'] . '-' . $book['time_period'];
            if (!isset($authorPeriodBooks[$key])) {
                $authorPeriodBooks[$key] = [
                    'author_id' => $book['author_id'],
                    'author_name' => $book['author_name'],
                    'time_period' => $book['time_period'],
                    'books' => []
                ];
            }
            
            if (count($authorPeriodBooks[$key]['books']) < 4) {
                $authorPeriodBooks[$key]['books'][] = $book;
            }
        }
        
        // Filter just to show weekly results for top 3 authors
        $weeklyAuthors = array_filter($authorPeriodBooks, function($item) {
            return $item['time_period'] == 'week';
        });
        
        $displayedAuthors = 0;
        foreach ($weeklyAuthors as $index => $authorData) {
            if ($displayedAuthors >= 3) break;
            
            // Get author details for image
            $authorInfo = ['author_id' => $authorData['author_id'], 'author_profile' => ''];
            $authorImgPath = getAuthorImagePath($authorInfo);
            
            // Get this author's books from the database
            $authorBooks = getBooksByAuthor($connect, $authorData['author_id'], 5);
            $totalBooks = count($authorBooks);
            
            // Calculate total borrows by summing borrow counts for each book
            $totalBorrows = 0;
            foreach ($authorData['books'] as $book) {
                $totalBorrows += $book['borrow_count']; 
            }
        ?>
        <div class="author-detail fade-in" style="animation-delay: <?php echo $index * 0.2; ?>s">
            <div class="author-detail-img-container">
                <img src="<?php echo htmlspecialchars($authorImgPath); ?>" alt="<?php echo htmlspecialchars($authorData['author_name']); ?>" class="author-detail-img">
            </div>
            <div class="author-detail-info">
                <h3 class="author-detail-name"><?php echo htmlspecialchars($authorData['author_name']); ?></h3>
                <div class="author-detail-meta">
                    <div class="meta-item">
                        <i class="bi bi-book"></i>
                        <div><span class="meta-value"><?php echo $totalBooks; ?></span> books</div>
                    </div>
                    <div class="meta-item">
                        <i class="bi bi-graph-up"></i>
                        <div><span class="meta-value"><?php echo $totalBorrows; ?></span> total borrows</div>
                    </div>
                </div>
                
                <div class="book-list-header">
                    <div class="book-list-title">Most Popular Books This Week</div>
                    <a href="#" class="see-all">View All Books</a>
                </div>
                
                <div class="book-list">
                    <?php 
                    foreach ($authorData['books'] as $bookIndex => $book) {
                        // Get actual book details
                        $bookData = getBookById($connect, $book['book_id'] ?? 0);
                        if (!$bookData) continue;
                        
                        $bookImgPath = getBookImagePath($bookData);
                        $totalCopies = $bookData['book_no_of_copy'] ?? 0;
                        $availability = getBookAvailability($connect, $bookData['book_id'], $totalCopies);
                    ?>
                    <div class="book-card fade-in" style="animation-delay: <?php echo ($index * 0.2 + $bookIndex * 0.1); ?>s">
                        <div class="book-img-container">
                            <img src="<?php echo htmlspecialchars($bookImgPath); ?>" alt="<?php echo htmlspecialchars($book['book_name']); ?>" class="book-img">
                        </div>
                        <div class="book-info">
                            <div class="book-title"><?php echo htmlspecialchars($book['book_name']); ?></div>
                            <div class="book-meta">
                                <div class="availability <?php echo $availability['is_available'] ? 'available' : 'unavailable'; ?>">
                                    <i class="bi <?php echo $availability['is_available'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?>"></i>
                                    <?php echo $availability['is_available'] ? $availability['available_copies'] . ' available' : 'Unavailable'; ?>
                                </div>
                                <div class="borrow-count">
                                    <?php echo $book['borrow_count']; ?> borrows
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php 
            $displayedAuthors++;
        } 
        ?>
    </div>
    
    <!-- All Authors Section -->
    <div class="section" id="all-authors">
        <h2 class="text-dark">Browse All Authors</h2>
        
        <!-- Alphabetical Index -->
        <div class="alpha-index">
            <?php
            for ($i = 65; $i <= 90; $i++) {
                $letter = chr($i);
                $activeClass = ($letter == 'A') ? 'active' : '';
                echo "<a href='#letter-$letter' class='alpha-letter $activeClass'>$letter</a>";
            }
            ?>
        </div>
        
        <!-- Author listing by alphabet - we should query each letter from DB instead of dummy data -->
        <?php
        $alphabet = range('A', 'Z');
        
        // Ideally, we would query all authors, then group them by first letter
        // For now, we'll use the sample data approach but with optimized image paths
        foreach ($alphabet as $letterIndex => $letter) {
            echo "<div id='letter-$letter' class='alpha-section fade-in' style='animation-delay: " . ($letterIndex * 0.1) . "s'>";
            echo "<h3 class='alpha-header'>$letter</h3>";
            echo "<div class='author-grid'>";
            
            // In real implementation, get authors whose names start with $letter
            // This is just dummy data
            if (in_array($letter, ['A', 'B', 'C', 'J', 'M', 'S'])) {
                for ($i = 1; $i <= rand(3, 5); $i++) {
                    $authorName = "$letter" . ucfirst(strtolower(substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, rand(5, 8))));
                    $dummyAuthor = ['author_profile' => '']; // For getAuthorImagePath function
                    $authorImgPath = getAuthorImagePath($dummyAuthor);
                    $bookCount = rand(3, 25);
                    $borrowCount = rand(10, 200);
                    
                    echo "<div class='author-card fade-in' style='animation-delay: " . ($i * 0.1) . "s'>";
                    echo "<div class='author-img-container'>";
                    echo "<img src='$authorImgPath' alt='$authorName' class='author-img'>";
                    echo "</div>";
                    echo "<div class='author-info'>";
                    echo "<div class='author-name'>$authorName</div>";
                    echo "<div class='author-stats'>";
                    echo "<div><span class='book-count'>$bookCount</span> books</div>";
                    echo "<div class='borrow-count'><i class='bi bi-book'></i> $borrowCount borrows</div>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                }
            } else {
                echo "<p class='text-center py-4 text-muted'>No authors found starting with '$letter'.</p>";
            }
            
            echo "</div>";
            echo "</div>";
        }
        ?>
    </div>

    <!-- Author Recommendations Section -->
    <div class="section">
        <h2 class="text-dark">Personalized Author Recommendations</h2>
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card bg-light border-0 p-4 rounded-4">
                    <div class="card-body text-center">
                        <i class="bi bi-magic fs-1 text-primary mb-3"></i>
                        <h4>Discover Authors You'll Love</h4>
                        <p class="text-muted mb-4">Based on your reading history and preferences, we can recommend authors that match your taste.</p>
                        <button class="btn btn-primary px-4 py-2 rounded-pill">
                            <i class="bi bi-lightbulb me-2"></i> Get Personalized Recommendations
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab functionality
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all stats sections
            document.querySelectorAll('.time-stats').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show the selected section
            const period = this.getAttribute('data-period');
            document.getElementById(`${period}-stats`).classList.add('active');
        });
    });
    
    // Alphabetic index functionality
    document.querySelectorAll('.alpha-letter').forEach(letter => {
        letter.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all letters
            document.querySelectorAll('.alpha-letter').forEach(l => l.classList.remove('active'));
            // Add active class to clicked letter
            this.classList.add('active');
            
            // Smooth scroll to the section
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Search functionality
    document.getElementById('search-button').addEventListener('click', function() {
        const searchTerm = document.getElementById('search-input').value.trim().toLowerCase();
        
        if (searchTerm.length > 0) {
            // In a real implementation, you would send this to the server
            // For now, just show an alert
            alert(`Searching for: "${searchTerm}"`);
            
            // You could also implement client-side filtering here
            // For demonstration purposes only
            const allAuthorCards = document.querySelectorAll('.author-card');
            let foundCount = 0;
            
            allAuthorCards.forEach(card => {
                const authorName = card.querySelector('.author-name').textContent.toLowerCase();
                if (authorName.includes(searchTerm)) {
                    card.style.border = '2px solid var(--accent-color)';
                    foundCount++;
                } else {
                    card.style.border = '';
                }
            });
            
            if (foundCount === 0) {
                alert('No authors found matching your search.');
            }
        }
    });
    
    // Allow search on Enter key
    document.getElementById('search-input').addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            document.getElementById('search-button').click();
        }
    });
    
    // Intersection Observer for fade-in animations
    const fadeElems = document.querySelectorAll('.fade-in');
    
    const fadeObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = 1;
                entry.target.style.transform = 'translateY(0)';
                fadeObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });
    
    fadeElems.forEach(elem => {
        elem.style.opacity = 0;
        elem.style.transform = 'translateY(10px)';
        elem.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        fadeObserver.observe(elem);
    });
</script>

<?php
include 'footer.php';
?>