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

    header('location:author.php?msg=' . strtolower($status));
    exit;
}

// Fetch Authors
$query = "SELECT * FROM lms_author ORDER BY author_name ASC";
$statement = $connect->prepare($query);
$statement->execute();

include '../header.php';
?>

<main class="container py-4" style="min-height: 700px;">
    <h1 class="mb-4">Author Management</h1>

    <?php if (isset($_GET["msg"])): ?>
        <?php if ($_GET["msg"] == 'add'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                New Author Added Successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($_GET["msg"] == 'edit'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Author Data Updated Successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($_GET["msg"] == 'enable'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Author Status Changed to Enable!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($_GET["msg"] == 'disable'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Author Status Changed to Disable!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($_GET["action"]) && $_GET["action"] == "add"): ?>

        <!-- Add Author Form -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-plus me-1"></i> Add New Author
                <a href="author.php" class="btn btn-secondary btn-sm float-end">Back</a>
            </div>
            <div class="card-body">
                <?php if ($error != ''): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="list-unstyled"><?php echo $error; ?></ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

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
        $author_row = $author_stmt->fetch();
        ?>

        <!-- Edit Author Form -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-edit me-1"></i> Edit Author
                <a href="author.php" class="btn btn-secondary btn-sm float-end">Back</a>
            </div>
            <div class="card-body">
                <?php if ($error != ''): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="list-unstyled"><?php echo $error; ?></ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

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
                        <a href="author.php?action=add" class="btn btn-success btn-sm">Add Author</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table id="dataTable" class="table table-bordered table-striped nowrap display responsive w-100">
                    <thead>
                        <tr>
                            <th>Author ID</th>
                            <th>Author Name</th>
                            <th>Status</th>
                            <th>Created On</th>
                            <th>Updated On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($statement->rowCount() > 0): ?>
                            <?php foreach ($statement->fetchAll() as $row): ?>
                                <tr>
                                    <td></td>
                                    <td><?php echo htmlspecialchars($row['author_name']); ?></td>
                                    <td>
                                        <?php echo $row['author_status'] === 'Enable' ? '<span class="badge bg-success">Enable</span>' : '<span class="badge bg-danger">Disable</span>'; ?>
                                    </td>
                                    <td><?php echo $row['author_created_on']; ?></td>
                                    <td><?php echo $row['author_updated_on']; ?></td>
                                    <td>
                                        <a href="author.php?action=edit&code=<?php echo convert_data($row['author_id']); ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="delete_data('<?php echo $row['author_id']; ?>', '<?php echo $row['author_status']; ?>')">
                                            <?php echo $row['author_status'] === 'Enable' ? 'Disable' : 'Enable'; ?>
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

    <?php endif; ?>
</main>

<script>
function delete_data(code, status) {
    const new_status = status === 'Enable' ? 'Disable' : 'Enable';
    if (confirm(`Are you sure you want to ${new_status} this Author?`)) {
        window.location.href = `author.php?action=delete&code=${code}&status=${new_status}`;
    }
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
