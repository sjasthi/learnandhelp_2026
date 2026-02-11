<?php
// account_password_change_process.php (revised redirects)
// - Errors -> my_account_password_change.php
// - Success -> my_account.php
// - No output before headers; exits after header()

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function back_with_error(string $message): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => 'error'];
    header('Location: my_account_password_change.php');
    exit;
}

function go_to_account(string $message): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => 'success'];
    header('Location: my_account.php');
    exit;
}

function db(): mysqli {
    require_once __DIR__ . '/db_configuration.php';
    $conn = @new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
    if ($conn->connect_errno) {
        throw new Exception('DB connection failed: '.$conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function hash_password_legacy(string $plain): string { return sha1($plain); }

// --- Request/CSRF guards ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    back_with_error('Invalid request method.');
}

if (empty($_SESSION['user_id'])) {
    back_with_error('Please login.');
}

// Optional CSRF token (render in form if you enable this)
if (!empty($_SESSION['csrf_token'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], (string)$token)) {
        back_with_error('Security check failed. Please try again.');
    }
}

// --- Validate inputs --------------------------------------------------------
$current = trim((string)($_POST['current_password'] ?? ''));
$new     = trim((string)($_POST['new_password'] ?? ''));
$confirm = trim((string)($_POST['confirm_password'] ?? ''));

if ($current === '' || $new === '' || $confirm === '') {
    back_with_error('Please fill in all required fields.');
}
if ($new !== $confirm) {
    back_with_error('New password and confirmation do not match.');
}
if (strlen($new) < 8 || strlen($new) > 16) {
    back_with_error('Password must be 8-16 characters.');
}
if (hash_equals($current, $new)) {
    back_with_error('New password must be different.');
}

// --- DB work ----------------------------------------------------------------
$userId = (int) $_SESSION['user_id'];

try {
    $conn = db();

    $stmt = $conn->prepare("SELECT Hash FROM users WHERE User_Id = ? LIMIT 1");
    if (!$stmt) { throw new Exception('Prepare failed (select): '.$conn->error); }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        back_with_error('Account not found.');
    }
    
    // check whether the current password matches what is in db
    $stored = (string)$row['Hash'];
    $authenticated = password_verify($current, $stored);
     if (!$authenticated)
     {
        back_with_error('Current password is incorrect.');   
     }
    
    // update the paswwrod now
    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $upd = $conn->prepare("UPDATE users SET Hash = ?, Modified_Time = NOW() WHERE User_Id = ?");
    if (!$upd) { throw new Exception('Prepare failed (update): '.$conn->error); }
    $upd->bind_param('si', $newHash, $userId);
    $upd->execute();
    $upd->close();
    

    // Optional: rotate session id
    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);

    go_to_account('Password updated successfully.');

} catch (Throwable $e) {
    // error_log($e->getMessage());
    back_with_error('Failed to update the password. Please try again.'. $e->getMessage());
}
