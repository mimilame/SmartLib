<?php
// fines.php

include '../database_connection.php';
include '../function.php';
include '../header.php';
authenticate_admin();
$message = '';

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

<h1 class="my-3">Fines Management</h1>
	<div class="row mb-4">
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-counter bg-warning shadow-sm hover-shadow translate-hover rounded transition">
                <i class="bi bi-exclamation-circle float-start"></i>
                <span class="count-numbers">
                <?php echo get_currency_symbol($connect) . Count_total_fines_outstanding($connect); ?>
                </span>
                <span class="count-name">Total Outstanding</span>
            </div>
        </div>


        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-counter bg-danger shadow-sm hover-shadow translate-hover rounded transition">
                <i class="bi bi-cash-coin float-start"></i>
                <span class="count-numbers">
                <?php echo get_currency_symbol($connect) . Count_total_fines_received($connect); ?>
                </span>
                <span class="count-name">Collected This Month</span>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
            <div class="card card-counter bg-primary shadow-sm hover-shadow translate-hover rounded transition">
                <i class="bi bi-receipt float-start"></i>
                <span class="count-numbers">
                <?php echo Count_total_fines($connect); ?>
                </span>
                <span class="count-name">Total Fines Issued</span>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
            <div class="card card-counter bg-success shadow-sm hover-shadow translate-hover rounded transition">
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
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center mb-3">
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
            <table id="dataTable" class="display nowrap" style="width:100%">
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
                                <td><?= date('M d, Y', strtotime($row['expected_return_date'])) ?></td>
                                <td><?= date('M d, Y', strtotime($row['return_date'])) ?></td>
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
                                <td><?= date('M d, Y', strtotime($row['fines_created_on'])) ?></td>
                                <td><?= date('M d, Y', strtotime($row['fines_updated_on'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="fines_action.php?action=view&code=<?= $row['fines_id'] ?>" class="btn btn-info btn-sm mb-1">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="fines_action.php?action=edit&code=<?= $row['fines_id'] ?>" class="btn btn-primary btn-sm mb-1">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>



<script>

    $(document).ready(function() {    
        const table = $('#dataTable').DataTable({
            responsive: true,
            columnDefs: [
            { responsivePriority: 1, targets: [0, 1, 5, 10] },
            { responsivePriority: 2, targets: [2, 3] }
            ],
            order: [[0, 'asc']],
            autoWidth: false,
            language: {
            emptyTable: "No fines available"
            },
            
            // Scroll and pagination settings
            info: true,
            paging: true, 
            scrollY: '500px',       // Vertical scroll
            scrollCollapse: true,   // Collapse height when less data
            searching: false,          // Enable pagination
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

<?php
include '../footer.php';
?>