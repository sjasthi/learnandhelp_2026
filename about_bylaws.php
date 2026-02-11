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
  <title>Bylaws | Learn and Help</title>

  <!-- shared assets -->
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

  <style>:root { --accent:#99D930; }
.accent-text { color: var(--accent); }
/* Intro banner (from instructors.php) */
.intro-banner { background:#1a1a1a; color:#fff; text-align:center; padding:24px 20px 20px; position:relative; }
.intro-banner h1 { font-family:'Montserrat',sans-serif; font-size:3rem; font-weight:900; margin:0 0 0px; }
.intro-banner h1 .accent-text { color:var(--accent); }
.intro-banner p { max-width:820px; margin:0 auto; font-size:1.5rem; line-height:1.65; }

    body{margin:0;font-family:'Montserrat',sans-serif;background:#f8f8f8;color:#252525;position:relative;}

    /* Draft watermark */
    .draft-watermark {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-45deg);
      font-size: 8rem;
      font-weight: 900;
      color: rgba(153, 217, 48, 0.1);
      z-index: 1;
      pointer-events: none;
      user-select: none;
      font-family: 'Montserrat', sans-serif;
    }

    /* Draft notice banner */
    .draft-notice {
      background: #fff3cd;
      border: 1px solid #ffeaa7;
      color: #856404;
      padding: 15px;
      text-align: center;
      font-weight: 600;
      margin-bottom: 20px;
      border-radius: 8px;
    }

    /* content container */
    .content-wrapper{
      max-width:1100px;
      margin:60px auto;
      padding:0 20px;
      background:#fff;
      border-radius:18px;
      box-shadow:0 4px 24px rgba(0,0,0,.08);
      position: relative;
      z-index: 2;
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

    .content-section h4{
      font-family:'Montserrat',sans-serif;
      font-size:1.3em;
      font-weight:600;
      margin:25px 0 10px;
      color:#444;
    }

    .content-section p{
      font-size:1.1em;
      line-height:1.7;
      color:#444;
      margin:0 0 20px;
    }

    .content-section ul {
      font-size:1.1em;
      line-height:1.7;
      color:#444;
      margin:0 0 20px;
    }

    .content-section li {
      margin-bottom: 8px;
    }

    .article-number{
      color:var(--accent);
      font-weight:700;
    }

    .bylaws-toc{
      background:#f9f9f9;
      border:1px solid #e0e0e0;
      border-radius:8px;
      padding:25px;
      margin:30px 0;
    }

    .bylaws-toc h3{
      margin-top:0;
      color:#252525;
    }

    .bylaws-toc ul{
      list-style:none;
      padding-left:0;
    }

    .bylaws-toc li{
      margin:8px 0;
      padding-left:20px;
      position:relative;
    }

    .bylaws-toc li:before{
      content:"▶";
      color:var(--accent);
      position:absolute;
      left:0;
    }

    .contact-info {
      background: #f0f8ff;
      border: 1px solid #b3d9ff;
      border-radius: 8px;
      padding: 20px;
      margin: 20px 0;
    }

    .board-member {
      background: #f9f9f9;
      border-left: 4px solid var(--accent);
      padding: 15px;
      margin: 10px 0;
    }

    @media(max-width:700px){
      .content-section{padding:25px;}
      .content-section h2{font-size:1.8em;}
      .content-section h3{font-size:1.4em;}
      .draft-watermark{font-size:4rem;}
    }
  </style>
</head>
<body>

<div class="draft-watermark">DRAFT</div>

<?php include 'show-navbar.php'; show_navbar(); ?>

<section class="intro-banner">
  <h1><span class="accent-text">Bylaws</span></h1>
  <p>The governing rules and procedures of Learn and Help.</p>
</section>

<div class="content-wrapper">
  <div class="content-section">
    <div class="draft-notice">
      <strong>DRAFT VERSION:</strong> These bylaws are currently in draft form (Version D) and subject to revision.
    </div>

    <h2>Learn and Help Bylaws</h2>
    <p style="text-align:center;font-style:italic;color:#666;">Version D - Draft | September 2025</p>
    
    <div class="bylaws-toc">
      <h3>Table of Contents</h3>
      <ul>
        <li>Article I - Name and Purpose</li>
        <li>Article II - Membership</li>
        <li>Article III - Classes</li>
        <li>Article IV - Causes</li>
        <li>Article V - Board of Directors</li>
        <li>Article VI - Meetings</li>
        <li>Article VII - Officers</li>
        <li>Article VIII - Finances</li>
        <li>Article IX - Amendments</li>
      </ul>
    </div>

    <h3><span class="article-number">Article I</span> - Name and Purpose</h3>
    
    <h4>Section 1.1 - Name</h4>
    <p>The name of the organization shall be "Learn and Help."</p>
    
    <h4>Section 1.2 - Registered Address</h4>
    <p>The registered address of Learn and Help is 5736 Pond Ct., Shoreview, MN 55126.</p>

    <div class="contact-info">
      <h4>Contact Information</h4>
      <p>For all organizational inquiries, please contact Learn and Help at +1 651 276 4671 or siva.jasthi@gmail.com.</p>
    </div>
    
    <h4>Section 1.3 - Purpose</h4>
    <p>The organization is organized as a nonprofit corporation under the laws of Minnesota:</p>
    <ul>
      <li>Its main goal is to educate students in computer science, programming, and computational thinking, and related courses, conducted via Zoom.</li>
      <li>Instructors shall determine the course curriculum, schedule, fees, delivery logistics, and any other relevant educational content and structure for each course.</li>
      <li>Instructors or Teaching Assistants are volunteers and do not receive any compensation.</li>
      <li>After deducting any nominal course delivery expenses – such as ZOOM, google tools, etc.), remaining proceeds are used to support charitable projects such as setting up school libraries, providing school supplies, and funding art projects.</li>
      <li>Primary Funding Source: Course fees paid by students are the primary funding source for the organization's charitable initiatives.</li>
      <li>Proceeds Usage: The organization strives to spend all proceeds collected in an academic year within that year or the following year to support various causes; accumulating funds is discouraged.</li>
    </ul>

    <h3><span class="article-number">Article II</span> - Membership</h3>
    
    <h4>Section 2.1 - Non-Membership Corporation</h4>
    <p>Learn and Help shall have no formal membership structure.</p>
    
    <h4>Section 2.2 - Organizational Roles</h4>
    <ul>
      <li><strong>Board:</strong> These members formulate the governing policies and update the bylaws as required.</li>
      <li><strong>Instructors:</strong> Individuals responsible for offering classes. Instructors are not paid for their roles.</li>
      <li><strong>Teaching Assistants:</strong> Typically middle and high school students who assist instructors with instructional duties and operational tasks. Teaching Assistants for a class are selected by the instructors teaching that class.</li>
      <li><strong>Students:</strong> Typically middle and high school students. At the discretion of instructors, adult learners and parents may also be permitted to register for classes.</li>
      <li><strong>Parents:</strong> Parents support their students and are encouraged to passively learn alongside them.</li>
      <li><strong>Industry Experts:</strong> The organization seeks the help of industry experts for guest talks and to review students' projects and presentations. Any honorarium the board decides to extend to the expert is applied to a cause as chosen by the invited industry expert.</li>
      <li><strong>LEAD Council (Learn and Help Emerging Advisors & Directors):</strong> The LEAD Council shall consist of senior students who have successfully completed at least two courses with Learn and Help and are currently enrolled in the program. Members of the LEAD Council serve in dual roles as Advisors and Directors, acting as role models for their peers.</li>
    </ul>

    <h3><span class="article-number">Article III</span> - Classes</h3>
    <ul>
      <li>Class offerings are typically in the domain of computer science, programming, and computational thinking.</li>
      <li>Based on the availability of instructors and requests from the students, courses in other domains (economics, AP classes) will be offered. Though instructors can propose new offerings, they must be approved by the board.</li>
      <li>The instructor can set the minimum enrollment needed for the class to continue.</li>
      <li>Regular session classes are run from September to May.</li>
      <li>Summer classes may be offered in June, July, and August.</li>
    </ul>

    <h3><span class="article-number">Article IV</span> - Causes</h3>
    
    <h4>Section 4.1 - Supported Projects</h4>
    <p>Learn and Help supports various charitable projects, including establishing school libraries in India, providing educational supplies, promoting the arts, preserving and sharing Indian cultural heritage, and offering scholarships.</p>
    
    <h4>Section 4.2 - Cause Selection</h4>
    <p>Instructors, Teaching Assistants, parents, and students are encouraged to recommend causes for organizational support. All recommendations must be secular and are subject to review and approval by the Board of Directors.</p>

    <h3><span class="article-number">Article V</span> - Board of Directors</h3>
    
    <h4>Section 5.1 - General Powers</h4>
    <p>The Board of Directors shall manage the affairs of the organization, establish policies, and ensure alignment with Learn and Help's mission to empower minds and inspire generosity through educational and charitable activities.</p>
    
    <h4>Section 5.2 - Number of Directors</h4>
    <p>The Board of Directors shall initially consist of four members, expanding to seven by 2027:</p>
    
    <div class="board-member">
      <strong>President:</strong> Presides over meetings, sets agendas, and represents the organization publicly. Until a Director of Finance is appointed, the President shall oversee financial responsibilities.
    </div>
    
    <div class="board-member">
      <strong>Director of Student Achievement:</strong> Responsible for encouraging, guiding, and supporting students in pursuing certifications, competitions, awards, and other forms of academic and professional recognition.
    </div>
    
    <div class="board-member">
      <strong>Director of School Libraries:</strong> Oversees the selection, planning, execution, establishment, and sustainment of school libraries supported by Learn and Help.
    </div>
    
    <div class="board-member">
      <strong>Director of Administration:</strong> Responsible for developing operating procedures, documenting board meeting minutes, maintaining and organizing organizational records, and managing board communication.
    </div>
    
    <div class="board-member">
      <strong>Director of Helping:</strong> Manages charitable initiatives, supports the selection and oversight of causes, and ensures alignment of projects with the organization's values of generosity and service.
    </div>

    <h4>Section 5.3 - Terms and Elections</h4>
    <ul>
      <li><strong>Term Length:</strong> Director of Student Leadership (LEAD Council) shall serve a term of one year. All other board members shall serve a term of three years.</li>
      <li><strong>Term Limits:</strong> Board members may serve a maximum of two consecutive terms (totaling up to six years). After two consecutive terms, directors must step down for at least one year before being eligible for re-election.</li>
      <li><strong>Board Vacancies:</strong> In the event of a vacancy, a new director may be elected by a majority vote of the remaining board members to complete the term of the departing director.</li>
    </ul>

    <h3><span class="article-number">Article VI</span> - Meetings</h3>
    <ul>
      <li><strong>Annual Meetings:</strong> An annual meeting of the Board shall be held on Zoom at a designated time to discuss organizational progress, review recommended causes, and set strategic directions.</li>
      <li><strong>Special Meetings:</strong> Special meetings may be called as necessary and will also be conducted on Zoom.</li>
      <li><strong>Notice of Meetings:</strong> Notice of each meeting, including the Zoom link, shall be given to each director not less than one week in advance.</li>
    </ul>

    <h3><span class="article-number">Article VII</span> - Officers</h3>
    
    <h4>Current Officers</h4>
    <div class="board-member">
      <strong>President:</strong> Siva Jasthi (Minneapolis, MN, USA)
    </div>
    <div class="board-member">
      <strong>Director of Student Achievements:</strong> Ishana Didwania (West Lafayette, IN, USA)
    </div>
    <div class="board-member">
      <strong>Director of School Libraries:</strong> Dr. C.A.Prasad (Ongole, AP, India)
    </div>
    <div class="board-member">
      <strong>Director of Helping:</strong> Varma Alluri (Irvine, CA, USA)
    </div>
    <div class="board-member">
      <strong>Director of Administration:</strong> Venkat (Bobby) Kodali (Woodbury, MN, USA)
    </div>

    <h3><span class="article-number">Article VIII</span> - Finances</h3>
    <ul>
      <li><strong>Use of Funds:</strong> All funds shall be used solely to support Learn and Help's mission of (a) empowering minds and (b) inspiring generosity.</li>
      <li><strong>Course Fees:</strong> The fee for each class is decided by the instructor and approved by the Board.</li>
      <li><strong>Payments:</strong> Board Members, and Instructors are not paid for their services. Teaching Assistants may be paid at the discretion of the instructors.</li>
      <li><strong>Donations:</strong> The organization accepts donations from parents and other organizations and can honor the requests of donors to support causes, provided those causes align with the organization's mission.</li>
      <li><strong>Usage of Proceeds:</strong> The organization strives to spend all proceeds collected in an academic year within that year or the following year to support various causes; accumulating funds is discouraged.</li>
      <li><strong>Selection of Charitable Causes:</strong> Education, Arts, Culture and Empowerment are some areas of focus for charitable causes.</li>
    </ul>

    <h4>Section 8.1 - Dissolution</h4>
    <p>If Learn and Help is dissolved for any reason, 50% of remaining assets shall go to NRIVA (https://nriva.org/pustakamitra), and 50% to the ALAMBAMANA Foundation (https://www.alambanafoundation.org/)</p>

    <h3><span class="article-number">Article IX</span> - Amendments</h3>
    <p>These bylaws may be amended or repealed by a majority vote of the Board at any duly constituted meeting, held on Zoom or in any other mutually agreed format.</p>

    <div class="bylaws-toc">
      <p><strong>Note:</strong> This is a draft version of the bylaws and is subject to revision and approval by the Board of Directors.</p>
    </div>
  </div>
</div>

<?php $db->close(); include 'footer.php'; ?>
</body>
</html>