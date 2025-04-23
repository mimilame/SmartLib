<?php
//author.php

include '../database_connection.php';
include '../function.php';
include '../header.php';
authenticate_admin();
$message = ''; // Feedback message

// DELETE (Disable/Enable)
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {
    $author_id = $_GET["code"];
    $status = $_GET["status"];

    $data = array(
        ':author_status' => $status,
        ':author_id'     => $author_id
    );

    $query = "
    UPDATE lms_author 
    SET author_status = :author_status 
    WHERE author_id = :author_id";

    $statement = $connect->prepare($query);
    $statement->execute($data);

    header('location:author.php?msg=' . strtolower($status) . '');
    exit;
}

// ADD author (Form Submit)
if (isset($_POST['add_author'])) {
    $name = trim($_POST['author_name']); // Clean the input
    $status = $_POST['author_status'];
    $biography = isset($_POST['author_biography']) ? trim($_POST['author_biography']) : '';
    
    // Default profile image
    $profile_image = 'author.jpg';
    
    // Check if profile image was uploaded
    if(isset($_FILES['author_profile']) && $_FILES['author_profile']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        $filename = $_FILES['author_profile']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($ext), $allowed)) {
            // Generate unique filename
            $new_filename = 'author_' . time() . '.' . $ext;
            $upload_path = '../upload/' . $new_filename;
            
            // Create directory if it doesn't exist
            if(!is_dir('../upload/')) {
                mkdir('../upload/', 0777, true);
            }
            
            // Move uploaded file
            if(move_uploaded_file($_FILES['author_profile']['tmp_name'], $upload_path)) {
                $profile_image = $new_filename;
            }
        }
    }

    // Check for duplicate (case-insensitive comparison)
    $check_query = "
        SELECT COUNT(*) 
        FROM lms_author 
        WHERE LOWER(author_name) = LOWER(:name)
    ";

    $statement = $connect->prepare($check_query);
    $statement->execute([':name' => $name]);
    $count = $statement->fetchColumn();

    if ($count > 0) {
        // author already exists
        header('location:author.php?action=add&error=exists');
        exit;
    }

    // If not existing, insert new author
    $date_now = get_date_time($connect); // Get the current date once

    $query = "
        INSERT INTO lms_author 
        (author_name, author_status, author_profile, author_biography, author_created_on, author_updated_on) 
        VALUES (:name, :status, :profile, :biography, :created_on, :updated_on)
    ";
    
    $statement = $connect->prepare($query);
    $statement->execute([
        ':name' => $name,
        ':status' => $status,
        ':profile' => $profile_image,
        ':biography' => $biography,
        ':created_on' => $date_now,   // Same date for both created and updated
        ':updated_on' => $date_now
    ]);
    
    header('location:author.php?msg=add');
    exit;
}

// EDIT author (Form Submit)
if (isset($_POST['edit_author'])) {
    $id = $_POST['author_id'];
    $name = $_POST['author_name'];
    $status = $_POST['author_status'];
    $biography = isset($_POST['author_biography']) ? trim($_POST['author_biography']) : '';
    
    // Get current profile image
    $query = "SELECT author_profile FROM lms_author WHERE author_id = :id";
    $statement = $connect->prepare($query);
    $statement->execute([':id' => $id]);
    $current_profile = $statement->fetchColumn();
    
    $profile_image = $current_profile;
    
    // Check if new profile image was uploaded
    if(isset($_FILES['author_profile']) && $_FILES['author_profile']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        $filename = $_FILES['author_profile']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($ext), $allowed)) {
            // Generate unique filename
            $new_filename = 'author_' . time() . '.' . $ext;
            $upload_path = '../upload/' . $new_filename;
            
            // Create directory if it doesn't exist
            if(!is_dir('../upload/')) {
                mkdir('../upload/', 0777, true);
            }
            
            // Move uploaded file
            if(move_uploaded_file($_FILES['author_profile']['tmp_name'], $upload_path)) {
                $profile_image = $new_filename;
                
                // Delete old file if it's not the default
                if($current_profile != 'author.jpg' && file_exists('../upload/' . $current_profile)) {
                    unlink('../upload/' . $current_profile);
                }
            }
        }
    }

    $update_query = "
    UPDATE lms_author 
    SET author_name = :name, 
        author_status = :status,
        author_profile = :profile,
        author_biography = :biography,
        author_updated_on = :updated_on
    WHERE author_id = :id
    ";

    $params = [
        ':name' => $name,
        ':status' => $status,
        ':profile' => $profile_image,
        ':biography' => $biography,
        ':updated_on' => get_date_time($connect),
        ':id' => $id
    ];

    $statement = $connect->prepare($update_query);
    $statement->execute($params);

    header('location:author.php?msg=edit');
    exit;
}

// SELECT author
$query = "SELECT * FROM lms_author ORDER BY author_id DESC";
$statement = $connect->prepare($query);
$statement->execute();
$author = $statement->fetchAll(PDO::FETCH_ASSOC);


?>

<h1 class="my-3">Author Management</h1>

