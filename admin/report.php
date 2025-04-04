<?php
// Database connection settings
include '../database_connection.php';
include '../function.php';
include '../header.php';

$message = '';

// Fetch book circulation statistics
$bookStatusStats = $connect->query("
    SELECT issue_book_status as status, COUNT(*) as count 
    FROM lms_issue_book 
    GROUP BY issue_book_status
")->fetchAll(PDO::FETCH_ASSOC);

// Convert to format usable by charts
$statusLabels = [];
$statusCounts = [];
foreach ($bookStatusStats as $stat) {
    $statusLabels[] = $stat['status'];
    $statusCounts[] = $stat['count'];
}

// Fetch overdue books statistics
$overdueBooks = $connect->query("
    SELECT COUNT(*) as count 
    FROM lms_issue_book 
    WHERE issue_book_status = 'Overdue' 
")->fetch(PDO::FETCH_ASSOC);

// Fetch monthly transaction statistics (last 6 months)
$monthlyStats = $connect->query("
    SELECT 
        DATE_FORMAT(issue_date, '%b %Y') as month,
        COUNT(CASE WHEN issue_book_status = 'Issue' THEN 1 END) as issued,
        COUNT(CASE WHEN issue_book_status = 'Return' THEN 1 END) as returned,
        COUNT(CASE WHEN issue_book_status = 'Not Return' THEN 1 END) as lost
    FROM lms_issue_book
    WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(issue_date, '%Y-%m')
    ORDER BY issue_date
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch most frequently borrowed books
$popularBooks = $connect->query("
    SELECT ib.book_id, b.book_name, COUNT(ib.book_id) AS issue_count 
    FROM lms_issue_book ib 
    INNER JOIN lms_book b ON ib.book_id = b.book_id 
    GROUP BY ib.book_id, b.book_name 
    ORDER BY issue_count DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch category distribution
$categoryStats = $connect->query("SELECT c.category_name, COUNT(b.book_id) as book_count FROM lms_category c LEFT JOIN lms_book b ON c.category_id = b.category_id WHERE c.category_status = 'Enable' GROUP BY c.category_id ORDER BY book_count DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch active users by role
$userRoleStats = $connect->query("
    SELECT r.role_name, COUNT(u.user_id) as user_count 
    FROM user_roles r 
    LEFT JOIN lms_user u ON r.role_id = u.role_id 
    WHERE u.user_status = 'Enable' 
    GROUP BY r.role_id, r.role_name
    ORDER BY user_count DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch most active borrowers
$activeBorrowers = $connect->query("
    SELECT u.user_name, u.user_unique_id, COUNT(i.issue_book_id) as borrow_count 
    FROM lms_user u 
    JOIN lms_issue_book i ON u.user_id = i.user_id 
    GROUP BY u.user_id, u.user_name, u.user_unique_id
    ORDER BY borrow_count DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent transactions
$recentTransactions = $connect->query("
    SELECT b.book_name, u.user_name, i.issue_date, i.return_date, i.expected_return_date, i.issue_book_status 
    FROM lms_issue_book i 
    JOIN lms_book b ON i.book_id = b.book_id 
    JOIN lms_user u ON i.user_id = u.user_id 
    ORDER BY i.issue_date DESC 
")->fetchAll(PDO::FETCH_ASSOC);

// Prepare category data for charts
$categoryNames = [];
$categoryCounts = [];
foreach ($categoryStats as $category) {
    $categoryNames[] = $category['category_name'];
    $categoryCounts[] = $category['book_count'];
}

// Prepare user role data for charts
$roleNames = [];
$roleCounts = [];
foreach ($userRoleStats as $role) {
    $roleNames[] = $role['role_name'];
    $roleCounts[] = $role['user_count'];
}

// Fetch detailed overdue books listing
$overdueBooksList = $connect->query("
    SELECT b.book_name, u.user_name, u.user_email, i.issue_date, i.expected_return_date,
           DATEDIFF(CURDATE(), i.expected_return_date) as days_overdue
    FROM lms_issue_book i 
    JOIN lms_book b ON i.book_id = b.book_id 
    JOIN lms_user u ON i.user_id = u.user_id 
    WHERE i.issue_book_status = 'Issue' 
    AND i.expected_return_date < CURDATE()
    ORDER BY days_overdue DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                                                <tr class="<?= $status_class ?>">
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

 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
 <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Initialize transaction trends chart
        const transactionTrendsCtx = document.getElementById("transactionTrendsChart").getContext("2d");
        const transactionTrendsChart = new Chart(transactionTrendsCtx, {
            type: "line",
            data: {
                labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
                datasets: [{
                    label: "Books Issued",
                    data: [50, 75, 60, 90, 120, 110],
                    borderColor: "#007bff",
                    backgroundColor: "rgba(0, 123, 255, 0.1)",
                    fill: true
                }, {
                    label: "Books Returned",
                    data: [40, 70, 55, 85, 115, 105],
                    borderColor: "#28a745",
                    backgroundColor: "rgba(40, 167, 69, 0.1)",
                    fill: true
                }]
            }
        });

        // Initialize book status chart
        const bookStatusCtx = document.getElementById("bookStatusChart").getContext("2d");
        const bookStatusChart = new Chart(bookStatusCtx, {
            type: "doughnut",
            data: {
                labels: ["Issued", "Returned", "Overdue", "Lost"],
                datasets: [{
                    data: [120, 90, 15, 5],
                    backgroundColor: ["#007bff", "#28a745", "#dc3545", "#ffc107"]
                }]
            }
        });

        // Toggle table views
        document.querySelectorAll("[data-bs-toggle='collapse']").forEach(button => {
            button.addEventListener("click", function () {
                const target = document.querySelector(this.getAttribute("data-bs-target"));
                target.classList.toggle("show");
            });
        });

        // Handle tab navigation
        document.querySelectorAll(".nav-link").forEach(tab => {
            tab.addEventListener("click", function () {
                document.querySelectorAll(".nav-link").forEach(t => t.classList.remove("active"));
                this.classList.add("active");
            });
        });

        // Popular Books Chart
        const popularBooksChartCtx = document.getElementById("popularBooksChart").getContext("2d");
        new Chart(popularBooksChartCtx, {
            type: "bar",
            data: {
                labels: popularBooks.map(book => book.book_name),
                datasets: [{
                    label: "Times Borrowed",
                    data: popularBooks.map(book => book.issue_count),
                    backgroundColor: "#007bff"
                }]
            }
        });

        // Category Distribution Chart
        const categoryChartCtx = document.getElementById("categoryDistributionChart").getContext("2d");
        new Chart(categoryChartCtx, {
            type: "doughnut",
            data: {
                labels: categoryStats.map(category => category.category_name),
                datasets: [{
                    data: categoryStats.map(category => category.book_count),
                    backgroundColor: ["#ff6384", "#36a2eb", "#ffce56", "#4bc0c0"]
                }]
            }
        });

        // User Roles Distribution Chart
        const userRolesChartCtx = document.getElementById("userRolesChart").getContext("2d");
        new Chart(userRolesChartCtx, {
            type: "pie",
            data: {
                labels: userRoleStats.map(role => role.role_name),
                datasets: [{
                    data: userRoleStats.map(role => role.user_count),
                    backgroundColor: ["#ff6384", "#36a2eb", "#ffce56"]
                }]
            }
        });

        // Active Borrowers Chart
        const activeBorrowersChartCtx = document.getElementById("activeBorrowersChart").getContext("2d");
        new Chart(activeBorrowersChartCtx, {
            type: "bar",
            data: {
                labels: activeBorrowers.map(borrower => borrower.user_name),
                datasets: [{
                    label: "Books Borrowed",
                    data: activeBorrowers.map(borrower => borrower.borrow_count),
                    backgroundColor: "#28a745"
                }]
            }
        });

        document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".filter-option").forEach(item => {
        item.addEventListener("click", function (e) {
            e.preventDefault();
            let filterType = this.getAttribute("data-filter");
            
            fetchTransactions(filterType);
        });
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const dateFilter = document.getElementById("dateFilter");
    const rows = document.querySelectorAll("#recentTransactionsTable tbody tr");

    function filterRows(dateRange) {
        const today = new Date();
        rows.forEach(row => {
            const issueDate = new Date(row.getAttribute("data-issue-date"));
            let isVisible = false;

            switch (dateRange) {
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
                    break;
            }

            row.style.display = isVisible ? "" : "none";
        });
    }

    // Event listener for the dropdown selection change
    dateFilter.addEventListener("change", function () {
        filterRows(this.value);
    });
});

    });
</script>
