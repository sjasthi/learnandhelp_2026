<?php
// Print errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

// Block unauthorized users from accessing the page
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] != 'admin') {
        http_response_code(403);
        die('Forbidden');
    }
} else {
    http_response_code(403);
    die('Forbidden');
}

require 'db_configuration.php';

// Get organization ID
$orgId = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$orgId) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid organization ID.'];
    header('Location: admin_non_profits.php');
    exit();
}

// Create connection
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// First, get the organization name for the success message
$stmt = $conn->prepare("SELECT org_name FROM recommended_orgs WHERE org_id = ?");
$stmt->bind_param("i", $orgId);
$stmt->execute();
$result = $stmt->get_result();
$org = $result->fetch_assoc();
$stmt->close();

if (!$org) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Organization not found.'];
    header('Location: admin_non_profits.php');
    exit();
}

// Delete the organization
$stmt = $conn->prepare("DELETE FROM recommended_orgs WHERE org_id = ?");
$stmt->bind_param("i", $orgId);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Organization "' . htmlspecialchars($org['org_name']) . '" deleted successfully.'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Organization not found or already deleted.'];
    }
} else {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error deleting organization. Please try again.'];
}

$stmt->close();
$conn->close();

// Redirect back to the non-profits list
header('Location: admin_non_profits.php');
exit();
?>