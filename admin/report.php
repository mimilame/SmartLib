<?php
// reports.php - Library Reports Page

include '../database_connection.php';
include '../function.php';
include '../header.php';

?>

<main class="container py-4" style="min-height: 700px;">

    <h1 class="my-3">Reports</h1>

    <!-- Tabs for Reports -->
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" href="#">Standard Reports</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#">Custom Reports</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#">Scheduled Reports</a>
        </li>
    </ul>

    <div class="row mt-4">
        <!-- Frequently Used Reports Section -->
        <div class="col-md-12">
            <h4>Frequently Used Reports</h4>
            <div class="row">

                <!-- Circulation Summary -->
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">📚 Circulation Summary</h5>
                            <p class="card-text">Total books issued, returned, and active checkouts.</p>
                            <p><small>Last generated: Today</small></p>
                            <a href="circulation_report.php" class="btn btn-primary btn-sm">View</a>
                        </div>
                    </div>
                </div>

                <!-- Member Activity -->
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">👥 Member Activity</h5>
                            <p class="card-text">Active members, new registrations, and engagement trends.</p>
                            <p><small>Last generated: Yesterday</small></p>
                            <a href="member_activity.php" class="btn btn-primary btn-sm">View</a>
                        </div>
                    </div>
                </div>

                <!-- Fine Collection -->
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">💰 Fine Collection</h5>
                            <p class="card-text">Fine collection summary and payment trends.</p>
                            <p><small>Last generated: 2 days ago</small></p>
                            <a href="fine_report.php" class="btn btn-primary btn-sm">View</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Analytics Dashboard -->
    <div class="mt-4">
        <h4>Analytics Dashboard</h4>

        <div class="card p-3">
            <h5>📊 Monthly Circulation Trends</h5>
            <p class="text-muted">[Bar Chart: Monthly checkouts and returns over time]</p>
            <!-- Placeholder for a real chart (e.g., using Chart.js) -->
            <canvas id="circulationChart"></canvas>
        </div>

    </div>

</main>

<!-- Chart.js for Analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var ctx = document.getElementById("circulationChart").getContext("2d");

    var circulationChart = new Chart(ctx, {
        type: "bar",
        data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
            datasets: [{
                label: "Books Issued",
                data: [120, 150, 180, 130, 170, 200],
                backgroundColor: "rgba(54, 162, 235, 0.5)"
            }, {
                label: "Books Returned",
                data: [100, 140, 160, 120, 160, 190],
                backgroundColor: "rgba(75, 192, 192, 0.5)"
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>


