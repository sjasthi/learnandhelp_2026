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

// ── Summary data helper (whitelist field names) ───────────────
function get_summary_data(mysqli $db, string $field): array {
    $allowed = ['state_name', 'category', 'supported_by', 'type'];
    if (!in_array($field, $allowed, true)) return [];

    $rows = [];
    $res  = $db->query("SELECT `{$field}`, COUNT(*) AS cnt FROM schools WHERE status != 'Proposed' GROUP BY `{$field}` ORDER BY cnt DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $key        = $row[$field] ?: 'Not Specified';
            $rows[$key] = (int)$row['cnt'];
        }
        $res->free();
    }
    return $rows;
}

// ── Inputs ────────────────────────────────────────────────────
$search     = trim($_GET['search']     ?? '');
$sort_order = $_GET['sort']            ?? 'id_desc';
$show_nriva = $_GET['show_nriva']      ?? '';
$show_pgnf  = $_GET['show_pgnf']       ?? '';

// ── Summary data ──────────────────────────────────────────────
$summary_by_state      = get_summary_data($db, 'state_name');
$summary_by_category   = get_summary_data($db, 'category');
$summary_by_supported  = get_summary_data($db, 'supported_by');

// ── Build schools query (prepared statement) ──────────────────
$where  = ["status != 'Proposed'"];
$params = [];
$types  = '';

if ($search !== '') {
    $like     = '%' . $search . '%';
    $where[]  = "(id LIKE ? OR name LIKE ? OR type LIKE ? OR state_name LIKE ? OR state_code LIKE ? OR address_text LIKE ? OR category LIKE ?)";
    $params   = array_merge($params, [$like, $like, $like, $like, $like, $like, $like]);
    $types   .= 'sssssss';
}
if ($show_nriva === 'true') {
    $where[]  = "supported_by = 'NRIVA'";
} elseif ($show_pgnf === 'true') {
    $where[]  = "supported_by = 'PGNF'";
}

$order = ($sort_order === 'id_asc') ? 'id ASC' : 'id DESC';
$sql   = "SELECT id, name, type, category, state_name, state_code, address_text, supported_by, contact_name
          FROM schools WHERE " . implode(' AND ', $where) . " ORDER BY $order";

