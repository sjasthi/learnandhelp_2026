<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_configuration.php'; // provides $db (mysqli)

// ── Fetch board members ───────────────────────────────────────
$active_members = [];
$tbd_members    = [];

$result = $db->query("SELECT * FROM board_members ORDER BY sort_order ASC, id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['status'] === 'active') {
            $active_members[] = $row;
        } else {
            $tbd_members[] = $row;
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
    <title>Governing Board – Learn and Help</title>
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
            max-width: 1200px;
            margin: 40px auto 60px auto;
            padding: 0 20px;
        }

        /* ── Admin back link ── */
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

        /* ── Intro card ── */
        .intro-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 36px 40px;
            margin-bottom: 40px;
            text-align: center;
        }
        .intro-card h2 {
            font-size: 1.8em;
            font-weight: 900;
            margin: 0 0 16px 0;
            color: #252525;
        }
        .intro-card p {
            font-size: 1.05em;
            line-height: 1.75;
            color: #444;
            max-width: 860px;
            margin: 0 auto;
        }

        /* ── Section heading ── */
        .section-heading {
            text-align: center;
            margin: 40px 0 28px 0;
        }
        .section-heading h2 {
            font-size: 1.8em;
            font-weight: 900;
            color: #252525;
            margin: 0 0 8px 0;
        }
        .section-heading p {
            color: #888;
            font-size: 1em;
            margin: 0;
        }
        .section-heading .divider {
            width: 60px;
            height: 4px;
            background: #99d930;
            border-radius: 2px;
            margin: 14px auto 0;
        }

        /* ── Board grid ── */
        .board-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 28px;
            margin-bottom: 20px;
        }

        /* ── Board member card ── */
        .board-member {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
        }
        .board-member:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 36px rgba(153,217,48,0.18);
        }

        /* ── TBD cards ── */
        .board-member.tbd {
            border: 2px dashed #cde8a0;
            opacity: .75;
        }

        /* ── Member photo ── */
        .member-photo {
            width: 100%;
            height: 260px;
            background: #f4fce6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            font-size: 1em;
            overflow: hidden;
            position: relative;
            border-bottom: 2px solid #e8f5c8;
        }
        .member-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }
        .member-photo .no-photo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: #bbb;
            font-size: .9em;
        }
        .member-photo .no-photo span {
            font-size: 2.5em;
        }

        /* ── Member info ── */
        .member-info { padding: 22px 24px; }

        .member-name {
            font-size: 1.3em;
            font-weight: 900;
            margin: 0 0 6px 0;
            color: #252525;
        }
        .member-title {
            color: #99d930;
            font-weight: 700;
            font-size: 1em;
            margin: 0 0 14px 0;
        }
        .member-bio {
            color: #444;
            line-height: 1.65;
            font-size: .97em;
            margin: 0 0 12px 0;
        }
        .member-credentials {
            font-size: .88em;
            color: #666;
            font-style: italic;
            border-top: 1px solid #e8f5c8;
            padding-top: 10px;
            margin-top: 10px;
        }

        @media (max-width: 700px) {
            .board-grid { grid-template-columns: 1fr; gap: 20px; }
            .intro-card { padding: 22px 16px; }
            .intro-card h2 { font-size: 1.4em; }
            .member-photo { height: 200px; }
            .banner-title { font-size: 2em; }
        }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<!-- ── Banner ── -->
<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Governing Board banner">
    <h1 class="banner-title">Governing Board</h1>
    <p class="banner-subtitle">Dedicated leaders guiding our mission to empower minds and inspire generosity</p>
</div>

<div class="page-wrap">

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="administration.php" class="back-link">&#8592; Back to Administration</a>
    <?php endif; ?>

    <!-- ── Intro ── -->
    <div class="intro-card">
        <h2>Board Leadership</h2>
        <p>Our Board of Directors brings together diverse expertise in education, technology, and community service. Each member is committed to Learn and Help's mission of empowering young minds through education while inspiring generosity through charitable initiatives. Together, they guide our strategic direction and ensure our programs make lasting impact in the lives of students and communities we serve.</p>
    </div>

    <!-- ── Active Members ── -->
    <?php if (!empty($active_members)): ?>
        <div class="section-heading">
            <h2>Current Board Members</h2>
            <p>Active leadership driving our mission forward</p>
            <div class="divider"></div>
        </div>

        <div class="board-grid">
            <?php foreach ($active_members as $member): ?>
            <div class="board-member">
                <div class="member-photo">
                    <?php if (!empty($member['image'])): ?>
                        <img src="<?= htmlspecialchars($member['image']) ?>"
                             alt="<?= htmlspecialchars($member['name']) ?>">
                    <?php else: ?>
                        <div class="no-photo">
                            <span>👤</span>
                            No Photo Available
                        </div>
                    <?php endif; ?>
                </div>
                <div class="member-info">
                    <h3 class="member-name"><?= htmlspecialchars($member['name']) ?></h3>
                    <?php if (!empty($member['role'])): ?>
                        <div class="member-title"><?= htmlspecialchars($member['role']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($member['bio'])): ?>
                        <p class="member-bio"><?= nl2br(htmlspecialchars($member['bio'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($member['notes'])): ?>
                        <div class="member-credentials"><?= htmlspecialchars($member['notes']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ── TBD / Inactive Members ── -->
    <?php if (!empty($tbd_members)): ?>
        <div class="section-heading">
            <h2>Future Board Positions</h2>
            <p>Planned expansion to support our growing mission</p>
            <div class="divider"></div>
        </div>

        <div class="board-grid">
            <?php foreach ($tbd_members as $member): ?>
            <div class="board-member tbd">
                <div class="member-photo">
                    <?php if (!empty($member['image'])): ?>
                        <img src="<?= htmlspecialchars($member['image']) ?>"
                             alt="<?= htmlspecialchars($member['name']) ?>">
                    <?php else: ?>
                        <div class="no-photo">
                            <span>🔲</span>
                            Position Open
                        </div>
                    <?php endif; ?>
                </div>
                <div class="member-info">
                    <h3 class="member-name"><?= htmlspecialchars($member['name']) ?></h3>
                    <?php if (!empty($member['role'])): ?>
                        <div class="member-title"><?= htmlspecialchars($member['role']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($member['bio'])): ?>
                        <p class="member-bio" style="font-style:italic;color:#888;">
                            <?= nl2br(htmlspecialchars($member['bio'])) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($active_members) && empty($tbd_members)): ?>
        <div style="text-align:center;color:#888;padding:60px 0;font-size:1.1em;">
            No board members found.
        </div>
    <?php endif; ?>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>