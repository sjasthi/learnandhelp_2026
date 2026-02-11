
<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

  $status = session_status();
  if ($status == PHP_SESSION_NONE) {
    session_start();
  }

    if(isset($_POST["send"])) {
        $userName = $_POST["userName"];
        $userEmail = $_POST["userEmail"];
        $userPhone = $_POST["userPhone"];
        $userMessage = $_POST["userMessage"];

        // PHPMailer configuration
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Your SMTP host
    $mail->SMTPAuth = true;
    $mail->Username = 'mekics499project24@gmail.com'; // Your SMTP username
    $mail->Password = 'fwlphiafqwbzkubj'; // Your SMTP password
    $mail->SMTPSecure = 'ssl'; // TLS or SSL
    $mail->Port = 465; // TCP port to connect to

    $mail->setFrom($userEmail, $userName);
    $mail->addAddress("mekics499project24@gmail.com"); // Recipient email

    $mail->isHTML(false);
    $mail->Subject = "Contact Form Submission";
    $mail->Body = "Name: $userName\r\nEmail: $userEmail\r\nPhone: $userPhone\r\nMessage: $userMessage";

    try {
        // Attempt to send email
        $mail->send();
        $successMessage = "Your information has been received successfully.";
    } catch (Exception $e) {
        $errorMessage = "Failed to send the email. Please try again later.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form</title>
    
     '<a href="form-send-email.php"></a>';
    <style>
        
        /* CSS Styles */
        
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
            padding: 20px;
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
            width: 100%;
            padding: 10px;
            border-radius: 3px;
            outline: 0;
            margin-bottom: 3px;
            font-size: 18px;
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
    </style>
</head>
<body>
    

    <div class="form-container">
        <form method="POST" name="emailContact">
            <div class="input-row">
                <label>Name <em>*</em></label>
                <input type="text" name="userName" required>
            </div>

            <div class="input-row">
                <label>Email <em>*</em></label>
                <input type="email" name="userEmail" required>
            </div>

            <div class="input-row">
                <label>Phone <em>*</em></label>
                <input type="tel" name="userPhone" required>
            </div>

            <div class="input-row">
                <label>Message <em>*</em></label>
                <textarea name="userMessage" required></textarea>
            </div>

            <div class="input-row">
                <input type="submit" name="send" value="Submit">
            </div>

        

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
    </div>
</body>
</html>
