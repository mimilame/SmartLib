<?php
//database_connection.php
    try {
        // In Docker environment, the host should be the service name, not localhost
        $host = 'db'; // This refers to the MySQL container service name
        $dbname = 'lms';
        $username = 'root';
        $password = 'root'; // As defined in docker-compose.yml
    
        // Remove the problematic constant if PDO extension doesn't support it
        $connect = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        
        // Set charset separately instead of using MYSQL_ATTR_INIT_COMMAND
        $connect->exec("SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci");
        $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        // Don't expose connection details in production
        die("Connection failed. Please try again later.");
    }
?>