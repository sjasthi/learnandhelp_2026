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
        <h1> <span class="accent-text">Contact Us</span></h1>
    </div>
</header>
<br>
<form action="" method="post">

    <label for="user_email">Your Email:</label><br>
    <input type="email" id="user_email" name="user_email" placeholder="Your email" style="width: 300px; height: 50px;" require><br>

    <label for="message">Message:</label><br>
    <textarea id="message" name="message" rows="5" cols="50" placeholder="Enter your message" require></textarea><br>

    <input type="submit" value="Submit" name="submit">
    <input type="button" value="Cancel" onclick="window.location.href='index.php'">
</form>
</body>
</html>
