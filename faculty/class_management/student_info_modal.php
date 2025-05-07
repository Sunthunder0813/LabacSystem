<?php
require_once '../../connection.php';

if (!isset($_GET['lrn'])) {
    echo '<div style="color:#dc3545;">No student LRN provided.</div>';
    exit;
}

$lrn = $_GET['lrn'];
$conn = getConnection();

$stmt = $conn->prepare("SELECT lrn, last_name, first_name, middle_name, sex, birth_date, age, mother_tongue, religion, house, barangay, city, province, father, mother, guardian_name, guardian_relation, contact, remarks, registered_by, date_time_registered, status FROM students WHERE lrn = ?");
$stmt->bind_param("s", $lrn);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo '<div style="color:#dc3545;">Student not found.</div>';
    $stmt->close();
    exit;
}

$stmt->bind_result($lrn, $last_name, $first_name, $middle_name, $sex, $birth_date, $age, $mother_tongue, $religion, $house, $barangay, $city, $province, $father, $mother, $guardian_name, $guardian_relation, $contact, $remarks, $registered_by, $date_time_registered, $status);
$stmt->fetch();

$address = [];
if (!empty($house)) $address[] = $house;
if (!empty($barangay)) $address[] = $barangay;
if (!empty($city)) $address[] = $city;
if (!empty($province)) $address[] = $province;
$address_str = implode(', ', $address);

?>
<table>
    <tr><th>LRN</th><td><?php echo htmlspecialchars($lrn); ?></td></tr>
    <tr><th>Name</th><td><?php echo htmlspecialchars("$last_name, $first_name $middle_name"); ?></td></tr>
    <tr><th>Sex</th><td><?php echo htmlspecialchars($sex); ?></td></tr>
    <tr><th>Birth Date</th><td><?php echo htmlspecialchars($birth_date); ?></td></tr>
    <tr><th>Age</th><td><?php echo htmlspecialchars($age); ?></td></tr>
    <tr><th>Mother Tongue</th><td><?php echo htmlspecialchars($mother_tongue); ?></td></tr>
    <tr><th>Religion</th><td><?php echo htmlspecialchars($religion); ?></td></tr>
    <tr><th>Address</th><td><?php echo htmlspecialchars($address_str); ?></td></tr>
    <tr><th>Father</th><td><?php echo htmlspecialchars($father); ?></td></tr>
    <tr><th>Mother</th><td><?php echo htmlspecialchars($mother); ?></td></tr>
    <tr><th>Guardian Name</th><td><?php echo htmlspecialchars($guardian_name); ?></td></tr>
    <tr><th>Guardian Relation</th><td><?php echo htmlspecialchars($guardian_relation); ?></td></tr>
    <tr><th>Contact</th><td><?php echo htmlspecialchars($contact); ?></td></tr>
    <tr><th>Remarks</th><td><?php echo htmlspecialchars($remarks); ?></td></tr>
    <tr><th>Status</th><td><?php echo htmlspecialchars($status); ?></td></tr>
    <tr><th>Date Registered</th><td><?php echo htmlspecialchars($date_time_registered); ?></td></tr>
</table>
<?php
$stmt->close();
?>
