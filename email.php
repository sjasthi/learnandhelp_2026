<?php


$local = true;
//$path = $_SERVER["DOCUMENT_ROOT"] . "/ICS_Classes/ICS499 Project/Labs/FP3/email/";
$path = $_SERVER["DOCUMENT_ROOT"] . "C:/xampp/htdocs/learnandhelp/email/";
//$path = "/ICS499_Group_repo/learnandhelp/FP3/email_index";
//$path = "/ICS 499 Project/FP3/sendemail";
// $path = "http://" . $_SERVER['HTTP_HOST'] . "/ICS_Classes/ICS499/Labs/FP3/email_index/";
if ($local == false) {
}

//require 'PHPMailer-master/src/Exception.php';
//require 'PHPMailer-master/src/PHPMailer.php';
//require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;                            //import PHPMailer
use PHPMailer\PHPMailer\SMTP;                                //import smtp class from PHPMailer
use PHPMailer\PHPMailer\Exception;                           // import the Exception class from PHPMailer

require __DIR__ . "PHPMailer-master";                        // directory to PHPMailer folder

$mail = new PHPMailer(true);                                // creating a new object of PHPMailer class

$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // sets the SMTP debugging level in PHPMailer

$mail->isSMTP();                                           //set mailer to use SMTP
$mail->SMTPAuth = true;                                   //to enable SMTP authentication when sending email

$mail->Host = "smtp.gmail.com";                        // set the host name of smtp server that connect to send email
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;    // configuring the encrytpion method and setting the encyption to use startls
$mail->Port = 587;                                     // sets port number that PHPMailer when connecting to smtp
$mail->Username = "mekics499project24@gmail.com";        //user email address
$mail->Password = "fwlp hiaf qwbz kubj";                  //password


$mail->isHtml(true);                                // content of email sent to HTML format

return $mail;                                     // return PHPMailer

