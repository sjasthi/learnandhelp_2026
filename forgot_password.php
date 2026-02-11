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
    <title>Forgot password</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
  </head>
  <body>
  <?php include 'show-navbar.php'; ?>
  <?php show_navbar(); ?>
  <header class="inverse">
      <div class="container">
          <h1><span class="accent-text">Reset your password</span></h1>
      </div>
  </header>
  <br>
  
  <h1> </h1>Please contact siva.jasthi@gmail.com for password reset. </h1>
  <br>
  <br>
  <br>
  
 <!-- <form action="password_change.php" method="post">-->
 <!--     <label for="email">Email</label>-->
 <!--     <br>-->
 <!--     <input id="email" type="email" name="email" placeholder="Yourname@email.com" required>-->
 <!--     <br>-->
 <!--     <input type="submit" id="submit" value="submit" name="submit"/>-->
	<!--</form>-->

 <!--   <form action="login.php" method="post">-->
 <!--       <br>-->
 <!--       <input type="submit" id="submit" value="cancel"/>-->
 <!--   </form>-->
</html>

<?php include 'footer.php'; ?>
