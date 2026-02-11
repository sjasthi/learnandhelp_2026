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
    <title>New Password Entry</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
  </head>
  <body>
  <?php include 'show-navbar.php'; ?>
  <?php show_navbar(); ?>
  <header class="inverse">
      <div class="container">
          <h1><span class="accent-text">Password Changed Successfully</span></h1>
      </div>
  </header>
  <br>
    <p>Click the button below to return to the login page and sign in with your new password.</p>
    <a href="login.php"><button> Login </button></a>
  <form action="password_change_confirmation.php" method="post"> 
  </form>
  </body>
</html>
</html>
