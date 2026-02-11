<?php
require 'db_configuration.php';

$status = session_status();
if ($status == PHP_SESSION_NONE) {
  session_start();
}

function fill_blog() {
  $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $sql = "SELECT * FROM blogs ORDER BY Created_Time DESC";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
    echo '<div class="blog-grid-horizontal">'; 

    while ($row = $result->fetch_assoc()) {
      
      $picture_sql = "SELECT Location FROM blog_pictures WHERE Blog_Id = " . $row["Blog_Id"];
      $picture_locations = $conn->query($picture_sql);
      $image_html = '';

      if ($picture_locations->num_rows > 0) {
        $picture = $picture_locations->fetch_assoc();
        $image_html = '<img src="' . htmlspecialchars($picture['Location']) . '" alt="Blog Image">';
      }

      $preview = strip_tags($row['Description']);
      $preview = (strlen($preview) > 200) ? substr($preview, 0, 200) . '...' : $preview;

     
      $video_link_html = '';
      if (!empty($row["Video_Link"])) {
        $video_link_html = '<p><a href="' . htmlspecialchars($row["Video_Link"]) . '" target="_blank">Watch Video</a></p>';
      }

      echo '
  <div class="blog-card-horizontal">
    <div class="blog-image">
      <a href="blog_entry.php?blog_id=' . urlencode($row['Blog_Id']) . '">
        ' . $image_html . '
      </a>
    </div>
    <div class="blog-text">
      <h3>
        <a href="blog_entry.php?blog_id=' . urlencode($row['Blog_Id']) . '" style="color:inherit;text-decoration:none;">
          ' . htmlspecialchars($row['Title']) . '
        </a>
      </h3>
      <p class="meta"><strong>By:</strong> ' . htmlspecialchars($row['Author']) . ' | <em>' . htmlspecialchars($row['Created_Time']) . '</em></p>
      <p>' . htmlspecialchars($preview) . '</p>
      ' . $video_link_html . '
      <a href="blog_entry.php?blog_id=' . urlencode($row['Blog_Id']) . '" class="blog-card-readmore" style="color:#99d930;text-decoration:underline;font-weight:bold;">Read More</a>
    </div>
  </div>
';

    }

    echo '</div>'; 
  } else {
    echo '<p>No blog posts found.</p>';
  }

  $conn->close();
}
?>
