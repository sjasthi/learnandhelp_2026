<?php
session_start();

// Include the database configuration file
require 'db_configuration.php';

$connection = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

// Check if the connection is established
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Retrieve the Cause_Id from the query parameter
if (isset($_GET['id'])) {
    $cause_id = $_GET['id'];
} else {
    $cause_id = '';
}

// Retrieve cause data based on the Cause_Id
$selectQuery = "SELECT * FROM causes WHERE Cause_Id = '$cause_id'";
$result = mysqli_query($connection, $selectQuery);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $cause_name = $row['Cause_name'];
    $description = $row['description'];
    $url = $row['URL'];
    $contact_name = $row['Contact_name'];
    $contact_email = $row['Contact_email'];
    $contact_phone = $row['Contact_phone'];
} else {
    echo "Cause data not found";
    exit;
}

mysqli_free_result($result);

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $cause_name = $_POST['Cause_name'];
    $description = $_POST['description'];
    $url = $_POST['URL'];
    $contact_name = $_POST['Contact_name'];
    $contact_email = $_POST['Contact_email'];
    $contact_phone = $_POST['Contact_phone'];

    // SQL query to update cause data in the "causes" table
    $updateQuery = "UPDATE causes SET 
        Cause_name = '$cause_name',
        description = '$description',
        URL = '$url',
        Contact_name = '$contact_name',
        Contact_email = '$contact_email',
        Contact_phone = '$contact_phone'
        WHERE Cause_Id = '$cause_id'";

    $updateResult = mysqli_query($connection, $updateQuery);

    if ($updateResult) {
        header("Location: causes.php");
        exit;
    } else {
        echo "Error updating cause: " . mysqli_error($connection);
    }
}

mysqli_close($connection);
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <title>Update Cause</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            text-align: left !important;
        }

        header {
            background-color: #333;
            color: #fff;
            padding: 20px;
        }

        main {
            padding: 20px;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        h1 {
            color: #333;
        }

        .form {
            width: 400px;
            margin-top: 20px;
        }

        label {
            display: block;
            margin-top: 10px;
        }

        input[type="text"], input[type="tel"] {
            width: 100%;
            padding: 10px;
            font-size: 16px;
        }

        select {
            width: 100%;
            padding: 5px;
            font-size: 16px;
        }

        .updateBtn {
            padding: 10px 20px;
            margin-top: 10px;
            display: block;
            font-size: 16px;
            background-color: #99D930;
            color: #000;
            border: none;
            cursor: pointer;
            width: 100%;
        }
    </style>
</head>
<body>
    <?php include 'show-navbar.php'; 
    show_navbar();
    ?>

    <main>
        <h1>Update Cause</h1>
        <form class="form" method="POST" action="">
            <input type="hidden" name="Cause_ID" value="<?php echo $cause_id; ?>">
            
            <label for="Cause_Name">Cause Name</label>
            <input type="text" name="Cause_name" id="Cause_name" required placeholder="Cause Name" value="<?php echo $cause_name; ?>">

            <label for="Description">Description</label>
            <textarea name="description" id="description" required placeholder="Description"><?php echo $description; ?></textarea>

            <label for="URL">URL</label>
            <input type="text" name="URL" id="URL" required placeholder="www.example.com" value="<?php echo $url; ?>">

            <label for="Contact_Name">Contact Name</label>
            <input type="text" name="Contact_name" id="Contact_name" required placeholder="Contact Name" value="<?php echo $contact_name; ?>">

            <label for="Contact_Email">Contact Email</label>
            <input type="email" name="Contact_email" id="Contact_email" required placeholder="Contact Email" value="<?php echo $contact_email; ?>">
            <label for="Contact_Phone">Contact Phone</label>
            <input type="tel" name="Contact_phone" id="Contact_phone" required placeholder="Contact Phone" value="<?php echo $contact_phone; ?>">

            <input type="submit" value="Update Cause">
        </form>
    </main>

    
</body>
</html>