<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../admin_login.php');
    exit();
}
if (isset($_SESSION['login_success'])) {
    $success_message = "Login successful!";
    unset($_SESSION['login_success']);
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
        body, html { height: 100%; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #d3d3d3; }
        .wrapper { display: flex; flex-direction: column; min-height: 100vh; }
        header { background: linear-gradient(to right, #0a1a3a, #1e3a5f); color: white; height: 60px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2); }
        h1 { font-size: 22px; }
        nav { display: flex; align-items: center; height: 100%; }
        nav a { color: white; text-decoration: none; padding: 0 14px; height: 100%; display: flex; align-items: center; transition: background-color 0.3s; border-radius: 4px; }
        nav a:hover { background-color: rgba(255, 255, 255, 0.15); }
        .dropdown { position: relative; height: 100%; }
        .dropdown > a { cursor: pointer; height: 100%; display: flex; align-items: center; }
        .dropdown-content { display: none; position: absolute; top: 100%; right: 0; background-color: #34495e; border-radius: 6px; min-width: 160px; z-index: 1000; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3); }
        .dropdown:hover .dropdown-content { display: block; }
        .dropdown-content a { display: block; padding: 10px 14px; text-decoration: none; color: #ecf0f1; transition: background-color 0.3s; }
        .dropdown-content a:hover { background-color: #2c3e50; }
        .dropdown a i { margin-right: 8px; }
        .dropdown-content .dropdown { position: relative; }
        .dropdown-content .dropdown-content { display: none; position: absolute; left: 100%; top: 0; background-color: #1a2e4a; border-radius: 6px; min-width: 160px; z-index: 1000; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3); }
        .dropdown-content .dropdown:hover .dropdown-content { display: block; }
        .dropdown-content-2 { display: none; position: absolute; left: -160px; top: 0; background-color: #1a2e4a; border-radius: 6px; min-width: 160px; min-height: 100%; z-index: 1000; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3); }
        .dropdown-content-2 a { display: block; padding: 10px 14px; text-decoration: none; color: #ecf0f1; transition: background-color 0.3s; }
        .dropdown-content-2 a:hover { background-color: #2c3e50; }
        .dropdown-content .dropdown:hover .dropdown-content-2 { display: block; }
        main { flex: 1; max-width: 800px; margin: 30px auto; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); backdrop-filter: blur(4px); background: #fff; }
        .welcome-message { font-size: 20px; font-weight: 500; margin-bottom: 20px; }
        footer { text-align: center; padding: 14px; background: linear-gradient(to right, #0a1a3a, #1e3a5f); color: white; width: 100%; margin-top: auto; }
        @media (max-width: 768px) {
            nav { flex-wrap: wrap; justify-content: center; }
            nav a { padding: 10px; margin: 4px 2px; }
            .dropdown-content { position: static; box-shadow: none; }
        }
        .notification { position: fixed; right: 0; bottom: 0; margin: 0; min-width: 250px; max-width: 400px; z-index: 9999; padding: 16px 24px; border-radius: 10px 0 0 0; font-size: 16px; font-weight: 500; color: #fff; opacity: 0.98; box-shadow: 0 4px 16px rgba(0,0,0,0.18); display: none; transition: opacity 0.5s, transform 0.5s; pointer-events: none; box-sizing: border-box; }
        .notification.success { background: #28a745; }
        .notification.error { background: #dc3545; }
        .notification.warning { background: #ffc107; color: #333; }
        .breadcrumb a {
            text-decoration: none;
            color: white;
            font-weight: bold;
            transition: color 0.3s, transform 0.3s;
        }

        .breadcrumb a:hover {
            color: #1e90ff; /* Complementary blue color to the theme */
        }

        .breadcrumb span {
            color: white;
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
                <a href="../../admin_dashboard.php"><h1>Admin Dashboard</h1></a>
                <span>&#8250;</span>
                <a href="grade_encoding.php"><h1>Grade Encoding</h1></a>
            </div>
            <nav>
                <!-- School Management -->
                <div class="dropdown">
                    <a href="#">School Management</a>
                    <div class="dropdown-content">
                        <div class="dropdown">
                            <a href="../management/faculty_management/faculty.php">Faculty Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="../management/faculty_management/register_teacher.php">Register Teacher</a>
                                <a href="../management/faculty_management/faculty_application.php">Application</a>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a href="../management/student_management/student.php">Student Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="../management/student_management/#">#</a>
                                <a href="../management/student_management/#">#</a>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a href="../management/class_management/class.php">Class Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="../management/class_management/#">#</a>
                                <a href="../management/class_management/#">#</a>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a href="../management/subject_management/subject.php">Subject Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="../management/subject_management/#">#</a>
                                <a href="../management/subject_management/#">#</a>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a href="../management/section_management/section.php">Section Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="../management/section_management/#">#</a>
                                <a href="../management/section_management/#">#</a>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a href="../management/schedule_management/schedule.php">Schedule Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="../management/schedule_management/#">#</a>
                                <a href="../management/schedule_management/#">#</a>
                            </div>
                        </div>
                        
                    </div>
                </div>
                <!-- Academic Records -->
                <div class="dropdown">
                    <a href="#">Academic Records</a>
                    <div class="dropdown-content">
                        <a href="grade_encoding.php">Grade Encoding</a>
                        <a href="grade_report.php">Grade Reports</a>
                    </div>
                </div>
                <!-- Enrollment -->
                <div class="dropdown">
                    <a href="#">Enrollment</a>
                    <div class="dropdown-content">
                        <a href="../enrollment/new_enroll.php">New Enrollment</a>
                        <a href="../enrollment/re_enroll.php">Re-enrollment</a>
                        <a href="../enrollment/enrollment_report.php">Enrollment Reports</a>
                    </div>
                </div>
                <div class="dropdown">
                    <a href="#">Reports</a>
                    <div class="dropdown-content">
                        <a href="../report/attendance_report.php">Attendance Reports</a>
                        <a href="../report/grade_report.php">Grade Reports</a>
                        <a href="../report/faculty_report.php">Faculty Reports</a>
                        <a href="../report/student_report.php">Student Reports</a>
                    </div>
                </div>
                <!-- Settings -->
                <div class="dropdown">
                    <a href="#"><i class="fas fa-cog"></i></a>
                    <div class="dropdown-content">
                        <a href="../setting/profile.php">Profile</a>
                        <a href="../setting/user.php">User Management</a>
                        <a href="../setting/help.php">Help & Support</a>
                        <a href="../../logout.php">Logout</a>
                    </div>
                </div>
            </nav>
        </header>
        <div id="notification" class="notification"></div>
        <main>
            <p class="welcome-message">Grade Encoding</p>
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
