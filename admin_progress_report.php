<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Block unauthorized users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Forbidden');
}

require 'db_configuration.php'; // provides $db (mysqli)

// ── Fetch preferences ─────────────────────────────────────────
$emailMode   = 'DEV';
$activeBatch = '';
$stmt = $db->prepare("SELECT Preference_Name, Value FROM preferences WHERE Preference_Name IN ('Email_Mode','Active Registration')");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    if ($row['Preference_Name'] === 'Email_Mode')          $emailMode   = $row['Value'];
    if ($row['Preference_Name'] === 'Active Registration') $activeBatch = $row['Value'];
}
$stmt->close();

// ── Reset session ─────────────────────────────────────────────
if (isset($_GET['reset'])) {
    unset($_SESSION['progress_report']);
}

// ── Mode toggle ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_mode'])) {
    $newMode = $_POST['set_mode'] === 'PROD' ? 'PROD' : 'DEV';
    $stmt = $db->prepare("UPDATE preferences SET Value = ? WHERE Preference_Name = 'Email_Mode'");
    $stmt->bind_param("s", $newMode);
    $stmt->execute();
    $stmt->close();
    $emailMode = $newMode;
    if (isset($_SESSION['progress_report'])) {
        $_SESSION['progress_report']['emailMode'] = $newMode;
    }
    header('Location: admin_progress_report.php');
    exit;
}

$preview    = $_SESSION['progress_report'] ?? null;
$error      = '';
$courseName = '';

