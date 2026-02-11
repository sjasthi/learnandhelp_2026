<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'determine_paths.php';
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

if (isset($_POST['submit'])) {
    $name = $_POST['user_name'];
    $phone = $_POST['user_phone'];
    $message = $_POST['message'];
    $visitorEmail = $_POST['user_email'];

    $conn = require_once __DIR__ . "/database_connection.php";

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
        $mail->setFrom('contact_us_message@learnandhelp.com', 'Learn and Help Contact us message');
        $mail->Username = 'mekics499project24@gmail.com';
        $mail->Password = 'fwlphiafqwbzkubj';
        foreach ($adminEmails as $adminEmail) {
            $mail->addAddress($adminEmail);
        }
        $mail->addReplyTo($visitorEmail);
        $mail->Subject = 'Contact Us Message';
        $mail->Body = "Name: $name\nEmail: $visitorEmail\nPhone: $phone\n\nMessage:\n$message";
        $mail->send();
        echo "<script>alert('Sent Successfully');document.location.href = 'contact_us.php'</script>";
        exit();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Contact Us | Learn and Help</title>
    <meta charset="UTF-8">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

<style>
/* Adopted from classes.php */
:root { --accent:#99D930; }
.accent-text { color: var(--accent); }
.page-title{
      font-family:'Montserrat',sans-serif;
      font-size:3em;          /* same size Blog uses */
      font-weight:700;        /* lighter than 900 */
      text-align:center;
      margin:60px 0 30px;
      color:#252525;
    }
.card{
  background:#fff;
  border-radius:18px;
  box-shadow:0 4px 24px rgba(0,0,0,.08);
  padding:28px;
  max-width:900px;
  margin: 0 auto 40px;
}


        body {
            font-family: 'Montserrat', 'Roboto', sans-serif;
            background: #f8f8f8;
            margin: 0;
            color: #252525;
        }
        
        .page-title {
            font-family: 'Montserrat',sans-serif;
            font-size: 3em;
            font-weight: 700;
            text-align: center;
            margin: 60px 0 30px;
            color: #252525;
        }
        .container {
            max-width: 900px;
            margin: 0 auto 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.09);
            padding: 40px 32px;
        }
        .form-container {
            background: #f8f8f8;
            padding: 24px 32px;
            border-radius: 10px;
            margin-bottom: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .form-container label {
            font-weight: bold;
        }
        .form-container input,
        .form-container textarea {
            width: 100%;
            margin-bottom: 16px;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #bbb;
            font-size: 1em;
            font-family: 'Roboto', sans-serif;
        }
        .form-container input[type="submit"], .form-container input[type="button"] {
            width: 120px;
            background: #99D930;
            color: #252525;
            border: none;
            cursor: pointer;
            margin-right: 10px;
            font-weight: bold;
            font-family: 'Montserrat',sans-serif;
            font-size: 1em;
            transition: background 0.2s, color 0.2s;
        }
        .form-container input[type="submit"]:hover, .form-container input[type="button"]:hover {
            background: #002f3a;
            color: #fff;
        }
        @media (max-width: 700px) {
            .container { padding: 10px; }
            .form-container { padding: 10px; }
            .page-title { font-size: 2em; margin: 30px 0 18px; }
        }
    </style>
</head>
<body>
<?php include 'show-navbar.php'; show_navbar(); ?>

<h1 class="page-title">Contact Us</h1>
<h5> This form is not functional yet. Please send your message to Siva.Jasthi@gmail.com. Thank you. </h5>

 
  
<div class="container">
    <div class="card form-container">
        <form action="" method="post">
            <label for="user_name">Your Name:</label>
            <input type="text" id="user_name" name="user_name" required>

            <label for="user_email">Your Email:</label>
            <input type="email" id="user_email" name="user_email">

            <label for="user_phone">Your Phone:</label>
            <input type="tel" id="user_phone" name="user_phone" required>

            <label for="message">Message:</label>
            <textarea id="message" name="message" rows="5" required></textarea>

            <input type="submit" value="Submit" name="submit">
            <input type="button" value="Cancel" onclick="window.location.href='index.php'">
        </form>
    </div>
    <div style="text-align:center; color:#333;">
        <p>Have a question? Feel free to use this form to reach us - We'll respond as soon as possible!</p>
        <br>
        <p>email <a href="mailto:Siva.Jasthi@gmail.com">Siva.Jasthi@gmail.com</a> or call 651.276.4671</p>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
