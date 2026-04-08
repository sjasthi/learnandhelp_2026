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
    <title>Mission – Learn and Help</title>
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
            font-size: 1.1em;
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
            padding: 40px;
            margin-bottom: 28px;
        }
        .card h2 {
            font-size: 1.9em;
            font-weight: 900;
            margin: 0 0 22px 0;
            color: #252525;
            text-align: center;
        }
        .card h3 {
            font-size: 1.3em;
            font-weight: 900;
            margin: 28px 0 12px 0;
            color: #274606;
        }
        .card p {
            font-size: 1.05em;
            line-height: 1.75;
            color: #444;
            margin: 0 0 18px 0;
        }
        .card p:last-child { margin-bottom: 0; }

        /* ── Highlight box ── */
        .highlight-box {
            background: #f8fbe9;
            border-left: 4px solid #99d930;
            border-radius: 0 10px 10px 0;
            padding: 20px 24px;
            margin: 24px 0 0 0;
        }
        .highlight-box p {
            margin: 0;
            font-size: 1em;
            color: #274606;
            line-height: 1.7;
        }

        /* ── Section divider ── */
        .section-divider {
            width: 60px;
            height: 4px;
            background: #99d930;
            border-radius: 2px;
            margin: 0 auto 28px auto;
        }

        @media (max-width: 680px) {
            .card { padding: 22px 16px; }
            .card h2 { font-size: 1.5em; }
            .card h3 { font-size: 1.1em; }
            .banner-title { font-size: 2em; }
            .banner-subtitle { font-size: .85em; }
        }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<!-- ── Banner ── -->
<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Mission banner">
    <h1 class="banner-title">Our Mission</h1>
    <p class="banner-subtitle">Empowering Minds, Inspiring Generosity!</p>
</div>

<div class="page-wrap">

    <div class="card">
        <h2>Learn and Help Mission Statement</h2>
        <div class="section-divider"></div>

        <p>Our mission is to provide accessible, high-quality educational resources that inspire learners to achieve their full potential and contribute positively to society.</p>

        <h3>Our Vision</h3>
        <p>Our vision is to create a world where every learner has access to the resources and support they need to succeed, regardless of their background or circumstances.</p>

        <h3>Core Values</h3>
        <p>Our core values include inclusivity, integrity, collaboration, and a commitment to excellence in education.</p>

        <h3>Our Commitment</h3>
        <p>Learn and Help exists to empower minds and inspire generosity. We teach computer science, programming, and computational thinking — primarily via Zoom — and we use 100% of the course proceeds to fund secular charitable projects such as school libraries, educational supplies, arts and culture initiatives, and scholarships. We aim to deploy the proceeds raised in a given academic year within that year or the next, discouraging accumulation and prioritizing impact. Our Board sets policy and safeguards alignment with our mission, while instructors and student leaders (LEAD Council) model excellence and service.</p>

        <h3>501(c)(3) Status</h3>
        <p>Incorporation of "Learn and Help" as a Minnesota nonprofit corporation is a work in progress. We will also be preparing our federal filing to seek 501(c)(3) recognition from the IRS.</p>

        <div class="highlight-box">
            <p>
                <strong>Note:</strong> Our legal address is 5736 Pond Ct., Shoreview, MN 55126.
                Primary contact: +1&nbsp;651&nbsp;276&nbsp;4671 &middot;
                <a href="mailto:siva.jasthi@gmail.com" style="color:#4a8500;">siva.jasthi@gmail.com</a>.
                Designated beneficiaries upon dissolution: 50% to NRIVA Pustaka Mitra and 50% to ALAMBAMANA Foundation.
                EIN and IRS determination letter will be posted here once available.
            </p>
        </div>
    </div>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>