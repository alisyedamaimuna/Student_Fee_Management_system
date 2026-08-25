<?php

session_start();

require_once "config/db.php";

if (!isset($_SESSION['Role']) || $_SESSION['Role'] != 'staff') {
    header("Location: index.html");
    exit;
}

$message = "";
$error = "";

$paymentSearch = null;
$searchedStudentID = "";


/* =========================
   SEARCH STUDENT BY ID
   ========================= */

if (isset($_POST['search_payment'])) {

    $searchedStudentID = trim($_POST['search_student_id']);

    if ($searchedStudentID == "") {

        $error = "Please enter a Student ID.";

    } else {

        $stmt = $conn->prepare(
            "SELECT
                s.StudentID,
                s.Name,
                p.PaymentID,
                p.Month,
                p.Year,
                p.Amount,
                p.PaymentDate,
                p.Status,
                r.ReceiptNumber,
                r.GeneratedAt
             FROM student s
             LEFT JOIN payment p
                ON s.StudentID = p.StudentID
             LEFT JOIN receipt r
                ON p.PaymentID = r.PaymentID
             WHERE s.StudentID = ?
             ORDER BY p.PaymentDate DESC, p.PaymentID DESC
             LIMIT 1"
        );

        $stmt->bind_param("i", $searchedStudentID);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $paymentSearch = $result->fetch_assoc();

        } else {

            $error = "No student found with Student ID "
                   . htmlspecialchars($searchedStudentID)
                   . ".";
        }

        $stmt->close();
    }
}


/* =========================
   ADD STUDENT
   ========================= */

