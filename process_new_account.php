<?php
require 'db_configuration.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper for 400s
function bad_request($msg) {
    http_response_code(400);
    echo "<h2>" . htmlspecialchars($msg) . "</h2>";
    echo "<a href='create_account.php'>Go back to create account</a>";
    exit;
}

// Enable strict MySQLi errors during debugging (remove in prod)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Normalize & validate phone (10 digits, ignoring spaces and hyphens)
if (!isset($_POST['phone'])) {
    bad_request("Phone is required.");
}
$phone = preg_replace('/[\s-]/', '', $_POST['phone']);
if (!preg_match('/^\d{10}$/', $phone)) {
    bad_request("Invalid phone number. Please enter a 10-digit number.");
}

// Required fields check
foreach (['firstname','lastname','usermail','password'] as $f) {
    if (!isset($_POST[$f]) || $_POST[$f] === '') {
        bad_request("All fields are required.");
    }
}

$firstname = trim($_POST['firstname']);
$lastname  = trim($_POST['lastname']);
$usermail  = trim($_POST['usermail']);
$password  = $_POST['password']; // don’t trim passwords

// Basic server-side validation
if (!filter_var($usermail, FILTER_VALIDATE_EMAIL)) {
    bad_request("Please enter a valid email address.");
}
if (strlen($password) < 8) {
    bad_request("Password must be at least 8 characters.");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
    $conn->set_charset('utf8mb4');

    // 1) Check if email already exists
    $stmt = $conn->prepare("SELECT User_Id FROM users WHERE Email = ?");
    if (!$stmt) { throw new Exception("Prepare failed (select): " . $conn->error); }
    $stmt->bind_param("s", $usermail);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        $conn->close();
        bad_request("User already exists with this email address.");
    }
    $stmt->close();

    // 2) Insert user (NOW WITH Phone)
    // Assumes your schema column is exactly named `Phone` (VARCHAR). Adjust if needed.
    $sql = "INSERT INTO users 
            (First_Name, Last_Name, Email, Phone, Hash, Active, Role, Modified_Time, Created_Time)
            VALUES (?, ?, ?, ?, ?, 'yes', 'student', NOW(), NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { throw new Exception("Prepare failed (insert): " . $conn->error); }

    // sssss => First_Name, Last_Name, Email, Phone, Hash
    $stmt->bind_param("sssss", $firstname, $lastname, $usermail, $phone, $hash);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new Exception("Insert affected {$stmt->affected_rows} rows, expected 1.");
    }

    $newUserId = $conn->insert_id;

    $stmt->close();
    $conn->close();

    // 3) Set session and redirect
    $_SESSION['email'] = $usermail;
    $_SESSION['first_name'] = $firstname;
    $_SESSION['last_name'] = $lastname; // fixed bug: was $firstname
    $_SESSION['phone'] = $phone; // fixed bug: was $firstname
    $_SESSION['role'] = 'student';
    $_SESSION['user_id'] = $newUserId;

    $_SESSION['flash_success'] = "Account is successfully created.";

    header('Location: index.php');
    exit;

} catch (Throwable $e) {
    // Friendly message + specific error for you (hide details in prod)
    http_response_code(500);
    echo "<h2>Error creating account.</h2>";
    echo "<p>Please try again or contact support.</p>";
    echo "<details><summary>Details (debug)</summary><pre>" . htmlspecialchars($e->getMessage()) . "</pre></details>";
    echo "<a href='create_account.php'>Go back to create account</a>";
    if (isset($stmt) && $stmt instanceof mysqli_stmt) { $stmt->close(); }
    if (isset($conn) && $conn instanceof mysqli) { $conn->close(); }
    exit;
}
