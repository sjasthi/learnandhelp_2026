<?php
// receives jQuery AJAX call from admin_schools.php to update database after admin "in-cell" editing of the schools table
$status = session_status();
if ($status == PHP_SESSION_NONE) {
  session_start();
}

// Block unauthorized users from accessing the page
if (isset($_SESSION['role'])) {
  if ($_SESSION['role'] != 'admin') {
    http_response_code(403);
    die('Forbidden');
  }
} else {
  http_response_code(403);
  die('Forbidden');
}

include_once 'db_configuration.php';

$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$value = $_POST['value'];
$column = $_POST['column'];
$id = $_POST['id'];

$sql = "UPDATE schools SET $column = '$value' WHERE id = '$id'";
$conn->query($sql);
$conn->close();
?>