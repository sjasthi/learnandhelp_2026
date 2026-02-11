<?php
require 'db_configuration.php';

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

$Blog_Id = $_POST['Blog_Id'];
$Picture_Id = $_POST['Picture_Id'];

// Create connection
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
// Check connection
if ($conn->connect_error) 
{
  die("Connection failed: " . $conn->connect_error);
}
  // GET IMAGE to Remove
  $sql = "select Location FROM  blog_pictures WHERE Picture_Id=".$Picture_Id;
  $result = $conn->query($sql);
  $result = mysqli_fetch_array($result);

  $location = $result[0];
  $sql = "DELETE FROM  blog_pictures WHERE Picture_Id=".$Picture_Id;
  $result = $conn->query($sql);
  unlink($location);
  $conn->close();
	echo "<script type=\"text/javascript\">setTimeout(function(){document.getElementById('delete_submitted_form').submit();},500);
	</script>";

?>
<div style="text-align:center;margin-top:200px;"><h3>One moment please. Processing changes...</h3>
       <img src="images/loadingIcon.gif"></img>
</div>
<form method="POST" id="delete_submitted_form" action="admin_edit_blog.php">
	<?php echo "<input type=\"hidden\" name=\"Blog_Id\" value=\"$Blog_Id\">"; ?>
</form>

