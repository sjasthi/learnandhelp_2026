<?php
  $status = session_status();
  if ($status == PHP_SESSION_NONE) {
    session_start();
  }

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

if (isset($_POST['submit'])) {
    // Gets the message and user's email from the form
    $name = $_POST['your name'];
    $phone= $_POST['your phone'];
    $message = $_POST['message'];
    $visitorEmail = $_POST['user_email'];
    

    // Connect to your database
    $db_host = "localhost";
    $db_name = "learn_and_help_db";
    $db_user = "root";
    $db_pass = "";

    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

    if (mysqli_connect_error()) {
        echo mysqli_connect_error();
        exit;
    }

    // gets all the admins from the database
    $sql = "SELECT Email FROM users WHERE Role = 'admin'";
    $result = $conn->query($sql);

    $adminEmails = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $adminEmails[] = $row['Email'];
        }
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom($visitorEmail);
        $mail->isHTML(true);
        $mail->setFrom('contact_us_message@learnandhelp.com', 'Learn and Help Contact us message'); // hides the sender
        $mail->Username = 'mekics499project24@gmail.com'; // your gmail, substitute with admin email
        $mail->Password = 'fwlphiafqwbzkubj'; // your gmail app pass

        foreach ($adminEmails as $adminEmail) {
            $mail->addAddress($adminEmail);
        }

        // add the option to automatically send a reply to the sender
        $mail->addReplyTo($visitorEmail);

        $mail->Subject = 'Contact Us Message';
        $mail->Body = $message;

        $mail->send();

        // sends a alert 
        echo "<script>alert('Sent Successfully');document.location.href = 'contact_us2.php'</script>";
        exit();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>



<!DOCTYPE html>
<html>
  <head>
   <title>Align Content to Left</title>
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
        <h1> <span class="accent-text">About</span></h1>
      </div>
  </header>
    <section class="about-me">
      <div class="container">
      <div class="input-row">
      <div class="content-container">
                
             </div>
       
        <h1 class="left-align"><span class="accent-text" style="font-size:34px;">Any Questions?</span></h1>

        
       <style>
        h1.accent-text{
        font-size:45px;
         text-align: left;
    }
</style>
         

<style>
    
    /* CSS Styles */
   

    .form-container {
        position: relative;
        right: 10%;
        transform: translateX(-50%);
    }

    .form-container {
        position: relative;
        top: 50%;
        transform: translateX(-50%);
    }
    
    body {
        font-family: Arial, sans-serif;
        font-size: 16px;
        margin: 0;
        background: #fff;
        color: #000;
    }

    .form-container {
        width: 100%;
        max-width: 650px;
        margin: 0 auto;
        padding: 10px;
        border-radius: 10px;
        color: #fff;
        background: #9ACD32;
    }

    .input-row {
        margin-bottom: 10px;
    }

    .input-row label {
        display: block;
        margin-bottom: 3px;
    }

    .input-row input,
    .input-row textarea {
        width: 70%;
        padding: 8px;
        border-radius: 3px;
        outline: 0;
        margin-bottom: 3px;
        font-size: 15px;
        font-family: Arial, sans-serif;
    }

    .input-row textarea {
        height: 100px;
    }

    .input-row input[type="submit"] {
        width: 100px;
        display: block;
        margin: 0 auto;
        text-align: center;
        color: #fff;
        cursor: pointer;
        background: #002f3a;
    }
   


    .success {
        background: #9fd2a1;
        padding: 5px 10px;
        text-align: center;
        color: #326b07;
        border-radius: 3px;
        font-size: 14px;
        margin-top: 10px;
    }
 
    /* Align content to the left */
            .contact-info {
            position: relative;
            left: 640px; 
            top: 80px;
            transform: translateY(-50%); 
            color: black;
            background: #fff;
            padding: 10px
           
        }

         /* Align content to the top */
         .website-creators {
            position: absolute;
            top:  -308px;
            left: 120px;
            transform: translateY(-50%); 
            color: black;
            background: #fff;
            padding: 10px;
           
        }
        



</style>
</head>
<body>


<div class="form-container">
    

     <form action="" method="post">
       
    <label for="user_email">Your Name:</label><br>
    <input type="email" id="user_email" name="user_email" placeholder="Your Name" style="width: 300px; height: 50px;" require><br>

    <label for="user_email">Your Email:</label><br>
    <input type="email" id="user_email" name="user_email" placeholder="Your email" style="width: 300px; height: 50px;" require><br>

    <label for="user_email">Your Phone:</label><br>
    <input type="email" id="user_email" name="user_email" placeholder="Your phone" style="width: 300px; height: 50px;" require><br>



    <label for="message">Message:</label><br>
    <textarea id="message" name="message" rows="5" cols="50" placeholder="Enter your message" require></textarea><br>

    <input type="submit" value="Submit" name="submit">
    <input type="button" value="Cancel" onclick="window.location.href='index.php'">
</form>
<style>


</style>
    

        <?php if(isset($successMessage)): ?>
            <div class="success">
                <strong><?php echo $successMessage; ?></strong>
            </div>

        <?php endif; ?>

        <?php if(isset($errorMessage)): ?>
            <div class="error">
                <strong><?php echo $errorMessage; ?></strong>
            </div>
        <?php endif; ?>
    </form>

    
    
     
    <div class="contact-info">
    <p>Please contact <strong>Siva Jasthi</strong></p>
        <a href="mailto: Siva.Jasthi@gmail.com"> Siva.Jasthi@gmail.com</a>
        <p>651.276.4671</p>
        <br>
        
        <div class="website-creators">
            <h1>Meet The Team</h1>
        <h2>Website Creators</h2>
        
        <p>Learn and Help 1.0 development team:</p>
        <p><b>Daniel Duea</b>: DanielDuea@gmail.com</p>
        <p><b>Luis Duran-Enriquez</b>: duranluis320@gmail.com</p>
        <p><b>Michael Olson</b>: michaelolson01@gmail.com</p>
        <p><b>William Vicic</b>: william.vicic@gmail.com</p>
        <p><b>Seth Arndt</b>: 17seth.arndt@gmail.com</p>
        <p><b>Robert LaPrise</b>: lapriserobert1@gmail.com</p>

        </section>
    <section class="about-me2">
      <div class="container22">
        <div class="info-box">

          <p><a href="http://localhost/learnandhelp.php">Learn and Help PDF</a></p>

         
      </div>
    </section>
  </body>
</html>
