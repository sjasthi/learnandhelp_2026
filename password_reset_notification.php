<?php
  $status = session_status();
  if ($status == PHP_SESSION_NONE) {
    session_start();
  }
 ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Password reset notification</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
  </head>
  <body>
  <?php include 'show-navbar.php'; ?>
  <?php show_navbar(); ?>
  <header class="inverse">
      <div class="container">
          <h1><span class="accent-text">We've sent you an email</span></h1> <!-- This is the header. No function implemented yet  -->
      </div>
  </header>
  <br>
  <form action="login.php" method="post"> <!-- This button redirects the user back to the login page -->
        <br>
        <input type="submit" id="submit" value="Back to login"/>
    </form>
</html>
