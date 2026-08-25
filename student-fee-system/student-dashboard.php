<?php

session_start();

require_once "config/db.php";

if (!isset($_SESSION['Role']) || $_SESSION['Role'] != 'student') {
    header("Location: index.html");
    exit;
}

$studentID = $_SESSION['StudentID'];

$stmt = $conn->prepare(
    "SELECT StudentID, Name, Email, Phone, Semester, DepartmentID
     FROM student
     WHERE StudentID = ?"
);

$stmt->bind_param("i", $studentID);
$stmt->execute();

$result = $stmt->get_result();
$student = $result->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Dashboard</title>

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

                <a href="student-dashboard.php" class="menu-item active">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>

                <a href="student-receipts.php" class="menu-item">
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
                        Student Dashboard
                    </h3>

                    <div class="admin-info">

                        <i class="bi bi-person-circle"></i>

                        <span>
                            <?= htmlspecialchars($student['Name']) ?>
                        </span>

                    </div>

                </div>

                <div class="card mt-4">

                    <div class="card-body">

                        <h4>
                            Welcome, <?= htmlspecialchars($student['Name']) ?>!
                        </h4>

                        <p class="text-muted">
                            Welcome to your Student Fee Management System dashboard.
                        </p>

                    </div>

                </div>

                <div class="row g-4 mt-2">

                    <div class="col-md-6">

                        <div class="dashboard-card">

                            <i class="bi bi-person-fill"></i>

                            <h5>
                                Student Name
                            </h5>

                            <h4>
                                <?= htmlspecialchars($student['Name']) ?>
                            </h4>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="dashboard-card">

                            <i class="bi bi-envelope-fill"></i>

                            <h5>
                                Email
                            </h5>

                            <h6>
                                <?= htmlspecialchars($student['Email']) ?>
                            </h6>

                        </div>

                    </div>

                </div>

                <div class="card mt-4">

                    <div class="card-body">

                        <h5>
                            Fee Receipts
                        </h5>

                        <p class="text-muted">
                            View your payment receipts and see which staff member recorded each payment.
                        </p>

                        <a href="student-receipts.php" class="btn btn-login">

                            <i class="bi bi-receipt"></i>

                            View My Receipts

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <script src="js/script.js"></script>

</body>

</html>