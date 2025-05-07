<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../../../admin_login.php');
    exit();
}

require_once '../../../connection.php'; // Include database connection
$conn = getConnection(); // Get the connection

// Show notification if redirected with success
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Teacher registered successfully!";
}

// Add: Show notification if redirected after approve/reject
if (isset($_GET['notif'])) {
    if ($_GET['notif'] === 'approved') {
        $success_message = "Teacher application approved successfully!";
    } elseif ($_GET['notif'] === 'rejected') {
        $error_message = "Teacher application rejected.";
    }
}

// Handle search and sorting
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$gender_filter = isset($_GET['gender']) ? $_GET['gender'] : '';

$sql = "SELECT * FROM teachers WHERE status = 'approved'"; // Only show approved teachers
if (!empty($search_query)) {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR employee_id LIKE ?)";
}
if (!empty($gender_filter)) {
    $sql .= " AND gender = ?";
}

$stmt = $conn->prepare($sql);
if (!empty($search_query) && !empty($gender_filter)) {
    $search_param = "%$search_query%";
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $gender_filter);
} elseif (!empty($search_query)) {
    $search_param = "%$search_query%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
} elseif (!empty($gender_filter)) {
    $stmt->bind_param("s", $gender_filter);
}
$stmt->execute();
$result = $stmt->get_result();
$teachers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024, maximum-scale=1.0, user-scalable=no"> <!-- Fixed viewport -->
    <title>Faculty Management</title>
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

        /*========================================================================================================*/
        
        .card-container {
            margin-top: 30px;
            flex: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            align-content: flex-start; /* Ensure proper alignment when sorting */
            overflow-y: auto; /* Enable vertical scrolling for the card container */
            padding: 10px;
            min-height: 200px; /* Set a minimum height to prevent shrinking */
            scrollbar-width: none; /* Hide scrollbar in Firefox */
            -ms-overflow-style: none; /* Hide scrollbar in IE and Edge */
        }

        .card-container::-webkit-scrollbar {
            display: none; /* Hide scrollbar in Chrome, Safari, and Edge */
        }
        .no-results {
            text-align: center;
            font-size: 18px;
            color: #555;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            nav {
                flex-wrap: wrap;
                justify-content: center;
            }

            nav a {
                padding: 10px;
                margin: 4px 2px;
            }

            .dropdown-content {
                position: static;
                box-shadow: none;
            }

            .modal-content {
                width: 90%;
                padding: 20px;
            }

            .modal-content .details {
                grid-template-columns: 1fr;
            }
        }

        .card {
            width: 320px;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background-color: white;
            text-align: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .card img {
            width: 160px; /* Adjusted width for better design */
            height: 160px; /* Adjusted height for better design */
            object-fit: cover;
            border-radius: 50%; /* Make the image circular */
            margin: 15px auto; /* Center the image horizontally and add spacing */
            display: block; /* Ensure the image is treated as a block element */
            border: 3px solid #1e3a5f; /* Add a border to match the theme */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Add a subtle shadow */
            transition: transform 0.3s, box-shadow 0.3s; /* Add hover effects */
        }

        .card-body {
            padding: 20px;
        }

        .card-body h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #0a1a3a;
            font-weight: bold;
        }

        .card-body p {
            font-size: 14px;
            margin: 5px 0;
            color: #555;
        }

        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6); /* Black background with opacity */
        }

        .modal-content {
            background-color: white;
            /* Center the modal */
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin: 0; /* Remove margin for absolute centering */
            padding: 30px;
            border-radius: 15px;
            width: 60%;
            max-width: 600px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            text-align: left;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .modal-content h3 {
            margin-bottom: 20px;
            color: #0a1a3a;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }

        .modal-content img {
            display: block;
            margin: 0 auto 20px;
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .modal-content table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .modal-content table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
            color: #555;
        }

        .modal-content table td:first-child {
            font-weight: bold;
            color: #333;
            width: 40%;
            text-align: left;
        }

        .modal-content table tr:last-child td {
            border-bottom: none;
        }

        .modal-content table tr:nth-child(even) {
            background-color: rgb(228, 228, 228);
        }

        .close {
            color: #aaa;
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header-actions .filters {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-actions .filters input,
        .header-actions .filters select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .header-actions .filters a {
            text-decoration: none;
            color: white;
            background-color: #1e3a5f;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            transition: background-color 0.3s;
            text-align: center;
        }

        .header-actions .filters a:hover {
            background-color: #34495e;
        }

        .header-actions .info {
            font-size: 16px;
            color: #333;
            font-weight: bold;
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
    <script>
        function openModal(teacherId) {
            const modal = document.getElementById(`modal-${teacherId}`);
            modal.style.display = "block";
        }

        function closeModal(teacherId) {
            const modal = document.getElementById(`modal-${teacherId}`);
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            });
        }

        function filterTeachers() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const genderFilter = document.getElementById('genderFilter').value;
            const cards = document.querySelectorAll('.card');
            let visibleCount = 0;

            cards.forEach(card => {
                const name = card.querySelector('h3').textContent.toLowerCase();
                const id = card.querySelector('.unique-id').textContent.toLowerCase();
                const gender = card.getAttribute('data-gender').toLowerCase();

                const matchesSearch = name.includes(searchInput) || id.includes(searchInput);
                const matchesGender = !genderFilter || gender === genderFilter.toLowerCase();

                if (matchesSearch && matchesGender) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const noResultsMessage = document.getElementById('noResultsMessage');
            const totalTeachersElement = document.getElementById('totalTeachers');
            if (visibleCount === 0) {
                noResultsMessage.style.display = 'block';
            } else {
                noResultsMessage.style.display = 'none';
            }
            totalTeachersElement.textContent = visibleCount; // Update total teachers count
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
    </script>
</head>
<body>
    <div class="wrapper">
    <header>
        <div class="breadcrumb" style="display: flex; align-items: center; justify-content: center; gap: 3px; height: 100%;">
                <a href="../../../admin_dashboard.php"><h1>Admin Dashboard</h1></a>
                <span>&#8250;</span>
                <a href="faculty.php"><h1>Faculty Management</h1></a>
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
        <main>
            <div class="header-actions">
                <div class="info">
                    <p><strong>Total Teachers:</strong> <span id="totalTeachers"><?php echo count($teachers); ?></span></p>
                </div>
                <div class="filters">
                    <input type="text" id="searchInput" placeholder="Search by name or ID" oninput="filterTeachers()">
                    <select id="genderFilter" onchange="filterTeachers()">
                        <option value="">All</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    <div class="dropdown">
                        <a href="#" style="text-decoration: none; color: white; background-color: #1a2e4a; padding: 8px 16px; border-radius: 4px; font-size: 14px; font-weight: bold; transition: background-color 0.3s; text-align: center;">Manage</a>
                        <div class="dropdown-content" style="position: absolute; background-color: #1a2e4a; border-radius: 6px; min-width: 160px; z-index: 1000; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3); ">
                            <a href="register_teacher.php" style="display: block; padding: 10px 14px; text-decoration: none; color: #ecf0f1; transition: background-color 0.3s;text-align: left;">Register Teacher</a>
                            <a href="faculty_application.php" style="display: block; padding: 10px 14px; text-decoration: none; color: #ecf0f1; transition: background-color 0.3s;text-align: left;">Application</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (isset($success_message)): ?>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
            <?php endif; ?>
            <div class="card-container">
                <?php if (empty($teachers)): ?>
                    <div id="noResultsMessage" class="no-results">No teachers found.</div>
                <?php else: ?>
                    <div id="noResultsMessage" class="no-results" style="display: none;">No teachers found.</div>
                    <?php foreach ($teachers as $teacher): ?>
                        <div class="card" data-gender="<?php echo $teacher['gender']; ?>" onclick="openModal('<?php echo $teacher['employee_id']; ?>')">
                            <?php
                            $image_path = "../register_teacher_img/{$teacher['employee_id']}";
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
                                $image_path = "../register_teacher_img/default.jpg"; // Ensure default.jpeg is used
                            }
                            ?>
                            <img src="<?php echo $image_path; ?>" alt="Teacher Image">
                            <div class="card-body">
                                <h3><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></h3>
                                <p class="unique-id">ID: <?php echo $teacher['employee_id']; ?></p>
                            </div>
                        </div>

                        <!-- Modal for teacher details -->
                        <div id="modal-<?php echo $teacher['employee_id']; ?>" class="modal">
                            <div class="modal-content">
                                <span class="close" onclick="closeModal('<?php echo $teacher['employee_id']; ?>')">&times;</span>
                                <h3>Teacher Details</h3>
                                <img src="<?php echo $image_path; ?>" alt="Teacher Image">
                                <table>
                                    <tbody>
                                        <tr><td>Last Name:</td><td><?php echo $teacher['last_name']; ?></td></tr>
                                        <tr><td>First Name:</td><td><?php echo $teacher['first_name']; ?></td></tr>
                                        <tr><td>Middle Name:</td><td><?php echo $teacher['middle_name'] ?: 'N/A'; ?></td></tr>
                                        <tr><td>Age:</td><td><?php echo $teacher['age'] ?: 'N/A'; ?></td></tr>
                                        <tr><td>Date of Birth:</td><td><?php echo $teacher['dob'] ?: 'N/A'; ?></td></tr>
                                        <tr><td>Gender:</td><td><?php echo $teacher['gender'] ?: 'N/A'; ?></td></tr>
                                        <tr><td>Contact Number:</td><td><?php echo $teacher['contact_number'] ?: 'N/A'; ?></td></tr>
                                        <tr><td>Email:</td><td><?php echo $teacher['email'] ?: 'N/A'; ?></td></tr>
                                        <tr><td>Address:</td><td><?php echo $teacher['address'] ?: 'N/A'; ?></td></tr>
                                        <tr><td>Employee ID:</td><td><?php echo $teacher['employee_id']; ?></td></tr>
                                        <tr><td>Joining Date:</td><td><?php echo $teacher['joining_date'] ?: 'N/A'; ?></td></tr>
                                        <tr><td>Qualifications:</td><td><?php echo $teacher['qualifications'] ?: 'N/A'; ?></td></tr>
                                        <tr><td>Experience:</td><td><?php echo $teacher['experience'] ?: 'N/A'; ?></td></tr>
                                        <tr><td>Previous Schools:</td><td><?php echo $teacher['previous_schools'] ?: 'N/A'; ?></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
        <footer>
            &copy; <?php echo date("Y"); ?> Faculty Management. All rights reserved.
        </footer>
        <div id="notification" class="notification"></div>
    </div>
    <script>
        <?php if (isset($success_message)): ?>
            showNotification('success', <?php echo json_encode($success_message); ?>);
        <?php elseif (isset($error_message)): ?>
            showNotification('error', <?php echo json_encode($error_message); ?>);
        <?php endif; ?>
    </script>
</body>
</html>
