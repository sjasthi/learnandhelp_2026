<?php
//set up error reporting for debugging 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

//function to get the profile image path based on school id 
function get_profile_image($id) {
    $image_name = glob('schools/' . $id . '/profile_image.*');
    if (count($image_name) == 1) {
        return $image_name[0]; //return the first image found
    } else {
        return "images/admin_icons/school.png"; //else were just going to return a default image 
    }
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "learn_and_help_db";

//make sure a connection is happening, establish a connection 
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch preferences from the database 

$sql = "SELECT preference_name, value FROM preferences";
$result = $conn->query($sql);
$preferences = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $preferences[$row['preference_name']] = $row['value'];
    }
}
$conn->close(); //close the database connection


// Default values if preferences are not set
$schoolsPerRow = isset($preferences['Number Of Schools Per Row']) ? $preferences['Number Of Schools Per Row'] : 5;
$schoolsPerPage = isset($preferences['Number Of Schools Per Page']) ? $preferences['Number Of Schools Per Page'] : 100;
$imageHeight = isset($preferences['Image Height']) ? $preferences['Image Height'] : '200px';



$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $schoolsPerPage;
?>

<!DOCTYPE html>
<html>
  <head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script>
    $(document).ready(function () {
      $('#classes_table thead tr').clone(true).appendTo( '#classes_table thead' );
      $('#classes_table thead tr:eq(1) th').each(function () {
      var title = $(this).text();
      $(this).html('<input type="text" placeholder="Search ' + title + '" />');
      });

      var table = $('#classes_table').DataTable({
         initComplete: function () {
             // Apply the search
             this.api()
                 .columns()
                 .every(function () {
                     var that = this;

                     $('input', this.header()).on('keyup change clear', function () {
                         if (that.search() !== this.value) {
                             that.search(this.value).draw();
                         }
                     });
                 });
             },
         });

      $('a.toggle-vis').on('click', function (e) {
      e.preventDefault();

      // Get the column API object
      var column = table.column($(this).attr('data-column'));

      // Toggle the visibility
      column.visible(!column.visible());
      });
     });
    </script>
  </head>
  <body>
  <?php include 'show-navbar.php'; ?>
  <?php show_navbar(); ?>
 

    <header class="inverse">
        <div class="slideshow-container">
            <?php
            // Get images from the directory
            $images_dir = "images/banner_images/schools-images/images-school";
            $images = glob($images_dir . "*.{jpg,png}", GLOB_BRACE);

            // Display each image as a slide
            foreach ($images as $index => $image) {
                $safe_image_path = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
                echo "<div class='banner_slide'>
                        <img src='{$safe_image_path}' alt='School banner image'>
                      </div>";
            }
            ?>
        </div>

        <h1><span class="accent-text">Schools</span></h1>

        <div class="dots-container">
            <?php
            // Generate dots for the slideshow
            for ($i = 0; $i < count($images); $i++) {
                echo "<span class='dot'></span>";
            }
            ?>
        </div>
    </header>

    <div class="container">
        <div class="search-container">
            <input type="text" id="search-input" class="search-input" placeholder="Search for schools...">
            <button id="search-button" class="search-button">Search</button>
        </div>

       
        <div style="padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto">
            <table id="school_icons">
                <?php
                // Database connection for fetching school data 
                $conn = new mysqli($servername, $username, $password, $dbname);
                $sql = "SELECT * FROM schools LIMIT $schoolsPerPage OFFSET $offset";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    $counter = 0;
                    while ($row = $result->fetch_assoc()) {
                        $time = time();
                        $counter++;
                        if ($counter == 1) {
                            echo "<tr>";
                        }
                        //get the school data from the row 
                        $id = $row["id"];
                        $name = $row["name"];
                        $type = $row["type"];
                        $address = $row["address_text"];
                        $state = $row["state_name"];
                        $state_code = $row["state_code"];
                        $category = $row["category"];
                        //showcase the school icon with profile images and information 
                        echo  "<td class=\"school-icon\">
                                <a href=\"school_details.php?id=$id\">";
                        $profile_image = get_profile_image($id);
                        echo "      <img src=\"$profile_image?v=$time\" alt=\"school image\"><br>
                                    <div class=\"school-info\">
                                        <p>$id</p>
                                        <p>$name</p>
                                    </div>
                                </a>
                              </td>";
                        if ($counter % $schoolsPerRow == 0 && $counter > 0) {
                            echo "</tr>";
                            if ($counter < $result->num_rows) {
                                echo "<tr>";
                            }
                        }
                    }
                    if ($counter % $schoolsPerRow != 0) {
                        echo "</tr>";
                    }
                } else {
                    echo '<p>No schools found.</p>';
                }
                $conn->close();
                ?>
            </table>

            <?php
            // the arrows to navigate trhough the school pages, like the next pages 
            $conn = new mysqli($servername, $username, $password, $dbname);
            $sql = "SELECT COUNT(*) AS total FROM schools";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $totalSchools = $row['total'];
            $totalPages = ceil($totalSchools / $schoolsPerPage);
            $conn->close();

            echo '<div class="pagination">';
            for ($i = 1; $i <= $totalPages; $i++) {
                echo "<a href='schools.php?page=$i'>$i</a> ";
            }
            echo '</div>';
            ?>
        </div>
    </div>
</body>

</html>
