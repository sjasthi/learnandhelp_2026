<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    http_response_code(403);
    die('Forbidden');
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $notes = $_POST['admin_notes'] ?? '';

    // Specify the file to save the notes
    $filename = 'admin_notes.txt';

    // Save the notes to the file
    if (file_put_contents($filename, $notes) !== false){
        // If save was successful, redirect back to administration.php with a success message
        header("Location: administration.php?status=success");
        exit;
    } else {
        // If save failed, redirect back to administration.php with an error message
        header("Location: administration.php?status=error");
        exit;
    }
} else {
    // If the script was accessed directly without a POST request, redirect to administration.php
    header("Location: administration.php");
    exit;
}
?>