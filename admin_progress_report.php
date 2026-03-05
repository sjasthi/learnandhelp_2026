<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); die('Forbidden');
}

require 'db_configuration.php';
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($conn->connect_error) { die('DB connection failed: ' . $conn->connect_error); }

// Fetch preferences
$emailMode    = 'DEV';
$activeBatch  = '';
$r = $conn->query("SELECT Preference_Name, Value FROM preferences WHERE Preference_Name IN ('Email_Mode','Active Registration')");
while ($row = $r->fetch_assoc()) {
    if ($row['Preference_Name'] === 'Email_Mode')       $emailMode   = $row['Value'];
    if ($row['Preference_Name'] === 'Active Registration') $activeBatch = $row['Value'];
}

// Clear session if user wants to upload a different file
if (isset($_GET['reset'])) {
    unset($_SESSION['progress_report']);
}

// ── Handle mode toggle ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_mode'])) {
    $newMode = $_POST['set_mode'] === 'PROD' ? 'PROD' : 'DEV';
    $conn->query("UPDATE preferences SET Value='$newMode' WHERE Preference_Name='Email_Mode'");
    $emailMode = $newMode;
    // Update session so preview reflects the new mode immediately
    if (isset($_SESSION['progress_report'])) {
        $_SESSION['progress_report']['emailMode'] = $newMode;
    }
    header('Location: admin_progress_report.php');
    exit;
}

$preview   = isset($_SESSION['progress_report']) ? $_SESSION['progress_report'] : null;
$error     = '';
$courseName = '';

