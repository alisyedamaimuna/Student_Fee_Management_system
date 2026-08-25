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

$result = $conn->query("SELECT COUNT(*) AS total FROM student");

if ($result) {
    $row = $result->fetch_assoc();
    $totalStudents = $row['total'];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM payment");

if ($result) {
    $row = $result->fetch_assoc();
    $totalPayments = $row['total'];
}

$result = $conn->query(
    "SELECT COALESCE(SUM(Amount), 0) AS total
     FROM payment
     WHERE LOWER(Status) = 'complete'"
);

if ($result) {
    $row = $result->fetch_assoc();
    $totalCollected = $row['total'];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM receipt");

if ($result) {
    $row = $result->fetch_assoc();
    $totalReceipts = $row['total'];
}

$paymentResult = $conn->query(
    "SELECT
        payment.PaymentID,
        student.StudentID,
        student.Name AS StudentName,
        payment.Month,
        payment.Year,
        payment.Amount,
        payment.PaymentDate,
        payment.Status,
        staff.Name AS StaffName,
        receipt.ReceiptNumber
     FROM payment
     LEFT JOIN student
        ON payment.StudentID = student.StudentID
     LEFT JOIN staff
        ON payment.StaffID = staff.StaffID
     LEFT JOIN receipt
        ON payment.PaymentID = receipt.PaymentID
     ORDER BY payment.PaymentDate DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Reports</title>

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

            <a href="admin-dashboard.html"
               class="menu-item">

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
               class="menu-item active">

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
                    Reports
                </h3>

                <div class="admin-info">

                    <i class="bi bi-person-circle"></i>

                    <span>
                        Staff
                    </span>

                </div>

            </div>

            <div class="row g-4 mt-2">

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <i class="bi bi-people-fill"></i>

                        <h5>
                            Total Students
                        </h5>

                        <h3>
                            <?= $totalStudents ?>
                        </h3>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <i class="bi bi-credit-card-fill"></i>

                        <h5>
                            Total Payments
                        </h5>

                        <h3>
                            <?= $totalPayments ?>
                        </h3>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <i class="bi bi-cash-stack"></i>

                        <h5>
                            Total Collected
                        </h5>

                        <h3>
                            ৳<?= number_format($totalCollected, 2) ?>
                        </h3>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <i class="bi bi-receipt"></i>

                        <h5>
                            Total Receipts
                        </h5>

                        <h3>
                            <?= $totalReceipts ?>
                        </h3>

                    </div>

                </div>

            </div>

            <div class="card mt-4">

                <div class="card-body">

                    <h5 class="mb-4">
                        Payment Report
                    </h5>

                    <?php if ($paymentResult && $paymentResult->num_rows > 0): ?>

                        <div class="table-responsive">

                            <table class="table table-bordered table-hover align-middle">

                                <thead>

                                    <tr>

                                        <th>Student ID</th>
                                        <th>Student</th>
                                        <th>Month</th>
                                        <th>Year</th>
                                        <th>Amount</th>
                                        <th>Payment Date</th>
                                        <th>Status</th>
                                        <th>Receipt No.</th>
                                        <th>Recorded By</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php while ($payment = $paymentResult->fetch_assoc()): ?>

                                        <tr>

                                            <td>
                                                <?= htmlspecialchars($payment['StudentID'] ?? 'N/A') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($payment['StudentName'] ?? 'N/A') ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($payment['Month']) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($payment['Year']) ?>
                                            </td>

                                            <td>
                                                ৳<?= number_format($payment['Amount'], 2) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($payment['PaymentDate']) ?>
                                            </td>

                                            <td>

                                                <?php if (strtolower($payment['Status']) == 'complete'): ?>

                                                    <span class="badge bg-success">
                                                        <?= htmlspecialchars($payment['Status']) ?>
                                                    </span>

                                                <?php else: ?>

                                                    <span class="badge bg-warning text-dark">
                                                        <?= htmlspecialchars($payment['Status'] ?? 'Incomplete') ?>
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                            <td>

                                                <?php if ($payment['ReceiptNumber']): ?>

                                                    <?= htmlspecialchars($payment['ReceiptNumber']) ?>

                                                <?php else: ?>

                                                    <span class="text-muted">
                                                        No Receipt
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                            <td>
                                                <?= htmlspecialchars($payment['StaffName'] ?? 'N/A') ?>
                                            </td>

                                        </tr>

                                    <?php endwhile; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="text-center py-5">

                            <i class="bi bi-bar-chart display-4"></i>

                            <h5 class="mt-3">
                                No Payment Data
                            </h5>

                            <p class="text-muted">
                                No payment records are available yet.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script src="js/script.js"></script>

</body>

</html>