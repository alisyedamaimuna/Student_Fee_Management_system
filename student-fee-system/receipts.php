<?php

session_start();

require_once "config/db.php";

if (!isset($_SESSION['Role']) || $_SESSION['Role'] != 'staff') {
    header("Location: index.html");
    exit;
}

$sql = "SELECT
            receipt.ReceiptID,
            payment.PaymentID AS ReceiptNumber,
            receipt.GeneratedAt,
            payment.PaymentID,
            payment.Month,
            payment.Year,
            payment.Amount,
            payment.PaymentDate,
            payment.Status,
            student.Name AS StudentName,
            staff.Name AS StaffName,
            staff.Email AS StaffEmail
        FROM receipt
        INNER JOIN payment
            ON receipt.PaymentID = payment.PaymentID
        INNER JOIN student
            ON payment.StudentID = student.StudentID
        INNER JOIN staff
            ON payment.StaffID = staff.StaffID
        ORDER BY payment.PaymentID DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Receipts</title>

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
                   class="menu-item active">

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
                        Receipts
                    </h3>

                    <div class="admin-info">

                        <i class="bi bi-person-circle"></i>

                        <span>
                            Staff
                        </span>

                    </div>

                </div>

                <div class="card mt-4">

                    <div class="card-body">

                        <h5 class="mb-4">
                            Payment Receipts
                        </h5>

                        <?php if ($result && $result->num_rows > 0): ?>

                            <div class="table-responsive">

                                <table class="table table-bordered table-hover align-middle">

                                    <thead>

                                        <tr>

                                            <th>Receipt No.</th>

                                            <th>Student</th>

                                            <th>Month</th>

                                            <th>Year</th>

                                            <th>Amount</th>

                                            <th>Payment Date</th>

                                            <th>Status</th>

                                            <th>Recorded By</th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                        <?php while ($row = $result->fetch_assoc()): ?>

                                            <tr>

                                                <td>

                                                    <strong>
                                                        <?= htmlspecialchars(
                                                            $row['ReceiptNumber']
                                                        ) ?>
                                                    </strong>

                                                </td>

                                                <td>

                                                    <?= htmlspecialchars(
                                                        $row['StudentName']
                                                    ) ?>

                                                </td>

                                                <td>

                                                    <?= htmlspecialchars(
                                                        $row['Month']
                                                    ) ?>

                                                </td>

                                                <td>

                                                    <?= htmlspecialchars(
                                                        $row['Year']
                                                    ) ?>

                                                </td>

                                                <td>

                                                    ৳<?= number_format(
                                                        $row['Amount'],
                                                        2
                                                    ) ?>

                                                </td>

                                                <td>

                                                    <?= htmlspecialchars(
                                                        $row['PaymentDate']
                                                    ) ?>

                                                </td>

                                                <td>

                                                    <?php

                                                    $statusClass = "bg-success";

                                                    if ($row['Status'] == "Pending") {
                                                        $statusClass = "bg-warning text-dark";
                                                    }

                                                    if ($row['Status'] == "Overdue") {
                                                        $statusClass = "bg-danger";
                                                    }

                                                    ?>

                                                    <span class="badge <?= $statusClass ?>">

                                                        <?= htmlspecialchars(
                                                            $row['Status']
                                                        ) ?>

                                                    </span>

                                                </td>

                                                <td>

                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            $row['StaffName']
                                                        ) ?>

                                                    </strong>

                                                    <br>

                                                    <small class="text-muted">

                                                        <?= htmlspecialchars(
                                                            $row['StaffEmail']
                                                        ) ?>

                                                    </small>

                                                </td>

                                            </tr>

                                        <?php endwhile; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php else: ?>

                            <div class="text-center py-5">

                                <i class="bi bi-receipt display-4"></i>

                                <h5 class="mt-3">
                                    No Receipts Found
                                </h5>

                                <p class="text-muted">

                                    No payment receipts have been recorded yet.

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