if (isset($_POST['add_student'])) {

    $studentID = intval($_POST['student_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $semester = trim($_POST['semester']);
    $department = intval($_POST['department']);
    $password = trim($_POST['password']);

    if ($password == "") {

        $error = "Please enter a password for the student.";

    } else {

        /* Check Student ID */

        $check = $conn->prepare(
            "SELECT StudentID
             FROM student
             WHERE StudentID = ?"
        );

        $check->bind_param("i", $studentID);

        $check->execute();

        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {

            $error = "Student ID already exists. Please enter a different ID.";

        } else {

            /* Check email in UserAccount through student/staff relationships */

            $emailCheck = $conn->prepare(
                "SELECT UserID
                 FROM UserAccount
                 WHERE StudentID IS NOT NULL
                 AND StudentID IN (
                     SELECT StudentID
                     FROM student
                     WHERE Email = ?
                 )"
            );

            $emailCheck->bind_param("s", $email);

            $emailCheck->execute();

            $emailResult = $emailCheck->get_result();

            if ($emailResult->num_rows > 0) {

                $error = "A student account with this email already exists.";

                $emailCheck->close();

            } else {

                $emailCheck->close();

                /* Start transaction */

                $conn->begin_transaction();

                try {

                    /* Insert student */

                    $stmt = $conn->prepare(
                        "INSERT INTO student
                        (
                            StudentID,
                            Name,
                            Email,
                            Phone,
                            Semester,
                            DepartmentID
                        )
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->bind_param(
                        "issssi",
                        $studentID,
                        $name,
                        $email,
                        $phone,
                        $semester,
                        $department
                    );

                    if (!$stmt->execute()) {
                        throw new Exception("Student could not be added.");
                    }

                    $stmt->close();


                    /* Create UserAccount */

                    $userName = $name;
                    $role = "user";

                    $accountStmt = $conn->prepare(
                        "INSERT INTO UserAccount
                        (
                            UserName,
                            StaffID,
                            Role,
                            Password,
                            StudentID
                        )
                        VALUES (?, NULL, ?, ?, ?)"
                    );

                    $accountStmt->bind_param(
                        "sssi",
                        $userName,
                        $role,
                        $password,
                        $studentID
                    );

                    if (!$accountStmt->execute()) {
                        throw new Exception(
                            "Student was added, but the login account could not be created."
                        );
                    }

                    $accountStmt->close();


                    /* Commit */

                    $conn->commit();

                    $message = "Student added successfully. Student login account created.";

                } catch (Exception $e) {

                    $conn->rollback();

                    $error = $e->getMessage();
                }
            }
        }

        $check->close();
    }
}


/* =========================
   UPDATE STUDENT
   ========================= */

if (isset($_POST['update_student'])) {

    $oldStudentID = intval($_POST['old_student_id']);
    $newStudentID = intval($_POST['student_id']);

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $semester = trim($_POST['semester']);
    $department = intval($_POST['department']);

    $password = trim($_POST['password'] ?? "");


    /* Check if new Student ID belongs to another student */

    if ($oldStudentID != $newStudentID) {

        $check = $conn->prepare(
            "SELECT StudentID
             FROM student
             WHERE StudentID = ?"
        );

        $check->bind_param("i", $newStudentID);

        $check->execute();

        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {

            $error = "That Student ID already exists.";

        }

        $check->close();
    }


    if ($error == "") {

        $conn->begin_transaction();

        try {

            /* Update student information */

            $stmt = $conn->prepare(
                "UPDATE student
                 SET StudentID = ?,
                     Name = ?,
                     Email = ?,
                     Phone = ?,
                     Semester = ?,
                     DepartmentID = ?
                 WHERE StudentID = ?"
            );

            $stmt->bind_param(
                "issssii",
                $newStudentID,
                $name,
                $email,
                $phone,
                $semester,
                $department,
                $oldStudentID
            );

            if (!$stmt->execute()) {

                throw new Exception(
                    "Student information could not be updated."
                );
            }

            $stmt->close();


            /*
             * Update UserAccount.
             *
             * StudentID is also changed if the Student ID was changed.
             */

            if ($password != "") {

                $accountStmt = $conn->prepare(
                    "UPDATE UserAccount
                     SET UserName = ?,
                         Password = ?,
                         StudentID = ?
                     WHERE StudentID = ?"
                );

                $accountStmt->bind_param(
                    "ssii",
                    $name,
                    $password,
                    $newStudentID,
                    $oldStudentID
                );

            } else {

                $accountStmt = $conn->prepare(
                    "UPDATE UserAccount
                     SET UserName = ?,
                         StudentID = ?
                     WHERE StudentID = ?"
                );

                $accountStmt->bind_param(
                    "sii",
                    $name,
                    $newStudentID,
                    $oldStudentID
                );
            }


            if (!$accountStmt->execute()) {

                throw new Exception(
                    "Student was updated, but the login account could not be updated."
                );
            }

            $accountStmt->close();


            /* Commit */

            $conn->commit();

            $message = "Student information and login account updated successfully.";

        } catch (Exception $e) {

            $conn->rollback();

            $error = $e->getMessage();
        }
    }
}


/* =========================
   DELETE STUDENT
   ========================= */

if (isset($_GET['delete'])) {

    $studentID = intval($_GET['delete']);

    /* Check whether payment records exist */

    $paymentCheck = $conn->prepare(
        "SELECT PaymentID
         FROM payment
         WHERE StudentID = ?
         LIMIT 1"
    );

    $paymentCheck->bind_param("i", $studentID);
    $paymentCheck->execute();

    $paymentResult = $paymentCheck->get_result();

    if ($paymentResult->num_rows > 0) {

        $error = "This student cannot be deleted because payment records are linked to this student.";

        $paymentCheck->close();

    } else {

        $paymentCheck->close();

        $conn->begin_transaction();

        try {

            /* Delete student login account */

            $accountStmt = $conn->prepare(
                "DELETE FROM UserAccount
                 WHERE StudentID = ?"
            );

            $accountStmt->bind_param("i", $studentID);

            if (!$accountStmt->execute()) {

                throw new Exception(
                    "Student login account could not be deleted."
                );
            }

            $accountStmt->close();


            /* Delete student */

            $studentStmt = $conn->prepare(
                "DELETE FROM student
                 WHERE StudentID = ?"
            );

            $studentStmt->bind_param("i", $studentID);

            if (!$studentStmt->execute()) {

                throw new Exception(
                    "Student could not be deleted."
                );
            }

            $studentStmt->close();


            $conn->commit();

            $message = "Student and student login account deleted successfully.";

        } catch (Exception $e) {

            $conn->rollback();

            $error = "Student could not be deleted.";
        }
    }
}


/* =========================
   EDIT STUDENT
   ========================= */

$editStudent = null;

if (isset($_GET['edit'])) {

    $studentID = intval($_GET['edit']);

    $stmt = $conn->prepare(
        "SELECT
            StudentID,
            Name,
            Email,
            Phone,
            Semester,
            DepartmentID
         FROM student
         WHERE StudentID = ?"
    );

    $stmt->bind_param("i", $studentID);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $editStudent = $result->fetch_assoc();
    }

    $stmt->close();
}


/* =========================
   GET ALL STUDENTS
   ========================= */

$students = $conn->query(
    "SELECT
        student.StudentID,
        student.Name,
        student.Email,
        student.Phone,
        student.Semester,
        department.DepartmentName
     FROM student
     LEFT JOIN department
        ON student.DepartmentID = department.DepartmentID
     ORDER BY student.StudentID ASC"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Students</title>

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

    <!-- SIDEBAR -->

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
               class="menu-item">

                <i class="bi bi-speedometer2"></i>
                Dashboard

            </a>

            <a href="students.php"
               class="menu-item active">

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


    <!-- MAIN CONTENT -->

    <div class="col-lg-10">

        <div class="p-4">

            <!-- TOP BAR -->

            <div class="topbar">

                <h3>
                    Students
                </h3>

                <div class="admin-info">

                    <i class="bi bi-person-circle"></i>

                    <span>
                        Staff
                    </span>

                </div>

            </div>


            <!-- SUCCESS MESSAGE -->

            <?php if ($message != ""): ?>

                <div class="alert alert-success mt-4">

                    <?= htmlspecialchars($message) ?>

                </div>

            <?php endif; ?>


            <!-- ERROR MESSAGE -->

            <?php if ($error != ""): ?>

                <div class="alert alert-danger mt-4">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <!-- =========================
                 STUDENT ID SEARCH
                 ========================= -->

            <div class="card mt-4">

                <div class="card-body">

                    <h5 class="mb-2">

                        <i class="bi bi-search"></i>

                        Search Student Payment & Receipt

                    </h5>

                    <p class="text-muted">

                        Enter a Student ID to see the student's
                        most recent payment and receipt.

                    </p>


                    <form method="POST">

                        <div class="row g-3 align-items-end">

                            <div class="col-md-6">

                                <label class="form-label">
                                    Student ID
                                </label>

                                <input type="number"
                                       name="search_student_id"
                                       class="form-control"
                                       placeholder="Enter Student ID"
                                       value="<?= htmlspecialchars($searchedStudentID) ?>"
                                       required>

                            </div>


                            <div class="col-md-3">

                                <button type="submit"
                                        name="search_payment"
                                        class="btn btn-login w-100">

                                    <i class="bi bi-search"></i>

                                    Search

                                </button>

                            </div>


                            <div class="col-md-3">

                                <a href="students.php"
                                   class="btn btn-secondary w-100">

                                    <i class="bi bi-x-circle"></i>

                                    Clear

                                </a>

                            </div>

                        </div>

                    </form>


                    <!-- SEARCH RESULT -->

                    <?php if ($paymentSearch): ?>

                        <hr class="my-4">

                        <h5 class="mb-3">
                            Latest Payment / Receipt
                        </h5>


                        <div class="table-responsive">

                            <table class="table table-bordered align-middle">

                                <tbody>

                                    <tr>

                                        <th style="width: 35%;">
                                            Student ID
                                        </th>

                                        <td>
                                            <?= htmlspecialchars(
                                                $paymentSearch['StudentID']
                                            ) ?>
                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Student Name
                                        </th>

                                        <td>
                                            <?= htmlspecialchars(
                                                $paymentSearch['Name']
                                            ) ?>
                                        </td>

                                    </tr>


                                    <?php if ($paymentSearch['PaymentID']): ?>

                                        <tr>

                                            <th>
                                                Payment ID
                                            </th>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $paymentSearch['PaymentID']
                                                ) ?>
                                            </td>

                                        </tr>


                                        <tr>

                                            <th>
                                                Payment Month
                                            </th>

                                            <td>

                                                <?= htmlspecialchars(
                                                    $paymentSearch['Month']
                                                ) ?>

                                                <?= htmlspecialchars(
                                                    $paymentSearch['Year']
                                                ) ?>

                                            </td>

                                        </tr>


                                        <tr>

                                            <th>
                                                Amount
                                            </th>

                                            <td>

                                                <strong>
                                                    ৳<?= number_format(
                                                        $paymentSearch['Amount'],
                                                        2
                                                    ) ?>
                                                </strong>

                                            </td>

                                        </tr>


                                        <tr>

                                            <th>
                                                Payment Date
                                            </th>

                                            <td>
                                                <?= htmlspecialchars(
                                                    $paymentSearch['PaymentDate']
                                                ) ?>
                                            </td>

                                        </tr>


                                        <tr>

                                            <th>
                                                Payment Status
                                            </th>

                                            <td>

                                                <?php
                                                $status = strtolower(
                                                    $paymentSearch['Status'] ?? ''
                                                );
                                                ?>

                                                <?php if (
                                                    $status == 'paid' ||
                                                    $status == 'complete'
                                                ): ?>

                                                    <span class="badge bg-success">

                                                        <?= htmlspecialchars(
                                                            $paymentSearch['Status']
                                                        ) ?>

                                                    </span>

                                                <?php elseif ($status == 'pending'): ?>

                                                    <span class="badge bg-warning text-dark">

                                                        <?= htmlspecialchars(
                                                            $paymentSearch['Status']
                                                        ) ?>

                                                    </span>

                                                <?php else: ?>

                                                    <span class="badge bg-danger">

                                                        <?= htmlspecialchars(
                                                            $paymentSearch['Status'] ?? 'Unknown'
                                                        ) ?>

                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                        </tr>


                                        <tr>

                                            <th>
                                                Receipt Number
                                            </th>

                                            <td>

                                                <?php if (
                                                    !empty(
                                                        $paymentSearch['ReceiptNumber']
                                                    )
                                                ): ?>

                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            $paymentSearch['ReceiptNumber']
                                                        ) ?>

                                                    </strong>

                                                <?php else: ?>

                                                    <span class="text-muted">
                                                        No receipt available
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                        </tr>


                                        <tr>

                                            <th>
                                                Receipt Generated
                                            </th>

                                            <td>

                                                <?php if (
                                                    !empty(
                                                        $paymentSearch['GeneratedAt']
                                                    )
                                                ): ?>

                                                    <?= htmlspecialchars(
                                                        $paymentSearch['GeneratedAt']
                                                    ) ?>

                                                <?php else: ?>

                                                    <span class="text-muted">
                                                        No receipt generated
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                        </tr>


                                    <?php else: ?>

                                        <tr>

                                            <th>
                                                Payment
                                            </th>

                                            <td>

                                                <span class="badge bg-warning text-dark">
                                                    No payment found
                                                </span>

                                            </td>

                                        </tr>


                                        <tr>

                                            <th>
                                                Receipt
                                            </th>

                                            <td>

                                                <span class="text-muted">
                                                    No receipt available
                                                </span>

                                            </td>

                                        </tr>

                                    <?php endif; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =========================
                 ADD / EDIT STUDENT
                 ========================= -->

            <div class="card mt-4">

                <div class="card-body">

                    <?php if ($editStudent): ?>

                        <h5 class="mb-4">
                            Edit Student
                        </h5>


                        <form method="POST">

                            <input type="hidden"
                                   name="old_student_id"
                                   value="<?= htmlspecialchars(
                                       $editStudent['StudentID']
                                   ) ?>">


                            <div class="row g-3">

                                <div class="col-md-4">

                                    <label class="form-label">
                                        Student ID
                                    </label>

                                    <input type="number"
                                           name="student_id"
                                           class="form-control"
                                           value="<?= htmlspecialchars(
                                               $editStudent['StudentID']
                                           ) ?>"
                                           required>

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Name
                                    </label>

                                    <input type="text"
                                           name="name"
                                           class="form-control"
                                           value="<?= htmlspecialchars(
                                               $editStudent['Name']
                                           ) ?>"
                                           required>

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Email
                                    </label>

                                    <input type="email"
                                           name="email"
                                           class="form-control"
                                           value="<?= htmlspecialchars(
                                               $editStudent['Email']
                                           ) ?>"
                                           required>

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Phone
                                    </label>

                                    <input type="text"
                                           name="phone"
                                           class="form-control"
                                           value="<?= htmlspecialchars(
                                               $editStudent['Phone']
                                           ) ?>"
                                           required>

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Semester
                                    </label>

                                    <input type="text"
                                           name="semester"
                                           class="form-control"
                                           value="<?= htmlspecialchars(
                                               $editStudent['Semester']
                                           ) ?>"
                                           required>

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Department
                                    </label>

                                    <select name="department"
                                            class="form-select"
                                            required>

                                        <option value="1"
                                            <?= $editStudent['DepartmentID'] == 1
                                                ? 'selected'
                                                : '' ?>>

                                            CSE

                                        </option>

                                        <option value="2"
                                            <?= $editStudent['DepartmentID'] == 2
                                                ? 'selected'
                                                : '' ?>>

                                            EEE

                                        </option>

                                    </select>

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        New Password
                                    </label>

                                    <input type="password"
                                           name="password"
                                           class="form-control"
                                           placeholder="Leave blank to keep current password">

                                    <small class="text-muted">
                                        Enter a password only if you want to change the student's login password.
                                    </small>

                                </div>

                            </div>


                            <div class="mt-4">

                                <button type="submit"
                                        name="update_student"
                                        class="btn btn-login">

                                    <i class="bi bi-check-circle"></i>

                                    Update Student

                                </button>


                                <a href="students.php"
                                   class="btn btn-secondary">

                                    Cancel

                                </a>

                            </div>

                        </form>


                    <?php else: ?>


                        <h5 class="mb-4">
                            Add Student
                        </h5>


                        <form method="POST">

                            <div class="row g-3">

                                <div class="col-md-4">

                                    <label class="form-label">
                                        Student ID
                                    </label>

                                    <input type="number"
                                           name="student_id"
                                           class="form-control"
                                           placeholder="Enter Student ID"
                                           required>

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Name
                                    </label>

                                    <input type="text"
                                           name="name"
                                           class="form-control"
                                           placeholder="Enter student name"
                                           required>

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Email
                                    </label>

                                    <input type="email"
                                           name="email"
                                           class="form-control"
                                           placeholder="Enter email"
                                           required>

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Phone
                                    </label>

                                    <input type="text"
                                           name="phone"
                                           class="form-control"
                                           placeholder="Enter phone"
                                           required>

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Semester
                                    </label>

                                    <input type="text"
                                           name="semester"
                                           class="form-control"
                                           placeholder="Example: 3rd"
                                           required>

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Department
                                    </label>

                                    <select name="department"
                                            class="form-select"
                                            required>

                                        <option value="">
                                            Select Department
                                        </option>

                                        <option value="1">
                                            CSE
                                        </option>

                                        <option value="2">
                                            EEE
                                        </option>

                                    </select>

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        Password
                                    </label>

                                    <input type="password"
                                           name="password"
                                           class="form-control"
                                           placeholder="Enter student login password"
                                           required>

                                    <small class="text-muted">
                                        This password will be used by the student to log in.
                                    </small>

                                </div>

                            </div>


                            <div class="mt-4">

                                <button type="submit"
                                        name="add_student"
                                        class="btn btn-login">

                                    <i class="bi bi-person-plus-fill"></i>

                                    Add Student

                                </button>

                            </div>

                        </form>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =========================
                 STUDENT LIST
                 ========================= -->

            <div class="card mt-4">

                <div class="card-body">

                    <h5 class="mb-4">
                        Student List
                    </h5>


                    <div class="mb-3">

                        <input type="text"
                               id="studentSearch"
                               class="form-control"
                               placeholder="Search students by ID, name, email, phone...">

                    </div>


                    <div class="table-responsive">

                        <table id="studentTable"
                               class="table table-bordered table-hover align-middle">

                            <thead>

                                <tr>

                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Semester</th>
                                    <th>Department</th>
                                    <th>Action</th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php if ($students && $students->num_rows > 0): ?>

                                    <?php while (
                                        $student = $students->fetch_assoc()
                                    ): ?>

                                        <tr>

                                            <td>

                                                <?= htmlspecialchars(
                                                    $student['StudentID']
                                                ) ?>

                                            </td>


                                            <td>

                                                <?= htmlspecialchars(
                                                    $student['Name']
                                                ) ?>

                                            </td>


                                            <td>

                                                <?= htmlspecialchars(
                                                    $student['Email']
                                                ) ?>

                                            </td>


                                            <td>

                                                <?= htmlspecialchars(
                                                    $student['Phone']
                                                ) ?>

                                            </td>


                                            <td>

                                                <?= htmlspecialchars(
                                                    $student['Semester']
                                                ) ?>

                                            </td>


                                            <td>

                                                <?= htmlspecialchars(
                                                    $student['DepartmentName']
                                                    ?? 'N/A'
                                                ) ?>

                                            </td>


                                            <td>

                                                <a
                                                    href="students.php?edit=<?= $student['StudentID'] ?>"
                                                    class="btn btn-sm btn-primary">

                                                    <i class="bi bi-pencil-fill"></i>

                                                    Edit

                                                </a>


                                                <a
                                                    href="students.php?delete=<?= $student['StudentID'] ?>"
                                                    class="btn btn-sm btn-danger delete-btn">

                                                    <i class="bi bi-trash-fill"></i>

                                                    Delete

                                                </a>

                                            </td>

                                        </tr>

                                    <?php endwhile; ?>

                                <?php else: ?>

                                    <tr>

                                        <td colspan="7"
                                            class="text-center">

                                            No students found.

                                        </td>

                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

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