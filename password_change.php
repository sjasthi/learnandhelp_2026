<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sanitize user input
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if ($email === false) {
    echo "Invalid email address.";
    exit;
}

//Establish connection to database
$mysqli = require __DIR__ . "/database_connection.php";

// Check if the email exists in the database
$sql_check_email = "SELECT COUNT(*) FROM users WHERE email = ?";
$stmt_check_email = $mysqli->prepare($sql_check_email);

if (!$stmt_check_email) {
    echo "Failed to prepare statement for email check.";
    exit;
}

$stmt_check_email->bind_param("s", $email);
$stmt_check_email->execute();

$stmt_check_email->bind_result($email_count);
$stmt_check_email->fetch();
$stmt_check_email->close();

if ($email_count === 0) {
    echo "Email address not found in our records.";
    exit;
}

// Proceed with generating the reset token and sending the reset email
$token = bin2hex(random_bytes(16));
$token_hash = hash("sha1", $token);

$expiry = date("Y-m-d H:i:s", time() + 60 * 30); // Token expires in 30 minutes

// Update the users table with reset token and expiry
$sql_insert_token = "UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?";
$stmt_insert_token = $mysqli->prepare($sql_insert_token);

if (!$stmt_insert_token) {
    echo "Failed to prepare statement for token insertion.";
    exit;
}

$stmt_insert_token->bind_param("sss", $token_hash, $expiry, $email);

if (!$stmt_insert_token->execute()) {
    echo "Failed to update reset token into the database.";
    exit;
}

if ($stmt_insert_token->affected_rows) {
    // Include mailer script
    require __DIR__ . "/sendemail.php";

    //Execute if running on localhost (local instance)
    if (DATABASE_USER === 'root') {

        // Send password reset email
        $subject = "Password Reset";
        $message = <<<END
    Click <a href="http://localhost/learnandhelp/new_password_entry.php?token=$token">here</a> 
    to reset your password.
    END;
    } else {  // code is executing on the remote server
        // Send password reset email
        $subject = "Password Reset";
        $message = <<<END
    Click <a href="https://learnandhelp.jasthi.com/new_password_entry.php?token=$token">here</a> 
    to reset your password.
    END;
    }

    if (sendEmail($email, $subject, $message)) {
        // Close the database connection after all operations are completed
        $mysqli->close();
        header("location:password_reset_notification.php");
        exit;
    } else {
        echo "Failed to send password reset email.";
    }
} else {
    echo "Failed to update reset token into the database.";
}

//Close the database connection if anything else failed above
$mysqli->close();
