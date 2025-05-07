<?php
session_start();
require_once '../connection.php';

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unique_id'], $_POST['password'])) {
    $unique_id = $_POST['unique_id'];
    $password = $_POST['password'];

    // Query to fetch user details
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE unique_id = ? AND user_type_id = 2");
    $stmt->bind_param("s", $unique_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $faculty = $result->fetch_assoc();

        // Verify the hashed password
        if (password_verify($password, $faculty['password'])) {
            $_SESSION['faculty_logged_in'] = true;
            $_SESSION['faculty_id'] = $faculty['id'];
            $_SESSION['faculty_name'] = $faculty['username'];
        } else {
            echo "Invalid password.";
            exit();
        }
    } else {
        echo "Invalid credentials.";
        exit();
    }
    $stmt->close();
}

if (!isset($_SESSION['faculty_logged_in']) || $_SESSION['faculty_logged_in'] !== true) {
    header('Location: faculty_login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Encoding</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { height: 100%; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #e3ede7; }
        .wrapper { display: flex; flex-direction: column; min-height: 100vh; }
        header { background: linear-gradient(to right, #183325, #24513a); color: white; height: 60px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; box-shadow: 0 2px 6px rgba(24, 51, 37, 0.13); }
        h1 { font-size: 22px; }
        nav { display: flex; align-items: center; height: 100%; }
        nav a { color: #eafaf3; text-decoration: none; padding: 0 14px; height: 100%; display: flex; align-items: center; transition: background-color 0.3s; border-radius: 4px; }
        nav a:hover { background-color: rgba(255, 255, 255, 0.15); }
        .dropdown { position: relative; height: 100%; }
        .dropdown > a { cursor: pointer; height: 100%; display: flex; align-items: center; }
        .dropdown-content { display: none; position: absolute; top: 100%; right: 0; background-color: #24513a; border-radius: 6px; min-width: 160px; z-index: 1000; box-shadow: 0 8px 16px rgba(24, 51, 37, 0.13); }
        .dropdown:hover .dropdown-content { display: block; }
        .dropdown-content a { display: block; padding: 10px 14px; text-decoration: none; color: #eafaf3; transition: background-color 0.3s; }
        .dropdown-content a:hover { background-color: #183325; }
        .dropdown a i { margin-right: 8px; }
        .dropdown-content .dropdown { position: relative; }
        .dropdown-content .dropdown-content { display: none; position: absolute; left: 100%; top: 0; background-color: #183325; border-radius: 6px; min-width: 160px; z-index: 1000; box-shadow: 0 8px 16px rgba(24, 51, 37, 0.13); }
        .dropdown-content .dropdown:hover .dropdown-content { display: block; }
        .dropdown-content-2 { display: none; position: absolute; left: -160px; top: 0; background-color: #183325; border-radius: 6px; min-width: 160px; min-height: 100%; z-index: 1000; box-shadow: 0 8px 16px rgba(24, 51, 37, 0.13); }
        .dropdown-content-2 a { display: block; padding: 10px 14px; text-decoration: none; color: #eafaf3; transition: background-color 0.3s; }
        .dropdown-content-2 a:hover { background-color: #24513a; }
        .dropdown-content .dropdown:hover .dropdown-content-2 { display: block; }
        main { flex: 1; max-width: 800px; margin: 30px auto; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(24, 51, 37, 0.13); backdrop-filter: blur(4px); background: #f8fff9; }
        .welcome-message { font-size: 20px; font-weight: 500; margin-bottom: 20px; color: #183325; }
        footer { text-align: center; padding: 14px; background: linear-gradient(to right, #183325, #24513a); color: #eafaf3; width: 100%; margin-top: auto; }
        @media (max-width: 768px) {
            nav { flex-wrap: wrap; justify-content: center; }
            nav a { padding: 10px; margin: 4px 2px; }
            .dropdown-content { position: static; box-shadow: none; }
        }
        .notification { position: fixed; right: 0; bottom: 0; margin: 0; min-width: 250px; max-width: 400px; z-index: 9999; padding: 16px 24px; border-radius: 10px 0 0 0; font-size: 16px; font-weight: 500; color: #fff; opacity: 0.98; box-shadow: 0 4px 16px rgba(24,51,37,0.13); display: none; transition: opacity 0.5s, transform 0.5s; pointer-events: none; box-sizing: border-box; }
        .notification.success { background: #24513a; }
        .notification.error { background: #a74528; }
        .notification.warning { background: #b7b728; color: #333; }
        .breadcrumb a {
            text-decoration: none;
            color: #eafaf3;
            font-weight: bold;
            transition: color 0.3s, transform 0.3s;
        }
        .breadcrumb a:hover {
            color: #b7b728;
        }
        .breadcrumb span {
            color: #eafaf3;
            font-size: 80px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            transform: translateY(-12px);
            transition: transform 0.3s, color 0.3s, rotate 0.3s;
        }
    </style>
</head>
<body>
    <div class="wrapper">
    <header>
        <div class="breadcrumb" style="display: flex; align-items: center; justify-content: center; gap: 3px; height: 100%;">
            <a href="faculty_dashboard.php"><h1>Faculty Dashboard</h1></a>
        </div>
        <nav>
    <div class="dropdown">
        <a href="">Class Management</a>
        <div class="dropdown-content">
            <a href="teacher_advisory.php">My Advisory</a>
            <div class="dropdown">
                <a href="class_management/student_management.php">Student Management</a>
                <div class="dropdown-content-2" style="left: -160px; top: 0;">
                    <a href="class_management/student_management/enroll_student.php">Enroll Student</a>
                </div>
            </div>
            <a href="class_schedule.php">Class Schedule</a>
            <a href="subject_list.php">Subjects List</a>
        </div>
    </div>
    <div class="dropdown">
        <a href="">Class Tools</a>
        <div class="dropdown-content">
            <div class="dropdown">
                <a href="attendance.php">Attendance</a>
                <div class="dropdown-content-2" style="left: -160px; top: 0;">
                    <a href="quick_attendance.php">Quick Attendance</a>
                    <a href="view_attendance.php">View Attendance</a>
                </div>
            </div>
            <a href="assignments.php">Assignments</a>
            <a href="announcement.php">Announcements</a>
            <a href="materials.php">Materials</a>
        </div>
    </div>
    <div class="dropdown">
        <a href="">Grade Management</a>
        <div class="dropdown-content">
            <a href="grade_encoding.php">Grade Encoding</a>
            <a href="grade_report.php">Grade Reports</a>
            <a href="grading_rubrics.php">Grading Rubrics</a>
        </div>
    </div>
    <div class="dropdown">
        <a href="">Generate Form</a>
        <div class="dropdown-content">
            <a href="school_form.php">School Form</a>
            <a href="generate_certificate.php">Generate Certificate</a> <!-- New addition -->
        </div>
    </div>
    <div class="dropdown">
        <a href="information.php">Information</a>
        <div class="dropdown-content">
            <div class="dropdown">
                <a href="info_announcements.php">Announcements</a>
                <div class="dropdown-content-2" style="left: -180px; top: 0;">
                    <a href="school_announcements.php">View Announcements</a>
                    <a href="post_announcement.php">Post Announcement</a>
                </div>
            </div>
            <div class="dropdown">
                <a href="school_calendar.php">School Calendar</a>
                <div class="dropdown-content-2" style="left: -160px; top: 0;">
                    <a href="add_event.php">Add Event</a>
                </div>
            </div>
        </div>
    </div>
    <div class="dropdown">
        <a href="#"><i class="fas fa-cog"></i></a>
        <div class="dropdown-content">
            <a href="../setting/profile.php">Profile</a>
            <a href="../setting/user.php">Settings</a>
            <a href="../setting/help.php">Help & Support</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

    </header>
        <div id="notification" class="notification"></div>
        <main>
            <p class="welcome-message">Faculty Dashboard</p>
            <p>Encode and manage student grades here. (Add your grade encoding features.)</p>
        </main>
        <footer>
            &copy; <?php echo date("Y"); ?> Admin Dashboard. All rights reserved.
        </footer>
    </div>
    <script>
        function showNotification(type, message) {
            var notif = document.getElementById('notification');
            notif.className = 'notification ' + type;
            notif.textContent = message;
            notif.style.display = 'block';
            notif.style.opacity = '0.98';
            notif.style.transform = 'translateY(0)';
            setTimeout(function() {
                notif.style.opacity = '0';
                notif.style.transform = 'translateY(40px)';
            }, 4000);
            setTimeout(function() {
                notif.style.display = 'none';
                notif.style.transform = 'translateY(0)';
            }, 4500);
        }
        <?php if (isset($success_message)): ?>
            showNotification('success', <?php echo json_encode($success_message); ?>);
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            showNotification('error', <?php echo json_encode($error_message); ?>);
        <?php endif; ?>
    </script>
</body>
</html>
