<?php
//user.php

include '../database_connection.php';
include '../function.php';
include '../header.php';
authenticate_admin();
$message = ''; // Feedback message

// DELETE (Disable/Enable)
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {
    $user_id = $_GET["code"];
    $status = $_GET["status"];

    $data = array(
        ':user_status' => $status,
        ':user_id'     => $user_id
    );

    $query = "
    UPDATE lms_user
    SET user_status = :user_status 
    WHERE user_id = :user_id";

    $statement = $connect->prepare($query);
    $statement->execute($data);

    header('location:user.php?msg=' . strtolower($status) . '');
    exit;
}

// ADD user (Form Submit)
if (isset($_POST['add_user'])) {
    $name = $_POST['user_name'];
    $email = $_POST['user_email'];
    $password = $_POST['user_password'];
    $unique_id = $_POST['user_unique_id'];
    $contact = $_POST['user_contact_no'];
    $status = $_POST['user_status'];
    $role_id = $_POST['role_id'];
    
    // Ensure unique_id prefix matches role_id
    $prefix = ($role_id == '3') ? 'F' : 'S';
    if (substr($unique_id, 0, 1) !== $prefix) {
        $unique_id = $prefix . substr($unique_id, 1);
    }
    
    // Default image filename
    $profile_image = ''; // Empty by default
    
    // Check if image is uploaded
    if(isset($_FILES['user_profile']) && $_FILES['user_profile']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png');
        $filename = $_FILES['user_profile']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($ext), $allowed)) {
            // Create unique filename
            $new_filename = 'user_' . time() . '.' . $ext;
            $upload_dir = '../upload/';
            
            // Create directory if it doesn't exist
            if(!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $upload_path = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['user_profile']['tmp_name'], $upload_path)) {
                $profile_image = $new_filename; // Save only the filename
            }
        }
    }

    $date_now = get_date_time($connect);

    // First check if the provided unique_id already exists
    $check_query = "SELECT COUNT(*) as count FROM lms_user WHERE user_unique_id = :unique_id";
    $check_statement = $connect->prepare($check_query);
    $check_statement->execute([':unique_id' => $unique_id]);
    $result = $check_statement->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        // Generate a new unique ID with the correct prefix
        $new_unique_id = $prefix . generateRandomDigits();
        $unique_id = $new_unique_id;
    }
    
    // Always use the unique_id parameter in the query
    $query = "
        INSERT INTO lms_user 
        (user_name, user_email, user_password, user_unique_id, user_contact_no, user_status, role_id, user_profile, user_created_on, user_updated_on) 
        VALUES (:name, :email, :password, :unique_id, :contact, :status, :role_id, :profile_image, :created_on, :updated_on)
    ";
    
    $statement = $connect->prepare($query);
    $statement->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':unique_id' => $unique_id,
        ':contact' => $contact,
        ':status' => $status,
        ':role_id' => $role_id,
        ':profile_image' => $profile_image,
        ':created_on' => $date_now,
        ':updated_on' => $date_now
    ]);

    header('location:user.php?msg=add');
    exit;
}