$stmt = $db->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result  = $stmt->get_result();
$schools = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$time = time();
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Schools – Learn and Help</title>
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
        .banner-wrapper img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
        .banner-title {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            margin: 0;
            font-size: 3em;
            font-weight: 900;
            color: #99d930;
            text-shadow: 0 2px 16px rgba(0,0,0,0.44);
            letter-spacing: 1px;
            z-index: 2;
            white-space: nowrap;
        }
        .banner_slide { display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .banner_slide img { width: 100%; height: 100%; object-fit: cover; }
        .dots-container { position: absolute; bottom: 14px; left: 0; right: 0; text-align: center; z-index: 3; }
        .dot {
            cursor: pointer; height: 10px; width: 10px; margin: 0 3px;
            background: rgba(255,255,255,.5); border-radius: 50%; display: inline-block; transition: background .3s;
        }
        .dot.active, .dot:hover { background: #99d930; }

        /* ── Page wrapper ── */
        .page-wrap { max-width: 1300px; margin: 36px auto 60px auto; padding: 0 18px; }

        /* ── Card ── */
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 22px 26px;
            margin-bottom: 24px;
        }
        .card h2 { margin: 0 0 14px 0; font-size: 1.1em; font-weight: 900; color: #274606; }

        /* ── Search bar ── */
        .search-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 22px;
        }
        .search-bar input[type="search"] {
            flex: 1 1 260px;
            padding: 10px 13px;
            border: 1.5px solid #cde8a0;
            border-radius: 8px;
            font-size: .97em;
            font-family: 'Roboto', sans-serif;
            background: #f8fbe9;
            outline: none;
            transition: border-color .18s;
        }
        .search-bar input[type="search"]:focus { border-color: #99d930; }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
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
        .btn-green   { background: #99d930; color: #274606; box-shadow: 0 2px 8px rgba(153,217,48,.2); }
        .btn-green:hover   { background: #85c220; transform: translateY(-1px); }
        .btn-outline { background: #fff; color: #274606; border: 1.5px solid #99d930; }
        .btn-outline:hover { background: #f0fad8; }
        .btn-outline.active { background: #99d930; color: #274606; }

        /* ── Summary tables ── */
        .summary-section { display: none; margin-bottom: 24px; }
        .summary-section.show { display: block; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .summary-table { width: 100%; border-collapse: collapse; font-size: .9em; }
        .summary-table thead tr { background: #99d930; color: #274606; }
        .summary-table th { padding: 9px 12px; text-align: left; font-weight: 900; }
        .summary-table td { padding: 8px 12px; border-bottom: 1px solid #e8f5c8; }
        .summary-table tbody tr:hover td { background: #f4fce6; }

        /* ── School icons grid ── */
        .schools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 18px;
        }
        .school-tile {
            background: #fff;
            border: 1.5px solid #cde8a0;
            border-radius: 12px;
            padding: 14px 10px;
            text-align: center;
            transition: transform .2s, box-shadow .2s;
            position: relative;
        }
        .school-tile:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(153,217,48,0.18);
            border-color: #99d930;
        }
        .school-tile a { text-decoration: none; color: #252525; display: block; }
        .school-tile img { max-width: 80px; max-height: 80px; object-fit: cover; border-radius: 8px; margin-bottom: 8px; border: 1px solid #e0e0e0; }
        .school-tile .school-id   { font-size: .78em; color: #99d930; font-weight: 700; margin: 0; }
        .school-tile .school-name { font-size: .88em; color: #333; font-weight: 700; margin: 2px 0 0 0; line-height: 1.35; }

        /* ── Tooltip ── */
        .school-tile .tooltip {
            display: none;
            position: absolute;
            bottom: calc(100% + 6px);
            left: 50%;
            transform: translateX(-50%);
            background: #274606;
            color: #fff;
            padding: 5px 10px;
            border-radius: 6px;
            white-space: nowrap;
            font-size: .8em;
            z-index: 100;
            pointer-events: none;
        }
        .school-tile:hover .tooltip { display: block; }

        /* ── Empty state ── */
        .empty-state { text-align: center; padding: 50px 0; color: #888; font-size: 1.05em; }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .schools-grid { grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); }
        }
    </style>

    <script>
        // ── Slideshow ─────────────────────────────────────────
        var slideIndex = 1;
        function plusSlides(n)   { showSlides(slideIndex += n); }
        function currentSlide(n) { showSlides(slideIndex = n); }
        function showSlides(n) {
            var slides = document.getElementsByClassName('banner_slide');
            var dots   = document.getElementsByClassName('dot');
            if (!slides.length) return;
            if (n > slides.length) slideIndex = 1;
            if (n < 1)             slideIndex = slides.length;
            for (var i = 0; i < slides.length; i++) slides[i].style.display = 'none';
            for (var i = 0; i < dots.length;   i++) dots[i].classList.remove('active');
            slides[slideIndex - 1].style.display = 'block';
            if (dots[slideIndex - 1]) dots[slideIndex - 1].classList.add('active');
        }

        function toggleSummary() {
            var s   = document.getElementById('summary-section');
            var btn = document.getElementById('summary-btn');
            var showing = s.classList.toggle('show');
            btn.textContent = showing ? '▲ Hide Summary' : '▼ Summary';
        }

        document.addEventListener('DOMContentLoaded', function () {
            showSlides(slideIndex);

            var searchInput = document.getElementById('search-input');

            document.getElementById('search-button').addEventListener('click', function () {
                window.location.href = 'schools.php?search=' + encodeURIComponent(searchInput.value);
            });
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') window.location.href = 'schools.php?search=' + encodeURIComponent(searchInput.value);
            });

            document.getElementById('sort-btn').addEventListener('click', function () {
                var params = new URLSearchParams(window.location.search);
                params.set('sort', params.get('sort') === 'id_asc' ? 'id_desc' : 'id_asc');
                window.location.href = 'schools.php?' + params.toString();
            });

            document.getElementById('nriva-btn').addEventListener('click', function () {
                var params = new URLSearchParams(window.location.search);
                if (params.get('show_nriva') === 'true') { params.delete('show_nriva'); }
                else { params.set('show_nriva', 'true'); params.delete('show_pgnf'); }
                window.location.href = 'schools.php?' + params.toString();
            });

            document.getElementById('pgnf-btn').addEventListener('click', function () {
                var params = new URLSearchParams(window.location.search);
                if (params.get('show_pgnf') === 'true') { params.delete('show_pgnf'); }
                else { params.set('show_pgnf', 'true'); params.delete('show_nriva'); }
                window.location.href = 'schools.php?' + params.toString();
            });
        });
    </script>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<!-- ── Banner with slideshow ── -->
<div class="banner-wrapper">
    <?php
    $images_dir = './images/banner_images/Schools/';
    $images     = glob($images_dir . '*.{jpg,png}', GLOB_BRACE);
    if (empty($images)) {
        $fallback = './images/admin_icons/school.png';
        $images   = file_exists($fallback) ? [$fallback] : [];
    }
    foreach ($images as $image) {
        $safe = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
        echo "<div class='banner_slide'><img src='{$safe}' alt='Schools banner'></div>";
    }
    ?>
    <h1 class="banner-title">Schools</h1>
    <div class="dots-container">
        <?php foreach ($images as $i => $image): ?>
            <span class="dot" onclick="currentSlide(<?= $i + 1 ?>)"></span>
        <?php endforeach; ?>
    </div>
</div>

<div class="page-wrap">

    <!-- ── Search & filter bar ── -->
    <div class="search-bar">
        <input type="search" id="search-input"
               placeholder="Search by name, ID, state, type…"
               value="<?= htmlspecialchars($search) ?>">
        <button id="search-button" class="btn btn-green">🔍 Search</button>
        <button id="sort-btn"  class="btn btn-outline">⇅ Sort by ID</button>
        <button id="nriva-btn" class="btn btn-outline <?= $show_nriva === 'true' ? 'active' : '' ?>">NRIVA Schools</button>
        <button id="pgnf-btn"  class="btn btn-outline <?= $show_pgnf  === 'true' ? 'active' : '' ?>">PGNF Schools</button>
        <a href="school_location.php" class="btn btn-outline">📍 Google Pins</a>
        <button id="summary-btn" class="btn btn-outline" onclick="toggleSummary()">▼ Summary</button>
    </div>

    <!-- ── Summary tables ── -->
    <div id="summary-section" class="summary-section">
        <div class="summary-grid">
            <div class="card">
                <h2>By State</h2>
                <table class="summary-table">
                    <thead><tr><th>State</th><th>Count</th></tr></thead>
                    <tbody>
                    <?php foreach ($summary_by_state as $key => $cnt): ?>
                        <tr>
                            <td><?= htmlspecialchars($key) ?></td>
                            <td><?= intval($cnt) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <h2>By Type / Category</h2>
                <table class="summary-table">
                    <thead><tr><th>Category</th><th>Count</th></tr></thead>
                    <tbody>
                    <?php foreach ($summary_by_category as $key => $cnt): ?>
                        <tr>
                            <td><?= htmlspecialchars($key) ?></td>
                            <td><?= intval($cnt) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <h2>By Supported By</h2>
                <table class="summary-table">
                    <thead><tr><th>Supported By</th><th>Count</th></tr></thead>
                    <tbody>
                    <?php foreach ($summary_by_supported as $key => $cnt): ?>
                        <tr>
                            <td><?= htmlspecialchars($key) ?></td>
                            <td><?= intval($cnt) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Schools grid ── -->
    <?php if (empty($schools)): ?>
        <div class="empty-state">
            <?= $search
                ? 'No schools found matching "' . htmlspecialchars($search) . '".'
                : 'No schools found.' ?>
        </div>
    <?php else: ?>
        <div class="schools-grid">
            <?php foreach ($schools as $row):
                $id           = intval($row['id']);
                $name         = htmlspecialchars($row['name']         ?? '', ENT_QUOTES);
                $contact_name = htmlspecialchars($row['contact_name'] ?? 'Not Available', ENT_QUOTES);
                $profile_img  = get_profile_image($id);
                $safe_img     = htmlspecialchars($profile_img, ENT_QUOTES);
            ?>
            <div class="school-tile">
                <a href="school_details.php?id=<?= $id ?>">
                    <img src="<?= $safe_img ?>?v=<?= $time ?>"
                         alt="<?= $name ?>"
                         onerror="this.src='images/admin_icons/school.png'">
                    <p class="school-id">#<?= $id ?></p>
                    <p class="school-name"><?= $name ?></p>
                </a>
                <div class="tooltip">Contact: <?= $contact_name ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>