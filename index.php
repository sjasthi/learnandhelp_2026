<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

// Get flash message if it exists
$flash_message = '';
$flash_type = 'info';
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    $flash_type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'info';
    
    // Clear the flash message after displaying
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
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
    
    /* Flash message styles */
    .flash-message {
      padding: 15px;
      margin: 20px auto;
      max-width: 900px;
      border-radius: 8px;
      font-weight: bold;
      text-align: center;
    }
    
    .flash-message.error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    
    .flash-message.success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    
    .flash-message.info {
      background-color: #cce7ff;
      color: #004085;
      border: 1px solid #b3d7ff;
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
    
    /* Hero section styles */
    .hero-section {
      max-width: 900px;
      margin: 50px auto;
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 24px 0 rgba(0,0,0,0.08);
      padding: 50px 40px;
      text-align: center;
    }
    
    .hero-title {
      font-size: 3em;
      font-weight: 900;
      color: #252525;
      margin-bottom: 16px;
      line-height: 1.1;
    }
    
    .hero-subtitle {
      font-size: 1.4em;
      font-weight: 700;
      color: #99D930;
      margin-bottom: 32px;
      font-style: italic;
    }
    
    .hero-description {
      font-size: 1.1em;
      color: #444;
      line-height: 1.6;
      max-width: 700px;
      margin: 0 auto;
    }
    
    @media (max-width: 768px) {
      .hero-section {
        margin: 30px 20px;
        padding: 35px 25px;
      }
      
      .hero-title {
        font-size: 2.2em;
      }
      
      .hero-subtitle {
        font-size: 1.2em;
      }
      
      .hero-description {
        font-size: 1em;
      }
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

<!--Show the account created success message-->
<!--This is coming from process_new_account.php-->

<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="alert-success" role="alert">
    <?= htmlspecialchars($_SESSION['flash_success']) ?>
  </div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<!-- Display flash message if it exists -->
<?php if (!empty($flash_message)): ?>
  <div class="flash-message <?php echo htmlspecialchars($flash_type, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo htmlspecialchars($flash_message, ENT_QUOTES, 'UTF-8'); ?>
  </div>
<?php endif; ?>

<div class="carousel-container">
  <div class="carousel-track">
    <?php
      
      $imageDir = 'images/home';
  
      $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'avif', 'webp'];


      $allFiles = array_diff(scandir($imageDir), ['..', '.']);

    
      $images = array_filter($allFiles, function($filename) use ($allowedExtensions) {
          $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
          return in_array($ext, $allowedExtensions);
      });

     
      natcasesort($images);

  
      foreach ($images as $image) {
       
          $safeFilename = htmlspecialchars($image, ENT_QUOTES);
          $imagePath = htmlspecialchars($imageDir . '/' . $image, ENT_QUOTES);
          echo '<div class="carousel-slide"><img src="' . $imagePath . '" alt="Slide"></div>';
      }

      if (empty($images)) {
          echo '<div class="carousel-slide" style="min-width:100vw; text-align:center; line-height:500px; color:#999;">No images found in the carousel.</div>';
      }
    ?>
  </div>
  <button class="carousel-btn prev" onclick="moveCarousel(-1)">&#10094;</button>
  <button class="carousel-btn next" onclick="moveCarousel(1)">&#10095;</button>
</div>

<!-- Hero Section -->
<section class="hero-section">
  <h1 class="hero-title">Learn and Help</h1>
  <h2 class="hero-subtitle">Empowering Minds, Inspiring Generosity!</h2>
  <p class="hero-description">
    Learn and Help is a program where middle and high school students learn computational thinking and computer programming. The proceeds from this program are used to support schools in need by providing books, educational materials, and resources to empower students everywhere.
  </p>
</section>

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
    $num_students = $total_array['num_students'] + 100;

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
      <div class="stat-label">Students Empowered</div>
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

  document.addEventListener('DOMContentLoaded', () => {
    let currentSlide = 0;
    const track = document.querySelector('.carousel-track');
    const slides = document.querySelectorAll('.carousel-slide');

    function updateCarousel() {
      track.style.transform = 'translateX(' + (-currentSlide * 100) + 'vw)';
    }

    window.moveCarousel = function(dir) {
      currentSlide += dir;
      if (currentSlide < 0) currentSlide = slides.length - 1;
      if (currentSlide >= slides.length) currentSlide = 0;
      updateCarousel();
    }

   
    setInterval(() => moveCarousel(1), 4000);

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
  });
</script>

<?php include 'footer.php'; ?>
</body>
</html>