<?php
session_start();

// Include the database configuration file
require 'db_configuration.php';

$connection = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

// Check if the connection is established
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Retrieve the User_ID from the query parameter
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
} else {
    // Handle the case where the User_ID is not provided in the URL
    $user_id = '';
}

// Retrieve user data based on the User_ID using prepared statement for security
$selectQuery = "SELECT * FROM users WHERE User_ID = ?";
$stmt = mysqli_prepare($connection, $selectQuery);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $firstname = $row['First_Name'];
    $lastname = $row['Last_Name'];
    $email = $row['Email'];
    $phone = $row['Phone'];
    $status = $row['Active'];
    $UserRole = $row['Role'];
    $notes = $row['notes'] ?? '';
    $secondary_contact_name = $row['secondary_contact_name'] ?? '';
    $secondary_contact_email = $row['secondary_contact_email'] ?? '';
    $secondary_contact_phone = $row['secondary_contact_phone'] ?? '';
    $secondary_contact_active = $row['secondary_contact_active'] ?? 0;
    $modified_time = $row['Modified_Time'];
} else {
    // Handle the case where the user data is not found (invalid User_ID)
    echo "User data not found";
    exit;
}

// Close the statement
mysqli_stmt_close($stmt);

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $firstname = $_POST['First_Name'];
    $lastname = $_POST['Last_Name'];
    $email = $_POST['email'];
    $phone = $_POST['Phone'];
    $status = $_POST['Active'];
    $UserRole = $_POST['Role'];
    $notes = $_POST['notes'];
    $secondary_contact_name = $_POST['secondary_contact_name'];
    $secondary_contact_email = $_POST['secondary_contact_email'];
    $secondary_contact_phone = $_POST['secondary_contact_phone'];
    $secondary_contact_active = isset($_POST['secondary_contact_active']) ? 1 : 0;
    
    // Set current timestamp for modified time
    date_default_timezone_set('America/Chicago');
    $modified_time = date("Y-m-d H:i:s");

    // Use prepared statement for security
    $updateQuery = "UPDATE users
                    SET
                        First_Name = ?,
                        Last_Name = ?,
                        Email = ?,
                        Phone = ?,
                        Active = ?,
                        Role = ?,
                        notes = ?,
                        secondary_contact_name = ?,
                        secondary_contact_email = ?,
                        secondary_contact_phone = ?,
                        secondary_contact_active = ?,
                        Modified_Time = ?
                    WHERE
                        User_ID = ?";

    $stmt = mysqli_prepare($connection, $updateQuery);
    mysqli_stmt_bind_param($stmt, "ssssssssssssi", 
        $firstname, $lastname, $email, $phone, $status, $UserRole, $notes,
        $secondary_contact_name, $secondary_contact_email, $secondary_contact_phone, 
        $secondary_contact_active, $modified_time, $user_id);
    
    $updateResult = mysqli_stmt_execute($stmt);

    // Check if the query was successful
    if ($updateResult) {
        // Redirect back to the main PHP file or any desired page after successful update
        header("Location: admin_usersList.php?msg=User updated successfully&type=success");
        exit;
    } else {
        // If there was an error, display the error message
        echo "Error updating user: " . mysqli_error($connection);
    }
    
    mysqli_stmt_close($stmt);
}