// ── Handle CSV upload ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed. Please try again.';
    } elseif (strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $error = 'Only CSV files are accepted.';
    } else {
        $rawName    = pathinfo($_FILES['csv_file']['name'], PATHINFO_FILENAME);
        $courseName = ucwords(str_replace('_', ' ', preg_replace('/_scores$/i', '', $rawName)));

        $tempDir  = __DIR__ . '/uploads/progress_temp/';
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
        $tempFile = $tempDir . uniqid('pr_', true) . '.csv';
        move_uploaded_file($_FILES['csv_file']['tmp_name'], $tempFile);

        $handle   = fopen($tempFile, 'r');
        $headers  = fgetcsv($handle);
        $datesRow = fgetcsv($handle);
        $pointsRow = fgetcsv($handle);

        $assignments = [];
        for ($i = 3; $i < count($headers); $i++) {
            $name = trim($headers[$i] ?? '');
            if ($name === '') continue;
            $assignments[] = [
                'index'  => $i,
                'name'   => $name,
                'date'   => trim($datesRow[$i]  ?? ''),
                'points' => trim($pointsRow[$i] ?? ''),
            ];
        }

        // Check for secondary_contact_email column
        $hasSecondary = false;
        $colCheck = $db->query("SHOW COLUMNS FROM users LIKE 'secondary_contact_email'");
        if ($colCheck && $colCheck->num_rows > 0) $hasSecondary = true;

        $students = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) continue;
            $lastName  = trim($row[0] ?? '');
            $firstName = trim($row[1] ?? '');
            $email     = strtolower(trim($row[2] ?? ''));
            if ($email === '' || $email === 'email account') continue;

            $fullName      = $firstName . ' ' . $lastName;
            $scores        = [];
            $missingOrZero = 0;
            foreach ($assignments as $a) {
                $val = trim($row[$a['index']] ?? '');
                $scores[$a['index']] = $val;
                if ($val === '' || $val === '0') $missingOrZero++;
            }

            // Look up registration
            $reg  = null;
            $stmt = $db->prepare(
                "SELECT Sponsor1_Email, Student_Email FROM registrations
                 WHERE LOWER(Student_Email) = ? ORDER BY Reg_Id DESC LIMIT 1"
            );
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res2 = $stmt->get_result();
            if ($res2->num_rows > 0) $reg = $res2->fetch_assoc();
            $stmt->close();

            $recipients = [];
            if ($reg) {
                if (!empty($reg['Sponsor1_Email'])) $recipients[] = $reg['Sponsor1_Email'];
                $studentEmail = $reg['Student_Email'] ?? $email;
                if ($hasSecondary && !empty($reg['Sponsor1_Email'])) {
                    $se   = strtolower($reg['Sponsor1_Email']);
                    $su   = $db->prepare("SELECT secondary_contact_email FROM users WHERE LOWER(Email) = ? LIMIT 1");
                    $su->bind_param('s', $se);
                    $su->execute();
                    $ur = $su->get_result()->fetch_assoc();
                    $su->close();
                    if (!empty($ur['secondary_contact_email'])) $recipients[] = $ur['secondary_contact_email'];
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
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Progress Report – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

    <style>
        body {
            background: #f8f8f8;
            margin: 0;
            font-family: 'Roboto', Arial, sans-serif;
            overflow-x: hidden;
        }

        /* ── Banner ── */
        .banner-wrapper {
            width: 100vw;
            left: 50%;
            margin-left: -50vw;
            height: 220px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            position: relative;
        }
        .banner-wrapper img {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;
        }
        .banner-title {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            margin: 0;
            font-family: 'Roboto', sans-serif;
            font-size: 3em;
            font-weight: 900;
            color: #99d930;
            text-shadow: 0 2px 16px rgba(0,0,0,0.44);
            letter-spacing: 1px;
            z-index: 2;
            white-space: nowrap;
        }

        /* ── Page wrapper ── */
        .page-wrap {
            max-width: 1400px;
            margin: 36px auto 60px auto;
            padding: 0 18px;
        }

        /* ── Back link ── */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 18px;
            font-weight: 700;
            color: #274606;
            text-decoration: none;
            font-size: .97em;
        }
        .back-link:hover { color: #99d930; }

        /* ── Card ── */
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 30px 32px;
            margin-bottom: 28px;
        }
        .card h2 {
            margin: 0 0 20px 0;
            font-size: 1.25em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Mode toggle ── */
        .mode-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .mode-toggle > span { font-weight: 700; color: #555; font-size: .95em; }
        .mode-btn {
            padding: 9px 24px;
            border-radius: 8px;
            font-size: .95em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            cursor: pointer;
            border: 2px solid #cde8a0;
            background: #f8fbe9;
            color: #888;
            transition: all .18s;
        }
        .mode-btn.active-dev  { background: #e65100; color: #fff; border-color: #e65100; box-shadow: 0 2px 8px rgba(230,81,0,.3); }
        .mode-btn.active-prod { background: #2e7d32; color: #fff; border-color: #2e7d32; box-shadow: 0 2px 8px rgba(46,125,50,.3); }

        /* ── Mode alerts ── */
        .mode-alert {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 22px;
            font-size: .95em;
            font-weight: 600;
        }
        .mode-alert-dev  { background: #fff3e0; border: 1.5px solid #e65100; color: #bf360c; }
        .mode-alert-prod { background: #edfae5; border: 1.5px solid #99d930; color: #2a6006; }
        .mode-alert .mode-icon { font-size: 1.3rem; flex-shrink: 0; }
        .mode-alert strong { display: block; margin-bottom: 2px; }

        /* ── Upload form ── */
        .upload-label { font-weight: 700; display: block; margin-bottom: 8px; color: #274606; }
        input[type=file] {
            padding: 10px;
            border: 2px dashed #cde8a0;
            border-radius: 8px;
            width: 100%;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
            background: #f8fbe9;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: .97em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .18s, transform .12s;
        }
        .btn-green  { background: #99d930; color: #274606; box-shadow: 0 2px 8px rgba(153,217,48,.25); }
        .btn-green:hover  { background: #85c220; transform: translateY(-1px); }
        .btn-dark   { background: #274606; color: #fff; }
        .btn-dark:hover   { background: #1a2e04; transform: translateY(-1px); }
        .btn-outline { background: #fff; color: #274606; border: 2px solid #99d930; }
        .btn-outline:hover { background: #f0fad8; }
        .btn-sm { padding: 6px 14px; font-size: .82em; }

        /* ── Error flash ── */
        .flash-error {
            padding: 13px 20px;
            border-radius: 9px;
            margin-bottom: 22px;
            font-weight: 700;
            background: #fff0f0;
            color: #a00;
            border: 1.5px solid #f88;
        }

        /* ── Preview table ── */
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: .9em;
        }
        .preview-table thead tr { background: #99d930; color: #274606; }
        .preview-table th {
            padding: 11px 13px;
            text-align: left;
            font-weight: 900;
            font-size: .83em;
            text-transform: uppercase;
            letter-spacing: .4px;
            white-space: nowrap;
        }
        .preview-table th:last-child {
            position: sticky;
            right: 0;
            background: #99d930;
            box-shadow: -3px 0 8px rgba(0,0,0,.1);
        }
        .preview-table td {
            padding: 9px 13px;
            border-bottom: 1px solid #e8f5c8;
            vertical-align: middle;
        }
        .preview-table tbody tr:hover td { background: #f4fce6; }
        .preview-table td.col-actions {
            position: sticky;
            right: 0;
            background: #fff;
            box-shadow: -3px 0 8px rgba(0,0,0,.06);
            white-space: nowrap;
        }
        .preview-table tbody tr:hover td.col-actions { background: #f4fce6; }

        .check  { color: #2a6006; font-weight: 700; }
        .cross  { color: #c00;    font-weight: 700; }
        .badge-missing {
            display: inline-block;
            background: #fff0f0;
            color: #c00;
            border-radius: 20px;
            padding: 2px 10px;
            font-size: .8em;
            font-weight: 700;
            border: 1px solid #f99;
        }
        .email-list { font-size: .8em; color: #555; }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .card { padding: 18px 14px; }
        }
    </style>
</head>
<body>

<?php
include 'show-navbar.php';
show_navbar();
?>

<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Admin banner">
    <h1 class="banner-title">Progress Report</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <?php if ($error): ?>
        <div class="flash-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php
    // ── Shared mode toggle + alert helper ────────────────────
    function renderModeSection(string $mode): void { ?>
        <div class="mode-toggle">
            <span>Email Mode:</span>
            <form method="POST" style="display:contents;">
                <button type="submit" name="set_mode" value="DEV"
                        class="mode-btn <?= $mode === 'DEV' ? 'active-dev' : '' ?>">DEV</button>
                <button type="submit" name="set_mode" value="PROD"
                        class="mode-btn <?= $mode === 'PROD' ? 'active-prod' : '' ?>">PROD</button>
            </form>
        </div>
        <?php if ($mode === 'DEV'): ?>
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
        <?php endif;
    }
    ?>

    <?php if (!$preview): ?>
    <!-- ── Upload card ── -->
    <div class="card">
        <h2>Upload Google Classroom Grades CSV</h2>
        <?php renderModeSection($emailMode); ?>
        <p style="color:#555;margin-bottom:18px;">
            Export the grades CSV from Google Classroom and upload it here.
            The student email column will be matched against registered students.
        </p>
        <form method="POST" enctype="multipart/form-data">
            <label class="upload-label" for="csv_file">Select CSV file:</label>
            <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
            <br>
            <button type="submit" class="btn btn-green" style="margin-top:16px;">
                ⬆ Upload &amp; Preview
            </button>
        </form>
    </div>

    <?php else: ?>
    <!-- ── Preview card ── -->
    <div class="card">
        <h2>Preview — <?= htmlspecialchars($preview['courseName']) ?></h2>
        <?php renderModeSection($preview['emailMode']); ?>

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
                        <th>Actions</th>
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
                        <a href="admin_progress_report_preview.php?student_index=<?= $i ?>"
                           target="_blank" class="btn btn-outline btn-sm">
                            👁 Preview
                        </a>
                        <form method="POST" action="admin_progress_report_send.php"
                              style="display:inline;">
                            <input type="hidden" name="student_index" value="<?= $i ?>">
                            <button type="submit" class="btn btn-dark btn-sm">
                                ✉ Send
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top:24px;display:flex;gap:14px;align-items:center;flex-wrap:wrap;">
            <form method="POST" action="admin_progress_report_send.php">
                <button type="submit" class="btn btn-dark">✉ Send All Reports</button>
            </form>
            <a href="admin_progress_report.php?reset=1" class="btn btn-outline">
                &#8592; Upload a different file
            </a>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>