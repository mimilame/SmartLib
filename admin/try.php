<?php
// Database connection settings

include '../database_connection.php';
include '../function.php';
include '../header.php';

$message = '';
    
    // Fetch book circulation statistics
    $bookStatusStats = $connect->query("
        SELECT 
            issue_book_status as status,
            COUNT(*) as count
        FROM lms_issue_book
        GROUP BY issue_book_status
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch most frequently borrowed books
    $popularBooks = $connect->query("
       SELECT 
        ib.book_id,
        b.book_name,
        COUNT(ib.book_id) AS issue_count
    FROM lms_issue_book ib
    INNER JOIN lms_book b ON ib.book_id = b.book_id
    GROUP BY ib.book_id, b.book_name
    ORDER BY issue_count DESC
    LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    

    
    // Fetch category distribution
    $categoryStats = $connect->query("
        SELECT 
            c.category_name,
            COUNT(b.book_id) as book_count
        FROM lms_category c
        LEFT JOIN lms_book b ON c.category_id = b.category_id
        WHERE c.category_status = 'Enable'
        GROUP BY c.category_id
        ORDER BY book_count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch active users by role
    $userRoleStats = $connect->query("
        SELECT 
            r.role_name,
            COUNT(u.user_id) as user_count
        FROM user_roles r
        LEFT JOIN lms_user u ON r.role_id = u.role_id
        WHERE u.user_status = 'Enable'
        GROUP BY r.role_id
        ORDER BY user_count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch most active borrowers
    $activeBorrowers = $connect->query("
        SELECT 
            u.user_name,
            u.user_unique_id,
            COUNT(i.issue_book_id) as borrow_count
        FROM lms_user u
        JOIN lms_issue_book i ON u.user_id = i.user_id
        GROUP BY u.user_id
        ORDER BY borrow_count DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch recently issued books
    $recentIssues = $connect->query("
        SELECT 
            b.book_name,
            u.user_name,
            i.issue_date,
            i.expected_return_date,
            i.issue_book_status
        FROM lms_issue_book i
        JOIN lms_book b ON i.book_id = b.book_id
        JOIN lms_user u ON i.user_id = u.user_id
        ORDER BY i.issued_on DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch library settings
    $librarySettings = $connect->query("
        SELECT * FROM lms_setting LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Calculate borrow duration metrics
    $borrowMetrics = $connect->query("
        SELECT 
            AVG(DATEDIFF(IFNULL(return_date, CURRENT_DATE), issue_date)) as avg_borrow_days,
            SUM(CASE WHEN return_date <= expected_return_date THEN 1 ELSE 0 END) as on_time_returns,
            SUM(CASE WHEN return_date > expected_return_date THEN 1 ELSE 0 END) as late_returns,
            SUM(CASE WHEN issue_book_status = 'Lost' THEN 1 ELSE 0 END) as lost_books,
            COUNT(*) as total_borrows
        FROM lms_issue_book
    ")->fetch(PDO::FETCH_ASSOC);
    

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartLib Analytics Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .dashboard-header {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            font-weight: bold;
            background-color: #e9ecef;
        }
        .stat-card {
            text-align: center;
            padding: 15px;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        table {
            width: 100%;
        }
        th {
            background-color: #e9ecef;
        }
        .chart-container {
            position: relative;
            height: 250px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
<main class="container py-4" style="min-height: 700px;">

<h1 class="my-3">Reports</h1>
	<div class="row mb-4"></div>

    <div class="container">
        <!-- Key Metrics Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-value text-primary">
                        <?= array_sum(array_column($bookStatusStats, 'count')) ?>
                    </div>
                    <div class="stat-label">Total Book Transactions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-value text-success">
                        <?= number_format($borrowMetrics['avg_borrow_days'], 1) ?>
                    </div>
                    <div class="stat-label">Avg. Borrowing Days</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-value text-danger">
                        <?= $borrowMetrics['lost_books'] ?>
                    </div>
                    <div class="stat-label">Books Lost</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="stat-value text-warning">
                        <?php 
                        $issued_count = 0;
                        foreach($bookStatusStats as $stat) {
                            if($stat['status'] == 'Issued') $issued_count = $stat['count'];
                        }
                        echo $issued_count;
                        ?>
                    </div>
                    <div class="stat-label">Currently Issued</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Book Circulation Chart -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Book Circulation Status</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="bookStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Distribution -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Book Category Distribution</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
    <!-- Popular Books Table -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Most Frequently Borrowed Books</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Authors</th>
                                <th>Category</th>
                                <th>Borrows</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($popularBooks as $book): ?>
                            <tr>
                                <td><?= htmlspecialchars($book['book_name']) ?></td>
                                <td><?= htmlspecialchars($book['author_name']) ?></td>
                                <td><?= htmlspecialchars($book['category_name']) ?></td>
                                <td><span class="badge bg-primary"><?= $book['borrow_count'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

            

        <div class="row">
            <!-- Active Borrowers -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Most Active Borrowers</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User Name</th>
                                        <th>ID</th>
                                        <th>Borrows</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($activeBorrowers as $borrower): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($borrower['user_name']) ?></td>
                                        <td><?= htmlspecialchars($borrower['user_unique_id']) ?></td>
                                        <td><span class="badge bg-info"><?= $borrower['borrow_count'] ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Issues -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Recent Book Transactions</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th>User</th>
                                        <th>Issue Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recentIssues as $issue): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($issue['book_name']) ?></td>
                                        <td><?= htmlspecialchars($issue['user_name']) ?></td>
                                        <td><?= htmlspecialchars($issue['issue_date']) ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = '';
                                            switch($issue['issue_book_status']) {
                                                case 'Issued': $statusClass = 'bg-warning'; break;
                                                case 'Returned': $statusClass = 'bg-success'; break;
                                                case 'Overdue': $statusClass = 'bg-danger'; break;
                                                case 'Lost': $statusClass = 'bg-dark'; break;
                                            }
                                            ?>
                                            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($issue['issue_book_status']) ?></span>
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

        <!-- User Role Distribution and Library Settings -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">User Role Distribution</div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="userRoleChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Library Settings</div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <tr>
                                <th>Operating Hours</th>
                                <td><?= htmlspecialchars($librarySettings['library_open_hours']) ?></td>
                            </tr>
                            <tr>
                                <th>Max Borrowing Days</th>
                                <td><?= htmlspecialchars($librarySettings['library_total_book_issue_day']) ?> days</td>
                            </tr>
                            <tr>
                                <th>Late Fee Per Day</th>
                                <td><?= htmlspecialchars($librarySettings['library_currency']) ?> <?= htmlspecialchars($librarySettings['library_one_day_fine']) ?></td>
                            </tr>
                            <tr>
                                <th>Max Books Per User</th>
                                <td><?= htmlspecialchars($librarySettings['library_issue_total_book_per_user']) ?> books</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script>
        // Book Status Chart
        const bookStatusData = <?= json_encode(array_column($bookStatusStats, 'count')) ?>;
        const bookStatusLabels = <?= json_encode(array_column($bookStatusStats, 'status')) ?>;
        
        new Chart(document.getElementById('bookStatusChart'), {
            type: 'pie',
            data: {
                labels: bookStatusLabels,
                datasets: [{
                    data: bookStatusData,
                    backgroundColor: [
                        '#ffc107', // Issued - warning
                        '#28a745', // Returned - success
                        '#dc3545', // Overdue - danger
                        '#343a40'  // Lost - dark
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Category Chart
        const categoryData = <?= json_encode(array_column($categoryStats, 'book_count')) ?>;
        const categoryLabels = <?= json_encode(array_column($categoryStats, 'category_name')) ?>;
        
        new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Number of Books',
                    data: categoryData,
                    backgroundColor: '#6f42c1',
                    borderColor: '#6610f2',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // User Role Chart
        const userRoleData = <?= json_encode(array_column($userRoleStats, 'user_count')) ?>;
        const userRoleLabels = <?= json_encode(array_column($userRoleStats, 'role_name')) ?>;
        
        new Chart(document.getElementById('userRoleChart'), {
            type: 'doughnut',
            data: {
                labels: userRoleLabels,
                datasets: [{
                    data: userRoleData,
                    backgroundColor: [
                        '#fd7e14', // Orange
                        '#20c997', // Teal
                        '#e83e8c', // Pink
                        '#17a2b8', // Cyan
                        '#6c757d'  // Gray
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    </script>
