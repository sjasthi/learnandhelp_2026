<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'db_configuration.php'; // provides $db (mysqli)

// ── Fetch instructors ─────────────────────────────────────────
$result      = $db->query("SELECT * FROM instructor ORDER BY instructor_ID ASC");
$instructors = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/png">
    <title>Instructors – Learn &amp; Help</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

    <style>
        :root { --accent: #99D930; --card-shadow: 0 4px 6px rgba(0,0,0,.1); }

        body {
            font-family: 'Roboto', sans-serif;
            background: #fafafa;
            margin: 0;
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
        .list-container {
            max-width: 1000px;
            margin: 1.5rem auto;
            padding: 0 20px;
        }

        /* ── Search bar ── */
        .search-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin: 10px 0 24px;
            flex-wrap: wrap;
        }
        .search-bar #instructor-search {
            flex: 0 0 auto;
            width: auto;
            max-width: 420px;
            padding: .6rem 1rem;
            font-size: 1rem;
            border: 1.5px solid #cde8a0;
            border-radius: 8px;
            background: #f8fbe9;
            outline: none;
            font-family: 'Roboto', sans-serif;
            transition: border-color .18s;
        }
        .search-bar #instructor-search:focus { border-color: #99d930; }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: .9rem;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .18s, transform .12s;
        }
        .btn-green   { background: #99d930; color: #274606; box-shadow: 0 2px 8px rgba(153,217,48,.25); }
        .btn-green:hover   { background: #85c220; transform: translateY(-1px); color: #274606; }
        .btn-outline { background: #fff; color: #274606; border: 2px solid #99d930; }
        .btn-outline:hover { background: #f0fad8; }
        .btn-edit    { background: #f0fad8; color: #274606; border: 1.5px solid #99d930; padding: 5px 12px; font-size: .8rem; }
        .btn-edit:hover    { background: #e2f5b2; }
        .btn-danger  { background: #fff0f0; color: #c00; border: 1.5px solid #f99; padding: 5px 12px; font-size: .8rem; }
        .btn-danger:hover  { background: #ffe0e0; }

        /* ── Back button ── */
        .back-btn {
            background: #99d930;
            color: #274606;
            border: none;
            padding: .7rem 1.4rem;
            font-size: .97rem;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 20px;
            font-family: 'Roboto', sans-serif;
            font-weight: 700;
            transition: background .18s;
            box-shadow: 0 2px 8px rgba(153,217,48,.25);
        }
        .back-btn:hover { background: #85c220; }

        /* ── Instructor cards ── */
        .cards-wrapper {
            display: flex;
            flex-direction: column;
            gap: 40px;
        }
        .instructor-card {
            display: flex;
            flex-direction: row;
            background: #fff;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: transform .25s;
        }
        .instructor-card:hover { transform: translateY(-4px); }
        .card-img { width: 45%; flex-shrink: 0; overflow: hidden; }
        .card-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .card-body {
            width: 55%;
            padding: 24px 28px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .card-body h3 { margin: 0 0 12px; font-size: 1.6rem; font-weight: 900; }
        .instructor-name {
            color: #274606;
            cursor: pointer;
            text-decoration: none;
            transition: color .2s;
        }
        .instructor-name:hover { color: #99d930; }
        .card-body .bio {
            font-size: .85em;
            line-height: 1.6;
            color: #333;
            white-space: pre-wrap;
        }
        .admin-controls { margin-top: 14px; display: flex; gap: 8px; flex-wrap: wrap; }

        /* ── Individual instructor view ── */
        .individual-view { display: none; }
        .individual-instructor {
            background: #fff;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .individual-header {
            display: flex;
            flex-direction: row;
            min-height: 300px;
        }
        .individual-img { width: 40%; flex-shrink: 0; overflow: hidden; }
        .individual-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .individual-info {
            width: 60%;
            padding: 32px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .individual-info h2 {
            font-size: 2.5rem;
            font-weight: 900;
            margin: 0 0 20px;
            color: #1a1a1a;
        }
        .individual-bio {
            font-size: 1.1rem;
            line-height: 1.7;
            color: #333;
            white-space: pre-wrap;
        }

        @media (max-width: 768px) {
            .instructor-card { flex-direction: column; }
            .card-img, .card-body { width: 100%; }
            .card-img { height: 150px; }
            .individual-header { flex-direction: column; min-height: auto; }
            .individual-img, .individual-info { width: 100%; }
            .individual-img { height: 200px; }
            .individual-info { padding: 24px; }
            .individual-info h2 { font-size: 2rem; }
            .banner-title { font-size: 2em; }
        }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<!-- ── Banner ── -->
<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Instructors banner">
    <h1 class="banner-title">Meet Our Instructors</h1>
</div>

<div class="list-container">

    <!-- Back button (hidden by default) -->
    <button id="back-to-all" class="back-btn" style="display:none;"
            onclick="showAllInstructors()">
        &#8592; Back to All Instructors
    </button>

    <!-- ── All Instructors View ── -->
    <div id="all-instructors-view">
        <div class="search-bar">
            <input id="instructor-search" type="text" placeholder="Search instructors…">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="add_instructor.php" class="btn btn-green">➕ Add Instructor</a>
            <?php endif; ?>
        </div>

        <div class="cards-wrapper" id="cards-wrapper">
            <?php foreach ($instructors as $row):
                $id    = intval($row['instructor_ID']);
                $first = htmlspecialchars($row['First_name'] ?? '', ENT_QUOTES);
                $last  = htmlspecialchars($row['Last_name']  ?? '', ENT_QUOTES);
                $bio   = htmlspecialchars($row['Bio_data']   ?? '', ENT_QUOTES);
                $img   = htmlspecialchars(trim(explode(',', $row['Image'] ?? '')[0]), ENT_QUOTES);
            ?>
            <article class="instructor-card"
                     data-filter="<?= strtolower("$first $last " . ($row['Bio_data'] ?? '')) ?>"
                     data-id="<?= $id ?>"
                     data-firstname="<?= $first ?>"
                     data-lastname="<?= $last ?>"
                     data-bio="<?= $bio ?>"
                     data-image="<?= $img ?>">

                <div class="card-img">
                    <img src="<?= $img ?>" alt="<?= "$first $last" ?>">
                </div>
                <div class="card-body">
                    <div>
                        <h3>
                            <a href="instructors.php?id=<?= $id ?>"
                               class="instructor-name"
                               onclick="showInstructorFromCard(this); return false;">
                                <?= "$first $last" ?>
                            </a>
                        </h3>
                        <div class="bio"><?= nl2br($bio) ?></div>
                    </div>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <div class="admin-controls">
                        <a href="admin_edit_instructors.php?instructor_ID=<?= $id ?>"
                           class="btn btn-edit">✏️ Edit</a>
                        <form action="admin_delete_instructor.php" method="POST"
                              onsubmit="return confirm('Delete this instructor? This cannot be undone.');"
                              style="display:inline;">
                            <input type="hidden" name="instructor_ID" value="<?= $id ?>">
                            <button type="submit" name="delete" class="btn btn-danger">🗑 Delete</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>

            <?php if (empty($instructors)): ?>
                <p style="text-align:center;color:#888;padding:40px 0;">
                    No instructors found.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Individual Instructor View ── -->
    <div id="individual-view" class="individual-view">
        <div class="individual-instructor">
            <div class="individual-header">
                <div class="individual-img">
                    <img id="individual-img" src="" alt="">
                </div>
                <div class="individual-info">
                    <h2 id="individual-name"></h2>
                    <div id="individual-bio" class="individual-bio"></div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /list-container -->

<?php include 'footer.php'; ?>

<script>
    var qInput        = document.getElementById('instructor-search');
    var cards         = document.querySelectorAll('.instructor-card');
    var allView       = document.getElementById('all-instructors-view');
    var individualView = document.getElementById('individual-view');
    var backBtn       = document.getElementById('back-to-all');

    // Search
    qInput.addEventListener('input', function (e) {
        var q = e.target.value.toLowerCase().trim();
        cards.forEach(function (c) {
            c.style.display = c.dataset.filter.includes(q) ? '' : 'none';
        });
    });

    // Show individual instructor from card data
    function showInstructorFromCard(linkEl) {
        var card      = linkEl.closest('.instructor-card');
        var id        = card.dataset.id;
        var firstName = card.dataset.firstname;
        var lastName  = card.dataset.lastname;
        var bio       = card.dataset.bio;
        var image     = card.dataset.image;

        window.history.pushState({ instructorId: id }, '', 'instructors.php?id=' + id);

        document.getElementById('individual-name').textContent = firstName + ' ' + lastName;
        document.getElementById('individual-bio').innerHTML    = bio.replace(/\n/g, '<br>');
        document.getElementById('individual-img').src          = image;
        document.getElementById('individual-img').alt          = firstName + ' ' + lastName;

        allView.style.display        = 'none';
        individualView.style.display = 'block';
        backBtn.style.display        = 'inline-flex';
        window.scrollTo(0, 0);
    }

    // Show all instructors
    function showAllInstructors() {
        window.history.pushState({}, '', 'instructors.php');
        allView.style.display        = 'block';
        individualView.style.display = 'none';
        backBtn.style.display        = 'none';
        qInput.value = '';
        cards.forEach(function (c) { c.style.display = ''; });
        window.scrollTo(0, 0);
    }

    // Helper to show instructor by card element
    function showInstructorFromCardEl(card) {
        document.getElementById('individual-name').textContent = card.dataset.firstname + ' ' + card.dataset.lastname;
        document.getElementById('individual-bio').innerHTML    = card.dataset.bio.replace(/\n/g, '<br>');
        document.getElementById('individual-img').src          = card.dataset.image;
        document.getElementById('individual-img').alt          = card.dataset.firstname + ' ' + card.dataset.lastname;
        allView.style.display        = 'none';
        individualView.style.display = 'block';
        backBtn.style.display        = 'inline-flex';
    }

    // Handle browser back/forward
    window.addEventListener('popstate', function () {
        var id = new URLSearchParams(window.location.search).get('id');
        if (id) {
            var card = document.querySelector('[data-id="' + id + '"]');
            if (card) showInstructorFromCardEl(card);
        } else {
            showAllInstructors();
        }
    });

    // On page load — check for ?id= in URL
    document.addEventListener('DOMContentLoaded', function () {
        var id = new URLSearchParams(window.location.search).get('id');
        if (id) {
            var card = document.querySelector('[data-id="' + id + '"]');
            if (card) showInstructorFromCardEl(card);
        }
    });
</script>
</body>
</html>