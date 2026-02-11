<?php
/* ───── session + DB connection ─────────────────────────────────── */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/db_configuration.php';   // provides $db
session_write_close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mission | Learn and Help</title>

  <!-- shared assets -->
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

  <style>:root { --accent:#99D930; }
.accent-text { color: var(--accent); }
/* Intro banner (from instructors.php) */
.intro-banner { background:#1a1a1a; color:#fff; text-align:center; padding:24px 20px 20px; }
.intro-banner h1 { font-family:'Montserrat',sans-serif; font-size:3rem; font-weight:900; margin:0 0 0px; }
.intro-banner h1 .accent-text { color:var(--accent); }
.intro-banner p { max-width:820px; margin:0 auto; font-size:1.5rem; line-height:1.65; }

    body{margin:0;font-family:'Montserrat',sans-serif;background:#f8f8f8;color:#252525;}

    /* content container */
    .content-wrapper{
      max-width:1100px;
      margin:60px auto;
      padding:0 20px;
      background:#fff;
      border-radius:18px;
      box-shadow:0 4px 24px rgba(0,0,0,.08);
    }

    .content-section{
      padding:40px;
    }

    .content-section h2{
      font-family:'Montserrat',sans-serif;
      font-size:2.2em;
      font-weight:900;
      margin:0 0 25px;
      color:#252525;
      text-align:center;
    }

    .content-section h3{
      font-family:'Montserrat',sans-serif;
      font-size:1.6em;
      font-weight:700;
      margin:30px 0 15px;
      color:#252525;
    }

    .content-section p{
      font-size:1.1em;
      line-height:1.7;
      color:#444;
      margin:0 0 20px;
    }

    .highlight-box{
      background:#f9f9f9;
      border-left:4px solid var(--accent);
      padding:25px;
      margin:30px 0;
      border-radius:0 8px 8px 0;
    }

    @media(max-width:700px){
      .content-section{padding:25px;}
      .content-section h2{font-size:1.8em;}
      .content-section h3{font-size:1.4em;}
    }
  </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<section class="intro-banner">
  <h1><span class="accent-text">Mission</span></h1>
  <p>Empowering Minds, Inspiring Generosity!</p>
</section>

<div class="content-wrapper">
  <div class="content-section">
    <h2>Learn and Help Mission Statement</h2>
    <p>Our mission is to provide accessible, high-quality educational resources that inspire learners to achieve their full potential and contribute positively to society.</p>

    <h3>Our Vision</h3>
    <p>Our vision is to create a world where every learner has access to the resources and support they need to succeed, regardless of their background or circumstances.</p>

    <h3>Core Values</h3>
    <p>Our core values include inclusivity, integrity, collaboration, and a commitment to excellence in education.</p>

    <h3>Our Commitment</h3>
    <p>Learn and Help exists to empower minds and inspire generosity. We teach computer science, programming, and computational thinking—primarily via Zoom—and we use 100% of the course proceeds to fund secular charitable projects such as school libraries, educational supplies, arts and culture initiatives, and scholarships. We aim to deploy the proceeds raised in a given academic year within that year or the next, discouraging accumulation and prioritizing impact. Our Board sets policy and safeguards alignment with our mission, while instructors and student leaders (LEAD Council) model excellence and service.</p>

    <h3>501(c)(3) Status</h3>
    <p>Incoporation of 'Learn and Help' as a Minnesota nonprofit corporation is work in process. We will also be preparing our federal filing to seek 501(c)(3) recognition from the IRS</p>

    <div class="highlight-box">
      <p><strong>Note:</strong> Our legal address is 5736 Pond Ct., Shoreview, MN 55126. Primary contact: +1&nbsp;651&nbsp;276&nbsp;4671 &middot; siva.jasthi@gmail.com. Designated beneficiaries upon dissolution: 50% to NRIVA Pustaka Mitra and 50% to ALAMBAMANA Foundation. EIN and IRS determination letter will be posted here once available.</p>
    </div>
  </div>
</div>

<?php $db->close(); include 'footer.php'; ?>
</body>
</html>