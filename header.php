<?php 
include 'head.php';

// Fetch library settings
$query = "SELECT * FROM lms_setting LIMIT 1";
$statement = $connect->prepare($query);
$statement->execute();
$row = $statement->fetch(PDO::FETCH_ASSOC);

$library_name = isset($row['library_name']) ? $row['library_name'] : 'Library Management System';

if (is_admin_login()) {
?>

<body class="overflow-hidden">
    <div class="d-flex">
        <!-- Sidebar -->
        <div id="sidebar" class="bg-dark text-light vh-100 p-3" style="width: 250px;height:-web-kit-max-content;">
            <h5 class="text-center"><?php echo $library_name; ?></h5>
            <nav class="nav flex-column">
                <a class="nav-link text-light" href="index.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                <a class="nav-link text-light" href="category.php"><i class="fas fa-list-alt me-2"></i>Category</a>
                <a class="nav-link text-light" href="author.php"><i class="fas fa-user me-2"></i>Author</a>
                <a class="nav-link text-light" href="location_rack.php"><i class="fas fa-map-marker-alt me-2"></i>Location Rack</a>
                <a class="nav-link text-light" href="book.php"><i class="fas fa-book me-2"></i>Book</a>
                <a class="nav-link text-light" href="librarian.php"><i class="fas fa-solid fa-user-tie me-2"></i>Librarian</a>
                <a class="nav-link text-light" href="user.php"><i class="fas fa-users me-2"></i>Users</a>
                <a class="nav-link text-light" href="issue_book.php"><i class="fas fa-book-open me-2"></i>Issue Book</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
                <div class="container-fluid">
                    <button class="btn btn-outline-light me-2 d-lg-none" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>

                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user fa-fw"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="setting.php">Settings</a></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="container py-4">
                <!-- Main Page Content Here -->
<?php 
} else { 
?>
    <main class="container">
        <header class="pb-3 mb-4 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="fs-4"><?php echo $library_name; ?></h1>
                <?php if (is_user_login()) { ?>
                    <nav>
                        <ul class="list-inline">
                            <li class="list-inline-item"><?php echo $_SESSION['user_id']; ?></li>
                            <li class="list-inline-item"><a href="issue_book_details.php">Issue Book</a></li>
                            <li class="list-inline-item"><a href="search_book.php">Search Book</a></li>
                            <li class="list-inline-item"><a href="profile.php">Profile</a></li>
                            <li class="list-inline-item"><a href="logout.php">Logout</a></li>
                        </ul>
                    </nav>
                <?php } ?>
            </div>
        </header>
<?php 
} 
?>
