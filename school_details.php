<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

require 'db_configuration.php'; // provides $db (mysqli)

// ── Profile image helper ──────────────────────────────────────
function get_profile_image(int $id): string {
    $matches = glob('schools/' . $id . '/profile_image.*');
    return (count($matches) === 1) ? $matches[0] : 'images/admin_icons/school.png';
}

// ── Validate School_Id ────────────────────────────────────────
$School_Id = (isset($_GET['id']) && ctype_digit($_GET['id'])) ? intval($_GET['id']) : 0;
if (!$School_Id) {
    header('Location: schools.php');
    exit();
}

// ── Fetch school ──────────────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM schools WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $School_Id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    header('Location: schools.php');
    exit();
}

// ── Escape all fields ─────────────────────────────────────────
$school_name        = htmlspecialchars($row['name']                ?? '', ENT_QUOTES);
$school_type        = htmlspecialchars($row['type']                ?? '', ENT_QUOTES);
$school_category    = htmlspecialchars($row['category']            ?? '', ENT_QUOTES);
$grade_start        = htmlspecialchars($row['grade_level_start']   ?? '', ENT_QUOTES);
$grade_end          = htmlspecialchars($row['grade_level_end']     ?? '', ENT_QUOTES);
$enrollment         = htmlspecialchars($row['current_enrollment']  ?? '', ENT_QUOTES);
$address            = htmlspecialchars($row['address_text']        ?? '', ENT_QUOTES);
$state_name         = htmlspecialchars($row['state_name']          ?? '', ENT_QUOTES);
$state_code         = htmlspecialchars($row['state_code']          ?? '', ENT_QUOTES);
$pin_code           = htmlspecialchars($row['pin_code']            ?? '', ENT_QUOTES);
$contact_name       = htmlspecialchars($row['contact_name']        ?? '', ENT_QUOTES);
$contact_designation= htmlspecialchars($row['contact_designation'] ?? '', ENT_QUOTES);
$contact_phone      = htmlspecialchars($row['contact_phone']       ?? '', ENT_QUOTES);
$contact_email      = htmlspecialchars($row['contact_email']       ?? '', ENT_QUOTES);
$school_status      = htmlspecialchars($row['status']              ?? '', ENT_QUOTES);
$notes              = htmlspecialchars($row['notes']               ?? '', ENT_QUOTES);
$referenced_by      = htmlspecialchars($row['referenced_by']       ?? '', ENT_QUOTES);
$supported_by       = htmlspecialchars($row['supported_by']        ?? '', ENT_QUOTES);

$profile_image = get_profile_image($School_Id);
$time          = time();

