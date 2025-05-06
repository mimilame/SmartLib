<?php
//category.php

include '../database_connection.php';
include '../function.php';
include '../header.php';
authenticate_admin();
$message = ''; // Feedback message

// DELETE (Disable/Enable)
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {
    $category_id = $_GET["code"];
    $status = $_GET["status"];

    $data = array(
        ':category_status' => $status,
        ':category_id'     => $category_id
    );

    $query = "
    UPDATE lms_category 
    SET category_status = :category_status 
    WHERE category_id = :category_id";

    $statement = $connect->prepare($query);
    $statement->execute($data);

    header('location:category.php?msg=' . strtolower($status) . '');
    exit;
}

// ADD category (Form Submit)
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']); // Clean the input
    $status = $_POST['category_status'];

    // Check for duplicate (case-insensitive comparison)
    $check_query = "
        SELECT COUNT(*) 
        FROM lms_category 
        WHERE LOWER(category_name) = LOWER(:name)
    ";

    $statement = $connect->prepare($check_query);
    $statement->execute([':name' => $name]);
    $count = $statement->fetchColumn();

    if ($count > 0) {
        // Category already exists
        header('location:category.php?action=add&error=exists');
        exit;
    }

    // If not existing, insert new category
    $date_now = get_date_time($connect); // Get the current date once

    $query = "
        INSERT INTO lms_category 
        (category_name, category_status, category_created_on, category_updated_on) 
        VALUES (:name, :status, :created_on, :updated_on)
    ";
    
    $statement = $connect->prepare($query);
    $statement->execute([
        ':name' => $name,
        ':status' => $status,
        ':created_on' => $date_now,   // Same date for both created and updated
        ':updated_on' => $date_now
    ]);
    
    header('location:category.php?msg=add');
    exit;
}

// EDIT category (Form Submit)
if (isset($_POST['edit_category'])) {
    $id = $_POST['category_id'];
    $name = $_POST['category_name'];
    $status = $_POST['category_status'];

    $update_query = "
    UPDATE lms_category 
    SET category_name = :name, 
        category_status = :status,
        category_updated_on = :updated_on
    WHERE category_id = :id
";

    $params = [
    ':name' => $name,
    ':status' => $status,
    ':updated_on' => get_date_time($connect),
    ':id' => $id
    ];

    $statement = $connect->prepare($update_query);
    $statement->execute($params);

    header('location:category.php?msg=edit');
    exit;
}

// SELECT category with book count
$query = "
    SELECT c.*, 
           (SELECT COUNT(*) FROM lms_book WHERE category_id = c.category_id) AS book_count 
    FROM lms_category c 
    ORDER BY c.category_id DESC
";
$statement = $connect->prepare($query);
$statement->execute();
$categories = $statement->fetchAll(PDO::FETCH_ASSOC);


