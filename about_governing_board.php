<?php

/* ───── session + DB connection ─────────────────────────────────── */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/db_configuration.php';

// Fetch active board members
$active_members = [];
$tbd_members = [];

$sql = "SELECT * FROM board_members ORDER BY sort_order ASC, id ASC";
$result = $db->query($sql);

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

<!-- ── Active Board Members ── -->
<?php if (!empty($active_members)): ?>
<div class="section-header">
  <h2>Current Board Members</h2>
  <p>Active leadership driving our mission forward</p>
</div>

<div class="board-grid">
  <?php foreach ($active_members as $member): ?>
  <div class="board-member">
    <div class="member-photo">
      <?php if (!empty($member['image'])): ?>
        <img src="<?= htmlspecialchars($member['image']) ?>" alt="<?= htmlspecialchars($member['name']) ?>">
      <?php else: ?>
        No Photo Available
      <?php endif; ?>
    </div>
    <div class="member-info">
      <h3 class="member-name"><?= htmlspecialchars($member['name']) ?></h3>
      <div class="member-title"><?= htmlspecialchars($member['role']) ?></div>
      <?php if (!empty($member['bio'])): ?>
        <p class="member-bio"><?= htmlspecialchars($member['bio']) ?></p>
      <?php endif; ?>
      <?php if (!empty($member['notes'])): ?>
        <div class="member-credentials"><?= htmlspecialchars($member['notes']) ?></div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Inactive / TBD Board Members ── -->
<?php if (!empty($tbd_members)): ?>
<div class="section-header">
  <h2>Future Board Positions</h2>
  <p>Planned expansion to support our growing mission</p>
</div>

<div class="board-grid">
  <?php foreach ($tbd_members as $member): ?>
  <div class="board-member tbd-position">
    <div class="member-photo">
      <?php if (!empty($member['image'])): ?>
        <img src="<?= htmlspecialchars($member['image']) ?>" alt="<?= htmlspecialchars($member['name']) ?>">
      <?php else: ?>
        Position Open
      <?php endif; ?>
    </div>
    <div class="member-info">
      <h3 class="member-name"><?= htmlspecialchars($member['name']) ?></h3>
      <div class="member-title"><?= htmlspecialchars($member['role']) ?></div>
      <?php if (!empty($member['bio'])): ?>
        <div class="tbd-content"><p><?= htmlspecialchars($member['bio']) ?></p></div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php $db->close(); include 'footer.php'; ?>
</body>
</html>