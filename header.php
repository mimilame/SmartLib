<?php
    //header.php
    session_start();

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

    // Initialize variables for form processing
    $message = '';
    $success = '';

    // Process login form submission
    if(isset($_POST["login_button"])) {
        // Validate email
        $email_validation = validate_email($_POST["email"] ?? '');
        
        if(!$email_validation['valid']) {
            set_flash_message('error', $email_validation['message']);
        }
        else if(empty($_POST['password'])) {
            set_flash_message('error', 'Password is required');
        }
        else {
            // Log the login attempt
            error_log("Login Attempt - Email: " . trim($_POST["email"]));
            
            $result = process_login(
                $connect,
                trim($_POST["email"]),
                trim($_POST['password'])
            );
            
            // Log the login result
            error_log("Login Result: " . ($result['success'] ? 'Success' : 'Failure'));
            error_log("Session Role ID: " . ($_SESSION['role_id'] ?? 'Not Set'));
            
            if($result['success']) {
                // Get the user's role from session and redirect accordingly
                $role_id = $_SESSION['role_id'] ?? null;
                
                // Log the role ID before redirection
                error_log("Redirecting with Role ID: " . $role_id);

                $redirect_url = get_role_landing_page($role_id);
                header("Location: " . $redirect_url);
                exit(); // Always call exit after header redirect
            } else {
                set_flash_message('error', $result['message']);
            }
        }
    }

    // Process user registration form submission (ONLY for Visitors)
    if(isset($_POST["register_button"])) {
        $formdata = array();
        $message = '';
        
        // Validate email
        $email_validation = validate_email($_POST["user_email"] ?? '');
        if(!$email_validation['valid']) {
            $message = $email_validation['message'];
        } else {
            $formdata['user_email'] = trim($_POST['user_email']);
        }
        
        // Validate other required fields
        if(empty($_POST["user_password"])) {
            $message .= 'Password is required';
        } else {
            // Don't trim passwords - could be intentional spaces
            $formdata['user_password'] = $_POST['user_password'];
        }
        
        if(empty($_POST['user_name'])) {
            $message .= 'User Name is required';
        } else {
            $formdata['user_name'] = trim($_POST['user_name']);
        }
        
        if(empty($_POST['user_address'])) {
            $message .= 'User Address is required';
        } else {
            $formdata['user_address'] = trim($_POST['user_address']);
        }
        
        if(empty($_POST['user_contact_no'])) {
            $message .= 'User Contact Number is required';
        } else {
            $formdata['user_contact_no'] = trim($_POST['user_contact_no']);
        }
        
        // Process profile image upload
        $image_result = process_profile_image_upload($_FILES['user_profile']);
        if(!$image_result['success']) {
            $message .= $image_result['message'];
        } else {
            $formdata['user_profile'] = $image_result['file_name'];
        }
        // **Automatically Assign Visitor Role (role_id = 5)**
        $formdata['role_id'] = 5;
        
        // If no validation errors, process registration
        if($message == '') {
            $registration_result = process_registration($connect, $formdata);
            
            if($registration_result['success']) {
                $_SESSION['user_unique_id'] = $registration_result['user_unique_id'];
                $_SESSION['role_id'] = 5; // Set session role as visitor
                // Redirect visitors after registration
                $redirect_url = get_role_landing_page(5);
                header("Location: " . $redirect_url);
                exit(); // Always call exit after header redirect
            } else {
                set_flash_message('error', $registration_result['message']);
            }
        } else {
            set_flash_message('error', $message);
        }
        
        // Redirect to avoid form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Get any flash messages at the beginning of the page
    $error_message = get_flash_message('error');
    $success_message = get_flash_message('success');

    $query = "
    SELECT * FROM lms_book 
    WHERE book_status = 'Enable' 
    ORDER BY book_id DESC
    ";

    $statement = $connect->prepare($query);

    $statement->execute();

?>
<body class="">
    <div class="bg"></div>
    <div class="bg bg2"></div>
    <div class="bg bg3"></div>
    <div class="bg1"></div>
    <div class="bg1 bg2"></div>
    <div class="bg1 bg3"></div>
     <div class="custom-bg">
<?php if ($role_id == 1 || $role_id == 2): ?>
    <!-- Admin & Librarian Header -->
    <?php 
    include 'preloader.php'; 
    ?>
    <div class="d-flex">
        <!-- Sidebar -->
        <div id="sidebar" class="bg-dark text-light">
            <div class="sidebar-header">
                <img src="<?php echo base_url().'asset/img/' . $library_logo;?>" alt="Library Logo" class="sidebar-logo">
                <span class="sidebar-header-text fw-bold"><?php echo $library_name; ?></span>
            </div>
            <nav class="nav flex-column p-2">
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/index.php" data-bs-toggle="tooltip" data-bs-placement="right" 
                data-bs-title="Dashboard">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'category.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/category.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                data-bs-title="Category">
                    <i class="fas fa-folder me-2"></i>
                    <span class="nav-text">Category</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'author.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/author.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                data-bs-title="Author">
                    <i class="fas fa-pen-fancy me-2"></i>
                    <span class="nav-text">Author</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'location_rack.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/location_rack.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                data-bs-title="Location Rack">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    <span class="nav-text">Location Rack</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'book.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/book.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                data-bs-title="Book">
                    <i class="fas fa-book me-2"></i>
                    <span class="nav-text">Book</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'issue_book.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/issue_book.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                data-bs-title="Issue Book">
                    <i class="fas fa-bookmark me-2"></i>
                    <span class="nav-text">Issue Book</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'return_book.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/return_book.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                data-bs-title="Return Book">
                    <i class="fas fa-book-reader me-2"></i>
                    <span class="nav-text">Return/Lost Book</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'fines.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/fines.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                data-bs-title="Fines">
                    <i class="fas fa-dollar-sign me-2"></i>
                    <span class="nav-text">Fines</span>
                </a>
                <?php if ($role_id == 1): ?>
                    <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'librarian.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/librarian.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                    data-bs-title="Librarian">
                        <i class="fas fa-user-tie me-2"></i>
                        <span class="nav-text">Librarian</span>
                    </a>
                    <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'user.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/user.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                    data-bs-title="Users">
                        <i class="fas fa-users me-2"></i>
                        <span class="nav-text">Users</span>
                    </a>
                <?php endif; ?>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'report.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>admin/report.php" data-bs-toggle="tooltip"  data-bs-placement="right" 
                data-bs-title="Reports">
                    <i class="fas fa-chart-bar me-2"></i>
                    <span class="nav-text">Reports</span>
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
                                <span class="d-none d-md-inline-block ms-3">
                                    <?php echo htmlspecialchars($user_name); ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>admin/profile.php"><i class="fas fa-user-edit fa-fw me-2 me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>admin/setting.php"><i class="fas fa-cog fa-fw me-2 me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>admin/logout.php"><i class="fas fa-sign-out-alt fa-fw me-2 me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
            <main class="wrapper" >
<?php elseif ($role_id == 3 || $role_id == 4): ?>
    <!-- Faculty & Student Header -->
    <?php 
    include 'preloader.php'; 
    ?>
    <div class="d-flex">
        <!-- Sidebar -->
        <div id="sidebar" class="bg-dark text-light">
            <div class="sidebar-header">
                <img src="<?php echo base_url().'asset/img/' . $library_logo;?>" alt="Library Logo" class="sidebar-logo">
                <span class="sidebar-header-text fw-bold"><?php echo $library_name; ?></span>
            </div>
            <nav class="nav flex-column p-2">
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>user/index.php" data-bs-toggle="tooltip" data-bs-placement="right" 
                data-bs-title="Home">
                    <i class="fas fa-home me-2"></i>
                    <span class="nav-text">Home</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'books.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>user/books.php" data-bs-toggle="tooltip" data-bs-placement="right" 
                data-bs-title="Books">
                    <i class="fas fa-book me-2"></i>
                    <span class="nav-text">Books</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'author.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>user/author.php" data-bs-toggle="tooltip" data-bs-placement="right" 
                data-bs-title="Authors">
                    <i class="fas fa-user-edit me-2"></i>
                    <span class="nav-text">Authors</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'my_books.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>user/my_books.php" data-bs-toggle="tooltip" data-bs-placement="right" 
                data-bs-title="My Books">
                    <i class="fas fa-bookmark me-2"></i>
                    <span class="nav-text">My Books</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'my_fines.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>user/my_fines.php" data-bs-toggle="tooltip" data-bs-placement="right" 
                data-bs-title="My Fines">
                    <i class="fas fa-dollar-sign me-2"></i>
                    <span class="nav-text">My Fines</span>
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
                                <span class="d-none d-md-inline-block ms-3">
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
            <div class="wrapper mt-5">
<?php elseif ($role_id == 5): ?>
    <!-- Visitor Header -->
    <?php 
    include 'preloader.php'; 
    ?>
    <div class="d-flex">
        <!-- Sidebar -->
        <div id="sidebar" class="bg-dark text-light">
            <div class="sidebar-header">
                <img src="<?php echo base_url().'asset/img/' . $library_logo;?>" alt="Library Logo" class="sidebar-logo">
                <span class="sidebar-header-text fw-bold"><?php echo $library_name; ?></span>
            </div>
            <nav class="nav flex-column p-2">
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>guest/index.php" data-bs-toggle="tooltip" data-bs-placement="right" 
                data-bs-title="Home">
                    <i class="fas fa-home me-2"></i>
                    <span class="nav-text">Home</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'books.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>guest/books.php" data-bs-toggle="tooltip" data-bs-placement="right" 
                data-bs-title="Books">
                    <i class="fas fa-book me-2"></i>
                    <span class="nav-text">Books</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'author.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>guest/author.php" data-bs-toggle="tooltip" data-bs-placement="right" 
                data-bs-title="Authors">
                    <i class="fas fa-user-edit me-2"></i>
                    <span class="nav-text">Authors</span>
                </a>
                <a class="nav-link text-light tooltip-nav <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>" href="<?php echo base_url(); ?>guest/reports.php" data-bs-toggle="tooltip" data-bs-placement="right" 
                data-bs-title="Reports">
                    <i class="fas fa-chart-bar me-2"></i>
                    <span class="nav-text">Reports</span>
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
                                <span class="d-none d-md-inline-block ms-3">
                                    <?php echo htmlspecialchars($user_name); ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>guest/profile.php"><i class="fas fa-user-edit fa-fw me-2"></i>Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo base_url(); ?>guest/logout.php"><i class="fas fa-sign-out-alt fa-fw me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
<?php else: ?>
    <!-- Default (Not Logged In) Header -->
    <?php include 'preloader.php'; ?>
    <div class="d-flex flex-wrap fixed-top align-items-center justify-content-center justify-content-md-between mb-4">
        <header class="header mask d-flex flex-wrap align-items-center justify-content-center justify-content-md-between py-3 mb-4 border-bottom bg-danger w-100">
            <a href="/" class="d-flex align-items-center col-md-3 mb-2 mb-md-0 ms-2 text-light fw-bold text-decoration-none">
                <img src="<?php echo base_url() . 'asset/img/' . $library_logo; ?>" alt="SmartLib" width="32" height="32" class="rounded-circle me-2">
                <?php echo $library_name; ?>
            </a>

            <ul class="nav col-12 col-md-auto mb-2 justify-content-center mb-md-0">
                <li><a href="<?php echo base_url(); ?>index.php" class="nav-link px-2 link-secondary text-light <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">Home</a></li>
                <li><a href="<?php echo base_url(); ?>book.php" class="nav-link px-2 link-dark text-light <?php echo (basename($_SERVER['PHP_SELF']) == 'book.php') ? 'active' : ''; ?>">Books</a></li>
                <li><a href="<?php echo base_url(); ?>author.php" class="nav-link px-2 link-dark text-light <?php echo (basename($_SERVER['PHP_SELF']) == 'author.php') ? 'active' : ''; ?>">Authors</a></li>
                <li><a href="<?php echo base_url(); ?>about_us.php" class="nav-link px-2 link-dark text-light <?php echo (basename($_SERVER['PHP_SELF']) == 'about_us.php') ? 'active' : ''; ?>">About Us</a></li>
            </ul>

            <div class="col-md-3 text-end d-flex justify-content-end">
                <button type="button" class="logbtn btn btn-outline-light bg-light me-3 text-secondary">Login</button>
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
            }, 500); 
        }
    });

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
            }
        }
        
        // Initial check on page load
        checkWidth();
        
        // Check on window resize
        window.addEventListener('resize', checkWidth);
        
        // Load sidebar state from localStorage
        function loadSidebarState() {
            if (window.innerWidth >= 992) { // Only apply saved state on desktop
                const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                
                if (isCollapsed) {
                    sidebar.classList.add('collapsed');
                    contentWrapper.classList.add('expanded');
                } else {
                    sidebar.classList.remove('collapsed');
                    contentWrapper.classList.remove('expanded');
                }
            }
        }
        
        // Save sidebar state to localStorage
        function saveSidebarState() {
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        }
        
        // Load sidebar state on page load
        loadSidebarState();
        
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
                    
                    // Save state after toggling
                    saveSidebarState();
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
<?php
    // Display SweetAlert messages if they exist
    if(!empty($error_message)) {
        echo sweet_alert('error', $error_message);
    }

    if(!empty($success_message)) {
        echo sweet_alert('success', $success_message);
    }
