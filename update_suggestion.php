<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    http_response_code(403);
    die('Forbidden');
}

require 'db_configuration.php';
$db = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($db->connect_error) { die('Connection failed: ' . $db->connect_error); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: admin_review_suggestions.php');
    exit;
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name                 = trim($_POST['name'] ?? '');
    $contact_name         = trim($_POST['contact_name'] ?? '');
    $contact_phone        = trim($_POST['contact_phone'] ?? '');
    $commitment_statement = trim($_POST['commitment_statement'] ?? '');

    if (empty($name)) {
        $message = 'School name is required.';
        $messageType = 'error';
    } else {
        $stmt = $db->prepare(
            "UPDATE schools SET name=?, contact_name=?, contact_phone=?, commitment_statement=?
             WHERE id=? AND status='Proposed'"
        );
        $stmt->bind_param('ssssi', $name, $contact_name, $contact_phone, $commitment_statement, $id);
        if ($stmt->execute()) {
            $stmt->close();
            $db->close();
            header('Location: admin_review_suggestions.php');
            exit;
        } else {
            $message = 'Error saving changes. Please try again.';
            $messageType = 'error';
        }
        $stmt->close();
    }
}

// Load current values
$stmt = $db->prepare("SELECT name, contact_name, contact_phone, commitment_statement FROM schools WHERE id=? AND status='Proposed'");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    header('Location: admin_review_suggestions.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Update Suggestion | Admin</title>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
  <style>
    :root { --accent: #99D930; }
    body { margin: 0; font-family: 'Montserrat', sans-serif; background: #f8f8f8; color: #252525; }
    .intro-banner { background: #1a1a1a; color: #fff; text-align: center; padding: 24px 20px 20px; }
    .intro-banner h1 { font-family: 'Montserrat', sans-serif; font-size: 3rem; font-weight: 900; margin: 0; }
    .accent-text { color: var(--accent); }
    .form-container { max-width: 800px; margin: 60px auto; padding: 0 20px; }
    .form-card { background: #fff; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.08); padding: 40px; }
    .form-group { margin-bottom: 24px; }
    .form-group label { display: block; font-weight: 700; margin-bottom: 8px; font-size: 1.1em; }
    .form-group input, .form-group textarea {
      width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 8px;
      font-size: 1em; font-family: 'Montserrat', sans-serif; box-sizing: border-box; transition: border-color 0.2s;
    }
    .form-group input:focus, .form-group textarea:focus { outline: none; border-color: var(--accent); }
    .form-group textarea { resize: vertical; min-height: 120px; }
    .button-group { display: flex; gap: 16px; justify-content: center; margin-top: 40px; }
    .btn { padding: 12px 32px; border: none; border-radius: 8px; font-size: 1.1em; font-weight: 700;
           font-family: 'Montserrat', sans-serif; cursor: pointer; transition: all 0.2s; }
    .btn-primary { background: var(--accent); color: #252525; }
    .btn-primary:hover { background: #8cc428; transform: translateY(-2px); }
    .btn-secondary { background: #f0f0f0; color: #252525; }
    .btn-secondary:hover { background: #e0e0e0; transform: translateY(-2px); }
    .message { padding: 16px; border-radius: 8px; margin-bottom: 24px; text-align: center; font-weight: 600; }
    .message.error { background: #ffe6e6; color: #d32f2f; border: 1px solid #ffcdd2; }
  </style>
</head>
<body>
<?php include 'show-navbar.php'; show_navbar(); ?>

<section class="intro-banner">
  <h1>Update <span class="accent-text">Suggestion</span></h1>
</section>

<div class="form-container">
  <div class="form-card">
    <?php if ($message): ?>
      <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="name">School Name *</label>
        <input type="text" id="name" name="name" required
               value="<?= htmlspecialchars($row['name']) ?>">
      </div>
      <div class="form-group">
        <label for="contact_name">Contact Name</label>
        <input type="text" id="contact_name" name="contact_name"
               value="<?= htmlspecialchars($row['contact_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="contact_phone">Contact Phone</label>
        <input type="tel" id="contact_phone" name="contact_phone"
               value="<?= htmlspecialchars($row['contact_phone'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="commitment_statement">Commitment Statement</label>
        <textarea id="commitment_statement" name="commitment_statement"><?= htmlspecialchars($row['commitment_statement'] ?? '') ?></textarea>
      </div>
      <div class="button-group">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="admin_review_suggestions.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php $db->close(); include 'footer.php'; ?>
</body>
</html>
