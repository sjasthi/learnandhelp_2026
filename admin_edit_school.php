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

// Sanitize $id — must be a positive integer or null
$id = null;
if (isset($_POST['id']) && $_POST['id'] !== '') {
    $candidate = intval($_POST['id']);
    if ($candidate > 0) $id = $candidate;
}

require_once __DIR__ . '/db_configuration.php'; // provides $db (mysqli)
include 'admin_fill.php';
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title><?= $id ? 'Edit School' : 'Add School' ?> – Administration</title>
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
            max-width: 1100px;
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
            padding: 26px 30px;
            margin-bottom: 28px;
        }
        .card h2 {
            margin: 0 0 20px 0;
            font-size: 1.2em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 20px;
            border-radius: 8px;
            font-size: .9em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .18s, transform .12s;
            white-space: nowrap;
        }
        .btn-green   { background: #99d930; color: #274606; box-shadow: 0 2px 8px rgba(153,217,48,.25); }
        .btn-green:hover   { background: #85c220; transform: translateY(-1px); }
        .btn-outline { background: #fff; color: #274606; border: 1.5px solid #99d930; }
        .btn-outline:hover { background: #f0fad8; }
        .btn-danger  { background: #fff0f0; color: #c00; border: 1.5px solid #f99; }
        .btn-danger:hover  { background: #ffe0e0; }

        /* ── Media grid ── */
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 18px;
        }
        .media-tile {
            background: #f8fbe9;
            border: 1.5px solid #cde8a0;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
        }
        .media-tile img {
            max-width: 100%;
            max-height: 120px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            margin-bottom: 8px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .media-tile .media-name {
            font-size: .8em;
            color: #555;
            word-break: break-all;
            margin-bottom: 10px;
        }
        .media-tile .media-actions {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .profile-badge {
            display: inline-block;
            background: #edfae5;
            color: #2a6006;
            border: 1px solid #99d930;
            border-radius: 20px;
            padding: 2px 10px;
            font-size: .75em;
            font-weight: 700;
            margin-bottom: 6px;
        }

        /* ── File upload input ── */
        .file-input-wrap {
            padding: 12px;
            border: 2px dashed #cde8a0;
            border-radius: 8px;
            background: #f8fbe9;
            margin-top: 10px;
        }
        .file-input-wrap label {
            font-size: .85em;
            font-weight: 700;
            color: #274606;
            display: block;
            margin-bottom: 8px;
        }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .card { padding: 16px 12px; }
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
    <h1 class="banner-title"><?= $id ? 'Edit School' : 'Add School' ?></h1>
</div>

<div class="page-wrap">

    <a href="admin_schools.php" class="back-link">&#8592; Back to Schools</a>

    <!-- ── Media section (edit mode only) ── -->
    <?php
    $schoolDir = "schools/{$id}/";
    if ($id !== null && is_dir($schoolDir)):
        $allFiles  = array_values(array_diff(scandir($schoolDir), ['.', '..']));
        $time      = time();
    ?>
    <?php if (!empty($allFiles)): ?>
    <div class="card">
        <h2>🖼️ School Media</h2>
        <div class="media-grid">
            <?php foreach ($allFiles as $filename):
                $isProfile = str_contains($filename, 'profile_image');
                $safeName  = htmlspecialchars($filename, ENT_QUOTES);
                $safeId    = intval($id);
                $imgSrc    = "schools/{$id}/{$safeName}" . ($isProfile ? "?v={$time}" : '');
            ?>
            <div class="media-tile">
                <?php if ($isProfile): ?>
                    <span class="profile-badge">✓ Profile Image</span>
                <?php endif; ?>
                <img src="<?= htmlspecialchars($imgSrc) ?>"
                     alt="<?= $safeName ?>"
                     onerror="this.src='images/admin_icons/school.png'">
                <div class="media-name"><?= $safeName ?></div>

                <?php if (!$isProfile): ?>
                <div class="media-actions">
                    <form method="POST" action="admin_school_media.php" style="display:contents;">
                        <input type="hidden" name="update"   value="1">
                        <input type="hidden" name="id"       value="<?= $safeId ?>">
                        <input type="hidden" name="filename" value="<?= $safeName ?>">
                        <button type="submit" name="default_btn" class="btn btn-outline">
                            ⭐ Make Profile
                        </button>
                    </form>
                    <form method="POST" action="admin_school_media.php" style="display:contents;"
                          onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($filename)) ?>? This cannot be undone.');">
                        <input type="hidden" name="delete"   value="1">
                        <input type="hidden" name="id"       value="<?= $safeId ?>">
                        <input type="hidden" name="filename" value="<?= $safeName ?>">
                        <button type="submit" name="delete_btn" class="btn btn-danger">
                            🗑 Delete
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- ── School details form ── -->
    <div class="card">
        <h2><?= $id ? '✏️ Edit School Details' : '➕ Add New School' ?></h2>

        <?php admin_school_form($id); ?>

        <div class="file-input-wrap" style="margin-top:18px;">
            <label for="media_upload">📎 Upload Media Files</label>
            <input id="media_upload" type="file" name="files[]" multiple
                   accept="image/*">
            <input type="hidden" name="id" value="<?= intval($id) ?>">
        </div>

        <?php if ($id !== null): ?>
            <input type="hidden" name="action" value="admin_edit_school">
        <?php else: ?>
            <input type="hidden" name="action" value="admin_add_school">
        <?php endif; ?>

        <br>
        <button type="submit" id="submit-school" name="submit" class="btn btn-green">
            💾 <?= $id ? 'Save Changes' : 'Add School' ?>
        </button>

    </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>