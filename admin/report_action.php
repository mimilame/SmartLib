<?php
// reports_action.php

include '../database_connection.php';
include '../function.php';
include '../header.php';

$message = '';

// View Report Form - Triggered by ?action=view&code=xxx
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])):
    $id = $_GET['code'];

    // Fetching the report data
    $query = "SELECT * FROM lms_reports WHERE report_id = :id LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->execute([':id' => $id]);
    $report = $statement->fetch(PDO::FETCH_ASSOC);

    if ($report): 
?>

    <!-- View Report Details -->
    <div class="card mx-5 mt-5">
        <div class="card-header"><h5>View Report</h5></div>
        <div class="card-body">
            <p><strong>Book Title:</strong> <?= htmlspecialchars($report['book_title']) ?></p>
            <p><strong>User:</strong> <?= htmlspecialchars($report['user_name']) ?></p>
            <p><strong>Issue Date:</strong> <?= date('M d, Y', strtotime($report['issue_date'])) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($report['status']) ?></p>

            <!-- Actions -->
            <div class="text-center">
                <a href="reports.php" class="btn btn-secondary">Back</a>
                <!-- You can add additional actions here, like Edit or Delete if needed -->
            </div>
        </div>
    </div>

<?php 
    else: 
?>
        <p class="alert alert-danger">Report not found.</p>
        <a href="report.php" class="btn btn-secondary">Back</a>
<?php 
    endif;
endif;
?>
