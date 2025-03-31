<?php
include '../database_connection.php';

$data = [];

// Check if connection is successful
if (!$connect) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Query to count issued and returned books per month based on issue_book_status
$query = "
    SELECT 
        DATE_FORMAT(issue_date, '%b') AS month, 
        COUNT(CASE WHEN issue_book_status = 'Issued' THEN 1 END) AS issued_books,
        COUNT(CASE WHEN issue_book_status = 'Returned' THEN 1 END) AS returned_books
    FROM issue_book 
    GROUP BY DATE_FORMAT(issue_date, '%b')
    ORDER BY MONTH(issue_date)
";

$result = $connect->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
} else {
    // Log SQL error for debugging
    echo "Error in SQL Query: " . $connect->error;
    exit();
}

// Return as JSON
header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Close the connection
$connect->close();
?>
