<?php
//profile.php

include '../database_connection.php';
include '../function.php';
include '../header.php';
authenticate_admin();
$message = '';
$error = '';
$role_id = $_SESSION['role_id'] ?? 0;
$user_unique_id = $_SESSION['user_unique_id'] ?? '';

// Process form submission
if (isset($_POST['edit_profile'])) {
    $formdata = array();

    // Validate email
    if (empty($_POST['email'])) {
        $error .= '<li>Email Address is required</li>';
    } else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $error .= '<li>Invalid Email Address</li>';
    } else {
        $formdata['email'] = $_POST['email'];
    }

    // Validate password
    if (!empty($_POST['password'])) {
        if (strlen($_POST['password']) < 6) {
            $error .= '<li>Password must be at least 6 characters</li>';
        } else {
            $formdata['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $password_updated = true;
        }
    }

    // Validate name for non-admin users
    if (isset($_POST['name']) && $role_id != 1) {
        if (empty($_POST['name'])) {
            $error .= '<li>Full Name is required</li>';
        } else {
            $formdata['name'] = $_POST['name'];
        }
    }

    // Validate address for non-admin users
    if (isset($_POST['address']) && $role_id != 1) {
        if (empty($_POST['address'])) {
            $error .= '<li>Address is required</li>';
        } else {
            $formdata['address'] = $_POST['address'];
        }
    }

    // Validate contact number for non-admin users
    if (isset($_POST['contact_no']) && $role_id != 1) {
        if (empty($_POST['contact_no'])) {
            $error .= '<li>Contact Number is required</li>';
        } else {
            $formdata['contact_no'] = $_POST['contact_no'];
        }
    }

    // Process profile image if uploaded
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_image']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed)) {
            $error .= '<li>Only JPG, JPEG, PNG, and GIF files are allowed</li>';
        } else {
            $new_filename = time() . '-' . rand(1000, 99999) . '.' . $file_ext;
            $upload_path = '../upload/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $formdata['profile_image'] = $new_filename;
            } else {
                $error .= '<li>Failed to upload profile image</li>';
            }
        }
    }

    // Update profile if no errors
    if ($error == '') {
        $data = array();
        $table_name = '';
        $id_column = '';
        $unique_id_column = '';
        $email_column = '';
        $password_column = '';

        // Set appropriate table and columns based on role
        switch ($role_id) {
            case 1: // Admin
                $table_name = 'lms_admin';
                $unique_id_column = 'admin_unique_id';
                $email_column = 'admin_email';
                $password_column = 'admin_password';
                $profile_column = 'admin_profile';
                break;
            case 2: // Librarian
                $table_name = 'lms_librarian';
                $unique_id_column = 'librarian_unique_id';
                $email_column = 'librarian_email';
                $password_column = 'librarian_password';
                $profile_column = 'librarian_profile';
                $name_column = 'librarian_name';
                $address_column = 'librarian_address';
                $contact_column = 'librarian_contact_no';
                break;
            default: // User (Faculty, Student, Visitor)
                $table_name = 'lms_user';
                $unique_id_column = 'user_unique_id';
                $email_column = 'user_email';
                $password_column = 'user_password';
                $profile_column = 'user_profile';
                $name_column = 'user_name';
                $address_column = 'user_address';
                $contact_column = 'user_contact_no';
                break;
        }

        // Build query based on form data
        $query = "UPDATE $table_name SET ";
        $query_parts = [];
        
        // Always update email
        $query_parts[] = "$email_column = :email";
        $data[':email'] = $formdata['email'];
        
        // Update password only if provided
        if (isset($formdata['password'])) {
            $query_parts[] = "$password_column = :password";
            $data[':password'] = $formdata['password'];
        }
        
        // Update profile image if uploaded
        if (isset($formdata['profile_image'])) {
            $query_parts[] = "$profile_column = :profile_image";
            $data[':profile_image'] = $formdata['profile_image'];
            
            // Update session profile image
            $_SESSION['profile_img'] = $formdata['profile_image'];
        }
        
        // Update additional fields for non-admin users
        if ($role_id != 1) {
            if (isset($formdata['name'])) {
                $query_parts[] = "$name_column = :name";
                $data[':name'] = $formdata['name'];
                
                // Update session user name
                $user_name = explode('@', $formdata['email'])[0]; // Extract username from email
                $_SESSION['user_name'] = $formdata['name'];
            }
            
            if (isset($formdata['address'])) {
                $query_parts[] = "$address_column = :address";
                $data[':address'] = $formdata['address'];
            }
            
            if (isset($formdata['contact_no'])) {
                $query_parts[] = "$contact_column = :contact_no";
                $data[':contact_no'] = $formdata['contact_no'];
            }
        }
        
        // Add timestamp for update
        if ($role_id == 2) {
            $query_parts[] = "lib_updated_on = :updated_on";
        } else if ($role_id > 2) {
            $query_parts[] = "user_updated_on = :updated_on";
        }
        
        if ($role_id >= 2) {
            $data[':updated_on'] = date('Y-m-d H:i:s');
        }
        
        // Complete the query
        $query .= implode(", ", $query_parts);
        $query .= " WHERE $unique_id_column = :unique_id";
        $data[':unique_id'] = $user_unique_id;

        // Execute query
        $statement = $connect->prepare($query);
        $statement->execute($data);

        // Update session email
        $_SESSION['email'] = $formdata['email'];
        
        $message = 'Profile Updated Successfully';
    }
}

