<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
  session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Home | Learn and Help</title>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Montserrat', sans-serif;
      background: #f8f8f8;
      color: #252525;
    }
    .carousel-container {
      position: relative;
      width: 100vw;
      left: 50%;
      right: 50%;
      margin-left: -50vw;
      margin-right: -50vw;
      overflow: hidden;
      box-shadow: 0 4px 24px 0 rgba(0,0,0,0.08);
      background: #fff;
    }
    .carousel-track {
      display: flex;
      transition: transform 0.5s ease;
      /* No fixed width needed, flex handles it */
    }
    .carousel-slide {
      min-width: 100vw;
      box-sizing: border-box;
    }
    .carousel-slide img {
      width: 100vw;
      height: 500px;
      object-fit: cover;
      display: block;
    }
    .carousel-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(153, 217, 48, 0.85);
      color: #252525;
      border: none;
      font-size: 2em;
      padding: 0 16px;
      border-radius: 50%;
      cursor: pointer;
      z-index: 2;
      transition: background 0.2s;
    }
    .carousel-btn:hover {
      background: #252525;
      color: #fff;
    }
    .carousel-btn.prev { left: 16px; }
    .carousel-btn.next { right: 16px; }
    @media (max-width: 700px) {
      .carousel-slide img { height: 207px; }
    }
    .achievements-section {
      max-width: 900px;
      margin: 50px auto;
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 24px 0 rgba(0,0,0,0.08);
      padding: 40px 30px;
      text-align: center;
    }
    .achievements-title {
      font-size: 2.2em;
      font-weight: 900;
      color: #252525;
      margin-bottom: 32px;
    }
    .stats-container {
      display: flex;
      justify-content: center;
      gap: 36px;
      margin: 40px 0;
      flex-wrap: wrap;
    }
    .stat-card {
      background: #f8f8f8;
      border-radius: 18px;
      box-shadow: 0 2px 12px 0 rgba(0,0,0,0.05);
      padding: 32px 32px 24px 32px;
      min-width: 180px;
      text-align: center;
      margin: 12px 0;
      transition: box-shadow 0.2s;
      font-family: 'Montserrat', sans-serif;
    }
    .stat-card:hover {
      box-shadow: 0 8px 32px 0 rgba(0,0,0,0.14);
    }
    .stat-value {
      font-size: 2.2em;
      font-weight: 900;
      color: #99D930;
      margin-bottom: 8px;
      display: block;
    }
    .stat-label {
      font-size: 1em;
      color: #252525;
      font-weight: 700;
      margin-bottom: 0;
    }
    @media (max-width: 900px) {
      .stats-container {
        flex-direction: column;
        align-items: center;
        gap: 24px;
      }
    }
  </style>
</head>
<body>
  
  <?php include 'show-navbar.php'; show_navbar(); ?>

  
  <div class="carousel-container">
    <div class="carousel-track">
      <div class="carousel-slide"><img src="images/home/home1.png" alt="Slide 1"></div>
      <div class="carousel-slide"><img src="images/home/home2.avif" alt="Slide 2"></div>
      <div class="carousel-slide"><img src="images/home/home3.avif" alt="Slide 3"></div>
    </div>
    <button class="carousel-btn prev" onclick="moveCarousel(-1)">&#10094;</button>
    <button class="carousel-btn next" onclick="moveCarousel(1)">&#10095;</button>
  </div>

  
  <section class="achievements-section">
    <div class="achievements-title">Achievements</div>
    <div class="stats-container">
      <?php
      require_once'db_configuration.php';
      $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
      if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

      $sql = "SELECT count(*) as num_schools FROM `schools`";
      $result = $conn->query($sql);
      $total_array = $result->fetch_assoc();
      $num_schools = $total_array['num_schools'];

      $sql = "SELECT count(*) as num_books FROM `books`";
      $result = $conn->query($sql);
      $total_array = $result->fetch_assoc();
      $num_books = $total_array['num_books'];

      $sql = "SELECT count(DISTINCT(User_Id)) as num_students FROM `registrations`";
      $result = $conn->query($sql);
      $total_array = $result->fetch_assoc();
      $num_students = $total_array['num_students'];

      $result->free();
      $conn->close();
      ?>
      <div class="stat-card">
        <span class="stat-value" data-value="<?= $num_schools ?>">0</span>
        <div class="stat-label">Schools Supported</div>
      </div>
      <div class="stat-card">
        <span class="stat-value" data-value="<?= $num_books ?>">0</span>
        <div class="stat-label">Books Shipped</div>
      </div>
      <div class="stat-card">
        <span class="stat-value" data-value="<?= $num_students ?>">0</span>
        <div class="stat-label">Students Enrolled</div>
      </div>
    </div>
  </section>
