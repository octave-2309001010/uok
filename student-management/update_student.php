<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'], $_POST['name'], $_POST['marks'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $marks = $_POST['marks'];

    $stmt = $conn->prepare("UPDATE Students SET name = ?, marks = ? WHERE id = ?");
    $stmt->bind_param("sii", $name, $marks, $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: add_student.php");
exit();
?>
