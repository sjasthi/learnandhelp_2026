<?php
  $status = session_status();
  if ($status == PHP_SESSION_NONE) {
    session_start();
  }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="icon" href="images/logo.png" type="image/icon type">
    <title>Create Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
  
<style>
  /* Ensure consistent width and alignment for all fields in this form */
  form#create-account .form-row { 
    max-width: 420px; 
    margin: 0 auto 14px; 
  }
  form#create-account .form-row label { 
    display: block; 
    margin-bottom: 6px; 
    text-align: center; 
    font-weight: 600;
  }
  form#create-account .form-row input[type="text"],
  form#create-account .form-row input[type="email"],
  form#create-account .form-row input[type="password"] {
    width: 100%;
    padding: 10px;
    box-sizing: border-box;
  }
  form#create-account .actions {
    max-width: 420px;
    margin: 16px auto;
    text-align: center;
  }
</style>

  </head>
  <body>
  <?php include 'show-navbar.php'; ?>
  <?php show_navbar(); ?>
  <header class="inverse">
      <div class="container">
          <h1> Welcome to <span class="accent-text">Learn and Help</span></h1>
      </div>
  </header>
  <br>
  <form id="create-account" action="process_new_account.php" method="post">
      <div> <p> Parents: Please create your account once with your details. After that, you can log in to register your child(ren).<br> <br></p></div>
      <div class="form-row"><label for="firstname">Parent's First Name</label>
      <input id="firstname" type="text" name="firstname" placeholder="First Name" required></div>
    
      
      <div class="form-row"><label for="lastname">Parent's Last Name</label>
      <input id="lastname" type="text" name="lastname" placeholder="Last name" required></div>
      
      <div class="form-row"><label for="usermail">Email</label>
      <input id="usermail" type="email" name="usermail" placeholder="Yourname@email.com" required></div>
      
      <div class="form-row"><label for="password">Password</label>
      <input id="password" type="password" name="password" placeholder="Password" required></div>
    
    <div class="form-row"><label for="phone">Parent's Mobile Number:</label>
     <input type="text" id="phone" name="phone" required inputmode="tel" autocomplete="tel" placeholder="(555) 123-4567"></div>
    
      <div class="actions"><input type="submit" id="submit-login" value="Create Account"/></div>
	  
    
</form>
  
<script>
document.addEventListener('DOMContentLoaded', function () {
  var phoneInput = document.getElementById('phone');
  if (!phoneInput) return;
  var form = phoneInput.closest('form');
  if (!form) return;

  function validatePhone() {
    var raw = phoneInput.value || '';
    var digits = raw.replace(/[\s-]/g, '');
    if (!/^\d{10}$/.test(digits)) {
      phoneInput.setCustomValidity('Please enter a valid 10-digit mobile number (spaces and hyphens are OK).');
      return false;
    } else {
      phoneInput.setCustomValidity('');
      return true;
    }
  }

  // Validate on input and on submit
  phoneInput.addEventListener('input', validatePhone);
  form.addEventListener('submit', function (e) {
    if (!validatePhone()) {
      e.preventDefault();
      phoneInput.reportValidity();
    }
  });
});
</script>

</body>
</html>