<?php if (isset($_GET["msg"])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_GET["msg"]) && $_GET["msg"] == 'disable'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Author Disabled',
                    text: 'The author has been successfully disabled.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'enable'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Author Enabled',
                    text: 'The author has been successfully enabled.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'add'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Author Added',
                    text: 'The author was added successfully!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'edit'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Author Updated',
                    text: 'The author was updated successfully!',
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
    <!-- Add author Form -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Author</h5>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
                <div class="alert alert-danger" id="error-alert">
                    <i class="fas fa-exclamation-circle me-2"></i>Author already exists.
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="author_name" class="form-label fw-bold">Author Name</label>
                            <input type="text" name="author_name" id="author_name" class="form-control" required>
                            <div class="invalid-feedback">Please enter the author name</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="author_status" class="form-label fw-bold">Status</label>
                            <select name="author_status" id="author_status" class="form-select">
                                <option value="Enable">Active</option>
                                <option value="Disable">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="author_biography" class="form-label fw-bold">Biography <small class="text-muted">(Optional)</small></label>
                            <textarea name="author_biography" id="author_biography" class="form-control" rows="5" placeholder="Enter author's biography or description"></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="author_profile" class="form-label fw-bold">Profile Image</label>
                            <div class="card">
                                <div class="card-body text-center">
                                    <img id="profile_preview" src="../asset/img/author.jpg" class="img-fluid mb-2 rounded" style="max-height: 200px; max-width: 100%;" alt="Author Profile">
                                    <input type="file" name="author_profile" id="author_profile" class="form-control" accept="image/*">
                                    <div class="form-text">Recommended size: 400×400 pixels</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <input type="submit" name="add_author" class="btn btn-success" value="Add Author">
                    <a href="author.php" class="btn btn-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>

<?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])): ?>
    <?php
    $id = $_GET['code'];
    $query = "SELECT * FROM lms_author WHERE author_id = :id LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->execute([':id' => $id]);
    $author = $statement->fetch(PDO::FETCH_ASSOC);
    
    // Determine image path (check if file exists)
    $image_path = '../upload/' . $author['author_profile'];
    if (!file_exists($image_path)) {
        $image_path = '../asset/img/author.jpg';
    }
    ?>

    <?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
        <div class="alert alert-danger">
            Author already exists.
        </div>
    <?php endif; ?>

    <!-- Edit author Form -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Author</h5>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="author_id" value="<?= $author['author_id'] ?>">
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="author_name" class="form-label fw-bold">Author Name</label>
                            <input type="text" name="author_name" id="author_name" class="form-control" value="<?= htmlspecialchars($author['author_name']) ?>" required>
                            <div class="invalid-feedback">Please enter the author name</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="author_status" class="form-label fw-bold">Status</label>
                            <select name="author_status" id="author_status" class="form-select">
                                <option value="Enable" <?= $author['author_status'] == 'Enable' ? 'selected' : '' ?>>Active</option>
                                <option value="Disable" <?= $author['author_status'] == 'Disable' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="author_biography" class="form-label fw-bold">Biography <small class="text-muted">(Optional)</small></label>
                            <textarea name="author_biography" id="author_biography" class="form-control" rows="5" placeholder="Enter author's biography or description"><?= isset($author['author_biography']) ? htmlspecialchars($author['author_biography']) : '' ?></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="author_profile" class="form-label fw-bold">Profile Image</label>
                            <div class="card">
                                <div class="card-body text-center">
                                    <img id="profile_preview" src="<?= $image_path ?>" class="img-fluid mb-2 rounded" style="max-height: 200px; max-width: 100%;" alt="Author Profile">
                                    <input type="file" name="author_profile" id="author_profile" class="form-control" accept="image/*">
                                    <div class="form-text">Recommended size: 400×400 pixels</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <input type="submit" name="edit_author" class="btn btn-primary" value="Update Author">
                    <a href="author.php" class="btn btn-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>

