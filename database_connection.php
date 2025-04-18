<?php

//database_connection.php

try {

    $host = 'localhost';
    $dbname = 'lms';
    $username = 'root';
    $password = '';
    
    $connect = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci"
    ]);
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    // Don't expose connection details in production
    die("Connection failed. Please try again later.");
}

?>