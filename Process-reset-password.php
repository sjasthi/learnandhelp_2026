<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

//Establish connection to database
$mysqli = require __DIR__ . "/database_connection.php";

// Retrieve form data
$password = $_POST["password"];
$password_confirmation = $_POST["password_confirmation"];
$token = $_POST["token"]; // Assuming you also have a hidden input for the token in your form


// Form Validation
if (empty($password) || empty($password_confirmation)) {
    die("Password fields cannot be empty");
}

if ($password !== $password_confirmation) {
    die("Passwords do not match");
}

if (strlen($password) < 8) {
    die("Password must be at least 8 characters long");
}

if (!preg_match("/[a-zA-Z]/", $password)) {
    die("Password must contain at least one letter");
}

if (!preg_match("/[0-9]/", $password)) {
    die("Password must contain at least one number");
}

// Token Verification
if (empty($token)) {
    die("Token is empty or not received");
}

$token_hash = hash("sha1", $token);

$sql = "SELECT email FROM users WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();
$reset_token = $result->fetch_assoc();

if (!$reset_token) {
    die("Invalid or expired token");
}

$email = $reset_token['email'];

// Password Hashing
$hash = sha1($password);

// Updating the User's Password
$sql = "UPDATE users SET hash = ? WHERE email = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ss", $hash, $email);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    header("Location: password_change_confirmation.php");
    exit;
} else {
    echo "Password update failed. No rows affected.";
}

// Clean up: delete the used reset token
$sql = "UPDATE users SET reset_token_hash = NULL, reset_token_expires_at = NULL WHERE email = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();

// Debugging output
//echo "<br>";
//echo "Debugging output:<br>";
//echo "SQL query: $sql<br>";
//echo "Hashed password: $hash<br>";
//echo "Email: $email<br>";

//echo "Password updated. You can now login.";

//Close the database connection if anything else failed above
$mysqli->close();
