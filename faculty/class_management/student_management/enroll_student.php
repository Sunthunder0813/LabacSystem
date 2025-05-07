<?php
session_start();
require_once '../../../connection.php';

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

if (!isset($_SESSION['faculty_logged_in']) || $_SESSION['faculty_logged_in'] !== true) {
    header('Location: faculty_login.php');
    exit();
}

require '../../../vendor/autoload.php'; // Adjust the path if necessary

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Set current school year (adjust as needed)
$current_school_year = date('Y') . '-' . (date('m') >= 6 ? (date('Y')+1) : date('Y'));

// Handle Excel file upload
if (isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    // Fetch employee_id of the logged-in faculty
    $faculty_id = $_SESSION['faculty_id'];
    $employee_id = null;
    $emp_stmt = $conn->prepare("SELECT unique_id FROM users WHERE id = ?");
    $emp_stmt->bind_param("i", $faculty_id);
    $emp_stmt->execute();
    $emp_stmt->bind_result($employee_id);
    $emp_stmt->fetch();
    $emp_stmt->close();

    // Load the spreadsheet
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();

    // Get merged cells and map all cells in merged ranges to their top-left value
    $mergedCells = $sheet->getMergeCells();
    $mergedMap = [];
    foreach ($mergedCells as $mergedRange) {
        $cells = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::extractAllCellReferencesInRange($mergedRange);
        $topLeft = $cells[0];
        $value = $sheet->getCell($topLeft)->getValue();
        foreach ($cells as $cell) {
            $mergedMap[$cell] = $value;
        }
    }

    // Helper to get merged value or normal value
    function getMergedCellValue($sheet, $cell, $mergedMap) {
        return isset($mergedMap[$cell]) ? $mergedMap[$cell] : $sheet->getCell($cell)->getValue();
    }

    // Get grade level and section from header cells (adjust cell references as needed)
    $grade_level = getMergedCellValue($sheet, 'U6', $mergedMap) ?? '';
    $section = getMergedCellValue($sheet, 'X6', $mergedMap) ?? '';

    if ($grade_level && $section) {
        // Try to insert section (ignore if duplicate)
        try {
            $ins_stmt = $conn->prepare("INSERT INTO sections (grade_level, section_name) VALUES (?, ?)");
            $ins_stmt->bind_param("ss", $grade_level, $section);
            $ins_stmt->execute();
            $ins_stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() != 1062) { // 1062 = duplicate entry
                throw $e;
            }
            // else: ignore duplicate entry
        }

        // Get section_id (ensure it exists)
        $sec_stmt = $conn->prepare("SELECT id FROM sections WHERE grade_level = ? AND section_name = ?");
        $sec_stmt->bind_param("ss", $grade_level, $section);
        $sec_stmt->execute();
        $sec_stmt->bind_result($section_id);
        $sec_stmt->fetch();
        $sec_stmt->close();

        // Assign faculty as adviser for this section and school year if not already assigned
        $advisory_check = $conn->prepare("SELECT id FROM class_advisory WHERE employee_id = ? AND section_id = ? AND school_year = ?");
        $advisory_check->bind_param("sis", $employee_id, $section_id, $current_school_year);
        $advisory_check->execute();
        $advisory_check->store_result();
        if ($advisory_check->num_rows == 0) {
            $advisory_check->close();
            $advisory_insert = $conn->prepare("INSERT INTO class_advisory (employee_id, section_id, school_year) VALUES (?, ?, ?)");
            $advisory_insert->bind_param("sis", $employee_id, $section_id, $current_school_year);
            $advisory_insert->execute();
            $advisory_insert->close();
        } else {
            $advisory_check->close();
        }
    }

    // Prepare the SQL statement (now 25 columns, adding grade_level and section)
    $stmt = $conn->prepare("INSERT INTO students (lrn, last_name, first_name, middle_name, name, sex, birth_date, age, mother_tongue, religion, house, barangay, city, province, father, mother, guardian_name, guardian_relation, contact, remarks, registered_by, date_time_registered, status, grade_level, section) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $success_count = 0;
    $error_count = 0;

    // Get merged cells
    $mergedCells = $sheet->getMergeCells();
    $mergedValues = [];

    // Store merged values
    foreach ($mergedCells as $mergedCell) {
        $cellRange = explode(':', $mergedCell);
        $topLeftCell = $cellRange[0];
        $value = $sheet->getCell($topLeftCell)->getValue();
        $mergedValues[$topLeftCell] = $value;
    }

    // Loop through the rows of the spreadsheet, starting from row 10
    $highestRow = $sheet->getHighestRow();
    for ($rowIdx = 10; $rowIdx <= $highestRow; $rowIdx++) {
        // Get values from Excel columns
        $lrn = $sheet->getCell('B' . $rowIdx)->getValue();
        if (empty($lrn)) {
            continue;
        }

        // Check for duplicate LRN
        $check_stmt = $conn->prepare("SELECT id FROM students WHERE lrn = ?");
        $check_stmt->bind_param("s", $lrn);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            $error_count++;
            $check_stmt->close();
            continue; // Skip duplicate LRN
        }
        $check_stmt->close();

        // Name is merged in C (C, D, E, F merged)
        $name = $sheet->getCell('C' . $rowIdx)->getValue() ?? '';

        // Enhanced name parsing logic for double surname, double given name, and suffix
        $last_name = '';
        $first_name = '';
        $middle_name = '';
        $extension = '';

        if ($name) {
            // Try to parse "Lastname [Lastname2], Firstname [Firstname2] Middlename [Suffix]"
            $parts = explode(',', $name, 2);
            $last_name = trim($parts[0]);
            if (isset($parts[1])) {
                $rest = trim($parts[1]);
                $restParts = preg_split('/\s+/', $rest);

                // If there are at least 2 parts, assume the first two are first names (double given name)
                if (count($restParts) >= 2) {
                    $first_name = $restParts[0];
                    // Check for double given name (e.g., "Juan Carlo")
                    if (preg_match('/^[A-Za-z\-]+$/', $restParts[1])) {
                        $first_name .= ' ' . $restParts[1];
                        $middle_name = $restParts[2] ?? '';
                        $extension = $restParts[3] ?? '';
                    } else {
                        $middle_name = $restParts[1] ?? '';
                        $extension = $restParts[2] ?? '';
                    }
                } else {
                    $first_name = $restParts[0] ?? '';
                    $middle_name = $restParts[1] ?? '';
                    $extension = $restParts[2] ?? '';
                }

                // Handle double surname (e.g., "De la Cruz, Juan Carlo")
                // If last_name contains spaces, keep as is (double surname)
                // If extension is a known suffix, move it to $extension
                $suffixes = ['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'];
                if ($extension && in_array($extension, $suffixes)) {
                    // already in $extension
                } elseif ($middle_name && in_array($middle_name, $suffixes)) {
                    $extension = $middle_name;
                    $middle_name = '';
                }
            }
        }

        $sex = $sheet->getCell('G' . $rowIdx)->getValue() ?? '';
        // Birth date: convert Excel date to Y-m-d if needed
        $birth_date_cell = $sheet->getCell('H' . $rowIdx);
        $birth_date_raw = $birth_date_cell->getValue();
        $birth_date = '';
        if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($birth_date_cell)) {
            // If Excel date, convert to PHP date
            $phpDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($birth_date_raw);
            $birth_date = $phpDate->format('Y-m-d');
        } elseif (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $birth_date_raw)) {
            // If string in mm/dd/yyyy, convert to Y-m-d
            $dt = \DateTime::createFromFormat('m/d/Y', $birth_date_raw);
            $birth_date = $dt ? $dt->format('Y-m-d') : $birth_date_raw;
        } else {
            $birth_date = $birth_date_raw ?? '';
        }
        $age = $sheet->getCell('I' . $rowIdx)->getValue() ?? '';
        $mother_tongue = $sheet->getCell('L' . $rowIdx)->getValue() ?? '';
        $religion = $sheet->getCell('N' . $rowIdx)->getValue() ?? '';
        $house = $sheet->getCell('O' . $rowIdx)->getValue() ?? '';
        $barangay = $sheet->getCell('P' . $rowIdx)->getValue() ?? '';
        $city = $sheet->getCell('Q' . $rowIdx)->getValue() ?? '';
        $province1 = $sheet->getCell('R' . $rowIdx)->getValue() ?? '';
        $province2 = $sheet->getCell('S' . $rowIdx)->getValue() ?? '';
        $province = trim($province1 . ($province2 ? ' ' . $province2 : ''));
        $father_last = $sheet->getCell('T' . $rowIdx)->getValue() ?? '';
        $father_first = $sheet->getCell('U' . $rowIdx)->getValue() ?? '';
        $father = trim($father_last . ($father_first ? ', ' . $father_first : ''));
        $mother_last = $sheet->getCell('V' . $rowIdx)->getValue() ?? '';
        $mother_first = $sheet->getCell('W' . $rowIdx)->getValue() ?? '';
        $mother = trim($mother_last . ($mother_first ? ', ' . $mother_first : ''));
        $guardian_name = $sheet->getCell('X' . $rowIdx)->getValue() ?? '';
        $guardian_relation = $sheet->getCell('Y' . $rowIdx)->getValue() ?? '';
        $contact = $sheet->getCell('Z' . $rowIdx)->getValue() ?? '';
        $remarks = $sheet->getCell('AA' . $rowIdx)->getValue() ?? '';

        // Optionally skip if first_name or last_name is empty
        if ($first_name === '' || $last_name === '') {
            $error_count++;
            continue;
        }

        // Use employer_id as registered_by
        $registered_by = $employee_id;
        $date_time_registered = date('Y-m-d H:i:s');
        $status = 'enrolled'; // Default status is 'enrolled', change to 'unenrolled' if needed

        // Bind parameters including section_id
        $stmt->bind_param(
            "sssssssisssssssssssssssss", // <-- 25 type specifiers: 7s, 1i, 17s
            $lrn,
            $last_name,
            $first_name,
            $middle_name,
            $name,
            $sex,
            $birth_date,
            $age,
            $mother_tongue,
            $religion,
            $house,
            $barangay,
            $city,
            $province,
            $father,
            $mother,
            $guardian_name,
            $guardian_relation,
            $contact,
            $remarks,
            $registered_by,
            $date_time_registered,
            $status,
            $grade_level,
            $section // use the section id, not the name
        );

        if ($stmt->execute()) {
            $success_count++;
            // Also insert into student_history for archival
            $history_stmt = $conn->prepare("INSERT INTO student_history (lrn, last_name, first_name, middle_name, name, sex, birth_date, age, mother_tongue, religion, house, barangay, city, province, father, mother, guardian_name, guardian_relation, contact, remarks, registered_by, date_time_registered, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $history_stmt->bind_param(
                "sssssssisssssssssssssss",
                $lrn,
                $last_name,
                $first_name,
                $middle_name,
                $name,
                $sex,
                $birth_date,
                $age,
                $mother_tongue,
                $religion,
                $house,
                $barangay,
                $city,
                $province,
                $father,
                $mother,
                $guardian_name,
                $guardian_relation,
                $contact,
                $remarks,
                $registered_by,
                $date_time_registered,
                $status
            );
            $history_stmt->execute();
            $history_stmt->close();
        } else {
            $error_count++;
        }
    }

    $stmt->close();
    $conn->close();

    if ($success_count > 0) {
        $success_message = "$success_count records imported successfully.";
    }
    if ($error_count > 0) {
        $error_message = "$error_count records failed to import.";
    }

}

