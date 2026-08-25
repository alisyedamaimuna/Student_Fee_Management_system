<?php

session_start();

require_once "config/db.php";

if (!isset($_SESSION['Role']) || $_SESSION['Role'] != 'staff') {
    header("Location: index.html");
    exit;
}

$message = "";
$messageType = "success";

if (isset($_GET['delete'])) {

    $paymentID = intval($_GET['delete']);

    $receiptStmt = $conn->prepare(
        "DELETE FROM receipt WHERE PaymentID = ?"
    );

    if ($receiptStmt) {
        $receiptStmt->bind_param("i", $paymentID);
        $receiptStmt->execute();
        $receiptStmt->close();
    }

    $deleteStmt = $conn->prepare(
        "DELETE FROM payment WHERE PaymentID = ?"
    );

    if ($deleteStmt) {

        $deleteStmt->bind_param("i", $paymentID);

        if ($deleteStmt->execute()) {
            $message = "Payment deleted successfully.";
            $messageType = "success";
        } else {
            $message = "Payment could not be deleted.";
            $messageType = "danger";
        }

        $deleteStmt->close();

    } else {

        $message = "Payment could not be deleted.";
        $messageType = "danger";
    }
}

if (isset($_POST['submit_payment'])) {

    $studentID = intval($_POST['student_id']);
    $month = $_POST['month'];
    $year = intval($_POST['year']);
    $amount = floatval($_POST['amount']);
    $staffID = intval($_SESSION['StaffID']);
    $status = "Complete";

    $studentCheck = $conn->prepare(
        "SELECT StudentID
         FROM student
         WHERE StudentID = ?"
    );

    $studentCheck->bind_param("i", $studentID);
    $studentCheck->execute();

    $studentResult = $studentCheck->get_result();

    if ($studentResult->num_rows == 0) {

        $message = "Student ID does not exist. Please enter a valid Student ID.";
        $messageType = "danger";

    } else {

        $checkStmt = $conn->prepare(
            "SELECT PaymentID
             FROM payment
             WHERE StudentID = ?
             AND Month = ?
             AND Year = ?"
        );

        $checkStmt->bind_param(
            "isi",
            $studentID,
            $month,
            $year
        );

        $checkStmt->execute();

        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {

            $message = "Payment already recorded for this student for "
                     . $month . " " . $year . ".";

            $messageType = "danger";

        } else {

            $idResult = $conn->query(
                "SELECT PaymentID
                 FROM payment
                 ORDER BY PaymentID ASC"
            );

            $nextPaymentID = 1;

            while ($idRow = $idResult->fetch_assoc()) {

                $currentID = intval($idRow['PaymentID']);

                if ($currentID == $nextPaymentID) {
                    $nextPaymentID++;
                } elseif ($currentID > $nextPaymentID) {
                    break;
                }
            }

            $paymentID = $nextPaymentID;

            $stmt = $conn->prepare(
                "INSERT INTO payment
                (PaymentID, StudentID, StaffID, Month, Year, Amount, Status)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "iiisids",
                $paymentID,
                $studentID,
                $staffID,
                $month,
                $year,
                $amount,
                $status
            );

            if ($stmt->execute()) {

                $receiptNumber = $paymentID;

                $receiptStmt = $conn->prepare(
                    "INSERT INTO receipt
                    (PaymentID, ReceiptNumber)
                    VALUES (?, ?)"
                );

                $receiptStmt->bind_param(
                    "ii",
                    $paymentID,
                    $receiptNumber
                );

                if ($receiptStmt->execute()) {

                    $message = "Payment recorded successfully. Receipt No: "
                             . $receiptNumber;

                    $messageType = "success";

                } else {

                    $message = "Payment recorded, but receipt could not be created.";
                    $messageType = "warning";
                }

                $receiptStmt->close();

            } else {

                $message = "Payment could not be recorded.";
                $messageType = "danger";
            }

            $stmt->close();
        }

        $checkStmt->close();
    }

    $studentCheck->close();
}

