<?php
session_start();
require_once __DIR__ . '/db_configuration.php';
include 'show-navbar.php';


$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0
    ? intval($_GET['page'])
    : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;


$sort = $_GET['sort'] ?? 'name';
$allowedSorts = ['name', 'classes'];
if (!in_array($sort, $allowedSorts)) $sort = 'name';


$sql = "
SELECT
    r.Student_Name,
    r.Student_Email,
    r.Student_Phone_Number,
    MAX(r.Student_Photo) AS Student_Photo,
    MAX(r.Has_Python_Cert) AS Has_Python_Cert,
    GROUP_CONCAT(DISTINCT c.Class_Name ORDER BY c.Class_Name SEPARATOR ', ') AS classes_taken
FROM registrations r
LEFT JOIN classes c ON r.Class_Id = c.Class_Id
GROUP BY r.Student_Email
ORDER BY " . ($sort === 'classes' ? "classes_taken ASC, r.Student_Name ASC" : "r.Student_Name ASC") . "
LIMIT $perPage OFFSET $offset";

$result = $db->query($sql);


$countQuery = $db->query("SELECT COUNT(DISTINCT Student_Email) AS total FROM registrations");
$totalStudents = ($countQuery && $row = $countQuery->fetch_assoc()) ? (int)$row['total'] : 0;
$totalPages = ceil($totalStudents / $perPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Students | Learn and Help</title>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Roboto&display=swap" rel="stylesheet">
  <link href="css/main.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Montserrat', 'Roboto', sans-serif;
      background: #f8f8f8;
      margin: 0;
      padding: 0 10px 40px;
      color: #252525;
    }
    .page-heading {
      margin-top: 60px;
      text-align: center;
    }
    .page-heading h1 {
      font-size: 2.5em;
      font-weight: 700;
      margin-bottom: 30px;
    }
    .sort-controls {
      text-align: center;
      margin-bottom: 25px;
    }
    .sort-controls a {
      margin: 0 10px;
      color: #252525;
      text-decoration: none;
      font-weight: 700;
    }
    .sort-controls a.active {
      color: #99d930;
    }
    .students-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }
    .student-tile {
      background: #fff;
      border-radius: 15px;
      padding: 20px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      text-align: center;
    }
    .student-tile img {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 50%;
      border: 3px solid #ccc;
      margin-bottom: 15px;
    }
    .student-name {
      font-weight: 700;
      font-size: 1.2em;
      margin-bottom: 5px;
      color: #252525;
    }
    .student-email,
    .student-phone,
    .classes-taken,
    .python-cert {
      font-size: 0.9em;
      color: #444;
      margin-bottom: 5px;
    }
    .classes-taken strong {
      color: #99d930;
    }
    .python-cert strong {
      color: #99d930;
    }
    .pagination {
      text-align: center;
      margin: 40px 0 0;
    }
    .pagination a,
    .pagination span {
      margin: 0 10px;
      color: #252525;
      text-decoration: none;
      font-weight: 700;
    }
    .pagination .current {
      color: #99d930;
    }
  </style>
</head>
<body>
<?php show_navbar(); ?>

<div class="page-heading">
  <h1>Registered Students</h1>
</div>

<div class="sort-controls">
  Sort by:
  <a href="?sort=name&page=1" class="<?= $sort === 'name' ? 'active' : '' ?>">Student Name</a> |
  <a href="?sort=classes&page=1" class="<?= $sort === 'classes' ? 'active' : '' ?>">Classes Taken</a>
</div>

<div class="students-grid">
<?php
if ($result && $result->num_rows > 0) {
  while ($student = $result->fetch_assoc()) {
    $name     = htmlspecialchars($student['Student_Name']);
    $email    = htmlspecialchars($student['Student_Email']);
    $phone    = htmlspecialchars($student['Student_Phone_Number']);
    $hasCert  = ($student['Has_Python_Cert'] === 'Yes') ? 'Yes' : 'No';
    $classes  = $student['classes_taken'] ? htmlspecialchars($student['classes_taken']) : '—';
    $photo    = trim($student['Student_Photo']) !== '' ? str_replace('\\', '/', $student['Student_Photo']) : 'images/banner_images/Classes/avatar.jpg';
?>
  <div class="student-tile" title="Student: <?= $name ?>">
    <img src="<?= htmlspecialchars($photo) ?>" alt="Profile picture of <?= $name ?>">
    <div class="student-name"><?= $name ?></div>
    <div class="student-email"><?= $email ?></div>
    <div class="student-phone"><?= $phone ?></div>
    <?php if ($sort === 'classes'): ?>
      <div class="classes-taken">
        <strong>Classes Taken:</strong><br>
        <?= nl2br(str_replace(', ', "\n", $classes)) ?>
      </div>
      <div class="python-cert">Python Certification: <strong><?= $hasCert ?></strong></div>
    <?php endif; ?>
  </div>
<?php
  }
} else {
  echo '<p style="text-align:center; color:#666;">No registered students found.</p>';
}
$result?->free();
$db->close();
?>
</div>

<div class="pagination">
  <?php if ($page > 1): ?>
    <a href="?sort=<?= urlencode($sort) ?>&page=<?= $page - 1 ?>">« Prev</a>
  <?php endif; ?>
  <span class="current">Page <?= $page ?> of <?= $totalPages ?></span>
  <?php if ($page < $totalPages): ?>
    <a href="?sort=<?= urlencode($sort) ?>&page=<?= $page + 1 ?>">Next »</a>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
