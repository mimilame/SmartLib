<?php
//header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();
include 'head.php';

$query = "SELECT * FROM lms_setting LIMIT 1";
$statement = $connect->prepare($query);
$statement->execute();
$row = $statement->fetch(PDO::FETCH_ASSOC);
$library_logo = isset($row['library_logo']) ? $row['library_logo'] : 'logo.png';
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

// Determine default profile image based on user role
$default_img = base_url() . 'upload/default.jpg'; // Default fallback

if (isset($_SESSION['role_name'])) {
    switch ($_SESSION['role_name']) {
        case 'admin':
            $default_img = base_url() . 'asset/img/admin.jpg';
            break;
        case 'librarian':
            $default_img = base_url() . 'asset/img/librarian.jpg';
            break;
        case 'faculty':
        case 'student':
        case 'visitor':
            $default_img = base_url() . 'asset/img/user.jpg';
            break;
    }
}

// Use session-stored profile image if available, otherwise use default
$profile_img = isset($_SESSION['profile_img']) && !empty($_SESSION['profile_img']) 
    ? base_url() . 'upload/' . $_SESSION['profile_img'] 
    : $default_img;

// Use session-stored username if available, otherwise default to "Account"
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Account';
?>
<style>
    .nav-link:focus, .nav-link:hover {
    color: #fff;
    }
</style>

<?php if ($role_id == 1 || $role_id == 2): ?>
    <!-- Admin & Librarian Header -->
    <body class="">
    <?php include 'preloader.php'; ?>
    <div class="d-flex">
        <!-- Sidebar -->
        <div id="sidebar" class="bg-dark text-light">
            <div class="sidebar-header">
                <img src="<?php echo base_url(); 'asset/img/' . $library_logo;?>" alt="Library Logo" class="sidebar-logo">
                <span class="sidebar-header-text fw-bold"><?php echo $library_name; ?></span>
            </div>
            <nav class="nav flex-column p-2">
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'index') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/index.php" data-bs-toggle="tooltip" data-bs-placement="right" 
                data-bs-title="Dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'category') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/category.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                data-bs-title="Category">
                    <i class="fas fa-folder"></i>
                    <span class="nav-text">Category</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'author') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/author.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                data-bs-title="Author">
                    <i class="fas fa-pen-fancy"></i>
                    <span class="nav-text">Author</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'location_rack') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/location_rack.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                data-bs-title="Location Rack">
                    <i class="fas fa-map-marker-alt"></i>
                    <span class="nav-text">Location Rack</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'book') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/book.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                data-bs-title="Book">
                    <i class="fas fa-book"></i>
                    <span class="nav-text">Book</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'issue_book') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/issue_book.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                data-bs-title="Issue Book">
                    <i class="fas fa-bookmark"></i>
                    <span class="nav-text">Issue Book</span>
                </a>
                <?php if ($role_id == 1): ?>
                    <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'librarian') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/librarian.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                    data-bs-title="Librarian">
                        <i class="fas fa-user-tie"></i>
                        <span class="nav-text">Librarian</span>
                    </a>
                    <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'user') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/user.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                    data-bs-title="Users">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Users</span>
                    </a>
                <?php endif; ?>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'report') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/report.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                data-bs-title="Reports">
                    <i class="fas fa-chart-bar"></i>
                    <span class="nav-text">Reports</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'fines') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/fines.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                data-bs-title="Fines">
                    <i class="fas fa-dollar-sign"></i>
                    <span class="nav-text">Fines</span>
                </a>
            </nav>
        </div>
        
        <!-- Main Content Area -->
        <div class="content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm">
                <div class="container-fluid">
                    <button class="btn btn-outline-light me-2" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="navbar-brand d-none d-lg-block"><?php echo $page_title; ?></span>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?php echo $profile_img; ?>" alt="Profile Image" class="rounded-circle" width="40" height="40">
                                <span class="d-none d-md-inline-block ms-1">
                                    <?php echo htmlspecialchars($user_name); ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>admin/profile.php"><i class="fas fa-user-edit fa-fw me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>admin/setting.php"><i class="fas fa-cog fa-fw me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>admin/logout.php"><i class="fas fa-sign-out-alt fa-fw me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
            <main class="container-fluid py-4" style="min-height: 700px;">
