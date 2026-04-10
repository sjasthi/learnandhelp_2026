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

// ── Constants ─────────────────────────────────────────────────
$UPLOAD_DIR   = __DIR__ . '/images/community_partners';
$UPLOAD_WEB   = 'images/community_partners';
$ALLOWED_MIME = ['image/png','image/jpeg','image/gif','image/webp','image/svg+xml'];
$MAX_BYTES    = 3 * 1024 * 1024; // 3 MB
$DEFAULT_LOGO = 'default_logo.png';

$message     = '';
$messageType = '';

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partner_name       = trim($_POST['partner_name']       ?? '');
    $partner_type       = trim($_POST['partner_type']       ?? '');
    $website_url        = trim($_POST['website_url']        ?? '');
    $impact_description = trim($_POST['impact_description'] ?? '');

    $allowed_types = ['school','library','non_profit','community_center','other'];
    if (!in_array($partner_type, $allowed_types, true)) $partner_type = '';

    $errors = [];
    if ($partner_name === '') $errors[] = 'Partner name is required.';
    if ($partner_type === '') $errors[] = 'Partner type is required.';
    if ($website_url !== '' && !filter_var($website_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Website URL is invalid.';
    }

    // ── Logo upload ───────────────────────────────────────────
    if (!is_dir($UPLOAD_DIR)) @mkdir($UPLOAD_DIR, 0755, true);

    $finalLogoFilename = '';
    if (!empty($_FILES['logo_image']['name'])) {
        $file = $_FILES['logo_image'];
        $tmp  = $file['tmp_name'] ?? '';
        $err  = $file['error']    ?? UPLOAD_ERR_NO_FILE;
        $size = $file['size']     ?? 0;

        if ($err === UPLOAD_ERR_OK && is_uploaded_file($tmp)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($tmp);
            if (!in_array($mime, $ALLOWED_MIME, true)) {
                $errors[] = 'Logo must be an image (PNG, JPG, GIF, WEBP, SVG).';
            }
            if ($size > $MAX_BYTES) {
                $errors[] = 'Logo exceeds the maximum size of 3 MB.';
            }
            $ext              = pathinfo($file['name'], PATHINFO_EXTENSION);
            $base             = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
            $finalLogoFilename = $base . '_' . date('Ymd_His') . '.' . $ext;
            $dest             = $UPLOAD_DIR . '/' . $finalLogoFilename;

            if (empty($errors) && !@move_uploaded_file($tmp, $dest)) {
                $errors[] = 'Failed to save the uploaded logo.';
            }
        } elseif ($err !== UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Error during logo upload.';
        }
    } else {
        $finalLogoFilename = file_exists($UPLOAD_DIR . '/' . $DEFAULT_LOGO) ? $DEFAULT_LOGO : '';
    }

    // ── Insert ────────────────────────────────────────────────
    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO community_partners (partner_name, partner_type, logo_image, website_url, impact_description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $stmt->bind_param("sssss", $partner_name, $partner_type, $finalLogoFilename, $website_url, $impact_description);

        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['flash_message'] = "Partner \"{$partner_name}\" created successfully.";
            $_SESSION['flash_type']    = 'success';
            header('Location: admin_partners_list.php');
            exit();
        } else {
            $message     = 'Insert failed. Please try again.';
            $messageType = 'error';
            $stmt->close();
        }
    } else {
        $message     = implode(' ', $errors);
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Create Partner – Administration</title>
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
            max-width: 800px;
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
            padding: 30px 32px;
            margin-bottom: 28px;
        }
        .card h2 {
            margin: 0 0 22px 0;
            font-size: 1.2em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Form groups ── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 18px;
        }
        .form-group label {
            font-size: .83em;
            font-weight: 700;
            color: #274606;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .form-group input[type="text"],
        .form-group input[type="url"],
        .form-group select,
        .form-group textarea {
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
            width: 100%;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #99d930;
            box-shadow: 0 0 0 3px rgba(153,217,48,.15);
        }
        .form-group textarea { resize: vertical; min-height: 130px; }
        .form-group input[type="file"] {
            padding: 10px;
            border: 2px dashed #cde8a0;
            border-radius: 8px;
            background: #f8fbe9;
            font-family: 'Roboto', sans-serif;
            width: 100%;
            box-sizing: border-box;
        }
        .form-group small {
            font-size: .8em;
            color: #888;
            margin-top: 2px;
        }
        .form-group .read-only {
            padding: 10px 13px;
            background: #f0f0f0;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            color: #888;
            font-weight: 700;
            font-size: .95em;
        }

        /* ── Buttons ── */
        .btn-row {
            display: flex;
            gap: 12px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 11px 26px;
            border-radius: 8px;
            font-size: .97em;
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
            .banner-title { font-size: 2em; }
            .card { padding: 18px 14px; }
            .btn-row { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Admin banner">
    <h1 class="banner-title">Create Partner</h1>
</div>

<div class="page-wrap">

    <a href="admin_partners.php" class="back-link">&#8592; Back to Partners</a>

    <?php if ($message): ?>
        <div class="flash <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>🤝 New Community Partner</h2>

        <form method="POST" action="" enctype="multipart/form-data" autocomplete="off">

            <div class="form-group">
                <label>Partner ID</label>
                <div class="read-only">Auto-generated</div>
            </div>

            <div class="form-group">
                <label for="partner_name">Partner Name <span style="color:#c00">*</span></label>
                <input type="text" id="partner_name" name="partner_name" required
                       placeholder="e.g. Sunrise Community Library"
                       value="<?= htmlspecialchars($_POST['partner_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="partner_type">Partner Type <span style="color:#c00">*</span></label>
                <select id="partner_type" name="partner_type" required>
                    <option value="">— Select a type —</option>
                    <?php
                    $opts = [
                        'school'           => 'School',
                        'library'          => 'Library',
                        'non_profit'       => 'Non-Profit',
                        'community_center' => 'Community Center',
                        'other'            => 'Other',
                    ];
                    $sel = $_POST['partner_type'] ?? '';
                    foreach ($opts as $val => $label):
                        $selected = ($sel === $val) ? 'selected' : '';
                    ?>
                        <option value="<?= htmlspecialchars($val) ?>" <?= $selected ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="logo_image">Logo Image</label>
                <input type="file" id="logo_image" name="logo_image"
                       accept=".png,.jpg,.jpeg,.gif,.webp,.svg">
                <small>Accepted: PNG, JPG, GIF, WEBP, SVG — up to 3 MB. Stored in <code>images/community_partners/</code>.</small>
            </div>

            <div class="form-group">
                <label for="website_url">Website URL</label>
                <input type="url" id="website_url" name="website_url"
                       placeholder="https://example.org"
                       value="<?= htmlspecialchars($_POST['website_url'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="impact_description">Impact Description</label>
                <textarea id="impact_description" name="impact_description"
                          placeholder="Brief description of this partner's impact…"><?= htmlspecialchars($_POST['impact_description'] ?? '') ?></textarea>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-green">➕ Create Partner</button>
                <a href="admin_partners.php" class="btn btn-outline">✕ Cancel</a>
            </div>

        </form>
    </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>