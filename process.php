<?php
    session_start();

    // Check if the form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $message = trim($_POST['message']);

        // Simple validation (make sure all fields are filled)
        if (!empty($name) && !empty($email) && !empty($message)) {
            // Store a success message
            $_SESSION['message'] = "Thank you for your message, {$name}! We will get back to you soon.";

            // Redirect back to the contact form
            header("Location: index.php");
            exit();
        } else {
            // If validation fails, set an error message
            $_SESSION['message'] = "Please fill in all the fields.";
            header("Location: index.php");
            exit();
        }
    } else {
        // If the form wasn't submitted, redirect to the contact form
        header("Location: index.php");
        exit();
    }
?>
