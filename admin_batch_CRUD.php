<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

// Block unauthorized users from accessing the page
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] != 'admin') {
        http_response_code(403);
        die('Forbidden');
    }
} else {
    http_response_code(403);
    die('Forbidden');
}

require 'db_configuration.php';

// Create connection
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Add, Edit, Delete actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $batch_name = $_POST['Batch_Name'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $sql = "INSERT INTO batch (Batch_Name, start_date, end_date) VALUES ('$batch_name', '$start_date', '$end_date')";
        $conn->query($sql);
    } elseif (isset($_POST['edit'])) {
        $batch_name = $_POST['Batch_Name'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $sql = "UPDATE batch SET start_date='$start_date', end_date='$end_date' WHERE Batch_Name='$batch_name'";
        $conn->query($sql);
    } elseif (isset($_POST['delete'])) {
        $batch_name = $_POST['Batch_Name'];
        $sql = "DELETE FROM batch WHERE Batch_Name='$batch_name'";
        $conn->query($sql);
    }
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

?>

<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css"/>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#batch_table thead tr').clone(true).appendTo('#batch_table thead');
            $('#batch_table thead tr:eq(1) th').each(function () {
                var title = $(this).text();
                $(this).html('<input type="text" placeholder="Search ' + title + '" />');
            });

            var table = $('#batch_table').DataTable({
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
        <h1><span class="accent-text">Batches</span></h1>
    </div>
</header>
<!-- Jquery Data Table -->
<div class="toggle_columns">
    Toggle column:
    <a class="toggle-vis" data-column="0">Batch_Name</a> -
    <a class="toggle-vis" data-column="1">Start Date</a> -
    <a class="toggle-vis" data-column="2">End Date</a> -
</div>
<div style="padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto">
    <table id="batch_table" class="display compact">
        <thead>
        <tr>
            <th>Batch Name</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <!-- Populating table with data from the database-->
        <?php
        $sql = "SELECT * FROM batch";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Create table with data from each row
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row["Batch_Name"] . "</td>
                        <td>" . $row["start_date"] . "</td>
                        <td>" . $row["end_date"] . "</td>
                        <td>
                        <a href='admin_batch_CRUD.php?action=edit&batch_name=" . $row["Batch_Name"] . "'>Edit</a> | 
                        <a href='admin_batch_CRUD.php?action=delete&batch_name=" . $row["Batch_Name"] . "'>Delete</a>
                        </td>
                    </tr>";
            }
        } else {
            echo "0 results";
        }
        ?>
        </tbody>
    </table>

    <?php if ($action == 'add' || $action == 'edit' || $action == 'delete') { ?>
        <div style="margin-top: 20px;">
            <?php if ($action == 'add') { ?>
                <h2>Add Batch</h2>
                <form action="admin_batch_CRUD.php" method="POST">
                    <label for="Batch_Name">Batch Name:</label><br>
                    <input type="text" id="Batch_Name" name="Batch_Name" required><br>
                    <label for="start_date">Start Date:</label><br>
                    <input type="date" id="start_date" name="start_date" required><br>
                    <label for="end_date">End Date:</label><br>
                    <input type="date" id="end_date" name="end_date" required><br><br>
                    <input type="submit" name="add" value="Add">
                    <a href="admin_batch_CRUD.php"><button type="button">Cancel</button></a>
                </form>
            <?php } elseif ($action == 'edit' && isset($_GET['batch_name'])) { 
                $batch_name = $_GET['batch_name'];
                $sql = "SELECT * FROM batch WHERE Batch_Name='$batch_name'";
                $result = $conn->query($sql);
                $row = $result->fetch_assoc();
                ?>
                <h2>Edit Batch</h2>
                <form action="admin_batch_CRUD.php" method="POST">
                    <input type="hidden" name="Batch_Name" value="<?php echo $batch_name; ?>">
                    <label for="start_date">Start Date:</label><br>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $row['start_date']; ?>" required><br>
                    <label for="end_date">End Date:</label><br>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $row['end_date']; ?>" required><br><br>
                    <input type="submit" name="edit" value="Edit">
                    <a href="admin_batch_CRUD.php"><button type="button">Cancel</button></a>
                </form>
            <?php } elseif ($action == 'delete' && isset($_GET['batch_name'])) { 
                $batch_name = $_GET['batch_name'];
                ?>
                <h2>Delete Batch</h2>
                <form action="admin_batch_CRUD.php" method="POST">
                    <input type="hidden" name="Batch_Name" value="<?php echo $batch_name; ?>">
                    <p>Are you sure you want to delete the batch "<?php echo $batch_name; ?>"?</p>
                    <input type="submit" name="delete" value="Delete">
                    <a href="admin_batch_CRUD.php"><button type="button">Cancel</button></a>
                </form>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div style="margin-top: 20px;">
            <a href="admin_batch_CRUD.php?action=add">Add Batch</a>
        </div>
    <?php } ?>
</div>
</body>
</html>

<?php $conn->close(); ?>
