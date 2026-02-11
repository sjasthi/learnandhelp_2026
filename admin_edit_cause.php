
<?php
    session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Cause</title>
    <!-- Include your CSS stylesheets and necessary libraries -->
    <link href="css/main.css" rel="stylesheet">
    <!-- Additional styles for form layout (you can adjust as needed) -->
    <style>
        /* Style the form layout */
        .form {
            width: 30%;
            margin: 20px auto;
            padding: 20px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
            text-align: left;
        }

        input[type="text"],
        textarea,
        input[type="email"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 3px;
            border: 1px solid #ccc;
            box-sizing: border-box;
             font-size: 1.3rem;
        }

        input[type="submit"] {
            padding: 10px 20px;
            border: none;
            border-radius: 3px;
            background-color: #4caf50;
            color: white;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }
        label{
            color: gray;
        }
    </style>
</head>
<body>
    <!-- include navbar -->
    <?php include 'show-navbar.php'; ?>
    <?php show_navbar(); ?>
    <!-- Your navigation or header content goes here -->
    <h1>Edit Cause</h1>
    <?php
    // Fetch cause details for editing
    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
        $causeId = $_GET['id'];

        // Fetch cause details from the database based on the received Cause_Id
        require 'db_configuration.php'; // Include your database configuration file

        $connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
        if ($connection->connect_error) {
            die("Connection failed: " . $connection->connect_error);
        }

        $stmt = $connection->prepare("SELECT * FROM causes WHERE Cause_Id = ?");
        $stmt->bind_param("i", $causeId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Pre-fill form fields with cause details
            $causeName = $row["Cause_name"];
            $description = $row["description"];
            $url = $row["URL"];
            $contactName = $row["Contact_name"];
            $contactEmail = $row["Contact_email"];
            $contactPhone = $row["Contact_phone"];
        } else {
            echo "Cause not found";
        }

        $stmt->close();
        $connection->close();
    }
    ?>

    <form class="form" action="admin_edit_process.php" method="post">
        <input type="hidden" name="cause_id" value="<?php echo $causeId; ?>">
        <label for="cause_name">Cause Name:</label>
        <input type="text" id="cause_name" name="cause_name" value="<?php echo $causeName; ?>" required>

        <label for="description">Description:</label>
        <textarea id="description" name="description" required><?php echo $description; ?></textarea>

        <label for="url">URL:</label>
        <input type="text" id="url" name="url" value="<?php echo $url; ?>" required>

        <label for="contact_name">Contact Name:</label>
        <input type="text" id="contact_name" name="contact_name" value="<?php echo $contactName; ?>" required>

        <label for="contact_email">Contact Email:</label>
        <input type="email" id="contact_email" name="contact_email" value="<?php echo $contactEmail; ?>" required>

        <label for="contact_phone">Contact Phone:</label>
        <input type="text" id="contact_phone" name="contact_phone" value="<?php echo $contactPhone; ?>" required>

        <input type="submit" value="Update">
    </form>
</body>
</html>
