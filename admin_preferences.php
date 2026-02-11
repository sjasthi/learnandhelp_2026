<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "learn_and_help_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $value = $_POST['value'];

    $sql = "INSERT INTO preferences (name, value) VALUES ('$name', '$value') 
            ON DUPLICATE KEY UPDATE value='$value'";
    
    if ($conn->query($sql) === TRUE) {
        echo "Preference updated successfully";
    } else {
        echo "Error updating preference: " . $conn->error;
    }
}

// Fetch preferences
$sql = "SELECT * FROM preferences";
$result = $conn->query($sql);
$preferences = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $preferences[$row['name']] = $row['value'];
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en-US">
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Learn and Help</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css"/>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script>
    $(document).ready(function () {
      $('#classes thead tr').clone(true).appendTo( '#classes thead' );
      $('#classes thead tr:eq(1) th').each(function () {
      var title = $(this).text();
      $(this).html('<input type="text" placeholder="Search ' + title + '" />');
      });

      var table = $('#classes').DataTable({
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
    <div class="container">
        <h1><span class="accent-text">Update Preferences</span></h1>
    </div>
</header>

<div style="padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto">
    <table id="classes" class="display compact">
        <thead>
            <tr>
                <th>Name</th>
                <th>Value</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <form method="POST">
                    <td>ACTIVE REGISTRATION</td>
                    <td>
                        <select name="value" required>
                            <option value="2023-2024" <?php if(isset($preferences['ACTIVE REGISTRATION']) && $preferences['ACTIVE REGISTRATION'] == '2023-2024') echo 'selected'; ?>>2023-2024</option>
                            <option value="2024-2025" <?php if(isset($preferences['ACTIVE REGISTRATION']) && $preferences['ACTIVE REGISTRATION'] == '2024-2025') echo 'selected'; ?>>2024-2025</option>
                            <option value="2025-2026" <?php if(isset($preferences['ACTIVE REGISTRATION']) && $preferences['ACTIVE REGISTRATION'] == '2025-2026') echo 'selected'; ?>>2025-2026</option>
                        </select>
                    </td>
                    <td>
                        <input type="hidden" name="name" value="ACTIVE REGISTRATION">
                        <button type="submit" name="update">Update</button>
                    </td>
                </form>
            </tr>
            <tr>
                <form method="POST">
                    <td>Course Fee</td>
                    <td>
                        <select name="value" required>
                            <option value="Free" <?php if(isset($preferences['Course Fee']) && $preferences['Course Fee'] == 'Free') echo 'selected'; ?>>$0-250</option>
                            <option value="500" <?php if(isset($preferences['Course Fee']) && $preferences['Course Fee'] == '500') echo 'selected'; ?>>$500</option>
                        </select>
                    </td>
                    <td>
                        <input type="hidden" name="name" value="Course Fee">
                        <button type="submit" name="update">Update</button>
                    </td>
                </form>
            </tr>
            <tr>
                <form method="POST">
                    <td>Number Of Schools Per Row</td>
                    <td>
                        <select name="value" required>
                            <option value="5" <?php if(isset($preferences['Number Of Schools Per Row']) && $preferences['Number Of Schools Per Row'] == '5') echo 'selected'; ?>>5</option>
                            <option value="8" <?php if(isset($preferences['Number Of Schools Per Row']) && $preferences['Number Of Schools Per Row'] == '8') echo 'selected'; ?>>8</option>
                            <option value="10" <?php if(isset($preferences['Number Of Schools Per Row']) && $preferences['Number Of Schools Per Row'] == '10') echo 'selected'; ?>>10</option>
                        </select>
                    </td>
                    <td>
                        <input type="hidden" name="name" value="Number Of Schools Per Row">
                        <button type="submit" name="update">Update</button>
                    </td>
                </form>
            </tr>
            <tr>
                <form method="POST">
                    <td>Number Of Schools Per Page</td>
                    <td>
                        <select name="value" required>
                            <option value="15" <?php if(isset($preferences['Number Of Schools Per Page']) && $preferences['Number Of Schools Per Page'] == '15') echo 'selected'; ?>>15</option>
                            <option value="25" <?php if(isset($preferences['Number Of Schools Per Page']) && $preferences['Number Of Schools Per Page'] == '25') echo 'selected'; ?>>25</option>
                            <option value="50" <?php if(isset($preferences['Number Of Schools Per Page']) && $preferences['Number Of Schools Per Page'] == '50') echo 'selected'; ?>>50</option>
                            <option value="100" <?php if(isset($preferences['Number Of Schools Per Page']) && $preferences['Number Of Schools Per Page'] == '100') echo 'selected'; ?>>100</option>
                        </select>
                    </td>
                    <td>
                        <input type="hidden" name="name" value="Number Of Schools Per Page">
                        <button type="submit" name="update">Update</button>
                    </td>
                </form>
            </tr>
            <tr>
                <form method="POST">
                    <td>Image Height</td>
                    <td>
                        <select name="value" required>
                            <option value="200px" <?php if(isset($preferences['Image Height']) && $preferences['Image Height'] == '200px') echo 'selected'; ?>>200px</option>
                            <option value="100px" <?php if(isset($preferences['Image Height']) && $preferences['Image Height'] == '100px') echo 'selected'; ?>>100px</option>
                            <option value="50px" <?php if(isset($preferences['Image Height']) && $preferences['Image Height'] == '50px') echo 'selected'; ?>>50px</option>
                        </select>
                    </td>
                    <td>
                        <input type="hidden" name="name" value="Image Height">
                        <button type="submit" name="update">Update</button>
                    </td>
                </form>
            </tr>
        </tbody>
    </table>
</div>
</body>
</html>