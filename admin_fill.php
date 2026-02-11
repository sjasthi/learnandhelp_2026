<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

function admin_school_form($id){
	if ($id != null) {   	
		$connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
  		if ($connection === false) {
    		die("Failed to connect to database: " . mysqli_connect_error());
  		}
  		$sql = "SELECT * FROM schools WHERE id = '$id'";
  		$row = mysqli_fetch_array(mysqli_query($connection, $sql));
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
		$referenced_by = $row["referenced_by"];
		$supported_by = $row["supported_by"];
  		$notes = $row["notes"];
	} else {
  		$name = "";
  		$type = "";
  		$category = "";
  		$grade_level_start = "";
  		$grade_level_end = "";
  		$current_enrollment = "";
  		$address_text = "";
  		$state_name = "";
  		$state_code = "";
  		$pin_code = "";
  		$contact_name = "";
  		$contact_designation = "";
  		$contact_phone = "";
  		$contact_email = "";
  		$status = "";
		$referenced_by = "";
		$supported_by = "";
  		$notes = "";
	}
  echo "<div id= \"container_2\">
  <form id=\"survey-form\" action=\"form-submit_school.php\" method = \"post\" enctype=\"multipart/form-data\">
  <div id=\"right\" style=\"width:400px;\">
  	<label id=\"school-name-label\" style=\"width: 50px;\">School ID</label>
  	<input type=\"text\" id=\"school-name\" name=\"name\" class=\"form\" value=\"$id\" readonly required><br> <!-- School ID -->
  
	<label id=\"school-name-label\">School Name</label>
    <input type=\"text\" id=\"school-name\" name=\"name\" class=\"form\" value=\"$name\" required><br> <!--School name--->
	
	<label id=\"school-type-label\">School Type</label>
	<select id=\"school-type-dropdown\" name=\"type\" required> <!--type--->
    	<option disabled value>Select School Type</option>
      	<option value='Primary School' ";
        	if (strtolower($type) == "primary school")
            	echo "selected";
      	        echo  ">Primary School</option>
      	<option value='Upper Primary School' ";
        	if (strtolower($type) == "upper primary school")
            	echo "selected";
      	        echo ">Upper Primary School</option>
      	<option value='High School' ";
        	if ($type == "High School" or $type == "high school")
            	echo "selected";
      	        echo ">High School</option>
	  	<option value='Other' ";
        	if ($type == "Other" or $type == "other")
            	echo "selected";
      	echo ">Other</option>
	</select><br>

	
	<label id=\"school-category-label\">Category</label>
	<select id=\"school-category-dropdown\" name=\"category\" required> <!--category--->
    	<option disabled value>Select School Category</option>
      	<option value='Private' ";
        	if ($category == "Private" or $category == "private")
            	echo "selected";
      	echo  ">Private</option>
      	<option value='Public' ";
        	if ($category == "Public" or $category == "public")
            	echo "selected";
      	echo ">Public</option>
	  	<option value='Other' ";
        	if ($category == "Other" or $category == "other")
            	echo "selected";
      	echo ">Other</option>
	</select><br>

	<label id=\"grade-level-start-label\">Grade Level Start</label>
	<select id=\"grade-level-start-dropdown\" name=\"grade_level_start\" required> <!--grade_level_start--->
    	<option disabled value>Select Grade Level Start</option>
      	<option value='1' ";
        	if ($grade_level_start == "1")
            	echo "selected";
      	echo  ">1</option>
      	<option value='2' ";
        	if ($grade_level_start == "2")
            	echo "selected";
      	echo ">2</option>
      	<option value='3' ";
        	if ($grade_level_start == "3")
            	echo "selected";
      	echo ">3</option>
	  	<option value='4' ";
        	if ($grade_level_start == "4")
            	echo "selected";
      	echo ">4</option>
      	<option value='5' ";
        	if ($grade_level_start == "5")
            	echo "selected";
      	echo ">5</option>
	  	<option value='6' ";
        	if ($grade_level_start == "6")
            	echo "selected";
      	echo ">6</option>
      	<option value='7' ";
        	if ($grade_level_start == "7")
            	echo "selected";
      	echo ">7</option>
	  	<option value='8' ";
        	if ($grade_level_start == "8")
            	echo "selected";
      	echo ">8</option>
      	<option value='9' ";
        	if ($grade_level_start == "9")
            	echo "selected";
      	echo ">9</option>
	  	<option value='10' ";
        	if ($grade_level_start == "10")
            	echo "selected";
      	echo ">10</option>
	</select><br>

	<label id=\"grade-level-end-label\">Grade Level End</label>
	<select id=\"grade-level-end-dropdown\" name=\"grade_level_end\" required><!--grade_level_end--->
    	<option disabled value>Select Grade Level End</option>
      	<option value='1' ";
        	if ($grade_level_end == "1")
            	echo "selected";
      	echo  ">1</option>
      	<option value='2' ";
        	if ($grade_level_end == "2")
            	echo "selected";
      	echo ">2</option>
      	<option value='3' ";
        	if ($grade_level_end == "3")
            	echo "selected";
      	echo ">3</option>
	  	<option value='4' ";
        	if ($grade_level_end == "4")
            	echo "selected";
      	echo ">4</option>
      	<option value='5' ";
        	if ($grade_level_end == "5")
            	echo "selected";
      	echo ">5</option>
	  	<option value='6' ";
        	if ($grade_level_end == "6")
            	echo "selected";
      	echo ">6</option>
      	<option value='7' ";
        	if ($grade_level_end == "7")
            	echo "selected";
      	echo ">7</option>
	  	<option value='8' ";
        	if ($grade_level_end == "8")
            	echo "selected";
      	echo ">8</option>
      	<option value='9' ";
        	if ($grade_level_end == "9")
            	echo "selected";
      	echo ">9</option>
	  	<option value='10' ";
        	if ($grade_level_end == "10")
            	echo "selected";
      	echo ">10</option>
	</select><br>

	<label id=\"current-enrollment-label\">Current Enrollment</label>
    <input type=\"text\" id=\"current-enrollment\" name=\"current_enrollment\" class=\"form\" value=\"$current_enrollment\" ><br> <!---current_enrollment-->

    <label id=\"school-address--label\">School Address</label>
    <input type=\"text\" id=\"school-address\" name=\"address_text\" class=\"form\" value=\"$address_text\" required><br> <!---address_text-->

    <label id=\"state-name-label\">State</label><br>
    <input type=\"text\" id=\"state-name\" name=\"state_name\" class=\"form\" value=\"$state_name\" ><br> <!---state_name--></div>

	<div id=\"right\">
    <label id=\"state-code-label\">State Code</label>
    <input type=\"text\" id=\"state-code\" name=\"state_code\" class=\"form\" value=\"$state_code\" ><br> <!---state_code-->

    <label id=\"pin-code-label\">Pin Code</label>
    <input type=\"text\" id=\"pin-code\" name=\"pin_code\" class=\"form\" value=\"$pin_code\" ><br> <!---pin_code-->

    <label id=\"contact-name-label\">Contact Name</label>
    <input type=\"text\" id=\"contact-name\" name=\"contact_name\" class=\"form\" value=\"$contact_name\" ><br> <!---contact_name-->

	<label id=\"contact-designation-label\">Contact Designation</label>
	<select id=\"contact-designation-dropdown\" name=\"contact_designation\" > <!--contact designation--->
    	<option disabled value>Select Contact Designation</option>
      	<option value='Teacher' ";
        	if ($contact_designation == "Teacher" or $contact_designation == "teacher")
            	echo "selected";
      	echo  ">Teacher</option>
      	<option value='Head Master' ";
        	if ($contact_designation == "Head Master" or $contact_designation == "head master")
            	echo "selected";
      	echo ">Head Master</option>
      	<option value='Volunteer' ";
        	if ($contact_designation == "Volunteer" or $contact_designation == "volunteer")
            	echo "selected";
      	echo ">Volunteer</option>
	  	<option value='Other' ";
        	if ($contact_designation == "Other" or $contact_designation == "other")
            	echo "selected";
      	echo ">Other</option>
	</select><br>

	<label id=\"contact-phone-label\">Contact Phone</label>
    <input type=\"tel\" id=\"contact-phone\" name=\"contact_phone\" class=\"form\" value=\"$contact_phone\" required><br><!---contact_phone-->

    <label id=\"contact-email-label\">Contact Email</label>
    <input type=\"email\" id=\"contact-email\" name=\"contact_email\" class=\"form\" value=\"$contact_email\" ><br><!---contact_email-->

	<label id=\"status-label\">Status</label><br>
	<select id=\"status-dropdown\" name=\"status\" ><!--status--->
    	<option disabled value>Select School Status</option>
      	<option value='Proposed' ";
        	if ($status == "Proposed" or $status == "proposed")
            	echo "selected";
      	echo  ">Proposed</option>
      	<option value='Rejected' ";
        	if ($status == "Rejected" or $status == "rejected")
            	echo "selected";
      	echo ">Rejected</option>
      	<option value='Approved' ";
        	if ($status == "Approved" or $status == "approved")
            	echo "selected";
      	echo ">Approved</option>
	  	<option value='Completed' ";
        	if ($status == "Completed" or $status == "completed")
            	echo "selected";
      	echo ">Completed</option>
	</select><br>
	
	<label id\"referenced-by-label\">Referenced By </label>
	<input type=\"text\" id=\"reference-by\" name=\"referenced_by\" class=\"form\" value=\"$referenced_by\"><br>

	<label id=\"supported-by-label\">Supported By</label>
	<select id=\"supported-by-dropdown\" name=\"supported_by\" required>
		<option disable value>Select Supported By</option>
		<option value='Learn and Help'";
			if ($supported_by == "Learn and Help" or $supported_by == "learn and help")
				echo "selected";
		echo ">Learn and Help</option>
		<option value='NRIVA'";
			if ($supported_by == "NRIVA" or $supported_by == "nriva")
				echo "selected";
		echo ">NRIVA</option>
		<option value='PGNF'";
			if ($supported_by == "PGNF" or $supported_by == "pgnf")
				echo "selected";
		echo ">PGNF</option>
	</select><br>
	
	<label id=\"notes-label\">Notes</label><br>
    <input type=\"text\" id=\"notes\" name=\"notes\" class=\"form\" value=\"$notes\"><br></div><!---notes-->";
}

