<?php 
include 'head.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$query = "SELECT * FROM lms_setting LIMIT 1";
$statement = $connect->prepare($query);
$statement->execute();
$row = $statement->fetch(PDO::FETCH_ASSOC);
// Fetch library settings
$query = "SELECT * FROM lms_setting LIMIT 1";
$statement = $connect->prepare($query);
$statement->execute();
$row = $statement->fetch(PDO::FETCH_ASSOC);

$library_name = isset($row['library_name']) ? $row['library_name'] : 'Library Management System';

// Validate if role_id exists before accessing
$role_id = $_SESSION['role_id'] ?? null;
$user_type = 'visitor'; // Default user type

if ($role_id) {
    // Fetch user type based on role_id
    $query = "SELECT role_name FROM user_roles WHERE role_id = :role_id LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->bindParam(':role_id', $role_id, PDO::PARAM_INT);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);

    $user_type = $result['role_name'] ?? 'visitor';
}

$page_title = $library_name . " - " . ucfirst($user_type); 


?>

<?php 
if ($role_id == 1 || $role_id == 2): ?>
    <!-- Sidebar for Admin and Librarian -->
    <div class="d-flex">
        <div id="sidebar" class="bg-dark text-light vh-100 p-3" style="width: 250px;">
            <h5 class="text-center"><?php echo $page_title; ?></h5>
            <nav class="nav flex-column">
                <a class="nav-link text-light" href="index.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                <a class="nav-link text-light" href="category.php"><i class="fas fa-list-alt me-2"></i>Category</a>
                <a class="nav-link text-light" href="author.php"><i class="fas fa-user me-2"></i>Author</a>
                <a class="nav-link text-light" href="location_rack.php"><i class="fas fa-map-marker-alt me-2"></i>Location Rack</a>
                <a class="nav-link text-light" href="book.php"><i class="fas fa-book me-2"></i>Book</a>
                <a class="nav-link text-light" href="issue_book.php"><i class="fas fa-book-open me-2"></i>Issue Book</a>
                <?php if ($role_id == 1): ?>
                    <a class="nav-link text-light" href="librarian.php"><i class="fas fa-user-tie me-2"></i>Librarian</a>
                    <a class="nav-link text-light" href="user.php"><i class="fas fa-users me-2"></i>Users</a>
                <?php endif; ?>
                <a class="nav-link text-light" href="report.php"><i class="fas fa-chart-line me-2"></i>Reports</a>
                <a class="nav-link text-light" href="fine.php"><i class="fas fa-money-check me-2"></i>Fines</a>
            </nav>
        </div>

        <!-- Navbar -->
        <div class="flex-grow-1 overflow-auto">
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
<?php endif; ?>

<?php 
if ($role_id == 3 || $role_id == 4): ?>
    <!-- Sidebar for Faculty and Student -->
    <div class="d-flex">
        <div id="sidebar" class="bg-dark text-light vh-100 p-3" style="width: 250px;">
            <h5 class="text-center"><?php echo $page_title; ?></h5>
            <nav class="nav flex-column">
                <a class="nav-link text-light" href="index.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                <a class="nav-link text-light" href="search_book.php"><i class="fas fa-search me-2"></i>Search Book</a>
                <a class="nav-link text-light" href="issue_book_details.php"><i class="fas fa-book-open me-2"></i>Issue Book Details</a>
                
            </nav>
        </div>

        <!-- Navbar -->
        <div class="flex-grow-1 overflow-auto">
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
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                    
                </div>
            </nav>
<?php endif; ?>

<?php 
    // Redirect guests to index
    if ($role_id == 5) {
        header('location:index.php');
        exit;
    }
?>

            <!-- Main Page Content Here -->
