<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();
include 'head.php';

$query = "SELECT * FROM lms_setting LIMIT 1";
$statement = $connect->prepare($query);
$statement->execute();
$row = $statement->fetch(PDO::FETCH_ASSOC);

$library_name = isset($row['library_name']) ? $row['library_name'] : 'Library Management System';
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;
$user_type = 'visitor';

if ($role_id) {
    $query = "SELECT role_name FROM user_roles WHERE role_id = :role_id LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->bindParam(':role_id', $role_id, PDO::PARAM_INT);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    $user_type = $result['role_name'] ?? 'visitor';
}

$page_title = $library_name . " - " . ucfirst($user_type);
?>

<?php if ($role_id == 1 || $role_id == 2): ?>
    <!-- Admin & Librarian Header -->
    <div class="d-flex">
        <div id="sidebar" class="bg-dark text-light vh-100 p-3" style="width: 250px;">
            <h5 class="text-center"><?php echo $page_title; ?></h5>
            <nav class="nav flex-column">
                <a class="nav-link text-light" href="<?php echo base_url(); ?>admin/index.php">Dashboard</a>
                <a class="nav-link text-light" href="<?php echo base_url(); ?>admin/category.php">Category</a>
                <a class="nav-link text-light" href="<?php echo base_url(); ?>admin/author.php">Author</a>
                <a class="nav-link text-light" href="<?php echo base_url(); ?>admin/location_rack.php">Location Rack</a>
                <a class="nav-link text-light" href="<?php echo base_url(); ?>admin/book.php">Book</a>
                <a class="nav-link text-light" href="<?php echo base_url(); ?>admin/issue_book.php">Issue Book</a>
                <?php if ($role_id == 1): ?>
                    <a class="nav-link text-light" href="<?php echo base_url(); ?>admin/librarian.php">Librarian</a>
                    <a class="nav-link text-light" href="<?php echo base_url(); ?>admin/user.php">Users</a>
                <?php endif; ?>
                <a class="nav-link text-light" href="<?php echo base_url(); ?>admin/report.php">Reports</a>
                <a class="nav-link text-light" href="<?php echo base_url(); ?>admin/fine.php">Fines</a>
            </nav>
        </div>
        <div class="flex-grow-1 overflow-auto">
            <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
                <div class="container-fluid">
                    <button class="btn btn-outline-light me-2 d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user fa-fw"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>admin/profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>admin/setting.php">Settings</a></li>
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>admin/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
<?php elseif ($role_id == 3 || $role_id == 4): ?>
    <!-- Faculty & Student Header -->
    <div class="d-flex">
            <div id="sidebar" class="bg-dark text-light vh-100 p-3" style="width: 250px;">
                <h5 class="text-center"><?php echo $page_title; ?></h5>
                <nav class="nav flex-column">
                    <a class="nav-link text-light" href="<?php echo base_url(); ?>user/index.php">Dashboard</a>
                    <a class="nav-link text-light" href="<?php echo base_url(); ?>user/search_book.php">Search Book</a>
                    <a class="nav-link text-light" href="<?php echo base_url(); ?>user/issue_book_details.php">Issue Book Details</a>
                </nav>
            </div>
        <div class="flex-grow-1 overflow-auto">
            <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
                <div class="container-fluid">
                    <button class="btn btn-outline-light me-2 d-lg-none" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user fa-fw"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>user/profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>user/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
<?php elseif ($role_id == 5): ?>
    <!-- Visitor Header -->
    <div class="d-flex flex-wrap fixed-top align-items-center justify-content-center justify-content-md-between mb-4">
		<header class="header d-flex flex-wrap align-items-center justify-content-center justify-content-md-between py-3 mb-4 border-bottom bg-danger w-100">
			<a href="/" class="d-flex align-items-center col-md-3 mb-2 mb-md-0 text-dark text-decoration-none">
				<img src="asset\img\logo.png" alt="SmartLib" width="32" height="32" class="rounded-circle ">
				<?php echo $library_name; ?>
			</a>

			<ul class="nav col-12 col-md-auto mb-2 justify-content-center mb-md-0">
				<li><a href="<?php echo base_url(); ?>index.php" class="nav-link px-2 link-secondary text-light">Home</a></li>
				<li><a href="<?php echo base_url(); ?>#" class="nav-link px-2 link-dark text-light">Books</a></li>
				<li><a href="<?php echo base_url(); ?>#" class="nav-link px-2 link-dark text-light">Reports</a></li>
			</ul>

			<div class="dropdown">
                <a href="#" class="d-block link-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <img src="https://github.com/mdo.png" alt="User" width="32" height="32" class="rounded-circle">
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?php echo base_url(); ?>#">Profile</a></li>
                    <li><a class="dropdown-item" href="<?php echo base_url(); ?>logout.php">Sign out</a></li>
                </ul>
            </div>
		</header>
	</div>
<?php else: ?>
    <!-- Default (Not Logged In) Header -->
    <div class="d-flex flex-wrap fixed-top align-items-center justify-content-center justify-content-md-between mb-4">
		<header class="header mask d-flex flex-wrap align-items-center justify-content-center justify-content-md-between py-3 mb-4 border-bottom bg-danger w-100">
			<a href="/" class="d-flex align-items-center col-md-3 mb-2 mb-md-0 ms-2 text-light fw-bold text-decoration-none">
				<img src="asset\img\logo.png" alt="SmartLib" width="32" height="32" class="rounded-circle ">
				<?php echo $library_name; ?>
			</a>

			<ul class="nav col-12 col-md-auto mb-2 justify-content-center mb-md-0">
				<li><a href="<?php echo base_url(); ?>#" class="nav-link px-2 link-secondary text-light">Home</a></li>
				<li><a href="<?php echo base_url(); ?>#" class="nav-link px-2 link-dark text-light">Books</a></li>
				<li><a href="<?php echo base_url(); ?>#" class="nav-link px-2 link-dark text-light">About Us</a></li>
			</ul>

			<div class="col-md-3 text-end d-flex justify-content-end">
				<button type="button" class="logbtn btn btn-outline-light me-3 text-secondary">Login</button>
				<button type="button" class="regbtn btn btn-warning text-secondary me-4">Sign-up</button>
			</div>
		</header>
	</div>
<?php endif; ?>


<script>
    console.log("Current path: <?php echo $_SERVER['PHP_SELF']; ?>");
    console.log("Session Data:", <?php echo json_encode($_SESSION); ?>);
</script>


            <!-- Main Page Content Here -->
