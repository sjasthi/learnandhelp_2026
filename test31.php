<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Instructors</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css"/>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#instructor_table thead tr').clone(true).appendTo('#instructor_table thead');
            $('#instructor_table thead tr:eq(1) th').each(function () {
                var title = $(this).text();
                $(this).html('<input type="text" placeholder="Search ' + title + '" />');
            });

            var table = $('#instructor_table').DataTable({
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
        <h1><span class="accent-text">Instructors</span></h1>
    </div>
</header>
<h4>Add Instructor</h4>
<form action="update_Instructors.php" method="post" id="add_Instructor">
    <label>
        <input type="text" name="name" placeholder="Name" required>
    </label>
    <br>
    <label>
        <textarea rows=5 cols=90 name="Bio_data" placeholder="Bio_data" required></textarea>
    </label>
    <br>


    <label for="Image">Image:</label>
    <select name="status" id="status" required>

    </select>
    <br>
    <input type="hidden" name="action" value="add">
    <input type="submit" value="Add" style="width: 15%">
</form>
<!-- Jquery Data Table -->
<div class="toggle_columns">
    Toggle column: <a class="toggle-vis" data-column="0">Instructor ID</a>
    - <a class="toggle-vis" data-column="1"> Name</a>
    - <a class="toggle-vis" data-column="2">Bio</a>
    - <a class="toggle-vis" data-column="3"></a>
    - <a class="toggle-vis" data-column="4">Image</a>
</div>
<div style="padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto">
    <table id="Instructor_table" class="display compact">
        <thead>
        <tr>
            <th>Instructor ID</th>
            <th> Name</th>
            <th>Bio</th>
            <th>Image</th> <!-- Added Status -->
            <th>Options</th>
        </tr>
        </thead>
        <tbody>
        <!-- Populating table with data from the database-->
        <?php
        require 'db_configuration.php';
        // Create connection
        $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT * FROM instructor";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Create table with data from each row
            // Added "Status"
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row["instructor_ID"] . "</td>
                        <td>" . $row["Name"] . "</td>
                        <td>" . $row["Bio_data"] . "</td>
                        <td>
                            <a href='http://localhost/learnandhelp/image/" . $row["Image"] . "'>
                                <img src='http://localhost/learnandhelp/image/siva.png" . $row["Image"] . "' alt='" . $row["Name"] . " Image'>
                            </a>
                        </td>
                        C:\xampp\htdocs\learnandhelp\images
                        <td>
                            <form action='Instructors.php' method='POST'>
                                <input type='hidden' name='instructor_ID' value='" . $row["instructor_ID"] . "'>
                                <input type='submit' id='admin_buttons' name='edit' value='Edit'>
                            </form>
                            <form action='Instructors.php' method='POST'>
                                <input type='hidden' name='instructor_ID' value='" . $row["instructor_ID"] . "'>
                                <input type='submit' id='admin_buttons' name='delete' value='Delete'>
                            </form>
                        </td>
                    </tr>";
            }
        } else {
            echo "0 results";
        }
        $conn->close();
        ?>
        </tbody>
    </table>
</div>
</body>
</html>