function admin_class_form($Class_Id){
  $connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

  if ($connection === false) {
    die("Failed to connect to database: " . mysqli_connect_error());
  }
  $sql = "SELECT * FROM classes WHERE Class_Id = '$Class_Id'";
  $row = mysqli_fetch_array(mysqli_query($connection, $sql));

  $class_name = $row["Class_Name"];
  $description = $row["Description"];
  $status = $row["Status"];  //Added to pull in Status column value

  // Updated form to include course status read in from database.  Gives admin option to change.
  // Changed "Course Description" field for input type="text" to text area; allowing customer to resize field.
  echo "<div id= \"container\">
  <form id=\"survey-form\" action=\"form-submit_class.php\" method = \"post\" onSubmit=\"window.location.reload()\">
    <input type='hidden' name='Class_Id' value=$Class_Id>
    <label id=\"name-label\">Course Name</label>
    <input type=\"text\" id=\"class-name\" name=\"Class_Name\" class=\"form\" value=\"$class_name\" required><br><!--class_name--->
    <label id=\"description-label\">Course Description</label><br>
	<textarea rows=\"5\" cols=\"35\" id=\"description\" name=\"Description\" class=\"form\" required>". htmlspecialchars($description) ."</textarea><br><!---description-->
	<select id=\"status\" name=\"Status\" class=\"form\" required>
		<option value=\"Proposed\" ". ($status == 'Proposed' ? 'selected' : '') .">Proposed</option>
		<option value=\"Approved\" ". ($status == 'Approved' ? 'selected' : '') .">Approved</option>
		<option value=\"Inactive\" ". ($status == 'Inactive' ? 'selected' : '') .">Inactive</option>
	</select><br><!---status-->    
    </div>";
  
}

