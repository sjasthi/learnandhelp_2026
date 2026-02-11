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

$book_id = $_POST['book_id'];
$image = $_POST['book_image'];

// data passed to form by $_POST
if($action == 'admin_edit_book' or $action == 'admin_add_book'){
	$id = $_POST['id'];
	$title = $_POST["title"];
	$author = $_POST["author"];
	$publisher = $_POST["publisher"];
	$publishyear = $_POST["publishyear"];
	$numpages = $_POST["numpages"];
	$price = $_POST["price"];
	$image = $_POST["image"];
    //potential multiple selections for gradelevel need to be converted to
	// a comma separated list that can be sent to MYSQL when doing an UPDATE
	// or INSERT
	$temp_array = $_POST["gradelevel"];
	for($i = 0; $i < count($temp_array); $i++) {
		if($i == 0) {
			$gradelevel = $temp_array[$i];
		} else {
			$gradelevel .= ', ' . $temp_array[$i];
		}
	}
	$available = $_POST["available"];
}

// where the inserts and updates take place
if($action == "admin_edit_book") {
	$sql = "UPDATE books SET
			title = '$title',
			author = '$author',
			publisher = '$publisher',
			publishYear = '$publishyear',
			numPages = '$numpages',
			price = '$price',
			image = '$image',
			grade_level = '$gradelevel',
			available = '$available'
			WHERE id = '$id';";
} elseif($action == 'admin_add_book') {
	$sql = "INSERT INTO books VALUES (
		'NULL',
		'$title',
		'$author',
		'$publisher', 
		'$publishyear',
		'$numpages',
	    '$price',
	    'NULL',
		'$gradelevel',
		'$available',
		CURRENT_TIME(),
	    CURRENT_TIME());";
}

if (!mysqli_query($connection, $sql)) {
	echo("Error description: " . mysqli_error($connection));
} else {
	// no sql error so we can check if we were adding or editing
	// and deal with the image file if one was provided
	if($action == 'admin_add_book') {
		// should already have the id if we were editing
		// if we added a new book get the id for it
		$id = mysqli_insert_id($connection);
	}

	// id is not null and there is some file in the list
	if($id != null && !empty($_FILES['file']['name'])) {

		// delete the book image if one exists
		if(is_file($image)) {
			unlink($image);
		}

		// get the name portion of the new image file
		$fileName = $_FILES['file']['name'];
		// get the tmp file name
		$fileTMP = $_FILES['file']['tmp_name'];
		// get any error
		$fileError = $_FILES['file']['error'];
		// get the extention of the new image file
		$fileExt = explode('.', $fileName);
		$fileActualExt = strtolower(end($fileExt));
		// create the destination variable
		$fileDestination = "";
		// if no error move tmp file to the destination directory
		if ($fileError === 0) {
  			$fileNewName = "book".$id.".".$fileActualExt;
  			$fileDestination = 'images/books/'.$fileNewName;
  			move_uploaded_file($fileTMP, $fileDestination);
		} else {
  			echo "There was an error uploading your file.";
		}

		$sql = "UPDATE books SET image='$fileDestination' WHERE id=$id;";

		if (!mysqli_query($connection, $sql)) {
  			echo("Error description: " . mysqli_error($connection));
		}
	}

	echo "<script type=\"text/javascript\">setTimeout(function(){document.getElementById('form_submitted').submit();},500);
		  </script>";
}

mysqli_close($connection);
?>
<div style="text-align:center;margin-top:200px;"><h3>One moment please. Processing changes...</h3>
       <img src="images/loadingIcon.gif"></img>
</div>
<form method="POST" id="form_submitted" action="book_edit.php">
	<?php echo "<input type=\"hidden\" name=\"book_id\" value=\"$id\">"; ?>
</form>

