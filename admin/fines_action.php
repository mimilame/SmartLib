<?php
// fines_action.php

include '../database_connection.php';
include '../function.php';
include '../header.php';

$message = '';


// EDIT fine (Form Submit)
if (isset($_POST['edit_fine'])) {
    $fines_id = $_POST['fines_id'];
    $user_id = $_POST['user_id'];
    $issue_book_id = $_POST['issue_book_id'];
    $expected_return_date = $_POST['expected_return_date'];
    $return_date = $_POST['return_date'];
    $days_late = $_POST['days_late'];
    $fines_amount = $_POST['fines_amount'];
    $fines_status = $_POST['fines_status'];

    $update_query = "
        UPDATE lms_fines 
        SET user_id = :user_id,
            issue_book_id = :issue_book_id,
            expected_return_date = :expected_return_date,
            return_date = :return_date,
            days_late = :days_late,
            fines_amount = :fines_amount,
            fines_status = :fines_status,
            fines_updated_on = :updated_on
        WHERE fines_id = :fines_id
    ";

    $params = [
        ':user_id' => $user_id,
        ':issue_book_id' => $issue_book_id,
        ':expected_return_date' => $expected_return_date,
        ':return_date' => $return_date,
        ':days_late' => $days_late,
        ':fines_amount' => $fines_amount,
        ':fines_status' => $fines_status,
        ':updated_on' => get_date_time($connect),
        ':fines_id' => $fines_id
    ];

    $statement = $connect->prepare($update_query);
    $statement->execute($params);

    header('location:fines.php?msg=edit');
    exit;
}
?>

<?php
// Assuming a connection to the database has been established as $connect

// Edit Fine Form - Triggered by ?action=edit&code=xxx
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['code'])):
    $id = $_GET['code'];

    // Fetching the fine data
    $query = "SELECT * FROM lms_fines WHERE fines_id = :id LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->execute([':id' => $id]);
    $fine = $statement->fetch(PDO::FETCH_ASSOC);

    // Handling error: Fine already exists
    if (isset($_GET['error']) && $_GET['error'] == 'exists'):
        echo '<div class="alert alert-danger">Fine already exists. Please choose another name.</div>';
    endif;
    ?>

    <!-- Edit Fine Form -->
    <div class="card mx-5 mt-5">
        <div class="card-header"><h5>Edit Fine</h5></div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="fines_id" value="<?= $fine['fines_id'] ?>">

                <div class="mb-3">
                    <label>User ID</label>
                    <input type="number" name="user_id" class="form-control" value="<?= htmlspecialchars($fine['user_id']) ?>" required>
                </div>

                <div class="mb-3">
                    <label>Issue Book ID</label>
                    <input type="number" name="issue_book_id" class="form-control" value="<?= htmlspecialchars($fine['issue_book_id']) ?>" required>
                </div>

                <div class="mb-3">
                    <label>Expected Return Date</label>
                    <input type="date" name="expected_return_date" class="form-control" value="<?= htmlspecialchars($fine['expected_return_date']) ?>" required>
                </div>

                <div class="mb-3">
                    <label>Return Date</label>
                    <input type="date" name="return_date" class="form-control" value="<?= htmlspecialchars($fine['return_date']) ?>" required>
                </div>

                <div class="mb-3">
                    <label>Days Late</label>
                    <input type="number" name="days_late" class="form-control" value="<?= htmlspecialchars($fine['days_late']) ?>" required min="0">
                </div>

                <div class="mb-3">
                    <label>Fine Amount</label>
                    <input type="number" name="fines_amount" class="form-control" value="<?= htmlspecialchars($fine['fines_amount']) ?>" required min="0" step="0.01">
                </div>

                <div class="mb-3">
                    <label>Status</label>
                    <select name="fines_status" class="form-select">
                        <option value="Paid" <?= $fine['fines_status'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="Unpaid" <?= $fine['fines_status'] == 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                    </select>
                </div>

                <input type="submit" name="edit_fine" class="btn btn-primary" value="Update Fine">
                <a href="fines.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

<?php
// View Fine Form - Triggered by ?action=view&code=xxx
elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['code'])):
    $id = $_GET['code'];

    // Fetching the fine data for view
    $query = "SELECT * FROM lms_fines WHERE fines_id = :id LIMIT 1";
    $statement = $connect->prepare($query);
    $statement->execute([':id' => $id]);
    $fine = $statement->fetch(PDO::FETCH_ASSOC);

    if ($fine): 
?>

    <!-- View Fine Details -->
    <div class="card mx-5 mt-5">
        <div class="card-header"><h5>View Fine</h5></div>
        <div class="card-body">
            <p><strong>ID:</strong> <?= htmlspecialchars($fine['fines_id']) ?></p>
            <p><strong>User ID:</strong> <?= htmlspecialchars($fine['user_id']) ?></p>
            <p><strong>Issue Book ID:</strong> <?= htmlspecialchars($fine['issue_book_id']) ?></p>
            <p><strong>Fine Amount:</strong> ₱<?= htmlspecialchars(number_format($fine['fines_amount'], 2)) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($fine['fines_status']) ?></p>
            <p><strong>Created On:</strong> <?= date('M d, Y h:i A', strtotime($fine['fines_created_on'])) ?></p>
            <p><strong>Updated On:</strong> <?= date('M d, Y h:i A', strtotime($fine['fines_updated_on'])) ?></p>
            <a href="fines.php" class="btn btn-secondary">Back</a>
        </div>
    </div>

<?php 
    else: 
?>
        <p class="alert alert-danger">Fine not found.</p>
        <a href="fines.php" class="btn btn-secondary">Back</a>
<?php 
    endif;
endif;
?>
