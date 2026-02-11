<?php
session_start();

// Include the database configuration file
require 'db_configuration.php';

$connection = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

// Check if the connection is established
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Retrieve the Cause_ID from the query parameter
if (isset($_GET['id'])) {
    $cause_id = $_GET['id'];

    // SQL query to delete cause from the "causes" table
    $deleteQuery = "DELETE FROM causes WHERE Cause_Id = '$cause_id'";

    // Perform the query
    $deleteResult = mysqli_query($connection, $deleteQuery);

    // Check if the query was successful
    if ($deleteResult) {
        header("Location: causes.php");  // Assuming you have a page to list causes similar to admin_usersList.php
        exit;
    } else {
        echo "Error deleting cause: " . mysqli_error($connection);
    }
} 

// Close the database connection
mysqli_close($connection);
?>
