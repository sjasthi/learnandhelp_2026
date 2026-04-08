<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>LEAD Council – Learn and Help</title>
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
            max-width: 1000px;
            margin: 40px auto 60px auto;
            padding: 0 20px;
        }

        /* ── Card ── */
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 36px 40px;
            margin-bottom: 28px;
        }
        .card h2 {
            font-size: 1.7em;
            font-weight: 900;
            margin: 0 0 20px 0;
            color: #252525;
            text-align: center;
        }
        .card h3 {
            font-size: 1.3em;
            font-weight: 900;
            margin: 0 0 20px 0;
            color: #274606;
            text-align: center;
        }
        .card p {
            font-size: 1.05em;
            line-height: 1.75;
            color: #444;
            margin: 0 0 18px 0;
        }
        .card p:last-child { margin-bottom: 0; }

        /* ── Signature ── */
        .signature {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1.5px solid #e8f5c8;
            font-style: italic;
            color: #666;
            line-height: 1.7;
        }
        .signature strong { color: #252525; font-style: normal; }
        .signature a { color: #4a8500; text-decoration: none; font-weight: 700; }
        .signature a:hover { color: #99d930; }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: #888;
            font-size: 1.1em;
        }
        .empty-state span {
            display: block;
            font-size: 2.5em;
            margin-bottom: 14px;
        }

        /* ── LEAD badge ── */
        .lead-badge {
            display: inline-block;
            background: #99d930;
            color: #274606;
            font-weight: 900;
            font-size: .85em;
            padding: 4px 14px;
            border-radius: 20px;
            margin-bottom: 18px;
            letter-spacing: .5px;
        }

        @media (max-width: 680px) {
            .card { padding: 22px 16px; }
            .card h2 { font-size: 1.4em; }
            .banner-title { font-size: 2em; }
            .banner-subtitle { font-size: .85em; }
        }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<!-- ── Banner ── -->
<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="LEAD Council banner">
    <h1 class="banner-title">LEAD Council</h1>
    <p class="banner-subtitle">Learn and Help Emerging Advisors and Directors</p>
</div>

<div class="page-wrap">

    <!-- ── Formation letter ── -->
    <div class="card">
        <div style="text-align:center;margin-bottom:18px;">
            <span class="lead-badge">LEAD Council Formation</span>
        </div>
        <h2>A Message from Our Founder</h2>

        <p><strong>Dear Students and Parents,</strong></p>

        <p>I am excited to share that we are creating new opportunities for students to take on leadership roles within Learn and Help. To this end, I am forming the LEAD Council (Learn and Help Emerging Advisors and Directors) — a group of students from our advanced classes who will serve as role models and help shape the future of our program.</p>

        <p>From this council, one student will be selected to serve on the Board as the <strong>Director of Student Leadership</strong>, representing the student voice in our decision-making.</p>

        <p>We are seeking a student from our advanced classes — Python DS (Data Science) or Python ML (Machine Learning) — to take on this important responsibility.</p>

        <p>Students who are actively engaged in volunteering or non-profit activities, and who share our mission of <em>Empowering Minds and Inspiring Generosity</em>, are especially encouraged to apply.</p>

        <p>This is a meaningful opportunity to contribute beyond the classroom, gain leadership experience, and make a difference in our community.</p>

        <p>Thank you for your continued support.</p>

        <div class="signature">
            <strong>Warm regards,</strong><br>
            Siva Jasthi, Ph.D.<br>
            Founder, President, and Chief Instructor<br>
            <a href="http://www.learnandhelp.com">www.learnandhelp.com</a>
        </div>
    </div>

    <!-- ── Council Members ── -->
    <div class="card">
        <h3>🎓 LEAD Council Members</h3>
        <div class="empty-state">
            <span>🌱</span>
            The LEAD Council is currently being formed.<br>
            Council members will be displayed here once selected.
        </div>
    </div>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>