<?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])): ?>
    <?php
    $id = $_GET['code'];
    $query = "SELECT * FROM lms_author WHERE author_id = :id LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->execute([':id' => $id]);
    $author = $statement->fetch(PDO::FETCH_ASSOC);

    // Determine image path (check if file exists)
    $image_path = '../upload/' . $author['author_profile'];
    if (!file_exists($image_path)) {
        $image_path = '../asset/img/author.jpg';
    }

    if ($author): 
    ?>

    <!-- View author Details -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Author Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <div class="card">
                        <div class="card-body p-2">
                            <img src="<?= $image_path ?>" class="img-fluid rounded" style="max-height: 250px;" alt="<?= htmlspecialchars($author['author_name']) ?>">
                        </div>
                    </div>
                    <h4 class="mt-3"><?= htmlspecialchars($author['author_name']) ?></h4>
                    <p class="mb-1">
                        <?= ($author['author_status'] === 'Enable') 
                            ? '<span class="badge bg-success">Active</span>' 
                            : '<span class="badge bg-danger">Inactive</span>' ?>
                    </p>
                </div>
                
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Author Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-md-4 fw-bold">ID:</div>
                                <div class="col-md-8"><?= htmlspecialchars($author['author_id']) ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4 fw-bold">Status:</div>
                                <div class="col-md-8">
                                    <?= ($author['author_status'] === 'Enable') 
                                        ? '<span class="badge bg-success">Active</span>' 
                                        : '<span class="badge bg-danger">Inactive</span>' ?>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4 fw-bold">Created On:</div>
                                <div class="col-md-8"><?= date('M d, Y H:i:s', strtotime($author['author_created_on'])) ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4 fw-bold">Last Updated:</div>
                                <div class="col-md-8"><?= date('M d, Y H:i:s', strtotime($author['author_updated_on'])) ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($author['author_biography'])): ?>
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Biography</h5>
                        </div>
                        <div class="card-body">
                            <?= nl2br(htmlspecialchars($author['author_biography'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-flex justify-content-end mt-4">
                <a href="author.php" class="btn btn-secondary">Back to List</a>
                <a href="author.php?action=edit&code=<?= $author['author_id'] ?>" class="btn btn-primary ms-2">
                    <i class="fas fa-edit me-1"></i> Edit Author
                </a>
            </div>
        </div>
    </div>

    <?php else: ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i> Author not found.
        </div>
        <a href="author.php" class="btn btn-secondary">Back to List</a>
    <?php endif;
    
    // END VIEW author
    else: ?>

    <!-- author List -->
    <div class="card hadow-sm border-0">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Author List</h5>
                <?php if (!isset($_GET['action'])): ?>            
                        <a href="author.php?action=add" class="btn btn-sm btn-success">
                            <i class="fas fa-plus-circle me-2"></i>Add Author
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
                        <th>Author Name</th>
                        <th>Status</th>
                        <th>Created On</th>
                        <th>Updated On</th> 
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($author) > 0): ?>
                        <?php foreach ($author as $row): 
                            // Determine image path (check if file exists)
                            $image_path = '../upload/' . $row['author_profile'];
                            if (!file_exists($image_path)) {
                                $image_path = '../asset/img/author.jpg';
                            }
                        ?>
                            <tr>
                                <td><?= $row['author_id'] ?></td>
                                <td class="text-center">
                                    <img src="<?= $image_path ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;" alt="<?= htmlspecialchars($row['author_name']) ?>">
                                </td>
                                <td><?= htmlspecialchars($row['author_name']) ?></td>
                                <td>
                                    <?= ($row['author_status'] === 'Enable') 
                                        ? '<span class="badge bg-success">Active</span>' 
                                        : '<span class="badge bg-danger">Inactive</span>' ?>
                                </td>
                                <td><?= date('M d, Y H:i:s', strtotime($row['author_created_on'])) ?></td>
                                <td><?= date('M d, Y H:i:s', strtotime($row['author_updated_on'])) ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="author.php?action=view&code=<?= $row['author_id'] ?>" class="btn btn-info btn-sm mb-1">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="author.php?action=edit&code=<?= $row['author_id'] ?>" class="btn btn-primary btn-sm mb-1">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm mb-1 delete-btn" 
                                                data-id="<?= $row['author_id'] ?>" 
                                                data-status="<?= $row['author_status'] ?>">
                                            <i class="fa fa-<?= $row['author_status'] === 'Enable' ? 'ban' : 'check-circle' ?>"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">No Data Found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

</main>

<script>
// Image preview script
document.addEventListener('DOMContentLoaded', function() {
    const profileInput = document.getElementById('author_profile');
    const previewImg = document.getElementById('profile_preview');
    
    if(profileInput && previewImg) {
        profileInput.addEventListener('change', function() {
            if(this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // DataTable initialization
    const table = $('#dataTable').DataTable({
        responsive: true,
        columnDefs: [
            { responsivePriority: 1, targets: [0, 2, 6] },
            { responsivePriority: 2, targets: [1, 3] }
        ],
        order: [[0, 'desc']],
        autoWidth: false,
        language: {
            emptyTable: "No data available"
        },
        
        // Scroll and pagination settings
        scrollY: '500px',
        scrollX: true,
        scrollCollapse: true,
        paging: true, 
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
    // Delete button functionality
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const authorId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const newStatus = (currentStatus === 'Enable') ? 'Disable' : 'Enable';
            const actionText = (currentStatus === 'Enable') ? 'disable' : 'enable';
            
            Swal.fire({
                title: `Are you sure you want to ${actionText} this author?`,
                text: "This action can be reverted later.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: `Yes, ${actionText} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `author.php?action=delete&status=${newStatus}&code=${authorId}`;
                }
            });
        });
    });
    
    // Remove query parameters from the URL (optional after showing alert)
    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('error');
        window.history.replaceState({}, document.title, url.pathname + url.search);
    }
    
    // Error alert auto-dismiss
    const alertBox = document.getElementById('error-alert');
    if (alertBox) {
        setTimeout(function() {
            alertBox.style.transition = 'opacity 0.5s ease';
            alertBox.style.opacity = '0';
            setTimeout(function() {
                alertBox.remove();
            }, 500);
            
            if (window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('error');
                window.history.replaceState({}, document.title, url.pathname + url.search);
            }
        }, 3000);
    }
});
</script>

<?php include '../footer.php'; ?>