<?php

//database_connection.php

$connect = new PDO("mysql:host=localhost; dbname=lms", "root", "");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


?>