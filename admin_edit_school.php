<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

  $id = $_POST['id'];
?>

<!DOCTYPE html>
<html>
  <head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Learn and Help</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
  </head>
  <body>
    <?php include 'show-navbar.php';
          include 'admin_fill.php';
          ?>
    <?php show_navbar(); ?>
    <header class="inverse">
      <div class="container">
        <h1> <span class="accent-text">Schools Form</span></h1>
      </div>
	</header>
   <form method="POST" action="admin_schools.php">
      <input type="submit" value="Return to Schools">
	</form>
<?php
	// check that the media directory exists, if not, nothing to show here
	if(file_exists("schools/$id/") and $id != null) {
		$fileCount = count(glob("schools/$id/*"));
		if ($fileCount > 0) {
			echo "<div style=\"padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto\">
   		<h3>Edit Media</h3>
   		<table id=\"edit_school_media\">";
			$media_files = array_diff(scandir("schools/$id/"), array('..', '.'));
			$counter = 0;  
        	while($counter < count($media_files)) {
				if($counter == 0) {
					echo "<tr>";
				}
				$filename = $media_files[$counter + 2];
				// add a "version" string to the filename so the system thinks it's a new file each load
				// this keeps the old profile image from being displayed when the page loads even though
				// it was changed to a different file
				$time = time();
				echo  "<td class=\"edit_school_media\">";
						if(str_contains($filename, 'profile_image')) {
							echo "<img src=\"schools/$id/$filename?v=$time\" alt=\"school image\">";
						} else {
							echo "<img src=\"schools/$id/$filename\" alt=\"school image\">";
						}	
						echo "<br>
							<label>$filename</label>";
							
					if(!str_contains($filename, 'profile_image')) { ?>
						<form method="post" is="profile_update" action="admin_school_media.php">
							<?php echo "<input type=\"hidden\" name=\"update\" value=1>"; ?>
							<?php echo "<input type=\"hidden\" name=\"id\" value='$id'>"; ?>
							<?php echo "<input type=\"hidden\" name=\"filename\" value='$filename'>"; ?>
        					<input type="submit" id="admin_buttons" name="default_btn" value="Make Profile Image"/>
						</form>
						<form method="post" id="media_delete" action="admin_school_media.php">
							<?php echo "<input type=\"hidden\" name=\"delete\" value=1>"; ?>
							<?php echo "<input type=\"hidden\" name=\"id\" value='$id'>"; ?>
							<?php echo "<input type=\"hidden\" name=\"filename\" value='$filename'>"; ?>
        					<input type="submit" id="admin_buttons" name="delete_btn" value="Delete File"/>
						</form>
				<?php
					}
					echo "</td>";

				if($counter % 5 == 0 && $counter > 0) {
					echo "</tr>";
					if($counter < count($media_files)) {
						echo "<tr>";
					}
				}
				$counter++;
			}
			echo "</table>
			</div>";
		}
	}
?>
	<?php if ($id != null) { 
		echo "<h3>Edit School Details</h3>"; //If you're trying to fix anything on the school detail form it's on file admin_fill.php. Line 24 is where it starts
	} else {
		echo "<h3>Add School Details</h3>";
	}
    ?>
     <div>
	<?php
	admin_school_form($id); ?>
		<div>
       		Select media files to upload:<br>
			<?php echo "<input type=\"hidden\" name=\"id\" value=\"$id\">"; ?>
       		<input id="media_upload" type="file" name="files[]" multiple>
		</div>
		<?php
		if($id != null) { 
			echo "<input type=\"hidden\" id=\"action\" name=\"action\" value=\"admin_edit_school\">
		  	<br>
		  	<input type=\"submit\" id=\"submit-school\" name=\"submit\" value=\"Submit\">";
		} else {
			echo "<input type=\"hidden\" id=\"action\" name=\"action\" value=\"admin_add_school\">
	        <br>
	        <input type=\"submit\" id=\"submit-school\" name=\"submit\" value=\"Submit\">";
		}
        ?>
	  </form><!---survey-form--->
	</div>
  </body>
</html>
