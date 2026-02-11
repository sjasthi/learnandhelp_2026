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

  $id = $_POST['id'] ?? null;
  $filename = $_POST['filename'] ?? null;
  $update = $_POST['update'] ?? null;
  $delete = $_POST['delete'] ?? null;

if($update == 1) {
	// delete any and all existing profile_image files
	foreach(glob("schools/$id/profile_image.*") as $f) {
   		unlink($f);
	}
	
	// the file path for this school
	$filepath = "schools/$id/";
	// create the full path and name to the selected file
	$selectedfile = "schools/$id/$filename";
	// get the selected file's name and extension
	$path_parts = pathinfo($selectedfile);
	// this will be the new profile image
	$new_file = $filepath . 'profile_image.' . $path_parts['extension'];
	// copy the selected file as the new profile image
	if(copy($selectedfile, $new_file)) {
		// a 1500 millsecond delay to let the script and the file system catch up with each other when deleting
		// the existing profile image and copying the selected file to be the new profile image
		echo "<script type=\"text/javascript\">setTimeout(function(){document.getElementById('media_changed_form').submit();},5);
			  </script>";
	} else {
		echo "<span id='error_msg'>ERROR: Unable to set file as profile image.  Renaming failed.</span>";
	}	
}

if($delete == 1) {
	if (!unlink("schools/$id/$filename")) {
   		echo ("<span id='error_msg'>$filename cannot be deleted due to an error</span>");
	} else {
		// a 1000 millisecond delay to delete the image and make sure it is gone vefore the page is reloaded
		echo "<script type=\"text/javascript\">setTimeout(function(){document.getElementById('media_changed_form').submit();},5);
			  </script>";
	}
}
?>
	<form method="post" id="media_changed_form" action="admin_edit_school.php">
		<?php echo "<input type=\"hidden\" name=\"id\" value=\"$id\">"; ?>
	</form>

