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

// Multi-recipient email for progress reports.
// $toEmails — array of TO addresses; $ccEmail — single CC (admin) or null.
function sendProgressReport(array $toEmails, $ccEmail, $subject, $htmlBody) {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'mekics499project24@gmail.com';
    $mail->Password   = 'fwlphiafqwbzkubj';
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;
    $mail->setFrom('mekics499project24@gmail.com', 'Learn and Help');
    foreach ($toEmails as $addr) {
        if (!empty(trim($addr))) { $mail->addAddress(trim($addr)); }
    }
    if ($ccEmail && !empty(trim($ccEmail))) { $mail->addCC(trim($ccEmail)); }
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $htmlBody;
    $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
    try {
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
