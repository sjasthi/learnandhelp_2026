<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/db_configuration.php';

$sql = "
    SELECT o.offering_id, o.day_of_week, o.start_time, o.end_time,
           o.start_date, o.end_date,
           c.Class_Name, c.Description, c.Image_URL
    FROM offerings o
    JOIN classes c ON o.Class_Id = c.Class_Id
    WHERE c.Status = 'Approved'
";
$result = $db->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Enroll | Learn and Help</title>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Montserrat', sans-serif;
      background: #f8f8f8;
      color: #252525;
    }
    .banner-wrapper {
      width: 100vw;
      margin-left: -50vw;
      margin-right: -50vw;
      position: relative;
      left: 50%;
      height: 200px;
      background: #fff;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .banner-wrapper img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    @media (max-width: 700px) {
      .banner-wrapper { height: 150px; }
    }
    .page-title {
      font-size: 3em;
      font-weight: 700;
      text-align: center;
      margin: 60px 0 30px;
    }
    .classes-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 30px;
      max-width: 1100px;
      margin: 60px auto;
      padding: 0 20px;
    }
    @media (max-width: 700px) {
      .classes-grid { grid-template-columns: 1fr; }
    }
    .class-card {
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 24px rgba(0,0,0,.08);
      overflow: hidden;
      transition: transform 0.2s;
      display: flex;
      flex-direction: column;
      text-align: center;
    }
    .class-card:hover {
      transform: translateY(-6px);
    }
 .class-image{
  display:block;
  width:100%;
  max-height:256px;      /* optional cap; remove if you want no limit */
  height:auto;           /* let the image define its height */
  object-fit:contain;    /* show the whole image */
  background:#fff5e6;    /* subtle backdrop behind transparent/letterboxed areas */
  padding:8px;           /* small breathing room inside the card */
  border-bottom:1px solid #f0f0f0;
}
    .class-info {
      padding: 22px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .session-info, .date-range {
      font-size: 0.95em;
      color: #555;
      margin: 4px 0;
    }
    .class-name {
      margin-top: 20px;
      font-weight: 900;
      font-size: 1.3em;
      color: #252525;
    }
    .enroll-btn {
      margin-top: 12px;
      background: #99d930;
      color: #252525;
      font-weight: bold;
      padding: 10px 24px;
      border-radius: 8px;
      font-size: 1em;
      text-decoration: none;
      transition: background 0.2s;
      display: inline-block;
    }
    .enroll-btn:hover {
      background: #7fc41c;
    }
  </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<div class="banner-wrapper">
  <img src="images/banner_images/Classes/enroll3.jpg" alt="Enroll banner">
</div>

<h1 class="page-title">Enroll</h1>
<h2> Classes start on 6th Sept;<br> Late registration till 21st Sept;<br> Registration will be closed on 22nd Sept.</h2></h1>


<div class="classes-grid">
<?php
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $className = htmlspecialchars($row['Class_Name']);
    $desc = htmlspecialchars($row['Description']);
    $image = htmlspecialchars($row['Image_URL'] ?: 'images/class_pics/default.jpg');
    $day = htmlspecialchars($row['day_of_week']);
    $offering_id = (int)$row['offering_id'];

    $startTime = DateTime::createFromFormat('H:i:s', $row['start_time'])->format('g:i A');
    $endTime = DateTime::createFromFormat('H:i:s', $row['end_time'])->format('g:i A');
    $startDate = new DateTime($row['start_date']);
    $endDate = new DateTime($row['end_date']);
    $dateRange = $startDate->format('M j, Y') . ' – ' . $endDate->format('M j, Y');
    $timeText = "$day ($startTime – $endTime CST)";
?>
  <div class="class-card">
    <img class="class-image"
         src="<?= $image ?>"
         alt="<?= $className ?>"
         onerror="this.onerror=null;this.src='images/class_pics/default.jpg';">
    <div class="class-info">
      <div class="session-info"><?= $timeText ?></div>
      <div class="date-range"><?= $dateRange ?></div>
      <div class="class-name"><?= $className ?></div>
      <a class="enroll-btn" href="registration_form.php?offering_id=<?= $offering_id ?>">Enroll Now</a>
    </div>
  </div>
<?php
  }
} else {
  echo '<p style="text-align: center; font-size: 1.2em; color: #777;">No available classes to enroll at this time.</p>';
}
$result?->free();
$db->close();
?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
