<?php
// fines.php

include '../database_connection.php';
include '../function.php';
include '../header.php';

$message = '';

// DELETE (Disable/Enable)
if (isset($_GET["action"], $_GET['status'], $_GET['code']) && $_GET["action"] == 'delete') {
    $fines_id = $_GET["code"];
    $status = $_GET["status"]; // Accepts 'Active' or 'Deleted'

    $data = [
        ':fines_status' => $status,
        ':fines_id'     => $fines_id
    ];

    $query = "
        UPDATE lms_fines 
        SET fines_status = :fines_status, fines_updated_on = NOW()
        WHERE fines_id = :fines_id
    ";

    $statement = $connect->prepare($query);
    $statement->execute($data);

    header('location:fines.php?msg=' . strtolower($status));
    exit;
}


// Fetch all fines with user and book details
$query = "
    SELECT 
        f.fines_id,
        u.user_name,
        ib.issue_book_id,
        ib.expected_return_date,
        ib.return_date,
        DATEDIFF(ib.return_date, ib.expected_return_date) AS days_late,
        f.fines_amount,
        f.fines_status,
        f.fines_created_on,
        f.fines_updated_on
    FROM lms_fines AS f
    LEFT JOIN lms_issue_book AS ib 
        ON f.issue_book_id = ib.issue_book_id
    LEFT JOIN lms_user AS u 
        ON f.user_id = u.user_id
    WHERE ib.issue_book_id IS NOT NULL
    ORDER BY f.fines_id ASC
";



