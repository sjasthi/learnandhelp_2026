<?php
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

require 'db_configuration.php';
// Create connection
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$id = $_POST['id'];

// remove school from database
$sql = "DELETE FROM schools WHERE id = " . $id;
$result = $conn->query($sql);

// delete all media and the media directory for the school
$structure = glob(rtrim("schools/${id}/", "/").'/*');
if (is_array($structure)) {
	foreach($structure as $file) {
 	if (is_dir($file))
 		deleteAll($file,true);
 	else if(is_file($file))
 		unlink($file);
 	}
}
rmdir("schools/${id}");

$conn->close();
header("Location: admin_schools.php");
?>
