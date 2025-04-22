<?php
//librarian.php

include '../database_connection.php';
include '../function.php';
include '../header.php';
authenticate_admin();
// Create the uploads directory if it doesn't exist
$uploadDir = '../upload/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$message = ''; // Feedback message
$defaultImage = '../asset/img/librarian.jpg'; // Default image path

// DELETE (Disable/Enable)
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {
    $librarian_id = $_GET["code"];
    $status = $_GET["status"];

    $data = array(
        ':librarian_status' => $status,
        ':librarian_id'     => $librarian_id
    );

    $query = "
    UPDATE lms_librarian 
    SET librarian_status = :librarian_status 
    WHERE librarian_id = :librarian_id";

    $statement = $connect->prepare($query);
    $statement->execute($data);

    header('location:librarian.php?msg=' . strtolower($status) . '');
    exit;
}

// ADD Librarian (Form Submit)
if (isset($_POST['add_librarian'])) {
    $name = $_POST['librarian_name'];
    $email = $_POST['librarian_email'];
    $password = $_POST['librarian_password'];
    $contact = $_POST['librarian_contact_no'];
    $status = $_POST['librarian_status'];
    
    // Handle profile image upload
    $profile_image = 'librarian.jpg';
    
    if(!empty($_FILES['librarian_profile']['name'])) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
		$fileName = 'librarian_' . time() . '.' . $ext;
		$targetFilePath = $uploadDir . $fileName;

        
        // Allow certain file formats
        $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
        if(in_array($ext, $allowTypes)) {
            // Upload file to server
            if(move_uploaded_file($_FILES["librarian_profile"]["tmp_name"], $targetFilePath)){
                $profile_image = $fileName;
            } else {
                $message = "Sorry, there was an error uploading your file.";
            }
        } else {
            $message = 'Sorry, only JPG, JPEG, PNG & GIF files are allowed to upload.';
        }
    }

    $query = "
		INSERT INTO lms_librarian
		(librarian_name, librarian_email, librarian_password, librarian_contact_no, librarian_status, librarian_profile, lib_created_on)
		VALUES (:name, :email, :password, :contact, :status, :profile, :created_on COLLATE utf8mb4_unicode_ci)
	";

    $statement = $connect->prepare($query);
    $statement->execute([ 
        ':name' => $name,
        ':email' => $email,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':contact' => $contact,
        ':status' => $status,
        ':profile' => $profile_image,
        ':created_on' => get_date_time($connect)
    ]);

    header('location:librarian.php?msg=add');
    exit;
}

