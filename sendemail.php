<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

function sendEmail($recipient, $subject, $message) {
    $mail = new PHPMailer(true);

    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'mekics499project24@gmail.com'; // Your Gmail address
    $mail->Password = 'fwlphiafqwbzkubj'; // Your Gmail password
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    // Sender and recipient
    $mail->setFrom('mekics499project24@gmail.com', 'no-reply@learnandhelp.com');
    $mail->addAddress($recipient);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $message;

    // Send email
    try {
        $mail->send();
        return true; // Email sent successfully
    } catch (Exception $e) {
        return false; // Failed to send email
    }
}
?>