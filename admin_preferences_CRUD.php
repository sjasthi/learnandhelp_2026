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
        $preference_name = $_POST['Preference_Name'];
        $value = $_POST['Value'];
        $sql = "INSERT INTO preferences (Preference_Name, Value) VALUES ('$preference_name', '$value')";
        $conn->query($sql);
    } elseif (isset($_POST['edit'])) {
        $preference_name = $_POST['Preference_Name'];
        $value = $_POST['Value'];
        $sql = "UPDATE preferences SET Value='$value' WHERE Preference_Name='$preference_name'";
        $conn->query($sql);
    } elseif (isset($_POST['delete'])) {
        $preference_name = $_POST['Preference_Name'];
        $sql = "DELETE FROM preferences WHERE Preference_Name='$preference_name'";
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
            $('#preferences_table thead tr').clone(true).appendTo('#preferences_table thead');
            $('#preferences_table thead tr:eq(1) th').each(function () {
                var title = $(this).text();
                $(this).html('<input type="text" placeholder="Search ' + title + '" />');
            });

            var table = $('#preferences_table').DataTable({
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
        <h1><span class="accent-text">Preferences</span></h1>
    </div>
</header>
<!-- Jquery Data Table -->
<div class="toggle_columns">
    Toggle column:
    <a class="toggle-vis" data-column="0">Preference_Name</a> -
    <a class="toggle-vis" data-column="1">Value</a> -
</div>
<div style="padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto">
    <table id="preferences_table" class="display compact">
        <thead>
        <tr>
            <th>Preference Name</th>
            <th>Value</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <!-- Populating table with data from the database-->
        <?php
        $sql = "SELECT * FROM preferences";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Create table with data from each row
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row["Preference_Name"] . "</td>
                        <td>" . $row["Value"] . "</td>
                        <td>
                        <a href='admin_preferences_CRUD.php?action=edit&preference_name=" . $row["Preference_Name"] . "'>Edit</a> | 
                        <a href='admin_preferences_CRUD.php?action=delete&preference_name=" . $row["Preference_Name"] . "'>Delete</a>
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
                <h2>Add Preference</h2>
                <form action="admin_preferences_CRUD.php" method="POST">
                    <label for="Preference_Name">Preference Name:</label><br>
                    <input type="text" id="Preference_Name" name="Preference_Name" required><br>
                    <label for="Value">Value:</label><br>
                    <input type="text" id="Value" name="Value" required><br><br>
                    <input type="submit" name="add" value="Add">
                    <a href="admin_preferences_CRUD.php"><button type="button">Cancel</button></a>
                </form>
            <?php } elseif ($action == 'edit' && isset($_GET['preference_name'])) { 
                $preference_name = $_GET['preference_name'];
                $sql = "SELECT * FROM preferences WHERE Preference_Name='$preference_name'";
                $result = $conn->query($sql);
                $row = $result->fetch_assoc();
                ?>
                <h2>Edit Preference</h2>
                <form action="admin_preferences_CRUD.php" method="POST">
                    <input type="hidden" name="Preference_Name" value="<?php echo $preference_name; ?>">
                    <label for="Value">Value:</label><br>
                    <input type="text" id="Value" name="Value" value="<?php echo $row['Value']; ?>" required><br><br>
                    <input type="submit" name="edit" value="Submit">
                    <a href="admin_preferences_CRUD.php"><button type="button">Cancel</button></a>
                </form>
            <?php } elseif ($action == 'delete' && isset($_GET['preference_name'])) { 
                $preference_name = $_GET['preference_name'];
                ?>
                <h2>Delete Preference</h2>
                <form action="admin_preferences_CRUD.php" method="POST">
                    <input type="hidden" name="Preference_Name" value="<?php echo $preference_name; ?>">
                    <p>Are you sure you want to delete the preference "<?php echo $preference_name; ?>"?</p>
                    <input type="submit" name="delete" value="Delete">
                    <a href="admin_preferences_CRUD.php"><button type="button">Cancel</button></a>
                </form>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div style="margin-top: 20px;">
            <a href="admin_preferences_CRUD.php?action=add">Add Preference</a>
        </div>
    <?php } ?>
</div>
</body>
</html>

<?php $conn->close(); ?>
