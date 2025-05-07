<?php
    // home.php - User dashboard home page
    include '../database_connection.php';
    include '../function.php';
    include '../header.php';
    authenticate_user();
    $user_unique_id = $_SESSION['user_unique_id'] ?? '';
	$userId= $_SESSION['user_id'] ?? '';
    // Get current user details
    $user = get_complete_user_details($user_unique_id, $connect);
    $base_url = base_url();
    
        
    // Fetch data for the dashboard
    $currentBooks = getUserCurrentBooks($connect, $userId);
    $overdueBooks = getUserOverdueBooks($connect, $userId);
    $recentlyReturned = getUserRecentlyReturnedBooks($connect, $userId);
    $totalFines = getUserTotalFines($connect, $userId);
    $popularBooks = getPopularBooks($connect);
    $newBooks = getNewBooks($connect);
    $readingStats = getUserReadingStats($connect, $userId);
?>

<!-- Hero Section - Personalized Welcome -->
<div class="card bg-dark text-white mb-4 border-0 rounded-3 overflow-hidden">
    <img src="../asset/img/library-hero.jpg" class="card-img opacity-50" alt="Library" style="height: 250px; object-fit: cover;">
    <div class="card-img-overlay d-flex flex-column justify-content-center">
        <div class="container">
            <h1 class="display-4 fw-bold">Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
            <p class="lead">Your personal library dashboard</p>
            <div class="d-flex gap-2 mt-3">
                <a href="books.php" class="btn btn-primary">Browse Books</a>
                <a href="author.php" class="btn btn-outline-light">Explore Authors</a>
            </div>
        </div>
    </div>
