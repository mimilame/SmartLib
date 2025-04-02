<?php
include '../database_connection.php';

$filter = $_GET['filter'] ?? 'all';

$query = "SELECT b.book_name, u.user_name, i.issue_date, i.return_date, i.expected_return_date, i.issue_book_status 
          FROM lms_issue_book i 
          JOIN lms_book b ON i.book_id = b.book_id 
          JOIN lms_user u ON i.user_id = u.user_id ";

if ($filter === 'day') {
    $query .= "WHERE DATE(i.issue_date) = CURDATE() ";
} elseif ($filter === 'week') {
    $query .= "WHERE YEARWEEK(i.issue_date, 1) = YEARWEEK(CURDATE(), 1) "; // Week filter
} elseif ($filter === 'month') {
    $query .= "WHERE MONTH(i.issue_date) = MONTH(CURDATE()) AND YEAR(i.issue_date) = YEAR(CURDATE()) ";
}

$query .= "ORDER BY i.issue_date DESC";

$stmt = $connect->prepare($query);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($transactions);
?>
