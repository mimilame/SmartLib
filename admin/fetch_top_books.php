<?php
include '../database_connection.php';

$query = "
    SELECT 
        ib.book_id,
        b.book_name,
        COUNT(ib.book_id) AS issue_count
    FROM lms_issue_book ib
    INNER JOIN lms_book b ON ib.book_id = b.book_id
    GROUP BY ib.book_id, b.book_name
    ORDER BY issue_count DESC
    LIMIT 10
";

$statement = $connect->prepare($query);
$statement->execute();
$books = $statement->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($books);
?>