?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center my-4">
        <h1 class="">Category Management</h1>
    </div>

    <?php if (isset($_GET["msg"])): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_GET["msg"]) && $_GET["msg"] == 'disable'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Category Disabled',
                    text: 'The category has been successfully disabled.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done',
                    timer: 2000
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'enable'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Category Enabled',
                    text: 'The category has been successfully enabled.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done',
                    timer: 2000
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'add'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Category Added',
                    text: 'The category was added successfully!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done',
                    timer: 2000
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'edit'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Category Updated',
                    text: 'The category was updated successfully!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done',
                    timer: 2000
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
        <!-- Add category Form -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-plus-circle me-2"></i>Add New Category</h5>
                    <a href="category.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
                    <div class="alert alert-danger alert-dismissible fade show" id="error-alert">
                        <i class="fas fa-exclamation-circle me-2"></i>Category already exists.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="category_name" class="form-label fw-bold">Category Name</label>
                        <input type="text" id="category_name" name="category_name" class="form-control" required>
                        <div class="invalid-feedback">Please enter a category name.</div>
                    </div>
                    <div class="mb-4">
                        <label for="category_status" class="form-label fw-bold">Status</label>
                        <select name="category_status" id="category_status" class="form-select">
                            <option value="Enable">Active</option>
                            <option value="Disable">Inactive</option>
                        </select>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-end mt-3">
                        <button type="submit" name="add_category" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Category
                        </button>
                        <a href="category.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])): ?>
        <?php
        $id = $_GET['code'];
        $query = "SELECT * FROM lms_category WHERE category_id = :id LIMIT 1";
        $statement = $connect->prepare($query);
        $statement->execute([':id' => $id]);
        $category = $statement->fetch(PDO::FETCH_ASSOC);
        ?>

        <!-- Edit category Form -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-edit me-2"></i>Edit Category</h5>
                    <a href="category.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
                    <div class="alert alert-danger alert-dismissible fade show" id="error-alert">
                        <i class="fas fa-exclamation-circle me-2"></i>Category already exists.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="category_id" value="<?= $category['category_id'] ?>">
                    <div class="mb-3">
                        <label for="category_name" class="form-label fw-bold">Category Name</label>
                        <input type="text" id="category_name" name="category_name" class="form-control" value="<?= htmlspecialchars($category['category_name']) ?>" required>
                        <div class="invalid-feedback">Please enter a category name.</div>
                    </div>
                    <div class="mb-4">
                        <label for="category_status" class="form-label fw-bold">Status</label>
                        <select name="category_status" id="category_status" class="form-select">
                            <option value="Enable" <?= $category['category_status'] == 'Enable' ? 'selected' : '' ?>>Active</option>
                            <option value="Disable" <?= $category['category_status'] == 'Disable' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-end mt-3">
                        <button type="submit" name="edit_category" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Category
                        </button>
                        <a href="category.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])): ?>
        <?php
        $id = $_GET['code'];
        $query = "
            SELECT c.*, 
                (SELECT COUNT(*) FROM lms_book WHERE category_id = c.category_id) AS book_count 
            FROM lms_category c 
            WHERE c.category_id = :id LIMIT 1
        ";
        $statement = $connect->prepare($query);
        $statement->execute([':id' => $id]);
        $category = $statement->fetch(PDO::FETCH_ASSOC);

        if ($category): 
        ?>
            <!-- View category Details -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-eye me-2"></i>View Category Details</h5>
                        <a href="category.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="fw-bold text-muted">Category ID</h6>
                                <p class="lead"><?= htmlspecialchars($category['category_id']) ?></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold text-muted">Category Name</h6>
                                <p class="lead"><?= htmlspecialchars($category['category_name']) ?></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold text-muted">Status</h6>
                                <p>
                                    <?= ($category['category_status'] === 'Enable') 
                                        ? '<span class="badge bg-success fs-6">Active</span>' 
                                        : '<span class="badge bg-danger fs-6">Inactive</span>' ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="fw-bold text-muted">Books in Category</h6>
                                <p class="lead">
                                    <span class="badge bg-primary fs-6"><?= $category['book_count'] ?> Books</span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold text-muted">Created On</h6>
                                <p class="text-muted"><?= date('F d, Y h:i A', strtotime($category['category_created_on'])) ?></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold text-muted">Last Updated</h6>
                                <p class="text-muted"><?= date('F d, Y h:i A', strtotime($category['category_updated_on'])) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <a href="category.php?action=edit&code=<?= $category['category_id'] ?>" class="btn btn-primary me-2">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                        <a href="category.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>Category not found.
                <a href="category.php" class="btn btn-sm btn-outline-secondary ms-3">Back to Categories</a>
            </div>
        <?php endif; ?>

    <?php else: ?>
		<!-- Data Table for detailed view -->
		<div class="card shadow-sm border-0">
			<div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Category List</h5>                      
                    <?php if (!isset($_GET['action'])): ?>            
                        <a href="category.php?action=add" class="btn btn-sm btn-success">
                            <i class="fas fa-plus-circle me-2"></i>Add Category
                        </a>
                    <?php endif; ?>
                </div>
				
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table id="dataTable" class="display nowrap">
						<thead>
							<tr>
								<th>ID</th>
								<th>Category Name</th>
								<th>Status</th>
								<th>Books</th>
								<th>Created On</th>
								<th>Updated On</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php if (count($categories) > 0): ?>
								<?php foreach ($categories as $row): ?>
									<tr>
										<td><?= $row['category_id'] ?></td>
										<td>
											<span class="fw-bold"><?= htmlspecialchars($row['category_name']) ?></span>
										</td>
										<td>
											<?= ($row['category_status'] === 'Enable') 
												? '<span class="badge bg-success">Active</span>' 
												: '<span class="badge bg-danger">Inactive</span>' ?>
										</td>
										<td>
											<span class="badge bg-primary rounded-pill"><?= $row['book_count'] ?></span>
										</td>
										<td><?= date('M d, Y H:i', strtotime($row['category_created_on'])) ?></td>
										<td><?= date('M d, Y H:i', strtotime($row['category_updated_on'])) ?></td>
										<td>
											<div class="btn-group btn-group-sm">
												<a href="category.php?action=view&code=<?= $row['category_id'] ?>" class="btn btn-info">
													<i class="fa fa-eye"></i>
												</a>
												<a href="category.php?action=edit&code=<?= $row['category_id'] ?>" class="btn btn-primary">
													<i class="fa fa-edit"></i>
												</a>
												<button type="button" class="btn btn-danger delete-btn" 
														data-id="<?= $row['category_id'] ?>"
														data-status="<?= $row['category_status'] ?>">
													<?php if ($row['category_status'] === 'Enable'): ?>
														<i class="fa fa-ban"></i>
													<?php else: ?>
														<i class="fa fa-check"></i>
													<?php endif; ?>
												</button>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php else: ?>
								<tr><td colspan="7" class="text-center">No categories found</td></tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
    <?php endif; ?>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// DataTable initialization
