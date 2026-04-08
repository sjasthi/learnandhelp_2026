<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_configuration.php'; // provides $db (mysqli)

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message     = '';
$messageType = '';

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['cancel'])) {
        header('Location: my_account.php');
        exit();
    }

    if (isset($_POST['submit'])) {
        $school_name          = trim($_POST['school_name']          ?? '');
        $contact_name         = trim($_POST['contact_name']         ?? '');
        $contact_mobile       = trim($_POST['contact_mobile']       ?? '');
        $commitment_statement = trim($_POST['commitment_statement'] ?? '');

        if (empty($school_name)) {
            $message     = 'School name is required.';
            $messageType = 'error';
        } elseif (empty($contact_name)) {
            $message     = 'Contact name is required.';
            $messageType = 'error';
        } else {
            $stmt = $db->prepare("INSERT INTO schools (name, contact_name, contact_phone, commitment_statement, status) VALUES (?, ?, ?, ?, 'Proposed')");
            $stmt->bind_param("ssss", $school_name, $contact_name, $contact_mobile, $commitment_statement);

            if ($stmt->execute()) {
                $stmt->close();
                $_SESSION['flash_message'] = 'Thank you for suggesting a school! You may nominate another school.';
                $_SESSION['flash_type']    = 'success';
                header('Location: index.php');
                exit();
            } else {
                $message     = ($db->errno === 1062)
                    ? 'This school has already been suggested. Thank you!'
                    : 'Error submitting suggestion. Please try again.';
                $messageType = 'error';
                $stmt->close();
            }
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
    <title>Suggest a School – Learn and Help</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

    <style>
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background: #f8f8f8;
            color: #252525;
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
        .banner-subtitle {
            position: absolute;
            bottom: 18px;
            left: 50%;
            transform: translateX(-50%);
            margin: 0;
            font-size: 1.05em;
            color: #fff;
            text-shadow: 0 1px 6px rgba(0,0,0,.5);
            z-index: 2;
            white-space: nowrap;
        }

        /* ── Page wrapper ── */
        .page-wrap {
            max-width: 780px;
            margin: 40px auto 60px auto;
            padding: 0 20px;
        }

        /* ── Flash messages ── */
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
            padding: 36px 40px;
        }
        .card h2 {
            margin: 0 0 22px 0;
            font-size: 1.25em;
            font-weight: 900;
            color: #274606;
        }

        /* ── Form groups ── */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: .85em;
            font-weight: 700;
            color: #274606;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 6px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 13px;
            border: 1.5px solid #cde8a0;
            border-radius: 8px;
            font-size: 1em;
            font-family: 'Roboto', sans-serif;
            background: #f8fbe9;
            color: #222;
            outline: none;
            transition: border-color .18s, box-shadow .18s;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #99d930;
            box-shadow: 0 0 0 3px rgba(153,217,48,.15);
        }
        .form-group textarea { resize: vertical; min-height: 130px; }
        .form-group small {
            display: block;
            color: #888;
            margin-top: 5px;
            font-size: .83em;
        }

        /* ── Buttons ── */
        .btn-row {
            display: flex;
            gap: 14px;
            justify-content: center;
            margin-top: 28px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 11px 28px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .18s, transform .12s;
        }
        .btn-green   { background: #99d930; color: #274606; box-shadow: 0 2px 8px rgba(153,217,48,.25); }
        .btn-green:hover   { background: #85c220; transform: translateY(-1px); }
        .btn-outline { background: #fff; color: #274606; border: 2px solid #99d930; }
        .btn-outline:hover { background: #f0fad8; }

        @media (max-width: 680px) {
            .card { padding: 22px 16px; }
            .btn-row { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
            .banner-title { font-size: 2em; }
        }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<!-- ── Banner ── -->
<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Suggest a School banner">
    <h1 class="banner-title">Suggest a School</h1>
    <p class="banner-subtitle">Help us connect with schools that could benefit from our library programs</p>
</div>

<div class="page-wrap">

    <?php if ($message): ?>
        <div class="flash <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>🏫 School Suggestion Form</h2>

        <form method="POST" action="">

            <div class="form-group">
                <label for="school_name">School Name <span style="color:#c00">*</span></label>
                <input type="text" id="school_name" name="school_name" required
                       placeholder="e.g. Sunrise Public School"
                       value="<?= htmlspecialchars($_POST['school_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="contact_name">Contact Name (Teacher or Head Master) <span style="color:#c00">*</span></label>
                <input type="text" id="contact_name" name="contact_name" required
                       placeholder="e.g. Mr. Ramesh Kumar"
                       value="<?= htmlspecialchars($_POST['contact_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="contact_mobile">Mobile Number of Contact</label>
                <input type="tel" id="contact_mobile" name="contact_mobile"
                       placeholder="e.g. +91 99999 88888"
                       value="<?= htmlspecialchars($_POST['contact_mobile'] ?? '') ?>">
                <small>Contact phone number for the school representative</small>
            </div>

            <div class="form-group">
                <label for="commitment_statement">Statement of Commitment</label>
                <textarea id="commitment_statement" name="commitment_statement"
                          placeholder="Describe how you plan to manage and maintain the library, and your commitment to the program…"><?= htmlspecialchars($_POST['commitment_statement'] ?? '') ?></textarea>
                <small>Describe your plans for managing and maintaining the library</small>
            </div>

            <div class="btn-row">
                <button type="submit" name="submit" class="btn btn-green">📨 Submit Suggestion</button>
                <button type="submit" name="cancel" class="btn btn-outline">✕ Cancel</button>
            </div>

        </form>
    </div>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>