// Handle Individual Enroll
if (isset($_POST['enroll_individual'])) {
    // Fetch employee_id of the logged-in faculty
    $faculty_id = $_SESSION['faculty_id'];
    $employee_id = null;
    $emp_stmt = $conn->prepare("SELECT unique_id FROM users WHERE id = ?");
    $emp_stmt->bind_param("i", $faculty_id);
    $emp_stmt->execute();
    $emp_stmt->bind_result($employee_id);
    $emp_stmt->fetch();
    $emp_stmt->close();

    // Collect and sanitize input
    $lrn = trim($_POST['lrn'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $sex = trim($_POST['sex'] ?? ''); // Get gender from form
    $birth_date = trim($_POST['birth_date'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $mother_tongue = trim($_POST['mother_tongue'] ?? '');
    $religion = trim($_POST['religion'] ?? '');
    $house = trim($_POST['house'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $father = trim($_POST['father'] ?? '');
    $mother = trim($_POST['mother'] ?? '');
    $guardian_name = trim($_POST['guardian_name'] ?? '');
    $guardian_relation = trim($_POST['guardian_relation'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $grade_level = trim($_POST['grade_level'] ?? '');
    $section = trim($_POST['section'] ?? '');

    // Compose full name for 'name' column
    $name = $last_name . ', ' . $first_name . ($middle_name ? ' ' . $middle_name : '');
    // Check for duplicate LRN
    $check_stmt = $conn->prepare("SELECT id FROM students WHERE lrn = ?");
    $check_stmt->bind_param("s", $lrn);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        $error_message = "LRN already exists. Student not enrolled.";
        $check_stmt->close();
    } else {
        $check_stmt->close();
        // Try to insert section (ignore if duplicate)
        if ($grade_level && $section) {
            try {
                $ins_stmt = $conn->prepare("INSERT INTO sections (grade_level, section_name) VALUES (?, ?)");
                $ins_stmt->bind_param("ss", $grade_level, $section);
                $ins_stmt->execute();
                $ins_stmt->close();
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() != 1062) {
                    throw $e;
                }
                // else: ignore duplicate entry
            }

            // Get section_id (ensure it exists)
            $sec_stmt = $conn->prepare("SELECT id FROM sections WHERE grade_level = ? AND section_name = ?");
            $sec_stmt->bind_param("ss", $grade_level, $section);
            $sec_stmt->execute();
            $sec_stmt->bind_result($section_id);
            $sec_stmt->fetch();
            $sec_stmt->close();

            // Assign faculty as adviser for this section and school year if not already assigned
            $advisory_check = $conn->prepare("SELECT id FROM class_advisory WHERE employee_id = ? AND section_id = ? AND school_year = ?");
            $advisory_check->bind_param("sis", $employee_id, $section_id, $current_school_year);
            $advisory_check->execute();
            $advisory_check->store_result();
            if ($advisory_check->num_rows == 0) {
                $advisory_check->close();
                $advisory_insert = $conn->prepare("INSERT INTO class_advisory (employee_id, section_id, school_year) VALUES (?, ?, ?)");
                $advisory_insert->bind_param("sis", $employee_id, $section_id, $current_school_year);
                $advisory_insert->execute();
                $advisory_insert->close();
            } else {
                $advisory_check->close();
            }
        }

        // Prepare insert (add grade_level, section)
        $stmt = $conn->prepare("INSERT INTO students (lrn, last_name, first_name, middle_name, name, sex, birth_date, age, mother_tongue, religion, house, barangay, city, province, father, mother, guardian_name, guardian_relation, contact, remarks, registered_by, date_time_registered, status, grade_level, section) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $date_time_registered = date('Y-m-d H:i:s');
        $status = 'enrolled';
        $stmt->bind_param(
            "sssssssisssssssssssssssss", // <-- 25 type specifiers
            $lrn,
            $last_name,
            $first_name,
            $middle_name,
            $name,
            $sex,
            $birth_date,
            $age,
            $mother_tongue,
            $religion,
            $house,
            $barangay,
            $city,
            $province,
            $father,
            $mother,
            $guardian_name,
            $guardian_relation,
            $contact,
            $remarks,
            $employee_id,
            $date_time_registered,
            $status,
            $grade_level,
            $section
        );
        if ($stmt->execute()) {
            // Also insert into student_history for archival
            $history_stmt = $conn->prepare("INSERT INTO student_history (lrn, last_name, first_name, middle_name, name, sex, birth_date, age, mother_tongue, religion, house, barangay, city, province, father, mother, guardian_name, guardian_relation, contact, remarks, registered_by, date_time_registered, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $history_stmt->bind_param(
                "sssssssisssssssssssssss",
                $lrn,
                $last_name,
                $first_name,
                $middle_name,
                $name,
                $sex,
                $birth_date,
                $age,
                $mother_tongue,
                $religion,
                $house,
                $barangay,
                $city,
                $province,
                $father,
                $mother,
                $guardian_name,
                $guardian_relation,
                $contact,
                $remarks,
                $employee_id,
                $date_time_registered,
                $status
            );
            $history_stmt->execute();
            $history_stmt->close();
            $success_message = "Student enrolled successfully.";
        } else {
            $error_message = "Failed to enroll student.";
        }
        $stmt->close();
    }
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
        html, body { height: 100%; }
        body {
            min-height: 100vh;
            height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e3ede7;
            overflow: hidden; /* Prevent body scroll */
        }
        .wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }
        header { background: linear-gradient(to right, #183325, #24513a); color: white; height: 60px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; box-shadow: 0 2px 6px rgba(24, 51, 37, 0.13); flex-shrink: 0; }
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
        footer { text-align: center; padding: 14px; background: linear-gradient(to right, #183325, #24513a); color: #eafaf3; width: 100%; margin-top: auto; }
        
        main {
            flex: 1 1 auto;
            width: 100%;
            max-width: 1400px;
            margin: 30px auto;
            padding: 30px 24px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(24, 51, 37, 0.13);
            backdrop-filter: blur(4px);
            background: #f8fff9;
            min-height: 600px;
            height: 600px;
            display: flex;
            flex-direction: column;
        }
        .enroll-flex-container {
            display: flex;
            width: 100%;
            min-height: 0;
            flex: 1 1 auto;
            flex-wrap: wrap;
            align-items: stretch;
            height: 100%; /* Add this line to make it fill the main container's height */
        }
        .enroll-left, .enroll-right {
            flex: 1 1 420px;
            min-width: 320px;
            max-width: 100%;
            padding: 32px 36px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(24,51,37,0.07);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            margin: 0;
            min-height: 600px;
            height: 600px;
        }
        .enroll-left {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 600px;
            height: 600px;
            transition: box-shadow 0.2s, border-color 0.2s;
            justify-content: stretch; /* Ensure children can stretch */
        }
        @media (max-width: 1100px) {
            main {
                min-height: 600px;
                height: 600px;
            }
            .enroll-flex-container {
                flex-direction: column;
                gap: 32px;
                align-items: stretch;
            }
            .enroll-left, .enroll-right {
                min-height: 0;
                height: auto;
                padding: 24px 8px;
            }
        }
        @media (max-width: 600px) {
            main {
                padding: 10px 2px;
                min-height: 600px;
                height: 600px;
            }
            .enroll-left, .enroll-right { padding: 12px 2px; }
        }
        .enroll-left form {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            background: none;
            border-radius: 0;
            box-shadow: none;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
            height: 100%; /* Make form fill parent */
            justify-content: stretch;
        }
        .enroll-left label {
            font-size: 1.1em;
            font-weight: 600;
            color: #24513a;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .enroll-left .dropzone-upload {
            width: 100%;
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            align-items: stretch;
            justify-content: center;
        }
        .enroll-left .dropzone-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            min-height: 220px;
            flex: 1 1 auto;
            border: 2.5px dashed #b7b728;
            border-radius: 18px;
            cursor: pointer;
            background: linear-gradient(135deg, #f8fff9 70%, #e3ede7 100%);
            transition: background 0.2s, border-color 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 24px 0 rgba(36,81,58,0.07);
        }
        .enroll-left .dropzone-label:hover,
        .enroll-left .dropzone-label:focus-within {
            background: #f3f6f2;
            border-color: #24513a;
            box-shadow: 0 6px 32px 0 rgba(36,81,58,0.10);
        }
        .enroll-left .dropzone-label.dragover {
            background: #eafaf3;
            border-color: #24513a;
        }
        .enroll-left .dropzone-label.has-file {
            border-color: #28a745;
            background: linear-gradient(135deg, #eafaf3 80%, #d4f5e9 100%);
            box-shadow: 0 6px 32px 0 rgba(40,167,69,0.08);
        }
        .enroll-left .dropzone-label svg {
            width: 54px;
            height: 54px;
            margin-bottom: 14px;
            color: #b7b728;
            transition: color 0.2s;
        }
        .enroll-left .dropzone-label.has-file svg {
            color: #28a745;
        }
        .enroll-left .dropzone-label .dz-main {
            font-weight: 700;
            color: #24513a;
            font-size: 1.13em;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        .enroll-left .dropzone-label .dz-sub {
            font-size: 0.98em;
            color: #b7b728;
            margin-bottom: 2px;
        }
        .enroll-left .dropzone-label .dz-filename {
            display: none;
            color: #24513a;
            font-size: 1.08em;
            margin-top: 12px;
            font-weight: 600;
            word-break: break-all;
            text-align: center;
            padding: 10px 0 0 0;
            background: #eafaf3;
            border-radius: 8px;
            width: 90%;
            box-shadow: 0 2px 8px rgba(40,167,69,0.07);
        }
        .enroll-left .dropzone-label.has-file .dz-main,
        .enroll-left .dropzone-label.has-file .dz-sub {
            display: none;
        }
        .enroll-left .dropzone-label.has-file .dz-filename {
            display: block;
        }
        .enroll-left .dropzone-label.has-file {
            animation: dzFileIn 0.2s;
        }
        @keyframes dzFileIn {
            from { background: #f8fff9; border-color: #b7b728; }
            to { background: #eafaf3; border-color: #28a745; }
        }
        .enroll-left input[type="file"] {
            display: none;
        }
        .enroll-left button {
            background: #24513a;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 12px 28px;
            font-size: 1em; /* Match .enroll-right button font size */
            font-weight: 600;
            cursor: pointer;
            margin-top: auto; /* Push button to the bottom */
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(24,51,37,0.10);
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center; /* Center icon/text horizontally */
            align-self: center;      /* Center the button itself in the form */
            min-width: 180px; /* Ensure same width as enroll_individual button */
        }
        .enroll-left button:hover {
            background: #183325;
            color: #b7b728;
        }
        .enroll-left .import-desc {
            font-size: 1.08em;
            color: #666;
            background: #f7faf9;
            border-left: 4px solid #b7b728;
            padding: 12px 22px;
            border-radius: 10px;
            margin-bottom: 10px;
            width: 100%;
            text-align: left;
            box-sizing: border-box;
            max-width: 540px;
            margin-right: auto;
        }
        @media (max-width: 1100px) {
            .enroll-left {
                padding: 24px 8px 24px 8px;
                min-height: 0;
                height: auto;
            }
            .enroll-left form {
                padding: 0;
                max-width: 100%;
                height: auto;
            }
            .enroll-left .dropzone-upload {
                min-height: 180px;
            }
            .enroll-left .dropzone-label {
                min-height: 180px;
            }
        }
        @media (max-width: 600px) {
            .enroll-left {
                padding: 10px 2px 10px 2px;
                min-height: 0;
                height: auto;
            }
            .enroll-left form {
                padding: 0;
                max-width: 100%;
                height: auto;
            }
            .enroll-left .dropzone-upload {
                min-height: 120px;
            }
            .enroll-left .dropzone-label {
                min-height: 120px;
            }
        }
        .enroll-right {
            align-items: flex-start;
        }
        .enroll-divider {
            display: none;
        }
        @media (max-width: 1100px) {
            .enroll-flex-container {
                flex-direction: column;
                gap: 32px;
            }
            .enroll-left, .enroll-right {
                margin: 0;
                padding: 24px 8px;
            }
        }
        @media (max-width: 600px) {
            main { padding: 10px 2px; }
            .enroll-left, .enroll-right { padding: 12px 2px; }
        }
        .enroll-left form, .enroll-right form {
            width: 100%;
        }
        .enroll-left label, .enroll-right label {
            font-weight: 600;
            color: #24513a;
            margin-bottom: 8px;
            display: block;
        }
        .enroll-left input[type="file"] {
            margin: 12px 0 18px 0;
            padding: 8px 0;
            font-size: 1em;
        }
        .enroll-left button, .enroll-right button {
            background: #24513a;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 22px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(24,51,37,0.08);
            justify-content: center; /* Center icon/text horizontally */
            align-self: center;      /* Center the button itself in the form */
        }
        .enroll-left button:hover, .enroll-right button:hover {
            background: #183325;
            color: #b7b728;
        }
        .enroll-right form > div:first-child {
            font-weight: bold;
            margin-bottom: 18px;
            color: #24513a;
            font-size: 1.15em;
        }
        .enroll-right input[type="text"],
        .enroll-right input[type="number"],
        .enroll-right input[type="date"] {
            padding: 10px 12px;
            border: 1px solid #b7b728;
            border-radius: 6px;
            font-size: 1em;
            outline: none;
            transition: border-color 0.2s;
            background: #f8fff9;
        }
        .enroll-right input:focus {
            border-color: #24513a;
            background: #fff;
        }
        .enroll-right form > div {
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        .enroll-right .row-1,
        .enroll-right .row-5,
        .enroll-right .row-9,
        .enroll-right .row-10 {
            display: flex;
            flex-direction: row;
            gap: 10px;
            margin-bottom: 10px;
        }
        .enroll-right .row-2,
        .enroll-right .row-3,
        .enroll-right .row-6,
        .enroll-right .row-7,
        .enroll-right .row-8 {
            display: flex;
            flex-direction: row;
            gap: 10px;
            margin-bottom: 10px;
        }
        .enroll-right .row-1 input,
        .enroll-right .row-5 input,
        .enroll-right .row-9 input,
        .enroll-right .row-10 input {
            flex: 1 1 0;
        }
        .enroll-right .row-2 input,
        .enroll-right .row-3 input,
        .enroll-right .row-7 input,
        .enroll-right .row-8 input {
            flex: 1 1 0;
        }
        .enroll-right .row-4 input,
        .enroll-right .row-6 input {
            flex: 1 1 0;
        }
        .enroll-right .row-4 {
            display: flex;
            flex-direction: row;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .enroll-right .row-4 input,
        .enroll-right .row-4 select,
        .enroll-right .row-4 label {
            flex: 1 1 0;
            min-width: 0;
        }
        .enroll-right .row-4 label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            color: #24513a;
            white-space: nowrap;
            flex: 1 1 0;
            min-width: 0;
        }
        .enroll-right .row-6 {
            display: flex;
            flex-direction: row;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .enroll-right .row-6 input {
            flex: 1 1 0;
        }
        .enroll-right form > div > button {
            margin-top: 10px;
            align-self: center; /* Center the button in the form */
            min-width: 180px;
        }
        @media (max-width: 900px) {
            .enroll-right form > div,
            .enroll-right .row-1,
            .enroll-right .row-2,
            .enroll-right .row-3,
            .enroll-right .row-4,
            .enroll-right .row-5,
            .enroll-right .row-6,
            .enroll-right .row-7,
            .enroll-right .row-8,
            .enroll-right .row-9,
            .enroll-right .row-10 {
                flex-direction: column !important;
                gap: 8px !important;
            }
            .enroll-right form > div > button {
                align-self: stretch;
            }
        }
        .enroll-left form label {
            margin-bottom: 10px;
        }
        .enroll-left form button {
            margin-top: 0;
            align-self: center; /* Center the button in the form */
        }
        .enroll-left form {
            background: none;
            box-shadow: none;
            border-radius: 0;
        }
        .enroll-left form input[type="file"] {
            border: none;
            background: none;
        }
        .enroll-left form label b {
            color: #24513a;
        }
        .enroll-note {
            color: #a74528;
            margin-bottom: 18px;
            font-size: 1em;
            background: #fff7f3;
            border-left: 4px solid #a74528;
            padding: 10px 18px;
            border-radius: 6px;
        }
        @media (max-width: 1100px) {
            .enroll-flex-container {
                flex-direction: column;
                gap: 32px;
            }
            .enroll-divider {
                width: 100%;
                height: 2px;
                min-height: unset;
                margin: 24px 0;
                background: linear-gradient(to right, #e3ede7 60%, #b7b728 100%);
            }
            .enroll-left, .enroll-right {
                margin: 0;
                padding: 24px 8px;
            }
        }
        @media (max-width: 600px) {
            main { padding: 10px 2px; }
            .enroll-left, .enroll-right { padding: 12px 2px; }
            .enroll-note { padding: 8px 8px; font-size: 0.97em; }
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
        .enroll-right .row-4,
        .enroll-right .row-6 {
            display: flex;
            flex-direction: row;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .enroll-right .row-4 input,
        .enroll-right .row-4 select,
        .enroll-right .row-4 label {
            flex: 1 1 0;
            min-width: 0;
        }
        .enroll-right .row-4 label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            color: #24513a;
            white-space: nowrap;
            flex: 1 1 0;
            min-width: 0;
        }
        .enroll-right .row-6 {
            display: flex;
            flex-direction: row;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .enroll-right .row-6 input {
            flex: 1 1 0;
        }
        .enroll-right form > div > button {
            margin-top: 10px;
            align-self: center; /* Center the button in the form */
            min-width: 180px;
        }
        @media (max-width: 900px) {
            .enroll-right form > div,
            .enroll-right .row-1,
            .enroll-right .row-2,
            .enroll-right .row-3,
            .enroll-right .row-4,
            .enroll-right .row-5,
            .enroll-right .row-6,
            .enroll-right .row-7,
            .enroll-right .row-8,
            .enroll-right .row-9,
            .enroll-right .row-10 {
                flex-direction: column !important;
                gap: 8px !important;
            }
            .enroll-right form > div > button {
                align-self: stretch;
            }
        }
        .enroll-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 18px;
            padding-left: 10px;
            flex-wrap: wrap;
        }
        .enroll-header-container {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-left: 0;
        }
        .enroll-tab-selector {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 0;
            margin-left: 0;
        }
        @media (max-width: 900px) {
            .enroll-header-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                padding-left: 0;
            }
            .enroll-tab-selector {
                margin-left: 0;
                margin-top: 6px;
            }
        }
        .enroll-header-container {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-left: 10px;
        }
        .enroll-header-title {
            font-size: 1.7em;
            font-weight: 700;
            color: #24513a;
            letter-spacing: 1px;
        }
        .enroll-tab-selector {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 24px;
            margin-left: 10px;
        }
        .enroll-tab {
            padding: 10px 36px;
            font-size: 1.13em;
            font-weight: 600;
            color: #24513a;
            background: #eafaf3;
            border-radius: 10px 10px 0 0;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            border: 1.5px solid #b7b728;
            border-bottom: none;
            margin-right: 0;
        }
        .enroll-tab-active {
            background: #fff;
            color: #183325;
            border-bottom: 2.5px solid #fff;
            z-index: 2;
            /* Add glow effect */
            box-shadow: 0 0 0 2px #b7b728, 0 4px 24px 0 rgba(183,183,40,0.18);
            filter: drop-shadow(0 0 8px #b7b72888);
        }
        .enroll-tab-divider {
            width: 2px;
            height: 28px;
            background: linear-gradient(to bottom, #b7b728 60%, #e3ede7 100%);
            margin: 0 0px;
            border-radius: 2px;
        }
        .enroll-flex-with-divider {
            position: relative;
            display: flex;
            flex-direction: row;
            align-items: stretch;
            gap: 0;
        }
        .enroll-divider-vertical {
            width: 2.5px;
            background: linear-gradient(to bottom, #b7b728 60%, #e3ede7 100%);
            margin: 0 0;
            min-height: 600px;
            height: 100%;
            align-self: stretch;
            border-radius: 2px;
            z-index: 1;
        }
        @media (max-width: 1100px) {
            .enroll-flex-with-divider {
                flex-direction: column;
            }
            .enroll-divider-vertical {
                display: none;
            }
            .enroll-tab-selector {
                flex-direction: row;
                margin-bottom: 18px;
            }
        }
        @media (max-width: 600px) {
            .enroll-header-title { font-size: 1.1em; }
            .enroll-header-icon { font-size: 1.2em; padding: 6px 8px 6px 7px; }
            .enroll-tab { font-size: 0.98em; padding: 7px 12px; }
        }
        /* Hide/show tab content (optional, JS below) */
        .enroll-tab-content { display: block; }
        .enroll-tab-content.hide { display: none; }
    </style>
</head>
<body>
    <div class="wrapper">
    <header>
        <div class="breadcrumb" style="display: flex; align-items: center; justify-content: center; gap: 3px; height: 100%;">
            <a href="../../faculty_dashboard.php"><h1>Faculty Dashboard</h1></a>
            <span>&#8250;</span>
            <a href=""><h1>Class Management</h1></a>
            <span>&#8250;</span>
            <a href="../student_management.php"><h1>Student Management</h1></a>
            <span>&#8250;</span>
            <a href="enroll_student.php"><h1>Enroll Student</h1></a>
        </div>
        <nav>
            <div class="dropdown">
                <a href="teacher_advisory.php">Class Management</a>
                <div class="dropdown-content">
                    <a href="teacher_advisory.php">My Advisory</a>
                    <div class="dropdown">
                        <a href="../student_management.php">Student Management</a>
                        <div class="dropdown-content-2" style="left: -160px; top: 0;">
                            <a href="enroll_student.php">Enroll Student</a>
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
                    <a href="school_calendar.php">School Calendar</a>
                    <div class="dropdown-content-2" style="left: -160px; top: 0;">
                        <a href="add_event.php">Add Event</a>
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
            <div class="enroll-header-row">
                <div class="enroll-header-container">
                    <span class="enroll-header-icon" style="display:inline-flex;align-items:center;justify-content:center;margin-right:18px;">
                        <i class="fas fa-user-plus" style="color:#24513a;font-size:2.4em;"></i>
                    </span>
                    <span class="enroll-header-title">Enroll Student</span>
                </div>
                <div class="enroll-tab-selector" style="background:linear-gradient(90deg,#eafaf3 60%,#f8fff9 100%);border-radius:12px;box-shadow:0 2px 8px rgba(36,81,58,0.07);padding:6px 8px;gap:0.5px;display:flex;align-items:center;justify-content:flex-start;max-width:420px;">
                    <span class="enroll-tab enroll-tab-active" id="tabExcel" style="transition:all 0.18s;border-radius:10px 0 0 10px;box-shadow:0 2px 8px rgba(36,81,58,0.04);border-right:none;">
                        <i class="fas fa-file-excel" style="margin-right:8px;color:#b7b728;"></i>via Excel
                    </span>
                    <span class="enroll-tab-divider" style="width:2px;height:32px;background:linear-gradient(to bottom,#b7b728 60%,#e3ede7 100%);margin:0 0px;border-radius:2px;"></span>
                    <span class="enroll-tab" id="tabIndividual" style="transition:all 0.18s;border-radius:0 10px 10px 0;box-shadow:0 2px 8px rgba(36,81,58,0.04);border-left:none;">
                        <i class="fas fa-user-edit" style="margin-right:8px;color:#24513a;"></i>Individual
                    </span>
                </div>
            </div>
            <div class="enroll-flex-container enroll-flex-with-divider">
                <div class="enroll-left enroll-tab-content" id="excelTabContent" style="width:50%;">
                    <form method="post" enctype="multipart/form-data" id="excelImportForm">
                        <div class="import-desc">
                            <span style="color:#a74528;"><b>Important:</b> Only <b>School Form 1</b> files are accepted for import.</span>
                        </div>
                        <div class="dropzone-upload">
                            <label for="excel_file" class="dropzone-label" tabindex="0" id="dropzoneLabel">
                                <svg aria-hidden="true" fill="none" viewBox="0 0 20 16">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                </svg>
                                <span class="dz-main">Click to upload Excel file</span>
                                <span class="dz-sub">or drag and drop (.xls, .xlsx)</span>
                                <span class="dz-filename" id="dzFilename"></span>
                                <input id="excel_file" name="excel_file" type="file" accept=".xls,.xlsx" required multiple>
                            </label>
                        </div>
                        <button type="submit" style="min-width:180px;"><i class="fas fa-file-import"></i> Import Students</button>
                    </form>
                </div>
                <div class="enroll-divider-vertical"></div>
                <div class="enroll-right enroll-tab-content" id="individualTabContent" style="width:50%;">
                    <form method="post" action="">
                        <div>
                            <div class="row-1">
                                <input type="text" name="lrn" placeholder="LRN" required>
                            </div>
                            <div class="row-2">
                                <input type="text" name="last_name" placeholder="Last Name" required>
                                <input type="text" name="first_name" placeholder="First Name" required>
                            </div>
                            <div class="row-3">
                                <input type="text" name="middle_name" placeholder="Middle Name">
                                <select name="sex" required style="padding: 10px 12px; border: 1px solid #b7b728; border-radius: 6px; font-size: 1em; background: #f8fff9;">
                                    <option value="" disabled selected>Gender</option>
                                    <option value="M">Male</option>
                                    <option value="F">Female</option>
                                </select>
                                <input type="number" name="age" placeholder="Age" min="1" max="100" required>
                                <label for="birth_date" style="display:flex;align-items:center;gap:6px;font-weight:600;color:#24513a;white-space:nowrap;">
                                    Birthdate
                                </label>
                                <input type="date" id="birth_date" name="birth_date" required style="margin-left:6px;">
                            </div>
                            <div class="row-4">
                                <input type="text" name="mother_tongue" placeholder="Mother Tongue">
                                <input type="text" name="religion" placeholder="Religion">
                            </div>
                            <div class="row-6">
                                <input type="text" name="house" placeholder="House Number">
                                <input type="text" name="barangay" placeholder="Barangay">
                                <input type="text" name="city" placeholder="City">
                                <input type="text" name="province" placeholder="Province">
                            </div>
                            <div class="row-7">
                                <input type="text" name="father" placeholder="Father Name (Surname, First Name, Middle Name)">
                                <input type="text" name="mother" placeholder="Mother Name (Surname, First Name, Middle Name)">
                            </div>
                            <div class="row-8">
                                <input type="text" name="guardian_name" placeholder="Guardian Name (Surname, First Name, Middle Name)">
                                <input type="text" name="guardian_relation" placeholder="Relation">
                            </div>
                            <div class="row-9">
                                <input type="text" name="contact" placeholder="Guardian Contact Number">
                            </div>
                            <div class="row-10">
                                <input type="text" name="remarks" placeholder="Remarks">
                            </div>
                            <!-- Add Grade Level and Section fields -->
                            <div class="row-11" style="display:flex;gap:10px;margin-bottom:10px;">
                                <input type="text" name="grade_level" placeholder="Grade Level" required>
                                <input type="text" name="section" placeholder="Section" required>
                            </div>
                            <button type="submit" name="enroll_individual"><i class="fas fa-user-plus"></i> Enroll Student</button>
                        </div>
                    </form>
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
                setTimeout(function() {
                    notif.style.display = 'none';
                }, 4000);
            }, 4500);
        }

        <?php if (isset($success_message)): ?>
            showNotification('success', <?php echo json_encode($success_message); ?>);
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            showNotification('error', <?php echo json_encode($error_message); ?>);
        <?php endif; ?>

        // Drag & Drop for Excel file upload and show filename
        (function() {
            var dropzone = document.getElementById('dropzoneLabel');
            var fileInput = document.getElementById('excel_file');
            var form = document.getElementById('excelImportForm');
            var dzFilename = document.getElementById('dzFilename');

            function updateFilenameDisplay(files) {
                if (files && files.length > 0) {
                    var names = [];
                    for (var i = 0; i < files.length; i++) {
                        names.push(files[i].name);
                    }
                    dzFilename.textContent = names.join(', ');
                    dropzone.classList.add('has-file');
                } else {
                    dzFilename.textContent = '';
                    dropzone.classList.remove('has-file');
                }
            }

            if (dropzone && fileInput && form) {
                // Prevent default drag behaviors
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(eventName) {
                    dropzone.addEventListener(eventName, function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }, false);
                });

                // Highlight dropzone on dragover
                dropzone.addEventListener('dragover', function() {
                    dropzone.classList.add('dragover');
                });
                dropzone.addEventListener('dragleave', function() {
                    dropzone.classList.remove('dragover');
                });
                dropzone.addEventListener('drop', function(e) {
                    dropzone.classList.remove('dragover');
                    var files = e.dataTransfer.files;
                    if (files && files.length > 0) {
                        fileInput.files = files;
                        updateFilenameDisplay(files);
                    }
                });

                // Update filename on file input change
                fileInput.addEventListener('change', function() {
                    updateFilenameDisplay(fileInput.files);
                });
            }
        })();

        // Tab switching logic
        (function() {
            var tabExcel = document.getElementById('tabExcel');
            var tabIndividual = document.getElementById('tabIndividual');
            var excelTabContent = document.getElementById('excelTabContent');
            var individualTabContent = document.getElementById('individualTabContent');

            function activateTab(tab) {
                if (tab === 'excel') {
                    tabExcel.classList.add('enroll-tab-active');
                    tabIndividual.classList.remove('enroll-tab-active');
                    excelTabContent.classList.remove('hide');
                    individualTabContent.classList.add('hide');
                } else {
                    tabExcel.classList.remove('enroll-tab-active');
                    tabIndividual.classList.add('enroll-tab-active');
                    excelTabContent.classList.add('hide');
                    individualTabContent.classList.remove('hide');
                }
            }
            if (tabExcel && tabIndividual && excelTabContent && individualTabContent) {
                tabExcel.addEventListener('click', function() { activateTab('excel'); });
                tabIndividual.addEventListener('click', function() { activateTab('individual'); });
                // Default: show Excel tab
                activateTab('excel');
            }
        })();

    </script>
</body>
</html>

