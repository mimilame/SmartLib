<?php
// author.php

include '../database_connection.php';
include '../function.php';

// Check admin login
if (!is_admin_login()) {
    header('location:../admin_login.php');
    exit;
}

$message = '';
$error = '';
$alert = '';

// Add Author
if (isset($_POST["add_author"])) {
    $formdata = [];

    // Validate author_name
    if (empty($_POST["author_name"])) {
        $error .= '<li>Author Name is required</li>';
    } else {
        $formdata['author_name'] = trim($_POST["author_name"]);
    }

    if ($error == '') {
        // Check for duplicate author
        $query = "SELECT * FROM lms_author WHERE author_name = :author_name";
        $statement = $connect->prepare($query);
        $statement->execute([':author_name' => $formdata['author_name']]);

        if ($statement->rowCount() > 0) {
            $error = '<li>Author Name Already Exists</li>';
        } else {
            // Insert new author
            $data = [
                ':author_name'       => $formdata['author_name'],
                ':author_status'     => 'Enable',
                ':author_created_on' => get_date_time($connect)
            ];

            $query = "INSERT INTO lms_author (author_name, author_status, author_created_on) 
                      VALUES (:author_name, :author_status, :author_created_on)";
            $statement = $connect->prepare($query);
            $statement->execute($data);
            set_flash_message('success', 'New Author Added Successfully');
            header('location:author.php?msg=add');
            exit;
        }
    }
}

// Edit Author
if (isset($_POST["edit_author"])) {
    $formdata = [];

    if (empty($_POST["author_name"])) {
        $error .= '<li>Author Name is required</li>';
    } else {
        $formdata['author_name'] = trim($_POST['author_name']);
    }

    if ($error == '') {
        $author_id = convert_data($_POST['author_id'], 'decrypt');

        // Check if another author with the same name exists
        $query = "SELECT * FROM lms_author WHERE author_name = :author_name AND author_id != :author_id";
        $statement = $connect->prepare($query);
        $statement->execute([
            ':author_name' => $formdata['author_name'],
            ':author_id'   => $author_id
        ]);

        if ($statement->rowCount() > 0) {
            $error = '<li>Author Name Already Exists</li>';
        } else {
            // Update author details
            $data = [
                ':author_name'       => $formdata['author_name'],
                ':author_updated_on' => get_date_time($connect),
                ':author_id'         => $author_id
            ];

            $query = "UPDATE lms_author 
                      SET author_name = :author_name, author_updated_on = :author_updated_on  
                      WHERE author_id = :author_id";
            $statement = $connect->prepare($query);
            $statement->execute($data);
            set_flash_message('success', 'Author Updated Successfully');
            header('location:author.php?msg=edit');
            exit;
        }
    }
}

// Toggle Author Status (Enable/Disable)
if (isset($_GET["action"], $_GET["code"], $_GET["status"]) && $_GET["action"] == 'delete') {
    $author_id = $_GET["code"];
    $status    = $_GET["status"];

    $data = [
        ':author_status'     => $status,
        ':author_updated_on' => get_date_time($connect),
        ':author_id'         => $author_id
    ];

    $query = "UPDATE lms_author 
              SET author_status = :author_status, author_updated_on = :author_updated_on 
              WHERE author_id = :author_id";
    $statement = $connect->prepare($query);
    $statement->execute($data);
    $message = ($status == 'Active') ? 'Author Marked as Active' : 'Author Marked as Inactive';
    set_flash_message('success', $message);
    header('location:author.php?msg=' . strtolower($status));
    exit;
}

// Fetch Authors
$query = "SELECT * FROM lms_author ORDER BY author_name ASC";
$statement = $connect->prepare($query);
$statement->execute();
$authors = $statement->fetchAll(PDO::FETCH_ASSOC);

// Check for flash messages
$success_message = get_flash_message('success');
if($success_message != '') {
    $alert = sweet_alert('success', $success_message);
}

// For form validation errors
if($error != '') {
    $alert_message = str_replace('<li>', '', $error);
    $alert_message = str_replace('</li>', '', $alert_message);
    $alert = sweet_alert('error', $alert_message);
}

include '../header.php';
?>

