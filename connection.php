<?php
function getConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "SANTANDER13";
    $dbname = "labac_system";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}
?>
