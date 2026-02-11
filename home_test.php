<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Learn and Help - Home</title>

  <!-- Bootstrap & Google Fonts -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Montserrat', sans-serif;
      background-color: #f8f9fa;
    }

    .navbar {
      background-color: #2c3e50;
    }

    .navbar-brand {
      font-weight: bold;
      font-size: 1.5rem;
      color: #ecf0f1 !important;
    }

    .nav-link {
      color: #ecf0f1 !important;
    }

    .hero {
      height: 320px;
      background-image: url('images/banner_images/Home/hero.jpg');
      background-size: cover;
      background-position: center;
      position: relative;
    }

    .hero::after {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: rgba(44, 62, 80, 0.5);
    }

    .hero h1 {
      position: relative;
      z-index: 2;
      color: #fff;
      text-align: center;
      padding-top: 120px;
      font-size: 3rem;
      text-shadow: 1px 1px 5px rgba(0,0,0,0.5);
    }

    .stats {
      font-size: 2rem;
      font-weight: 500;
    }

    footer {
      background-color: #2c3e50;
      color: #fff;
      text-align: center;
      padding: 20px 0;
      margin-top: 50px;
    }
  </style>
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow">
  <div class="container">
    <a class="navbar-brand" href="#">Learn & Help</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarNav" aria-controls="navbarNav"
            aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
        <li class="nav-item"><a class="nav-link" href="schools.php">Schools</a></li>
        <li class="nav-item"><a class="nav-link" href="books.php">Books</a></li>
        <li class="nav-item"><a class="nav-link" href="contact_us.php">Enroll</a></li>
        <li class="nav-item"><a class="nav-link" href="blog.php">Blog</a></li>
      </ul>
    </div>
  </div>
</nav>

<header class="hero">
  <h1>Home</h1>
</header>

<main class="container py-5 text-center">
  <?php
    require 'db_configuration.php';
    $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
    if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

    $schools = $conn->query("SELECT count(*) as num FROM schools")->fetch_assoc()['num'];
    $books = $conn->query("SELECT count(*) as num FROM books")->fetch_assoc()['num'];
    $students = $conn->query("SELECT count(DISTINCT(User_Id)) as num FROM registrations")->fetch_assoc()['num'];

    $conn->close();

    echo "
      <h2 class='fw-bold mb-4'>Learn and Help Program Summarized</h2>
      <p class='stats'>📚 $books Books Shipped</p>
      <p class='stats'>🏫 $schools Schools Supported</p>
      <p class='stats'>👨‍🎓 $students Students Enrolled</p>
    ";
  ?>
</main>

<footer>
  <div class="container">
    &copy; <?= date("Y") ?> Learn & Help. All rights reserved.
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
