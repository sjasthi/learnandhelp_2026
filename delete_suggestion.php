<?php
require 'db_configuration.php';
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// delete_suggestion.php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $school_id = $_POST['school_id'];

    // Delete the proposed school entry
    $sql = "DELETE FROM schools WHERE id = ? AND status = 'Proposed'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $school_id);

    if ($stmt->execute()) {
        // Redirect back to admin review page
    } else {
        echo "Error deleting record: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header('Location: admin_review_suggestions.php');
    exit();
}
?>
