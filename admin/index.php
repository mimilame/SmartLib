<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$root_path = $_SERVER['DOCUMENT_ROOT'] . '/SmartLib/';
include_once($root_path . 'database_connection.php');
include_once($root_path . 'function.php');

include_once($root_path . 'header.php');

?>

<main class="container py-4">
    <h1 class="mb-5">Dashboard</h1>
    
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h1 class="text-center"><?php echo Count_total_issue_book_number($connect); ?></h1>
                    <h5 class="text-center">Total Book Issued</h5>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h1 class="text-center"><?php echo Count_total_returned_book_number($connect); ?></h1>
                    <h5 class="text-center">Total Book Returned</h5>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <h1 class="text-center"><?php echo Count_total_not_returned_book_number($connect); ?></h1>
                    <h5 class="text-center">Total Book Not Returned</h5>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h1 class="text-center"><?php echo get_currency_symbol($connect) . Count_total_fines_received($connect); ?></h1>
                    <h5 class="text-center">Total Fines Received</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Dashboard -->
    <div class="row mt-4">
        <!-- Most Frequently Borrowed Books Chart -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">📚 Most Frequently Borrowed Books</h5>
                    <canvas id="topBooksChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Book Category Distribution Pie Chart -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">📚 Book Categories Distribution</h5>
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Chart.js for Analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Fetch top borrowed books data for the bar chart
    fetch("fetch_top_books.php")
        .then(response => response.json())
        .then(data => {
            const bookTitles = data.map(book => book.book_title);
            const borrowCounts = data.map(book => book.borrow_count);

            var ctxTopBooks = document.getElementById("topBooksChart").getContext("2d");

            new Chart(ctxTopBooks, {
                type: "bar",
                data: {
                    labels: bookTitles,
                    datasets: [{
                        label: "Number of Borrows",
                        data: borrowCounts,
                        backgroundColor: "rgba(54, 162, 235, 0.6)",
                        borderColor: "rgba(54, 162, 235, 1)",
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        })
        .catch(error => console.error("Error fetching top books data:", error));

    // Fetch category data for the pie chart
    fetch("category_data.php")
        .then(response => response.json())
        .then(data => {
            const labels = data.map(category => category.category_name);
            const counts = data.map(category => category.count);

            var ctxCategory = document.getElementById("categoryChart").getContext("2d");

            new Chart(ctxCategory, {
                type: "pie",
                data: {
                    labels: labels,
                    datasets: [{
                        data: counts,
                        backgroundColor: [
    "#007bff", "#ffc107", "#dc3545", "#28a745", "#17a2b8", 
    "#ff5733", "#33ff57", "#3357ff", "#ff33a8", "#33fff0",
    "#ff8c00", "#8a2be2", "#7fff00", "#ff1493", "#00ced1",
    "#ff6347", "#32cd32", "#8b0000", "#9400d3", "#ff4500",
    "#00ff7f", "#4682b4", "#da70d6", "#ff69b4", "#00bfff",
    "#b22222", "#ff00ff", "#ffb6c1", "#ff1493", "#ff7f50",
    "#ff8c00", "#ff4500", "#b0e0e6", "#7fffd4", "#dc143c",
    "#20b2aa", "#ff69b4", "#00ff7f", "#ffdab9", "#ba55d3"
]

                    }]
                },
                options: {
                    responsive: true
                }
            });
        })
        .catch(error => console.error("Error fetching category data:", error));
});
</script>