// Close the database connection
mysqli_close($connection);
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <title>Update User</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            text-align: left !important;
            background: #f8f8f8;
        }

        header {
            background-color: #333;
            color: #fff;
            padding: 20px;
        }

        main {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 18px rgba(0,0,0,.09);
            margin-top: 20px;
            margin-bottom: 40px;
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }

        .form {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #fafafa;
        }

        .form-section h3 {
            margin-top: 0;
            color: #99d930;
            border-bottom: 2px solid #99d930;
            padding-bottom: 5px;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        input[type="text"], input[type="tel"], input[type="email"], select, textarea {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        select {
            padding: 10px;
        }

        .radio-group {
            display: flex;
            gap: 15px;
            margin-top: 5px;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            margin-top: 0;
            font-weight: normal;
        }

        .radio-group input[type="radio"] {
            width: auto;
            margin-right: 5px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 5px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .checkbox-group label {
            margin-top: 0;
            font-weight: normal;
        }

        .updateBtn {
            padding: 12px 30px;
            margin-top: 20px;
            display: block;
            font-size: 16px;
            background-color: #99D930;
            color: #000;
            border: none;
            cursor: pointer;
            width: 100%;
            border-radius: 6px;
            font-weight: bold;
        }

        .updateBtn:hover {
            background-color: #7da22b;
            color: #fff;
        }

        .info-text {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }

        .family-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .family-status.enabled {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .family-status.disabled {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }
    </style>
</head>
<body>
    <?php 
    include 'show-navbar.php'; 
    show_navbar();
    ?>

    <main>
        <h1>👤 Update User Profile</h1>
        
        <form class="form" method="POST" action="" autocomplete="on">
            <!-- Include a hidden field for User_ID to specify the user to update -->
            <input type="hidden" name="User_ID" value="<?php echo htmlspecialchars($user_id); ?>">
            
            <!-- Basic Information Section -->
            <div class="form-section">
                <h3>📋 Basic Information</h3>
                
                <label for="First_Name">First Name *</label>
                <input type="text" name="First_Name" id="First_Name" required placeholder="First Name" value="<?php echo htmlspecialchars($firstname); ?>">
                
                <label for="Last_Name">Last Name *</label>
                <input type="text" name="Last_Name" id="Last_Name" required placeholder="Last Name" value="<?php echo htmlspecialchars($lastname); ?>">
                
                <label for="email">Email Address *</label>
                <input type="email" name="email" id="email" required placeholder="Email" value="<?php echo htmlspecialchars($email); ?>">
                
                <label for="Phone">Phone Number *</label>
                <input type="tel" name="Phone" id="Phone" required placeholder="Phone" value="<?php echo htmlspecialchars($phone); ?>">
            </div>

            <!-- Account Settings Section -->
            <div class="form-section">
                <h3>⚙️ Account Settings</h3>
                
                <label>Account Status *</label>
                <div class="radio-group">
                    <label for="active_yes">
                        <input type="radio" name="Active" id="active_yes" value="Yes" <?php if ($status == 'Yes') { echo 'checked'; } ?>>
                        Active
                    </label>
                    
                    <label for="active_no">
                        <input type="radio" name="Active" id="active_no" value="No" <?php if ($status == 'No') { echo 'checked'; } ?>>
                        Inactive
                    </label>
                </div>
                
                <label for="Role">User Role *</label>
                <select name="Role" id="Role" required>
                    <option value="admin" <?php if ($UserRole == 'admin') { echo 'selected'; } ?>>Admin</option>
                    <option value="student" <?php if ($UserRole == 'student') { echo 'selected'; } ?>>Student</option>
                    <option value="instructor" <?php if ($UserRole == 'instructor') { echo 'selected'; } ?>>Instructor</option>
                </select>
            </div>

            <!-- Family Access Section -->
            <div class="form-section">
                <h3>👨‍👩‍👧‍👦 Family Access Management</h3>
                
                <div class="family-status <?php echo $secondary_contact_active ? 'enabled' : 'disabled'; ?>">
                    <span><?php echo $secondary_contact_active ? '✅ Family access is currently enabled' : '❌ Family access is currently disabled'; ?></span>
                </div>
                
                <label for="secondary_contact_name">Secondary Contact Name (Spouse/Partner)</label>
                <input type="text" name="secondary_contact_name" id="secondary_contact_name" 
                       placeholder="e.g., Jane Smith" value="<?php echo htmlspecialchars($secondary_contact_name); ?>">
                <div class="info-text">Name of spouse or partner who should receive notifications</div>
                
                <label for="secondary_contact_email">Secondary Contact Email</label>
                <input type="email" name="secondary_contact_email" id="secondary_contact_email" 
                       placeholder="e.g., jane@example.com" value="<?php echo htmlspecialchars($secondary_contact_email); ?>">
                <div class="info-text">Email address for registration and payment notifications</div>
                
                <label for="secondary_contact_phone">Secondary Contact Phone (Optional)</label>
                <input type="tel" name="secondary_contact_phone" id="secondary_contact_phone" 
                       placeholder="e.g., (555) 123-4567" value="<?php echo htmlspecialchars($secondary_contact_phone); ?>">
                
                <div class="checkbox-group">
                    <input type="checkbox" name="secondary_contact_active" id="secondary_contact_active" 
                           <?php echo $secondary_contact_active ? 'checked' : ''; ?>>
                    <label for="secondary_contact_active">Enable family access and email notifications</label>
                </div>
                <div class="info-text">When enabled, the secondary contact will receive emails about registrations, payments, and important announcements</div>
            </div>

            <!-- Admin Notes Section -->
            <div class="form-section">
                <h3>📝 Admin Notes</h3>
                
                <label for="notes">Internal Notes</label>
                <textarea name="notes" id="notes" placeholder="Add any internal notes about this user account..." rows="4"><?php echo htmlspecialchars($notes); ?></textarea>
                <div class="info-text">These notes are only visible to administrators and are not shown to the user</div>
            </div>
            
            <button class="updateBtn" type="submit">✅ Update User Profile</button>
        </form>
    </main>
</body>
</html>