<?php 

include 'head.php';
$query = "SELECT * FROM lms_setting LIMIT 1";
$statement = $connect->prepare($query);
$statement->execute();
$row = $statement->fetch(PDO::FETCH_ASSOC);
    if(is_admin_login())
    {

    ?>
    <body class="sb-nav-fixed">

        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-danger">
            <!-- Navbar Brand-->
            <a class="navbar-brand ps-3" href="index.php"><?php echo isset($row['library_name']) ? $row['library_name'] : 'Library Management System'; ?></a>
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
            <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
                
            </form>
            <!-- Navbar-->
            <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="setting.php">Setting</a></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </nav>

        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <a class="nav-link" href="category.php">Category</a>
                            <a class="nav-link" href="author.php">Author</a>
                            <a class="nav-link" href="location_rack.php">Location Rack</a>
                            <a class="nav-link" href="book.php">Book</a>
                            <a class="nav-link" href="user.php">User</a>
                            <a class="nav-link" href="issue_book.php">Issue Book</a>
                            

                        </div>
                    </div>
                    <div class="sb-sidenav-footer">
                       
                    </div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>


        <?php 
        }
        else
        {

        ?>

        <main>
            <header class="pb-3 mb-4 mx-5 border-bottom">
                <div class="row">
                    <div class="col-md-6 d-flex align-items-center">
                        <a href="index.php" class="text-dark text-decoration-none">
                            <span class="fs-4"><?php echo isset($row['library_name']) ? $row['library_name'] : 'Library Management System'; ?></span>
                        </a>
                    </div>
                    <div class="col-md-6 d-flex justify-content-end align-items-center">
                        <?php 

                        if(is_user_login())
                        {
                        ?>
                        <ul class="list-inline mt-4 float-end">
                            <li class="list-inline-item"><?php echo $_SESSION['user_id']; ?></li>
                            <li class="list-inline-item"><a href="issue_book_details.php">Issue Book</a></li>
                            <li class="list-inline-item"><a href="search_book.php">Search Book</a></li>
                            <li class="list-inline-item"><a href="profile.php">Profile</a></li>
                            <li class="list-inline-item"><a href="logout.php">Logout</a></li>
                        </ul>
                        <?php 
                        }

                        ?>
                    </div>
                </div>

            </header>
        <?php 
        }
        ?>
