<?php
//admin_about_us.php
include '../database_connection.php';
include '../function.php';

include '../header.php';

// Create tables if they don't exist
$create_about_table = "CREATE TABLE IF NOT EXISTS `lms_about_us` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `history` text NOT NULL,
  `mission` text NOT NULL,
  `vision` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$create_staff_table = "CREATE TABLE IF NOT EXISTS `lms_library_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `profile_img` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `description` text,
  `position_order` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$connect->exec($create_about_table);
$connect->exec($create_staff_table);

// Check if about_us table has data, if not insert defaults
$check_query = "SELECT COUNT(*) FROM lms_about_us";
$check_statement = $connect->prepare($check_query);
$check_statement->execute();
$count = $check_statement->fetchColumn();

if ($count == 0) {
    $default_data = "INSERT INTO `lms_about_us` (`history`, `mission`, `vision`) VALUES 
    (
      '<p>SmartLib is the heart of the Western Mindanao State University - External Studies Unit in Curuan, serving as the intellectual center for our academic community. Since our establishment, we have been committed to providing resources and services that support the university\'s teaching, learning, and research missions.</p><p>Our library has evolved from a small collection of books to a comprehensive resource center equipped with modern technology and a diverse range of materials to meet the needs of our growing student population and faculty.</p>',
      
      '<p>At SmartLib, our mission is to empower students, faculty, and the community through access to information resources, technology, and services that enhance learning, teaching, research, and personal growth. We strive to be innovative, responsive, and user-focused while fostering academic excellence and intellectual discovery.</p>',
      
      '<p>SmartLib aims to be a leading academic library that inspires intellectual curiosity, promotes digital literacy, and serves as a model for innovative library services. We envision a library that adapts to evolving educational needs while preserving our cultural heritage and contributing to the academic success of our community.</p>'
    )";
    $connect->exec($default_data);
}

// Process form submissions
$message = '';
$success = '';

// Update About Us Content
if(isset($_POST['update_about'])) {
    $formdata = array(
        ':history' => $_POST['history'],
        ':mission' => $_POST['mission'],
        ':vision' => $_POST['vision']
    );

    $query = "UPDATE lms_about_us SET 
              history = :history, 
              mission = :mission,
              vision = :vision";
              
    $statement = $connect->prepare($query);
    $statement->execute($formdata);
    
    $success = 'About Us content has been updated successfully';
}

// Add/Edit Staff
if(isset($_POST['save_staff'])) {
    $error = '';
    $formdata = array(
        ':name' => trim($_POST['name']),
        ':position' => trim($_POST['position']),
        ':email' => trim($_POST['email']),
        ':description' => trim($_POST['description']),
        ':position_order' => intval($_POST['position_order'])
    );
    
    // Process profile image upload
    $profile_img = '';
    if($_FILES['profile_img']['name'] != '') {
        $allowed_extensions = array('jpg', 'jpeg', 'png');
        $file_extension = strtolower(pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION));
        
        if(in_array($file_extension, $allowed_extensions)) {
            $new_name = 'staff_' . rand() . '.' . $file_extension;
            $path = 'asset/img/' . $new_name;
            if(move_uploaded_file($_FILES['profile_img']['tmp_name'], $path)) {
                $profile_img = $new_name;
            }
        } else {
            $error = 'Only JPG, JPEG, and PNG files are allowed';
        }
    }
    
    if($error == '') {
        if(isset($_POST['staff_id']) && $_POST['staff_id'] != '') {
            // Update existing staff
            $query = "UPDATE lms_library_staff SET 
                      name = :name,
                      position = :position,
                      email = :email,
                      description = :description,
                      position_order = :position_order";
            
            if($profile_img != '') {
                $query .= ", profile_img = :profile_img";
                $formdata[':profile_img'] = $profile_img;
            }
            
            $query .= " WHERE id = :staff_id";
            $formdata[':staff_id'] = $_POST['staff_id'];
        } else {
            // Add new staff
            $query = "INSERT INTO lms_library_staff 
                      (name, position, email, description, position_order";
            
            if($profile_img != '') {
                $query .= ", profile_img";
            }
            
            $query .= ") VALUES (:name, :position, :email, :description, :position_order";
            
            if($profile_img != '') {
                $query .= ", :profile_img";
                $formdata[':profile_img'] = $profile_img;
            }
            
            $query .= ")";
        }
        
        $statement = $connect->prepare($query);
        $statement->execute($formdata);
        
        $success = 'Staff information has been saved successfully';
        
        // Redirect to avoid form resubmission
        header('Location: admin_about_us.php');
        exit;
    } else {
        $message = $error;
    }
}