$statement = $connect->prepare($query);
$statement->execute();
$fines = $statement->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .status-card {
            transition: all 0.3s ease;
        }
        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            font-weight: bold;
        }
        .card-counter {
            padding: 20px;
            border-radius: 10px;
            color: #fff;
            transition: all 0.3s ease;
        }
        .card-counter i {
            font-size: 4rem;
            opacity: 0.4;
        }
        .card-counter .count-numbers {
            position: absolute;
            right: 35px;
            top: 20px;
            font-size: 32px;
            display: block;
        }
        .card-counter .count-name {
            position: absolute;
            right: 35px;
            top: 65px;
            font-style: italic;
            text-transform: capitalize;
            opacity: 0.8;
            display: block;
        }
        .bg-paid { background-color: #4caf50; }
        .bg-returned { background-color: #2196F3; }
        .bg-issued { background-color: #ff9800; }
        .bg-unpaid { background-color: #f44336; }
    </style>
</head>
<main class="container py-4" style="min-height: 700px;">

<h1 class="my-3">Fines Management</h1>
	<div class="row mb-4">
        
    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-counter bg-unpaid status-card">
                            <i class="bi bi-exclamation-circle float-start"></i>
                            <span class="count-numbers">
                            <?php echo get_currency_symbol($connect) . Count_total_fines_outstanding($connect); ?>
                            </span>
                            <span class="count-name">Total Outstanding</span>
                        </div>
                    </div>


                <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-counter bg-paid status-card">
                            <i class="bi bi-cash-coin float-start"></i>
                            <span class="count-numbers">
                            <?php echo get_currency_symbol($connect) . Count_total_fines_received($connect); ?>
                            </span>
                            <span class="count-name">Collected This Month</span>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                        <div class="card card-counter bg-issued status-card">
                            <i class="bi bi-receipt float-start"></i>
                            <span class="count-numbers">
                            <?php echo Count_total_fines($connect); ?>
                            </span>
                            <span class="count-name">Total Fines Issued</span>
                        </div>
                    </div>

                    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                        <div class="card card-counter bg-returned status-card">
                            <i class="bi bi-wallet2 float-start"></i>
                            <span class="count-numbers">
                            <?php echo get_currency_symbol($connect) . Count_fines_paid_today($connect); ?>
                            </span>
                            <span class="count-name">Fines Paid Today</span>
                        </div>
                    </div>
                    </div>

    <?php if (isset($_GET["msg"])): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_GET["msg"]) && $_GET["msg"] == 'disable'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Fine Disabled',
                    text: 'The fine has been successfully disabled.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'enable'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Fine Enabled',
                    text: 'The fine has been successfully enabled.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'add'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Fine Added',
                    text: 'The fine was added successfully!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'edit'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Fine Updated',
                    text: 'The fine was updated successfully!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Done'
                });
            <?php elseif (isset($_GET["msg"]) && $_GET["msg"] == 'delete'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Fine Deleted',
                    text: 'The fine was successfully deleted or marked as deleted.',
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


<!-- Fines Management -->


<div class="d-flex justify-content-between mx-3 mb-3">
    <!-- Long Search Bar -->
    <div class="w-75 me-2">
        <input type="text" class="form-control" id="searchInput" placeholder="Search by User Name or Issue Book ID">
    </div>

    <!-- Short Dropdown Search Bar -->
    <div class="w-25">
        <select class="form-select" id="statusFilter">
            <option value="All">All</option>
            <option value="Paid">Paid</option>
            <option value="Unpaid">Unpaid</option>
        </select>
    </div>
</div>

    <div class="card-body mx-3" style="overflow-x: auto;">
        <table id="dataTable" class="display nowrap cell-border" style="width:100%">
            <thead>
                <tr>
                    <th>Fine ID</th>
                    <th>User Name</th>
                    <th>Issue Book ID</th>
                    <th>Expected Return Date</th>
                    <th>Return Date</th>
                    <th>Days Late</th>
                    <th>Fines Amount</th>
                    <th>Status</th>
                    <th>Created On</th>
                    <th>Updated On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($fines)): ?>
                    <?php foreach ($fines as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['fines_id']) ?></td>
                            <td><?= htmlspecialchars($row['user_name']) ?></td>
                            <td><?= htmlspecialchars($row['issue_book_id']) ?></td>
                            <td><?= htmlspecialchars($row['expected_return_date']) ?></td>
                            <td><?= htmlspecialchars($row['return_date']) ?></td>
                            <td><?= htmlspecialchars($row['days_late']) ?></td>
                            <td><?= htmlspecialchars($row['fines_amount']) ?></td>
                            <td>
                                <?php
                                    if ($row['fines_status'] === 'Paid') {
                                        echo '<span class="badge bg-success">Paid</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">Unpaid</span>';
                                    }
                                ?>
                            </td>
                            <td><?= date('Y-m-d H:i:s', strtotime($row['fines_created_on'])) ?></td>
                            <td><?= date('Y-m-d H:i:s', strtotime($row['fines_updated_on'])) ?></td>
                            <td class="text-center">
                                <a href="fines_action.php?action=view&code=<?= $row['fines_id'] ?>" class="btn btn-info btn-sm mb-1">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="fines_action.php?action=edit&code=<?= $row['fines_id'] ?>" class="btn btn-primary btn-sm mb-1">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-danger btn-sm delete-btn"
                                        data-id="<?= $row['fines_id'] ?>"
                                        data-status="<?= $row['fines_status'] ?>">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="11" class="text-center">No fines found!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>

<script>
// Function to disable a fine via confirm dialog (basic)
function delete_data(fineId) {
    if (confirm("Are you sure you want to disable this Fine?")) {
        window.location.href = "fines.php?action=delete&code=" + fineId + "&status=Disable";
    }
}

$(document).ready(function() {    
  $('#dataTable').DataTable({
    responsive: true,
    columnDefs: [
      { responsivePriority: 1, targets: [0, 1, 5] },
      { responsivePriority: 2, targets: [2, 3] }
    ],
    order: [[0, 'asc']],
    autoWidth: false,
    language: {
      emptyTable: "No fines available"
    },
    
    // Scroll and pagination settings
    scrollY: '400px',       // Vertical scroll
    scrollX: true,          // Horizontal scroll
    scrollCollapse: true,   // Collapse height when less data
    paging: false,
    searching: false           // Enable pagination
  });
});

// For deleting alert
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const fineId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const action = (currentStatus === 'Enable') ? 'disable' : 'enable';

            Swal.fire({
                title: `Are you sure you want to ${action} this fine?`,
                text: "This action can be reverted later.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: `Yes, ${action} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `fines.php?action=delete&status=${action === 'disable' ? 'Disable' : 'Enable'}&code=${fineId}`;
                }
            });
        });
    });
});
</script>

<script>
    // Remove query parameters from the URL (optional after showing alert)
    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('error');
        window.history.replaceState({}, document.title, url.pathname + url.search);
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
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
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const dataTable = document.getElementById('dataTable').getElementsByTagName('tbody')[0];

        // Function to filter rows based on search and status
        function filterTable() {
            const searchText = searchInput.value.toLowerCase();
            const selectedStatus = statusFilter.value;
            const rows = dataTable.getElementsByTagName('tr');

            for (let row of rows) {
                const userName = row.cells[1].textContent.toLowerCase();
                const issueId = row.cells[2].textContent.toLowerCase();
                const status = row.cells[7].textContent.trim();

                const matchesSearch = userName.includes(searchText) || issueId.includes(searchText);
                const matchesStatus = selectedStatus === 'All' || status === selectedStatus;

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        // Event listeners for filtering
        searchInput.addEventListener('keyup', filterTable);
        statusFilter.addEventListener('change', filterTable);
    });
</script>

