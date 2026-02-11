<!DOCTYPE html>
<html>
<head>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <title>Learn and Help</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
  <link href="css/main.css" rel="stylesheet">
  <style>
    .book-details-container {
      max-width: 600px;
      margin: 0 auto;
      padding: 20px;
      text-align: center;
    }

    .book-details-container img {
      max-width: 200px;
      max-height: 200px;
    }

    h3 {
      font-size: 24px;
    }

    p {
      font-size: 16px;
    }
  </style>
</head>
<body>
  <?php include 'show-navbar.php'; ?>
  <?php show_navbar(); ?>
  <header class="inverse">
    <div class="container">
      <h1 class="accent-text">Book Details</h1>
    </div>
  </header>

  <div class="book-details-container">
    <?php
    // Check if a book_id is provided in the query string
    if (isset($_GET['book_id'])) {
      // Retrieve the book details based on the provided book_id
      $book_id = $_GET['book_id'];

      // Use your database connection and query to fetch book details
      require 'db_configuration.php'; // Include your database configuration

      $connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
      if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
      }

      $sql = "SELECT * FROM books";
      $result = $connection->query($sql);

      if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo '<img src="images/books/default.png" alt="' . $row['title'] . '">';
        echo '<h3>' . $row['title'] . '</h3>';
        echo '<p>Author: ' . $row['author'] . '</p>';
        echo '<p>Publisher: ' . $row['publisher'] . '</p>';
        echo '<p>Grade Level: ' . $row['grade_level'] . '</p>';
      } else {
        echo '<p>Book not found.</p>';
      }

      $connection->close();
    } else {
      echo '<p>Book ID is missing in the query string.</p>';
    }
    ?>
  </div>
</body>
</html>