// Delete Staff
if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $staff_id = $_GET['id'];
    
    // Check if staff exists
    $check_query = "SELECT profile_img FROM lms_library_staff WHERE id = :staff_id";
    $check_statement = $connect->prepare($check_query);
    $check_statement->bindParam(':staff_id', $staff_id);
    $check_statement->execute();
    $staff = $check_statement->fetch(PDO::FETCH_ASSOC);
    
    if($staff) {
        // Delete profile image if exists
        if(!empty($staff['profile_img'])) {
            $image_path = 'asset/img/' . $staff['profile_img'];
            if(file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Delete staff record
        $delete_query = "DELETE FROM lms_library_staff WHERE id = :staff_id";
        $delete_statement = $connect->prepare($delete_query);
        $delete_statement->bindParam(':staff_id', $staff_id);
        $delete_statement->execute();
        
        $success = 'Staff has been deleted successfully';
    }
}

// Fetch About Us content
$query = "SELECT * FROM lms_about_us LIMIT 1";
$statement = $connect->prepare($query);
$statement->execute();
$about_us = $statement->fetch(PDO::FETCH_ASSOC);

// Fetch staff list
$staff_query = "SELECT * FROM lms_library_staff ORDER BY position_order";
$staff_statement = $connect->prepare($staff_query);
$staff_statement->execute();
$staff_list = $staff_statement->fetchAll(PDO::FETCH_ASSOC);

// Fetch staff details for edit
$edit_staff = null;
if(isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $staff_id = $_GET['id'];
    $edit_query = "SELECT * FROM lms_library_staff WHERE id = :staff_id";
    $edit_statement = $connect->prepare($edit_query);
    $edit_statement->bindParam(':staff_id', $staff_id);
    $edit_statement->execute();
    $edit_staff = $edit_statement->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manage About Us Page</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">About Us Management</li>
    </ol>
    
    <?php
    if($message != '') {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            '.$message.'
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
    
    if($success != '') {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            '.$success.'
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
    ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-file-alt me-1"></i>
            About Us Content
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="mb-3">
                    <label for="history" class="form-label">Our History</label>
                    <textarea class="form-control ckeditor" id="history" name="history" rows="5"><?php echo isset($about_us['history']) ? $about_us['history'] : ''; ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="mission" class="form-label">Our Mission</label>
                    <textarea class="form-control ckeditor" id="mission" name="mission" rows="5"><?php echo isset($about_us['mission']) ? $about_us['mission'] : ''; ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="vision" class="form-label">Our Vision</label>
                    <textarea class="form-control ckeditor" id="vision" name="vision" rows="5"><?php echo isset($about_us['vision']) ? $about_us['vision'] : ''; ?></textarea>
                </div>
                
                <button type="submit" name="update_about" class="btn btn-primary">Update Content</button>
            </form>
        </div>
    </div>
    
    <div class="row">
        <!-- Add/Edit Staff Form -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user-plus me-1"></i>
                    <?php echo $edit_staff ? 'Edit Staff' : 'Add New Staff'; ?>
                </div>
                <div class="card-body">
                    <form method="post" action="" enctype="multipart/form-data">
                        <?php if($edit_staff): ?>
                            <input type="hidden" name="staff_id" value="<?php echo $edit_staff['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required 
                                value="<?php echo $edit_staff ? $edit_staff['name'] : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="position" class="form-label">Position</label>
                            <input type="text" class="form-control" id="position" name="position" required
                                value="<?php echo $edit_staff ? $edit_staff['position'] : ''; ?>">
                        </div>