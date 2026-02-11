<?php

$local = true;
$path = $_SERVER["DOCUMENT_ROOT"] . "C:/xampp/htdocs/learnandhelp/send_reset_email/";
//$path = "/ICS 499 Project/FP3/sendemail";
// $path = "http://" . $_SERVER['HTTP_HOST'] . "/ICS_Classes/ICS499/";
if ($local == false) {
}



$email = $_POST["email"];                             // extracting the value of email by using POST

$token = bin2hex(random_bytes(16));                    //genertate random token value

$token_hash = hash("sha256", $token);                   // holds the resulting hash value and extra secure

$expiry = date("Y-m-d H:i:s", time() + 60 * 30);          //generate timestamp for future date and time

$mysqli = require __DIR__ . "/database_connection.php";     //directory to the database_connection

//sql statement using update columns in user table
$sql = "UPDATE user                                         
        SET reset_token_hash = ?,
            reset_token_expires_at = ?
        WHERE email = ?";

$stmt = $mysqli->prepare($sql);                              //prepare statement for execution

$stmt->bind_param("sss", $token_hash, $expiry, $email);     //binding parameters before execution 

$stmt->execute();                                          //execute prepared statement

if ($mysqli->affected_rows) {

    $mail = require __DIR__ . "/email.php";              //directory to email.php

    $mail->setFrom('mekics499project24@gmail.com', 'no-reply@learnandhelp.com');       // sending email using mail library (admin email add)
    $mail->addAddress($email);
    $mail->Subject = "Password Reset";                    //subject password rest
    $mail->Body = <<<END

    Click <a href="http://example.com/reset-password.php?token=$token">here</a>   //add link
    to reset your password.

    END;

    try {

        $mail->send();

    } catch (Exception $e) {

        echo "Message could not be sent. Mailer error: {$mail->ErrorInfo}";

    }

}

echo "Message sent, please check your inbox.";                   //print message
