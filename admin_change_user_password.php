<?php

// Print errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Get user ID from URL parameter
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid user ID'];
    header('Location: admin_usersList.php');
    exit();
}

// Database connection
require 'db_configuration.php';
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user details
$sql = "SELECT User_Id, First_Name, Last_Name, Email FROM users WHERE User_Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'User not found'];
    header('Location: admin_usersList.php');
    exit();
}

$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($new_password)) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Password cannot be empty'];
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Passwords do not match'];
    } elseif (strlen($new_password) < 6) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Password must be at least 6 characters long'];
    } else {
        try {
            // Hash the password
            $newHash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password in database
            $upd = $conn->prepare("UPDATE users SET Hash = ?, Modified_Time = NOW() WHERE User_Id = ?");
            if (!$upd) { 
                throw new Exception('Prepare failed (update): ' . $conn->error); 
            }
            
            $upd->bind_param('si', $newHash, $user_id);
            
            if (!$upd->execute()) {
                throw new Exception('Execute failed: ' . $upd->error);
            }
            
            $upd->close();
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Password updated successfully for ' . $user['First_Name'] . ' ' . $user['Last_Name']];
            header('Location: admin_usersList.php');
            exit();
            
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error updating password: ' . $e->getMessage()];
        }
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Change User Password - Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-right: 10px;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #545b62;
        }
        
        .user-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            border-left: 4px solid #2196f3;
        }
        
        .password-requirements {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include 'show-navbar.php'; ?>
    <?php show_navbar(); ?>
    
    <header class="inverse">
        <div class="container">
            <h1><span class="accent-text">Change User Password</span></h1>
        </div>
    </header>

    <?php if (!empty($_SESSION['flash'])): ?>
        <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="flash <?= $flash['type'] === 'success' ? 'flash-success' : 'flash-error' ?>" style="margin:16px auto;max-width:90%;padding:12px 14px;border-radius:10px;border:1px solid #ddd;font-weight:600;<?= $flash['type'] === 'success' ? 'background:#f0fff0;border-color:#b2e2b2;' : 'background:#fff5f5;border-color:#f5b5b5;' ?>">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <div class="user-info">
            <h3><i class="fas fa-user"></i> User Information</h3>
            <p><strong>ID:</strong> <?= htmlspecialchars($user['User_Id']) ?></p>
            <p><strong>Name:</strong> <?= htmlspecialchars($user['First_Name'] . ' ' . $user['Last_Name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['Email']) ?></p>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="new_password">
                    <i class="fas fa-key"></i> New Password
                </label>
                <input type="password" id="new_password" name="new_password" required>
                <div class="password-requirements">
                    Password must be at least 6 characters long
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">
                    <i class="fas fa-key"></i> Confirm New Password
                </label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Change Password
                </button>
                <button type="button" class="btn btn-secondary" onclick="location.href='admin_usersList.php'">
                    <i class="fas fa-arrow-left"></i> Back to Users List
                </button>
            </div>
        </form>
    </div>

    <script>
        // Add client-side password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        document.getElementById('new_password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value !== '') {
                if (this.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
        });
    </script>
</body>
</html>