<main class="container py-4" style="min-height: 700px;">
    <h1 class="mb-4">Author Management</h1>
    <?php echo $alert; ?>

    <?php if (isset($_GET["action"]) && $_GET["action"] == "add"): ?>

        <!-- Add Author Form -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-plus me-1"></i> Add New Author
                <a href="author.php" class="btn btn-secondary btn-sm float-end">Back</a>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="author_name" class="form-label">Author Name</label>
                        <input type="text" name="author_name" id="author_name" class="form-control" />
                    </div>
                    <div class="text-end">
                        <button type="submit" name="add_author" class="btn btn-success">Add Author</button>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif (isset($_GET["action"]) && $_GET["action"] == "edit"): ?>

        <?php
        $author_id = convert_data($_GET["code"], 'decrypt');
        $author_query = "SELECT author_id, author_name FROM lms_author WHERE author_id = :author_id";
        $author_stmt = $connect->prepare($author_query);
        $author_stmt->execute([':author_id' => $author_id]);
        $author_row = $author_stmt->fetch(PDO::FETCH_ASSOC);
        ?>

        <!-- Edit Author Form -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-edit me-1"></i> Edit Author
                <a href="author.php" class="btn btn-secondary btn-sm float-end">Back</a>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="author_name" class="form-label">Author Name</label>
                        <input type="text" name="author_name" id="author_name" class="form-control" value="<?php echo $author_row['author_name']; ?>" />
                    </div>
                    <input type="hidden" name="author_id" value="<?php echo $_GET['code']; ?>" />
                    <div class="text-end">
                        <button type="submit" name="edit_author" class="btn btn-primary">Update Author</button>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>

        <!-- Author List -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col"><i class="fas fa-table me-1"></i> Author List</div>
                    <div class="col-auto">
                        <a href="javascript:void(0)" onclick="openAddModal()" class="btn btn-success btn-sm">Add Author</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table id="dataTable" class="table table-bordered table-striped display responsive nowrap py-4 dataTable no-footer dtr-column collapsed " style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Author Name</th>
                            <th>Status</th>
                            <th>Created On</th>
                            <th>Updated On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($authors)): ?>
                            <?php foreach ($authors as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['author_id']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['author_name']); ?></td>
                                    <td>
                                        <div class="badge bg-<?= ($row['author_status'] === 'Enable') ? 'success' : 'danger'; ?>">
											<?= ($row['author_status'] === 'Enable') ? 'Active' : 'Inactive'; ?>
										</div>
                                    </td>
                                    <td><?php echo $row['author_created_on']; ?></td>
                                    <td><?php echo $row['author_updated_on']; ?></td>
                                    <td>
                                        <a href="javascript:void(0);" onclick="openEditModal('<?= convert_data($row["author_id"]); ?>', '<?= htmlspecialchars($row["author_name"]); ?>')" class="btn btn-sm btn-primary">
											<i class="fa fa-edit"></i>
										</a>
                                        <button type="button" class="btn btn-<?= ($row['author_status'] === 'Enable') ? 'danger' : 'success'; ?> btn-sm"
											onclick="toggle_status('<?= convert_data($row["author_id"]); ?>', '<?= $row["author_status"]; ?>')">
											<?= ($row['author_status'] === 'Enable') ? 'Deactivate' : 'Activate'; ?>
										</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No Author Found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Edit Modal -->
        <div class="modal fade" id="editAuthorModal" tabindex="-1" aria-labelledby="editAuthorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAuthorModalLabel">Edit Author</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_author_name" class="form-label">Author Name</label>
                        <input type="text" class="form-control" id="edit_author_name" name="author_name" required>
                    </div>
                    <input type="hidden" id="edit_author_id" name="author_id">
                    </div>
                    <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_author" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
                </div>
            </div>
        </div>
        <!-- Add Modal -->
        <div class="modal fade" id="addAuthorModal" tabindex="-1" aria-labelledby="addAuthorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAuthorModalLabel">Add New Author</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_author_name" class="form-label">Author Name</label>
                        <input type="text" class="form-control" id="add_author_name" name="author_name" required>
                    </div>
                    <input type="hidden" id="add_author_id" name="author_id">
                    </div>
                    <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_author" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
                </div>
            </div>
        </div>

    <?php endif;?>
    
</main>

<script>
        function toggle_status(code, status) {
			let newStatus = status === 'Enable' ? 'Disable' : 'Enable';
			let statusText = status === 'Enable' ? 'mark as Inactive' : 'mark as Active';
			
			Swal.fire({
				title: 'Are you sure?',
				text: "You want to " + statusText + " this Author?",
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Yes, ' + statusText + '!'
			}).then((result) => {
				if (result.isConfirmed) {
					window.location.href = "author.php?action=delete&code=" + code + "&status=" + newStatus;
				}
			});
		}
		// Function to open edit modal
		function openEditModal(id, name) {
			document.getElementById('edit_author_id').value = id;
			document.getElementById('edit_author_name').value = name;
			
			// Show the modal
			const editModal = new bootstrap.Modal(document.getElementById('editAuthorModal'));
			editModal.show();
		}
		// Function to open add modal
		function openAddModal() {
			document.getElementById('add_author_id').value = '';
			document.getElementById('add_author_name').value = '';
			
			// Show the modal
			const addModal = new bootstrap.Modal(document.getElementById('addAuthorModal'));
			addModal.show();
		}

$(document).ready(function() {
    $('#dataTable').DataTable({
        responsive: {
            details: { type: 'column', target: 'tr' }
        },
        columnDefs: [
            { className: 'dtr-control', orderable: false, targets: 0 },
            { responsivePriority: 1, targets: 1 },
            { responsivePriority: 2, targets: 2 },
            { responsivePriority: 3, targets: 3 },
            { responsivePriority: 4, targets: 4 },
            { responsivePriority: 5, targets: 5 }
        ],
        order: [[1, 'asc']],
        autoWidth: false,
        language: { emptyTable: "No data available" }
    });
});
</script>

<?php include '../footer.php'; ?>
