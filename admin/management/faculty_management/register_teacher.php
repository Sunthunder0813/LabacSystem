<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../../admin_login.php');
    exit();
}

require_once '../../../connection.php';
$conn = getConnection(); // Get the connection

// Handle teacher registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $age = $_POST['age'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $joining_date = $_POST['joining_date'];
    $qualifications = $_POST['qualifications'];
    $experience = $_POST['experience'];
    $previous_schools = $_POST['previous_schools'];
    $status = 'approved'; // Default status for new teachers

    if (!empty($last_name) && !empty($first_name) && !empty($email)) {
        // Insert into teachers table
        try {
            $stmt = $conn->prepare("INSERT INTO teachers (last_name, first_name, middle_name, age, dob, gender, contact_number, email, address, joining_date, qualifications, experience, previous_schools, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param(
                "sssissssssssss",
                $last_name, $first_name, $middle_name, $age, $dob, $gender, $contact_number, $email, $address, $joining_date, $qualifications, $experience, $previous_schools, $status
            );
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();

            // Insert into users table
            $username = "$first_name $last_name"; // Assuming username is a combination of first and last name
            $default_password = password_hash('labac2025', PASSWORD_DEFAULT); // Default password
            $user_type_id = 2; // Assuming 'faculty' user type has ID 2
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, user_type_id) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("sssi", $username, $email, $default_password, $user_type_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();

            $success_message = "Teacher registered successfully!";
            header('Location: faculty.php?success=1');
            exit();
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), "Duplicate entry") !== false && strpos($e->getMessage(), "email") !== false) {
                $error_message = "A teacher or user with this email already exists.";
            } else {
                $error_message = "An error occurred during registration. Please try again.";
            }
        } catch (Exception $e) {
            $error_message = "System error: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024, maximum-scale=1.0, user-scalable=no"> <!-- Fixed viewport -->
    <title>Register Teacher</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome CDN -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            height: 100%;
            overflow: hidden; /* Prevent scrolling of the entire page */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #d3d3d3;
        }

        .wrapper {
            display: flex;
            flex-direction: column;
            height: 100%; /* Ensure the wrapper takes the full height */
        }

        header {
            background: linear-gradient(to right, #0a1a3a, #1e3a5f);
            color: white;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        h1 {
            font-size: 22px;
        }

        nav {
            display: flex;
            align-items: center;
            height: 100%;
        }

        nav a {
            color: white;
            text-decoration: none;
            padding: 0 14px;
            height: 100%;
            display: flex;
            align-items: center;
            transition: background-color 0.3s;
            border-radius: 4px;
        }

        nav a:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }

        .dropdown {
            position: relative;
            height: 100%;
        }

        .dropdown > a {
            cursor: pointer;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: #34495e; /* Updated color */
            border-radius: 6px;
            min-width: 160px;
            z-index: 1000;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            display: block;
            padding: 10px 14px;
            text-decoration: none;
            color: #ecf0f1; /* Updated text color */
            transition: background-color 0.3s;
        }

        .dropdown-content a:hover {
            background-color: #2c3e50; /* Updated hover color */
        }

        .dropdown a i {
            margin-right: 8px; /* Space between icon and text */
        }

        .dropdown-content .dropdown {
            position: relative;
        }

        .dropdown-content .dropdown-content {
            display: none;
            left: -160px; /* Position the sub-dropdown to the left */
            top: 0;
        }

        .dropdown-content .dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-content-2 {
            display: none;
            position: absolute;
            left: -160px; /* Position the sub-dropdown to the left of the parent */
            top: 0;
            background-color: #1a2e4a; /* Complementary color to the parent */
            border-radius: 6px;
            min-width: 160px; /* Match the size of the parent dropdown */
            min-height: 100%; /* Match the height of the parent dropdown */
            z-index: 1000;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        .dropdown-content-2 a {
            display: block;
            padding: 10px 14px; /* Match the padding of the parent dropdown */
            text-decoration: none;
            color: #ecf0f1; /* Updated text color */
            transition: background-color 0.3s;
        }

        .dropdown-content-2 a:hover {
            background-color: #2c3e50; /* Updated hover color */
        }

        .dropdown-content .dropdown:hover .dropdown-content-2 {
            display: block;
        }
        main {
            flex: 1;
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(4px);
            background-color: white;
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Prevent scrolling in the main container */
            min-height: 400px; /* Set a minimum height to maintain consistent layout */
            width: 100%; /* Ensure consistent width */
        }
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

        footer {
            text-align: center;
            padding: 14px;
            background: linear-gradient(to right, #0a1a3a, #1e3a5f);
            color: white;
            width: 100%;
            flex-shrink: 0;
        }

        .container {
            width: 1000px;
            max-width: 1200px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        form .full-width {
            grid-column: span 2;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #1e3a5f;
            box-shadow: 0 0 5px rgba(30, 58, 95, 0.5);
            outline: none;
        }

        textarea {
            resize: none;
        }

        button {
            grid-column: span 2;
            padding: 12px;
            background-color: #1e3a5f;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }

        button:hover {
            background-color: #34495e;
            transform: translateY(-2px);
        }

        .message {
            grid-column: span 2;
            text-align: center;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .notification {
            position: fixed;
            right: 0;
            bottom: 0;
            margin: 0;
            min-width: 250px;
            max-width: 400px;
            z-index: 9999; /* Overlap all content */
            padding: 16px 24px;
            border-radius: 10px 0 0 0;
            font-size: 16px;
            font-weight: 500;
            color: #fff;
            opacity: 0.98;
            box-shadow: 0 4px 16px rgba(0,0,0,0.18);
            display: none;
            transition: opacity 0.5s, transform 0.5s;
            pointer-events: none;
            box-sizing: border-box;
        }
        .notification.success { background: #28a745; }
        .notification.error { background: #dc3545; }
        .notification.warning { background: #ffc107; color: #333; }
    </style>
</head>
<body>
    <div class="wrapper">
    <header>
        <div class="breadcrumb" style="display: flex; align-items: center; justify-content: center; gap: 3px; height: 100%;">
                <a href="../../../admin_dashboard.php"><h1>Admin Dashboard</h1></a>
                <span>&#8250;</span>
                <a href="faculty.php"><h1>Faculty Management</h1></a>
                <span>&#8250;</span>
                <a href="register_teacher.php"><h1>Register Teacher</h1></a>
            </div>
            <nav>
                <!-- School Management -->
                <div class="dropdown">
                    <a href="#">School Management</a>
                    <div class="dropdown-content">
                        <div class="dropdown">
                            <a href="faculty.php">Faculty Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="register_teacher.php">Register Teacher</a>
                                <a href="faculty_application.php">Application</a>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a href="../student_management/student.php">Student Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="../student_management/#">#</a>
                                <a href="../student_management/#">#</a>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a href="class.php">Class Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="#">#</a>
                                <a href="#">#</a>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a href="../subject_management/subject.php">Subject Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="../subject_management/#">#</a>
                                <a href="../subject_management/#">#</a>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a href="../section_management/section.php">Section Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="../section_management/#">#</a>
                                <a href="../section_management/#">#</a>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a href="../schedule_management/schedule.php">Schedule Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="../schedule_management/#">#</a>
                                <a href="../schedule_management/#">#</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic Records -->
                <div class="dropdown">
                    <a href="#">Academic Records</a>
                    <div class="dropdown-content">
                        <a href="../../academic/grade_encoding.php">Grade Encoding</a>
                        <a href="../../academic/grade_report.php">Grade Reports</a>
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
        <!-- Notification area -->
        <div id="notification" class="notification"></div>
                <?php if (isset($success_message)): ?>
                <?php endif; ?>
                <?php if (isset($error_message)): ?>
                <?php endif; ?>
        <main>
            
            <div class="container">
                <h1>Register Teacher</h1>
                <hr>
                <form method="POST" action="">
                    <input type="text" name="last_name" placeholder="Last Name" required>
                    <input type="text" name="first_name" placeholder="First Name" required>
                    <input type="text" name="middle_name" placeholder="Middle Name">
                    <input type="number" name="age" placeholder="Age" required>
                    <input type="date" name="dob" placeholder="Date of Birth" required>
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    <input type="text" name="contact_number" placeholder="Contact Number" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <textarea name="address" placeholder="Address" rows="3" class="full-width" required></textarea>
                    <input type="date" name="joining_date" placeholder="Joining Date" required>
                    <textarea name="qualifications" placeholder="Qualifications" rows="3"></textarea>
                    <textarea name="experience" placeholder="Experience" rows="3"></textarea>
                    <textarea name="previous_schools" placeholder="Previous Schools" rows="3"></textarea>
                    <button type="submit">Register</button>
                </form>
            </div>
        </main>
        <footer>
            &copy; <?php echo date("Y"); ?> Faculty Management. All rights reserved.
        </footer>
    </div>
    <script>
        // Flash notification logic
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

        // Show PHP flash messages as notification
        <?php if (isset($success_message)): ?>
            showNotification('success', <?php echo json_encode($success_message); ?>);
        <?php elseif (isset($error_message)): ?>
            showNotification('error', <?php echo json_encode($error_message); ?>);
        <?php endif; ?>
    </script>
</body>
</html>
