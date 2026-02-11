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
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
  </head>
  <body>
  <?php include 'show-navbar.php'; ?>
  <?php show_navbar(); ?>
  <header class="inverse">
      <div class="container">
          <h1><span class="accent-text">Login</span></h1>
      </div>
  </header>
  <br>
  
  <?php
  // Display login error if exists
  if (isset($_SESSION['login_error'])) {
      echo '<div style="color: red; text-align: center; margin: 10px 0;">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
      unset($_SESSION['login_error']); // Clear the error after displaying
  }
  ?>
  
  <form action="loginAction.php" method="post">
      <label for="usermail">Email</label>
      <br>
      <input id="usermail" type="email" name="usermail" placeholder="Yourname@email.com" required>
      <br>
      <label for="password">Password</label>
      <br>
      <input id="password" type="password" name="password" placeholder="Password" required>
      <br>
      <input type="submit" id="submit-login" value="Login"/>
  </form>
  <a href="create_account.php" id="create_account_button">Create Account</a>
  <br>
  <br>
  <a href="forgot_password.php" id="create_account_button">Forgot password?</a>
  </body>
</html>