// Fetch user data based on role
$query = "";
switch ($role_id) {
    case 1: // Admin
        $query = "SELECT * FROM lms_admin WHERE admin_unique_id = :unique_id";
        break;
    case 2: // Librarian
        $query = "SELECT * FROM lms_librarian WHERE librarian_unique_id = :unique_id";
        break;
    default: // User (Faculty, Student, Visitor)
        $query = "SELECT * FROM lms_user WHERE user_unique_id = :unique_id";
        break;
}

$statement = $connect->prepare($query);
$statement->execute([':unique_id' => $user_unique_id]);
$user_data = $statement->fetch(PDO::FETCH_ASSOC);

// Get role name from session or database
$role_name = $_SESSION['role_name'] ?? 'User';

// Set field references based on role
if ($role_id == 1) { // Admin
    $email_field = $user_data['admin_email'] ?? $_SESSION['email'] ?? '';
    $profile_image = $user_data['admin_profile'] ?? $_SESSION['profile_img'] ?? '../asset/img/admin.jpg';
    $name_field = 'Administrator';
	$unique_id_field = $user_data['admin_unique_id'] ?? $_SESSION['user_unique_id'] ?? '';
} else if ($role_id == 2) { // Librarian
    $email_field = $user_data['librarian_email'] ?? $_SESSION['email'] ?? '';
    $name_field = $user_data['librarian_name'] ?? $_SESSION['user_name'] ?? '';
    $address_field = $user_data['librarian_address'] ?? '';
    $contact_field = $user_data['librarian_contact_no'] ?? '';
    $profile_image = $user_data['librarian_profile'] ?? $_SESSION['profile_img'] ?? '../asset/img/librarian.jpg';
	$unique_id_field = $user_data['librarian_unique_id'] ?? $_SESSION['user_unique_id'] ?? '';
} else { // User (Faculty, Student, Visitor)
    $email_field = $user_data['user_email'] ?? $_SESSION['email'] ?? '';
    $name_field = $user_data['user_name'] ?? $_SESSION['user_name'] ?? '';
    $address_field = $user_data['user_address'] ?? '';
    $contact_field = $user_data['user_contact_no'] ?? '';
    $profile_image = $user_data['user_profile'] ?? $_SESSION['profile_img'] ?? '../asset/img/user.jpg';
	$unique_id_field = $user_data['user_unique_id'] ?? $_SESSION['user_unique_id'] ?? '';
}
?>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php 
            if ($error != '') {
                echo '<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <ul class="list-unstyled mb-0">' . $error . '</ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
            }

            if ($message != '') {
                echo '<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        ' . $message . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
            }
            ?>
            
            <div class="card profile-card mb-4">
                <div class="card-body p-4">
                    <form method="post" enctype="multipart/form-data">
                        <div class="text-center mb-4">
                            <div class="profile-image-container mb-3">
                                <img src="../upload/<?php echo $profile_image; ?>" class="profile-image" id="profile-preview">
                                <label for="profile_image" class="edit-image-overlay">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <input type="file" name="profile_image" id="profile_image" class="d-none" accept="image/*">
                            </div>
                            
                            <h3 class="mb-1"><?php echo htmlspecialchars($name_field); ?></h3>
                            <div class="role-badge badge-<?php echo strtolower($role_name); ?>">
                                <?php echo $role_name; ?>
                            </div>
							<div class="user-id mt-2">
                                <small class="text-muted">ID: <?php echo htmlspecialchars($unique_id_field); ?></small>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email_field); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                                </div>
                                <div class="form-text">Leave blank to keep your current password</div>
                            </div>
                            
                            <?php if ($role_id != 1) { ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($name_field); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="text" name="contact_no" class="form-control" value="<?php echo htmlspecialchars($contact_field); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($address_field); ?></textarea>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                        
                        <div class="d-grid gap-2 justify-content-center">
                            <button type="submit" name="edit_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center">
                <a href="../<?php echo $role_id == 1 || $role_id == 2 ? 'admin' : ($role_id == 5 ? 'guest' : 'user'); ?>/index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('profile_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profile-preview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include '../footer.php'; ?>