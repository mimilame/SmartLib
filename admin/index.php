<?php
// fines.php

include '../database_connection.php';
include '../function.php';
include '../header.php';

$message = '';

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
            .card {
                transition: all 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    </style>
</head>

<main class="container py-4">
    <h1 class="mb-5">Dashboard</h1>
    
    <div class="row">

    <div class="col-xl-3 col-md-6">
    <div class="card text-white bg-primary shadow-sm p-3 mb-4">
        <div class="card-body d-flex align-items-center justify-content-between">
            <div>
                <h2 class="mb-0"><?php echo Count_total_issue_book_number($connect); ?></h2>
                <h6 class="mb-0">Total Books Issued</h6>
            </div>
            <i class="bi bi-book fa-3x"></i>
        </div>
    </div>
</div>

<div class="col-xl-3 col-md-6">
    <div class="card text-white bg-warning shadow-sm p-3 mb-4">
        <div class="card-body d-flex align-items-center justify-content-between">
            <div>
                <h2 class="mb-0"><?php echo Count_total_returned_book_number($connect); ?></h2>
                <h6 class="mb-0">Total Books Returned</h6>
            </div>
            <i class="bi bi-arrow-return-left fa-3x"></i>
        </div>
    </div>
</div>

<div class="col-xl-3 col-md-6">
    <div class="card text-white bg-danger shadow-sm p-3 mb-4">
        <div class="card-body d-flex align-items-center justify-content-between">
            <div>
                <h2 class="mb-0"><?php echo Count_total_not_returned_book_number($connect); ?></h2>
                <h6 class="mb-0">Total Books Not Returned</h6>
            </div>
            <i class="bi bi-exclamation-circle fa-3x"></i>
        </div>
    </div>
</div>

<div class="col-xl-3 col-md-6">
    <div class="card text-white bg-success shadow-sm p-3 mb-4">
        <div class="card-body d-flex align-items-center justify-content-between">
            <div>
                <h2 class="mb-0"><?php echo get_currency_symbol($connect) . Count_total_fines_received($connect); ?></h2>
                <h6 class="mb-0">Total Fines Received</h6>
            </div>
            <i class="bi bi-cash-stack fa-3x"></i>
        </div>
    </div>
</div>

</div>

<!-- Analytics Dashboard -->
<div class="row mt-4">
    <!-- Most Frequently Borrowed Books Chart -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title">📚 Most Frequently Borrowed Books</h5>
                <canvas id="topBooksChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Book Category Distribution Pie Chart -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
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
    // Fetch top issued books data for the bar & line chart (Mixed Chart)
    fetch("fetch_top_books.php")
        .then(response => response.json())
        .then(data => {
            const bookIds = data.map(book => book.book_id);
            const bookNames = data.map(book => book.book_name); // Fetch book names as well
            const issueCounts = data.map(book => book.issue_count);

            var ctxTopBooks = document.getElementById("topBooksChart").getContext("2d");

            new Chart(ctxTopBooks, {
                type: "bar",
                data: {
                    labels: bookIds, // Display book IDs on the x-axis
                    datasets: [
                        {
                            type: 'line',
                            label: "Trend Line",
                            data: issueCounts,
                            borderColor: "#ff6b6b",
                            borderWidth: 2,
                            fill: false,
                            tension: 0.3,
                        },
                        {
                            type: 'bar',
                            label: "Number of Issues",
                            data: issueCounts,
                            backgroundColor: "rgba(54, 162, 235, 0.6)",
                            borderColor: "rgba(54, 162, 235, 1)",
                            borderWidth: 1,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const index = context.dataIndex;
                                    const bookName = bookNames[index]; // Show the actual book name
                                    const issueCount = issueCounts[index];
                                    if (context.dataset.type === 'line') {
                                        return `Trend (${bookName}): ${issueCount} Issues`;
                                    }
                                    return `${bookName}: ${issueCount} Issues`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        })
        .catch(error => console.error("Error fetching top issued books data:", error));

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
                            "#ff5733", "#33ff57", "#3357ff", "#ff33a8", "#33fff0"
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'right' }
                    }
                }
            });
        })
        .catch(error => console.error("Error fetching category data:", error));
});
</script>

