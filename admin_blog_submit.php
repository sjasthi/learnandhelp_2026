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
    $connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

	$Blog_Id        = $_POST['Blog_Id'];
    $Title          = $_POST['Title'];
    $Author         = $_POST['Author'];
    $Description    = $_POST['Description'];
    $Video_Link     = $_POST['Video_Link'];

    if($Blog_Id!="" && $Blog_Id!="0")
    {
        $sql = "UPDATE blogs SET
                     Title = '$Title',
                     Author = '$Author',
                     Description = '$Description',
                     Video_Link = '$Video_Link',
                     Modified_Time = CURRENT_TIME()
                     WHERE Blog_Id = '$Blog_Id'";
		if (!mysqli_query($connection, $sql)) {
			echo("Error description: " . mysqli_error($connection));
		} else {
			echo "<script type=\"text/javascript\">setTimeout(function(){document.getElementById('add_submitted_form').submit();},500);
		  		</script>";
		}

		if(!empty(array_filter($_FILES['Location']['name']))) {

			 $fileCount = count($_FILES['Location']['name']);
             for($i=0; $i < $fileCount; $i++)
             {
                 $fileTmpName   = $_FILES['Location']['tmp_name'][$i];
                 $fileType      = $_FILES['Location']['type'][$i];
                 $guid          = uniqid();
                 $extension     = pathinfo($_FILES['Location']['name'][$i], PATHINFO_EXTENSION);
                 $FileLocation  = $guid . '.' . $extension;
                 $destination   = 'images/blog_pictures/' . $FileLocation;
                 $sql           = "INSERT INTO blog_pictures VALUES (NULL, '$Blog_Id', '$destination')";
                 mysqli_query($connection, $sql);
                 move_uploaded_file($fileTmpName, $destination);
             }
		 }
    }
    mysqli_close($connection);
?>
<div style="text-align:center;margin-top:200px;"><h4>One moment please. Processing changes...</h4>
       <img src="images/loadingIcon.gif"></img>
</div>
<form method="POST" id="add_submitted_form" action="admin_edit_blog.php">
	<?php echo "<input type=\"hidden\" name=\"Blog_Id\" value=\"$Blog_Id\">"; ?>
</form>

