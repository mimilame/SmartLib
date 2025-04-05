<?php
// Database connection settings
include '../database_connection.php';
include '../function.php';
include '../header.php';

$message = '';

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

?>


    <style>
        .status-card {
            transition: all 0.3s ease;
        }
        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            font-weight: bold;
        }
        .card-counter {
            padding: 20px;
            border-radius: 10px;
            color: #fff;
            transition: all 0.3s ease;
        }
        .card-counter i {
            font-size: 4rem;
            opacity: 0.4;
        }
        .card-counter .count-numbers {
            position: absolute;
            right: 35px;
            top: 20px;
            font-size: 32px;
            display: block;
        }
        .card-counter .count-name {
            position: absolute;
            right: 35px;
            top: 65px;
            font-style: italic;
            text-transform: capitalize;
            opacity: 0.8;
            display: block;
        }
        .bg-issued { background-color: #4caf50; }
        .bg-returned { background-color: #2196F3; }
        .bg-overdue { background-color: #ff9800; }
        .bg-lost { background-color: #f44336; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h1 class="mb-4"><i class="bi bi-bar-chart-line"></i> Reports</h1>
        
        <ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab" aria-controls="transactions" aria-selected="true">
                    <i class="bi bi-arrow-left-right"></i> Book Transactions
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="popular-tab" data-bs-toggle="tab" data-bs-target="#popular" type="button" role="tab" aria-controls="popular" aria-selected="false">
                    <i class="bi bi-star"></i> Popular Books
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="authors-tab" data-bs-toggle="tab" data-bs-target="#authors" type="button" role="tab" aria-controls="authors" aria-selected="true">
                    <i class="bi bi-pen"></i> Author Analytics
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab" aria-controls="categories" aria-selected="false">
                    <i class="bi bi-list-check"></i> Categories
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="false">
                    <i class="bi bi-people"></i> Active Users
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="borrowers-tab" data-bs-toggle="tab" data-bs-target="#borrowers" type="button" role="tab" aria-controls="borrowers" aria-selected="false">
                    <i class="bi bi-person-badge"></i> Active Borrowers
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="reportTabsContent">
            <!-- Book Transactions Tab -->
            <div class="tab-pane fade show active" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
                <div class="row mb-4">
                    
                <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                        <div class="card card-counter bg-issued status-card">
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
                        <div class="card card-counter bg-returned status-card">
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
                        <div class="card card-counter bg-overdue status-card">
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
                        <div class="card card-counter bg-lost status-card">
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
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#overdueTable">
                                    <i class="bi bi-arrows-expand"></i> Toggle View
                                </button>
                            </div>
                            <div class="collapse show" id="overdueTable">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
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
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center">No overdue books at the moment.</td>
                                                    </tr>
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

                    <div class="collapse show" id="recentTransactionsTable">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
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
            <div class="tab-pane fade" id="popular" role="tabpanel" aria-labelledby="popular-tab">
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
            <!-- Author Analytics -->
            <div class="tab-pane fade" id="authors" role="tabpanel" aria-labelledby="authors-tab">
                <div class="row mb-4">
                        <!-- Time period filters -->
                        <div class="col-md-8 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title"><i class="bi bi-filter"></i> Author Analytics by Time Period</h5>
                                    
                                    <!-- Short Dropdown Filter -->
                                    <div class="w-25 mb-3">
                                        <select class="form-select" id="author-time-select">
                                            <option value="author-week" selected>This Week</option>
                                            <option value="author-month">This Month</option>
                                            <option value="author-year">This Year</option>
                                            <option value="author-all">All Time</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-body">
                                
                                    
                                    <div class="tab-content" id="author-time-content">
                                        <!-- This Week -->
                                        <div class="tab-pane fade show active" id="author-week" role="tabpanel" aria-labelledby="author-week-tab">
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
                                        <div class="tab-pane fade" id="author-all" role="tabpanel" aria-labelledby="author-all-tab">
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
                                        <table class="table table-bordered table-hover">
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
            <div class="tab-pane fade" id="categories" role="tabpanel" aria-labelledby="categories-tab">
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
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
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
            <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
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
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
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
            <div class="tab-pane fade" id="borrowers" role="tabpanel" aria-labelledby="borrowers-tab">
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
                                    <table class="table table-bordered table-hover">
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
        </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
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
        });
    </script>