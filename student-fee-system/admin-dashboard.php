<?php

session_start();

require_once "config/db.php";

if (!isset($_SESSION['Role']) || $_SESSION['Role'] != 'staff') {
    header("Location: index.html");
    exit;
}

$totalStudents = 0;
$totalPayments = 0;
$totalCollected = 0;
$totalReceipts = 0;
$pendingFees = 0;


/* Total Students */
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM student"
);

if ($result) {
    $row = $result->fetch_assoc();
    $totalStudents = $row['total'];
}


/* Total Payments */
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM payment"
);

if ($result) {
    $row = $result->fetch_assoc();
    $totalPayments = $row['total'];
}


/* Total Fees Collected */
$result = $conn->query(
    "SELECT COALESCE(SUM(Amount), 0) AS total
     FROM payment
     WHERE Status = 'Complete'"
);

if ($result) {
    $row = $result->fetch_assoc();
    $totalCollected = $row['total'];
}


/* Total Receipts */
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM receipt"
);

if ($result) {
    $row = $result->fetch_assoc();
    $totalReceipts = $row['total'];
}


/* Pending / Incomplete Payments */
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM payment
     WHERE Status != 'Complete'"
);

if ($result) {
    $row = $result->fetch_assoc();
    $pendingFees = $row['total'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - Student Fee Management System</title>

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet"
          href="css/style.css">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

</head>

<body>

<div class="row g-0">

    <div class="col-lg-2">

        <div class="sidebar">

            <div class="text-center py-4">

                <i class="bi bi-mortarboard-fill logo-icon"></i>

                <h5 class="text-white mt-2">
                    Student Fee<br>
                    Management System
                </h5>

            </div>


            <a href="admin-dashboard.php"
               class="menu-item active">

                <i class="bi bi-speedometer2"></i>
                Dashboard

            </a>


            <a href="students.php"
               class="menu-item">

                <i class="bi bi-people-fill"></i>
                Students

            </a>


            <a href="payment.php"
               class="menu-item">

                <i class="bi bi-credit-card-fill"></i>
                Fee Payment

            </a>


            <a href="receipts.php"
               class="menu-item">

                <i class="bi bi-receipt"></i>
                Receipts

            </a>


            <a href="reports.php"
               class="menu-item">

                <i class="bi bi-bar-chart-fill"></i>
                Reports

            </a>


            <a href="logout.php"
               class="menu-item">

                <i class="bi bi-box-arrow-right"></i>
                Logout

            </a>

        </div>

    </div>


    <div class="col-lg-10">

        <div class="p-4">

            <div class="topbar">

                <h3>
                    Dashboard
                </h3>

                <div class="admin-info">

                    <i class="bi bi-person-circle"></i>

                    <span>
                        Admin
                    </span>

                </div>

            </div>


            <p class="text-muted">

                Welcome back, Admin! Here's an overview of the
                Student Fee Management System.

            </p>


            <div class="row g-4 mt-2">


                <div class="col-lg-3 col-md-6">

                    <div class="dashboard-card">

                        <h5>
                            Total Students
                        </h5>

                        <h2>
                            <?= $totalStudents ?>
                        </h2>

                    </div>

                </div>


                <div class="col-lg-3 col-md-6">

                    <div class="dashboard-card">

                        <h5>
                            Fees Collected
                        </h5>

                        <h2>
                            ৳<?= number_format($totalCollected, 2) ?>
                        </h2>

                    </div>

                </div>


                <div class="col-lg-3 col-md-6">

                    <div class="dashboard-card">

                        <h5>
                            Pending Fees
                        </h5>

                        <h2>
                            <?= $pendingFees ?>
                        </h2>

                    </div>

                </div>


                <div class="col-lg-3 col-md-6">

                    <div class="dashboard-card">

                        <h5>
                            Receipts
                        </h5>

                        <h2>
                            <?= $totalReceipts ?>
                        </h2>

                    </div>

                </div>

            </div>


            <div class="row mt-4">

                <div class="col-lg-12">

                    <div class="card">

                        <div class="card-body">

                            <h5 class="mb-3">
                                Quick Actions
                            </h5>


                            <a href="students.php"
                               class="btn btn-login me-2">

                                <i class="bi bi-people-fill"></i>

                                Manage Students

                            </a>


                            <a href="payment.php"
                               class="btn btn-login">

                                <i class="bi bi-credit-card-fill"></i>

                                Record Fee Payment

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script src="js/script.js"></script>

</body>

</html>
```
