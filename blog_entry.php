<?php
require 'db_configuration.php';

// Validate blog_id
if (!isset($_GET['blog_id']) || !is_numeric($_GET['blog_id'])) {
    die("Invalid blog entry.");
}
$blog_id = intval($_GET['blog_id']);

// Connect
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch blog (includes youtube_link column that already exists in your table)
$blog_sql = "SELECT * FROM blogs WHERE Blog_Id = ?";
$stmt = $conn->prepare($blog_sql);
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$blog = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$blog) {
    $conn->close();
    die("Blog entry not found.");
}

// Fetch one picture (if any)
$image_html = '';
$picture_sql = "SELECT Location FROM blog_pictures WHERE Blog_Id = ? LIMIT 1";
$pstmt = $conn->prepare($picture_sql);
$pstmt->bind_param("i", $blog_id);
$pstmt->execute();
$presult = $pstmt->get_result();
if ($presult && $presult->num_rows > 0) {
    $picture = $presult->fetch_assoc();
    $image_html = '<img src="' . htmlspecialchars($picture['Location']) . '" class="blog-entry-image" alt="Blog Image">';
}
$pstmt->close();

// Helper to convert many YouTube URL shapes into an embeddable URL
function youtube_embed_url(?string $url): ?string {
    if (empty($url)) return null;
    $url = trim($url);

    // Already an embed URL
    if (preg_match('~^https?://(www\.)?youtube\.com/embed/([A-Za-z0-9_-]{11})~', $url, $m)) {
        return $url;
    }

    // watch?v=VIDEOID
    if (preg_match('~^https?://(www\.)?youtube\.com/watch\?v=([A-Za-z0-9_-]{11})~', $url, $m)) {
        return "https://www.youtube.com/embed/" . $m[2];
    }

    // youtu.be/VIDEOID
    if (preg_match('~^https?://(www\.)?youtu\.be/([A-Za-z0-9_-]{11})~', $url, $m)) {
        return "https://www.youtube.com/embed/" . $m[2];
    }

    // shorts/VIDEOID
    if (preg_match('~^https?://(www\.)?youtube\.com/shorts/([A-Za-z0-9_-]{11})~', $url, $m)) {
        return "https://www.youtube.com/embed/" . $m[2];
    }

    // Fallback: try to parse v= param
    $parts = parse_url($url);
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $qs);
        if (!empty($qs['v']) && preg_match('~^[A-Za-z0-9_-]{11}$~', $qs['v'])) {
            return "https://www.youtube.com/embed/" . $qs['v'];
        }
    }
    return null; // unknown shape
}

$embed_url = youtube_embed_url($blog['Video_Link'] ?? null);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($blog['Title']) ?> | Learn and Help Blog</title>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
  <style>
    body {
      font-family: 'Montserrat', sans-serif;
      background: #f8f8f8;
      color: #252525;
      margin: 0;
    }
    .blog-entry-container {
      max-width: 800px;
      margin: 60px auto 40px auto;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 8px 32px 0 rgba(0,0,0,0.09);
      padding: 36px;
    }
    .blog-entry-title {
      font-size: 2.5em;
      font-weight: 900;
      color: #252525;
      margin-bottom: 12px;
    }
    .blog-entry-meta {
      color: #99d930;
      font-weight: 700;
      margin-bottom: 20px;
      font-size: 1em;
    }
    .blog-entry-image {
      width: 100%;
      height: auto;
      object-fit: contain;
      border-radius: 16px;
      margin-bottom: 24px;
      background: #eee;
      display: block;
    }
    .blog-entry-content {
      font-size: 1.2em;
      color: #444;
      line-height: 1.7;
      margin-bottom: 32px;
      white-space: pre-wrap; /* preserve intentional line breaks too */
      text-align: left;
    }

    /* Responsive 16:9 video wrapper */
    .video-wrapper {
      position: relative;
      width: 100%;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 6px 24px rgba(0,0,0,0.08);
      margin-bottom: 28px;
      aspect-ratio: 16 / 9;        /* modern browsers */
      background: #000;
    }
    .video-wrapper iframe {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      border: 0;
    }

    .back-link {
      display: inline-block;
      margin-top: 32px;
      color: #99d930;
      text-decoration: none;
      font-weight: bold;
      font-size: 1.1em;
      transition: color 0.2s;
    }
    .back-link:hover {
      color: #252525;
    }
    @media (max-width: 900px) {
      .blog-entry-container { padding: 16px 4vw; }
      .blog-entry-title { font-size: 1.8em; }
    }
  </style>
</head>
<body>
<?php include 'show-navbar.php'; show_navbar(); ?>

<div class="blog-entry-container">
  <div class="blog-entry-title"><?= htmlspecialchars($blog['Title']) ?></div>
  <div class="blog-entry-meta">
    <?= date('F j, Y', strtotime($blog['Created_Time'])) ?> &bull;
    by <?= htmlspecialchars($blog['Author']) ?>
  </div>

  <?= $image_html ?>

  <?php if (!empty($embed_url)): ?>
    <div class="video-wrapper" aria-label="YouTube video">
      <iframe
        src="<?= htmlspecialchars($embed_url) ?>"
        title="YouTube video player"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        allowfullscreen>
      </iframe>
    </div>
  <?php endif; ?>

  <div class="blog-entry-content">
    <?= htmlspecialchars($blog['Description']) ?>
  </div>

  <a href="blog.php" class="back-link">&larr; Back to Blog</a>
</div>

<?php include 'footer.php'; ?>
</body>
</html>