// ── Media files (exclude profile image) ──────────────────────
$school_dir  = "schools/{$School_Id}/";
$media_files = [];
if (is_dir($school_dir)) {
    foreach (array_diff(scandir($school_dir), ['.', '..']) as $filename) {
        if (!str_contains($filename, 'profile_image')) {
            $media_files[] = $filename;
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
    <title><?= $school_name ?> – Learn and Help</title>
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
        .banner-wrapper img.banner-bg {
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
            padding: 28px 32px;
            margin-bottom: 24px;
        }
        .card h2 {
            margin: 0 0 20px 0;
            font-size: 1.15em;
            color: #274606;
            font-weight: 900;
            border-bottom: 1.5px solid #e8f5c8;
            padding-bottom: 10px;
        }

        /* ── Profile header ── */
        .school-header {
            display: flex;
            gap: 28px;
            align-items: flex-start;
            margin-bottom: 24px;
        }
        .school-header img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #cde8a0;
            flex-shrink: 0;
        }
        .school-header-info h1 {
            margin: 0 0 6px 0;
            font-size: 1.8em;
            font-weight: 900;
            color: #252525;
        }
        .school-header-info .school-meta {
            font-size: .95em;
            color: #666;
        }

        /* ── Detail grid ── */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 32px;
        }
        .detail-row {
            display: flex;
            gap: 8px;
            font-size: .95em;
            color: #333;
        }
        .detail-label {
            font-weight: 700;
            color: #274606;
            min-width: 140px;
            flex-shrink: 0;
        }

        /* ── Status badge ── */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .82em;
            font-weight: 700;
        }
        .badge-proposed  { background: #fff8e1; color: #f57f17; }
        .badge-active    { background: #edfae5; color: #2a6006; border: 1px solid #99d930; }
        .badge-completed { background: #e3f2fd; color: #1565c0; }
        .badge-default   { background: #f3f3f3; color: #555; }

        /* ── Notes box ── */
        .notes-box {
            background: #f8fbe9;
            border-left: 4px solid #99d930;
            border-radius: 0 8px 8px 0;
            padding: 14px 18px;
            font-size: .95em;
            color: #333;
            line-height: 1.7;
        }

        /* ── Media grid ── */
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
        }
        .media-tile img {
            width: 100%;
            height: 130px;
            object-fit: cover;
            border-radius: 8px;
            border: 1.5px solid #cde8a0;
            display: block;
        }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .school-header { flex-direction: column; }
            .detail-grid { grid-template-columns: 1fr; }
            .card { padding: 18px 14px; }
        }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<!-- ── Banner ── -->
<div class="banner-wrapper">
    <img class="banner-bg" src="images/banner_images/Schools/<?= file_exists('images/banner_images/Schools/') ? '' : '' ?>school_banner.jpg" alt="School banner"
         onerror="this.src='images/admin_icons/school.png'">
    <h1 class="banner-title">School Details</h1>
</div>

<div class="page-wrap">

    <a href="schools.php" class="back-link">&#8592; Back to Schools</a>

    <!-- ── School header ── -->
    <div class="card">
        <div class="school-header">
            <img src="<?= htmlspecialchars($profile_image) ?>?v=<?= $time ?>"
                 alt="<?= $school_name ?>"
                 onerror="this.src='images/admin_icons/school.png'">
            <div class="school-header-info">
                <h1><?= $school_name ?></h1>
                <p class="school-meta">
                    ID: <strong>#<?= intval($School_Id) ?></strong>
                    &nbsp;|&nbsp; <?= $school_type ?>
                    &nbsp;|&nbsp; <?= $state_name ?><?= $state_code ? " ({$state_code})" : '' ?>
                </p>
                <?php
                $badgeClass = match($row['status'] ?? '') {
                    'Proposed'  => 'badge-proposed',
                    'Active'    => 'badge-active',
                    'Completed' => 'badge-completed',
                    default     => 'badge-default',
                };
                ?>
                <span class="badge <?= $badgeClass ?>"><?= $school_status ?></span>
            </div>
        </div>

        <!-- ── Details grid ── -->
        <div class="detail-grid">
            <div class="detail-row">
                <span class="detail-label">Category</span>
                <span><?= $school_category ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Grade Range</span>
                <span><?= $grade_start ?> to <?= $grade_end ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Enrollment</span>
                <span><?= $enrollment ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Pin Code</span>
                <span><?= $pin_code ?></span>
            </div>
            <div class="detail-row" style="grid-column:1/-1;">
                <span class="detail-label">Address</span>
                <span><?= $address ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Referenced By</span>
                <span><?= $referenced_by ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Supported By</span>
                <span><?= $supported_by ?></span>
            </div>
        </div>
    </div>

    <!-- ── Contact ── -->
    <div class="card">
        <h2>📞 Contact Information</h2>
        <div class="detail-grid">
            <div class="detail-row">
                <span class="detail-label">Contact Name</span>
                <span><?= $contact_name ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Designation</span>
                <span><?= $contact_designation ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone</span>
                <span>
                    <?php if ($contact_phone): ?>
                        <a href="tel:<?= $contact_phone ?>" style="color:#4a8500;"><?= $contact_phone ?></a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email</span>
                <span>
                    <?php if ($contact_email): ?>
                        <a href="mailto:<?= $contact_email ?>" style="color:#4a8500;"><?= $contact_email ?></a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- ── Notes ── -->
    <?php if ($notes): ?>
    <div class="card">
        <h2>📝 Notes</h2>
        <div class="notes-box"><?= nl2br($notes) ?></div>
    </div>
    <?php endif; ?>

    <!-- ── Media ── -->
    <?php if (!empty($media_files)): ?>
    <div class="card">
        <h2>🖼️ School Photos</h2>
        <div class="media-grid">
            <?php foreach ($media_files as $filename):
                $safe = htmlspecialchars($filename, ENT_QUOTES);
            ?>
            <div class="media-tile">
                <img src="<?= $school_dir . $safe ?>"
                     alt="<?= $school_name ?> photo"
                     onerror="this.src='images/admin_icons/school.png'">
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>