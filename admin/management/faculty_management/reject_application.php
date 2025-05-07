<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../admin_login.php');
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: admin/management/faculty_management/faculty.php');
    exit();
}

require_once '../../../connection.php';
$conn = getConnection();

$employee_id = $_GET['id'];

// Update the teacher's status to 'rejected'
$stmt = $conn->prepare("UPDATE teachers SET status = 'rejected' WHERE employee_id = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$stmt->close();
$conn->close();

// Redirect to faculty.php with notification
header('Location: faculty.php?notif=rejected');
exit();
?>
