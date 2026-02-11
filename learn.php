<?php
include 'show-navbar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Learn | Learn and Help</title>
    <meta charset="UTF-8">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <style>
        body{margin:0;font-family:'Montserrat',sans-serif;background:#f8f8f8;color:#252525;}

        .page-title{
          font-family:'Montserrat',sans-serif;
          font-size:3em;
          font-weight:700;
          text-align:center;
          margin:60px 0 30px;
          color:#252525;
        }

        .container { max-width: 900px; margin: 0 auto 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.09); padding: 40px 32px; }
        .section { margin-bottom: 46px; }
        .section h2 { color: #99D930; font-size: 1.35em; margin-bottom: 16px; font-weight: 900; }
        .academics-links { list-style: none; padding: 0; display: flex; flex-wrap: wrap; gap: 24px; justify-content: center; }
        .academics-links li { flex: 1 1 220px; min-width: 220px; background: #f8f8f8; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.05); margin-bottom: 18px; }
        .academics-links a {
            display: block;
            padding: 22px 16px;
            color: #005b6b;
            font-size: 1.1em;
            font-weight: 700;
            text-decoration: none;
            border-radius: 10px;
            transition: background 0.2s, color 0.2s;
            text-align: center;
        }
        .academics-links a:hover { background: #99D930; color: #252525; }
        .section p, .section ol { font-size: 1.07em; line-height: 1.6; }
        .section ol { padding-left: 20px; }
        @media (max-width: 700px) {
            .container { padding: 10px; }
            .academics-links { flex-direction: column; gap: 0; }
        }
    </style>
</head>
<body>
<?php show_navbar(); ?>

<h1 class="page-title">Learn</h1>

<div class="container">
    <div class="section">
        <h2>Classes</h2>
        <ul class="academics-links">
            <li><a href="classes.php">View All Classes</a></li>
        </ul>
    </div>

    <div class="section">
        <h2>Instructors</h2>
        <ul class="academics-links">
            <li><a href="instructors.php">Meet Our Instructors</a></li>
        </ul>
    </div>

    <div class="section">
        <h2>Students</h2>
        <ul class="academics-links">
            <li><a href="students.php">Student Resources</a></li>
        </ul>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
