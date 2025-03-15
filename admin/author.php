<?php
include '../database_connection.php';
include '../function.php';

// Check if admin is logged in
if (!is_admin_login()) {
    header('location:../admin_login.php');
    exit();
}

// ADD AUTHOR LOGIC
if (isset($_GET["action"]) && $_GET["action"] == "add") {

    $error = '';

    // If form submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $author_name = trim($_POST['author_name']);

        if ($author_name == '') {
            $error = 'Author name is required!';
        }

        if ($error == '') {

            $data = array(
                ':author_name'       => $author_name,
                ':author_status'     => 'Enable',
                ':author_created_on' => get_date_time($connect),
                ':author_updated_on' => get_date_time($connect)
            );

            $query = "
                INSERT INTO lms_author 
                (author_name, author_status, author_created_on, author_updated_on)
                VALUES (:author_name, :author_status, :author_created_on, :author_updated_on)
            ";

            $statement = $connect->prepare($query);

            if ($statement->execute($data)) {
                header('location:author.php?msg=add');
                exit();
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }

    include '../header.php';
    ?>

    <main class="container py-4" style="min-height: 700px;">
        <h1 class="my-3">Add New Author</h1>

        <?php 
        if (!empty($error)) {
            echo '<div class="alert alert-danger">' . $error . '</div>';
        }
        ?>

        <div class="card">
            <div class="card-header">
                <h5>Author Form</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="author_name" class="form-label">Author Name</label>
                        <input type="text" name="author_name" id="author_name" class="form-control" required />
                    </div>
                    <div>
                        <button type="submit" class="btn btn-success">Save</button>
                        <a href="author.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php
    include '../footer.php';
    exit();
}

// Delete / Disable / Enable logic
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {

    $author_id = convert_data($_GET["code"], 'decrypt');
    $status = $_GET["status"];

    $data = array(
        ':author_status'     => $status,
        ':author_updated_on' => get_date_time($connect),
        ':author_id'         => $author_id
    );

    $query = "
        UPDATE lms_author 
        SET author_status = :author_status, 
            author_updated_on = :author_updated_on 
        WHERE author_id = :author_id
    ";

    $statement = $connect->prepare($query);
    $statement->execute($data);

    header('location:author.php?msg=' . strtolower($status) . '');
    exit();
}

// SELECT Query for all authors
$query = "
    SELECT * FROM lms_author 
    ORDER BY author_id DESC
";

$statement = $connect->prepare($query);
$statement->execute();

$authors = $statement->fetchAll(PDO::FETCH_ASSOC);

include '../header.php';
?>

<main class="container py-4" style="min-height: 700px;">
    <h1 class="my-3">Author Management</h1>

    <?php 
    if (isset($_GET["msg"])) {
        if ($_GET["msg"] == 'disable') {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                Author Status Changed to Disable 
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        }

        if ($_GET["msg"] == 'enable') {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                Author Status Changed to Enable 
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        }

        if ($_GET["msg"] == 'add') {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                New Author Added Successfully
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        }
    }
    ?>

    <div class="card mb-4">
        <div class="card-header">
            <div class="row">
                <div class="col col-md-6">
                    <i class="fas fa-table me-1"></i> Author Management
                </div>
                <div class="col col-md-6">
                    <a href="author.php?action=add" class="btn btn-success btn-sm float-end">Add</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table id="dataTable" class="table table-bordered table-striped display responsive nowrap py-4 dataTable no-footer collapsed table-active" style="width:100%">
                <thead>
                    <tr>
                        <th style="width: 50px;">Author ID</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                if (count($authors) > 0) {
                    foreach ($authors as $row) {
                        $status_badge = $row["author_status"] == "Enable" ? 
                            '<span class="badge bg-success">Active</span>' : 
                            '<span class="badge bg-danger">Inactive</span>';

                        echo '
                        <tr>
                            <td>' . htmlspecialchars($row["author_id"]) . '</td>
                            <td>' . htmlspecialchars($row["author_name"]) . '</td>
                            <td>' . $status_badge . '</td>
                            <td>' . $row["author_created_on"] . '</td>
                            <td>' . $row["author_updated_on"] . '</td>
                            <td class="text-center">
                                <a href="author.php?action=view&code=' . convert_data($row["author_id"]) . '" class="btn btn-info btn-sm mb-1">View</a>
                                <a href="author.php?action=edit&code=' . convert_data($row["author_id"]) . '" class="btn btn-primary btn-sm mb-1">Edit</a>
                                <button type="button" name="delete_button" class="btn btn-danger btn-sm" onclick="delete_data(`' . convert_data($row["author_id"]) . '`)">Delete</button>
                            </td>
                        </tr>';
                    }
                } else {
                    echo '
                    <tr>
                        <td colspan="6" class="text-center">No Data Found</td>
                    </tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
function delete_data(code) {
    if (confirm("Are you sure you want to disable this Author?")) {
        window.location.href = "author.php?action=delete&code=" + code + "&status=Disable";
    }
}

$(document).ready(function() {
    $('#dataTable').DataTable({
        responsive: true,
        columnDefs: [
            { responsivePriority: 1, targets: [0, 1, 5] },
            { responsivePriority: 2, targets: [2] },
            { responsivePriority: 10000, targets: [3, 4] }
        ],
        order: [[0, 'asc']],
        autoWidth: false,
        language: {
            emptyTable: "No data available"
        }
    });
});
</script>

<?php include '../footer.php'; ?>
