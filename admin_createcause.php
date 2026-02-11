<?php
session_start();

// Include the database configuration file
require 'db_configuration.php';

$connection = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

// Check if the connection is established
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $cause_name = $_POST['Cause_name'];
    $description = $_POST['description'];
    $url = $_POST['URL'];
    $contact_name = $_POST['Contact_name'];
    $contact_email = $_POST['Contact_email'];
    $contact_phone = $_POST['Contact_phone'];

    // SQL query to insert cause data into the "causes" table
    $insertQuery = "INSERT INTO causes 
        (Cause_name, description, URL, Contact_name, Contact_email, Contact_phone)
        VALUES 
        ('$cause_name', '$description', '$url', '$contact_name', '$contact_email', '$contact_phone')";

    $insertResult = mysqli_query($connection, $insertQuery);

    if ($insertResult) {
        header("Location: causes.php");
        exit;
    } else {
        echo "Error adding cause: " . mysqli_error($connection);
    }
}

mysqli_close($connection);
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <title>Add New Cause</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            text-align: left !important;
        }

        header {
            background-color: #333;
            color: #fff;
            padding: 20px;
        }
        main {
            padding: 20px;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        h1 {
            color: #333;
        }

        form {
            width: 400px;
            margin-top: 20px;
        }

        label {
            display: block;
            margin-top: 10px;
            
        }

        input[type="text"], input[type="password"],input[type="tel"] {
            width: 100%;
            padding: 10px;
            font-size: 16px;
        }

        select {
            width: 100%;
            padding: 5px;
            font-size: 16px;
        }

        .createBtn {
            padding: 10px 20px;
            margin-top: 10px;
            display: block;
            font-size: 16px;
            background-color: #99D930;
            color: #000;
            border: none;
            cursor: pointer;
            width: 100%;
        }
    </style>
</head>
<body>
    <?php include 'show-navbar.php'; ?>

    <main>
        <h1>Add New Cause</h1>
        <form method="POST" action="">
            <label for="Cause_Name">Cause Name</label>
            <input type="text" name="Cause_name" id="Cause_name" required placeholder="Cause Name">

            <label for="Description">Description</label>
            <textarea name="description" id="description" required placeholder="Description"></textarea>

            <label for="URL">URL</label>
            <input type="text" name="URL" id="URL" required placeholder="www.example.com">

            <label for="Contact_Name">Contact Name</label>
            <input type="text" name="Contact_name" id="Contact_name" required placeholder="Contact Name">

            <label for="Contact_Email">Contact Email</label>
            <input type="email" name="Contact_email" id="Contact_email" required placeholder="Contact Email">

            <label for="Contact_Phone">Contact Phone</label>
            <input type="tel" name="Contact_phone" id="Contact_phone" required placeholder="Contact Phone">

            <input type="submit" value="Add Cause">
        </form>
    </main>
</body>
</html>