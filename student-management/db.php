<?php
$servername = "localhost";
$username = "root"; // your database username
$password = ""; // your database password
$dbname = "student_management";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
