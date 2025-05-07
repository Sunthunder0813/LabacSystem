<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../../admin_login.php');
    exit();
}

require_once '../../../connection.php'; // Include database connection
$conn = getConnection(); // Get the connection

// Fetch faculty applications with status 'pending'
$sql = "SELECT * FROM teachers WHERE status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$applications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024, maximum-scale=1.0, user-scalable=no"> <!-- Fixed viewport -->
    <title>Faculty Applications</title>
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

        footer {
            text-align: center;
            padding: 14px;
            background: linear-gradient(to right, #0a1a3a, #1e3a5f);
            color: white;
            width: 100%;
            flex-shrink: 0;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        thead {
            background-color: #0a1a3a;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            position: sticky;
            top: 0; /* Keep the header fixed at the top */
            z-index: 1;
        }

        tbody {
            display: block;
            max-height: 700px; /* Set a fixed height for the table body */
            overflow-y: auto; /* Enable vertical scrolling */
            min-height: 200px; /* Set a minimum height to prevent shrinking */
            scrollbar-width: none; /* Hide scrollbar in Firefox */
            -ms-overflow-style: none;
        }

        tbody tr {
            display: table;
            width: 100%;
            table-layout: fixed; /* Ensure consistent column widths */
        }

        thead tr {
            display: table;
            width: 100%;
            table-layout: fixed; /* Ensure consistent column widths */
        }

        table th, table td {
            padding: 12px 15px;
            text-align: center;
        }

        table tr:nth-child(even) {
            background-color:rgb(228, 228, 228);
        }

        table td img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
        }
        table th:nth-child(4), table td:nth-child(4) {
            width: 500px; /* Set a fixed width for the actions column */
        }

        .action-buttons a {
            text-decoration: none;
            color: white;
            padding: 8px 6px;
            border-radius: 5px;
            font-size: 15px;
            font-weight: bold;
            display: inline-block;
            transition: background-color 0.3s ease;
        }

        .action-buttons a.approve {
            background-color: #28a745;
        }

        .action-buttons a.approve:hover {
            background-color: #218838;
        }

        .action-buttons a.reject {
            background-color: #dc3545;
        }

        .action-buttons a.reject:hover {
            background-color: #c82333;
        }

        .action-buttons a.view-details {
            background-color: #007bff;
        }

        .action-buttons a.view-details:hover {
            background-color: #0056b3;
        }

        table th:nth-child(4), table td:nth-child(4) {
            width: 500px; /* Set a fixed width for the actions column */
        }

        .action-buttons a {
            text-decoration: none;
            color: white;
            padding: 8px 6px;
            border-radius: 5px;
            font-size: 15px;
            font-weight: bold;
            display: inline-block;
            transition: background-color 0.3s ease;
        }

        .action-buttons a.approve {
            background-color: #28a745;
        }

        .action-buttons a.approve:hover {
            background-color: #218838;
        }

        .action-buttons a.reject {
            background-color: #dc3545;
        }

        .action-buttons a.reject:hover {
            background-color: #c82333;
        }

        .action-buttons a.view-details {
            background-color: #007bff;
        }

        .action-buttons a.view-details:hover {
            background-color: #0056b3;
        }

        #detailsModal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            width: 80%;
            max-width: 600px;
        }

        #detailsModal h2 {
            margin-bottom: 20px;
            font-size: 24px;
            text-align: center;
            color: #333;
        }

        #detailsModal img {
            display: block;
            margin: 0 auto 20px;
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        #detailsModal table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        #detailsModal table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
            color: #555;
            text-align: left; /* Make all table text left-aligned */
        }

        #detailsModal table td:first-child {
            font-weight: bold;
            color: #333;
            width: 40%;
            text-align: left;
        }

        #detailsModal table tr:last-child td {
            border-bottom: none;
        }

        #detailsModal .close {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        #detailsModal .close:hover {
            color: #000;
        }

        #modalOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .notification {
            position: fixed;
            right: 0;
            bottom: 0;
            margin: 0;
            min-width: 250px;
            max-width: 400px;
            z-index: 9999;
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
                <a href="faculty_application.php"><h1>Application</h1></a>
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
        <div id="notification" class="notification"></div>
        <main>
            <div class="header-actions">
                <div class="info">
                    <p><strong>Total Applications:</strong> <span id="totalApplications"><?php echo count($applications); ?></span></p>
                </div>
            </div>
            <?php if (empty($applications)): ?>
                <div id="noResultsMessage" class="no-results">No applications found.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $application): ?>
                            <?php
                            $image_path = "../register_teacher_img/{$application['employee_id']}";
                            $image_extensions = ['jpg', 'jpeg', 'png'];
                            $image_found = false;

                            foreach ($image_extensions as $ext) {
                                if (file_exists("$image_path.$ext")) {
                                    $image_path = "$image_path.$ext";
                                    $image_found = true;
                                    break;
                                }
                            }

                            if (!$image_found) {
                                $image_path = "../register_teacher_img/default.jpg";
                            }
                            $application['image_path'] = $image_path;
                            ?>
                            <tr>
                                <td><img src="<?php echo $image_path; ?>" alt="Applicant Image"></td>
                                <td><?php echo $application['first_name'] . ' ' . $application['last_name']; ?></td>
                                <td><?php echo $application['employee_id']; ?></td>
                                <td class="action-buttons">
                                    <a href="approve_application.php?id=<?php echo $application['employee_id']; ?>" class="approve">Approve</a>
                                    <a href="reject_application.php?id=<?php echo $application['employee_id']; ?>" class="reject">Reject</a>
                                    <a href="#" class="view-details" onclick="showDetailsModal(<?php echo htmlspecialchars(json_encode($application), ENT_QUOTES, 'UTF-8'); ?>)">Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>

        <!-- Modal for displaying details -->
        <div id="detailsModal">
            <span class="close" onclick="closeDetailsModal()">&times;</span>
            <h2>Application Details</h2>
            <img id="applicantImage" src="" alt="Applicant Image">
            <table>
                <tbody id="detailsTable">
                    <!-- Details will be dynamically populated -->
                </tbody>
            </table>
        </div>
        <div id="modalOverlay" onclick="closeDetailsModal()"></div>

        <script>
            function showDetailsModal(application) {
                const detailsTable = document.getElementById('detailsTable');
                const applicantImage = document.getElementById('applicantImage');
                const imagePath = application.image_path || 'register_teacher_img/default.jpg';

                applicantImage.src = imagePath;
                detailsTable.innerHTML = `
                    <tr><td>Last Name:</td><td>${application.last_name}</td></tr>
                    <tr><td>First Name:</td><td>${application.first_name}</td></tr>
                    <tr><td>Middle Name:</td><td>${application.middle_name || 'N/A'}</td></tr>
                    <tr><td>Age:</td><td>${application.age || 'N/A'}</td></tr>
                    <tr><td>Date of Birth:</td><td>${application.dob || 'N/A'}</td></tr>
                    <tr><td>Gender:</td><td>${application.gender || 'N/A'}</td></tr>
                    <tr><td>Contact Number:</td><td>${application.contact_number || 'N/A'}</td></tr>
                    <tr><td>Email:</td><td>${application.email || 'N/A'}</td></tr>
                    <tr><td>Address:</td><td>${application.address || 'N/A'}</td></tr>
                    <tr><td>Employee ID:</td><td>${application.employee_id}</td></tr>
                    <tr><td>Joining Date:</td><td>${application.joining_date || 'N/A'}</td></tr>
                    <tr><td>Qualifications:</td><td>${application.qualifications || 'N/A'}</td></tr>
                    <tr><td>Experience:</td><td>${application.experience || 'N/A'}</td></tr>
                    <tr><td>Previous Schools:</td><td>${application.previous_schools || 'N/A'}</td></tr>
                `;
                document.getElementById('detailsModal').style.display = 'block';
                document.getElementById('modalOverlay').style.display = 'block';
            }

            function closeDetailsModal() {
                document.getElementById('detailsModal').style.display = 'none';
                document.getElementById('modalOverlay').style.display = 'none';
            }

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
            <?php elseif (isset($error_message)): ?>
                showNotification('error', <?php echo json_encode($error_message); ?>);
            <?php endif; ?>
        </script>
        <footer>
            &copy; <?php echo date("Y"); ?> Faculty Applications. All rights reserved.
        </footer>
    </div>
</body>
</html>
