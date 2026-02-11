<?php
    require 'db_configuration.php';
    $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $school_name = $_POST['school_name'];
    $contact_name = $_POST['contact_name'];
    $contact_mobile = $_POST['contact_mobile'];
    $commitment_statement = $_POST['commitment_statement'];

    // Insert the data into the schools_suggested table
    $sql = "INSERT INTO schools_suggested (school_name, contact_name, contact_mobile, commitment_statement)
            VALUES ('$school_name', '$contact_name', '$contact_mobile', '$commitment_statement')";

    if ($conn->query($sql) === TRUE) {
        // alert the user that the school suggestion was submitted successfully
        echo "<script type='text/javascript'>alert('School suggestion submitted successfully!');</script>";
        // upon clinking the OK button, redirect the user to the home page
        echo "<script type='text/javascript'>window.location.href = 'index.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }



    // Close the database connection
    $conn->close();
}
?>
