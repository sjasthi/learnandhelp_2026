<?php
require 'db_configuration.php';
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// delete_suggestion.php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assuming $conn is the database connection
    $suggested_school_id = $_POST['suggested_school_id'];

    // Delete the suggested school entry
    $sql = "DELETE FROM schools_suggested WHERE id = $suggested_school_id";

    if ($conn->query($sql) === TRUE) {
        echo "School suggestion deleted successfully!";
    } else {
        echo "Error deleting record: " . $conn->error;
    }

    header('Location: admin_review_suggestions.php');

    // Close the database connection
    $conn->close();
}
?>
