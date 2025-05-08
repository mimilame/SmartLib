<?php
//database_connection.php
    try {
        // WAMP local environment settings
        $host = 'localhost'; // For local WAMP, use localhost
        $dbname = 'lms';
        $username = 'root';
        $password = ''; // Default WAMP MySQL password is blank
    
        $connect = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        
        // Set encoding
        $connect->exec("SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci");
        $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        // Don't expose connection details in production
        die("Connection failed. Please try again later.");
    }
?>