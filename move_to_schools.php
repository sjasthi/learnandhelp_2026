<?php
// Include database configuration
require 'db_configuration.php';

// Connect to the database
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the suggested school ID from the form
    $suggested_school_id = $_POST['suggested_school_id'];

    // Fetch the suggested school details from the schools_suggested table
    $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
    $sql_select = "SELECT * FROM schools_suggested WHERE id = '$suggested_school_id'";
    $result_select = $conn->query($sql_select);

    if ($result_select->num_rows > 0) {
        $row = $result_select->fetch_assoc();

        // Insert the suggested school into the schools table
        $sql_insert = "INSERT INTO schools (name, type, category, address_text, contact_name, contact_phone, supported_by)
                       VALUES ('" . $row['school_name'] . "', NULL, NULL, '" . $row['address'] . "',
                               '" . $row['contact_name'] . "', '" . $row['contact_mobile'] . "', NULL)";
        
        if ($conn->query($sql_insert) === TRUE) {
            // Delete the suggested school from schools_suggested after moving it
            $sql_delete = "DELETE FROM schools_suggested WHERE id = '$suggested_school_id'";
            $conn->query($sql_delete);
            header("Location: admin_review_suggestions.php"); // Redirect back to the review_suggestions page
            exit();
        } else {
            echo "Error moving the suggested school: " . $conn->error;
        }
    } else {
        echo "Suggested school not found.";
    }

    $conn->close();
}
?>