</div>

    <!-- Quick Stats Cards - Modern Design -->
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden">
                <div class="card-body text-center position-relative">
                    <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
                    <h5 class="card-title mt-2 text-primary">Current Books</h5>
                    <div class="display-4 fw-bold text-primary"><?php echo $readingStats['current']; ?></div>
                    <p class="card-text text-muted">Books currently borrowed</p>
                </div>
                <div class="card-footer bg-transparent border-0 text-center">
                    <a href="my_books.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">View My Books</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden">
                <div class="card-body text-center position-relative">
                    <div class="position-absolute top-0 start-0 w-100 bg-warning" style="height: 4px;"></div>
                    <h5 class="card-title mt-2 text-warning">Overdue</h5>
                    <div class="display-4 fw-bold text-warning"><?php echo count($overdueBooks); ?></div>
                    <p class="card-text text-muted">Books past due date</p>
                </div>
                <div class="card-footer bg-transparent border-0 text-center">
                    <a href="my_books.php?filter=overdue" class="btn btn-sm btn-outline-warning rounded-pill px-3">Check Overdue</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden">
                <div class="card-body text-center position-relative">
                    <div class="position-absolute top-0 start-0 w-100 bg-danger" style="height: 4px;"></div>
                    <h5 class="card-title mt-2 text-danger">Outstanding Fines</h5>
                    <div class="display-4 fw-bold text-danger"><?php echo get_currency_symbol($connect) . number_format($totalFines, 2); ?></div>
                    <p class="card-text text-muted">Total unpaid fines</p>
                </div>
                <div class="card-footer bg-transparent border-0 text-center">
                    <a href="my_fines.php" class="btn btn-sm btn-outline-danger rounded-pill px-3">Pay Fines</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 rounded-3 overflow-hidden">
                <div class="card-body text-center position-relative">
                    <div class="position-absolute top-0 start-0 w-100 bg-success" style="height: 4px;"></div>
                    <h5 class="card-title mt-2 text-success">Return History</h5>
                    <div class="display-4 fw-bold text-success"><?php echo $readingStats['total']; ?></div>
                    <p class="card-text text-muted">Total books you've returned</p>
                </div>
                <div class="card-footer bg-transparent border-0 text-center">
                    <a href="my_reading_history.php" class="btn btn-sm btn-outline-success rounded-pill px-3">View History</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Current Books Section -->
    <?php if (!empty($currentBooks)): ?>
    <div class="card shadow-sm mb-4 rounded-3 border-0 overflow-hidden">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">My Current Books</h3>
                <a href="my_books.php" class="btn btn-sm btn-primary rounded-pill px-3">View All</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Book</th>
                            <th>Borrowed On</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th class="pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($currentBooks as $book): 
                            $base_url = base_url();
							$bookImgPath = getBookImagePath($book);
							$bookImgUrl = str_replace('../', $base_url, $bookImgPath);
                            $daysRemaining = $book['days_remaining'];
                            $status = $book['issue_book_status'];
                            $statusClass = $status == 'Overdue' ? 'danger' : ($daysRemaining <= 2 ? 'warning' : 'success');
                        ?>
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $bookImgUrl; ?>" alt="<?php echo htmlspecialchars($book['book_name']); ?>" 
                                         class="me-3 rounded shadow-sm" style="width: 50px; height: 70px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($book['book_name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($book['authors']);; ?></small><br>
                                        <small class="text-muted">ISBN: <?php echo htmlspecialchars($book['book_isbn_number']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($book['issue_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($book['expected_return_date'])); ?></td>
                            <td>
                                <?php if ($status == 'Overdue'): ?>
                                <span class="badge bg-danger rounded-pill">Overdue</span>
                                <?php elseif ($daysRemaining <= 2): ?>
                                <span class="badge bg-warning text-dark rounded-pill">Due Soon</span>
                                <?php else: ?>
                                <span class="badge bg-success rounded-pill">Active</span>
                                <?php endif; ?>
                                
                                <?php if ($status == 'Overdue'): ?>
                                <div class="small text-danger mt-1">
                                    <?php echo abs($daysRemaining); ?> day<?php echo abs($daysRemaining) > 1 ? 's' : ''; ?> overdue
                                </div>
                                <?php elseif ($daysRemaining >= 0): ?>
                                <div class="small text-muted mt-1">
                                    <?php echo $daysRemaining; ?> day<?php echo $daysRemaining > 1 ? 's' : ''; ?> remaining
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="pe-3">
                                <div class="d-flex gap-2">
                                    <a href="books.php?book_id=<?php echo $book['book_id']; ?>" class="btn btn-sm btn-outline-secondary rounded-pill">
                                        <i class="bi bi-info-circle"></i> Details
                                    </a>
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
    
    <!-- Overdue Books & Fines Alert -->
    <?php if (!empty($overdueBooks)): ?>
    <div class="alert alert-danger mb-4 rounded-3 shadow-sm border-0">
        <div class="d-flex align-items-center mb-3">
            <i class="bi bi-exclamation-triangle-fill fs-3 me-2"></i>
            <h4 class="mb-0">Attention: You have overdue books</h4>
        </div>
        <p>Please return the following books as soon as possible to avoid additional fines:</p>
        <ul class="mb-0">
            <?php foreach ($overdueBooks as $book): ?>
            <li class="mb-2">
                <strong><?php echo htmlspecialchars($book['book_name']); ?></strong> - 
                Due: <?php echo date('M d, Y', strtotime($book['expected_return_date'])); ?> 
                (<?php echo $book['days_overdue']; ?> days late)
                <?php if (isset($book['fines_amount']) && $book['fines_amount'] > 0): ?>
                <span class="badge bg-danger ms-2 rounded-pill"><?php echo get_currency_symbol($connect) . number_format($book['fines_amount'], 2); ?> fine</span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="mt-3">
            <a href="my_fines.php" class="btn btn-danger rounded-pill px-4">View & Pay Fines</a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Two Column Layout: Reading Stats and Book Carousels -->
    <div class="row mb-4 g-4">
        <!-- Reading Statistics Card - Now in col-md-7 -->
        <div class="col-lg-8">
            <div class="card shadow-sm h-100 rounded-3 border-0 overflow-hidden">
                <div class="card-header bg-white py-3">
                    <h3 class="mb-0">My Reading Statistics</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="position-relative">
                                <canvas id="readingStats" width="100%" height="220"></canvas>
                                <div class="position-absolute top-50 start-50 translate-middle text-center">
                                    <h3 class="mb-0"><?php echo $readingStats['total']; ?></h3>
                                    <div class="text-muted small">Total Books</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="row row-cols-1 row-cols-sm-2 g-3">
                                <div class="col">
                                    <div class="border rounded-3 p-3 h-100 bg-light bg-opacity-50">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="rounded-circle bg-primary p-2 me-2">
                                                <i class="bi bi-book text-white"></i>
                                            </div>
                                            <h5 class="mb-0">Total Borrowed</h5>
                                        </div>
                                        <p class="fs-4 mb-0"><?php echo $readingStats['total']; ?> books</p>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="border rounded-3 p-3 h-100 bg-light bg-opacity-50">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="rounded-circle bg-success p-2 me-2">
                                                <i class="bi bi-check-circle text-white"></i>
                                            </div>
                                            <h5 class="mb-0">Returned On Time</h5>
                                        </div>
                                        <p class="fs-4 mb-0"><?php echo $readingStats['on_time']; ?> books</p>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="border rounded-3 p-3 h-100 bg-light bg-opacity-50">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="rounded-circle bg-warning p-2 me-2">
                                                <i class="bi bi-clock-history text-white"></i>
                                            </div>
                                            <h5 class="mb-0">Returned Late</h5>
                                        </div>
                                        <p class="fs-4 mb-0"><?php echo $readingStats['late']; ?> books</p>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="border rounded-3 p-3 h-100 bg-light bg-opacity-50">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="rounded-circle bg-info p-2 me-2">
                                                <i class="bi bi-graph-up text-white"></i>
                                            </div>
                                            <h5 class="mb-0">On-time Rate</h5>
                                        </div>
                                        <?php 
                                        $totalReturned = $readingStats['on_time'] + $readingStats['late'];
                                        $onTimeRate = $totalReturned > 0 ? round(($readingStats['on_time'] / $totalReturned) * 100) : 0;
                                        ?>
                                        <p class="fs-4 mb-0"><?php echo $onTimeRate; ?>%</p>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($recentlyReturned)): ?>
                            <div class="mt-3">
                                <h5 class="mb-3">Recently Returned</h5>
                                <div class="d-flex gap-2 overflow-auto pb-2">
                                    <?php foreach (array_slice($recentlyReturned, 0, 3) as $book): 
                                        $base_url = base_url();
                                        $bookImgPath = getBookImagePath($book);
                                        $bookImgUrl = str_replace('../', $base_url, $bookImgPath);
                                    ?>
                                    <div class="card border-0 shadow-sm" style="min-width: 140px; max-width: 140px;">
                                        <img src="<?php echo $bookImgUrl; ?>" class="card-img-top" 
                                             alt="<?php echo htmlspecialchars($book['book_name']); ?>" 
                                             style="height: 120px; object-fit: cover;">
                                        <div class="card-body p-2">
                                            <p class="card-title small mb-0 text-truncate" title="<?php echo htmlspecialchars($book['book_name']); ?>">
                                                <?php echo htmlspecialchars($book['book_name']); ?>
                                            </p>
                                            <p class="card-text small text-muted mb-0">
                                                <?php echo date('M d', strtotime($book['return_date'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    <div class="d-flex align-items-center justify-content-center" style="min-width: 140px;">
                                        <a href="my_reading_history.php" class="btn btn-sm btn-outline-primary rounded-pill">
                                            View All <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Book Carousels - Now in col-md-5 -->
        <div class="col-lg-4">
            <!-- Popular Books and New Arrivals Carousel -->
            <div class="card shadow-sm h-100 rounded-3 border-0 overflow-hidden">
                <div class="card-header bg-white p-0">
                    <ul class="nav nav-tabs card-header-tabs" id="bookTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active px-4 py-3" id="popular-tab" data-bs-toggle="tab" 
                                    data-bs-target="#popular-books" type="button" role="tab" aria-selected="true">
                                <i class="bi bi-star-fill text-warning me-1"></i> Popular Books
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link px-4 py-3" id="new-tab" data-bs-toggle="tab" 
                                    data-bs-target="#new-books" type="button" role="tab" aria-selected="false">
                                <i class="bi bi-lightning-fill text-success me-1"></i> New Arrivals
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="bookTabsContent">
                        <!-- Popular Books Tab -->
                        <div class="tab-pane fade show active" id="popular-books" role="tabpanel">
                            <div id="popularBooksCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
                                <div class="carousel-inner">
                                    <?php 
                                    $chunks = array_chunk($popularBooks, 3);
                                    foreach ($chunks as $index => $chunk): 
                                    ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <div class="row row-cols-1 g-3">
                                            <?php foreach ($chunk as $book): 
                                                $base_url = base_url();
                                                $bookImgPath = getBookImagePath($book);
                                                $bookImgUrl = str_replace('../', $base_url, $bookImgPath);
                                            ?>
                                            <div class="col">
                                                <a href="books.php?book_id=<?php echo $book['book_id']; ?>" class="text-decoration-none">
                                                    <div class="card border-0 bg-light shadow-sm">
                                                        <div class="row g-0">
                                                            <div class="col-4">
                                                                <div class="position-relative">
                                                                    <img src="<?php echo $bookImgUrl; ?>" 
                                                                        alt="<?php echo htmlspecialchars($book['book_name']); ?>" 
                                                                        class="img-fluid rounded-start" style="height: 150px; object-fit: cover;">
                                                                    <span class="position-absolute top-0 start-0 badge bg-primary rounded-0 rounded-end m-2">
                                                                        #<?php echo $index * 3 + array_search($book, $chunk) + 1; ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div class="col-8">
                                                                <div class="card-body">
                                                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($book['book_name']); ?></h5>
                                                                    <p class="card-text text-muted small"><?php echo htmlspecialchars($book['book_author']); ?></p>
                                                                    <div class="d-flex align-items-center mt-2">
                                                                        <div class="badge bg-primary rounded-pill me-2"><?php echo $book['issue_count']; ?> borrows</div>
                                                                        <div class="text-warning">
                                                                            <?php for($i = 0; $i < 5; $i++): ?>
                                                                                <i class="bi bi-star-fill <?php echo $i < min(round($book['issue_count']/10), 5) ? '' : 'text-muted'; ?>"></i>
                                                                            <?php endfor; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Carousel Controls -->
                                <div class="d-flex justify-content-between mt-3">
                                    <button class="btn btn-sm btn-primary rounded-circle" type="button" data-bs-target="#popularBooksCarousel" data-bs-slide="prev">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <a href="books.php?sort=popular" class="btn btn-outline-primary rounded-pill">
                                        View All Popular Books
                                    </a>
                                    <button class="btn btn-sm btn-primary rounded-circle" type="button" data-bs-target="#popularBooksCarousel" data-bs-slide="next">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- New Arrivals Tab -->
                        <div class="tab-pane fade" id="new-books" role="tabpanel">
                            <div id="newBooksCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
                                <div class="carousel-inner">
                                    <?php 
                                    $chunks = array_chunk($newBooks, 3);
                                    foreach ($chunks as $index => $chunk): 
                                    ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <div class="row row-cols-1 g-3">
                                            <?php foreach ($chunk as $book): 
                                                $base_url = base_url();
                                                $bookImgPath = getBookImagePath($book);
                                                $bookImgUrl = str_replace('../', $base_url, $bookImgPath);
                                                $daysAgo = round((time() - strtotime($book['book_added_on'])) / (60 * 60 * 24));
                                            ?>
                                            <div class="col">
                                                <a href="books.php?book_id=<?php echo $book['book_id']; ?>" class="text-decoration-none">
                                                    <div class="card border-0 bg-light shadow-sm">
                                                        <div class="row g-0">
                                                            <div class="col-4">
                                                                <div class="position-relative">
                                                                    <img src="<?php echo $bookImgUrl; ?>" 
                                                                        alt="<?php echo htmlspecialchars($book['book_name']); ?>" 
                                                                        class="img-fluid rounded-start" style="height: 150px; object-fit: cover;">
                                                                    <?php if($daysAgo <= 7): ?>
                                                                    <span class="position-absolute top-0 start-0 badge bg-success rounded-0 rounded-end m-2">
                                                                        NEW
                                                                    </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-8">
                                                                <div class="card-body">
                                                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($book['book_name']); ?></h5>
                                                                    <p class="card-text text-muted small"><?php echo htmlspecialchars($book['authors']); ?></p>
                                                                    <div class="mt-2">
                                                                        <span class="badge bg-success rounded-pill">
                                                                            <?php echo $daysAgo <= 7 ? 'Added ' . $daysAgo . ' days ago' : 'Added ' . date('M d', strtotime($book['book_added_on'])); ?>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Carousel Controls -->
                                <div class="d-flex justify-content-between mt-3">
                                    <button class="btn btn-sm btn-success rounded-circle" type="button" data-bs-target="#newBooksCarousel" data-bs-slide="prev">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <a href="books.php?sort=newest" class="btn btn-outline-success rounded-pill">
                                        View All New Arrivals
                                    </a>
                                    <button class="btn btn-sm btn-success rounded-circle" type="button" data-bs-target="#newBooksCarousel" data-bs-slide="next">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


<!-- JavaScript for Charts and Functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the reading stats chart
    initReadingStatsChart();
});

// Initialize the reading statistics chart
function initReadingStatsChart() {
    // Get chart canvas element
    const ctx = document.getElementById('readingStats').getContext('2d');
    
    // Chart data from PHP variables
    const readingData = {
        labels: ['Returned On Time', 'Returned Late', 'Currently Borrowed'],
        datasets: [{
            data: [
                <?php echo $readingStats['on_time']; ?>, 
                <?php echo $readingStats['late']; ?>, 
                <?php echo $readingStats['current']; ?>
            ],
            backgroundColor: ['#198754', '#ffc107', '#0d6efd'],
            borderWidth: 0
        }]
    };
    
    // Create the chart
    const readingStatsChart = new Chart(ctx, {
        type: 'doughnut',
        data: readingData,
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '70%'
        }
    });
}

</script>

<?php include '../footer.php'; ?>