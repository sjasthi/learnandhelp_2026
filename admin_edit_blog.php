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
	// Create connection
	$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
	// Check connection
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
	$sql = "SELECT * FROM blogs where Blog_Id='$Blog_Id'";
	$result = $conn->query($sql);
	$result = mysqli_fetch_array($result);
	// Getting List Images
	$PIC_sql = "SELECT * FROM blog_pictures where Blog_Id='$Blog_Id'";
	$picture_list = $conn->query($PIC_sql);
	$picture_count = mysqli_num_rows($picture_list);
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
    <?php include 'show-navbar.php'; ?>
    <?php show_navbar(); ?>
    <header class="inverse">
      <div class="container">
        <h1> <span class="accent-text">Edit Blog</span></h1>
      </div>
	</header>
    <form method="POST" action="admin_blogs.php">
      <input type="submit" value="Return to Blogs">
	</form>
    <div id="container_1" style="text-align:center">
  	<center>
		<table>
					<?php
					$counter = 1;
					echo "<tr>";	
				
				while($row = mysqli_fetch_array($picture_list)) 
				{
					echo "<td align=\"center\" style=\"border:solid 1px; width:25%\">
						<img src='".$row["Location"]."' style='width:150px; height:150px'></img>						
						<form action='admin_delete_blog_pictures.php' method='post' enctype='multipart/form-data'>
							<input type='hidden' id='Picture_Id' name='Picture_Id' value='".$row['Picture_Id']."'>
							<input type='hidden' id='Blog_Id]' name='Blog_Id' value='".$result['Blog_Id']."'><br>	
							<input type=\"submit\" value=\"Delete\" id=\"btnDelete\" name=\"btnDelete\">
						</form>
						</td>";
					if($counter % 4 == 0 or $counter == $picture_count) {
						echo "</tr>";
					}
					$counter++;
				}
				?>
				</table>
			</td>
		<table>
	<br>
	<form action="admin_blog_submit.php" method="post" enctype="multipart/form-data">
	 <table id="blog_edit">
		<tr>
			<td class="td_right">Blog ID</td>
			<td class="td_left"> &nbsp;&nbsp; <?php echo $result['Blog_Id']; ?></td>
		</tr>
		<tr>
			<td class="td_right">Title </td>
			<td class="td_left"><input type="text" id="Title" name="Title" value='<?php echo $result['Title']; ?>'>
			</td>
		</tr>
		<tr>
			<td class="td_right">Author </td>
			<td class="td_left"><input type="text" id="Author" name="Author" value='<?php echo $result['Author']; ?>' placeholder="Author"></td>
		</tr>

		
		<tr>
  <td class="td_right">Description </td>
  <td class="td_left">
    <textarea id="Description" name="Description" placeholder="Description" rows="5" cols="70"><?php echo $result['Description']; ?></textarea>
  </td>
</tr>

		<tr>
			<td class="td_right">Video_Link </td>
			<td class="td_left"><input type="text" id="Video_Link" name="Video_Link" value='<?php echo $result['Video_Link']; ?>' placeholder="Video Link"></td>
		</tr>
		<tr>
			<td class="td_right">Upload Picture </td>
			<td class="td_left"><input type="file" id="Location" name="Location[]" multiple></td>
		</tr>
	 </table>
		<input type="hidden" id="Blog_Id" name="Blog_Id" value="<?php echo $result['Blog_Id']; ?>">	
 	 	<input type="submit" value="Save" id="btnSave" name="btnSave">
	 </form>
	  </center>
	</div>
  </body>
</html>