if (isset($_POST['update_payment'])) {

    $paymentID = intval($_POST['payment_id']);
    $studentID = intval($_POST['student_id']);
    $month = $_POST['month'];
    $year = intval($_POST['year']);
    $amount = floatval($_POST['amount']);
    $status = $_POST['status'];

    $allowedStatuses = [
        "Complete",
        "Pending",
        "Overdue"
    ];

    if (!in_array($status, $allowedStatuses)) {
        $status = "Complete";
    }

    $studentCheck = $conn->prepare(
        "SELECT StudentID
         FROM student
         WHERE StudentID = ?"
    );

    $studentCheck->bind_param("i", $studentID);
    $studentCheck->execute();

    $studentResult = $studentCheck->get_result();

    if ($studentResult->num_rows == 0) {

        $message = "Student ID does not exist. Please enter a valid Student ID.";
        $messageType = "danger";

    } else {

        $checkStmt = $conn->prepare(
            "SELECT PaymentID
             FROM payment
             WHERE StudentID = ?
             AND Month = ?
             AND Year = ?
             AND PaymentID != ?"
        );

        $checkStmt->bind_param(
            "isii",
            $studentID,
            $month,
            $year,
            $paymentID
        );

        $checkStmt->execute();

        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {

            $message = "Another payment already exists for this student for "
                     . $month . " " . $year . ".";

            $messageType = "danger";

        } else {

            $updateStmt = $conn->prepare(
                "UPDATE payment
                 SET StudentID = ?,
                     Month = ?,
                     Year = ?,
                     Amount = ?,
                     Status = ?
                 WHERE PaymentID = ?"
            );

            $updateStmt->bind_param(
                "isidsi",
                $studentID,
                $month,
                $year,
                $amount,
                $status,
                $paymentID
            );

            if ($updateStmt->execute()) {

                $message = "Payment updated successfully.";
                $messageType = "success";

            } else {

                $message = "Payment could not be updated.";
                $messageType = "danger";
            }

            $updateStmt->close();
        }

        $checkStmt->close();
    }

    $studentCheck->close();
}

$editPayment = null;

if (isset($_GET['edit'])) {

    $editID = intval($_GET['edit']);

    $editStmt = $conn->prepare(
        "SELECT
            PaymentID,
            StudentID,
            Month,
            Year,
            Amount,
            Status
         FROM payment
         WHERE PaymentID = ?"
    );

    $editStmt->bind_param("i", $editID);

    $editStmt->execute();

    $editResult = $editStmt->get_result();

    if ($editResult->num_rows == 1) {
        $editPayment = $editResult->fetch_assoc();
    }

    $editStmt->close();
}

