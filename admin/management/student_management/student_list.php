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

// --- Add DB connection and fetch students registered by teachers ---
require_once '../../../connection.php';
$conn = getConnection();

// Get all students registered by a faculty (user_type_id = 2)
$sql = "SELECT s.lrn, 
               CONCAT(s.last_name, ', ', s.first_name, IF(s.middle_name != '', CONCAT(' ', s.middle_name), '')) AS full_name,
               s.section,
               s.grade_level,
               s.sex
        FROM students s
        LEFT JOIN users u ON s.registered_by = u.unique_id
        WHERE u.user_type_id = 2
        ORDER BY s.date_time_registered DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" width="device-width, initial-scale=1.0">
    <title>Student Management</title>
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
        main { flex: 1; max-width: 1600px; margin: 30px auto; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); backdrop-filter: blur(4px); background: #fff; }
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

        /* --- Student Table Styles (match faculty_application) --- */
        .student-table-container {
            width: 100%;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.10);
        }
        table.student-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            background-color: #fff;
            table-layout: fixed; /* Ensure equal-width columns */
        }
        .student-table thead {
            background-color: #0a1a3a;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .student-table tbody {
            display: block;
            max-height: 590px;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .student-table thead tr, .student-table tbody tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        .student-table th, .student-table td {
            padding: 12px 10px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
            white-space: normal;
            word-break: break-word;
            width: 16.66%; /* 6 columns: 100/6 ≈ 16.66% */
        }
        .student-table th, .student-table td {
            min-width: 120px;
            max-width: 1px; /* allow shrink/grow but keep equal */
        }
        .student-table th:first-child, .student-table td:first-child,
        .student-table th:last-child, .student-table td:last-child {
            width: 16.66%;
        }
        .student-table tr:nth-child(even) {
            background-color: #f4f6fa;
        }
        .student-table td img {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 50%;
            background: #e0e0e0;
        }
        /* Responsive */
        @media (max-width: 900px) {
            main { padding: 10px 2px; }
            .student-table th, .student-table td { font-size: 12px; padding: 8px 4px; }
        }
        /* --- Modal Styles (match student_management) --- */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 99999;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(30,58,95,0.18);
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active,
        .modal-overlay[style*="display: flex"] {
            display: flex !important;
        }
        .modal-content {
            background: #fff;
            border-radius: 12px;
            max-width: 500px;
            width: 95vw;
            padding: 32px 24px 24px 24px;
            box-shadow: 0 8px 32px rgba(30,58,95,0.18);
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
            color: #1e3a5f;
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
            color: #1e3a5f;
            margin-bottom: 18px;
        }
        .modal-body {
            font-size: 1em;
            color: #222d3a;
        }
        .modal-body table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5em;
        }
        .modal-body th, .modal-body td {
            text-align: left;
            padding: 7px 10px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 1em;
        }
        .modal-body th {
            width: 38%;
            color: #1e3a5f;
            font-weight: 600;
            background: #f4f6fa;
        }
        .modal-body tr:last-child td, .modal-body tr:last-child th {
            border-bottom: none;
        }
        @media (max-width: 600px) {
            .modal-content { padding: 16px 4px 10px 4px; }
            .modal-title { font-size: 1.05em; }
            .modal-body th, .modal-body td { font-size: 0.97em; padding: 6px 4px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <header>
        <div class="breadcrumb" style="display: flex; align-items: center; justify-content: center; gap: 3px; height: 100%;">
                <a href="../../../admin_dashboard.php"><h1>Admin Dashboard</h1></a>
                <span>&#8250;</span>
                <a href="student_list.php"><h1>Student Management</h1></a>
            </div>
            <nav>
                <!-- School Management -->
                <div class="dropdown">
                    <a href="#">School Management</a>
                    <div class="dropdown-content">
                        <div class="dropdown">
                            <a href="../faculty_management/faculty.php">Faculty Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="../faculty_management/register_teacher.php">Register Teacher</a>
                                <a href="../faculty_management/faculty_application.php">Application</a>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a href="student_list.php">Student Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="#">#</a>
                                <a href="#">#</a>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a href="../class_management/class.php">Class Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="../class_management/#">#</a>
                                <a href="../class_management/#">#</a>
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
            <div class="student-table-container">
                <!-- Search bar -->
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;gap:12px;flex-wrap:wrap;">
                    <div style="font-size:1.25em;font-weight:600;color:#0a1a3a;display:flex;align-items:center;gap:10px;">
                        <i class="fas fa-users" style="color:#0a1a3a;font-size:1.2em;"></i>
                        <span>Student List</span>
                    </div>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <input type="text" id="studentSearch" class="search-bar" placeholder="Search students..." style="padding:8px 14px;border:1px solid #1e3a5f;border-radius:6px;font-size:1em;outline:none;min-width:200px;">
                        <select id="sexFilter" style="padding:8px 12px;border:1px solid #1e3a5f;border-radius:6px;font-size:1em;outline:none;min-width:110px;">
                            <option value="">Gender</option>
                            <option value="M">Male</option>
                            <option value="F">Female</option>
                        </select>
                    </div>
                </div>
                <table class="student-table">
                    <thead>
                        <tr>
                            <th>LRN</th>
                            <th>Full Name</th>
                            <th>Section</th>
                            <th>Grade Level</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr 
                                    data-sex="<?php echo htmlspecialchars($row['sex']); ?>"
                                >
                                    <td data-label="LRN"><?php echo htmlspecialchars($row['lrn']); ?></td>
                                    <td data-label="Full Name"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td data-label="Section"><?php echo htmlspecialchars($row['section']); ?></td>
                                    <td data-label="Grade Level"><?php echo htmlspecialchars($row['grade_level']); ?></td>
                                    <td data-label="Action">
                                        <button class="view-btn" data-lrn="<?php echo htmlspecialchars($row['lrn']); ?>" style="background:#1e3a5f;color:#fff;border:none;border-radius:6px;padding:7px 16px;font-size:0.97em;font-weight:600;cursor:pointer;">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="padding:16px;text-align:center;color:#888;">No students registered by teachers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
        <!-- Student Info Modal -->
        <div class="modal-overlay" id="studentInfoModal">
            <div class="modal-content">
                <button class="modal-close" id="closeStudentModal" title="Close">&times;</button>
                <div class="modal-title">Student Information</div>
                <div class="modal-body" id="studentModalBody">
                    <div style="text-align:center; color:#888;">Loading...</div>
                </div>
            </div>
        </div>
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

        // Student search and filter
        document.addEventListener('DOMContentLoaded', function() {
            var searchInput = document.getElementById('studentSearch');
            var sexFilter = document.getElementById('sexFilter');
            var tableBody = document.getElementById('studentsTableBody');
            function filterTable() {
                var filter = searchInput.value.toLowerCase();
                var sex = sexFilter.value;
                var rows = tableBody.getElementsByTagName('tr');
                for (var i = 0; i < rows.length; i++) {
                    var lrnCell = rows[i].querySelector('td[data-label="LRN"]');
                    var nameCell = rows[i].querySelector('td[data-label="Full Name"]');
                    var lrn = lrnCell ? lrnCell.textContent.toLowerCase() : '';
                    var name = nameCell ? nameCell.textContent.toLowerCase() : '';
                    var sexVal = rows[i].getAttribute('data-sex') ? rows[i].getAttribute('data-sex').trim() : '';
                    // Accept both "M"/"F"/"Other" and "Male"/"Female"/"Other"
                    var matchSex = !sex || sexVal === sex || 
                        (sex === "M" && sexVal.toLowerCase() === "male") ||
                        (sex === "F" && sexVal.toLowerCase() === "female") ||
                        (sex === "Other" && sexVal.toLowerCase() === "other");
                    var matchSearch = (lrn.indexOf(filter) > -1 || name.indexOf(filter) > -1);
                    rows[i].style.display = (matchSearch && matchSex) ? '' : 'none';
                }
            }
            if (searchInput && tableBody && sexFilter) {
                searchInput.addEventListener('input', filterTable);
                sexFilter.addEventListener('change', filterTable);
            }
            // Modal functionality for student details
            var modal = document.getElementById('studentInfoModal');
            var modalBody = document.getElementById('studentModalBody');
            var closeModalBtn = document.getElementById('closeStudentModal');
            if (tableBody) {
                tableBody.addEventListener('click', function(e) {
                    var btn = e.target.closest('.view-btn');
                    if (btn) {
                        var lrn = btn.getAttribute('data-lrn');
                        modal.classList.add('active');
                        modalBody.innerHTML = '<div style="text-align:center; color:#888;">Loading...</div>';
                        fetch('student_info_modal.php?lrn=' + encodeURIComponent(lrn))
                            .then(function(response) { return response.text(); })
                            .then(function(html) { modalBody.innerHTML = html; })
                            .catch(function() { modalBody.innerHTML = '<div style="color:#dc3545;">Failed to load student info.</div>'; });
                    }
                });
            }
            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', function() {
                    modal.classList.remove('active');
                    modal.style.display = 'none';
                });
            }
            window.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('active');
                    modal.style.display = 'none';
                }
            });
            window.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    modal.classList.remove('active');
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
