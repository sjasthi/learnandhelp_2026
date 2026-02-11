<?php
// Errors (dev-friendly; tone down in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ---- Session + Admin Gate ----
$status = session_status();
if ($status == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Forbidden');
}

// ---- Dependencies ----
require 'db_configuration.php';

// ---- Flash helpers ----
function set_flash($type, $msg) {
    $_SESSION["flash_$type"] = $msg;
}
function show_flash_and_clear() {
    foreach (['success','error'] as $t) {
        if (!empty($_SESSION["flash_$t"])) {
            $class = $t === 'success' ? 'flash-success' : 'flash-error';
            echo '<div class="'.$class.'">'.htmlspecialchars($_SESSION["flash_$t"]).'</div>';
            unset($_SESSION["flash_$t"]);
        }
    }
}

// ---- Constants ----
$UPLOAD_DIR = __DIR__ . '/images/community_partners';
$UPLOAD_WEB = 'images/community_partners'; // for <img src> or DB paths
$ALLOWED_MIME = ['image/png','image/jpeg','image/gif','image/webp','image/svg+xml'];
$MAX_BYTES    = 3 * 1024 * 1024; // 3 MB
$DEFAULT_LOGO = 'default_logo.png'; // put a fallback file here if you want

// ---- Handle POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $partner_name       = trim($_POST['partner_name'] ?? '');
    $partner_type       = trim($_POST['partner_type'] ?? '');
    $website_url        = trim($_POST['website_url'] ?? '');
    $impact_description = trim($_POST['impact_description'] ?? '');

    // Validate required fields
    $errors = [];
    if ($partner_name === '') { $errors[] = 'Partner name is required.'; }
    if ($partner_type === '') { $errors[] = 'Partner type is required.'; }

    // Basic URL validation (optional)
    if ($website_url !== '' && !filter_var($website_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Website URL is invalid.';
    }

    // Ensure upload dir exists
    if (!is_dir($UPLOAD_DIR)) {
        @mkdir($UPLOAD_DIR, 0755, true);
    }

    // Handle logo upload (optional)
    $finalLogoFilename = '';
    if (!empty($_FILES['logo_image']['name'])) {
        $file      = $_FILES['logo_image'];
        $tmp       = $file['tmp_name'] ?? '';
        $origName  = $file['name'] ?? '';
        $size      = $file['size'] ?? 0;
        $err       = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($err === UPLOAD_ERR_OK && is_uploaded_file($tmp)) {
            // MIME check (best-effort)
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($tmp);
            if (!in_array($mime, $ALLOWED_MIME, true)) {
                $errors[] = 'Logo must be an image (png, jpg, gif, webp, svg).';
            }
            if ($size > $MAX_BYTES) {
                $errors[] = 'Logo exceeds the maximum size of 3 MB.';
            }

            // Build safe filename
            $ext = pathinfo($origName, PATHINFO_EXTENSION);
            $base = pathinfo($origName, PATHINFO_FILENAME);
            $base = preg_replace('/[^A-Za-z0-9_\-]/', '_', $base);
            $stamp = date('Ymd_His');
            $finalLogoFilename = $base . '_' . $stamp . '.' . $ext;
            $dest = $UPLOAD_DIR . '/' . $finalLogoFilename;

            if (empty($errors)) {
                if (!@move_uploaded_file($tmp, $dest)) {
                    $errors[] = 'Failed to save the uploaded logo.';
                }
            }
        } elseif ($err !== UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Error during logo upload.';
        }
    } else {
        // If you want a default always: use DEFAULT_LOGO (make sure it exists in the folder)
        if (file_exists($UPLOAD_DIR . '/' . $DEFAULT_LOGO)) {
            $finalLogoFilename = $DEFAULT_LOGO;
        } else {
            $finalLogoFilename = ''; // store empty if no default
        }
    }

    // Insert if no errors
    if (empty($errors)) {
        $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
        if ($conn->connect_error) {
            set_flash('error', 'Database connection failed: ' . $conn->connect_error);
        } else {
            // Prepared INSERT
            // Columns: partner_id (auto), partner_name, partner_type, logo_image, website_url, impact_description, created_at, updated_at
            $sql = "INSERT INTO community_partners
                        (partner_name, partner_type, logo_image, website_url, impact_description, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                set_flash('error', 'Prepare failed: ' . $conn->error);
            } else {
                $logoToStore = $finalLogoFilename; // store just the filename
                $stmt->bind_param(
                    'sssss',
                    $partner_name,
                    $partner_type,
                    $logoToStore,
                    $website_url,
                    $impact_description
                );
                if ($stmt->execute()) {
                    set_flash('success', 'Partner created successfully.');
                    $stmt->close();
                    $conn->close();
                    header('Location: admin_partners_list.php');
                    exit;
                } else {
                    set_flash('error', 'Insert failed: ' . $stmt->error);
                    $stmt->close();
                    $conn->close();
                }
            }
        }
    } else {
        // Show collected validation errors
        set_flash('error', implode(' ', $errors));
    }
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="utf-8">
    <title>Create Partner</title>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <style>
        .container { max-width: 980px; margin: 30px auto; background: #fff; padding: 24px 28px; border-radius: 14px; box-shadow: 0 6px 28px rgba(0,0,0,0.06); }
        h1 { margin-top: 0; }
        .form-row { display: grid; grid-template-columns: 240px 1fr; gap: 16px; align-items: center; margin: 12px 0; }
        .form-row textarea { min-height: 120px; }
        .actions { margin-top: 22px; display: flex; gap: 12px; }
        .btn { height: 44px; border-radius: 6px; padding: 0 18px; cursor: pointer; }
        .btn-primary { background: #99D930; color: #fff; border: 0; }
        .btn-outline { background: transparent; border: 1px solid #333; }
        .flash-success { background: #e8f8ea; color: #136b2c; padding: 12px 14px; border: 1px solid #b9e3c3; border-radius: 8px; margin-bottom: 16px; }
        .flash-error { background: #fff3f3; color: #8a1020; padding: 12px 14px; border: 1px solid #ffd2d6; border-radius: 8px; margin-bottom: 16px; }
        .help { font-size: 12px; color: #567; }
        @media (max-width: 720px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<?php include 'show-navbar.php'; show_navbar(); ?>

<div class="container">
    <h1>Create Partner</h1>

    <?php show_flash_and_clear(); ?>

    <form method="post" enctype="multipart/form-data" autocomplete="off">
        <!-- partner_id (auto) -->
        <div class="form-row">
            <label for="partner_id">Partner ID</label>
            <input type="text" id="partner_id" value="Auto-generated" disabled>
        </div>

        <div class="form-row">
            <label for="partner_name">Partner Name <span class="help">(required)</span></label>
            <input type="text" id="partner_name" name="partner_name" required
                   value="<?php echo htmlspecialchars($_POST['partner_name'] ?? ''); ?>">
        </div>

        <div class="form-row">
            <label for="partner_type">Partner Type <span class="help">(required)</span></label>
            <select id="partner_type" name="partner_type" required>
                <?php
                // Keep values consistent with your DB ENUM / inserts
                $opts = [
                    'school'            => 'School',
                    'library'           => 'Library',
                    'non_profit'        => 'Non-Profit',
                    'community_center'  => 'Community Center',
                    'other'             => 'Other'
                ];
                $sel = $_POST['partner_type'] ?? '';
                foreach ($opts as $val => $label) {
                    $selected = ($sel === $val) ? 'selected' : '';
                    echo "<option value=\"".htmlspecialchars($val)."\" $selected>".htmlspecialchars($label)."</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-row">
            <label for="logo_image">Logo Image</label>
            <input type="file" id="logo_image" name="logo_image" accept=".png,.jpg,.jpeg,.gif,.webp,.svg">
            <div class="help">Accepted: png, jpg, gif, webp, svg — up to 3 MB. Stored in <code>images/community_partners/</code>.</div>
        </div>

        <div class="form-row">
            <label for="website_url">Website URL</label>
            <input type="url" id="website_url" name="website_url"
                   value="<?php echo htmlspecialchars($_POST['website_url'] ?? ''); ?>"
                   placeholder="https://example.org">
        </div>

        <div class="form-row">
            <label for="impact_description">Impact Description</label>
            <textarea id="impact_description" name="impact_description" placeholder="Brief description of the partner’s impact"><?php
                echo htmlspecialchars($_POST['impact_description'] ?? '');
            ?></textarea>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-primary">Create Partner</button>
            <button type="button" class="btn btn-outline" onclick="location.href='admin_partners.php'">Cancel</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