<?php elseif ($role_id == 3 || $role_id == 4): ?>
    <!-- Faculty & Student Header -->
    <body class="">
    <?php include 'preloader.php'; ?>
    <div class="d-flex">
        <!-- Sidebar -->
        <div id="sidebar" class="bg-dark text-light">
            <div class="sidebar-header">
                <img src="<?php echo base_url(); 'asset/img/' . $library_logo;?>" alt="Library Logo" class="sidebar-logo">
                <span class="sidebar-header-text fw-bold"><?php echo $library_name; ?></span>
            </div>
            <nav class="nav flex-column p-2">
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'index') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>user/index.php" data-bs-toggle="tooltip" data-bs-placement="right" 
                data-bs-title="Dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'search_book') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>user/search_book.php" data-bs-toggle="tooltip" data-bs-placement="right" 
                data-bs-title="Search Book">
                    <i class="fas fa-search"></i>
                    <span class="nav-text">Search Book</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'issue_book_details') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>user/issue_book_details.php" data-bs-toggle="tooltip" data-bs-placement="right" 
                data-bs-title="Issue Book Details">
                    <i class="fas fa-book-open"></i>
                    <span class="nav-text">Issue Book Details</span>
                </a>
            </nav>
        </div>
        
        <!-- Main Content Area -->
        <div class="content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm">
                <div class="container-fluid">
                    <button class="btn btn-outline-light me-2" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="navbar-brand d-none d-lg-block"><?php echo $page_title; ?></span>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?php echo $profile_img; ?>" alt="Profile Image" class="rounded-circle" width="40" height="40">
                                <span class="d-none d-md-inline-block ms-1">
                                    <?php echo htmlspecialchars($user_name); ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>user/profile.php"><i class="fas fa-user-edit fa-fw me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>user/setting.php"><i class="fas fa-cog fa-fw me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>user/logout.php"><i class="fas fa-sign-out-alt fa-fw me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
            <main class="container-fluid py-4" style="min-height: 700px;">
<?php elseif ($role_id == 5): ?>
    <!-- Visitor Header -->
    <?php include 'preloader.php'; ?>
    <div class="d-flex flex-wrap fixed-top align-items-center justify-content-center justify-content-md-between mb-4">
		<header class="header d-flex flex-wrap align-items-center justify-content-center justify-content-md-between py-3 mb-4 border-bottom bg-danger w-100">
			<a href="/" class="d-flex align-items-center col-md-3 mb-2 mb-md-0 text-dark text-decoration-none">
				<img src="<?php echo base_url() . 'asset/img/' . $library_logo; ?>" alt="Library Logo" width="32" height="32" class="rounded-circle ">
				<?php echo $library_name; ?>
			</a>

			<ul class="nav col-12 col-md-auto mb-2 justify-content-center mb-md-0">
				<li><a href="<?php echo base_url(); ?>guest/index.php" class="nav-link px-2 link-secondary text-light <?php echo (basename($_SERVER['PHP_SELF']) == 'index') ? 'active' : ''; ?>" >Home</a></li>
				<li><a href="<?php echo base_url(); ?>guest/books.php" class="nav-link px-2 link-secondary text-light <?php echo (basename($_SERVER['PHP_SELF']) == 'book') ? 'active' : ''; ?>" >Books</a></li>
				<li><a href="<?php echo base_url(); ?>guest/reports.php" class="nav-link px-2 link-dark text-light  <?php echo (basename($_SERVER['PHP_SELF']) == 'report') ? 'active' : ''; ?>">Reports</a></li>
			</ul>

			<div class="dropdown">
                <a href="#" class="d-block link-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <img src="<?php echo $profile_img; ?>" alt="User" width="32" height="32" class="rounded-circle">
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
    <?php include 'preloader.php'; ?>
    <div class="d-flex flex-wrap fixed-top align-items-center justify-content-center justify-content-md-between mb-4">
		<header class="header mask d-flex flex-wrap align-items-center justify-content-center justify-content-md-between py-3 mb-4 border-bottom bg-danger w-100">
			<a href="/" class="d-flex align-items-center col-md-3 mb-2 mb-md-0 ms-2 text-light fw-bold text-decoration-none">
				<img src="<?php echo base_url() . 'asset/img/' . $library_logo; ?>" alt="SmartLib" width="32" height="32" class="rounded-circle ">
				<?php echo $library_name; ?>
			</a>

			<ul class="nav col-12 col-md-auto mb-2 justify-content-center mb-md-0">
				<li><a href=" <?php echo base_url(); ?>index.php" class="nav-link px-2 link-secondary text-light <?php echo (basename($_SERVER['PHP_SELF']) == 'home') ? 'active' : ''; ?>">Home</a></li>
				<li><a href="<?php echo base_url(); ?>book.php" class="nav-link px-2 link-dark text-light <?php echo (basename($_SERVER['PHP_SELF']) == 'books') ? 'active' : ''; ?>">Books</a></li>
				<li><a href="<?php echo base_url(); ?>about_us" class="nav-link px-2 link-dark text-light <?php echo (basename($_SERVER['PHP_SELF']) == 'about us') ? 'active' : ''; ?>">About Us</a></li>
			</ul>

			<div class="col-md-3 text-end d-flex justify-content-end">
				<button type="button" class="logbtn btn btn-outline-light me-3 text-secondary">Login</button>
				<button type="button" class="regbtn btn btn-warning text-secondary me-4">Sign-up</button>
			</div>
		</header>
	</div>
