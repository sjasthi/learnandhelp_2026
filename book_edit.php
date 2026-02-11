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

  $book_id = $_POST['book_id'] ?? null;
  $book_image = $_POST['book_image'] ?? null;
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
          include 'book_fill.php';
          ?>
    <?php show_navbar(); ?>
    <header class="inverse">
      <div class="container">
        <h1> <span class="accent-text">Books Form</span></h1>
      </div>
	</header>
	<?php if ($book_id != null) {
		echo "<h3>Edit Book</h3>";
	} else {
		echo "<h3>Add Book</h3>";
	}
    ?>
    <form method="POST" action="books.php">
      <input type="submit" value="Return to Books">
	</form>
    <div>
	<?php
		  fill_book_form($book_id);
		  echo "<div>
			Select cover image for book:<br>
			<input type=\"hidden\" name=\"book_id\" value=\"$book_id\">
			<input type=\"hidden\" name=\"book_image\" value=\"$book_image\">
			<input id=\"media_upload\" type=\"file\" name=\"file\">
		</div>";
		if($book_id != null) { 
			echo "<input type=\"hidden\" id=\"action\" name=\"action\" value=\"admin_edit_book\">
		  	<br>
		  	<input type=\"submit\" id=\"submit-book\" name=\"submit\" value=\"Submit\">";
		} else {
			echo "<input type=\"hidden\" id=\"action\" name=\"action\" value=\"admin_add_book\">
	        <br>
	        <input type=\"submit\" id=\"submit-book\" name=\"submit\" value=\"Submit\">";
		}
	?>
	  </form><!--survey-form-->
	</div>
  </body>
</html>
