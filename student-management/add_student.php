<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// Handle form submission for adding new students
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name']) && isset($_POST['marks'])) {
    $name = $_POST['name'];
    $marks = $_POST['marks'];

    $stmt = $conn->prepare("INSERT INTO Students (name, marks) VALUES (?, ?)");
    $stmt->bind_param("si", $name, $marks);
    $stmt->execute();
    $stmt->close();
}

// Fetch all students
$students = [];
$result = $conn->query("SELECT * FROM Students ORDER BY id DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Student</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .action-btn {
      padding: 4px 8px;
      margin: 2px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .update-btn {
      background-color: #4CAF50;
      color: white;
    }
    .delete-btn {
      background-color: #f44336;
      color: white;
    }
    table input {
      width: 100px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <h1>Welcome, <?php echo $_SESSION['username']; ?></h1>
      <form action="logout.php" method="POST">
        <button type="submit" style="padding: 8px 12px; background-color: #ff4d4d; color: white; border: none; border-radius: 6px; cursor: pointer;">
          Logout
        </button>
      </form>
    </div>

    <h2>Add New Student</h2>
    <form method="POST" action="add_student.php">
      <input type="text" name="name" placeholder="Student Name" required>
      <input type="number" name="marks" placeholder="Marks" required>
      <button type="submit">Add Student</button>
    </form>

    <h2>List of Added Students</h2>
    <table border="1" cellpadding="8">
      <tr>
        <th>Name</th>
        <th>Marks</th>
        <th>Actions</th>
      </tr>
      <?php if (count($students) > 0): ?>
        <?php foreach ($students as $s): ?>
          <tr>
            <form action="update_student.php" method="POST">
              <td>
                <input type="text" name="name" value="<?php echo htmlspecialchars($s['name']); ?>" required>
              </td>
              <td>
                <input type="number" name="marks" value="<?php echo htmlspecialchars($s['marks']); ?>" required>
              </td>
              <td>
                <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                <button type="submit" class="action-btn update-btn">Update</button>
              </form>

              <form action="delete_student.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this student?');">
                <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                <button type="submit" class="action-btn delete-btn">Delete</button>
              </form>
              </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="3">No students added yet.</td></tr>
      <?php endif; ?>
    </table>
  </div>
</body>
</html>