?>
        
<div class="modal-overlay" id="authModal">
    <section class="index">
        <div class="toggle-box">
            <article class="toggle-panel toggle-left">
                <div class="p-3">
                    <div class="mx-5">
                        <h2 class="h3">Explore Our Collection</h2>
                        <p class="mb-5">Browse through hundreds of books, journals, and digital resources in our extensive catalog. From classic literature to the latest research papers, we have resources for every reader.</p>
                        <ul class="row gap-4 justify-content-between mb-5 list-unstyled">
                            <li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
                                <div class="card-body">
                                <div class="d-inline-flex flex-column align-items-center">
                                    <i class="fas fa-book-open text-warning fa-3x me-2"></i>
                                    <span class="mt-2">Over 500 books</span>
                                </div>
                                </div>
                            </li>
                            <li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
                                <div class="card-body">
                                <div class="d-inline-flex flex-column align-items-center">
                                    <i class="fas fa-laptop-code text-warning fa-3x me-2"></i>
                                    <span class="mt-2">Digital resources</span>
                                </div>
                                </div>
                            </li>
                            <li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
                                <div class="card-body">
                                <div class="d-inline-flex flex-column align-items-center">
                                    <i class="fas fa-newspaper text-warning fa-3x me-2"></i>
                                    <span class="mt-2">Academic journals</span>
                                </div>
                                </div>
                            </li>
                            <li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
                                <div class="card-body">
                                <div class="d-inline-flex flex-column align-items-center">
                                    <i class="fas fa-flask text-warning fa-3x me-2"></i>
                                    <span class="mt-2">Research materials</span>
                                </div>
                                </div>
                            </li>
                        </ul>
                        <div class="h5">
                            <p>Don't have an account?
                                <span class="register-btn text-light h6 text-decoration-underline">Register</span>
                            </p>
                        </div>
                    </div>
                </div>
            </article>
            <div class="toggle-panel toggle-right">
                <div class="p-3">
                    <div class="mx-5">
                        <h1 class="h2 mb-5">Register with us to start your library experience!</h1>
                        <ul class="row gap-4 justify-content-between mb-5 list-unstyled">
                            <li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
                                <div class="card-body">
                                    <div class="d-inline-flex flex-column align-items-center">
                                        <i class="fas fa-search text-warning fa-3x me-2"></i>
                                        <span class="mt-2">Search Availability</span>
                                    </div>
                                </div>
                            </li>
                            <li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
                                <div class="card-body">
                                    <div class="d-inline-flex flex-column align-items-center">
                                        <i class="fas fa-tasks text-warning fa-3x me-2"></i>
                                        <span class="mt-2">Manage Issued Books</span>
                                    </div>
                                </div>
                            </li>
                            <li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
                                <div class="card-body">
                                    <div class="d-inline-flex flex-column align-items-center">
                                        <i class="fas fa-bell text-warning fa-3x me-2"></i>
                                        <span class="mt-2">Return Due Reminders</span>
                                    </div>
                                </div>
                            </li>
                            <li class="col-md-5 card mb-4 bg-dark text-light align-items-center">
                                <div class="card-body">
                                    <div class="d-inline-flex flex-column align-items-center">
                                        <i class="fas fa-receipt text-warning fa-3x me-2"></i>
                                        <span class="mt-2">Library Transactions</span>
                                    </div>
                                </div>
                            </li>
                        </ul>

                        <div class="h5">
                            <p>Already have an account? 
                                <span class="login-btn text-light h6 text-decoration-underline">Login</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="form-box login" id="loginForm">
            <header class="w-50 d-inline bg-light z-index-99">
                <div class="mx-5">
                    <nav class="navbar navbar-expand-lg navbar-light">
                        <div class="container-fluid">
                            <a class="navbar-brand" href="index.php">
                                <img src="asset/img/logo.png" height="40" class="me-2">
                                <span class="fw-bold">SmartLib</span>
                            </a>
                        </div>
                    </nav>
                </div>
            </header>
            <form action="#" method="POST" class="my-auto p-3">
                <h1>Login</h1>
                <div class="w-75 mx-5 mx-auto">
                    <div class="input-box mb-3">
                        <input type="text" name="email" placeholder="Email" required>
                        <i class='bx bxs-user'></i>
                    </div>
                    <div class="input-box mb-3">
                        <input type="password" name="password" placeholder="Password" required>
                        <i class='bx bxs-lock-alt' ></i>
                    </div>
                </div>
                <div class="forgot-link">
                    <a href="#">Forgot Password?</a>
                </div>
                <button type="submit" name="login_button" class="btn btn-primary">Sign in</button>
            </form>
        </div>

        <div class="form-box register" id="signupForm">	
            <header class="w-50 d-inline bg-light z-index-99">
                <div class="mx-5">
                    <nav class="navbar navbar-expand-lg navbar-light">
                        <div class="container-fluid">
                            <a class="navbar-brand" href="index.php">
                                <img src="asset/img/logo.png" height="40" class="me-2">
                                <span class="fw-bold">SmartLib</span>
                            </a>
                        </div>
                    </nav>
                </div>
            </header>
            <form action="" method="POST" enctype="multipart/form-data" class="my-auto p-3">

                <h1>Register</h1>
                <div class="w-75 mx-5 mx-auto">
                    <div class="input-box mb-3">
                        <input type="text" class="form-control" name="user_name" id="user_name" placeholder="Full name" required>
                    </div>
                    <div class="input-box mb-3">
                        <input type="email" class="form-control" name="user_email" id="user_email" placeholder="Email" required>
                    </div>
                    <div class="input-box mb-3">
                        <input type="password" class="form-control" name="user_password" id="signup_password" placeholder="Password" required>
                    </div>
                    <div class="input-box mb-3">
                        <input type="text" class="form-control" name="user_contact_no" id="user_contact_no" placeholder="Contact Number" required>
                    </div>
                    <div class="input-box mb-3">
                        <textarea class="form-control" name="user_address" id="user_address" rows="2" placeholder="Address" required></textarea>
                    </div>
                    <div class="input-box mb-3">
                        <input type="file" class="form-control" name="user_profile" id="user_profile" placeholder="Profile Image" required>
                        <small class="text-muted">Only .jpg & .png (height greater than width, max 2MB)</small>
                    </div>
                    
                </div>
                <button type="submit" name="register_button" class="btn btn-primary">Sign Up</button>
            </form>
        </div>
        
    </section>
