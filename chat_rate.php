<?php
/**
 * chat_rate.php — Accept thumbs up/down ratings for chat responses.
 * Called via POST JSON: { "log_id": 42, "rating": 1 } (1=up, -1=down)
 */
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$log_id = (int)($input['log_id'] ?? 0);
$rating = (int)($input['rating'] ?? 0);

if ($log_id <= 0 || !in_array($rating, [1, -1])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid input']);
    exit;
}

require_once __DIR__ . '/db_configuration.php';
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB error']);
    exit;
}

// Ensure rating column exists (may be missing on older installs)
$rc = $conn->query("SHOW COLUMNS FROM chat_log LIKE 'rating'");
if ($rc && $rc->num_rows === 0) {
    $conn->query("ALTER TABLE chat_log ADD COLUMN rating TINYINT DEFAULT NULL");
}

$stmt = $conn->prepare("UPDATE chat_log SET rating = ? WHERE id = ?");
$stmt->bind_param("ii", $rating, $log_id);
$ok = $stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['ok' => (bool)$ok]);