// EDIT Librarian (Form Submit)
if (isset($_POST['edit_librarian'])) {
    $id = $_POST['librarian_id'];
    $name = $_POST['librarian_name'];
    $email = $_POST['librarian_email'];
    $password = $_POST['librarian_password'];
    $contact = $_POST['librarian_contact_no'];
    $status = $_POST['librarian_status'];
    
    // Get current profile image
    $query = "SELECT librarian_profile FROM lms_librarian WHERE librarian_id = :id LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->execute([':id' => $id]);
    $current_librarian = $statement->fetch(PDO::FETCH_ASSOC);
    $profile_image = $current_librarian['librarian_profile'];
    
    // Handle profile image upload
    if(!empty($_FILES['librarian_profile']['name'])) {
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$fileName = 'librarian_' . time() . '.' . $ext;
		$targetFilePath = $uploadDir . $fileName;

        
        // Allow certain file formats
        $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
        if(in_array($ext, $allowTypes)) {
            // Upload file to server
            if(move_uploaded_file($_FILES["librarian_profile"]["tmp_name"], $targetFilePath)){
                // Remove old file if it's not the default image
                if($profile_image != 'librarian.jpg' && file_exists($uploadDir . $profile_image)) {
					unlink($uploadDir . $profile_image);
				}
                $profile_image = $fileName;
            } else {
                $message = "Sorry, there was an error uploading your file.";
            }
        } else {
            $message = 'Sorry, only JPG, JPEG, PNG & GIF files are allowed to upload.';
        }
    }

    // Optional password update
    $update_query = "
        UPDATE lms_librarian 
        SET librarian_name = :name, 
            librarian_email = :email, 
            librarian_contact_no = :contact, 
            librarian_status = :status,
            librarian_profile = :profile,
            lib_updated_on = :updated_on";

    if (!empty($password)) {
        $update_query .= ", librarian_password = :password";
    }

    $update_query .= " WHERE librarian_id = :id";

    $params = [
        ':name' => $name,
        ':email' => $email,
        ':contact' => $contact,
        ':status' => $status,
        ':profile' => $profile_image,
        ':updated_on' => get_date_time($connect),
        ':id' => $id
    ];

    if (!empty($password)) {
        $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $statement = $connect->prepare($update_query);
    $statement->execute($params);

    header('location:librarian.php?msg=edit');
    exit;
}

// SELECT Librarians
$query = "SELECT * FROM lms_librarian ORDER BY librarian_id DESC";
$statement = $connect->prepare($query);
$statement->execute();
$librarians = $statement->fetchAll(PDO::FETCH_ASSOC);


?>

<div class="container-fluid px-4">
    <h1 class="mt-4 mb-4">Librarian Management</h1>

    <?php if (isset($_GET["msg"])): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_GET["msg"]) && $_GET["msg"] == 'disable'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Librarian Disabled',
                    text: 'The librarian has been successfully disabled.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'enable'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Librarian Enabled',
                    text: 'The librarian has been successfully enabled.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'add'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Librarian Added',
                    text: 'The librarian was added successfully!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'edit'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Librarian Updated',
                    text: 'The librarian was updated successfully!',
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

    <?php if (!empty($message)): ?>
        <div class="alert alert-warning"><?= $message ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
        <!-- Add Librarian Form - Enhanced Design -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-user-plus me-2"></i>Add New Librarian</h5>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="mb-3">
                                <label for="librarian_name" class="form-label fw-bold">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="librarian_name" id="librarian_name" class="form-control" placeholder="Enter full name" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="librarian_email" class="form-label fw-bold">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="librarian_email" id="librarian_email" class="form-control" placeholder="Enter email address" required>
                                </div>
                            </div>

							<div class="mb-3">
								<label for="librarian_password" class="form-label fw-bold">Password</label>
								<div class="input-group">
									<span class="input-group-text"><i class="fas fa-lock"></i></span>
									<input type="password" name="librarian_password" id="librarian_password" class="form-control" placeholder="Enter password" required>
									<button class="btn btn-outline-secondary" type="button" id="togglePassword">
										<i class="fas fa-eye"></i>
									</button>
								</div>
								<div class="form-text">Password must be at least 8 characters long</div>
							</div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="librarian_contact_no" class="form-label fw-bold">Contact Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="text" name="librarian_contact_no" id="librarian_contact_no" class="form-control" placeholder="Enter contact number" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="librarian_status" class="form-label fw-bold">Status</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                                    <select name="librarian_status" id="librarian_status" class="form-select">
                                        <option value="Enable">Active</option>
                                        <option value="Disable">Not Active</option>
                                    </select>
                                </div>
                            </div>                            
                        </div>
						<div class="col-md-4">
							<div class="mb-3">
                                <label for="librarian_profile" class="form-label fw-bold">Profile Photo</label>
								<div class="card">
									<div class="mt-2">
										<div class="profile-preview-container text-center">
											<img id="profile-preview" src="<?= $defaultImage ?>" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
										</div>
									</div>
									<div class="input-group mb-2">
										<span class="input-group-text"><i class="fas fa-image"></i></span>
										<input type="file" name="librarian_profile" id="librarian_profile" class="form-control" accept="image/*">
									</div>									
								</div>
                            </div>
						</div>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" name="add_librarian" class="btn btn-success btn-lg px-4">
                            <i class="fas fa-save me-2"></i>Add Librarian
                        </button>
                        <a href="librarian.php" class="btn btn-secondary btn-lg px-4 ms-2">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])): ?>

        <?php
        $id = $_GET['code'];
        $query = "SELECT * FROM lms_librarian WHERE librarian_id = :id LIMIT 1";
        $statement = $connect->prepare($query);
        $statement->execute([':id' => $id]);
        $librarians = $statement->fetch(PDO::FETCH_ASSOC);
        ?>

        <!-- Edit Librarian Form - Enhanced Design -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-user-edit me-2"></i>Edit Librarian</h5>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="librarian_id" value="<?= $librarians['librarian_id'] ?>">
                    
                    <div class="row">
                        <div class="col-md-5">
                            <div class="mb-3">
                                <label for="librarian_name" class="form-label fw-bold">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="librarian_name" id="librarian_name" class="form-control" value="<?= $librarians['librarian_name'] ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="librarian_email" class="form-label fw-bold">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="librarian_email" id="librarian_email" class="form-control" value="<?= $librarians['librarian_email'] ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="librarian_password" class="form-label fw-bold">New Password (Leave blank to keep current)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="librarian_password" id="librarian_password" class="form-control" placeholder="Enter new password">
									<button class="btn btn-outline-secondary" type="button" id="togglePassword">
										<i class="fas fa-eye"></i>
									</button>
                                </div>
                                <div class="form-text">Password must be at least 8 characters long</div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="librarian_contact_no" class="form-label fw-bold">Contact Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="text" name="librarian_contact_no" id="librarian_contact_no" class="form-control" value="<?= $librarians['librarian_contact_no'] ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="librarian_status" class="form-label fw-bold">Status</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                                    <select name="librarian_status" id="librarian_status" class="form-select">
                                        <option value="Enable" <?= $librarians['librarian_status'] == 'Enable' ? 'selected' : '' ?>>Enable</option>
                                        <option value="Disable" <?= $librarians['librarian_status'] == 'Disable' ? 'selected' : '' ?>>Disable</option>
                                    </select>
                                </div>
                            </div>
                            
                        </div>
                    
						<div class="col-md-4">
							<div class="mb-3">
								<label for="librarian_profile" class="form-label fw-bold">Profile Photo</label>
								<div class="card">
									<div class="mt-2">
										<div class="profile-preview-container text-center">
											<img id="profile-preview" src="<?= !empty($librarians['librarian_profile']) ? '../upload/'. $librarians['librarian_profile'] : $defaultImage ?>" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
										</div>
									</div>								
									<div class="input-group mb-2">
										<span class="input-group-text"><i class="fas fa-image"></i></span>
										<input type="file" name="librarian_profile" id="librarian_profile" class="form-control" accept="image/*">
									</div>
								</div>
							</div>
						</div>
					</div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" name="edit_librarian" class="btn btn-primary btn-lg px-4">
                            <i class="fas fa-save me-2"></i>Update Librarian
                        </button>
                        <a href="librarian.php" class="btn btn-secondary btn-lg px-4 ms-2">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])): ?>
        <?php
        $id = $_GET['code'];
        $query = "SELECT * FROM lms_librarian WHERE librarian_id = :id LIMIT 1";
        $statement = $connect->prepare($query);
        $statement->execute([':id' => $id]);
        $librarian = $statement->fetch(PDO::FETCH_ASSOC);

        if ($librarian): 
        ?>

        <!-- View Librarian Details - Enhanced Grid Layout -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0"><i class="fas fa-user-circle me-2"></i>Librarian Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-4 text-center">
                        <div class="card h-100">
                            <div class="card-body">
                                <img src="<?= !empty($librarian['librarian_profile']) ? '../upload/' . $librarian['librarian_profile'] : $defaultImage ?>" 
                                     class="img-fluid rounded-circle mb-3" 
                                     style="max-width: 200px; max-height: 200px;">
                                <h4 class="text-primary"><?= htmlspecialchars($librarian['librarian_name']) ?></h4>
                                <p class="mb-1">
                                    <?= ($librarian['librarian_status'] === 'Enable') 
                                        ? '<span class="badge bg-success fs-6">Active</span>' 
                                        : '<span class="badge bg-danger fs-6">Disabled</span>' ?>
                                </p>
                                <p class="text-muted mb-0">ID: <?= htmlspecialchars($librarian['librarian_unique_id']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <h6 class="fw-bold text-secondary"><i class="fas fa-envelope me-2"></i>Email</h6>
                                        <p class="fs-5"><?= htmlspecialchars($librarian['librarian_email']) ?></p>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <h6 class="fw-bold text-secondary"><i class="fas fa-phone me-2"></i>Contact</h6>
                                        <p class="fs-5"><?= htmlspecialchars($librarian['librarian_contact_no']) ?></p>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <h6 class="fw-bold text-secondary"><i class="fas fa-calendar-plus me-2"></i>Created On</h6>
                                        <p><?= date('M d, Y h:i A', strtotime($librarian['lib_created_on'])) ?></p>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <h6 class="fw-bold text-secondary"><i class="fas fa-calendar-check me-2"></i>Updated On</h6>
                                        <p><?= date('M d, Y h:i A', strtotime($librarian['lib_updated_on'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    <a href="librarian.php?action=edit&code=<?= $librarian['librarian_id'] ?>" class="btn btn-primary px-4">
                        <i class="fas fa-edit me-2"></i>Edit Profile
                    </a>
                    <a href="librarian.php" class="btn btn-secondary px-4 ms-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                </div>
            </div>
        </div>

        <?php 
        else: 
        ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i> Librarian not found.
            </div>
            <a href="librarian.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        <?php 
        endif;
        // END VIEW LIBRARIAN

	else: ?>

        <!-- Librarian List -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i> Librarian Management</h5>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="librarian.php?action=add" class="btn btn-success">
                            <i class="fas fa-user-plus me-2"></i>Add New Librarian
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body" style="overflow-x: auto;">
                <table id="dataTable" class="display nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Profile</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact No.</th>
                            <th>Status</th>
                            <th>Created On</th>
                            <th>Updated On</th> 
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($librarians) > 0): ?>
                        <?php foreach ($librarians as $row): ?>
                            <tr>
                                <td><?= $row['librarian_id'] ?></td>
                                <td class="text-center">
                                    <img src="<?= !empty($row['librarian_profile']) ? '../upload/' . $row['librarian_profile'] : $defaultImage ?>" 
                                         class="rounded-circle" 
                                         style="width: 40px; height: 40px; object-fit: cover;">
                                </td>
                                <td><?= $row['librarian_name'] ?></td>
                                <td><?= $row['librarian_email'] ?></td>
                                <td><?= $row['librarian_contact_no'] ?></td>
                                <td>
                                    <?= ($row['librarian_status'] === 'Enable') 
                                        ? '<span class="badge bg-success">Active</span>' 
                                        : '<span class="badge bg-danger">Disabled</span>' ?>
                                </td>
                                <td><?= date('M d, Y H:i:s', strtotime($row['lib_created_on'])) ?></td>
                                <td><?= date('M d, Y H:i:s', strtotime($row['lib_updated_on'])) ?></td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="librarian.php?action=view&code=<?= $row['librarian_id'] ?>" class="btn btn-info btn-sm" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="librarian.php?action=edit&code=<?= $row['librarian_id'] ?>" class="btn btn-primary btn-sm" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <?php if ($row['librarian_status'] === 'Enable'): ?>
                                            <button type="button" class="btn btn-danger btn-sm delete-btn" 
                                                  data-id="<?= $row['librarian_id'] ?>" 
                                                  data-status="<?= $row['librarian_status'] ?>" 
                                                  title="Disable">
                                                <i class="fa fa-ban"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-success btn-sm delete-btn" 
                                                  data-id="<?= $row['librarian_id'] ?>" 
                                                  data-status="<?= $row['librarian_status'] ?>" 
                                                  title="Enable">
                                                <i class="fa fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center">No Data Found</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
// For profile image preview
document.addEventListener('DOMContentLoaded', function() {
    const profileInput = document.getElementById('librarian_profile');
    const profilePreview = document.getElementById('profile-preview');
    
    if(profileInput && profilePreview) {
        profileInput.addEventListener('change', function() {
            if(this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePreview.src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // DataTable initialization
    if(document.getElementById('dataTable')) {
        const table = $('#dataTable').DataTable({
            responsive: true,
            columnDefs: [
                { responsivePriority: 1, targets: [0, 2, 8] },
                { responsivePriority: 2, targets: [3, 4] }
            ],
            order: [[0, 'desc']], // Changed to sort by ID descending by default
            autoWidth: false,
            language: {
                emptyTable: "No data available"
            },
            
            // Scroll settings combined here
            scrollY: '500px',     // Vertical scrollbar height
            scrollX: true,        // Enable horizontal scrolling
            scrollCollapse: true, // Collapse the table height when fewer records
            paging: true,          // Enable pagination
			fixedHeader: true,
			stateSave: true,
			// Fix alignment issues on draw and responsive changes
			drawCallback: function() {
				setTimeout(() => table.columns.adjust().responsive.recalc(), 100);
			}
        });
		// Adjust columns on window resize
		$(window).on('resize', function () {
			table.columns.adjust().responsive.recalc();
		});

		// Final adjustment after full load/render
		setTimeout(() => {
			table.columns.adjust().responsive.recalc();
		}, 300);
    }
    
    // For delete/enable/disable buttons
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const librarianId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const action = (currentStatus === 'Enable') ? 'disable' : 'enable';
			
			Swal.fire({
                title: `Are you sure you want to ${action} this librarian?`,
                text: "This action can be reverted later.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: `Yes, ${action} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `librarian.php?action=delete&status=${action === 'disable' ? 'Disable' : 'Enable'}&code=${librarianId}`;
                }
            });
        });
    });
});
document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordInput = document.getElementById('librarian_password');

	// toggle the type attribute
	const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
	passwordInput.setAttribute("type", type);
	
	// toggle the icon
	this.classList.toggle("fa-eye-slash");

});
// Legacy function for backward compatibility
function delete_data(code) {
    Swal.fire({
        title: 'Are you sure?',
        text: "Do you want to change this librarian's status?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, change it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "librarian.php?action=delete&code=" + code + "&status=Disable";
        }
    });
}
</script>

<?php include '../footer.php'; ?>