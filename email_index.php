<!DOCTYPE html>
<html lang="en" dir="ltr">
<?php
$local = true;
$path = "http://" . $_SERVER["DOCUMENT_ROOT"] . "learnandhelp/email_index/";

if ($local == false) {
}
?>

<head>
              <meta charset="utf-8">
              <title>Send Email</title>
          </head>
          <body>
              <form class="" action="sendemail.php" method="post">
              Email <input type="email" name="email" value=""> <br>
              Subject <input type="text" name="subject" value=""> <br>
              Message <input type="text" name="message" value=""> <br>
              <button type="submit" name="send">Send</button>
              </form>
          </body>
</html>
        