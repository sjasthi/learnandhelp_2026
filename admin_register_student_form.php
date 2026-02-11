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

// Get user ID from URL parameter
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid user ID provided.'];
    header('Location: admin_usersList.php');
    exit;
}

// Create connection
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user information
$stmt = $conn->prepare("SELECT User_Id, First_Name, Last_Name, Email FROM users WHERE User_Id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows === 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'User not found.'];
    header('Location: admin_usersList.php');
    exit;
}

$user = $user_result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = trim($_POST['student_name'] ?? '');
    $student_email = trim($_POST['student_email'] ?? '');
    $student_phone = trim($_POST['student_phone'] ?? '');
    $offering_id = (int)($_POST['offering_id'] ?? 0);
    $class_id = (int)($_POST['class_id'] ?? 0);
    $batch_name = trim($_POST['batch_name'] ?? '2025-2026');
    
    // Validation
    $errors = [];
    if (empty($student_name)) $errors[] = 'Student name is required.';
    if (empty($student_email)) $errors[] = 'Student email is required.';
    if (!filter_var($student_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($offering_id <= 0) $errors[] = 'Please enter a valid offering ID.';
    if ($class_id <= 0) $errors[] = 'Please enter a valid class ID.';
    
    if (empty($errors)) {
        // Insert registration
        $insert_sql = "INSERT INTO registrations (User_Id, Sponsor1_Name, Sponsor1_Email, Student_Name, Student_Email, Student_Phone_Number, offering_id, Class_Id, Batch_Name, Created_Time, Modified_Time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $sponsor_name = $user['First_Name'] . ' ' . $user['Last_Name'];
        $sponsor_email = $user['Email'];
        
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isssssiss", $user_id, $sponsor_name, $sponsor_email, $student_name, $student_email, $student_phone, $offering_id, $class_id, $batch_name);
        
        if ($insert_stmt->execute()) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Registered. Please check Administration --> Registrations'];
            header('Location: admin_usersList.php');
            exit;
        } else {
            $errors[] = 'Failed to register student. Please try again.';
        }
        $insert_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Register Student</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        
        .form-group input[readonly] {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        
        .btn-submit {
            background-color: #28a745;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-submit:hover {
            background-color: #218838;
        }
        
        .btn-cancel {
            background-color: #6c757d;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            margin-left: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        
        .btn-cancel:hover {
            background-color: #5a6268;
        }
        
        .error-messages {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
        }
        
        .error-messages ul {
            margin: 0;
            padding-left: 1.5rem;
        }
    </style>
</head>
<body>
<?php include 'show-navbar.php'; ?>
<?php show_navbar(); ?>

<header class="inverse">
    <div class="container">
        <h1><span class="accent-text">Register Student</span></h1>
    </div>
</header>

<div class="form-container">
    <h2>Register Student for User: <?= htmlspecialchars($user['First_Name'] . ' ' . $user['Last_Name']) ?></h2>
    
    <?php if (!empty($errors)): ?>
        <div class="error-messages">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="user_id">User ID:</label>
            <input type="text" id="user_id" name="user_id" value="<?= htmlspecialchars($user['User_Id']) ?>" readonly>
        </div>
        
        <div class="form-group">
            <label for="sponsor_name">Sponsor Name:</label>
            <input type="text" id="sponsor_name" name="sponsor_name" value="<?= htmlspecialchars($user['First_Name'] . ' ' . $user['Last_Name']) ?>" readonly>
        </div>
        
        <div class="form-group">
            <label for="sponsor_email">Sponsor Email:</label>
            <input type="email" id="sponsor_email" name="sponsor_email" value="<?= htmlspecialchars($user['Email']) ?>" readonly>
        </div>
        
        <div class="form-group">
            <label for="student_name">Student Name: <span style="color: red;">*</span></label>
            <input type="text" id="student_name" name="student_name" value="<?= htmlspecialchars($_POST['student_name'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="student_email">Student Email: <span style="color: red;">*</span></label>
            <input type="email" id="student_email" name="student_email" value="<?= htmlspecialchars($_POST['student_email'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="student_phone">Student Phone:</label>
            <input type="tel" id="student_phone" name="student_phone" value="<?= htmlspecialchars($_POST['student_phone'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="offering_id">Offering ID: <span style="color: red;">*</span></label>
            <input type="number" id="offering_id" name="offering_id" value="<?= htmlspecialchars($_POST['offering_id'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="class_id">Class ID: <span style="color: red;">*</span></label>
            <input type="number" id="class_id" name="class_id" value="<?= htmlspecialchars($_POST['class_id'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="batch_name">Batch Name:</label>
            <input type="text" id="batch_name" name="batch_name" value="<?= htmlspecialchars($_POST['batch_name'] ?? '2025-2026') ?>">
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn-submit">Register Student</button>
            <a href="admin_usersList.php" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>