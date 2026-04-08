<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

// Block unauthorized users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Forbidden');
}

require 'db_configuration.php'; // provides $db (mysqli)

$message      = '';
$message_type = '';
$row_count    = 0;
$error_count  = 0;

// ── Handle CSV upload ─────────────────────────────────────────
if (isset($_POST['submit'])) {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $message      = "Error uploading file. Please try again.";
        $message_type = "error";
    } elseif (strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $message      = "Only CSV files are accepted.";
        $message_type = "error";
    } else {
        $handle = fopen($_FILES['file']['tmp_name'], 'r');

        // Skip header rows
        fgetcsv($handle); // header row

        $stmt = $db->prepare("
            INSERT INTO schools
                (id, name, type, category, grade_level_start, grade_level_end,
                 current_enrollment, address_text, state_name, state_code,
                 pin_code, contact_name, contact_designation, contact_phone,
                 contact_email, status, notes, referenced_by, supported_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                name               = VALUES(name),
                type               = VALUES(type),
                category           = VALUES(category),
                grade_level_start  = VALUES(grade_level_start),
                grade_level_end    = VALUES(grade_level_end),
                current_enrollment = VALUES(current_enrollment),
                address_text       = VALUES(address_text),
                state_name         = VALUES(state_name),
                state_code         = VALUES(state_code),
                pin_code           = VALUES(pin_code),
                contact_name       = VALUES(contact_name),
                contact_designation= VALUES(contact_designation),
                contact_phone      = VALUES(contact_phone),
                contact_email      = VALUES(contact_email),
                status             = VALUES(status),
                notes              = VALUES(notes),
                referenced_by      = VALUES(referenced_by),
                supported_by       = VALUES(supported_by)
        ");

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            // Skip blank rows or rows where first cell is literally "id"
            if (empty(array_filter($data)) || $data[0] === 'id') continue;

            // Pad to 19 columns in case CSV has fewer
            while (count($data) < 19) $data[] = '';

            // Convert empty strings to null
            $vals = array_map(fn($v) => trim($v) === '' ? null : trim($v), $data);

            $stmt->bind_param(
                "isssssisssssssssss",
                $vals[0],  $vals[1],  $vals[2],  $vals[3],  $vals[4],
                $vals[5],  $vals[6],  $vals[7],  $vals[8],  $vals[9],
                $vals[10], $vals[11], $vals[12], $vals[13], $vals[14],
                $vals[15], $vals[16], $vals[17], $vals[18]
            );

            if ($stmt->execute()) {
                $row_count++;
            } else {
                $error_count++;
            }
        }

        $stmt->close();
        fclose($handle);

        if ($error_count === 0) {
            $message      = "CSV uploaded successfully! {$row_count} row(s) imported.";
            $message_type = "success";
        } else {
            $message      = "{$row_count} row(s) imported. {$error_count} row(s) failed.";
            $message_type = $row_count > 0 ? "success" : "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Upload CSV – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">

    <style>
        body {
            background: #f8f8f8;
            margin: 0;
            font-family: 'Roboto', Arial, sans-serif;
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
            max-width: 700px;
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

        /* ── Flash message ── */
        .flash {
            padding: 13px 20px;
            border-radius: 9px;
            margin-bottom: 22px;
            font-weight: 700;
            font-size: 1em;
        }
        .flash.success { background: #edfae5; color: #2a6006; border: 1.5px solid #99d930; }
        .flash.error   { background: #fff0f0; color: #a00;    border: 1.5px solid #f88; }

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
            margin: 0 0 8px 0;
            font-size: 1.2em;
            color: #274606;
            font-weight: 900;
        }
        .card p {
            margin: 0 0 20px 0;
            color: #666;
            font-size: .95em;
            line-height: 1.6;
        }

        /* ── File input ── */
        .file-label {
            display: block;
            font-size: .85em;
            font-weight: 700;
            color: #274606;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 8px;
        }
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #cde8a0;
            border-radius: 8px;
            background: #f8fbe9;
            font-family: 'Roboto', sans-serif;
            font-size: .95em;
            box-sizing: border-box;
            outline: none;
            transition: border-color .18s;
        }
        input[type="file"]:focus { border-color: #99d930; }

        /* ── Submit button ── */
        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 18px;
            padding: 11px 28px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            background: #99d930;
            color: #274606;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(153,217,48,.25);
            transition: background .18s, transform .12s;
        }
        .btn-submit:hover { background: #85c220; transform: translateY(-1px); }

        /* ── Info box ── */
        .info-box {
            background: #f8fbe9;
            border: 1.5px solid #cde8a0;
            border-radius: 10px;
            padding: 14px 18px;
            font-size: .88em;
            color: #274606;
            line-height: 1.65;
        }
        .info-box strong { display: block; margin-bottom: 6px; font-size: .95em; }

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
    <h1 class="banner-title">Upload CSV</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <?php if ($message): ?>
        <div class="flash <?= htmlspecialchars($message_type) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- ── Upload form ── -->
    <div class="card">
        <h2>📂 Upload Schools CSV</h2>
        <p>
            Upload a CSV file exported from the schools database. Existing records will be
            updated by ID; new records will be inserted automatically.
        </p>
        <form method="POST" enctype="multipart/form-data">
            <label class="file-label" for="file">Select CSV File</label>
            <input type="file" id="file" name="file" accept=".csv" required>
            <button type="submit" name="submit" class="btn-submit">⬆ Upload CSV</button>
        </form>
    </div>

    <!-- ── Expected format ── -->
    <div class="card">
        <h2>📋 Expected CSV Format</h2>
        <div class="info-box">
            <strong>Column order (19 columns):</strong>
            id, name, type, category, grade_level_start, grade_level_end,
            current_enrollment, address_text, state_name, state_code, pin_code,
            contact_name, contact_designation, contact_phone, contact_email,
            status, notes, referenced_by, supported_by
            <br><br>
            Row 1 is treated as the header and skipped. Empty cells are imported as NULL.
            Existing records matched by <strong>id</strong> will be updated.
        </div>
    </div>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>