// ── POST: parse uploaded CSV ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {

    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed. Please try again.';
    } elseif (strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $error = 'Only CSV files are accepted.';
    } else {
        // Derive course name from filename
        $rawName    = pathinfo($_FILES['csv_file']['name'], PATHINFO_FILENAME);
        $courseName = ucwords(str_replace('_', ' ', preg_replace('/_scores$/i', '', $rawName)));

        // Move to temp location
        $tempDir  = __DIR__ . '/uploads/progress_temp/';
        $tempFile = $tempDir . uniqid('pr_', true) . '.csv';
        move_uploaded_file($_FILES['csv_file']['tmp_name'], $tempFile);

        $handle = fopen($tempFile, 'r');

        // Row 1 — headers
        $headers = fgetcsv($handle);
        // Row 2 — dates
        $datesRow = fgetcsv($handle);
        // Row 3 — points
        $pointsRow = fgetcsv($handle);

        // Build assignment meta (cols 3+)
        $assignments = [];
        for ($i = 3; $i < count($headers); $i++) {
            $name = trim($headers[$i] ?? '');
            if ($name === '') continue;
            $assignments[] = [
                'index'  => $i,
                'name'   => $name,
                'date'   => trim($datesRow[$i] ?? ''),
                'points' => trim($pointsRow[$i] ?? ''),
            ];
        }

        // Check if users table has secondary_contact_email column
        $hasSecondary = false;
        $colCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'secondary_contact_email'");
        if ($colCheck && $colCheck->num_rows > 0) { $hasSecondary = true; }

        // Parse student rows
        $students = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) continue; // skip blank rows
            $lastName  = trim($row[0] ?? '');
            $firstName = trim($row[1] ?? '');
            $email     = strtolower(trim($row[2] ?? ''));
            if ($email === '' || $email === 'email account') continue;

            $fullName = $firstName . ' ' . $lastName;

            // Scores
            $scores = [];
            $missingOrZero = 0;
            foreach ($assignments as $a) {
                $val = trim($row[$a['index']] ?? '');
                $scores[$a['index']] = $val;
                if ($val === '' || $val === '0') $missingOrZero++;
            }

            // Look up registration
            $reg = null;
            $stmt = $conn->prepare(
                "SELECT Sponsor1_Email, Student_Email
                 FROM registrations WHERE LOWER(Student_Email) = ?
                 ORDER BY Reg_Id DESC LIMIT 1"
            );
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) { $reg = $res->fetch_assoc(); }
            $stmt->close();

            // Collect recipient emails
            $recipients = [];
            if ($reg) {
                if (!empty($reg['Sponsor1_Email'])) $recipients[] = $reg['Sponsor1_Email'];
                $studentEmail = $reg['Student_Email'] ?? $email;

                // Check users table for secondary contact email linked to sponsor
                if ($hasSecondary && !empty($reg['Sponsor1_Email'])) {
                    $su = $conn->prepare("SELECT secondary_contact_email FROM users WHERE LOWER(Email) = ? LIMIT 1");
                    $se = strtolower($reg['Sponsor1_Email']);
                    $su->bind_param('s', $se);
                    $su->execute();
                    $ur = $su->get_result()->fetch_assoc();
                    $su->close();
                    if (!empty($ur['secondary_contact_email'])) {
                        $recipients[] = $ur['secondary_contact_email'];
                    }
                }
                $recipients = array_unique(array_filter($recipients));
            } else {
                $studentEmail = $email;
            }

            $students[] = [
                'lastName'      => $lastName,
                'firstName'     => $firstName,
                'email'         => $email,
                'fullName'      => $fullName,
                'studentEmail'  => $studentEmail,
                'matched'       => $reg !== null,
                'recipients'    => $recipients,
                'scores'        => $scores,
                'missingOrZero' => $missingOrZero,
            ];
        }
        fclose($handle);

        // Store in session for send step
        $_SESSION['progress_report'] = [
            'courseName'  => $courseName,
            'assignments' => $assignments,
            'students'    => $students,
            'tempFile'    => $tempFile,
            'emailMode'   => $emailMode,
        ];

        $preview = $_SESSION['progress_report'];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Progress Report | Admin</title>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
  <style>
    :root { --accent: #99D930; }
    body { margin: 0; font-family: 'Montserrat', sans-serif; background: #f8f8f8; color: #252525; overflow-x: hidden; }
    .intro-banner { background: #1a1a1a; color: #fff; text-align: center; padding: 24px 20px 20px; }
    .intro-banner h1 { font-size: 3rem; font-weight: 900; margin: 0; }
    .accent-text { color: var(--accent); }
    .page-wrap { max-width: 1400px; margin: 40px auto; padding: 0 20px; }
    .card { background: #fff; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,.08); padding: 36px; margin-bottom: 30px; }

    /* Mode badge */
    .mode-badge { display: inline-block; padding: 4px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; margin-left: 12px; vertical-align: middle; }
    .mode-dev  { background: #e65100; color: #fff; }
    .mode-prod { background: #2e7d32; color: #fff; }

    /* Mode toggle */
    .mode-toggle { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; flex-wrap: wrap; }
    .mode-toggle > span { font-weight: 700; font-size: 0.95rem; color: #555; }
    .mode-btn { padding: 10px 28px; border: 2px solid #ddd; border-radius: 8px; font-family: 'Montserrat', sans-serif; font-size: 1rem; font-weight: 700; cursor: pointer; background: #f4f4f4; color: #bbb; transition: all 0.2s; opacity: 0.5; }
    .mode-btn.active-dev  { background: #e65100; color: #fff; border-color: #e65100; opacity: 1; box-shadow: 0 3px 10px rgba(230,81,0,.35); }
    .mode-btn.active-prod { background: #2e7d32; color: #fff; border-color: #2e7d32; opacity: 1; box-shadow: 0 3px 10px rgba(46,125,50,.35); }
    .mode-alert { padding: 14px 20px; border-radius: 10px; font-size: 0.95rem; font-weight: 600; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 12px; }
    .mode-alert-dev  { background: #fff3e0; border: 2px solid #e65100; color: #bf360c; }
    .mode-alert-prod { background: #e8f5e9; border: 2px solid #2e7d32; color: #1b5e20; }
    .mode-alert .mode-icon { font-size: 1.4rem; line-height: 1; flex-shrink: 0; }
    .mode-alert .mode-text strong { display: block; font-size: 1rem; margin-bottom: 3px; }

    /* Upload form */
    .upload-label { font-weight: 700; display: block; margin-bottom: 10px; font-size: 1.05rem; }
    input[type=file] { padding: 8px; border: 2px dashed #ccc; border-radius: 8px; width: 100%; box-sizing: border-box; font-family: inherit; }
    .btn { padding: 12px 32px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; font-family: 'Montserrat', sans-serif; cursor: pointer; transition: all 0.2s; }
    .btn-primary { background: var(--accent); color: #252525; margin-top: 16px; }
    .btn-primary:hover { background: #8cc428; transform: translateY(-2px); }
    .btn-send { background: #1a1a1a; color: #fff; padding: 12px 40px; }
    .btn-send:hover { background: #333; transform: translateY(-2px); }

    /* Error */
    .alert-error { background: #ffe6e6; color: #d32f2f; border: 1px solid #ffcdd2; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; font-weight: 600; }

    /* Preview table */
    .preview-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9rem; }
    .preview-table th { background: #1a1a1a; color: #fff; padding: 10px 12px; text-align: left; white-space: nowrap; }
    .preview-table td { padding: 9px 12px; border-bottom: 1px solid #e0e0e0; vertical-align: middle; }
    .preview-table tr:hover td { background: #f8f8f8; }
    .check  { color: #2e7d32; font-weight: 700; }
    .cross  { color: #d32f2f; font-weight: 700; }
    .badge-missing { background: #ffe6e6; color: #d32f2f; border-radius: 10px; padding: 2px 8px; font-size: 0.8rem; font-weight: 700; }
    .email-list { font-size: 0.8rem; color: #555; }
    .col-actions { white-space: nowrap; position: sticky; right: 0; background: #fff; box-shadow: -3px 0 8px rgba(0,0,0,.06); }
    .preview-table thead th:last-child { position: sticky; right: 0; background: #1a1a1a; box-shadow: -3px 0 8px rgba(0,0,0,.15); }
    .preview-table tr:hover td.col-actions { background: #f8f8f8; }
  </style>
</head>
<body>
<?php include 'show-navbar.php'; show_navbar(); ?>

<section class="intro-banner">
  <h1>Progress <span class="accent-text">Report</span></h1>
</section>

<div class="page-wrap">

  <?php if ($error): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Upload card -->
  <?php if (!$preview): ?>
  <div class="card">
    <h2 style="margin-top:0;">Upload Google Classroom Grades CSV</h2>

    <div class="mode-toggle">
      <span>Email Mode:</span>
      <form method="POST" style="display:contents;">
        <button type="submit" name="set_mode" value="DEV"
          class="mode-btn <?= $emailMode === 'DEV' ? 'active-dev' : '' ?>">DEV</button>
        <button type="submit" name="set_mode" value="PROD"
          class="mode-btn <?= $emailMode === 'PROD' ? 'active-prod' : '' ?>">PROD</button>
      </form>
    </div>
    <?php if ($emailMode === 'DEV'): ?>
    <div class="mode-alert mode-alert-dev">
      <div class="mode-icon">⚠️</div>
      <div class="mode-text">
        <strong>DEV Mode — Test Only</strong>
        All emails will be sent only to the admin inbox. Parents and students will NOT receive anything.
      </div>
    </div>
    <?php else: ?>
    <div class="mode-alert mode-alert-prod">
      <div class="mode-icon">✅</div>
      <div class="mode-text">
        <strong>PROD Mode — Live Send</strong>
        Emails will be sent to the student's parent(s) and the student directly. Admin will be CC'd.
      </div>
    </div>
    <?php endif; ?>

    <p style="color:#555;">Export the grades CSV from Google Classroom and upload it here. The student email column will be matched against registered students.</p>
    <form method="POST" enctype="multipart/form-data">
      <label class="upload-label" for="csv_file">Select CSV file:</label>
      <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
      <br>
      <button type="submit" class="btn btn-primary">Upload &amp; Preview</button>
    </form>
  </div>

  <?php else: ?>
  <!-- Preview card -->
  <div class="card">
    <h2 style="margin-top:0;">Preview — <?= htmlspecialchars($preview['courseName']) ?></h2>

    <div class="mode-toggle">
      <span>Email Mode:</span>
      <form method="POST" style="display:contents;">
        <button type="submit" name="set_mode" value="DEV"
          class="mode-btn <?= $preview['emailMode'] === 'DEV' ? 'active-dev' : '' ?>">DEV</button>
        <button type="submit" name="set_mode" value="PROD"
          class="mode-btn <?= $preview['emailMode'] === 'PROD' ? 'active-prod' : '' ?>">PROD</button>
      </form>
    </div>
    <?php if ($preview['emailMode'] === 'DEV'): ?>
    <div class="mode-alert mode-alert-dev">
      <div class="mode-icon">⚠️</div>
      <div class="mode-text">
        <strong>DEV Mode — Test Only</strong>
        All emails will be sent only to the admin inbox. Parents and students will NOT receive anything.
      </div>
    </div>
    <?php else: ?>
    <div class="mode-alert mode-alert-prod">
      <div class="mode-icon">✅</div>
      <div class="mode-text">
        <strong>PROD Mode — Live Send</strong>
        Emails will be sent to the student's parent(s) and the student directly. Admin will be CC'd.
      </div>
    </div>
    <?php endif; ?>

    <p style="color:#555;">
      <strong><?= count($preview['assignments']) ?></strong> assignments &nbsp;|&nbsp;
      <strong><?= count($preview['students']) ?></strong> students found in CSV
    </p>

    <div style="overflow-x:auto;">
    <table class="preview-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Student Name</th>
          <th>CSV Email</th>
          <th>Matched?</th>
          <th>Will Email To</th>
          <th>Missing / Zero</th>
          <th>Send</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($preview['students'] as $i => $s): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= htmlspecialchars($s['fullName']) ?></td>
          <td><?= htmlspecialchars($s['email']) ?></td>
          <td>
            <?php if ($s['matched']): ?>
              <span class="check">&#10003; Yes</span>
            <?php else: ?>
              <span class="cross">&#10007; No match</span>
            <?php endif; ?>
          </td>
          <td class="email-list">
            <?php if ($preview['emailMode'] === 'DEV'): ?>
              <em>Admin only (DEV)</em>
            <?php elseif (!empty($s['recipients']) || !empty($s['studentEmail'])): ?>
              <?= htmlspecialchars(implode(', ', array_merge($s['recipients'], [$s['studentEmail']]))) ?>
            <?php else: ?>
              <span class="cross">None found</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($s['missingOrZero'] > 0): ?>
              <span class="badge-missing"><?= $s['missingOrZero'] ?></span>
            <?php else: ?>
              <span class="check">All submitted</span>
            <?php endif; ?>
          </td>
          <td class="col-actions">
            <a href="admin_progress_report_preview.php?student_index=<?= $i ?>" target="_blank"
               style="display:inline-block;padding:6px 14px;font-size:0.8rem;font-weight:700;font-family:'Montserrat',sans-serif;background:#f4f4f4;color:#252525;border:2px solid #ddd;border-radius:8px;text-decoration:none;margin-right:6px;">
              &#128065; Preview
            </a>
            <form method="POST" action="admin_progress_report_send.php" style="display:inline;">
              <input type="hidden" name="student_index" value="<?= $i ?>">
              <button type="submit" class="btn btn-send" style="padding:6px 14px;font-size:0.8rem;">&#9993; Send</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <div style="margin-top:28px;display:flex;gap:16px;align-items:center;">
      <form method="POST" action="admin_progress_report_send.php">
        <button type="submit" class="btn btn-send">&#9993; Send All Reports</button>
      </form>
      <a href="admin_progress_report.php?reset=1" style="color:#555;font-weight:600;text-decoration:none;">&#8592; Upload a different file</a>
    </div>
  </div>
  <?php endif; ?>

</div>
<?php include 'footer.php'; ?>
</body>
</html>
