<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'db_configuration.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: instructors.php");
    exit();
}

$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if (isset($_POST['submit'])) {
    $first = trim($_POST['First_name']);
    $last  = trim($_POST['Last_name']);
    $bio   = trim($_POST['Bio_data']);

    
    $imagePath = '';
    if (!empty($_FILES['Image']['name'])) {
        $targetDir = "images/instructors/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['Image']['name'], PATHINFO_EXTENSION));
        $fileName = uniqid("instructor_", true) . '.' . $ext;
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['Image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    
    $stmt = $conn->prepare("INSERT INTO instructor (First_name, Last_name, Bio_data, Image) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $first, $last, $bio, $imagePath);
    if ($stmt->execute()) {
        header("Location: instructors.php");
        exit();
    } else {
        $error = "Error adding instructor: " . $conn->error;
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Instructor | Learn & Help</title>
<link rel="icon" href="images/icon_logo.png" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500&display=swap" rel="stylesheet">
<link href="css/main.css" rel="stylesheet">
<style>
body {
    font-family:'Roboto', sans-serif;
    background:#fafafa;
    margin:0;
}
.container {
    max-width: 800px;
    margin: 2rem auto;
    background: #fff;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,.1);
}
h1 {
    font-family:'Montserrat', sans-serif;
    text-align: center;
    margin-bottom: 1.5rem;
    color: #99D930;
}
label {
    font-weight: bold;
    display: block;
    margin-top: 1rem;
}
input[type=text], textarea, input[type=file] {
    width: 100%;
    padding: .6rem;
    margin-top: .4rem;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
}
textarea {
    resize: vertical;
}
button, input[type=submit] {
    background: var(--accent, #99D930);
    color: #fff;
    border: none;
    padding: .6rem 1.4rem;
    font-size: 1rem;
    border-radius: 25px;
    cursor: pointer;
    margin-top: 1rem;
}
button:hover, input[type=submit]:hover {
    background: #7da22b;
}
.error { color: red; margin-top: 1rem; }
</style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<div class="container">
    <h1>Add  Instructor</h1>
    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <label for="First_name">First Name:</label>
        <input type="text" name="First_name" id="First_name" required>

        <label for="Last_name">Last Name:</label>
        <input type="text" name="Last_name" id="Last_name" required>

        <label for="Bio_data">Bio:</label>
        <textarea name="Bio_data" id="Bio_data" rows="6" required></textarea>

        <label for="Image">Photo:</label>
        <input type="file" name="Image" id="Image" accept="image/*">

        <input type="submit" name="submit" value="Add Instructor">
    </form>
</div>

<?php include 'footer.php'; ?>

</body>
</html>
