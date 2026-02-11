<?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "learn_and_help_db";

// Establish database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch preferences from the database
$preferences = [];
$sql = "SELECT preference_name, value FROM preferences";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $preferences[$row['preference_name']] = $row['value'];
    }
}

$schoolsPerRow = isset($preferences['Number of schools per row']) ? $preferences['Number of schools per row'] : 5;
$schoolsPerPage = isset($preferences['Number of schools per page']) ? $preferences['Number of schools per page'] : 100;
$imageHeight = isset($preferences['Image Height']) ? $preferences['Image Height'] : '200px';

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
    <style>
        .school-image {
            height: <?php echo $imageHeight; ?>;
            width: auto;
        }
        .school-container {
            display: flex;
            flex-wrap: wrap;
        }
        .school-item {
            flex: 1 0 <?php echo (100 / $schoolsPerRow); ?>%;
            box-sizing: border-box;
        }
    </style>
    <script>
        $(document).ready(function () {
            $('#classes thead tr').clone(true).appendTo('#classes thead');
            $('#classes thead tr:eq(1) th').each(function () {
                var title = $(this).text();
                $(this).html('<input type="text" placeholder="Search ' + title + '" />');
            });

            var table = $('#classes').DataTable({
                initComplete: function () {
                    this.api().columns().every(function () {
                        var that = this;
                        $('input', this.header()).on('keyup change clear', function () {
                            if (that.search() !== this.value) {
                                that.search(this.value).draw();
                            }
                        });
                    });
                },
                pageLength: <?php echo $schoolsPerPage; ?>
            });

            $('a.toggle-vis').on('click', function (e) {
                e.preventDefault();
                var column = table.column($(this).attr('data-column'));
                column.visible(!column.visible());
            });
        });
    </script>
</head>
<body>
    <?php include 'show-navbar.php'; ?>
    <?php show_navbar(); ?>
    <!-- Your content here -->
</body>
</html>
