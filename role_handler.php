<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'])) {
    $role = $_POST['role'];

    if ($role === 'teacher') {
        header('Location: faculty/faculty_login.php'); // Redirect to faculty login page
        exit();
    } elseif ($role === 'student') {
        header('Location: student_login.php'); // Redirect to student login page
        exit();
    } else {
        echo "Invalid role selected.";
    }
} else {
    echo "No role selected.";
}
?>
