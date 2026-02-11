<?php
  $status = session_status();
  if ($status == PHP_SESSION_NONE) {
    session_start();
  }

  // Check if instructor_ID is set
  if(isset($_POST['instructor_ID'])) {
    $instructor_ID = $_POST['instructor_ID'];

    // Fetch instructor details from the database based on the provided ID
    require 'db_configuration.php';
    $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
    if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT * FROM instructor WHERE instructor_ID = '$instructor_ID'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
      $row = $result->fetch_assoc();
      $first_name = $row["First_name"];
      $last_name = $row["Last_name"];
      $bio_data = $row["Bio_data"];
      // Assuming there's only one image path stored for an instructor
      $image_path = $row["Image"];
    } else {
      echo "Instructor not found";
      exit(); // Exit if instructor not found
    }
    $conn->close();
  } else {
    echo "No instructor ID provided";
    exit(); // Exit if no instructor ID provided
  }
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Edit Instructor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>

</head>
<body>
<?php include 'show-navbar.php'; ?>
<?php show_navbar(); ?>
<header class="inverse">
    <div class="container">
        <h1><span class="accent-text">Edit Instructor</span></h1>
    </div>
</header>
<br>
  <!-- Your HTML content for editing instructor information goes here -->

  <form action="admin_update_instructor.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="instructor_ID" value="<?php echo $instructor_ID; ?>">
    <label>First Name:</label>
    <input type="text" name="first_name" value="<?php echo $first_name; ?>"><br><br>
    <label>Last Name:</label>
    <input type="text" name="last_name" value="<?php echo $last_name; ?>"><br><br>
    <label>Bio:</label>
    <textarea name="bio_data"><?php echo $bio_data; ?></textarea><br><br>
    <label>Image:</label>
    <img src="<?php echo $image_path; ?>" width="100"><br>
    <input type="file" name="new_image"><br><br>
    <input type="submit" name="submit" value="Update Instructor">
  </form>
</body>
</html>