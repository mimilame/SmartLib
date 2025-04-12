<?php

//database_connection.php
$host = 'localhost';
$dbname = 'lms';
$username = 'root';
$password = '';


$connect = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci"
]);

$connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



?>