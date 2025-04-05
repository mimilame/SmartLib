<?php
// Add this to your existing PHP code section where other queries are defined
// Database connection settings
include '../database_connection.php';
include '../function.php';
include '../header.php';

// Fetch top authors by borrowed books (overall)
$topAuthors = $connect->query("
    SELECT 
        a.author_id,
        a.author_name,
        GROUP_CONCAT(DISTINCT b.book_name ORDER BY b.book_name ASC) as unique_books_borrowed,  -- Concatenate unique book names
        COUNT(ib.issue_book_id) as total_borrows,
        SUM(CASE WHEN ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK) THEN 1 ELSE 0 END) as week_borrows,
        SUM(CASE WHEN ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN 1 ELSE 0 END) as month_borrows,
        SUM(CASE WHEN ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 1 ELSE 0 END) as year_borrows
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
        total_borrows DESC
    LIMIT 15
")->fetchAll(PDO::FETCH_ASSOC);

// This block contains the corrected version of the SQL union query
$authorTimeStats = $connect->query("
    (SELECT 
        'week' as time_period,
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
        a.author_status = 'Enable' AND
        ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)
    GROUP BY 
        a.author_id, a.author_name
    ORDER BY 
        borrow_count DESC
    LIMIT 5)

    UNION ALL

    (SELECT 
        'month' as time_period,
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
        a.author_status = 'Enable' AND
        ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
    GROUP BY 
        a.author_id, a.author_name
    ORDER BY 
        borrow_count DESC
    LIMIT 5)

    UNION ALL

    (SELECT 
        'year' as time_period,
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
        a.author_status = 'Enable' AND
        ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
    GROUP BY 
        a.author_id, a.author_name
    ORDER BY 
        borrow_count DESC
    LIMIT 5)
")->fetchAll(PDO::FETCH_ASSOC);


// Format the data for charts
$weeklyAuthors = array_filter($authorTimeStats, function($item) { return $item['time_period'] == 'week'; });
$monthlyAuthors = array_filter($authorTimeStats, function($item) { return $item['time_period'] == 'month'; });
$yearlyAuthors = array_filter($authorTimeStats, function($item) { return $item['time_period'] == 'year'; });

// Get the most borrowed book for each top author
$authorTopBooks = $connect->query("
    SELECT * FROM (
    SELECT * FROM (
        SELECT 
            'week' AS time_period,
            a.author_id,
            a.author_name,
            b.book_name,
            COUNT(ib.issue_book_id) AS borrow_count
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
            AND ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)
        GROUP BY 
            a.author_id, a.author_name, b.book_id, b.book_name
        ORDER BY 
            borrow_count DESC
        LIMIT 15
    ) AS weekly

    UNION ALL

    SELECT * FROM (
        SELECT 
            'month' AS time_period,
            a.author_id,
            a.author_name,
            b.book_name,
            COUNT(ib.issue_book_id) AS borrow_count
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
            AND ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
        GROUP BY 
            a.author_id, a.author_name, b.book_id, b.book_name
        ORDER BY 
            borrow_count DESC
        LIMIT 15
    ) AS monthly

    UNION ALL

    SELECT * FROM (
        SELECT 
            'year' AS time_period,
            a.author_id,
            a.author_name,
            b.book_name,
            COUNT(ib.issue_book_id) AS borrow_count
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
            AND ib.issue_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
        GROUP BY 
            a.author_id, a.author_name, b.book_id, b.book_name
        ORDER BY 
            borrow_count DESC
        LIMIT 15
    ) AS yearly
) AS combined_data
ORDER BY time_period, borrow_count DESC;


")->fetchAll(PDO::FETCH_ASSOC);

// Group the top books by author
$authorBooksMap = [];
foreach ($authorTopBooks as $book) {
    if (!isset($authorBooksMap[$book['author_id']])) {
        $authorBooksMap[$book['author_id']] = [];
    }
    if (count($authorBooksMap[$book['author_id']]) < 3) { // Get top 3 books per author
        $authorBooksMap[$book['author_id']][] = $book;
    }
}
?>
<!-- Add this to your CSS file or style section -->
<style>
    /* Author Tab Specific Styles */
    #authors .card {
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: all 0.3s ease;
    }
    
    #authors .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    #authors .card-header {
        background-color: rgba(0, 123, 255, 0.1);
        border-bottom: 1px solid rgba(0, 123, 255, 0.2);
    }
    
    #authors .nav-pills .nav-link {
        color: #495057;
        border-radius: 0.25rem;
        margin-right: 0.5rem;
        padding: 0.5rem 1rem;
    }
    
    #authors .nav-pills .nav-link.active {
        background-color: #007bff;
        color: white;
    }
    
    #authors .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }
    
    #authors .badge {
        font-size: 0.875rem;
    }
    
    /* Author spotlight styles */
    #authors .display-6 {
        font-size: 1.75rem;
        font-weight: 600;
        color: #333;
    }
    
    #authors .list-group-numbered .list-group-item {
        position: relative;
        padding: 0.75rem 1.25rem 0.75rem 3rem;
    }
    
    #authors .list-group-numbered .list-group-item::before {
        position: absolute;
        left: 1rem;
        color: #6c757d;
    }
</style>
<div class="container-fluid mt-4">
        <h1 class="mb-4"><i class="bi bi-bar-chart-line"></i> Reports</h1>
        
        <ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
                <button class="nav-link active" id="authors-tab" data-bs-toggle="tab" data-bs-target="#authors" type="button" role="tab" aria-controls="authors" aria-selected="true">
                    <i class="bi bi-arrow-left-right"></i> Author Analytics
                </button>
</li>
        </ul>
        
        <div class="tab-content" id="reportTabsContent">
            <!-- Author Analytics -->
            <div class="tab-pane fade show active" id="authors" role="tabpanel" aria-labelledby="authors-tab">
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
                                        $spotlightAuthorBooks = isset($authorBooksMap[$spotlightAuthor['author_id']]) 
                                            ? $authorBooksMap[$spotlightAuthor['author_id']] 
                                            : [];
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

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
            <script>
                // Add this to your existing JavaScript code section

document.addEventListener("DOMContentLoaded", function() {
    // Convert PHP arrays to JavaScript for author charts
    const weeklyAuthors = <?php echo json_encode(array_values($weeklyAuthors)); ?>;
    const monthlyAuthors = <?php echo json_encode(array_values($monthlyAuthors)); ?>;
    const yearlyAuthors = <?php echo json_encode(array_values($yearlyAuthors)); ?>;
    const topAuthors = <?php echo json_encode($topAuthors); ?>;
    
    // Chart initialization code (unchanged)
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
    
    // Other chart initializations remain the same
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

    // FIX: This is the section that needs to be corrected
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