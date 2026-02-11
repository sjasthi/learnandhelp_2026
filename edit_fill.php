<?php
require 'db_configuration.php';

$status = session_status();
if ($status == PHP_SESSION_NONE) {
  session_start();
}


function fill_form() {
    $connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
    if (isset($_SESSION['User_Id']) && !isset($_POST['action'])) {
        $user_id = $_SESSION['User_Id'];

        if ($connection === false) {
            die("Failed to connect to database: " . mysqli_connect_error());
        }

        $sql = "SELECT * FROM registrations Natural Join classes WHERE User_Id = '$user_id'";
        $row = mysqli_fetch_array(mysqli_query($connection, $sql));

        $db_id = $row['Reg_Id'];
        $sponsor1_name = $row['Sponsor1_Name'];
        $sponsor1_email = $row['Sponsor1_Email'];
        $sponsor1_phone = $row['Sponsor1_Phone_Number'];
        $sponsor2_name = $row['Sponsor2_Name'];
        $sponsor2_email = $row['Sponsor2_Email'];
        $sponsor2_phone = $row['Sponsor2_Phone_Number'];
        $student_name = $row['Student_Name'];
        $student_email = $row['Student_Email'];
        $student_phone = $row['Student_Phone_Number'];

        $payment_id = $row['Payment_Id'];
        $class_name = $row['Class_Name'];
        $class = $row['Class_Id'];
        $batch = $row['Batch_Name'];
    } else {
        $student_email = $_POST['students-email'];
        $sql = "SELECT Reg_Id FROM registrations WHERE Student_Email = '$student_email'";
        $row = mysqli_fetch_array(mysqli_query($connection, $sql));
        $db_id = $row['Reg_Id'];

        $sponsor1_name = isset($_POST['sponsor1s-name']) ? $_POST['sponsor1s-name'] : '';
        $sponsor1_email = isset($_POST['sponsor1s-email']) ? $_POST['sponsor1s-email'] : '';
        $sponsor1_phone = isset($_POST['sponsor1s-phone']) ? $_POST['sponsor1s-phone'] : '';
        $sponsor2_name = isset($_POST['sponsor2s-name']) ? $_POST['sponsor2s-name'] : '';
        $sponsor2_email = isset($_POST['sponsor2s-email']) ? $_POST['sponsor2s-email'] : '';
        $sponsor2_phone = isset($_POST['sponsor2s-phone']) ? $_POST['sponsor2s-phone'] : '';
        $student_name = isset($_POST['students-name']) ? $_POST['students-name'] : '';
        $batch = isset($_POST['batch']) ? $_POST['batch'] : '';
        $student_phone = isset($_POST['students-phone']) ? $_POST['students-phone'] : '';

        $class_id = isset($_POST['class']) ? $_POST['class'] : '';
        $payment_id = isset($_POST['payment_id']) ? $_POST['payment_id'] : '';
    }
    echo "<div id= \"container_2\">
      <form id=\"survey-form\" action=\"form-submit.php\" method = \"post\">
        <input type=\"hidden\" name=\"reg_id\" value=\"$db_id\">
        <!---Sponsors Section -->
        <label id=\"name-label\">Sponsor 1's Name</label>
        <input type=\"text\" id=\"sponsor1s-name\" name=\"sponsor1s-name\" class=\"form\" value=\"$sponsor1_name\" required><br><!--name--->
        <label id=\"sponsor1-email-label\"> Sponsor 1's Email</label>
        <input type=\"email\" id=\"sponsor1s-email\" name=\"sponsor1s-email\" class=\"form\" value=\"$sponsor1_email\" required><br><!---email-->
        <label id=\"sponsors-number-label\">Sponsor 1's Phone Number</label>
        <input type=\"tel\" id=\"sponsor1s-phone\" name=\"sponsor1s-phone\" value=\"$sponsor1_phone\" required>

        <br>
        <!---sponsor2 Section -->
        <label id=\"sponsor2s-name-label\">Sponsor 2's Name</label>
        <input type=\"text\" id=\"sponsor2s-name\" name=\"sponsor2s-name\" class=\"form\" value=\"$sponsor2_name\"><br>
        <label id=\"sponsor2s-email-label\"> Sponsor 2's Email</label>
        <input type=\"email\" id=\"sponsor2s-email\" name=\"sponsor2s-email\" class=\"form\" value=\"$sponsor2_email\"><br>
        <label id=\"sponsor2s-number-label\">Sponsor 2's Phone Number</label>
        <input type=\"tel\" id=\"sponsor2s-phone\" name=\"sponsor2s-phone\" value=\"$sponsor2_phone\">

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
        <br>";
        // Get active Batch
        $connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

        if ($connection === false) {
          $connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
          if ($connection === false) {
            die("Failed to connect to database: " . mysqli_connect_error());
          }
        }
        $sql = <<< SQL
                  SELECT value 
                  FROM preferences 
                  WHERE Preference_Name = 'Active Registration';
                  SQL;
        $result = mysqli_query($connection, $sql);
        if ($result) {
          $row = mysqli_fetch_assoc($result);
          $active_batch = $row['value'];
        } else {
          $active_batch = null;
          echo "Error: " . mysqli_error($connection);
        }
      
        echo "
        <br>
        <label id=\"batch-label\"><b>Batch Name:</b> $active_batch</label>
        <input type='hidden' name='active_batch' value=$active_batch>
	<input type='hidden' name='payment_id' value=$payment_id>
        <br>
        <br>
        <label id=\"class\">Select Class</label>
        <select id=\"dropdown\" name=\"class\" required>
          <option disabled value>
            Select your class
          </option>";
		  
		  	// Select view of available classes for users from accessing the page 
	// Admin's can see all classes regardless of status
	if ((isset($_SESSION['email'])) &&  $_SESSION['role'] == 'admin') {
	  $class_query = "SELECT Class_Id, Class_Name, Description, Status FROM classes;";
	}
	//Non-Admin's and users not logged in can only see "Approved" Classes
	else {		
		$offerings_query = "SELECT Class_Id FROM offerings WHERE Batch_Name = '$batch';";
		$offerings_result = $connection->query($offerings_query);
		
		$class_id_list = "";
		if($offerings_result->num_rows > 0)
		{
			$i = 0;
			while($offerings_row = $offerings_result->fetch_assoc())
			{
				$class_id_list .= strval($offerings_row["Class_Id"]);
				$i++;
				if($i < $offerings_result->num_rows){
					$class_id_list .= ", ";
				}
			}
		}
		
		$class_query = "SELECT Class_Id, Class_Name, Description, Status FROM classes WHERE Class_Id IN ($class_id_list)";
	}

	// Fetch classes from the database
	//$class_query = "SELECT * FROM classes";
	$class_result = $connection->query($class_query);
	if (!$class_result) {
	  echo "Error: " . $connection->error;
	} 
	else {
	  if ($class_result->num_rows > 0) {
		while ($row = $class_result->fetch_assoc())
			echo "<option value=\"" . $row["Class_Id"] . "\">" . $row["Class_Name"] . "</option>";
	  } 
	  else {
			echo "<option disabled selected value>No classes found</option>";
	  }
	}
	mysqli_free_result($class_result);
	mysqli_close($connection);
	echo "</select>
		<!--dropdown--->
    </div>
    ";
}
?>