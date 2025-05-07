<?php
// home.php

include '../database_connection.php';
include '../function.php';
include '../header.php';

// Check if user is logged in
authenticate_admin();

$message = '';

// Get data for charts directly
$topBooks = getPopularBooks($connect, 10);
$categoryStats = getCategoryStats($connect);
$monthlyStats = getMonthlyStats($connect);
$activeUsers = getActiveBorrowers($connect, 5);
$overdueBooks = getOverdueBooksList($connect, 5);
$recentTransactions = getRecentTransactions($connect, 5);
$userRoleStats = getUserRoleStats($connect);
$bookStatusStats = getBookStatusStats($connect);
$authorTopBooks = getAuthorTopBooks($connect);
?>

<main class="py-4">
    <h1 class="mb-5">Dashboard</h1>
    
    <!-- Stats Overview Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-counter bg-primary shadow-sm hover-shadow translate-hover rounded transition">
                <span class="count-numbers"><?php echo Count_total_issue_book_number($connect); ?></span>
                <span class="count-name">Total Books Issued</span>
                <i class="bi bi-book float-end"></i>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-counter bg-warning shadow-sm hover-shadow translate-hover rounded transition">
                <span class="count-numbers"><?php echo Count_total_returned_book_number($connect); ?></span>
                <span class="count-name">Total Books Returned</span>
                <i class="bi bi-arrow-return-left float-end"></i>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-counter bg-danger shadow-sm hover-shadow translate-hover rounded transition">
                <span class="count-numbers"><?php echo Count_total_not_returned_book_number($connect); ?></span>
                <span class="count-name">Total Books Not Returned</span>
                <i class="bi bi-exclamation-circle float-end"></i>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-counter bg-success shadow-sm hover-shadow translate-hover rounded transition">
                <span class="count-numbers"><?php echo get_currency_symbol($connect) . Count_total_fines_received($connect); ?></span>
                <span class="count-name">Total Fines Received</span>
                <i class="bi bi-cash-stack float-end"></i>
            </div>
        </div>
    </div>

    <!-- Main Analytics Charts -->
    <div class="row mt-4">
        <!-- Most Frequently Borrowed Books Chart -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">📚 Most Frequently Borrowed Books</h5>
                    <canvas id="topBooksChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Book Category Distribution Pie Chart -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">📊 Book Categories Distribution</h5>
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Analytics -->
    <div class="row mt-4">
        <!-- Monthly Circulation Trends -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">📈 Monthly Circulation Trends</h5>
                    <canvas id="monthlyStatsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- User Role Distribution -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">👥 User Role Distribution</h5>
                    <canvas id="userRolesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables and Lists -->
    <div class="row mt-4">
        <!-- Active Borrowers -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title">🏆 Top Active Borrowers</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>ID</th>
                                    <th>Books Borrowed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeUsers as $user): ?>
                                <tr>
                                    <td><?php echo $user['user_name']; ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $user['user_unique_id']; ?></span></td>
                                    <td><span class="badge bg-primary"><?php echo $user['borrow_count']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Books -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title">⚠️ Overdue Books</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>User</th>
                                    <th>Days Overdue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdueBooks as $book): ?>
                                <tr>
                                    <td><?php echo $book['book_name']; ?></td>
                                    <td><?php echo $book['user_name']; ?></td>
                                    <td><span class="badge bg-danger"><?php echo $book['days_overdue']; ?> days</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">📝 Recent Transactions</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>User</th>
                                    <th>Issue Date</th>
                                    <th>Expected Return</th>
                                    <th>Return Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                <tr>
                                    <td><?php echo $transaction['book_name']; ?></td>
                                    <td><?php echo $transaction['user_name']; ?></td>
                                    <td><?php echo $transaction['issue_date']; ?></td>
                                    <td><?php echo $transaction['expected_return_date']; ?></td>
                                    <td><?php echo $transaction['return_date'] ?: '-'; ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = '';
                                        switch($transaction['issue_book_status']) {
                                            case 'Issue': $statusClass = 'warning'; break;
                                            case 'Return': $statusClass = 'success'; break;
                                            case 'Not Return': $statusClass = 'danger'; break;
                                            case 'Overdue': $statusClass = 'danger'; break;
                                            default: $statusClass = 'secondary';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $transaction['issue_book_status']; ?></span>
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
</main>

<!-- Chart.js for Analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Convert PHP data to JavaScript
        const topBooksData = <?php 
            $bookIds = array_column($topBooks, 'book_id');
            $bookNames = array_column($topBooks, 'book_name');
            $issueCounts = array_column($topBooks, 'issue_count');
            echo json_encode(['ids' => $bookIds, 'names' => $bookNames, 'counts' => $issueCounts]);
        ?>;

        const categoryData = <?php 
            $categoryNames = array_column($categoryStats, 'category_name');
            $bookCounts = array_column($categoryStats, 'book_count');
            echo json_encode(['names' => $categoryNames, 'counts' => $bookCounts]);
        ?>;

        const monthlyData = <?php echo json_encode($monthlyStats); ?>;
        
        const userRoleData = <?php 
            $roleNames = array_column($userRoleStats, 'role_name');
            $userCounts = array_column($userRoleStats, 'user_count');
            echo json_encode(['names' => $roleNames, 'counts' => $userCounts]);
        ?>;

        // Top Books Chart (Bar & Line)
        const ctxTopBooks = document.getElementById("topBooksChart").getContext("2d");
        new Chart(ctxTopBooks, {
            type: "bar",
            data: {
                labels: topBooksData.names.map(name => name.length > 25 ? name.substring(0, 22) + '...' : name),
                datasets: [
                    {
                        type: 'line',
                        label: "Trend Line",
                        data: topBooksData.counts,
                        borderColor: "#ff6b6b",
                        borderWidth: 2,
                        fill: false,
                        tension: 0.3,
                    },
                    {
                        type: 'bar',
                        label: "Number of Issues",
                        data: topBooksData.counts,
                        backgroundColor: "rgba(54, 162, 235, 0.6)",
                        borderColor: "rgba(54, 162, 235, 1)",
                        borderWidth: 1,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const index = context.dataIndex;
                                const bookName = topBooksData.names[index];
                                const issueCount = topBooksData.counts[index];
                                if (context.dataset.type === 'line') {
                                    return `Trend (${bookName}): ${issueCount} Issues`;
                                }
                                return `${bookName}: ${issueCount} Issues`;
                            }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Category Distribution Chart (Pie)
        const ctxCategory = document.getElementById("categoryChart").getContext("2d");
        new Chart(ctxCategory, {
            type: "pie",
            data: {
                labels: categoryData.names,
                datasets: [{
                    data: categoryData.counts,
                    backgroundColor: [
                        "#007bff", "#ffc107", "#dc3545", "#28a745", "#17a2b8", 
                        "#ff5733", "#33ff57", "#3357ff", "#ff33a8", "#33fff0"
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });

        // Monthly Stats Chart (Line)
        const ctxMonthlyStats = document.getElementById("monthlyStatsChart").getContext("2d");
        new Chart(ctxMonthlyStats, {
            type: "line",
            data: {
                labels: monthlyData.map(item => item.month),
                datasets: [
                    {
                        label: "Books Issued",
                        data: monthlyData.map(item => item.issued),
                        borderColor: "#007bff",
                        backgroundColor: "rgba(0, 123, 255, 0.1)",
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: "Books Returned",
                        data: monthlyData.map(item => item.returned),
                        borderColor: "#28a745",
                        backgroundColor: "rgba(40, 167, 69, 0.1)",
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: "Books Lost/Not Returned",
                        data: monthlyData.map(item => item.lost),
                        borderColor: "#dc3545",
                        backgroundColor: "rgba(220, 53, 69, 0.1)",
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // User Roles Chart (Doughnut)
        const ctxUserRoles = document.getElementById("userRolesChart").getContext("2d");
        new Chart(ctxUserRoles, {
            type: "doughnut",
            data: {
                labels: userRoleData.names,
                datasets: [{
                    data: userRoleData.counts,
                    backgroundColor: [
                        "#6610f2", "#fd7e14", "#20c997", "#e83e8c", "#6f42c1"
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    });
</script>

    <?php 
        include '../footer.php';
    ?>