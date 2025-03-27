<?php 

//logout.php

session_start();

session_destroy();

// Redirect to the index.php outside the user folder
header('Location: /SmartLib/index.php');
exit();

?>