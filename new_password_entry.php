<?php
// Added to report errors encountered
error_reporting(E_ALL);
ini_set('display_errors', 1);

$token = $_GET["token"];

$token_hash = hash("sha1", $token);

//Establish connection to database
$mysqli = require __DIR__ . "/database_connection.php";

$sql = "SELECT email FROM users WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();
$reset_token = $result->fetch_assoc();

if ($reset_token === null) {
    die("Token not found or expired");
}

$email = $reset_token['email'];

// Close the database connection
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>New Password Entry</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
</head>

<body>
    <?php include 'show-navbar.php'; ?>
    <?php show_navbar(); ?>
    <header class="inverse">
        <div class="container">
            <h1><span class="accent-text">Reset your password</span></h1>
        </div>
    </header>
    <br>

    <form method="post" action="process-reset-password.php">

        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <label for="password">New password</label>
        <input type="password" id="password" name="password" style="width: 400px" required>

        <label for="password_confirmation">Repeat password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" style="width: 400px" required>

        <button>Submit</button>
    </form>
</body>

</html>