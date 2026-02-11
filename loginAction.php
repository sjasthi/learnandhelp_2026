<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_configuration.php';

// Enable mysqli errors during debugging (remove in prod)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function fail($msg) {
    $_SESSION['login_error'] = $msg;
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email = trim($_POST['usermail'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    fail('Please fill in all fields');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Please enter a valid email address.');
}

try {
    $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
    $conn->set_charset('utf8mb4');

    // Fetch user (only active accounts)
    $stmt = $conn->prepare("
        SELECT User_Id, First_Name, Last_Name, Email, Phone, Hash, Role
        FROM users
        WHERE Email = ? AND Active = 'yes'
        LIMIT 1
    ");
    $stmt->bind_param('s', $email);
    $stmt->execute();

    // Use result binding
    $stmt->store_result();
    $stmt->bind_result($user_id, $first_name, $last_name, $user_email, $phone, $hash, $role);

    if (!$stmt->fetch()) {
        // No such active user
        $stmt->close();
        $conn->close();
        fail('Invalid user name or password.');
    }
    $stmt->close();

    $authenticated = false;

    // Primary: verify modern hash
    if ($hash && password_get_info($hash)['algo'] !== 0) {
        // Looks like bcrypt/argon2
        $authenticated = password_verify($password, $hash);

        // Optional: rehash to current cost if needed
        if ($authenticated && password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET Hash = ?, Modified_Time = NOW() WHERE User_Id = ?");
            $upd->bind_param('si', $newHash, $user_id);
            $upd->execute();
            $upd->close();
        }
    } else {
        // Legacy support: DB may have stored SHA1 previously.
        // If it's a 40-char hex, try migrating.
        if (preg_match('/^[a-f0-9]{40}$/i', (string)$hash) && hash_equals($hash, sha1($password))) {
            $authenticated = true;
            // Migrate to PASSWORD_DEFAULT
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET Hash = ?, Modified_Time = NOW() WHERE User_Id = ?");
            $upd->bind_param('si', $newHash, $user_id);
            $upd->execute();
            $upd->close();
        }
    }

    if (!$authenticated) {
        $conn->close();
        fail('Invalid user name or password.');
    }

    // Success: set session (use keys your app expects)
    $_SESSION['user_id']    = (int)$user_id;      
    $_SESSION['first_name'] = $first_name;
    $_SESSION['last_name'] = $last_name;
    $_SESSION['email']      = $user_email;
    $_SESSION['phone']      = $phone;
    $_SESSION['role']       = $role ?? 'student';

    // Optional flash
    $_SESSION['flash_success'] = "Welcome back, " . htmlspecialchars($first_name) . "!";

    $conn->close();

    // Redirect wherever you want after login
    header('Location: index.php'); // or 'account.php'
    exit;

} catch (Throwable $e) {
    // Hide details in production
    $_SESSION['login_error'] = 'Unexpected error during login.';
    // For debugging ONLY, you could append $e->getMessage()
    // $_SESSION['login_error'] .= ' ' . $e->getMessage();
    header('Location: login.php');
    exit;
}
