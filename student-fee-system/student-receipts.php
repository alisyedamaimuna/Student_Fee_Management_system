<?php

session_start();

require_once "config/db.php";

if (!isset($_SESSION['Role']) || $_SESSION['Role'] != 'student') {
    header("Location: index.html");
    exit;
}

$studentID = $_SESSION['StudentID'];

$stmt = $conn->prepare(
    "SELECT
        receipt.ReceiptID,
        receipt.ReceiptNumber,
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
     WHERE payment.StudentID = ?
     ORDER BY payment.PaymentDate DESC"
);

$stmt->bind_param("i", $studentID);
$stmt->execute();

$receipts = $stmt->get_result();

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Receipts</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">

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

                <a href="student-dashboard.php" class="menu-item">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>

                <a href="student-receipts.php" class="menu-item active">
                    <i class="bi bi-receipt"></i>
                    My Receipts
                </a>

                <a href="logout.php" class="menu-item">
                    <i class="bi bi-box-arrow-right"></i>
                    Logout
                </a>

            </div>

        </div>

        <div class="col-lg-10">

            <div class="p-4">

                <div class="topbar">

                    <h3>
                        My Receipts
                    </h3>

                    <div class="admin-info">

                        <i class="bi bi-person-circle"></i>

                        <span>
                            <?= htmlspecialchars($_SESSION['Name']) ?>
                        </span>

                    </div>

                </div>

                <p class="text-muted">
                    Your fee payment receipts are shown below.
                </p>

                <?php if ($receipts->num_rows > 0): ?>

                    <div class="row g-4 mt-2">

                        <?php while ($receipt = $receipts->fetch_assoc()): ?>

                            <div class="col-lg-6">

                                <div class="card h-100">

                                    <div class="card-body">

                                        <div class="d-flex justify-content-between align-items-center mb-3">

                                            <h5 class="mb-0">
                                                Receipt #<?= htmlspecialchars($receipt['ReceiptNumber']) ?>
                                            </h5>

                                            <span class="badge bg-success">
                                                <?= htmlspecialchars($receipt['Status']) ?>
                                            </span>

                                        </div>

                                        <hr>

                                        <p>
                                            <strong>Student:</strong>
                                            <?= htmlspecialchars($receipt['StudentName']) ?>
                                        </p>

                                        <p>
                                            <strong>Month:</strong>
                                            <?= htmlspecialchars($receipt['Month']) ?>
                                        </p>

                                        <p>
                                            <strong>Year:</strong>
                                            <?= htmlspecialchars($receipt['Year']) ?>
                                        </p>

                                        <p>
                                            <strong>Amount:</strong>
                                            ৳<?= number_format($receipt['Amount'], 2) ?>
                                        </p>

                                        <p>
                                            <strong>Payment Date:</strong>
                                            <?= htmlspecialchars($receipt['PaymentDate']) ?>
                                        </p>

                                        <hr>

                                        <div class="p-3 bg-light rounded">

                                            <h6>
                                                <i class="bi bi-person-check-fill"></i>
                                                Recorded By
                                            </h6>

                                            <p class="mb-1">

                                                <strong>
                                                    <?= htmlspecialchars($receipt['StaffName']) ?>
                                                </strong>

                                            </p>

                                            <p class="mb-0 text-muted">

                                                <?= htmlspecialchars($receipt['StaffEmail']) ?>

                                            </p>

                                        </div>

                                        <p class="text-muted mt-3 mb-0">

                                            Receipt Generated:
                                            <?= htmlspecialchars($receipt['GeneratedAt']) ?>

                                        </p>

                                    </div>

                                </div>

                            </div>

                        <?php endwhile; ?>

                    </div>

                <?php else: ?>

                    <div class="card mt-4">

                        <div class="card-body text-center py-5">

                            <i class="bi bi-receipt display-4"></i>

                            <h5 class="mt-3">
                                No Receipts Found
                            </h5>

                            <p class="text-muted">
                                You do not have any recorded fee payments yet.
                            </p>

                        </div>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <script src="js/script.js"></script>

</body>

</html>