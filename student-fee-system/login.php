<?php

session_start();

require_once "config/db.php";

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

if ($email == '' || $password == '' || $role == '') {
    die("Please fill in all fields.");
}

if ($role == 'staff') {

    $sql = "SELECT UserAccount.*, staff.Email, staff.Name
            FROM UserAccount
            INNER JOIN staff ON UserAccount.StaffID = staff.StaffID
            WHERE staff.Email = ?
            AND UserAccount.Password = ?
            AND UserAccount.Role = ?";

} else {

    $sql = "SELECT UserAccount.*, student.Email, student.Name
            FROM UserAccount
            INNER JOIN student ON UserAccount.StudentID = student.StudentID
            WHERE student.Email = ?
            AND UserAccount.Password = ?
            AND UserAccount.Role = ?";
}

$stmt = $conn->prepare($sql);

$stmt->bind_param("sss", $email, $password, $role);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 1) {

    $user = $result->fetch_assoc();

    $_SESSION['UserID'] = $user['UserID'];
    $_SESSION['Role'] = $user['Role'];
    $_SESSION['StaffID'] = $user['StaffID'];
    $_SESSION['StudentID'] = $user['StudentID'];
    $_SESSION['Name'] = $user['Name'];
    $_SESSION['Email'] = $user['Email'];

    if ($role == 'staff') {

        header("Location: admin-dashboard.php");

    } else {

        header("Location: student-dashboard.php");

    }

    exit;

} else {

    echo "Invalid email, password, or role.";

}

$stmt->close();
$conn->close();

?>