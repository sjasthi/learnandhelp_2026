<?php
  $status = session_status();
  if ($status == PHP_SESSION_NONE) {
    session_start();
  }

	function get_profile_image($id) {
		$image_name = glob('schools/' . $id . '/profile_image.*');
		// should only be one file found, if there are two profile_image files
		// with different extensions something is wrong.  If there is no profile
		// image or more than one default to the admin_icons school icon.
		if(count($image_name) == 1) {
	 		return $image_name[0];
		} else {
			return "images/admin_icons/school.png";
		}
	}

  $School_Id = $_GET['id']
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
    ?>
    <?php show_navbar(); ?>
    <header class="inverse">
      <div class="container">
        <h1> <span class="accent-text">School Details</span></h1>
      </div>
    </header>
<?php
  require 'db_configuration.php';
  $connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

  if ($connection === false) {
    die("Failed to connect to database: " . mysqli_connect_error());
  }
  $sql = "SELECT * FROM schools WHERE id = '$School_Id'";
  $row = mysqli_fetch_array(mysqli_query($connection, $sql));

  $school_name = $row['name'];
  $school_type = $row['type'];
  $school_category = $row['category'];
  $grade_level_start = $row['grade_level_start'];
  $grade_level_end = $row['grade_level_end'];
  $current_enrollment = $row['current_enrollment'];
  $address_text = $row['address_text'];
  $state_name = $row['state_name'];
  $state_code = $row['state_code'];
  $pin_code = $row['pin_code'];
  
  $contact_name = $row['contact_name'];
  $contact_designation = $row['contact_designation'];
  $contact_phone = $row['contact_phone'];
  $contact_email = $row['contact_email'];
  $status = $row['status'];
  $notes = $row['notes'];
  $referenced_by = $row['referenced_by'];
  $supported_by = $row['supported_by'];

  $time = time();
  echo "<h3> School Details </h3>
	  <div id=\"school_icons\" class=\"school_icon\">";
			$profile_image = get_profile_image($School_Id); 
			echo "			<img src=\"$profile_image?v=$time\" alt=\"school image\">
	  </div>
	  <br>
      <div id= \"container_2\" class=\"school_details\">
      <label id=\"id-label\">School ID:</label><span class=\"school_details\">$School_Id</span><br>
      <label id=\"name-label\">School Name:</label><span class=\"school_details\">$school_name</span><br>
      <label id=\"type-label\">Type:</label><span class=\"school_details\">$school_type</span><br>
      <label id=\"category-label\">Category:</label><span class=\"school_details\">$school_category</span><br>
      <label id=\"grade-range-label\">Grades:</label><span class=\"school_details\">$grade_level_start to $grade_level_end</span><br>
      <label id=\"enrollment-label\">Current Enrollment:</label><span class=\"school_details\">$current_enrollment</span><br>
      <label id=\"address-label\">Address:</label><span class=\"school_details\">$address_text</span><br>
      <label id=\"state-name-label\">State Name:</label><span class=\"school_details\">$state_name</span><br>
      <label id=\"state-code-label\">State Code:</label><span class=\"school_details\">$state_code</span><br>
      <label id=\"type-label\">Pin Code:</label><span class=\"school_details\">$pin_code</span><br>
      <label id=\"referenced-by-label\">Referenced By: </label><span class\"school_details\">$referenced_by</span><br>
      <label id=\"supported-by-by\">Supported By:</label><span class=\"school_details\">$supported_by</span>
      </div>

	  <div id=\"right\" class=\"school_details\">
      <label id=\"contact-name-label\">Contact Name:</label><span class=\"school_details\">$contact_name</span><br>
      <label id=\"contact-designation-label\">Contact Designation:</label><span class=\"school_details\">$contact_designation</span><br>
      <label id=\"contact-number-label\">Contact Phone Number:</label><span class=\"school_details\">$contact_phone</span><br>
	  <label id=\"contact-email-label\">Contact Email:</label><span class=\"school_details\">$contact_email</span><br>
	  <label id=\"status-label\">Status:</label><span class=\"school_details\">$status</span>
	</div>
	<div class=\"school_notes\">
	  <span class=\"inverse\"><label id=\"notes-label\">Notes</label></span><br>
	  <span>$notes</span>
	</div>";
	// check that the media directory exists, if not, nothing to show here
    if(file_exists("schools/$School_Id/")) {
     echo "<div> 
	    <table id=\"school_media\">";
			$media_files = array_diff(scandir("schools/$School_Id/"), array('..', '.'));
			$counter = 0;  
        	while($counter < count($media_files)) {
				if($counter == 0) {
					echo "<tr>";
				}
				$filename = $media_files[$counter + 2];
				// profile image is at top of page so skip tiling it
				if(!str_contains($media_files[$counter + 2], "profile_image")) {
					echo  "<td class=\"school_media\">
						<img src=\"schools/$School_Id/$filename\" alt=\"school image\">
						<br>
					</td>";
				}
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
	} // end if file_exists
?>
  </body>
</html>