// EDIT user (Form Submit)
if (isset($_POST['edit_user'])) {
    $id = $_POST['user_id'];
    $name = $_POST['user_name'];
    $email = $_POST['user_email'];
    $password = $_POST['user_password'];
    $unique_id = $_POST['user_unique_id'];
    $contact = $_POST['user_contact_no'];
    $status = $_POST['user_status'];
    $role_id = $_POST['role_id'];
    
    // Ensure unique_id prefix matches role_id
    $prefix = ($role_id == '3') ? 'F' : 'S';
    if (substr($unique_id, 0, 1) !== $prefix) {
        $unique_id = $prefix . substr($unique_id, 1);
    }
    
    // Get current profile image
    $query = "SELECT user_profile FROM lms_user WHERE user_id = :id LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->execute([':id' => $id]);
    $user_data = $statement->fetch(PDO::FETCH_ASSOC);
    $profile_image = $user_data['user_profile'];
    
    // Check if new image is uploaded
    if(isset($_FILES['user_profile']) && $_FILES['user_profile']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png');
        $filename = $_FILES['user_profile']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($ext), $allowed)) {
            // Create unique filename
            $new_filename = 'user_' . time() . '.' . $ext;
            $upload_dir = '../upload/';
            
            // Create directory if it doesn't exist
            if(!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $upload_path = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['user_profile']['tmp_name'], $upload_path)) {
                // If this isn't the default image and exists, delete the old one
                if(!empty($profile_image) && file_exists($upload_dir . $profile_image)) {
                    unlink($upload_dir . $profile_image);
                }
                $profile_image = $new_filename; // Save only the filename
            }
        }
    }

    // Start building the update query
    $update_query = "
        UPDATE lms_user 
        SET user_name = :name, 
            user_email = :email, 
            user_unique_id = :unique_id,
            user_contact_no = :contact, 
            user_status = :status,
            role_id = :role_id,
            user_profile = :profile_image,
            user_updated_on = :updated_on";

    if (!empty($password)) {
        $update_query .= ", user_password = :password";
    }

    $update_query .= " WHERE user_id = :id";

    $date_now = get_date_time($connect);
    
    $params = [
        ':name' => $name,
        ':email' => $email,
        ':unique_id' => $unique_id,
        ':contact' => $contact,
        ':status' => $status,
        ':role_id' => $role_id,
        ':profile_image' => $profile_image,
        ':updated_on' => $date_now,
        ':id' => $id
    ];

    if (!empty($password)) {
        $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $statement = $connect->prepare($update_query);
    $statement->execute($params);

    header('location:user.php?msg=edit');
    exit;
}

// SELECT User with role name
$query = "
    SELECT u.*, r.role_name 
    FROM lms_user u 
    LEFT JOIN user_roles r ON u.role_id = r.role_id 
    ORDER BY u.user_id DESC";
$statement = $connect->prepare($query);
$statement->execute();
$user = $statement->fetchAll(PDO::FETCH_ASSOC);


?>

