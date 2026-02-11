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
  <title>Governing Board | Learn and Help</title>

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

    /* board members grid */
    .board-grid{
      display:grid;
      grid-template-columns:repeat(auto-fit, minmax(380px, 1fr));
      gap:30px;
      max-width:1200px;
      margin:60px auto;
      padding:0 20px;
    }

    .board-member{
      background:#fff;
      border-radius:18px;
      box-shadow:0 4px 24px rgba(0,0,0,.08);
      overflow:hidden;
      transition:transform .2s;
    }
    .board-member:hover{transform:translateY(-6px);}

    .member-photo{
      width:100%;
      height:280px;
      background:#f0f0f0;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#999;
      font-size:1.1em;
      border-bottom:1px solid #e0e0e0;
      overflow:hidden;
      position:relative;
    }

    .member-photo img {
      width:100%;
      height:100%;
      object-fit:cover;
      object-position:center;
      border-radius:0;
      display:block;
    }

    .member-info{
      padding:25px;
    }

    .member-name{
      font-family:'Montserrat',sans-serif;
      font-size:1.4em;
      font-weight:900;
      margin:0 0 8px;
      color:#252525;
    }

    .member-title{
      color:var(--accent);
      font-weight:700;
      font-size:1.1em;
      margin:0 0 15px;
    }

    .member-bio{
      color:#444;
      line-height:1.6;
      margin:0 0 15px;
      font-size:1em;
    }

    .member-credentials{
      font-size:0.9em;
      color:#666;
      font-style:italic;
    }

    .member-location {
      color:#888;
      font-size:0.9em;
      margin:5px 0 15px;
    }

    /* TBD positions styling */
    .tbd-position {
      opacity: 0.7;
      border: 2px dashed #ddd;
    }

    .tbd-position .member-photo {
      background: #f9f9f9;
      color: #bbb;
    }

    .tbd-content {
      color: #888;
      font-style: italic;
      text-align: center;
      padding: 20px;
    }

    /* intro section */
    .intro-section{
      max-width:1200px;
      margin:60px auto 40px;
      padding:0 20px;
      background:#fff;
      border-radius:18px;
      box-shadow:0 4px 24px rgba(0,0,0,.08);
    }

    .intro-content{
      padding:40px;
      text-align:center;
    }

    .intro-content h2{
      font-family:'Montserrat',sans-serif;
      font-size:2.2em;
      font-weight:900;
      margin:0 0 20px;
      color:#252525;
    }

    .intro-content p{
      font-size:1.1em;
      line-height:1.7;
      color:#444;
      margin:0;
    }

    /* Current vs Future Board sections */
    .section-header {
      max-width:1200px;
      margin:60px auto 30px;
      padding:0 20px;
      text-align:center;
    }

    .section-header h2 {
      font-family:'Montserrat',sans-serif;
      font-size:2em;
      font-weight:900;
      color:#252525;
      margin:0 0 10px;
    }

    .section-header p {
      color:#666;
      font-size:1.1em;
    }

    @media(max-width:700px){
      .board-grid{grid-template-columns:1fr; gap:20px;}
      .intro-content{padding:25px;}
      .intro-content h2{font-size:1.8em;}
      .member-info{padding:20px;}
    }
  </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<section class="intro-banner">
  <h1><span class="accent-text"> Governing Board</span></h1>
  <p>Meet the dedicated leaders guiding our mission to empower minds and inspire generosity.</p>
</section>

<div class="intro-section">
  <div class="intro-content">
    <h2>Board Leadership</h2>
    <p>Our Board of Directors brings together diverse expertise in education, technology, and community service. Each member is committed to Learn and Help's mission of empowering young minds through education while inspiring generosity through charitable initiatives. Together, they guide our strategic direction and ensure our programs make lasting impact in the lives of students and communities we serve.</p>
  </div>
</div>

<div class="section-header">
  <h2>Current Board Members</h2>
  <p>Active leadership driving our mission forward</p>
</div>

