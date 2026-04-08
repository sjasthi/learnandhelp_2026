<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_configuration.php'; // provides $db (mysqli)

// ── Pagination ────────────────────────────────────────────────
$perPage = 10;
$page    = (isset($_GET['page']) && ctype_digit($_GET['page']) && $_GET['page'] > 0)
           ? (int)$_GET['page'] : 1;

$totalRows  = (int)$db->query("SELECT COUNT(*) AS cnt FROM classes WHERE Status = 'Approved'")
                       ->fetch_assoc()['cnt'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// ── Fetch classes (prepared statement) ───────────────────────
$stmt = $db->prepare("
    SELECT Class_Id, Class_Name, Description, Image_URL
    FROM classes
    WHERE Status = 'Approved'
    ORDER BY Class_Id
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
$classes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Classes – Learn and Help</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

    <style>
        :root { --accent: #99D930; }

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
            max-width: 1100px;
            margin: 40px auto 60px auto;
            padding: 0 20px;
        }

        /* ── Classes grid ── */
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 28px;
            margin-bottom: 36px;
        }
        @media (max-width: 700px) {
            .classes-grid { grid-template-columns: 1fr; }
        }

        /* ── Class card ── */
        .class-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
            display: flex;
            flex-direction: column;
        }
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 36px rgba(153,217,48,0.18);
        }
        .class-image {
            display: block;
            width: 100%;
            max-height: 240px;
            height: auto;
            object-fit: contain;
            background: #f8fbe9;
            padding: 8px;
            box-sizing: border-box;
            border-bottom: 1.5px solid #e8f5c8;
        }
        .class-info {
            padding: 22px;
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .class-info h3 {
            margin: 0 0 12px 0;
            font-size: 1.25em;
            font-weight: 900;
            color: #252525;
        }
        .class-desc {
            font-size: .95em;
            color: #555;
            line-height: 1.65;
            flex-grow: 1;
        }

        /* ── Empty state ── */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            color: #888;
            padding: 50px 0;
            font-size: 1.1em;
        }

        /* ── Pager ── */
        .pager {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 24px;
            font-weight: 700;
            margin-top: 8px;
        }
        .pager a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 20px;
            border-radius: 8px;
            background: #99d930;
            color: #274606;
            text-decoration: none;
            font-size: .95em;
            transition: background .18s;
            box-shadow: 0 2px 8px rgba(153,217,48,.25);
        }
        .pager a:hover { background: #85c220; }
        .pager .page-info { color: #555; font-size: .95em; }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<!-- ── Banner ── -->
<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Classes banner">
    <h1 class="banner-title">Our Classes</h1>
    <p class="banner-subtitle">Explore the classes and prerequisites</p>
</div>

<div class="page-wrap">

    <!-- ── Classes grid ── -->
    <div class="classes-grid">
        <?php if (!empty($classes)): ?>
            <?php foreach ($classes as $row):
                $name = htmlspecialchars($row['Class_Name']  ?? '', ENT_QUOTES);
                $desc = htmlspecialchars($row['Description'] ?? '', ENT_QUOTES);
                $img  = htmlspecialchars($row['Image_URL']   ?? '', ENT_QUOTES);
            ?>
            <div class="class-card">
                <img class="class-image"
                     src="<?= $img ?>"
                     alt="<?= $name ?>"
                     loading="lazy"
                     onerror="if(!this.dataset.fallback){this.dataset.fallback='y';this.src='images/class_pics/default.jpg';}">
                <div class="class-info">
                    <h3><?= $name ?></h3>
                    <p class="class-desc"><?= nl2br($desc) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">No classes found.</div>
        <?php endif; ?>
    </div>

    <!-- ── Pagination ── -->
    <?php if ($totalPages > 1): ?>
        <div class="pager">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">&laquo; Previous</a>
            <?php endif; ?>
            <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>