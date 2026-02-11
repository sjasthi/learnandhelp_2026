<?php
// Check if the form is submitted via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require 'db_configuration.php'; // Include your database configuration file

    // Retrieve form data
    $causeId = $_POST['cause_id'];
    $causeName = $_POST['cause_name'];
    $description = $_POST['description'];
    $url = $_POST['url'];
    $contactName = $_POST['contact_name'];
    $contactEmail = $_POST['contact_email'];
    $contactPhone = $_POST['contact_phone'];

    // Update the database with the modified cause details
    $connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }

    $stmt = $connection->prepare("UPDATE causes SET Cause_name=?, description=?, URL=?, Contact_name=?, Contact_email=?, Contact_phone=? WHERE Cause_Id=?");
    $stmt->bind_param("ssssssi", $causeName, $description, $url, $contactName, $contactEmail, $contactPhone, $causeId);

    if ($stmt->execute()) {
        echo "Cause details updated successfully";
    } else {
        echo "Error updating cause details: " . $connection->error;
    }

    $stmt->close();
    $connection->close();

    // Redirect the user to the admin page
    header('Location: admin_causes.php');
} else {
    echo "Invalid request method";
}
?>
