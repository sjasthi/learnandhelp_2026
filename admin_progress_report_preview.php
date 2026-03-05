<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); die('Forbidden');
}

if (empty($_SESSION['progress_report']) || !isset($_GET['student_index'])) {
    header('Location: admin_progress_report.php'); exit;
}

$data        = $_SESSION['progress_report'];
$i           = (int)$_GET['student_index'];
$students    = $data['students'];

if (!isset($students[$i])) {
    header('Location: admin_progress_report.php'); exit;
}

$s           = $students[$i];
$courseName  = $data['courseName'];
$assignments = $data['assignments'];
$emailMode   = $data['emailMode'];

// Build assignment rows
$rows = '';
foreach ($assignments as $a) {
    $val   = trim($s['scores'][$a['index']] ?? '');
    $isBad = ($val === '' || $val === '0');
    $bg    = $isBad ? 'background:#ffe6e6;' : '';
    $fw    = $isBad ? 'font-weight:700;color:#d32f2f;' : '';
    $display = $val === '' ? '<em style="color:#d32f2f;">Missing</em>' : htmlspecialchars($val);
    $rows .= '<tr>
        <td style="padding:9px 14px;border-bottom:1px solid #e0e0e0;' . $bg . $fw . '">' . htmlspecialchars($a['name']) . '</td>
        <td style="padding:9px 14px;border-bottom:1px solid #e0e0e0;' . $bg . '">' . htmlspecialchars($a['date']) . '</td>
        <td style="padding:9px 14px;border-bottom:1px solid #e0e0e0;text-align:center;' . $bg . '">' . htmlspecialchars($a['points']) . '</td>
        <td style="padding:9px 14px;border-bottom:1px solid #e0e0e0;text-align:center;' . $bg . $fw . '">' . $display . '</td>
    </tr>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Preview: <?= htmlspecialchars($s['fullName']) ?> — <?= htmlspecialchars($courseName) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&display=swap" rel="stylesheet">
  <style>
    body { margin: 0; background: #e0e0e0; font-family: Arial, sans-serif; }

    /* Admin preview banner */
    .preview-banner {
      background: #1a1a1a;
      color: #fff;
      text-align: center;
      padding: 12px 20px;
      font-family: 'Montserrat', sans-serif;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 16px;
      flex-wrap: wrap;
    }
    .preview-banner strong { color: #99D930; }
    .mode-badge { padding: 3px 12px; border-radius: 20px; font-weight: 700; font-size: 0.8rem; }
    .mode-dev  { background: #e65100; color: #fff; }
    .mode-prod { background: #2e7d32; color: #fff; }
    .close-btn { background: #99D930; color: #1a1a1a; border: none; padding: 6px 18px; border-radius: 6px; font-weight: 700; font-family: 'Montserrat', sans-serif; cursor: pointer; font-size: 0.85rem; }
    .close-btn:hover { background: #8cc428; }

    /* Email preview wrapper */
    .email-wrap { padding: 30px 20px; }
  </style>
</head>
<body>

<div class="preview-banner">
  <span>&#128065; <strong>Email Preview</strong> — This is how the report will appear in the recipient's inbox</span>
  <span class="mode-badge <?= $emailMode === 'DEV' ? 'mode-dev' : 'mode-prod' ?>">
    <?= htmlspecialchars($emailMode) ?> MODE
  </span>
  <button class="close-btn" onclick="window.close()">✕ Close Preview</button>
</div>

<!-- Exact email HTML rendered in browser -->
<div class="email-wrap">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#e0e0e0;">
  <tr><td align="center" style="padding:20px 0;">
    <table width="800" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.15);">

      <!-- Header -->
      <tr><td style="background:#1a1a1a;padding:28px 36px;">
        <span style="font-size:1.6rem;font-weight:900;color:#99D930;font-family:Arial,sans-serif;letter-spacing:1px;">Learn</span>
        <span style="font-size:1.6rem;font-weight:900;color:#fff;font-family:Arial,sans-serif;"> and </span>
        <span style="font-size:1.6rem;font-weight:900;color:#99D930;font-family:Arial,sans-serif;">Help</span>
        <span style="font-size:0.95rem;color:#aaa;font-family:Arial,sans-serif;margin-left:14px;">Progress Report</span>
      </td></tr>

      <!-- Body -->
      <tr><td style="padding:28px 36px;font-family:Arial,sans-serif;">
        <?php if ($emailMode === 'DEV'): ?>
        <p style="background:#fff3e0;color:#e65100;padding:10px 16px;border-radius:6px;font-weight:700;margin-bottom:20px;">
          ⚠ DEV MODE — This is a test email. In production this report would be sent to the student's parents.
        </p>
        <?php endif; ?>

        <p style="margin:0 0 4px;color:#555;font-size:0.9rem;">Course</p>
        <p style="margin:0 0 20px;font-size:1.25rem;font-weight:700;color:#1a1a1a;"><?= htmlspecialchars($courseName) ?></p>

        <p style="margin:0 0 4px;color:#555;font-size:0.9rem;">Student</p>
        <p style="margin:0 0 24px;font-size:1.1rem;font-weight:700;color:#1a1a1a;"><?= htmlspecialchars($s['fullName']) ?></p>

        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:0.9rem;">
          <thead>
            <tr style="background:#1a1a1a;color:#fff;">
              <th style="padding:10px 14px;text-align:left;">Assignment</th>
              <th style="padding:10px 14px;text-align:left;">Due Date</th>
              <th style="padding:10px 14px;text-align:center;">Points</th>
              <th style="padding:10px 14px;text-align:center;">Score</th>
            </tr>
          </thead>
          <tbody><?= $rows ?></tbody>
        </table>

        <p style="margin-top:28px;font-size:0.85rem;color:#888;">
          This report was generated by Learn and Help. Please do not reply to this email.
        </p>
      </td></tr>

      <!-- Footer -->
      <tr><td style="background:#1a1a1a;padding:16px 36px;text-align:center;">
        <span style="color:#99D930;font-weight:700;font-size:0.9rem;font-family:Arial,sans-serif;">learnandhelp.com</span>
      </td></tr>

    </table>
  </td></tr>
</table>
</div>

</body>
</html>
