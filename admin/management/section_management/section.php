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

// --- DB CONNECTION ---
require_once '../../../connection.php';
$conn = getConnection();

// Fetch all sections
$sections = [];
$section_sql = "SELECT `id`, `grade_level`, `section_name` FROM `sections`";
$section_result = $conn->query($section_sql);
if ($section_result && $section_result->num_rows > 0) {
    while ($row = $section_result->fetch_assoc()) {
        $sections[] = $row;
    }
}

// Fetch students grouped by section
$students_by_section = [];
$student_sql = "SELECT `id`, `lrn`, `last_name`, `first_name`, `middle_name`, `sex`, `grade_level`, `section` FROM `students`";
$student_result = $conn->query($student_sql);
if ($student_result && $student_result->num_rows > 0) {
    while ($row = $student_result->fetch_assoc()) {
        // Use both grade_level and section name as the key
        $key = $row['grade_level'] . '||' . $row['section'];
        if (!isset($students_by_section[$key])) $students_by_section[$key] = [];
        $students_by_section[$key][] = $row;
    }
}

function section_avatar_svg($section_name, $size = 120) {
    $first = mb_strtoupper(mb_substr(trim($section_name), 0, 1));
    $gradients = [
        ['#0a1a3a', '#1e3a5f'],
        ['#1e3a5f', '#28a745'],
        ['#1e3a5f', '#ffc107'],
        ['#0a1a3a', '#17a2b8'],
        ['#28a745', '#ffc107'],
        ['#0a1a3a', '#fd7e14'],
        ['#1e3a5f', '#6f42c1'],
        ['#0a1a3a', '#dc3545'],
    ];
    $idx = ord($first) % count($gradients);
    $g = $gradients[$idx];
    $svg = '<svg width="'.$size.'" height="'.$size.'" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="'.$g[0].'" />
                <stop offset="100%" stop-color="'.$g[1].'" />
            </linearGradient>
        </defs>
        <circle cx="'.($size/2).'" cy="'.($size/2).'" r="'.($size/2).'" fill="url(#grad)" />
        <text x="50%" y="50%" text-anchor="middle" dominant-baseline="central" font-size="'.($size*0.55).'" fill="#fff" font-family="Segoe UI, Arial, sans-serif" font-weight="bold">'.$first.'</text>
    </svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" width="device-width, initial-scale=1.0">
    <title>Section Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ...copy the full CSS from admin_dashboard.php here... */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html {
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #d3d3d3;
            overflow: hidden; /* Prevent page scroll */
        }
        .wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            height: 100vh;
        }
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
        main {
            flex: 1;
            max-width: 1600px;
            margin: 30px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(4px);
            background: #fff;
            width: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Prevent main scroll */
        }
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
        /* Section Table Styles */
        .section-table-container {
            width: 100%;
            max-width: 100%;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.10);
            margin-bottom: 36px;
            box-sizing: border-box;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            overflow-y: hidden;
            padding: 0; /* Remove padding from container, move to header/card if needed */
        }
        .section-header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            padding: 20px 20px 0 20px;
            background: #fff;
            border-radius: 8px 8px 0 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            min-height: 80px;
            box-sizing: border-box;
            flex-wrap: wrap;
            gap: 12px;
        }
        .section-header-info {
            font-size: 16px;
            color: #333;
            font-weight: bold;
        }
        .section-header-filters {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .section-filter-select {
            padding:8px 12px;
            border:1px solid #1e3a5f;
            border-radius:6px;
            font-size:1em;
            outline:none;
            min-width:110px;
        }
        .section-card-list {
            margin-top: 0;
            flex: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            align-content: flex-start;
            overflow-y: auto;
            padding: 20px;
            min-height: 200px;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .section-card-list::-webkit-scrollbar {
            display: none;
        }
        .section-card {
            width: 320px;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: white;
            text-align: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
            margin-bottom: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            box-sizing: border-box;
        }
        .section-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            background: #f4f6fa;
        }
        .section-card-icon {
            width: 120px;
            height: 120px;
            background: #1e3a5f;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 18px auto 0 auto;
            color: #fff;
            font-size: 60px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .section-card-body {
            padding: 20px;
            width: 100%;
        }
        .section-card-body h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #0a1a3a;
            font-weight: bold;
        }
        .section-card-body p {
            font-size: 15px;
            color: #555;
            margin: 0;
        }
        .section-no-students {
            color: #888; 
            margin-left: 8px;
            text-align: left;
            padding: 10px 0 10px 8px;
        }
        .section-modal {
            display: none;
            position: fixed;
            z-index: 3000; /* Increased z-index for modal */
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.6);
        }
        .section-modal-content {
            background-color: white;
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            margin: 0;
            padding: 48px 40px 40px 40px;
            border-radius: 22px;
            width: 98vw;
            max-width: 1400px;
            max-height: 92vh;
            box-shadow: 0 12px 36px rgba(0,0,0,0.40);
            text-align: left;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            border: 2px solid #1e3a5f22;
        }
        .section-modal-content h3 {
            margin-bottom: 28px;
            color: #0a1a3a;
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 1px;
        }
        .section-modal-content .section-no-students {
            text-align: center;
            color: #888;
            font-size: 20px;
            margin: 40px 0;
        }
        .section-modal-content .modal-table-container {
            width: 100%;
            overflow-x: auto;
            margin-top: 10px;
            margin-bottom: 10px;
            position: relative;
            max-height: 60vh;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .section-modal-content .modal-table-container::-webkit-scrollbar {
            display: none;
        }
        .section-modal-content table.section-table {
            width: 100%;
            border-collapse: collapse;
            background: #f9f9f9;
            margin: 0;
            font-size: 17px;
        }
        .section-modal-content th, .section-modal-content td {
            padding: 16px 12px;
            border-bottom: 1px solid #e0e0e0;
            color: #333;
            text-align: center;
        }
        .section-modal-content th {
            background: #1e3a5f;
            color: #fff;
            font-weight: bold;
            font-size: 18px;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .section-modal-content tr:last-child td {
            border-bottom: none;
        }
        .section-modal-content tr:nth-child(even) {
            background-color: #f4f6fa;
        }
        .section-close {
            color: #aaa;
            position: absolute;
            top: 18px;
            right: 32px;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
            z-index: 10;
        }
        .section-close:hover,
        .section-close:focus {
            color: #000;
            text-decoration: none;
        }
        @media (max-width: 1300px) {
            .section-modal-content {
                max-width: 99vw;
                padding: 20px 6px 20px 6px;
            }
            .section-modal-content table.section-table th,
            .section-modal-content table.section-table td {
                font-size: 15px;
                padding: 10px 4px;
            }
        }
        @media (max-width: 700px) {
            .section-modal-content {
                padding: 4px 0 4px 0;
                border-radius: 10px;
            }
            .section-modal-content h3 {
                font-size: 18px;
            }
        }
        /* Add horizontal scroll for table on small screens */
        .section-modal-content .modal-table-container {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <!-- Modal overlay always above all content -->
    <div id="modalOverlay" style="display:none; position:fixed; z-index:2999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.01); pointer-events:none;"></div>
    <div class="wrapper">
        <header>
        <div class="breadcrumb" style="display: flex; align-items: center; justify-content: center; gap: 3px; height: 100%;">
                <a href="../../../admin_dashboard.php"><h1>Admin Dashboard</h1></a>
                <span>&#8250;</span>
                <a href="section.php"><h1>Section Management</h1></a>
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
                            <a href="../student_management/student.php">Student Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="../student_management/#">#</a>
                                <a href="../student_management/#">#</a>
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
                            <a href="section.php">Section Management</a>
                            <div class="dropdown-content-2" style="left: -160px; top: 0;">
                                <a href="#">#</a>
                                <a href="#">#</a>
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
            <div class="section-table-container">
                <div class="section-header-actions">
                    <div class="section-header-info">
                        <p><strong>Total Sections:</strong> <span id="totalSections"><?php echo count($sections); ?></span></p>
                    </div>
                    <div class="section-header-filters">
                        <select id="gradeFilter" class="section-filter-select">
                            <option value="">Grade Level</option>
                            <?php
                                $grades = array_unique(array_column($sections, 'grade_level'));
                                sort($grades);
                                foreach ($grades as $grade) {
                                    echo '<option value="'.htmlspecialchars($grade).'">'.htmlspecialchars($grade).'</option>';
                                }
                            ?>
                        </select>
                        <select id="sectionNameFilter" class="section-filter-select">
                            <option value="">Section Name</option>
                            <?php
                                $section_names = array_unique(array_column($sections, 'section_name'));
                                sort($section_names);
                                foreach ($section_names as $sname) {
                                    echo '<option value="'.htmlspecialchars($sname).'">'.htmlspecialchars($sname).'</option>';
                                }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="section-card-list">
                    <?php if (count($sections) === 0): ?>
                        <div class="section-no-students">No sections found.</div>
                    <?php else: ?>
                        <div id="noSectionResults" class="section-no-students" style="display:none;">No sections found.</div>
                        <?php foreach ($sections as $section): ?>
                            <?php
                                $key = $section['grade_level'] . '||' . $section['section_name'];
                                $students = isset($students_by_section[$key]) ? $students_by_section[$key] : [];
                                $section_id = 'section_' . htmlspecialchars($section['grade_level']) . '_' . htmlspecialchars($section['section_name']);
                                $modal_id = 'modal_' . md5($section_id);
                                $avatar_svg = section_avatar_svg($section['section_name']);
                            ?>
                            <div class="section-card" data-grade="<?= htmlspecialchars($section['grade_level']) ?>" data-section="<?= htmlspecialchars($section['section_name']) ?>" onclick="openSectionModal('<?= $modal_id ?>')">
                                <div class="section-card-icon" style="padding:0;">
                                    <img src="<?= $avatar_svg ?>" alt="<?= htmlspecialchars($section['section_name']) ?>" style="width: 100%; height: 100%; border-radius: 50%; display: block;">
                                </div>
                                <div class="section-card-body">
                                    <h3>Grade <?= htmlspecialchars($section['grade_level']) ?> - <?= htmlspecialchars($section['section_name']) ?></h3>
                                    <p><?= count($students) ?> student<?= count($students) == 1 ? '' : 's' ?></p>
                                </div>
                            </div>
                            <!-- Modal for section students -->
                            <div id="<?= $modal_id ?>" class="section-modal">
                                <div class="section-modal-content">
                                    <span class="section-close" onclick="closeSectionModal('<?= $modal_id ?>')">&times;</span>
                                    <h3>Students in Grade <?= htmlspecialchars($section['grade_level']) ?> - <?= htmlspecialchars($section['section_name']) ?></h3>
                                    <?php if (count($students) === 0): ?>
                                        <div class="section-no-students">No students in this section.</div>
                                    <?php else: ?>
                                        <div class="modal-table-container">
                                            <table class="section-table">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>LRN</th>
                                                        <th>Last Name</th>
                                                        <th>First Name</th>
                                                        <th>Middle Name</th>
                                                        <th>Sex</th>
                                                        <th>Grade Level</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($students as $idx => $stu): ?>
                                                        <tr>
                                                            <td><?= $idx + 1 ?></td>
                                                            <td><?= htmlspecialchars($stu['lrn']) ?></td>
                                                            <td><?= htmlspecialchars($stu['last_name']) ?></td>
                                                            <td><?= htmlspecialchars($stu['first_name']) ?></td>
                                                            <td><?= htmlspecialchars($stu['middle_name']) ?></td>
                                                            <td><?= htmlspecialchars($stu['sex']) ?></td>
                                                            <td><?= htmlspecialchars($stu['grade_level']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
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
        // Section search/filter
        document.addEventListener('DOMContentLoaded', function() {
            var gradeFilter = document.getElementById('gradeFilter');
            var sectionNameFilter = document.getElementById('sectionNameFilter');
            var sectionCards = document.querySelectorAll('.section-card');
            var cardList = document.querySelector('.section-card-list');
            var totalSections = document.getElementById('totalSections');
            var noSectionResults = document.getElementById('noSectionResults');
            function filterSections() {
                var grade = gradeFilter.value;
                var sname = sectionNameFilter.value;
                var visibleCount = 0;
                sectionCards.forEach(function(card) {
                    var showCard = true;
                    if (grade && card.getAttribute('data-grade') !== grade) showCard = false;
                    if (sname && card.getAttribute('data-section') !== sname) showCard = false;
                    card.style.display = showCard ? '' : 'none';
                    if (showCard) visibleCount++;
                });
                cardList.style.justifyContent = visibleCount === 0 ? 'center' : 'center';
                totalSections.textContent = visibleCount;
                if (noSectionResults) noSectionResults.style.display = visibleCount === 0 ? 'block' : 'none';
            }
            if (gradeFilter && sectionNameFilter) {
                gradeFilter.addEventListener('change', filterSections);
                sectionNameFilter.addEventListener('change', filterSections);
            }
        });
        function openSectionModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }
        function closeSectionModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }
        window.onclick = function(event) {
            var modals = document.querySelectorAll('.section-modal');
            modals.forEach(function(modal) {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            });
        };
    </script>
</body>
</html>
<?php
$conn->close();
?>
