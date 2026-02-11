<?php
session_start();

// Include the database configuration file
require 'db_configuration.php';

$connection = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

// Check if the connection is established
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Retrieve the User_ID from the query parameter
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // SQL query to delete user from the "users" table
    $deleteQuery = "DELETE FROM users WHERE User_ID = '$user_id'";

    // Perform the query
    $deleteResult = mysqli_query($connection, $deleteQuery);

    // Check if the query was successful
    if ($deleteResult) {
        header("Location: admin_usersList.php");
        exit;
    } else {
        echo "Error deleting user: " . mysqli_error($connection);
    }
} 

// Close the database connection
mysqli_close($connection);
?>
