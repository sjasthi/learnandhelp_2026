<?php
// Include database configuration
require 'db_configuration.php';

// Connect to the database
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the school ID from the form
    $school_id = $_POST['school_id'];

    // Update the school status from 'Proposed' to 'Completed'
    $sql = "UPDATE schools SET status = 'Completed' WHERE id = ? AND status = 'Proposed'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $school_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            header("Location: admin_review_suggestions.php");
            exit();
        } else {
            echo "School not found or already completed.";
        }
    } else {
        echo "Error updating school status: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
