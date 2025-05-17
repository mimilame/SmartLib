<?php
//profile.php

include '../database_connection.php';
include '../function.php';
include '../header.php';
authenticate_user();

$message = '';
$error = '';
$role_id = $_SESSION['role_id'] ?? 0;
$user_unique_id = $_SESSION['user_unique_id'] ?? '';


// Get user details
$user_data = get_complete_user_details($user_unique_id, $connect);

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
        }
    }

    // Validate name for non-admin users
    if (isset($_POST['name']) && $user_data['role_id'] != 1) {
        if (empty($_POST['name'])) {
            $error .= '<li>Full Name is required</li>';
        } else {
            $formdata['name'] = $_POST['name'];
        }
    }

    // Validate address for non-admin users
    if (isset($_POST['address']) && $user_data['role_id'] != 1) {
        if (empty($_POST['address'])) {
            $error .= '<li>Address is required</li>';
        } else {
            $formdata['address'] = $_POST['address'];
        }
    }

    // Validate contact number for non-admin users
    if (isset($_POST['contact_no']) && $user_data['role_id'] != 1) {
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
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if (!in_array($ext, $allowed)) {
            $error .= '<li>Only JPG, JPEG, PNG, and GIF files are allowed</li>';
        } else {
            $new_filename = 'admin_' . time() . '.' . $ext;
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
        $table_prefix = $user_data['table_prefix'];
        
        // Define table and column mappings based on role
        $table_mapping = [
            'admin' => [
                'table' => 'lms_admin',
                'unique_id' => 'admin_unique_id',
                'email' => 'admin_email',
                'password' => 'admin_password',
                'profile' => 'admin_profile',
                'updated_on' => null,
            ],
            'librarian' => [
                'table' => 'lms_librarian',
                'unique_id' => 'librarian_unique_id',
                'email' => 'librarian_email',
                'password' => 'librarian_password',
                'profile' => 'librarian_profile',
                'name' => 'librarian_name',
                'address' => 'librarian_address',
                'contact' => 'librarian_contact_no',
                'updated_on' => 'lib_updated_on',
            ],
            'user' => [
                'table' => 'lms_user',
                'unique_id' => 'user_unique_id',
                'email' => 'user_email',
                'password' => 'user_password',
                'profile' => 'user_profile',
                'name' => 'user_name',
                'address' => 'user_address',
                'contact' => 'user_contact_no',
                'updated_on' => 'user_updated_on',
            ]
        ];
        
        $table_config = $table_mapping[$table_prefix];
        
        // Build query based on form data
        $query = "UPDATE {$table_config['table']} SET ";
        $query_parts = [];
        
        // Always update email
        $query_parts[] = "{$table_config['email']} = :email";
        $data[':email'] = $formdata['email'];
        
        // Update password only if provided
        if (isset($formdata['password'])) {
            $query_parts[] = "{$table_config['password']} = :password";
            $data[':password'] = $formdata['password'];
        }
        
        // Update profile image if uploaded
        if (isset($formdata['profile_image'])) {
            $query_parts[] = "{$table_config['profile']} = :profile_image";
            $data[':profile_image'] = $formdata['profile_image'];
            
            // Update session profile image
            $_SESSION['profile_img'] = $formdata['profile_image'];
        }
        
        // Update additional fields for non-admin users
        if ($user_data['role_id'] != 1) {
            if (isset($formdata['name'])) {
                $query_parts[] = "{$table_config['name']} = :name";
                $data[':name'] = $formdata['name'];
                
                // Update session user name
                $_SESSION['user_name'] = $formdata['name'];
            }
            
            if (isset($formdata['address'])) {
                $query_parts[] = "{$table_config['address']} = :address";
                $data[':address'] = $formdata['address'];
            }
            
            if (isset($formdata['contact_no'])) {
                $query_parts[] = "{$table_config['contact']} = :contact_no";
                $data[':contact_no'] = $formdata['contact_no'];
            }
        }
        
        // Add timestamp for update
        if ($table_config['updated_on'] !== null) {
            $query_parts[] = "{$table_config['updated_on']} = :updated_on";
            $data[':updated_on'] = date('Y-m-d H:i:s');
        }
        
        // Complete the query
        $query .= implode(", ", $query_parts);
        $query .= " WHERE {$table_config['unique_id']} = :unique_id";
        $data[':unique_id'] = $user_unique_id;

        // Execute query
        $statement = $connect->prepare($query);
        $statement->execute($data);

        // Update session email
        $_SESSION['email'] = $formdata['email'];
        
        $message = 'Profile Updated Successfully';
        
        // Refresh user data
        $user_data = get_complete_user_details($user_unique_id, $connect);
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <?php
            if ($error != '' || $message != '') {
                echo '<script>';
                
                if ($error != '') {
                    echo 'document.addEventListener("DOMContentLoaded", function() {
                        Swal.fire({
                            title: "Error",
                            html: "<ul class=\"list-unstyled mb-0\">' . $error . '</ul>",
                            icon: "error",
                            confirmButtonColor: "#d33",
                            confirmButtonText: "OK"
                        });
                    });';
                }
                
                if ($message != '') {
                    echo 'document.addEventListener("DOMContentLoaded", function() {
                        Swal.fire({
                            title: "Success",
                            text: "' . $message . '",
                            icon: "success",
                            confirmButtonColor: "#3085d6",
                            confirmButtonText: "OK"
                        });
                    });';
                }
                
                echo '</script>';
            }
        ?>
        
        <div class="card profile-card mb-4">
            <div class="card-body p-4">   
                <div class="d-flex justify-content-end">
                    <?php
                    $dashboard_path = '../';
                    switch ($user_data['role_id']) {
                        case 1:
                        case 2:
                            $dashboard_path .= 'admin';
                            break;
                        case 5:
                            $dashboard_path .= 'guest';
                            break;
                        default:
                            $dashboard_path .= 'user';
                            break;
                    }
                    $dashboard_path .= '/index.php';
                    ?>
                    <a href="<?php echo $dashboard_path; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                </div>
                <form method="post" enctype="multipart/form-data">
                        
                    <div class="row">
                        <div class="col-md-5 text-center mb-4">
                            <div class="profile-image-container mb-3">
                                <img src="../upload/<?php echo htmlspecialchars($user_data['profile_image']); ?>" class="profile-image" id="profile-preview">
                                <label for="profile_image" class="edit-image-overlay">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <input type="file" name="profile_image" id="profile_image" class="d-none" accept="image/*">
                            </div>
                            
                            <h3 class="mb-1"><?php echo htmlspecialchars($user_data['name']); ?></h3>
                            <div class="role-badge badge-<?php echo strtolower($user_data['role_name']); ?>">
                                <?php echo htmlspecialchars($user_data['role_name']); ?>
                            </div>
                            <div class="user-id mt-2">
                                <small class="text-muted">ID: <?php echo htmlspecialchars($user_data['unique_id']); ?></small>
                            </div>
                            <?php if (isset($user_data['created_on'])): ?>
                            <div class="user-info mt-2">
                                <small class="text-muted">Member since: <?php echo date('M d, Y', strtotime($user_data['created_on'])); ?></small>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-7">
                            <div class="row mb-4">
                                <div class="col-md-9 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-9 mb-3">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" id="toggle-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Leave blank to keep your current password</div>
                                </div>
                                
                                <?php if ($user_data['role_id'] != 1) { ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="text" name="contact_no" class="form-control" value="<?php echo htmlspecialchars($user_data['contact_no']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($user_data['address']); ?></textarea>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="d-grid gap-2 justify-content-center">
                            <button type="submit" name="edit_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Update Profile
                            </button>
                        </div>
                            
                    </div>
                </form>
            </div>
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

// Toggle password visibility
document.getElementById('toggle-password').addEventListener('click', function() {
    const passwordField = document.querySelector('input[name="password"]');
    const icon = this.querySelector('i');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>


<?php include '../footer.php'; ?>