<div class="board-grid">
  <!-- President -->
  <div class="board-member">
    <div class="member-photo">
      <img src="images/board/siva_jasthi.png" alt="Dr. Siva Jasthi">
    </div>
    <div class="member-info">
      <h3 class="member-name">Dr. Siva Jasthi</h3>
      <div class="member-title">President & Chief Instructor</div>
      <div class="member-location">Minneapolis, MN, USA</div>
      <p class="member-bio">Dr. Siva Jasthi is a software consultant at Siemens PLM, adjunct faculty at Metropolitan State University, and founder of several educational and cultural initiatives. With a Ph.D. from IIT Delhi and nearly three decades in the software industry, he has taught CS classes to over 2000 students. He authored over 10,000 Telugu puzzles and founded Learn and Help, where students learn coding while funding school libraries in India. His books reflect his passion for education, culture, and giving back.</p>
      <div class="member-credentials">Ph.D. from IIT Delhi • Software Consultant • Adjunct Faculty</div>
    </div>
  </div>

  <!-- Director of Student Achievement -->
  <div class="board-member">
    <div class="member-photo">
      <img src="images/board/ishana_didwania.png" alt="Ishana Didwania">
    </div>
    <div class="member-info">
      <h3 class="member-name">Ishana Didwania</h3>
      <div class="member-title">Director of Student Achievement</div>
      <div class="member-location">West Lafayette, IN, USA</div>
      <p class="member-bio">Ishana is a sophomore at Purdue University majoring in Computer Science and a Machine Learning intern at Humana, where she works on an AI knowledge surfacing voice agent for home health clinicians. At Purdue, she conducts GenAI research and fundraises for Riley Hospital for Children through Purdue University Dance Marathon. She served as a teaching assistant for Learn and Help for two years, connecting individually with students to support their programming journey.</p>
      <div class="member-credentials">Computer Science Major, Purdue University • ML Intern at Humana</div>
    </div>
  </div>

  <!-- Director of Administration -->
  <div class="board-member">
    <div class="member-photo">
      <img src="images/board/bobby_kodali.png" alt="Venkata (Bobby) Kodali">
    </div>
    <div class="member-info">
      <h3 class="member-name">Venkata (Bobby) Kodali</h3>
      <div class="member-title">Director of Administration</div>
      <div class="member-location">Woodbury, MN, USA</div>
      <p class="member-bio">Bobby is a seasoned technology professional with decades of experience designing and delivering reliable, scalable software solutions across industries, including healthcare. As a freelance consultant, he helps organizations bridge the gap between business and technology, delivering solutions that make meaningful impact. A lifelong learner, Bobby is deeply committed to sharing knowledge in today's rapidly evolving information age, which aligns with Learn and Help's mission.</p>
      <div class="member-credentials">Technology Consultant • Healthcare Software Expert</div>
    </div>
  </div>

  <!-- Director of Helping -->
  <div class="board-member">
    <div class="member-photo">
      <img src="images/board/varma_alluri.png" alt="Varma Alluri">
    </div>
    <div class="member-info">
      <h3 class="member-name">Varma Alluri</h3>
      <div class="member-title">Director of Helping</div>
      <div class="member-location">Irvine, CA, USA</div>
      <p class="member-bio">Varma is a Data Architect with an Engineering Degree in Computer Science, originally from Eluru, Andhra Pradesh. He has a passion for teaching, particularly in Mathematics and Telugu, and co-founded the Alamabana Foundation during the pandemic, which now has over 120 youth volunteers aged 8 to 18, serving more than 500 meals weekly. He dreams of universal food security and learning opportunities for every child, inspiring his commitment to charitable initiatives.</p>
      <div class="member-credentials">Data Architect • Co-founder, Alamabana Foundation</div>
    </div>
  </div>

  <!-- Director of Technology -->
  <div class="board-member">
    <div class="member-photo">
      <img src="images/board/lakshi_aparna_logesetti.png" alt="Lakshmi Aparna Logisetti">
    </div>
    <div class="member-info">
      <h3 class="member-name">Lakshmi Aparna Logisetti</h3>
      <div class="member-title">Director of Technology</div>
      <div class="member-location">Location TBD</div>
      <p class="member-bio">Aparna is a Senior Principal Engineer at Optum with over 18 years of experience in AI/ML, big data engineering, and cloud solutions. She specializes in designing large-scale data platforms and machine learning models using Python, Scala, PySpark, and Azure Cloud. She holds dual master's degrees—M.S. in Data Science and M.A. in Kuchipudi Dance—and is a 200-hour certified Yoga Teacher Training practitioner, demonstrating her balance of technology innovation and cultural arts.</p>
      <div class="member-credentials">Senior Principal Engineer, Optum • M.S. Data Science • M.A. Kuchipudi Dance</div>
    </div>
  </div>

  <!-- Director of School Libraries -->
  <div class="board-member">
    <div class="member-photo">
      <img src="images/board/ca_prasad.png" alt="Dr. C.A. Prasad">
    </div>
    <div class="member-info">
      <h3 class="member-name">Dr. C.A. Prasad</h3>
      <div class="member-title">Director of School Libraries</div>
      <div class="member-location">Ongole, AP, India</div>
      <p class="member-bio">Dr. Prasad is a distinguished educationist and social activist with a lifelong commitment to schools, children, and transformative education. Recognized with the Lifetime Achievement Award by Indo-Canadian SSRN and Elsevier IJIEMR (2020), he promotes educational reforms and advocates for Nai Talim—learning through experience and self-reliance. Through his association with Jana Vignana Vedika, he has consistently advanced innovative approaches to learning and voiced student needs.</p>
      <div class="member-credentials">Lifetime Achievement Award Winner • Educational Reformist • Social Activist</div>
    </div>
  </div>
