<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

if($action == 'admin_edit_school' or $action == 'admin_add_school'){
	$name = $_POST["name"];
	$type = $_POST["type"];
	$category = $_POST["category"];
	$grade_level_start = $_POST["grade_level_start"];
	$grade_level_end = $_POST["grade_level_end"];
	$current_enrollment = $_POST["current_enrollment"];
	$address_text = $_POST["address_text"];
	$state_name = $_POST["state_name"];
	$state_code = $_POST["state_code"];
	$pin_code = $_POST["pin_code"];
	$contact_name = $_POST["contact_name"];
	$contact_designation = $_POST["contact_designation"];
	$contact_phone = $_POST["contact_phone"];
	$contact_email = $_POST["contact_email"];
	$status = $_POST["status"];
	$notes = $_POST["notes"];
	$referenced_by = $_POST["referenced_by"];
	$supported_by = $_POST["supported_by"];
} else {
	$id = $_SESSION['id'];
    $sql = "SELECT * FROM schools WHERE id = '$id'";
    $row = mysqli_fetch_array(mysqli_query($connection, $sql));

	$action = '';
	$id = $row['id'];
	$name = $row["name"];
	$type = $row["type"];
	$category = $row["category"];
	$grade_level_start = $row["grade_level_start"];
	$grade_level_end = $row["grade_level_end"];
	$current_enrollment = $row["current_enrollment"];
	$address_text = $row["address_text"];
	$state_name = $row["state_name"];
	$state_code = $row["state_code"];
	$pin_code = $row["pin_code"];
	$contact_name = $row["contact_name"];
	$contact_designation = $row["contact_designation"];
	$contact_phone = $row["contact_phone"];
	$contact_email = $row["contact_email"];
	$status = $row["status"];
	$notes = $row["notes"];
	$referenced_by = $row["referenced_by"];
	$supported_by = $row["supported_by"];
}

if($action == "admin_edit_school") {
	$id = $_POST['id'];
	$sql = "UPDATE schools SET
			name = '$name',
			type = '$type',
			category = '$category',
			grade_level_start = '$grade_level_start',
			grade_level_end = '$grade_level_end',
			current_enrollment = '$current_enrollment',
			address_text = '$address_text',
			state_name = '$state_name',
			state_code = '$state_code',
			pin_code = '$pin_code',
			contact_name = '$contact_name',
			contact_designation = '$contact_designation',
			contact_phone = '$contact_phone',
			contact_email = '$contact_email',
			status = '$status',
			notes = '$notes',
			referenced_by = '$referenced_by',
			supported_by = '$supported_by'
			WHERE id = '$id';";
} elseif($action == 'admin_add_school') {
	$sql = "INSERT INTO schools VALUES (
		NULL,
		'$name',
		'$type',
		'$category',
		'$grade_level_start', 
		'$grade_level_end',
		'$current_enrollment',
	    '$address_text',
	    '$state_name',
		'$state_code',
		'$pin_code',
		'$contact_name',
		'$contact_designation',
		'$contact_phone',
		'$contact_email',
		'$status',
		'$notes',
		'$referenced_by',
		'$supported_by');";
}

if (!mysqli_query($connection, $sql)) {
	echo("Error description: " . mysqli_error($connection));
} else {
	if($action == 'admin_add_school') {
		$id = mysqli_insert_id($connection);
	}

	// id is not null and there is something in $_FILES so upload them
	if($id != null && !empty(array_filter($_FILES['files']['name']))) {
    	// Configure upload directory and allowed file types
    	$upload_dir = "schools/$id/";
    	$allowed_types = array('jpg', 'png', 'jpeg', 'gif');
     
    	// Define maxsize for files i.e 2MB
    	$maxsize = 3 * 1024 * 1024;

		//echo "$upload_dir";
		if(!file_exists($upload_dir)) {
			mkdir($upload_dir);
		}
  
       	// Loop through each file in files[] array
       	foreach ($_FILES['files']['tmp_name'] as $key => $value) {
             
           	$file_tmpname = $_FILES['files']['tmp_name'][$key];
           	$file_name = $_FILES['files']['name'][$key];
           	$file_size = $_FILES['files']['size'][$key];
           	$file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
 
           	// Set upload file path
           	$filepath = $upload_dir.$file_name;
 
           	// Check file type is allowed or not
           	if(in_array(strtolower($file_ext), $allowed_types)) {
               	// Verify file size - 3MB max
               	if ($file_size <= $maxsize) {        
					// Check if filename already exists
               		if(!file_exists($filepath)) {
						// Upload the file
						if( move_uploaded_file($file_tmpname, $filepath)) {
                   			// check if a profile image already exists
							$fileCount = count(glob($upload_dir."profile_image.*"));
							// if there is no profile image copy this file to a new profile image
							if ($fileCount == 0) {
								// get the new files name and extension
								$path_parts = pathinfo($filepath);
								// create the path and name for the new profile image since one doesn't exist
								$new_file = $upload_dir . 'profile_image.' . $path_parts['extension'];
								// copy the new file to profile_image.(extension)
								copy($filepath, $new_file);
							}
                   		} else {                    
                       		echo "Error uploading $file_name<br>";
                   		}
					} else {
						echo "$file_name already exists<br>";
					}
				} else {
                 	echo "Error: File size is larger than the allowed limit.<br>";
				}
           	} else {
           		// If file extension not valid
               	echo "Error uploading $file_name ";
               	echo "($file_ext file type is not allowed)<br>";
           	}
		}
	}

	// trigger hidden form to load admin_edit_school.php and POST $id
	echo "<script type=\"text/javascript\">setTimeout(function(){document.getElementById('add_submitted_form').submit();},500);
		  </script>";
}

mysqli_close($connection);

?>

<div style="text-align:center;margin-top:200px;"><h3>One moment please. Processing changes...</h3>
       <img src="images/loadingIcon.gif"></img>
</div>
<form method="POST" id="add_submitted_form" action="admin_edit_school.php">
	<?php echo "<input type=\"hidden\" name=\"id\" value=\"$id\">"; ?>
</form>

