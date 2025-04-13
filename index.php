<?php
    // Start the session to store any potential error or success messages
    session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="container">
        <h1>Contact Us</h1>
        
        <?php
        // Display success or error messages from the session
        if (isset($_SESSION['message'])) {
            echo "<div class='message'>{$_SESSION['message']}</div>";
            unset($_SESSION['message']); // Clear message after display
        }
        ?>
        
        <form action="process.php" method="POST">
            <div class="input-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="input-group">
                <label for="message">Message:</label>
                <textarea id="message" name="message" required></textarea>
            </div>
            
            <button type="submit" class="submit-btn">Submit</button>
        </form>
    </div>

</body>
</html>
