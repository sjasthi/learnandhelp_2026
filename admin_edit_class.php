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
 ?>

<?php $Class_Id = $_POST['Class_Id']?>
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
        <h1> <span class="accent-text">Class Editor</span></h1>
      </div>
    </header>
    <form method="POST" action="admin_classes.php">
      <input type="submit" value="Return to Classes">
	</form>
    <div id="container_2">
    <?php
      admin_class_form($Class_Id);
      if(isset($_SESSION['message'])) {
          echo $_SESSION['message'];
	      unset($_SESSION['message']);
	  } else {
		  echo "<input type=\"hidden\" id=\"action\" name=\"action\" value=\"admin_edit_class\">
		  <br>
		  <input type=\"submit\" id=\"submit-class\" name=\"submit\" value=\"Submit\">";
	  } ?>
	  </form><!---survey-form--->
	</div>
  </body>
</html>
