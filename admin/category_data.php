<?php
include '../database_connection.php';

$query = "SELECT category_name, COUNT(*) as count FROM lms_category WHERE category_status = 'Enable' GROUP BY category_name";
$statement = $connect->prepare($query);
$statement->execute();
$categories = $statement->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($categories);
?>