<div class="container-fluid px-4">
<div class="d-flex justify-content-between align-items-center my-4">
        <h1 class="">User Management</h1>
    </div>

    <?php if (isset($_GET["msg"])): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_GET["msg"]) && $_GET["msg"] == 'disable'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'User Disabled',
                    text: 'The user has been successfully disabled.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'enable'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'User Enabled',
                    text: 'The user has been successfully enabled.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'add'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'User Added',
                    text: 'The user was added successfully!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'edit'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'User Updated',
                    text: 'The user was updated successfully!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php endif; ?>

            // Remove ?msg=... from the URL without reloading the page
            if (window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('msg');
                window.history.replaceState({}, document.title, url.pathname + url.search);
            }
        });
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
        <!-- Add User Form -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Add User</h5>
                <a href="user.php" class="btn btn-secondary btn-sm">Back to List</a>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="row mb-4">
                        <div class="col-md-3 text-center mb-4">
                            <div class="mb-3">
                                <img id="profile_preview" src="../asset/img/user.jpg" alt="Profile Image" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                            </div>
                            <div class="mb-3">
                                <label for="user_profile" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="user_profile" name="user_profile" accept="image/jpeg, image/png" onchange="previewImage(this)">
                                <div class="form-text">Select JPG, JPEG or PNG file</div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="user_name" class="form-label">Full Name</label>
                                    <input type="text" id="user_name" name="user_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="user_email" class="form-label">Email Address</label>
                                    <input type="email" id="user_email" name="user_email" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="user_password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <input type="password" id="user_password" name="user_password" class="form-control" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
								<div class="col-md-6">
									<label for="user_unique_id" class="form-label">User Unique ID</label>
									<div class="input-group">
										<span class="input-group-text bg-light" id="id_prefix">S</span>
										<input type="text" id="user_unique_id" name="user_unique_id" class="form-control" value="S<?= generateRandomDigits() ?>" required readonly>
										<button class="btn btn-outline-secondary" type="button" id="regenerateId">
											<i class="fas fa-sync-alt"></i>
										</button>
									</div>
									<div class="form-text">Auto-generated based on user type. ID will be finalized on submission.</div>
								</div>
                                <div class="col-md-6">
                                    <label for="user_contact_no" class="form-label">Contact Number</label>
                                    <input type="text" id="user_contact_no" name="user_contact_no" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="user_status" class="form-label">Status</label>
                                    <select id="user_status" name="user_status" class="form-select">
                                        <option value="Enable">Active</option>
                                        <option value="Disable">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
									<label for="role_id" class="form-label">User Type</label>
									<select id="role_id" name="role_id" class="form-select" required>
										<option value="4">Student</option>
										<option value="3">Faculty</option>
									</select>
								</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 text-end">
                            <button type="submit" name="add_user" class="btn btn-success">Add User</button>
                            <a href="user.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])): ?>

        <?php
        $id = $_GET['code'];
        $query = "
            SELECT u.*, r.role_name 
            FROM lms_user u 
            LEFT JOIN user_roles r ON u.role_id = r.role_id 
            WHERE u.user_id = :id LIMIT 1";
        $statement = $connect->prepare($query);
        $statement->execute([':id' => $id]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        ?>

        <!-- Edit User Form -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Edit User</h5>
                <a href="user.php" class="btn btn-secondary btn-sm">Back to List</a>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                    <div class="row mb-4">
                        <div class="col-md-3 text-center mb-4">
                            <div class="mb-3">
                                <img id="profile_preview" src="<?= !empty($user['user_profile']) ? '../upload/'. $user['user_profile'] : '../asset/img/user.jpg' ?>" alt="Profile Image" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                            </div>
                            <div class="mb-3">
                                <label for="user_profile" class="form-label">Profile Image</label>
                                <input type="file" class="form-control" id="user_profile" name="user_profile" accept="image/jpeg, image/png" onchange="previewImage(this)">
                                <div class="form-text">Select JPG, JPEG or PNG file</div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="user_name" class="form-label">Full Name</label>
                                    <input type="text" id="user_name" name="user_name" class="form-control" value="<?= $user['user_name'] ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="user_email" class="form-label">Email Address</label>
                                    <input type="email" id="user_email" name="user_email" class="form-control" value="<?= $user['user_email'] ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="user_password" class="form-label">New Password (Leave blank to keep current)</label>
                                    <div class="input-group">
                                        <input type="password" id="user_password" name="user_password" class="form-control">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </div>                                
								<div class="col-md-6">
									<label for="user_unique_id" class="form-label">User Unique ID</label>
									<div class="input-group">
										<span class="input-group-text bg-light" id="id_prefix"><?= substr($user['user_unique_id'], 0, 1) ?></span>
										<input type="text" id="user_unique_id" name="user_unique_id" class="form-control" value="<?= $user['user_unique_id'] ?>" readonly>
									</div>
									<div class="form-text">Auto-generated based on user type</div>
								</div>
                                <div class="col-md-6">
                                    <label for="user_contact_no" class="form-label">Contact Number</label>
                                    <input type="text" id="user_contact_no" name="user_contact_no" class="form-control" value="<?= $user['user_contact_no'] ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="user_status" class="form-label">Status</label>
                                    <select id="user_status" name="user_status" class="form-select">
                                        <option value="Enable" <?= $user['user_status'] == 'Enable' ? 'selected' : '' ?>>Active</option>
                                        <option value="Disable" <?= $user['user_status'] == 'Disable' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
									<label for="role_id" class="form-label">User Type</label>
									<select id="role_id" name="role_id" class="form-select" required>
										<option value="4" <?= $user['role_id'] == '4' ? 'selected' : '' ?>>Student</option>
										<option value="3" <?= $user['role_id'] == '3' ? 'selected' : '' ?>>Faculty</option>
									</select>
								</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 text-end">
                            <button type="submit" name="edit_user" class="btn btn-primary">Update User</button>
                            <a href="user.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])): ?>
        <?php
        $id = $_GET['code'];
        $query = "
            SELECT u.*, r.role_name 
            FROM lms_user u 
            LEFT JOIN user_roles r ON u.role_id = r.role_id 
            WHERE u.user_id = :id LIMIT 1";
        $statement = $connect->prepare($query);
        $statement->execute([':id' => $id]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user): 
        ?>

        <!-- View User Details -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">View User</h5>
                <a href="user.php" class="btn btn-secondary btn-sm">Back to List</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center mb-4">
                        <img src="<?= !empty($user['user_profile']) ? '../upload/'. htmlspecialchars($user['user_profile']) : '../asset/img/user.jpg' ?>" alt="Profile Image" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    <div class="col-md-9">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p><strong>ID:</strong> <?= htmlspecialchars($user['user_id']) ?></p>
                                <p><strong>Name:</strong> <?= htmlspecialchars($user['user_name']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($user['user_email']) ?></p>
                                <p><strong>User Unique ID:</strong> <?= htmlspecialchars($user['user_unique_id']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Contact:</strong> <?= htmlspecialchars($user['user_contact_no']) ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge <?= $user['user_status'] == 'Enable' ? 'bg-success' : 'bg-danger' ?>">
                                        <?= htmlspecialchars($user['user_status']) ?>
                                    </span>
                                </p>
                                <p><strong>User Type:</strong> <?= htmlspecialchars($user['role_name']) ?></p>
                                <p><strong>Created On:</strong> <?= date('M d, Y h:i A', strtotime($user['user_created_on'])) ?></p>
                                <p><strong>Updated On:</strong> <?= date('M d, Y h:i A', strtotime($user['user_updated_on'])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php 
        else: 
        ?>
        <p class="alert alert-danger">User not found.</p>
        <a href="user.php" class="btn btn-secondary">Back</a>
        <?php 
        endif;
        // END VIEW User

        else: ?>

        <!-- User List -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>User List</h5>                      
                    <?php if (!isset($_GET['action'])): ?>            
                        <a href="user.php?action=add" class="btn btn-sm btn-success">
                            <i class="fas fa-plus-circle me-2"></i>Add User
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <table id="dataTable" class="display nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Profile</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>User Unique ID</th>
                            <th>Contact No.</th>
                            <th>User Type</th>
                            <th>Status</th>
                            <th>Created On</th>
                            <th>Updated On</th> 
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($user) > 0): ?>
                        <?php foreach ($user as $row): ?>
                            <tr>
                                <td><?= $row['user_id'] ?></td>
                                <td>
                                    <img src="<?= !empty($row['user_profile']) ? '../upload/'. $row['user_profile'] : '../asset/img/user.jpg' ?>" 
                                         alt="Profile" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                </td>
                                <td><?= $row['user_name'] ?></td>
                                <td><?= $row['user_email'] ?></td>
                                <td><?= $row['user_unique_id'] ?></td>
                                <td><?= $row['user_contact_no'] ?></td>
                                <td><?= $row['role_name'] ?></td>
                                <td>
                                    <?= ($row['user_status'] === 'Enable') 
                                        ? '<span class="badge bg-success">Active</span>' 
                                        : '<span class="badge bg-danger">Disabled</span>' ?>
                                </td>
                                <td><?= date('M d, Y H:i:s', strtotime($row['user_created_on'])) ?></td>
                                <td><?= date('M d, Y H:i:s', strtotime($row['user_updated_on'])) ?></td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="user.php?action=view&code=<?= $row['user_id'] ?>" class="btn btn-info btn-sm">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="user.php?action=edit&code=<?= $row['user_id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm delete-btn" 
                                                data-id="<?= $row['user_id'] ?>" 
                                                data-status="<?= $row['user_status'] ?>">
                                            <i class="fa fa-<?= $row['user_status'] === 'Enable' ? 'ban' : 'check' ?>"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="11" class="text-center">No Data Found</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get elements
    const roleSelect = document.getElementById('role_id');
    const idPrefix = document.getElementById('id_prefix');
    const regenerateBtn = document.getElementById('regenerateId');
    const uniqueIdField = document.getElementById('user_unique_id');
    
    // Function to generate random 6-digit number
    function generateRandomDigits() {
        return Math.floor(100000 + Math.random() * 900000).toString();
    }
    
    // Function to update the complete unique ID
    function updateUniqueId() {
        let prefix;
        
        // Set prefix based on role
        switch(roleSelect.value) {
            case '3':
                prefix = 'F';
                break;
            case '4':
                prefix = 'S';
                break;
            default:
                prefix = 'U';
        }
        
        // Update visible prefix
        if (idPrefix) {
            idPrefix.textContent = prefix;
        }
        
        // Get current digits (or generate new ones)
        let currentValue = uniqueIdField.value;
        let digits;
        
        if (currentValue && currentValue.length > 1) {
            // Extract existing digits if they exist
            digits = currentValue.substring(1);
        } else {
            // Generate new digits
            digits = generateRandomDigits();
        }
        
        // Set the complete unique ID
        uniqueIdField.value = prefix + digits;
    }
    
    // Initial update
    if (roleSelect && uniqueIdField) {
        updateUniqueId();
    }
    
    // Update prefix and ID when role changes
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            updateUniqueId();
        });
    }
    
    // Regenerate button click handler
    if (regenerateBtn) {
        regenerateBtn.addEventListener('click', function() {
            const prefix = idPrefix.textContent;
            const newDigits = generateRandomDigits();
            uniqueIdField.value = prefix + newDigits;
        });
    }
    
    // Password toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('user_password');
    
    if (togglePassword && passwordField) {
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }

    // Profile image preview functionality
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profile_preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // DataTable initialization
    const table = $('#dataTable').DataTable({
        responsive: true,
		scrollX: true,
        scrollY: '500px',       // Optional: Sets vertical scroll area (adjust height as needed)
        scrollCollapse: true,   // Collapse table height if fewer records
        autoWidth: false,
        info: true,
        paging: true,  
		columnDefs: [
            { responsivePriority: 1, targets: [0, 2, 10] },
            { responsivePriority: 2, targets: [1, 3] }
        ],     
        order: [[0, 'desc']],
		language: {
            emptyTable: "No data available"
        },
		fixedHeader: true,
        stateSave: true,
        // Fix alignment issues on draw and responsive changes
        drawCallback: function() {
            setTimeout(() => table.columns.adjust().responsive.recalc(), 100);
        }
    });
	// Handle window resize to maintain column alignment
    $(window).on('resize', function() {
        table.columns.adjust().responsive.recalc();
    });
    
    // Force alignment after a short delay to ensure proper rendering
    setTimeout(() => table.columns.adjust().responsive.recalc(), 300);
    
    // Delete/Enable/Disable functionality
    $('.delete-btn').on('click', function() {
        const userId = $(this).data('id');
        const currentStatus = $(this).data('status');
        const newStatus = currentStatus === 'Enable' ? 'Disable' : 'Enable';
        const actionText = currentStatus === 'Enable' ? 'disable' : 'enable';
        
        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to ${actionText} this user?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `user.php?action=delete&code=${userId}&status=${newStatus}`;
            }
        });
    });
});

// Function to generate random 6-digit number for use outside event listeners
function generateRandomDigits() {
    return Math.floor(100000 + Math.random() * 900000).toString();
}
</script>

<?php
// Include footer
include '../footer.php';
?>