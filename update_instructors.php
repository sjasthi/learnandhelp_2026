<?php
require 'db_configuration.php';

$connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$action = $_POST['action'];
$First_name = $_POST['First_name'];
$Last_name = $_POST['Last_name'];
$Bio_data = $_POST['Bio_data'];

// File upload handling
$image = $_FILES['image']['name'];
$image_temp = $_FILES['image']['tmp_name'];
$image_folder = 'uploads/'; // Folder to save the uploaded images

// Move uploaded file to the specified folder
if (!empty($image_temp)) {
    $image_path = $image_folder . $image;
    move_uploaded_file($image_temp, $image_path);
} else {
    // Handle if no image uploaded
    $image_path = ''; // Set default image path or handle as necessary
}

if ($action == 'add') {
    $sql = "INSERT INTO instructor (First_name, Last_name, Bio_data, Image) VALUES ('$First_name', '$Last_name', '$Bio_data', '$image_path')";
} else if ($action == 'update') {
    $id = $_POST['rowId']; // You need to include rowId in your form for updates
    $sql = "UPDATE instructor SET
            First_name = '$First_name',
            Last_name = '$Last_name',
            Bio_data = '$Bio_data'";
    if (!empty($image_path)) {
        $sql .= ", Image = '$image_path'";
    }
    $sql .= " WHERE instructor_ID = '$id'";
} else if ($action == 'delete') {
    $id = $_POST['rowId'];
    $sql = "DELETE FROM instructor WHERE instructor_ID = '$id'";
}

if (!$connection->query($sql)) {
    echo("Error description: " . $connection->error);
}

$connection->close();
header("Location: instructors.php");
exit();
?>