<?php endif; ?>


<script>
    window.addEventListener('load', function () {
        var preloader = document.getElementById('preloader');
        if (preloader) {
            preloader.style.transition = 'opacity 0.5s ease'; // Smooth transition
            preloader.style.opacity = '0';
            setTimeout(function () {
                preloader.style.display = 'none';
            }, 800); // 2000ms = 2 seconds delay
        }
    });


    
    console.log("Current path: <?php echo $_SERVER['PHP_SELF']; ?>");
    console.log("Session Data:", <?php echo json_encode($_SESSION); ?>);
    // Add this to your scripts.js file or in a script tag at the bottom of your page
    document.addEventListener('DOMContentLoaded', function() {
        // Get sidebar toggle button
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const contentWrapper = document.querySelector('.content-wrapper');
        // Add data-title attributes to all nav links (if not already added in PHP)
        document.querySelectorAll('#sidebar .nav-link').forEach(link => {
            if (!link.hasAttribute('data-title')) {
                const navText = link.querySelector('.nav-text');
                if (navText) {
                    link.setAttribute('data-title', navText.textContent);
                }
            }
            // Add tooltip-nav class to all nav links
            link.classList.add('tooltip-nav');
        });
         // Function to check viewport width and collapse sidebar if needed
        function checkWidth() {
            if (window.innerWidth < 992) {
                sidebar.classList.remove('collapsed');
                sidebar.classList.add('show');
                contentWrapper.classList.remove('expanded');
            } else {
                // For desktop, restore the saved state from localStorage
                const sidebarState = localStorage.getItem('sidebarCollapsed');
                
                if (sidebarState === 'true') {
                    sidebar.classList.add('collapsed');
                    contentWrapper.classList.add('expanded');
                } else {
                    sidebar.classList.remove('collapsed');
                    contentWrapper.classList.remove('expanded');
                }
            }
        }
        
        // Initial check on page load
        checkWidth();
        
        // Check on window resize
        window.addEventListener('resize', checkWidth);
        
        // Toggle sidebar on button click
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    // Mobile behavior - show/hide sidebar
                    sidebar.classList.toggle('show');
                } else {
                    // Desktop behavior - expand/collapse sidebar
                    sidebar.classList.toggle('collapsed');
                    contentWrapper.classList.toggle('expanded');
                     // Save the state to localStorage
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                }
            });
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth < 992 && 
                !sidebar.contains(event.target) && 
                !sidebarToggle.contains(event.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
        
        // Add active class to current page link
        const currentLocation = window.location.pathname;
        const navLinks = document.querySelectorAll('#sidebar .nav-link');
        
        navLinks.forEach(link => {
            const linkPath = link.getAttribute('href');
            if (currentLocation.includes(linkPath) && linkPath !== '#') {
                link.classList.add('active');
            }
        });
    });
</script>


            <!-- Main Page Content Here -->