$payments = $conn->query(
    "SELECT
        p.PaymentID,
        p.StudentID,
        s.Name AS StudentName,
        p.StaffID,
        p.Month,
        p.Year,
        p.Amount,
        p.Status,
        r.ReceiptNumber
     FROM payment p
     INNER JOIN student s
        ON p.StudentID = s.StudentID
     LEFT JOIN receipt r
        ON p.PaymentID = r.PaymentID
     ORDER BY p.PaymentID DESC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Fee Payment</title>

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

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2>Fee Payment</h2>

        <a href="admin-dashboard.php"
           class="btn btn-login">

            Dashboard

        </a>

    </div>

    <?php if ($message != ""): ?>

        <div class="alert alert-<?= htmlspecialchars($messageType) ?>">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>

    <div class="card mb-5">

        <div class="card-body">

            <h4 class="mb-4">

                <?= $editPayment
                    ? "Edit Student Fee Payment"
                    : "Record Student Fee Payment" ?>

            </h4>

            <form method="POST"
                  id="paymentForm">

                <?php if ($editPayment): ?>

                    <input type="hidden"
                           name="payment_id"
                           value="<?= $editPayment['PaymentID'] ?>">

                <?php endif; ?>

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Student ID
                        </label>

                        <input
                            type="number"
                            name="student_id"
                            class="form-control"
                            value="<?= $editPayment
                                ? htmlspecialchars($editPayment['StudentID'])
                                : '' ?>"
                            placeholder="Enter Student ID"
                            required>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Month
                        </label>

                        <select name="month"
                                class="form-select"
                                required>

                            <option value=""
                                    disabled
                                    <?= !$editPayment ? 'selected' : '' ?>>

                                Select Month

                            </option>

                            <?php

                            $months = [
                                "January",
                                "February",
                                "March",
                                "April",
                                "May",
                                "June",
                                "July",
                                "August",
                                "September",
                                "October",
                                "November",
                                "December"
                            ];

                            foreach ($months as $month):

                            ?>

                                <option
                                    value="<?= $month ?>"
                                    <?= (
                                        $editPayment &&
                                        $editPayment['Month'] == $month
                                    )
                                    ? 'selected'
                                    : '' ?>>

                                    <?= $month ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Year
                        </label>

                        <input
                            type="number"
                            name="year"
                            class="form-control"
                            value="<?= $editPayment
                                ? htmlspecialchars($editPayment['Year'])
                                : '2026' ?>"
                            min="2000"
                            max="2100"
                            required>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Amount
                        </label>

                        <input
                            type="number"
                            name="amount"
                            id="amount"
                            class="form-control"
                            step="0.01"
                            min="0.01"
                            value="<?= $editPayment
                                ? htmlspecialchars($editPayment['Amount'])
                                : '' ?>"
                            placeholder="Enter amount"
                            required>

                    </div>

                    <?php if ($editPayment): ?>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Payment Status
                            </label>

                            <select
                                name="status"
                                class="form-select"
                                required>

                                <option
                                    value="Complete"
                                    <?= $editPayment['Status'] == 'Complete'
                                        ? 'selected'
                                        : '' ?>>

                                    Complete

                                </option>

                                <option
                                    value="Pending"
                                    <?= $editPayment['Status'] == 'Pending'
                                        ? 'selected'
                                        : '' ?>>

                                    Pending

                                </option>

                                <option
                                    value="Overdue"
                                    <?= $editPayment['Status'] == 'Overdue'
                                        ? 'selected'
                                        : '' ?>>

                                    Overdue

                                </option>

                            </select>

                        </div>

                    <?php endif; ?>

                </div>

                <?php if ($editPayment): ?>

                    <button
                        type="submit"
                        name="update_payment"
                        class="btn btn-login">

                        Update Payment

                    </button>

                    <a href="payment.php"
                       class="btn btn-secondary">

                        Cancel

                    </a>

                <?php else: ?>

                    <button
                        type="submit"
                        name="submit_payment"
                        class="btn btn-login">

                        Record Payment

                    </button>

                <?php endif; ?>

            </form>

        </div>

    </div>

    <div class="card">

        <div class="card-body">

            <h4 class="mb-4">
                Payment Records
            </h4>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-light">

                        <tr>

                            <th>Payment ID</th>
                            <th>Student</th>
                            <th>Month</th>
                            <th>Year</th>
                            <th>Amount</th>
                            <th>Staff ID</th>
                            <th>Status</th>
                            <th>Receipt</th>
                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($payments && $payments->num_rows > 0): ?>

                            <?php while ($payment = $payments->fetch_assoc()): ?>

                                <tr>

                                    <td>
                                        <?= htmlspecialchars(
                                            $payment['PaymentID']
                                        ) ?>
                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $payment['StudentName']
                                        ) ?>

                                        <br>

                                        <small class="text-muted">

                                            ID:
                                            <?= htmlspecialchars(
                                                $payment['StudentID']
                                            ) ?>

                                        </small>

                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $payment['Month']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $payment['Year']
                                        ) ?>
                                    </td>

                                    <td>

                                        ৳<?= number_format(
                                            $payment['Amount'],
                                            2
                                        ) ?>

                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $payment['StaffID']
                                        ) ?>
                                    </td>

                                    <td>

                                        <?php

                                        $statusClass = "bg-success";

                                        if ($payment['Status'] == "Pending") {
                                            $statusClass = "bg-warning text-dark";
                                        }

                                        if ($payment['Status'] == "Overdue") {
                                            $statusClass = "bg-danger";
                                        }

                                        ?>

                                        <span class="badge <?= $statusClass ?>">

                                            <?= htmlspecialchars(
                                                $payment['Status']
                                            ) ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?php if ($payment['ReceiptNumber']): ?>

                                            <?= htmlspecialchars(
                                                $payment['ReceiptNumber']
                                            ) ?>

                                        <?php else: ?>

                                            <span class="text-danger">
                                                No Receipt
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <a
                                            href="payment.php?edit=<?= $payment['PaymentID'] ?>"
                                            class="btn btn-sm btn-warning">

                                            Edit

                                        </a>

                                        <a
                                            href="payment.php?delete=<?= $payment['PaymentID'] ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this payment?');">

                                            Delete

                                        </a>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="9"
                                    class="text-center">

                                    No payment records found.

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script src="js/script.js"></script>

</body>

</html>