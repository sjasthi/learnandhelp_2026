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
  <title>Lead Council | Learn and Help</title>

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

    /* council members grid */
    .council-grid{
      display:grid;
      grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));
      gap:30px;
      max-width:1100px;
      margin:60px auto;
      padding:0 20px;
    }

    .council-member{
      background:#fff;
      border-radius:18px;
      box-shadow:0 4px 24px rgba(0,0,0,.08);
      overflow:hidden;
      transition:transform .2s;
    }
    .council-member:hover{transform:translateY(-6px);}

    .member-photo{
      width:100%;
      height:250px;
      background:#f0f0f0;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#999;
      font-size:1.1em;
      border-bottom:1px solid #e0e0e0;
    }

    .member-info{
      padding:25px;
    }

    .member-name{
      font-family:'Montserrat',sans-serif;
      font-size:1.3em;
      font-weight:900;
      margin:0 0 8px;
      color:#252525;
    }

    .member-role{
      color:var(--accent);
      font-weight:700;
      font-size:1em;
      margin:0 0 12px;
    }

    .member-department{
      background:#f9f9f9;
      color:#666;
      font-size:0.9em;
      padding:4px 12px;
      border-radius:15px;
      display:inline-block;
      margin:0 0 15px;
    }

    .member-bio{
      color:#444;
      line-height:1.6;
      margin:0 0 15px;
      font-size:0.95em;
    }

    .member-contact{
      font-size:0.9em;
      color:#666;
    }

    /* intro section */
    .intro-section{
      max-width:1100px;
      margin:60px auto 40px;
      padding:0 20px;
      background:#fff;
      border-radius:18px;
      box-shadow:0 4px 24px rgba(0,0,0,.08);
    }

    .intro-content{
      padding:40px;
      text-align:left;
    }

    .intro-content h2{
      font-family:'Montserrat',sans-serif;
      font-size:2.2em;
      font-weight:900;
      margin:0 0 20px;
      color:#252525;
      text-align:center;
    }

    .intro-content p{
      font-size:1.1em;
      line-height:1.7;
      color:#444;
      margin:0 0 20px;
    }

    .intro-content .signature{
      margin-top:30px;
      font-style:italic;
      color:#666;
    }

    .intro-content .signature strong{
      color:#252525;
    }

    /* department sections */
    .department-section{
      max-width:1100px;
      margin:40px auto;
      padding:0 20px;
    }

    .department-title{
      background:#fff;
      padding:20px;
      border-radius:18px;
      box-shadow:0 4px 24px rgba(0,0,0,.08);
      text-align:center;
      margin-bottom:30px;
    }

    .department-title h3{
      font-family:'Montserrat',sans-serif;
      font-size:1.8em;
      font-weight:700;
      margin:0;
      color:#252525;
    }

    @media(max-width:700px){
      .council-grid{grid-template-columns:1fr;}
      .intro-content{padding:25px;}
      .intro-content h2{font-size:1.8em;}
      .department-title h3{font-size:1.5em;}
    }
  </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<section class="intro-banner">
  <h1><span class="accent-text">LEAD Council</span></h1>
  <p>Learn and Help Emergening Advisors and Directors (LEAD)</p>
</section>

<div class="intro-section">
  <div class="intro-content">
    <h2>LEAD Council Formation</h2>
    <p><strong>Dear Students and Parents,</strong></p>
    
    <p>I am excited to share that we are creating new opportunities for students to take on leadership roles within Learn and Help. To this end, I am forming the LEAD Council (Learn and Help Emerging Advisors and Directors)—a group of students from our advanced classes who will serve as role models and help shape the future of our program.</p>
    
    <p>From this council, one student will be selected to serve on the Board as the Director of Student Leadership, representing the student voice in our decision-making.</p>
    
    <p><strong>Director of Student Leadership (LEAD Council)</strong> will be a representative from the LEAD Council and serve as the official student voice on the Board.</p>
    
    <p>We are seeking a student from our advanced classes—Python DS (Data Science) or Python ML (Machine Learning)—to take on this important responsibility.</p>
    
    <p>Students who are actively engaged in volunteering or non-profit activities, and who share our mission of <em>Empowering Minds and Inspiring Generosity</em>, are especially encouraged to apply.</p>
    
    <p>This is a meaningful opportunity to contribute beyond the classroom, gain leadership experience, and make a difference in our community.</p>
    
    <p>Thank you for your continued support.</p>
    
    <div class="signature">
      <p><strong>Warm regards,<br>
      Siva Jasthi, Ph.D.<br>
      Founder, President, and Chief Instructor<br>
      <a href="http://www.learnandhelp.com" style="color: var(--accent);">www.learnandhelp.com</a></strong></p>
    </div>
  </div>
</div>

<!-- Executive Leadership -->
<div class="department-section">
  <div class="department-title">
    <h3>LEAD Council Members</h3>
  </div>
  
  <div style="text-align: center; padding: 40px; background: #fff; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.08); color: #666;">
    <p style="font-size: 1.2em; margin: 0;">The LEAD Council is currently being formed. Council members will be displayed here once selected.</p>
  </div>
</div>

</body>
</html>