</div>
<script>
    const index = document.querySelector('.index');
    const toggleBox = document.querySelector('.toggle-box');
    const registerBtn = document.querySelector('.register-btn');
    const loginBtn = document.querySelector('.login-btn');
    const authModal = document.getElementById("authModal");
    const regBtn = document.querySelector(".regbtn");
    const logBtn = document.querySelector(".logbtn");

    // Open modal
    regBtn.addEventListener("click", () => {
        authModal.style.display = "flex";
        document.querySelector(".index").classList.add("active");
    });

    logBtn.addEventListener("click", () => {
        authModal.style.display = "flex";
        document.querySelector(".index").classList.remove("active");
    });

    // Close modal when clicking outside the form
    authModal.addEventListener("click", (e) => {
        if (e.target === authModal) {
            authModal.style.display = "none";
        }
    });


    // Function to handle the transition overlay effect
    function handleTransition() {
    toggleBox.classList.add('transitioning');
    
    setTimeout(function() {
        toggleBox.classList.remove('transitioning');
    }, 1800);
    }

    // Register button click
    registerBtn.addEventListener('click', () => {
    handleTransition();
    index.classList.add('active');
    });

    // Login button click
    loginBtn.addEventListener('click', () => {
    handleTransition();
    index.classList.remove('active');
    });

</script>
            <!-- Main Page Content Here -->
