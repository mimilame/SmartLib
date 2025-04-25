<?php
// Database connection settings
include '../database_connection.php';
include '../function.php';
include '../header.php';
authenticate_admin();
$message = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'transactions';

// Delete rejected reviews older than 6 months
$sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));

$query = "DELETE FROM lms_book_review 
          WHERE (status = 'rejected' AND updated_at < :date)
          OR (review_text IS NULL OR LENGTH(review_text) < 10)";

$statement = $connect->prepare($query);
$statement->bindParam(':date', $sixMonthsAgo);
$statement->execute();

$count = $statement->rowCount();
error_log("Cleanup complete: Deleted $count old rejected reviews.");

// Fetch all required data for the dashboard
$bookStatusStats = getBookStatusStats($connect);
$overdueBooks = getOverdueBooks($connect);
$monthlyStats = getMonthlyStats($connect);
$popularBooks = getPopularBooks($connect);
$categoryStats = getCategoryStats($connect);
$userRoleStats = getUserRoleStats($connect);
$activeBorrowers = getActiveBorrowers($connect);
$recentTransactions = getRecentTransactions($connect);
$overdueBooksList = getOverdueBooksList($connect);

// Fetch author-related data
$topAuthors = getTopAuthors($connect);
$authorTimeStats = getAuthorTimeStats($connect);
$authorTopBooks = getAuthorTopBooks($connect);

// Format author data for charts
$formattedAuthorStats = formatAuthorTimeStats($authorTimeStats);
$weeklyAuthors = $formattedAuthorStats['weekly'];
$monthlyAuthors = $formattedAuthorStats['monthly'];
$yearlyAuthors = $formattedAuthorStats['yearly'];

// Group top books by author
$authorBooksMap = groupAuthorTopBooks($authorTopBooks);

// Get book review data
$highestRatedBooks = getHighestRatedBooks($connect, 1);
$lowestRatedBooks = getLowestRatedBooks($connect, 1);
$mostReviewedBooks = getMostReviewedBooks($connect, 3);
$mostActiveReviewers = getMostActiveReviewers($connect, 3);

$recentReviews = getRecentReviews($connect, 10);
$pendingReviews = getPendingReviews($connect);
$flaggedReviews = getFlaggedReviews($connect);
$reviewSettings = getReviewSettings($connect);
$getReviews = getReviews($connect, null, null);
// Combine all reviews to generate modals for each
$combinedReviews = array_merge($getReviews, $pendingReviews, $flaggedReviews);
// Remove duplicates based on review_id
$uniqueReviews = [];
foreach ($combinedReviews as $review) {
    $uniqueReviews[$review['review_id']] = $review;
}