$(document).ready(function() {    
    const table = $('#dataTable').DataTable({
        responsive: true,
        columnDefs: [
            { responsivePriority: 1, targets: [0, 1, 6] },
            { responsivePriority: 2, targets: [2, 3] }
        ],
        order: [[0, 'asc']],
        autoWidth: false,
        language: {
            emptyTable: "No data available"
        },
        // Scroll and pagination settings
        scrollY: '500px',       // Vertical scroll
        scrollX: true,          // Horizontal scroll
        scrollCollapse: true,   // Collapse height when less data
        paging: true,           // Enable pagination
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
});

// SweetAlert for delete confirmation
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const categoryId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const action = (currentStatus === 'Enable') ? 'disable' : 'enable';
            const confirmText = (currentStatus === 'Enable') ? 'Yes, disable it!' : 'Yes, enable it!';
            const icon = (currentStatus === 'Enable') ? 'warning' : 'info';
            const confirmButtonColor = (currentStatus === 'Enable') ? '#d33' : '#3085d6';

            Swal.fire({
                title: `Are you sure you want to ${action} this category?`,
                text: "This action can be reverted later.",
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: confirmButtonColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmText
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `category.php?action=delete&status=${currentStatus === 'Enable' ? 'Disable' : 'Enable'}&code=${categoryId}`;
                }
            });
        });
    });

    // Handle alert dismissal
    const alertBox = document.getElementById('error-alert');
    if (alertBox) {
        // Display the alert for 3 seconds (3000ms)
        setTimeout(function() {
            // Optional fade-out effect
            alertBox.style.transition = 'opacity 0.5s ease';
            alertBox.style.opacity = '0';

            // Remove the alert after fade (0.5s delay)
            setTimeout(function() {
                alertBox.remove();
            }, 500);

            // Remove 'error' param from the URL after the alert disappears
            if (window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('error');
                window.history.replaceState({}, document.title, url.pathname + url.search);
            }
        }, 3000);
    }
});

// Clean up URL parameters
if (window.history.replaceState) {
    const url = new URL(window.location);
    url.searchParams.delete('error');
    window.history.replaceState({}, document.title, url.pathname + url.search);
}
</script>

<?php include '../footer.php'; ?>