<?php
require 'db_configuration.php';

$status = session_status();
if ($status == PHP_SESSION_NONE) {
  session_start();
}

if (!(isset($_SESSION['email']))) {
	header('Location: login.php');
}

include 'show-navbar.php';
$connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

if ($connection === false) {
	die("Failed to connect to database: " . mysqli_connect_error());
}

if (isset($_POST['action'])) {
	$action = $_POST['action'];
} else {
	$action = '';
}

if($action == 'admin_edit_class'){
	$Class_Id = $_POST['Class_Id'];
	$Class_Name = $_POST["Class_Name"];
	$Description = $_POST["Description"];
	$Status = $_POST["Status"];  //Update status value from form (altered)
} else {
	$Class_Id = $_SESSION['Class_Id'];
    $sql = "SELECT * FROM classes WHERE Class_Id = '$Class_Id'";
    $row = mysqli_fetch_array(mysqli_query($connection, $sql));

		$action = '';
		$Class_Id = $row['Class_Id'];
		$class_Name = $row['Class_Name'];
		$description = $row['Description'];
		$Status = $_POST["Status"];  //Get status value from database		
}

if($action == "admin_edit_class") {
	$Class_Id = $_POST['Class_Id'];
	$sql = "UPDATE classes SET
			Class_Name = '$Class_Name',
			Description = '$Description',
			Status = '$Status'  
			WHERE Class_Id = '$Class_Id';";
}

if (!mysqli_query($connection, $sql)) {
	echo("Error description: " . mysqli_error($connection));
} else {
	echo "<script type=\"text/javascript\">setTimeout(function(){document.getElementById('add_submitted_form').submit();},500);
		  </script>";}

mysqli_close($connection);

?>

<div style="text-align:center;margin-top:200px;"><h3>One moment please. Processing changes...</h3>
       <img src="images/loadingIcon.gif"></img>
</div>
<form method="POST" id="add_submitted_form" action="admin_edit_class.php">
	<?php echo "<input type=\"hidden\" name=\"Class_Id\" value=\"$Class_Id\">"; ?>
</form>