</div>

<div class="section-header">
  <h2>Future Board Positions</h2>
  <p>Planned expansion to support our growing mission</p>
</div>

<div class="board-grid">
  <!-- Vice President -->
  <div class="board-member tbd-position">
    <div class="member-photo">
      Position Open
    </div>
    <div class="member-info">
      <h3 class="member-name">TBD</h3>
      <div class="member-title">Vice President</div>
      <div class="tbd-content">
        <p>Assists the President and assumes duties in the President's absence.</p>
      </div>
    </div>
  </div>

  <!-- Director of Learning -->
  <div class="board-member tbd-position">
    <div class="member-photo">
      Position Open
    </div>
    <div class="member-info">
      <h3 class="member-name">TBD</h3>
      <div class="member-title">Director of Learning</div>
      <div class="tbd-content">
        <p>Oversees educational content quality, helps guide curriculum standards, supports instructors, and explores innovative approaches to student engagement.</p>
      </div>
    </div>
  </div>

  <!-- Director of Finance -->
  <div class="board-member tbd-position">
    <div class="member-photo">
      Position Open
    </div>
    <div class="member-info">
      <h3 class="member-name">TBD</h3>
      <div class="member-title">Director of Finance</div>
      <div class="tbd-content">
        <p>Manages finances, prepares budgets, and ensures compliance with financial obligations.</p>
      </div>
    </div>
  </div>

  <!-- Director of Outreach -->
  <div class="board-member tbd-position">
    <div class="member-photo">
      Position Open
    </div>
    <div class="member-info">
      <h3 class="member-name">TBD</h3>
      <div class="member-title">Director of Outreach</div>
      <div class="tbd-content">
        <p>Responsible for expanding Learn and Help's network and partnerships, promoting public awareness, and fostering relationships with educational and charitable communities.</p>
      </div>
    </div>
  </div>

  <!-- Director of Communications -->
  <div class="board-member tbd-position">
    <div class="member-photo">
      Position Open
    </div>
    <div class="member-info">
      <h3 class="member-name">TBD</h3>
      <div class="member-title">Director of Communications</div>
      <div class="tbd-content">
        <p>Manages Learn and Help's internal and external communications, including newsletters, website updates, and public relations.</p>
      </div>
    </div>
  </div>

  <!-- Director of Enrollment -->
  <div class="board-member tbd-position">
    <div class="member-photo">
      Position Open
    </div>
    <div class="member-info">
      <h3 class="member-name">TBD</h3>
      <div class="member-title">Director of Enrollment</div>
      <div class="tbd-content">
        <p>Manages the evaluation of prior learning and follows up with students and parents to ensure continuity of learning path.</p>
      </div>
    </div>
  </div>

  <!-- Director of Student Leadership -->
  <div class="board-member tbd-position">
    <div class="member-photo">
      Student Recruitment Open
    </div>
    <div class="member-info">
      <h3 class="member-name">Student Representative</h3>
      <div class="member-title">Director of Student Leadership (LEAD Council)</div>
      <div class="tbd-content">
        <p>We are seeking a student from our advanced classes—Python DS (Data Science) or Python ML (Machine Learning)—to represent the student body and contribute to shaping Learn and Help's direction. Students engaged in volunteering or non-profit activities who believe in our mission are encouraged to apply to join the LEAD Council.</p>
      </div>
    </div>
  </div>
</div>

<?php $db->close(); include 'footer.php'; ?>
</body>
</html>