<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
  session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500&display=swap" rel="stylesheet">
  <style>
    .blog-grid {
      display: flex;
      flex-direction: column;
      gap: 30px;
      padding: 30px;
      max-width: 1000px;
      margin: 0 auto;
    }
    .blog-grid-horizontal {
      display: flex;
      flex-direction: column;
      gap: 40px;
      max-width: 1000px;
      margin: 0 auto;
      padding: 40px 20px;
    }
    .blog-card-horizontal {
      display: flex;
      flex-direction: row;
      align-items: stretch;
      background: #fff;
      border: 1px solid #ccc;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      overflow: hidden;
      width: 100%;
    }
    .blog-card-horizontal:hover {
      transform: translateY(-5px);
    }
    .blog-image {
      width: 50%;
      height: auto;
      flex-shrink: 0;
    }
    .blog-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .blog-text {
      width: 50%;
      padding: 20px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .blog-text h3 {
      font-size: 1.5em;
      margin: 0 0 10px;
    }
    .blog-text .meta {
      font-size: 0.70em;
      color: #666;
      margin-bottom: 15px;
    }
    .blog-text p {
      font-size: .85em;
      line-height: 1.6;
      flex-grow: 1;
    }
    @media (max-width: 768px) {
      .blog-card-horizontal {
        flex-direction: column;
        height: auto;
      }
      .blog-image {
        width: 100%;
        height: 200px;
      }
    }
    #causes {
      margin-left: auto;
      margin-right: auto;
      width: 80%
    }
    th,
    td {
      border: solid black 1px;
    }
    .search-container {
      text-align: center;
      margin-bottom: 20px;
    }
    .search-input {
      width: 300px;
      padding: 10px;
      font-size: 16px;
    }
    .search-button {
      padding: 10px 20px;
      background-color: #99D930;
      color: white;
      border: none;
      cursor: pointer;
      font-size: 16px;
    }
    .school-icon {
      text-align: center;
      vertical-align: top;
      padding: 10px;
    }
    .school-icon img {
      max-width: 100px;
      max-height: 100px;
    }
    .school-info p {
      font-size: 14px;
      margin: 0;
      color: #333;
    }
    .inverse {
      position: relative;
      background-size: cover;
      height: 200px;
      overflow: hidden;
    }
    .inverse h1 {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 3;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
      color: white;
      font-size: 3em;
      text-align: center;
      width: 100%;
    }
    .blog-header-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      position: absolute;
      top: 0;
      left: 0;
      z-index: 1;
    }
    .blog-header-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to bottom, rgba(0,0,0,0.45) 0%, rgba(0,0,0,0.25) 100%);
      z-index: 2;
    }
    @media (max-width: 700px) {
      .inverse h1 {
        font-size: 2em;
        padding: 0 10px;
      }
      .inverse {
        height: 120px;
      }
    }
  </style>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <title>Learn and Help</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
  <script type="text/javascript" src="js/blog_functions.js"></script>
</head>
<body>
  <?php include 'show-navbar.php'; ?>
  <?php include 'blog_fill.php'; ?>
  <?php show_navbar(); ?>

  <header class="inverse">
    <img class="blog-header-img" src="./images/banner_images/Blog/header.jpg" alt="Blog header">
    <div class="blog-header-overlay"></div>
    <h1><span class="accent-text" style="color: white;">Blog</span></h1>
  </header>

  <script>
    let show_form = () => {
      let form = document.getElementById("blog_creation_form");
      let show_button = document.getElementById("form_show_button");
      form.removeAttribute("hidden");
      show_button.setAttribute("hidden", "hidden");
    }
  </script>

  <?php
  if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    echo '<button id="form_show_button" onclick="show_form();">Create Post</button>';
  }
  ?>

  <form id="blog_creation_form" action="create_post.php" method="POST" enctype="multipart/form-data" hidden="hidden">
    <div id="blog_creation_left">
      <label>Blog Title</label><br>
      <input type="text" name="title" maxlength="100" required><br>
      <label for="description">Description</label><br>
      <textarea name="description" rows="9" cols="50" required></textarea>
    </div>
    <div id="blog_creation_right">
      <label for="author">Author</label><br>
      <input type="text" name="author" maxlength="50" required><br>
      <label>Image(s)</label><br>
      <input type="file" name="file[]" accept="image/*" multiple="multiple"><br>
      <label>Video Link</label><br>
      <input type="text" name="video_link" maxlength="100" placeholder="Optional">
    </div>
    <br>
    <input type="submit" name="create_post" value="Publish">
  </form>

  <div>
    <?php fill_blog(); ?>
    <!--<div id="blog_buttons">
      <button id="blog_previous" onclick="handlePageButton('previous')" hidden="hidden">Previous</button>
      <button id="blog_next" onclick="handlePageButton('next')">Next</button>
    </div>-->
  </div>

  <?php include 'footer.php'; ?>
</body>
</html>
