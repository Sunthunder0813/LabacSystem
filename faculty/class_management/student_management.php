<?php
session_start();
require_once '../../connection.php';

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

// Get the faculty's unique_id
$faculty_id = $_SESSION['faculty_id'];
$faculty_unique_id = '';
$stmt = $conn->prepare("SELECT unique_id FROM users WHERE id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$stmt->bind_result($faculty_unique_id);
$stmt->fetch();
$stmt->close();

// Fetch only students registered by this faculty AND status = 'enrolled'
$stmt = $conn->prepare("SELECT lrn, last_name, first_name, middle_name, sex, birth_date, age, house, barangay, city, province, date_time_registered, status FROM students WHERE registered_by = ? AND status = 'enrolled' ORDER BY date_time_registered DESC");
$stmt->bind_param("s", $faculty_unique_id);
$stmt->execute();
$stmt->bind_result($lrn, $last_name, $first_name, $middle_name, $sex, $birth_date, $age, $house, $barangay, $city, $province, $date_time_registered, $status);

$students = [];
while ($stmt->fetch()) {
    // Build address, omitting leading comma if house is empty
    $address_parts = [];
    if (!empty($house)) $address_parts[] = $house;
    if (!empty($barangay)) $address_parts[] = $barangay;
    if (!empty($city)) $address_parts[] = $city;
    if (!empty($province)) $address_parts[] = $province;
    $address = implode(', ', $address_parts);

    $students[] = [
        'lrn' => $lrn,
        'name' => trim($last_name . ', ' . $first_name . ' ' . $middle_name),
        'sex' => $sex,
        'birth_date' => $birth_date,
        'age' => $age,
        'address' => $address,
        'registered_at' => $date_time_registered,
        'status' => $status
    ];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Encoding</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* --- CSS --- */
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
        .dropdown-content-2 { display: none; position: absolute; left: -160px; top: 0; background-color: #183325; border-radius: 6px; min-width: 160px; min-height: 100%; z-index: 1000; box-shadow: 0 8px 16px rgba(24,51,37,0.13); }
        .dropdown-content-2 a { display: block; padding: 10px 14px; text-decoration: none; color: #eafaf3; transition: background-color 0.3s; }
        .dropdown-content-2 a:hover { background-color: #24513a; }
        .dropdown-content .dropdown:hover .dropdown-content-2 { display: block; }
        main {
            flex: 1;
            width: 100%;
            max-width: 1400px;
            margin: 30px auto;
            padding: 30px 24px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(24, 51, 37, 0.13);
            backdrop-filter: blur(4px);
            background: #f8fff9;
        }
        .welcome-message { font-size: 20px; font-weight: 500; margin-bottom: 20px; color: #183325; }
        footer { text-align: center; padding: 14px; background: linear-gradient(to right, #183325, #24513a); color: #eafaf3; width: 100%; margin-top: auto; }
        @media (max-width: 768px) {
            nav { flex-wrap: wrap; justify-content: center; }
            nav a { padding: 10px; margin: 4px 2px; }
            .dropdown-content { position: static; box-shadow: none; }
        }
        .notification { position: fixed; right: 0; bottom: 0; margin: 0; min-width: 250px; max-width: 400px; z-index: 9999; padding: 16px 24px; border-radius: 10px 0 0 0; font-size: 16px; font-weight: 500; color: #fff; opacity: 0.98; box-shadow: 0 4px 16px rgba(24,51,37,0.13); display: none; transition: opacity 0.5s, transform 0.5s; pointer-events: none; box-sizing: border-box; }
        .notification.success { background: #28a745; }
        .notification.error { background: #dc3545; }
        .notification.warning { background: #ffc107; color: #333; }
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
        /* Enhanced Table Design */
        .table-container {
            width: 100%;
            max-width: 1350px;
            margin: 0 auto;
            background: transparent;
            padding-left: 0;
            padding-right: 0;
            box-sizing: border-box;
        }
        table.enhanced-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            box-shadow: 0 2px 12px rgba(24,51,37,0.10);
            border-radius: 12px;
            overflow: hidden;
            margin: 0 auto;
            table-layout: fixed;
        }
        table.enhanced-table thead th {
            background: #24513a;
            color: #fff;
            padding: 20px 16px;
            text-align: left;
            font-size: 1.13em;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(24,51,37,0.07);
            border-bottom: none;
            /* No sticky header */
        }
        table.enhanced-table thead th:first-child {
            border-top-left-radius: 12px;
        }
        table.enhanced-table thead th:last-child {
            border-top-right-radius: 12px;
        }
        /* Scrollable tbody styling */
        table.enhanced-table tbody.scrollable-table {
            display: block;
            max-height: 550px;
            overflow-y: auto;
            scrollbar-width: none; /* Hide scrollbar in Firefox */
            -ms-overflow-style: none;
            width: 100%;
        }
        table.enhanced-table thead, table.enhanced-table tbody.scrollable-table tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        table.enhanced-table tbody.scrollable-table tr {
            transition: background 0.2s;
        }
        table.enhanced-table tbody.scrollable-table tr:nth-child(odd) {
            background: #f7faf9;
        }
        table.enhanced-table tbody.scrollable-table tr:nth-child(even) {
            background: #e9f1ec;
        }
        table.enhanced-table tbody.scrollable-table tr:hover {
            background: #eafaf3;
        }
        table.enhanced-table td {
            padding: 15px 14px;
            border-bottom: 1px solid #e3ede7;
            color: #183325;
            font-size: 1em;
            word-break: break-word;
        }
        table.enhanced-table td:last-child, table.enhanced-table th:last-child {
            border-right: none;
        }
        table.enhanced-table th:first-child, table.enhanced-table td:first-child {
            border-left: none;
        }
        table.enhanced-table th, table.enhanced-table td {
            border-right: 1px solid #e3ede7;
        }
        table.enhanced-table tbody.scrollable-table tr:last-child td {
            border-bottom: none;
        }
        table.enhanced-table td span {
            background: #28a745;
            color: #fff;
            padding: 4px 12px;
            border-radius: 10px;
            font-size: 0.97em;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        /* Responsive adjustments for scrollable table */
        @media (max-width: 900px) {
            main, .table-container { max-width: 100%; padding: 10px; }
            table.enhanced-table thead th, table.enhanced-table td { padding: 10px 6px; }
            table.enhanced-table tbody.scrollable-table { max-height: 500px; }
        }
        @media (max-width: 600px) {
            .table-container, table.enhanced-table, table.enhanced-table thead, table.enhanced-table tbody, table.enhanced-table th, table.enhanced-table td, table.enhanced-table tr {
                display: block;
            }
            table.enhanced-table thead {
                display: none;
            }
            table.enhanced-table tbody.scrollable-table {
                max-height: none;
                overflow-y: visible;
            }
            table.enhanced-table tr {
                margin-bottom: 18px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(24,51,37,0.07);
                background: #fff;
            }
            table.enhanced-table td {
                padding: 12px 10px;
                border: none;
                position: relative;
                text-align: left;
            }
            table.enhanced-table td:before {
                content: attr(data-label);
                font-weight: bold;
                color: #24513a;
                display: block;
                margin-bottom: 4px;
            }
        }
        .section-header-flex {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 18px;
        }
        .section-title {
            color: #24513a;
            font-size: 1.5em;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .search-bar {
            padding: 8px 14px;
            border: 1px solid #b7b728;
            border-radius: 6px;
            font-size: 1em;
            outline: none;
            min-width: 200px;
            transition: border-color 0.2s;
        }
        .search-bar:focus {
            border-color: #24513a;
        }
        .enroll-btn {
            background: #24513a;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 9px 18px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .enroll-btn:hover {
            background: #183325;
            color: #b7b728;
            box-shadow: 0 2px 8px rgba(24,51,37,0.10);
        }
        .view-btn {
            background: #1e7a4d;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 7px 16px;
            font-size: 0.97em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .view-btn:hover {
            background: #24513a;
            color: #b7b728;
            box-shadow: 0 2px 8px rgba(24,51,37,0.13);
        }
        @media (max-width: 600px) {
            .section-header-flex {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .section-actions {
                justify-content: flex-start;
            }
            .search-bar {
                min-width: 0;
                width: 100%;
            }
        }
        /* Modal styles */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 99999;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(24,51,37,0.25);
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            background: #fff;
            border-radius: 12px;
            max-width: 500px;
            width: 95vw;
            padding: 32px 24px 24px 24px;
            box-shadow: 0 8px 32px rgba(24,51,37,0.18);
            position: relative;
            animation: modalIn 0.2s;
        }
        @keyframes modalIn {
            from { transform: translateY(40px) scale(0.98); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }
        .modal-close {
            position: absolute;
            top: 12px;
            right: 18px;
            font-size: 1.5em;
            color: #24513a;
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s;
        }
        .modal-close:hover {
            color: #dc3545;
        }
        .modal-title {
            font-size: 1.25em;
            font-weight: 600;
            color: #24513a;
            margin-bottom: 18px;
        }
        .modal-body {
            font-size: 1em;
            color: #183325;
        }
        .modal-body table {
            width: 100%;
            border-collapse: collapse;
        }
        .modal-body th, .modal-body td {
            text-align: left;
            padding: 6px 8px;
            border-bottom: 1px solid #e3ede7;
        }
        .modal-body th {
            width: 40%;
            color: #24513a;
            font-weight: 600;
            background: #f7faf9;
        }
        .modal-body tr:last-child td, .modal-body tr:last-child th {
            border-bottom: none;
        }
        @media (max-width: 600px) {
            .modal-content { padding: 18px 6px 12px 6px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <header>
            <div class="breadcrumb" style="display: flex; align-items: center; justify-content: center; gap: 3px; height: 100%;">
                <a href="../faculty_dashboard.php"><h1>Faculty Dashboard</h1></a>
                <span>&#8250;</span>
                <a href=""><h1>Class Management</h1></a>
                <span>&#8250;</span>
                <a href="student_management.php"><h1>Student Management</h1></a>
            </div>
            <nav>
                <!-- ...existing navigation HTML... -->
                <div class="dropdown">
                    <a href="">Class Management</a>
                    <div class="dropdown-content">
                        <a href="teacher_advisory.php">My Advisory</a>
                        <div class="dropdown">
                            <a href="class_management/student_management.php">Student Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="student_management/enroll_student.php">Enroll Student</a>
                            </div>
                        </div>
                        <a href="class_schedule.php">Class Schedule</a>
                        <a href="subject_list.php">Subjects List</a>
                    </div>
                </div>
                <div class="dropdown">
                    <a href="class_tool.php">Class Tools</a>
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
                    <a href="grade_management.php">Grade Management</a>
                    <div class="dropdown-content">
                        <a href="grade_encoding.php">Grade Encoding</a>
                        <a href="grade_report.php">Grade Reports</a>
                        <a href="grading_rubrics.php">Grading Rubrics</a>
                    </div>
                </div>
                <div class="dropdown">
                    <a href="generate_form.php">Generate Form</a>
                    <div class="dropdown-content">
                        <a href="school_form.php">School Form</a>
                        <a href="generate_certificate.php">Generate Certificate</a>
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
            <!-- Students Registered By You (Enrolled Only) -->
            <section style="margin-top:20px;">
                <div class="section-header-flex" >
                    <div class="section-title" style="display:flex;align-items:center;gap:16px;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;">
                            <i class="fas fa-users" style="color:#24513a;font-size:1.5em;"></i>
                        </span>
                        <span>Student Management</span>
                    </div>
                    <div class="section-actions">
                        <input type="text" id="studentSearch" class="search-bar" placeholder="Search students...">
                        <a href="student_management/enroll_student.php" class="enroll-btn">
                            <i class="fas fa-user-plus"></i> Enroll Student
                        </a>
                    </div>
                </div>
                <div class="table-container">
                    <table class="enhanced-table">
                        <thead>
                            <tr>
                                <th>LRN</th>
                                <th>Name</th>
                                <th>Sex</th>
                                <th>Birth Date</th>
                                <th>Age</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody class="scrollable-table" id="studentsTableBody">
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td data-label="LRN"><?php echo htmlspecialchars($student['lrn']); ?></td>
                                    <td data-label="Name"><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td data-label="Sex"><?php echo htmlspecialchars($student['sex']); ?></td>
                                    <td data-label="Birth Date"><?php echo htmlspecialchars($student['birth_date']); ?></td>
                                    <td data-label="Age"><?php echo htmlspecialchars($student['age']); ?></td>
                                    <td data-label="Address"><?php echo htmlspecialchars($student['address']); ?></td>
                                    <td data-label="Status"><span><?php echo htmlspecialchars(ucfirst($student['status'])); ?></span></td>
                                    <td data-label="Action">
                                        <button class="view-btn view-student-btn"
                                            data-lrn="<?php echo htmlspecialchars($student['lrn']); ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; color:#888; padding:18px;">No enrolled students registered by you.</td>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
        <!-- Student Info Modal -->
        <div class="modal-overlay" id="studentInfoModal">
            <div class="modal-content">
                <button class="modal-close" id="closeStudentModal" title="Close">&times;</button>
                <div class="modal-title">Student Information</div>
                <div class="modal-body" id="studentModalBody">
                    <!-- Student details will be loaded here -->
                    <div style="text-align:center; color:#888;">Loading...</div>
                </div>
            </div>
        </div>
        <footer>
            &copy; <?php echo date("Y"); ?> Admin Dashboard. All rights reserved.
        </footer>
    </div>
    <script>
        // --- JavaScript ---
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
        // Student search filter
        document.addEventListener('DOMContentLoaded', function() {
            var searchInput = document.getElementById('studentSearch');
            var tableBody = document.getElementById('studentsTableBody');
            if (searchInput && tableBody) {
                searchInput.addEventListener('input', function() {
                    var filter = searchInput.value.toLowerCase();
                    var rows = tableBody.getElementsByTagName('tr');
                    for (var i = 0; i < rows.length; i++) {
                        var lrnCell = rows[i].querySelector('td[data-label="LRN"]');
                        var nameCell = rows[i].querySelector('td[data-label="Name"]');
                        var lrn = lrnCell ? lrnCell.textContent.toLowerCase() : '';
                        var name = nameCell ? nameCell.textContent.toLowerCase() : '';
                        if (lrn.indexOf(filter) > -1 || name.indexOf(filter) > -1) {
                            rows[i].style.display = '';
                        } else {
                            rows[i].style.display = 'none';
                        }
                    }
                });
            }

            // Modal functionality for student details
            var modal = document.getElementById('studentInfoModal');
            var modalBody = document.getElementById('studentModalBody');
            var closeModalBtn = document.getElementById('closeStudentModal');
            tableBody.addEventListener('click', function(e) {
                var btn = e.target.closest('.view-student-btn');
                if (btn) {
                    var lrn = btn.getAttribute('data-lrn');
                    modal.classList.add('active');
                    modalBody.innerHTML = '<div style="text-align:center; color:#888;">Loading...</div>';
                    // AJAX fetch student info
                    fetch('student_info_modal.php?lrn=' + encodeURIComponent(lrn))
                        .then(function(response) { return response.text(); })
                        .then(function(html) { modalBody.innerHTML = html; })
                        .catch(function() { modalBody.innerHTML = '<div style="color:#dc3545;">Failed to load student info.</div>'; });
                }
            });
            closeModalBtn.addEventListener('click', function() {
                modal.classList.remove('active');
            });
            window.addEventListener('click', function(e) {
                if (e.target === modal) modal.classList.remove('active');
            });
            window.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') modal.classList.remove('active');
            });
        });
    </script>
</body>
</html>