?>
    <div class="mt-4">
        <h1 class="mb-4"><i class="bi bi-bar-chart-line"></i> Reports</h1>
        
        <ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $active_tab === 'transactions' ? 'active bg-white text-primary' : 'text-secondary' ?>" 
                href="report.php?tab=transactions" role="tab">
                    <i class="bi bi-arrow-left-right"></i> Book Transactions
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $active_tab === 'popular' ? 'active bg-white text-primary' : 'text-secondary' ?>" 
                href="report.php?tab=popular" role="tab">
                    <i class="bi bi-star"></i> Popular Books
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $active_tab === 'review_trends' ? 'active bg-white text-primary' : 'text-secondary' ?>" 
                href="report.php?tab=review_trends" role="tab">
                    <i class="bi bi-chat-quote"></i> Review Trends
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $active_tab === 'authors' ? 'active bg-white text-primary' : 'text-secondary' ?>" 
                href="report.php?tab=authors" role="tab">
                    <i class="bi bi-pen"></i> Author Analytics
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $active_tab === 'categories' ? 'active bg-white text-primary' : 'text-secondary' ?>" 
                href="report.php?tab=categories" role="tab">
                    <i class="bi bi-list-check"></i> Categories
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $active_tab === 'users' ? 'active bg-white text-primary' : 'text-secondary' ?>" 
                href="report.php?tab=users" role="tab">
                    <i class="bi bi-people"></i> Active Users
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $active_tab === 'borrowers' ? 'active bg-white text-primary' : 'text-secondary' ?>" 
                href="report.php?tab=borrowers" role="tab">
                <i class="bi bi-person-badge"></i> Active Borrowers
                </a>
            </li>
        </ul>
        
        <div class="tab-content" id="reportTabsContent">
            <!-- Book Transactions Tab -->
            <div class="tab-pane fade <?= $active_tab === 'transactions' ? 'show active' : '' ?>" id="transactions" role="tabpanel">
                <div class="row mb-4">
                    
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                        <div class="card card-counter bg-success shadow-sm hover-shadow translate-hover rounded transition">
                            <i class="bi bi-book float-start"></i>
                            <span class="count-numbers">
                                <?php
                                $issued = 0;
                                foreach ($bookStatusStats as $stat) {
                                    if ($stat['status'] == 'Issued') {
                                        $issued = $stat['count'];
                                        break;
                                    }
                                }
                                echo $issued;
                                ?>
                            </span>
                            <span class="count-name">Books Issued</span>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                        <div class="card card-counter bg-primary shadow-sm hover-shadow translate-hover rounded transition">
                            <i class="bi bi-arrow-return-left float-start"></i>
                            <span class="count-numbers">
                                <?php
                                $returned = 0;
                                foreach ($bookStatusStats as $stat) {
                                    if ($stat['status'] == 'Returned') {
                                        $returned = $stat['count'];
                                        break;
                                    }
                                }
                                echo $returned;
                                ?>
                            </span>
                            <span class="count-name">Books Returned</span>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                        <div class="card card-counter bg-warning shadow-sm hover-shadow translate-hover rounded transition">
                            <i class="bi bi-alarm float-start"></i>
                            <span class="count-numbers">
                                <?php
                                $overdue = 0;
                                foreach ($bookStatusStats as $stat) {
                                    if ($stat['status'] == 'Overdue') {
                                        $overdue = $stat['count'];
                                        break;
                                    }
                                }
                                echo $overdue;
                                ?>
                            </span>
                            <span class="count-name">Books Overdue</span>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                        <div class="card card-counter bg-danger shadow-sm hover-shadow translate-hover rounded transition">
                            <i class="bi bi-question-circle float-start"></i>
                            <span class="count-numbers">
                                <?php
                                $lost = 0;
                                foreach ($bookStatusStats as $stat) {
                                    if ($stat['status'] == 'Lost') {
                                        $lost = $stat['count'];
                                        break;
                                    }
                                }
                                echo $lost;
                                ?>
                            </span>
                            <span class="count-name">Books Lost</span>
                        </div>
                    </div>
                </div>
                

                <div class="row">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><i class="bi bi-exclamation-triangle"></i> Overdue Books</h5>
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#overdueContainer">
                                    <i class="bi bi-arrows-expand"></i> Toggle View
                                </button>
                            </div>
                            <div class="collapse show" id="overdueContainer">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="overdueTable" class="display nowrap">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Book Name</th>
                                                    <th>Borrower</th>
                                                    <th>Email</th>
                                                    <th>Issue Date</th>
                                                    <th>Due Date</th>
                                                    <th>Days Overdue</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($overdueBooksList) > 0): ?>
                                                    <?php foreach ($overdueBooksList as $book): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($book['book_name']) ?></td>
                                                        <td><?= htmlspecialchars($book['user_name']) ?></td>
                                                        <td><?= htmlspecialchars($book['user_email']) ?></td>
                                                        <td><?= date('M d, Y', strtotime($book['issue_date'])) ?></td>
                                                        <td><?= date('M d, Y', strtotime($book['expected_return_date'])) ?></td>
                                                        <td><span class="badge bg-danger"><?= $book['days_overdue'] ?> days</span></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center"> 
                        <h5 class="card-title mb-0"><i class="bi bi-card-list"></i> Book Transactions History</h5>
                        <!-- Short Dropdown Filter -->
                        <div class="w-25">
                            <select class="form-select" id="dateFilter">
                                <option value="All">All</option>
                                <option value="Today">Today</option>
                                <option value="ThisWeek">This Week</option>
                                <option value="ThisMonth">This Month</option>
                            </select>
                        </div>
                    </div>

                    <div class="collapse show">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="recentTransactionsTable" class="display nowrap">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Book Name</th>
                                            <th>User</th>
                                            <th>Issue Date</th>
                                            <th>Expected Return</th>
                                            <th>Return Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentTransactions as $transaction): ?>
                                        <?php 
                                            $status_class = '';
                                            switch ($transaction['issue_book_status']) {
                                                case 'Issue':
                                                    if (strtotime($transaction['expected_return_date']) < time()) {
                                                        $status_class = 'table-danger';
                                                    } else {
                                                        $status_class = 'table-warning';
                                                    }
                                                    break;
                                                case 'Return':
                                                    $status_class = 'table-success';
                                                    break;
                                                case 'Not Return':
                                                    $status_class = 'table-danger';
                                                    break;
                                                default:
                                                    $status_class = '';
                                            }
                                        ?>
                                        <tr class="<?= $status_class ?>" data-issue-date="<?= $transaction['issue_date'] ?>">
                                            <td><?= htmlspecialchars($transaction['book_name']) ?></td>
                                            <td><?= htmlspecialchars($transaction['user_name']) ?></td>
                                            <td><?= date('M d, Y', strtotime($transaction['issue_date'])) ?></td>
                                            <td><?= date('M d, Y', strtotime($transaction['expected_return_date'])) ?></td>
                                            <td><?= $transaction['return_date'] ? date('M d, Y', strtotime($transaction['return_date'])) : 'Not returned' ?></td>
                                            <td>
                                                <?php if ($transaction['issue_book_status'] == 'Issued'): ?>
                                                    <span class="badge bg-warning">Issued</span>
                                                <?php elseif ($transaction['issue_book_status'] == 'Returned'): ?>
                                                    <span class="badge bg-success">Returned</span>
                                                <?php elseif ($transaction['issue_book_status'] == 'Overdue'): ?>
                                                    <span class="badge bg-danger">Overdue</span>
                                                <?php elseif ($transaction['issue_book_status'] == 'Lost'): ?>
                                                    <span class="badge bg-dark">Lost</span>
                                                <?php endif; ?>
                                            </td>

                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
            
            <!-- Popular Books Tab -->
            <div class="tab-pane fade <?= $active_tab === 'popular' ? 'show active' : '' ?>" id="popular" role="tabpanel">
                <!-- Book Popularity Section -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-trophy"></i> Most Borrowed Books</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="popularBooksChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-list-ol"></i> Top 10 Books</h5>
                            </div>
                            <div class="card-body">
                                <ol class="list-group list-group-numbered">
                                    <?php foreach ($popularBooks as $book): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                        <div class="ms-2 me-auto">
                                            <div class="fw-bold"><?= htmlspecialchars($book['book_name']) ?></div>
                                        </div>
                                        <span class="badge bg-primary rounded-pill"><?= $book['issue_count'] ?> times</span>
                                    </li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Review Book Trends Tab -->
            <div class="tab-pane fade <?= $active_tab === 'review_trends' ? 'show active' : '' ?>" id="review_trends" role="tabpanel">
                <!-- Book Reviews Analytics Section -->
                <div class="row">
                    <div class="col-md-12 d-flex gap-2">                         
                        <!-- Monthly Review Trends -->
                        <div class="col-xl-6 col-sm-12">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title"><i class="bi bi-graph-up"></i> Review Trends</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container h-100" style="position: relative;min-height: 300px;">
                                        <canvas id="reviewTrendsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>         
                        
                        <!-- Combined Right Side (Book Ratings + Review Analytics) -->
                        <div class="col-xl-6 col-sm-12">
                            <!-- Highest & Lowest Rated Books -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title"><i class="bi bi-star"></i> Book Ratings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="bi bi-arrow-up-circle text-success"></i> Highest Rated</h6>
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($highestRatedBooks as $book): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <?= htmlspecialchars($book['book_name']) ?>
                                                    <span class="badge bg-success rounded-pill">
                                                        <?= $book['average_rating'] ?> ⭐
                                                    </span>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="bi bi-arrow-down-circle text-danger"></i> Lowest Rated</h6>
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($lowestRatedBooks as $book): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <?= htmlspecialchars($book['book_name']) ?>
                                                    <span class="badge bg-warning text-dark rounded-pill">
                                                        <?= $book['average_rating'] ?> ⭐
                                                    </span>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>                                                                    
                            
                            <!-- Most Reviewed Books & Active Reviewers -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title"><i class="bi bi-chat-left-text"></i> Review Analytics</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="bi bi-book"></i> Most Reviewed Books</h6>
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($mostReviewedBooks as $book): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <?= htmlspecialchars($book['book_name']) ?>
                                                    <span class="badge bg-info rounded-pill">
                                                        <?= $book['review_count'] ?>
                                                    </span>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="bi bi-person"></i> Most Active Reviewers</h6>
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($mostActiveReviewers as $reviewer): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <?= htmlspecialchars($reviewer['user_name']) ?>
                                                    <span class="badge bg-primary rounded-pill">
                                                        <?= $reviewer['review_count'] ?>
                                                    </span>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>  
                    </div> 
                    <!-- Recent Reviews -->     
                    <div class="col-md-12">
                        <div class="card mb-4 bg-transparent">
                            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                                <h5 class="card-title mb-0"><i class="bi bi-chat-quote"></i> Recent Book Reviews</h5>
                                <!-- Manage Reviews Button -->
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#reviewManagementListModal">
                                    <i class="bi bi-gear"></i> Manage Reviews
                                </button>
                            </div>
                            <div class="card-body p-0 border-0">
                                <!-- Carousel Container -->
                                <div class="reviews-carousel-container">
                                    <div class="reviews-carousel" id="reviewsCarousel" style="width: 65em;">
                                        <?php foreach ($getReviews as $review): ?>
                                            <?php 
                                                $book = getBookById($connect, $review['book_id']);
                                                $imagePath = getBookImagePath($book);
                                                
                                                // Get review status for badge
                                                $statusClass = '';
                                                $statusBadge = '';
                                                
                                                if (isset($review['status'])) {
                                                    switch ($review['status']) {
                                                        case 'approved':
                                                            $statusClass = '';
                                                            $statusBadge = '<span class="badge bg-success position-absolute top-0 end-0 m-2">Approved</span>';
                                                            break;
                                                        case 'rejected':
                                                            $statusClass = 'border-danger';
                                                            $statusBadge = '<span class="badge bg-danger position-absolute top-0 end-0 m-2">Rejected</span>';
                                                            break;
                                                        case 'pending':
                                                            $statusClass = 'border-secondary';
                                                            $statusBadge = '<span class="badge bg-secondary position-absolute top-0 end-0 m-2">Pending</span>';
                                                            break;
                                                        case 'flagged':
                                                            $statusClass = 'border-warning';
                                                            $statusBadge = '<span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">Flagged</span>';
                                                            break;
                                                        default:
                                                            break;
                                                    }
                                                }
                                            ?>
                                            <div class="carousel-item">
                                                <div class="card h-100 review-card position-relative <?= $statusClass ?>">
                                                    <div class="d-flex justify-content-end mt-1">
                                                        <?= $statusBadge ?>
                                                    </div>
                                                    <div class="row g-0">
                                                        <div class="col-4">
                                                            <img src="<?= htmlspecialchars($imagePath) ?>" class="img-fluid rounded-start h-100 object-fit-cover" alt="<?= htmlspecialchars($book['book_name']) ?>">
                                                        </div>
                                                        <div class="col-8">
                                                            <div class="card-body">
                                                                <div class="d-flex justify-content-between mt-3">
                                                                    <h5 class="card-title text-truncate"><?= htmlspecialchars($review['book_name']) ?></h5>
                                                                </div>
                                                                <div class="mb-1">
                                                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                                                        <?php if($i <= $review['rating']): ?>
                                                                            <i class="bi bi-star-fill text-warning"></i>
                                                                        <?php else: ?>
                                                                            <i class="bi bi-star text-secondary"></i>
                                                                        <?php endif; ?>
                                                                    <?php endfor; ?>
                                                                </div>
                                                                <p class="card-text review-text"><?= htmlspecialchars(substr($review['review_text'], 0, 120)) ?><?= strlen($review['review_text']) > 120 ? '...' : '' ?></p>
                                                                <p class="card-text">
                                                                    <small class="text-muted">
                                                                        By <?= htmlspecialchars($review['reviewer_name']) ?> on 
                                                                        <?= date('M d, Y', strtotime($review['created_at'])) ?>
                                                                    </small>
                                                                </p>
                                                                <div class="d-flex justify-content-end mt-1">
                                                                    <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#reviewActionModal<?= $review['review_id'] ?>">View</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>              
            </div>

            <!-- Author Analytics -->
            <div class="tab-pane fade <?= $active_tab === 'authors' ? 'show active' : '' ?>" id="authors" role="tabpanel">
                <div class="row mb-4">
                        <!-- Time period filters -->
                        <div class="col-md-8 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title"><i class="bi bi-filter"></i> Author Analytics by Time Period</h5>
                                    
                                    <!-- Short Dropdown Filter -->
                                    <div class="w-25 mb-3">
                                        <select class="form-select" id="author-time-select">
                                            <option value="author-week">This Week</option>
                                            <option value="author-month">This Month</option>
                                            <option value="author-year">This Year</option>
                                            <option value="author-all" selected>All Time</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-body">
                                
                                    
                                    <div class="tab-content" id="author-time-content">
                                        <!-- This Week -->
                                        <div class="tab-pane fade" id="author-week" role="tabpanel" aria-labelledby="author-week-tab">
                                            <div class="chart-container" style="height: 300px;">
                                                <canvas id="authorWeekChart"></canvas>
                                            </div>
                                        </div>
                                        
                                        <!-- This Month -->
                                        <div class="tab-pane fade" id="author-month" role="tabpanel" aria-labelledby="author-month-tab">
                                            <div class="chart-container" style="height: 300px;">
                                                <canvas id="authorMonthChart"></canvas>
                                            </div>
                                        </div>
                                        
                                        <!-- This Year -->
                                        <div class="tab-pane fade" id="author-year" role="tabpanel" aria-labelledby="author-year-tab">
                                            <div class="chart-container" style="height: 300px;">
                                                <canvas id="authorYearChart"></canvas>
                                            </div>
                                        </div>
                                        
                                        <!-- All Time -->
                                        <div class="tab-pane fade show active" id="author-all" role="tabpanel" aria-labelledby="author-all-tab">
                                            <div class="chart-container" style="height: 300px;">
                                                <canvas id="authorAllTimeChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Author Spotlight -->
                        <div class="col-xl-4 col-lg-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title"><i class="bi bi-award"></i> Author Spotlight</h5>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    // Get the top author from the collected data
                                    $spotlightAuthor = !empty($topAuthors) ? $topAuthors[0] : null;
                                    
                                    if ($spotlightAuthor):
                                        // Extract unique books using book name as the identifier
                                        $uniqueBooks = [];
                                        if (isset($authorBooksMap[$spotlightAuthor['author_id']])) {
                                            foreach ($authorBooksMap[$spotlightAuthor['author_id']] as $book) {
                                                // Use book_name as the identifier since book_id might not be available
                                                $bookIdentifier = $book['book_name']; // We're assuming book_name always exists
                                                
                                                // If this book hasn't been added yet or has a higher borrow count, use this entry
                                                if (!isset($uniqueBooks[$bookIdentifier]) || $uniqueBooks[$bookIdentifier]['borrow_count'] < $book['borrow_count']) {
                                                    $uniqueBooks[$bookIdentifier] = $book;
                                                }
                                            }
                                        }
                                        // Convert associative array back to indexed array
                                        $spotlightAuthorBooks = array_values($uniqueBooks);
                                        
                                        // Sort books by borrow count (highest first)
                                        usort($spotlightAuthorBooks, function($a, $b) {
                                            return $b['borrow_count'] - $a['borrow_count'];
                                        });

                                        // Limit to top 3 books
                                        $spotlightAuthorBooks = array_slice($spotlightAuthorBooks, 0, 3);
                                    ?>
                                    <div class="text-center mb-3">
                                        <div class="display-6"><?= htmlspecialchars($spotlightAuthor['author_name']) ?></div>
                                        <div class="text-muted">Top Author This Month</div>
                                        <div class="fs-4 mt-2">
                                            <span class="badge bg-primary rounded-pill">
                                                <?= $spotlightAuthor['total_borrows'] ?> Total Borrows
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <h6 class="card-subtitle mb-2 text-muted">Most Popular Books:</h6>
                                    <?php if (!empty($spotlightAuthorBooks)): ?>
                                        <ol class="list-group list-group-numbered">
                                            <?php foreach ($spotlightAuthorBooks as $book): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold"><?= htmlspecialchars($book['book_name']) ?></div>
                                                </div>
                                                <span class="badge bg-primary rounded-pill"><?= $book['borrow_count'] ?> borrows</span>
                                            </li>
                                            <?php endforeach; ?>
                                        </ol>
                                    <?php else: ?>
                                        <p class="text-muted">No book data available for this author.</p>
                                    <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            No author data available.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Top Authors Table -->
                        <div class="col-xl-12 col-lg-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title"><i class="bi bi-list-stars"></i> Top Authors</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="topAuthors" class="display nowrap">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Author</th>
                                                    <th>Unique Books Borrowed</th>
                                                    <th>Total Borrows</th>
                                                    <th>This Week</th>
                                                    <th>This Month</th>
                                                    <th>This Year</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($topAuthors as $author): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($author['author_name']) ?></td>
                                                    <td><span class="badge bg-info"><?= $author['unique_books_borrowed'] ?></span></td>
                                                    <td><span class="badge bg-primary"><?= $author['total_borrows'] ?></span></td>
                                                    <td><span class="badge bg-success"><?= $author['week_borrows'] ?></span></td>
                                                    <td><span class="badge bg-warning"><?= $author['month_borrows'] ?></span></td>
                                                    <td><span class="badge bg-danger"><?= $author['year_borrows'] ?></span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                        
                </div>
            </div>

            <!-- Categories Tab -->
            <div class="tab-pane fade <?= $active_tab === 'categories' ? 'show active' : '' ?>" id="categories" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-diagram-3"></i> Category Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="categoryDistributionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-list-check"></i> Categories List</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="category" class="display nowrap">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Number of Books</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categoryStats as $category): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($category['category_name']) ?></td>
                                                <td><span class="badge bg-info"><?= $category['book_count'] ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Active Users Tab -->
            <div class="tab-pane fade <?= $active_tab === 'users' ? 'show active' : '' ?>" id="users" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-people"></i> User Distribution by Role</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="userRolesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-person-lines-fill"></i> Active Users by Role</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="activeUsers" class="display nowrap">
                                        <thead>
                                            <tr>
                                                <th>Role</th>
                                                <th>Number of Users</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $totalUsers = 0;
                                            foreach ($userRoleStats as $role) {
                                                $totalUsers += $role['user_count'];
                                            }
                                            
                                            foreach ($userRoleStats as $role): 
                                                $percentage = ($role['user_count'] / $totalUsers) * 100;
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($role['role_name']) ?></td>
                                                <td><?= $role['user_count'] ?></td>
                                                <td>
                                                    <div class="progress">
                                                        <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%;" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"><?= number_format($percentage, 1) ?>%</div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Active Borrowers Tab -->
            <div class="tab-pane fade <?= $active_tab === 'borrowers' ? 'show active' : '' ?>" id="borrowers" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-bar-chart-line"></i> Most Active Borrowers</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="activeBorrowersChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-person-badge"></i> Top Borrowers</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="topBorrowers" class="display nowrap">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>ID</th>
                                                <th>Books Borrowed</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($activeBorrowers as $borrower): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($borrower['user_name']) ?></td>
                                                <td><?= htmlspecialchars($borrower['user_unique_id']) ?></td>
                                                <td><span class="badge bg-success"><?= $borrower['borrow_count'] ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Review Management List -->
            <div class="modal fade" id="reviewManagementListModal" tabindex="-1" aria-labelledby="reviewManagementModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="reviewManagementModalLabel">Review Management</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <ul class="nav nav-tabs nav-fill mb-3" id="reviewTypeTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="all-reviews-tab" data-bs-toggle="tab" data-bs-target="#all-reviews" type="button" role="tab">
                                        All Reviews
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pending-reviews-tab" data-bs-toggle="tab" data-bs-target="#pending-reviews" type="button" role="tab">
                                        Pending Reviews
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="flagged-reviews-tab" data-bs-toggle="tab" data-bs-target="#flagged-reviews" type="button" role="tab">
                                        Flagged Reviews
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="reviewTypeTabsContent">
                                <!-- All Reviews Tab -->
                                <div class="tab-pane fade show active" id="all-reviews" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Book</th>
                                                    <th>Rating</th>
                                                    <th>Reviewer</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($uniqueReviews as $review): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($review['book_name']) ?></td>
                                                    <td>
                                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                                            <?php if($i <= $review['rating']): ?>
                                                                <i class="bi bi-star-fill text-warning"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-star text-secondary"></i>
                                                            <?php endif; ?>
                                                        <?php endfor; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($review['reviewer_name']) ?></td>
                                                    <td>
                                                        <?php
                                                        $badgeClass = 'bg-secondary';
                                                        switch ($review['status']) {
                                                            case 'approved':
                                                                $badgeClass = 'bg-success-subtle text-dark';
                                                                break;
                                                            case 'rejected':
                                                                $badgeClass = 'bg-danger text-dark';
                                                                break;
                                                            case 'flagged':
                                                                $badgeClass = 'bg-warning text-dark';
                                                                break;                
                                                            case 'pending':
                                                                $badgeClass = 'bg-secondary text-white';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge <?= $badgeClass ?>"><?= ucfirst($review['status']) ?></span>
                                                    </td>
                                                    <td><?= date('M d, Y', strtotime($review['created_at'])) ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#reviewActionModal<?= $review['review_id'] ?>" 
                                                                onclick="handleReviewAction(<?= $review['review_id'] ?>)">
                                                            <i class="bi bi-tools"></i> 
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Pending Reviews Tab -->
                                <div class="tab-pane fade" id="pending-reviews" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Book</th>
                                                    <th>Rating</th>
                                                    <th>Reviewer</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $hasPendingReviews = false;
                                                foreach ($pendingReviews as $review): 
                                                    if ($review['status'] === 'pending'):
                                                    $hasPendingReviews = true;
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($review['book_name']) ?></td>
                                                    <td>
                                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                                            <?php if($i <= $review['rating']): ?>
                                                                <i class="bi bi-star-fill text-warning"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-star text-secondary"></i>
                                                            <?php endif; ?>
                                                        <?php endfor; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($review['reviewer_name']) ?></td>
                                                    <td><?= date('M d, Y', strtotime($review['created_at'])) ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#reviewActionModal<?= $review['review_id'] ?>" 
                                                                onclick="handleReviewAction(<?= $review['review_id'] ?>)">
                                                            <i class="bi bi-tools"></i> 
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php 
                                                    endif; 
                                                endforeach; 
                                                
                                                if (!$hasPendingReviews):
                                                ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No pending reviews found.</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Flagged Reviews Tab -->
                                <div class="tab-pane fade" id="flagged-reviews" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Book</th>
                                                    <th>Rating</th>
                                                    <th>Reviewer</th>
                                                    <th>Flagged By</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $hasFlaggedReviews = false;
                                                foreach ($flaggedReviews as $review): 
                                                    if ($review['status'] === 'flagged'):
                                                    $hasFlaggedReviews = true;
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($review['book_name']) ?></td>
                                                    <td>
                                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                                            <?php if($i <= $review['rating']): ?>
                                                                <i class="bi bi-star-fill text-warning"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-star text-secondary"></i>
                                                            <?php endif; ?>
                                                        <?php endfor; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($review['reviewer_name']) ?></td>
                                                    <td><?= htmlspecialchars($review['flagged_by_name'] ?? 'Unknown') ?></td>
                                                    <td><?= date('M d, Y', strtotime($review['created_at'])) ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#reviewActionModal<?= $review['review_id'] ?>" 
                                                                onclick="handleReviewAction(<?= $review['review_id'] ?>)">
                                                            <i class="bi bi-tools"></i> 
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php 
                                                    endif; 
                                                endforeach; 
                                                
                                                if (!$hasFlaggedReviews):
                                                ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">No flagged reviews found.</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Review Action Modal -->
            <?php foreach ($uniqueReviews as $review): ?>
            <div class="modal fade" id="reviewActionModal<?= $review['review_id'] ?>" tabindex="-1" aria-labelledby="reviewActionLabel<?= $review['review_id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="reviewManagementLabel<?= $review['review_id'] ?>">Manage Review</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Review Details -->
                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?= htmlspecialchars($review['book_name']) ?></h5>
                                    <div>
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <?php if($i <= $review['rating']): ?>
                                                <i class="bi bi-star-fill text-warning"></i>
                                            <?php else: ?>
                                                <i class="bi bi-star text-secondary"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?= htmlspecialchars($review['review_text']) ?></p>
                                    <div class="d-flex justify-content-between mt-3">
                                        <small class="text-muted">By: <?= htmlspecialchars($review['reviewer_name']) ?></small>
                                        <small class="text-muted">Posted: <?= date('M d, Y h:i A', strtotime($review['created_at'])) ?></small>
                                    </div>
                                    
                                    <?php if (!empty($review['remarks'])): ?>
                                    <div class="alert alert-info mt-3">
                                        <h6>Remarks:</h6>
                                        <p><?= htmlspecialchars($review['remarks']) ?></p>
                                        <?php if (!empty($review['flagged_by_name'])): ?>
                                        <small>Flagged by: <?= htmlspecialchars($review['flagged_by_name']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($review['status'] === 'rejected'): ?>
                                    <div class="alert alert-danger mt-3">
                                        <h6>Review Status: Rejected</h6>
                                        <p>This review has been previously rejected. You can still approve it if the issues have been resolved.</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Action Tabs -->
                            <ul class="nav nav-tabs nav-fill" id="reviewActionTabs<?= $review['review_id'] ?>" role="tablist">
                                <?php 
                                // Allow approving for pending, flagged, and rejected reviews
                                $allowApprove = in_array($review['status'], ['pending', 'flagged', 'rejected']);
                                // Always allow delete
                                $allowDelete = true;
                                // Allow reject for pending reviews or approved reviews
                                $allowReject = in_array($review['status'], ['pending', 'approved']);
                                // Allow flag for approved reviews
                                $allowFlag = $review['status'] === 'approved';
                                ?>
                                
                                <?php if ($allowApprove): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="approve-tab<?= $review['review_id'] ?>" data-bs-toggle="tab" data-bs-target="#approve<?= $review['review_id'] ?>" type="button" role="tab">
                                        <i class="bi bi-check-circle"></i> Approve
                                    </button>
                                </li>
                                <?php endif; ?>
                                
                                <?php if ($allowReject): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link<?= !$allowApprove ? ' active' : '' ?>" id="reject-tab<?= $review['review_id'] ?>" data-bs-toggle="tab" data-bs-target="#reject<?= $review['review_id'] ?>" type="button" role="tab">
                                        <i class="bi bi-x-circle"></i> Reject
                                    </button>
                                </li>
                                <?php endif; ?>
                                
                                <?php if ($allowFlag): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link<?= (!$allowApprove && !$allowReject) ? ' active' : '' ?>" id="flag-tab<?= $review['review_id'] ?>" data-bs-toggle="tab" data-bs-target="#flag<?= $review['review_id'] ?>" type="button" role="tab">
                                        <i class="bi bi-flag"></i> Flag
                                    </button>
                                </li>
                                <?php endif; ?>
                                
                                <?php if ($allowDelete): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link<?= (!$allowApprove && !$allowReject && !$allowFlag) ? ' active' : '' ?>" id="delete-tab<?= $review['review_id'] ?>" data-bs-toggle="tab" data-bs-target="#delete<?= $review['review_id'] ?>" type="button" role="tab">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </li>
                                <?php endif; ?>
                            </ul>
                            
                            <!-- Tab Content -->
                            <div class="tab-content p-3 border border-top-0 rounded-bottom" id="reviewActionTabContent<?= $review['review_id'] ?>">
                                <!-- Approve Tab -->
                                <?php if ($allowApprove): ?>
                                <div class="tab-pane fade show active" id="approve<?= $review['review_id'] ?>" role="tabpanel">
                                    <div class="alert alert-success">
                                        <p><strong>Confirmation: </strong><br> Are you sure you want to approve this review? It will be made visible to all users.</p>
                                    </div>
                                    <form action="review_action.php" method="post" data-review-action="true">
                                        <input type="hidden" name="action" value="manage_review">
                                        <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                                        <input type="hidden" name="review_action" value="approve">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <button type="submit" class="btn btn-success">Approve Review</button>
                                            <button class="btn btn-outline-secondary" data-bs-target="#reviewManagementListModal" onclick="goBackToListModal(<?= $review['review_id'] ?>)" data-bs-toggle="modal">Back to List</button>
                                        </div>
                                    </form>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Reject Tab -->
                                <?php if ($allowReject): ?>
                                <div class="tab-pane fade<?= !$allowApprove ? ' show active' : '' ?>" id="reject<?= $review['review_id'] ?>" role="tabpanel">
                                    <form action="review_action.php" method="post" data-review-action="true">
                                        <div class="mb-3">
                                            <label for="remarks<?= $review['review_id'] ?>" class="form-label">Reason for rejection:</label>
                                            <textarea class="form-control" id="remarks<?= $review['review_id'] ?>" name="remarks" rows="3" required></textarea>
                                        </div>
                                        <input type="hidden" name="action" value="manage_review">
                                        <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                                        <input type="hidden" name="review_action" value="reject">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <button type="submit" class="btn btn-danger">Reject Review</button>
                                            <button class="btn btn-outline-secondary" data-bs-target="#reviewManagementListModal" onclick="goBackToListModal(<?= $review['review_id'] ?>)" data-bs-toggle="modal">Back to List</button>
                                        </div>
                                    </form>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Flag Tab -->
                                <?php if ($allowFlag): ?>
                                <div class="tab-pane fade<?= (!$allowApprove && !$allowReject) ? ' show active' : '' ?>" id="flag<?= $review['review_id'] ?>" role="tabpanel">
                                    <form action="review_action.php" method="post" data-flag-action="true">
                                        <div class="mb-3">
                                            <label for="flag_reason<?= $review['review_id'] ?>" class="form-label">Reason for flagging:</label>
                                            <textarea class="form-control" id="flag_reason<?= $review['review_id'] ?>" name="flag_reason" rows="3" required></textarea>
                                        </div>
                                        <input type="hidden" name="action" value="flag_review">
                                        <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <button type="submit" class="btn btn-warning">Flag Review</button>
                                            <button class="btn btn-outline-secondary" data-bs-target="#reviewManagementListModal" onclick="goBackToListModal(<?= $review['review_id'] ?>)" data-bs-toggle="modal">Back to List</button>
                                        </div>
                                    </form>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Delete Tab -->
                                <?php if ($allowDelete): ?>
                                <div class="tab-pane fade<?= (!$allowApprove && !$allowReject && !$allowFlag) ? ' show active' : '' ?>" id="delete<?= $review['review_id'] ?>" role="tabpanel">
                                    <div class="alert alert-danger">
                                        <p><strong>Warning: </strong><br> Are you sure you want to permanently delete this review? This action cannot be undone.</p>
                                    </div>
                                    <form action="review_action.php" method="post" data-review-action="true">
                                        <input type="hidden" name="action" value="manage_review">
                                        <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                                        <input type="hidden" name="review_action" value="delete">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <button type="submit" class="btn btn-danger">Delete Permanently</button>
                                            <button class="btn btn-outline-secondary" data-bs-target="#reviewManagementListModal" onclick="goBackToListModal(<?= $review['review_id'] ?>)" data-bs-toggle="modal">Back to List</button>
                                        </div>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>



    
    <script>
        document.addEventListener("DOMContentLoaded", function () {
           // Convert PHP data for charts (these have been prepared above)
            const popularBooks = <?php echo json_encode($popularBooks); ?>;
            const categoryStats = <?php echo json_encode($categoryStats); ?>;
            const userRoleStats = <?php echo json_encode($userRoleStats); ?>;
            const activeBorrowers = <?php echo json_encode($activeBorrowers); ?>;
            const bookStatusStats = <?php echo json_encode($bookStatusStats); ?>;
            const weeklyAuthors = <?php echo json_encode($weeklyAuthors); ?>;
            const monthlyAuthors = <?php echo json_encode($monthlyAuthors); ?>;
            const yearlyAuthors = <?php echo json_encode($yearlyAuthors); ?>;
            const topAuthors = <?php echo json_encode($topAuthors); ?>;
            const monthlyStats = <?php echo json_encode($monthlyStats); ?>; // For transaction trends
                       
            // Only initialize charts if their canvas elements exist
            
            // Book Status Chart (Doughnut)
            const bookStatusCanvas = document.getElementById("bookStatusChart");
            if (bookStatusCanvas) {
                const bookStatusCtx = bookStatusCanvas.getContext("2d");
                new Chart(bookStatusCtx, {
                    type: "doughnut",
                    data: {
                        labels: bookStatusStats.map(stat => stat.status),
                        datasets: [{
                            data: bookStatusStats.map(stat => stat.count),
                            backgroundColor: ["#007bff", "#28a745", "#dc3545", "#ffc107"]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
            
            // Transaction Trends Chart (Line)
            const transactionTrendsCanvas = document.getElementById("transactionTrendsChart");
            if (transactionTrendsCanvas) {
                const transactionTrendsCtx = transactionTrendsCanvas.getContext("2d");
                // This should use your monthlyStats data, but using placeholder for now
                new Chart(transactionTrendsCtx, {
                    type: "line",
                    data: {
                        labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"], // Replace with actual months
                        datasets: [{
                            label: "Books Issued",
                            data: [50, 75, 60, 90, 120, 110], // Replace with actual data
                            borderColor: "#007bff",
                            backgroundColor: "rgba(0, 123, 255, 0.1)",
                            fill: true
                        }, {
                            label: "Books Returned",
                            data: [40, 70, 55, 85, 115, 105], // Replace with actual data
                            borderColor: "#28a745",
                            backgroundColor: "rgba(40, 167, 69, 0.1)",
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
            
            // Popular Books Chart (Bar)
            const popularBooksCanvas = document.getElementById("popularBooksChart");
            if (popularBooksCanvas) {
                const popularBooksCtx = popularBooksCanvas.getContext("2d");
                new Chart(popularBooksCtx, {
                    type: "bar",
                    data: {
                        labels: popularBooks.map(book => book.book_name),
                        datasets: [{
                            label: "Times Borrowed",
                            data: popularBooks.map(book => book.issue_count),
                            backgroundColor: "#007bff"
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            
            // Category Distribution Chart (Doughnut)
            const categoryCanvas = document.getElementById("categoryDistributionChart");
            if (categoryCanvas) {
                const categoryCtx = categoryCanvas.getContext("2d");
                new Chart(categoryCtx, {
                    type: "doughnut",
                    data: {
                        labels: categoryStats.map(category => category.category_name),
                        datasets: [{
                            data: categoryStats.map(category => category.book_count),
                            backgroundColor: [
                                "#ff6384", "#36a2eb", "#ffce56", "#4bc0c0", 
                                "#ff9f40", "#9966ff", "#c9cbcf", "#7bc043",
                                "#f37736", "#ee4035"
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
            
            // User Roles Chart (Pie)
            const userRolesCanvas = document.getElementById("userRolesChart");
            if (userRolesCanvas) {
                const userRolesCtx = userRolesCanvas.getContext("2d");
                new Chart(userRolesCtx, {
                    type: "pie",
                    data: {
                        labels: userRoleStats.map(role => role.role_name),
                        datasets: [{
                            data: userRoleStats.map(role => role.user_count),
                            backgroundColor: ["#ff6384", "#36a2eb", "#ffce56", "#4bc0c0"]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
            
            // Active Borrowers Chart (Bar)
            const activeBorrowersCanvas = document.getElementById("activeBorrowersChart");
            if (activeBorrowersCanvas) {
                const activeBorrowersCtx = activeBorrowersCanvas.getContext("2d");
                new Chart(activeBorrowersCtx, {
                    type: "bar",
                    data: {
                        labels: activeBorrowers.map(borrower => borrower.user_name),
                        datasets: [{
                            label: "Books Borrowed",
                            data: activeBorrowers.map(borrower => borrower.borrow_count),
                            backgroundColor: "#28a745"
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y', // Horizontal bar chart
                        scales: {
                            x: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            
            // Date filter functionality for transactions table
            const dateFilter = document.getElementById("dateFilter");
            if (dateFilter) {
                dateFilter.addEventListener("change", function() {
                    const dateRange = this.value;
                    const rows = document.querySelectorAll("#recentTransactionsTable tbody tr");
                    
                    const today = new Date();
                    rows.forEach(row => {
                        // Get issue date from data attribute, or fallback to cell content
                        let issueDateCell = row.querySelector("td:nth-child(3)");
                        let issueDateStr = issueDateCell ? issueDateCell.textContent : "";
                        let issueDate = new Date(issueDateStr);
                        
                        let isVisible = false;
                        
                        switch(dateRange) {
                            case 'All':
                                isVisible = true;
                                break;
                            case 'Today':
                                isVisible = issueDate.toDateString() === today.toDateString();
                                break;
                            case 'ThisWeek':
                                const oneWeekAgo = new Date();
                                oneWeekAgo.setDate(today.getDate() - 7);
                                isVisible = issueDate >= oneWeekAgo && issueDate <= today;
                                break;
                            case 'ThisMonth':
                                const oneMonthAgo = new Date();
                                oneMonthAgo.setMonth(today.getMonth() - 1);
                                isVisible = issueDate >= oneMonthAgo && issueDate <= today;
                                break;
                            default:
                                isVisible = true;
                        }
                        
                        row.style.display = isVisible ? "" : "none";
                    });
                });
            }
            
            // Toggle table views
            document.querySelectorAll("[data-bs-toggle='collapse']").forEach(button => {
                button.addEventListener("click", function() {
                    const targetId = this.getAttribute("data-bs-target");
                    const target = document.querySelector(targetId);
                    if (target) {
                        target.classList.toggle("show");
                    }
                });
            });

            const weeklyCanvas = document.getElementById("authorWeekChart");
            if (weeklyCanvas) {
                const weeklyCtx = weeklyCanvas.getContext("2d");
                new Chart(weeklyCtx, {
                    // Chart configuration remains the same
                    type: "bar",
                    data: {
                        labels: weeklyAuthors.map(item => item.author_name),
                        datasets: [{
                            label: "Books Borrowed This Week",
                            data: weeklyAuthors.map(item => item.borrow_count),
                            backgroundColor: "#4CAF50" // Green
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y', // Horizontal bar chart
                        scales: {
                            x: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Books Borrowed'
                                }
                            }
                        }
                    }
                });
            }
            
   
            const monthlyCanvas = document.getElementById("authorMonthChart");
            if (monthlyCanvas) {
                const monthlyCtx = monthlyCanvas.getContext("2d");
                new Chart(monthlyCtx, {
                    type: "bar",
                    data: {
                        labels: monthlyAuthors.map(item => item.author_name),
                        datasets: [{
                            label: "Books Borrowed This Month",
                            data: monthlyAuthors.map(item => item.borrow_count),
                            backgroundColor: "#FF9800" // Orange
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        scales: {
                            x: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Books Borrowed'
                                }
                            }
                        }
                    }
                });
            }
            
            const yearlyCanvas = document.getElementById("authorYearChart");
            if (yearlyCanvas) {
                const yearlyCtx = yearlyCanvas.getContext("2d");
                new Chart(yearlyCtx, {
                    type: "bar",
                    data: {
                        labels: yearlyAuthors.map(item => item.author_name),
                        datasets: [{
                            label: "Books Borrowed This Year",
                            data: yearlyAuthors.map(item => item.borrow_count),
                            backgroundColor: "#E91E63" // Pink
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        scales: {
                            x: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Books Borrowed'
                                }
                            }
                        }
                    }
                });
            }
            
            const allTimeCanvas = document.getElementById("authorAllTimeChart");
            if (allTimeCanvas && topAuthors.length > 0) {
                const allTimeCtx = allTimeCanvas.getContext("2d");
                new Chart(allTimeCtx, {
                    type: "bar",
                    data: {
                        labels: topAuthors.slice(0, 10).map(author => author.author_name),
                        datasets: [{
                            label: "Total Books Borrowed",
                            data: topAuthors.slice(0, 10).map(author => author.total_borrows),
                            backgroundColor: "#3F51B5" // Indigo
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        scales: {
                            x: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Books Borrowed'
                                }
                            }
                        }
                    }
                });
            }
           
            const timeSelect = document.getElementById('author-time-select');
            
            // Add change event handler to the select
            timeSelect.addEventListener('change', function() {
                const selectedValue = this.value;
                
                // Hide all tab panes first
                document.querySelectorAll('#author-time-content .tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                });
                
                // Show the selected tab pane
                const selectedPane = document.getElementById(selectedValue);
                if (selectedPane) {
                    selectedPane.classList.add('show', 'active');
                }
            });


            // Get review data from PHP
            const reviewTrends = <?php echo json_encode(getMonthlyReviewTrends($connect)); ?>;

            // Format month names for chart
            const months = reviewTrends.map(item => {
                const [year, month] = item.month.split('-');
                const date = new Date(year, month - 1);
                return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            });

            // Extract data for charts
            const reviewCounts = reviewTrends.map(item => item.review_count);
            const averageRatings = reviewTrends.map(item => parseFloat(item.average_rating).toFixed(1));

            // Review Trends Chart (Line)
            const reviewTrendsCanvas = document.getElementById("reviewTrendsChart");
            
            if (reviewTrendsCanvas) {
                const reviewTrendsCtx = reviewTrendsCanvas.getContext("2d");
                new Chart(reviewTrendsCtx, {
                    type: "line",
                    data: {
                        labels: months,
                        datasets: [
                            {
                                label: "Number of Reviews",
                                data: reviewCounts,
                                borderColor: "#007bff",
                                backgroundColor: "rgba(0, 123, 255, 0.1)",
                                yAxisID: 'y',
                                fill: true
                            },
                            {
                                label: "Average Rating",
                                data: averageRatings,
                                borderColor: "#ffc107",
                                backgroundColor: "rgba(255, 193, 7, 0.1)",
                                yAxisID: 'y1',
                                fill: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Number of Reviews'
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                min: 0,
                                max: 5,
                                title: {
                                    display: true,
                                    text: 'Average Rating (0-5)'
                                }
                            }
                        }
                    }
                });
            }
        });        
    </script>
    <script>
        $(document).ready(function () {
            // Wait a bit to ensure all tables are rendered
            setTimeout(function() {
                // Function to initialize a DataTable with common options
                function initDataTable(tableId, orderColumn = 0, orderDirection = 'asc') {
                    const table = document.getElementById(tableId);
                    if (table) {
                        const dataTable = new DataTable('#' + tableId, {
                            responsive: true,
                            autoWidth: false,
                            scrollY: '500px',
                            scrollX: true,
                            scrollCollapse: true,
                            paging: false,
                            searching: false, 
                            info: false,
                            fixedHeader: true,
                            stateSave: true,
                            order: [[orderColumn, orderDirection]],
                            language: {
                                emptyTable: "No data found"
                            },
                            drawCallback: function() {
                                // Force recalculation of column sizing
                                setTimeout(() => this.columns.adjust().responsive.recalc(), 80);
                            }
                        });
                        
                        // Store reference to the DataTable
                        return dataTable;
                    }
                    return null;
                }
                
                // Initialize each table with appropriate sorting
                const tables = {
                    recentTransactionsTable: initDataTable('recentTransactionsTable', 2, 'desc'), // Order by issue date
                    overdueTable: initDataTable('overdueTable', 5, 'desc'), // Order by days overdue
                    topAuthors: initDataTable('topAuthors', 2, 'desc'), // Order by total borrows
                    category: initDataTable('category', 1, 'desc'), // Order by book count
                    activeUsers: initDataTable('activeUsers', 1, 'desc'), // Order by user count
                    topBorrowers: initDataTable('topBorrowers', 2, 'desc') // Order by books borrowed
                };
                
                // Handle window resize to maintain column alignment
                $(window).on('resize', function () {
                    Object.values(tables).forEach(table => {
                        if (table) {
                            table.columns.adjust().responsive.recalc();
                        }
                    });
                });
            }, 500); // 500ms delay to ensure DOM is fully loaded
            // Initialize pending reviews table
            $('#pendingReviews').DataTable({
                responsive: true,
                order: [[4, 'desc']], // Order by date column descending
                autoWidth: false,
                scrollY: '500px',
                scrollX: true,
                scrollCollapse: true,
                paging: false,
                searching: false, 
                info: false,
                fixedHeader: true,
                stateSave: true,
                language: {
                    emptyTable: "No pending reviews"
                },
                drawCallback: function() {
                    // Force recalculation of column sizing
                    setTimeout(() => this.columns.adjust().responsive.recalc(), 80);
                }
            });
            
            // Initialize flagged reviews table
            $('#flaggedReviews').DataTable({
                responsive: true,
                order: [[4, 'desc']], // Order by flag count column descending
                autoWidth: false,
                scrollY: '500px',
                scrollX: true,
                scrollCollapse: true,
                paging: false,
                searching: false, 
                info: false,
                fixedHeader: true,
                stateSave: true,
                language: {
                    emptyTable: "No flagged reviews"
                },
                drawCallback: function() {
                    // Force recalculation of column sizing
                    setTimeout(() => this.columns.adjust().responsive.recalc(), 80);
                }
            });
            
            
            

        });
    </script>
    <script>
        // Enhanced Review Action Handler
        function handleReviewAction(reviewId) {
            // Store the current state to allow going back
            window.currentListModal = '#reviewManagementListModal';
            
            // Hide the parent modal
            $(window.currentListModal).modal('hide');
            
            // Show the specific review action modal after a brief delay
            setTimeout(function() {
                $('#reviewActionModal' + reviewId).modal('show');
            }, 300);
            
            // Add event listener for when action modal is hidden
            $('#reviewActionModal' + reviewId).on('hidden.bs.modal', function (e) {
                // Check if we should go back to list modal
                if (window.returnToListModal === true) {
                    $(window.currentListModal).modal('show');
                    window.returnToListModal = false;
                }
            });
            
            // Prevent Bootstrap's default behavior from interfering
            return false;
        }

        // Function to go back to the list modal
        function goBackToListModal(reviewId) {
            window.returnToListModal = true;
            $('#reviewActionModal' + reviewId).modal('hide');
        }

        // Replace form submissions with AJAX + SweetAlert
        $(document).ready(function() {
            // Handle all review management forms
            $(document).on('submit', 'form[data-review-action="true"]', function(e) {
                e.preventDefault();
                
                var form = $(this);
                var formData = form.serialize();
                var reviewId = form.find('input[name="review_id"]').val();
                var action = form.find('input[name="review_action"]').val();
                
                // Close the modal
                $('#reviewActionModal' + reviewId).modal('hide');
                
                // Show loading state
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we process your request.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Send AJAX request
                $.ajax({
                    url: 'review_action.php',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        try {
                            // First check if response is already a JSON object
                            var result = typeof response === 'object' ? response : JSON.parse(response);
                            
                            if (result.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: result.message,
                                    confirmButtonText: 'OK'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: result.message || 'An error occurred while processing your request.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        } catch (e) {
                            console.error('JSON Parse Error:', e, 'Raw Response:', response);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An unexpected error occurred. Please try again.',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to connect to the server. Please check your internet connection and try again.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });
            
            // Handle flag review forms
            $(document).on('submit', 'form[data-flag-action="true"]', function(e) {
                e.preventDefault();
                
                var form = $(this);
                var formData = form.serialize();
                var reviewId = form.find('input[name="review_id"]').val();
                
                // Close the modal
                $('#reviewActionModal' + reviewId).modal('hide');
                
                // Show loading state
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we flag this review.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Send AJAX request
                $.ajax({
                    url: 'review_action.php',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        try {
                            var result = JSON.parse(response);
                            
                            if (result.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: result.message,
                                    confirmButtonText: 'OK'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload(); // Reload to update the review lists
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: result.message || 'An error occurred while flagging the review.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        } catch (e) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An unexpected error occurred. Please try again.',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to connect to the server. Please check your internet connection and try again.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });
            
            // Handle settings form
            $(document).on('submit', 'form[data-settings-action="true"]', function(e) {
                e.preventDefault();
                
                var form = $(this);
                var formData = form.serialize();
                
                // Show loading state
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we update the settings.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Send AJAX request
                $.ajax({
                    url: 'review_action.php',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        try {
                            var result = JSON.parse(response);
                            
                            if (result.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: result.message,
                                    confirmButtonText: 'OK'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: result.message || 'An error occurred while updating settings.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        } catch (e) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An unexpected error occurred. Please try again.',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to connect to the server. Please check your internet connection and try again.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

            // Check for session messages on page load and display with SweetAlert
            <?php if(isset($_SESSION['success_message'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: '<?= htmlspecialchars($_SESSION['success_message']) ?>',
                    confirmButtonText: 'OK'
                });
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error_message'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?= htmlspecialchars($_SESSION['error_message']) ?>',
                    confirmButtonText: 'OK'
                });
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
        });
    </script>
    


<?php include '../footer.php'; ?>