<?php
require_once 'db_configuration.php';
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }


$sql = "SELECT * FROM blogs ORDER BY Created_Time DESC LIMIT 1";
$result = $conn->query($sql);
$blog = $result->fetch_assoc();


$image_html = '';
if ($blog) {
    $picture_sql = "SELECT Location FROM blog_pictures WHERE Blog_Id = " . intval($blog["Blog_Id"]);
    $picture_locations = $conn->query($picture_sql);
    if ($picture_locations && $picture_locations->num_rows > 0) {
        $picture = $picture_locations->fetch_assoc();
        $image_html = '<img src="' . htmlspecialchars($picture['Location']) . '" alt="Blog Image" style="width:100%;max-height:300px;object-fit:cover;border-radius:12px;margin-bottom:18px;">';
    }
}
$conn->close();
?>

<?php if ($blog): ?>
<div class="blog-preview-container" style="max-width:720px;margin:40px auto 0 auto;padding:0 16px;">
  <div class="blog-card" style="background:#fff;border-radius:22px;box-shadow:0 8px 32px 0 rgba(0,0,0,0.09);overflow:hidden;transition:box-shadow 0.2s;display:flex;flex-direction:column;align-items:center;padding-bottom:24px;width:100%;">
    <?= $image_html ?>
    <div class="blog-card-content" style="padding:24px 32px 0 32px;width:100%;box-sizing:border-box;">
      <div class="blog-card-meta" style="font-size:15px;color:#99d930;margin-bottom:8px;display:flex;gap:16px;font-weight:700;">
        <span class="blog-card-date"><?= date('F j, Y', strtotime($blog['Created_Time'])) ?></span>
        <span class="blog-card-author">by <?= htmlspecialchars($blog['Author']) ?></span>
      </div>
      <h2 class="blog-card-title" style="font-size:32px;margin:0 0 12px 0;color:#252525;font-weight:900;text-align:left;">
        <a href="blog_entry.php?blog_id=<?= $blog['Blog_Id'] ?>" style="color:inherit;text-decoration:none;">
          <?= htmlspecialchars($blog['Title']) ?>
        </a>
      </h2>
      <p class="blog-card-excerpt" style="font-size:19px;color:#444;margin-bottom:20px;text-align:left;">
        <?= htmlspecialchars(mb_strimwidth(strip_tags($blog['Description']), 0, 180, '...')) ?>
      </p>
      <a class="blog-card-readmore" href="blog_entry.php?blog_id=<?= $blog['Blog_Id'] ?>" style="display:inline-block;background:#99d930;color:#252525;font-weight:bold;padding:10px 24px;border-radius:6px;text-decoration:none;transition:background 0.2s,color 0.2s;">Read More</a>
      <a class="blog-card-readmore" href="blog.php" style="display:inline-block;background:#99d930;color:#252525;font-weight:bold;padding:10px 24px;border-radius:6px;text-decoration:none;transition:background 0.2s,color 0.2s;margin-left:12px;">Blog</a>
    </div>
  </div>
</div>
<?php endif; ?>

  <script>
    
    let currentSlide = 0;
    const slides = document.querySelectorAll('.carousel-slide');
    const track = document.querySelector('.carousel-track');
    function updateCarousel() {
      track.style.transform = 'translateX(' + (-currentSlide * 100) + 'vw)';
    }
    function moveCarousel(dir) {
      currentSlide += dir;
      if (currentSlide < 0) currentSlide = slides.length - 1;
      if (currentSlide >= slides.length) currentSlide = 0;
      updateCarousel();
    }
    setInterval(() => { moveCarousel(1); }, 4000);
    updateCarousel();

    
    document.querySelectorAll('.stat-value').forEach(function(singleValue) {
      let startValue = 0;
      let endValue = parseInt(singleValue.getAttribute("data-value"));
      let duration = Math.floor(1500 / Math.max(endValue, 1));
      let counter = setInterval(function () {
        startValue += (endValue > 1000) ? 12 : 1;
        if (startValue >= endValue) {
          singleValue.textContent = endValue;
          clearInterval(counter);
        } else {
          singleValue.textContent = startValue;
        }
      }, duration);
    });
  </script>
  <?php include 'footer.php'; ?>
</body>
</html>