function admin_fill_form($Reg_Id) {

  $connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

  if ($connection === false) {
    die("Failed to connect to database: " . mysqli_connect_error());
  }
  $sql = "SELECT * FROM registrations NATURAL JOIN classes WHERE Reg_ID = '$Reg_Id'";
  $row = mysqli_fetch_array(mysqli_query($connection, $sql));

  $sponsor_name = $row['Sponsor_Name'];
  $sponsor_email = $row['Sponsor_Email'];
  $sponsor_phone = $row['Sponsor_Phone_Number'];
  $spouse_name = $row['Spouse_Name'];
  $spouse_email = $row['Spouse_Email'];
  $spouse_phone = $row['Spouse_Phone_Number'];
  $student_name = $row['Student_Name'];
  $student_email = $row['Student_Email'];
  $student_phone = $row['Student_Phone_Number'];
  $class = $row['Class_Name'];
  $cause = $row['Cause'];

  echo "<div id= \"container_2\">
    <form id=\"survey-form\" action=\"form-submit.php\" method = \"post\">
      <input type='hidden' name='Reg_Id' value=$Reg_Id>
      <!---Sponsors Section -->
      <label id=\"name-label\">Sponsor's Name</label>
      <input type=\"text\" id=\"sponsers-name\" name=\"sponsers-name\" class=\"form\" value=\"$sponsor_name\" required><br><!--name--->
      <label id=\"sponsers-email-label\"> Sponsor's Email</label>
      <input type=\"email\" id=\"sponsers-email\" name=\"sponsers-email\" class=\"form\" value=\"$sponsor_email\" required><br><!---email-->
      <label id=\"sponsors-number-label\">Sponsor's Phone Number</label>
      <input type=\"tel\" id=\"sponsers-phone\" name=\"sponsers-phone\" value=\"$sponsor_phone\" required>

      <br>
      <!---Spouse Section -->
      <label id=\"spouses-name-label\">Spouse's Name</label>
      <input type=\"text\" id=\"spouses-name\" name=\"spouses-name\" class=\"form\" value=\"$spouse_name\" required><br>
      <label id=\"spouses-email-label\"> Spouse's Email</label>
      <input type=\"email\" id=\"spouses-email\" name=\"spouses-email\" class=\"form\" value=\"$spouse_email\" required ><br>
      <label id=\"spouses-number-label\">Spouse's Phone Number</label>
      <input type=\"tel\" id=\"spouses-phone\" name=\"spouses-phone\" value=\"$spouse_phone\" required>

      <br>
      </div>
      <div id=\"right\">
      <!---Student Section -->
      <label id=\"students-name-label\">Student's Name</label>
      <input type=\"text\" id=\"students-name\" name=\"students-name\" class=\"form\" required value=\"$student_name\"><br>
      <label id=\"students-email-label\"> Student's Email</label>
      <input type=\"email\" id=\"students-email\" name=\"students-email\" class=\"form\" required value=\"$student_email\"><br>
      <label id=\"students-number-label\">Student's Phone Number</label>
      <input type=\"tel\" id=\"students-phone\" name=\"students-phone\" value=\"$student_phone\" required>

      <br>
      <label id=\"class\">Select Class</label>
      <select id=\"dropdown\" name=\"role\" required>
        <option disabled value>
          Select your class
        </option>
        <option value=2 ";
          if ($class == "Python 101")
              echo "selected";
      echo  ">
          Python 101
        </option>
        <option value=1 ";
        if ($class == "Java 101")
            echo "selected";
      echo ">
          Java 101
        </option>
        <option value=4 ";
        if ($class == "Python 201")
            echo "selected";
      echo ">
          Python 201
        </option>
	  <option value=3 ";
        if ($class == "Java 201")
            echo "selected";
      echo ">
		Java 201
	  </option>
	</select>
	<!--dropdown--->
	<p><strong>Cause</strong></p>
	<label>
	  <input type=\"radio\" name=\"cause\" value=\"lib\" ";
        if ($cause == "Library")
            echo "checked=\"checked\"";
      echo ">Library
	</label>
	<br>
	<label>
	  <input type=\"radio\" name=\"cause\" value=\"Dig_class\" ";
        if ($cause == "Digital Classroom")
            echo "checked=\"checked\"";
      echo ">Digital Classroom</label>
	<label>
	  <br>
	  <input type=\"radio\" name=\"cause\" value=\"Other\" ";
        if ($cause == "No Preference")
            echo "checked=\"checked\"";
      echo "> No Preference
	</label><!---radioButtons--->
  <input type='hidden' name='Reg_Id' value='". $Reg_Id . "'/>
  </div>
  ";
}
?>
