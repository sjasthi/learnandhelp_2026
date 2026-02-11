<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'db_configuration.php';
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$currentYear = date('Y');
$currentBatch = $currentYear . '-' . ($currentYear + 1);
$messages = [];
$errors = [];

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');

    if ($first_name == '')  $errors[] = 'First name required.';
    if ($last_name == '')   $errors[] = 'Last name required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';

    if (!$errors) {
        $stmt = $conn->prepare(
            'UPDATE users SET First_Name=?, Last_Name=?, Email=?, Phone=? WHERE User_Id=?');
        $stmt->bind_param('ssssi', $first_name, $last_name, $email, $phone, $user_id);
        if ($stmt->execute()) $messages[] = 'Profile updated!';
        else $errors[] = 'Profile update failed: ' . $conn->error;
        $stmt->close();
    }
}

// Handle Address Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_address'])) {
    $address = trim($_POST['address'] ?? '');
    
    // Address can be empty, so no validation required
    $stmt = $conn->prepare('UPDATE users SET Address=? WHERE User_Id=?');
    $stmt->bind_param('si', $address, $user_id);
    if ($stmt->execute()) $messages[] = 'Address updated!';
    else $errors[] = 'Address update failed: ' . $conn->error;
    $stmt->close();
}

// Handle Family Access Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_family_access'])) {
    $secondary_name = trim($_POST['secondary_contact_name'] ?? '');
    $secondary_email = trim($_POST['secondary_contact_email'] ?? '');
    $secondary_phone = trim($_POST['secondary_contact_phone'] ?? '');
    $secondary_active = isset($_POST['secondary_contact_active']) ? 1 : 0;

    // Validation
    if ($secondary_active && $secondary_name == '') {
        $errors[] = 'Secondary contact name is required when family access is enabled.';
    }
    if ($secondary_active && $secondary_email != '' && !filter_var($secondary_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email for secondary contact.';
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            'UPDATE users SET secondary_contact_name=?, secondary_contact_email=?, secondary_contact_phone=?, secondary_contact_active=? WHERE User_Id=?');
        $stmt->bind_param('sssii', $secondary_name, $secondary_email, $secondary_phone, $secondary_active, $user_id);
        if ($stmt->execute()) {
            $messages[] = $secondary_active ? 'Family access updated and enabled!' : 'Family access updated and disabled.';
        } else {
            $errors[] = 'Family access update failed: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Get user data including secondary contact info
$stmt = $conn->prepare(
    'SELECT First_Name, Last_Name, Email, Phone, Address, secondary_contact_name, secondary_contact_email, secondary_contact_phone, secondary_contact_active FROM users WHERE User_Id=?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($first_name, $last_name, $email, $phone, $address, $sec_name, $sec_email, $sec_phone, $sec_active);
$user = [];
if ($stmt->fetch()) {
    $user['First_Name'] = $first_name;
    $user['Last_Name']  = $last_name;
    $user['Email']      = $email;
    $user['Phone']      = $phone;
    $user['Address']    = $address;
    $user['secondary_contact_name'] = $sec_name;
    $user['secondary_contact_email'] = $sec_email;
    $user['secondary_contact_phone'] = $sec_phone;
    $user['secondary_contact_active'] = $sec_active;
}
$stmt->close();

// Handle Active Registration Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_active'])) {
    $stmt = $conn->prepare(
        'SELECT Student_Name, Student_Photo FROM registrations WHERE User_Id = ? AND Batch_Name = ?');
    $stmt->bind_param('is', $user_id, $currentBatch);
    $stmt->execute();
    $stmt->bind_result($db_student_name, $db_student_photo);
    $activeReg = [];
    if ($stmt->fetch()) {
        $activeReg['Student_Name']  = $db_student_name;
        $activeReg['Student_Photo'] = $db_student_photo;
    }
    $stmt->close();

    if (!empty($activeReg)) {
        $studentName = trim($_POST['student_name'] ?? '');
        if ($studentName === '') { $errors[] = 'Student name cannot be empty.'; }

        $photoPath = $activeReg['Student_Photo'] ?? '';
        if (!empty($_FILES['student_photo']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
            $fileType = mime_content_type($_FILES['student_photo']['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP, AVIF.';
            } elseif ($_FILES['student_photo']['size'] > (2 * 1024 * 1024)) {
                $errors[] = 'Image must be under 2MB.';
            } else {
                $ext = strtolower(pathinfo($_FILES['student_photo']['name'], PATHINFO_EXTENSION));
                $unique = uniqid("s{$user_id}_y{$currentYear}_", true);
                $targetDir = 'images/students/';
                if (!is_dir($targetDir)) { mkdir($targetDir, 0755, true); }
                $savedFile = $targetDir . $unique . '.' . $ext;
                if (move_uploaded_file($_FILES['student_photo']['tmp_name'], $savedFile)) {
                    if ($photoPath && file_exists($photoPath) && $photoPath !== $savedFile) {
                        @unlink($photoPath);
                    }
                    $photoPath = $savedFile;
                } else { $errors[] = 'Error uploading photo.'; }
            }
        }

        if (!$errors) {
            $stmt = $conn->prepare(
                'UPDATE registrations SET Student_Name = ?, Student_Photo = ? WHERE User_Id = ? AND Batch_Name = ?');
            $stmt->bind_param('ssis', $studentName, $photoPath, $user_id, $currentBatch);
            if ($stmt->execute()) $messages[] = 'Registration updated.';
            else $errors[] = 'Could not update registration.';
            $stmt->close();
        }
    } else {
        $errors[] = 'No active registration found for the current batch.';
    }
}

// Get Active Registrations
$stmt = $conn->prepare(
    'SELECT r.Student_Name, r.Student_Photo, r.Payment_Status, c.Class_Name
     FROM registrations r
     JOIN classes c ON r.Class_Id = c.Class_Id
     WHERE r.User_Id = ? AND r.Batch_Name = ?'
);
$stmt->bind_param('is', $user_id, $currentBatch);
$stmt->execute();
$stmt->bind_result($active_student_name, $active_student_photo, $active_payment_status, $active_class_name);
$activeRegs = [];
$hasPendingPayments = false;
$hasPartialPayments = false;
while ($stmt->fetch()) {
    $activeRegs[] = [
        'Student_Name'   => $active_student_name,
        'Student_Photo'  => $active_student_photo,
        'Payment_Status' => $active_payment_status,
        'Class_Name'     => $active_class_name
    ];
    $status = strtolower($active_payment_status);
    if ($status === 'pending') {
        $hasPendingPayments = true;
    } elseif ($status === 'partial') {
        $hasPartialPayments = true;
    }
}
$stmt->close();

// Get Past Registrations
$stmt = $conn->prepare(
    'SELECT r.Batch_Name, r.Student_Name, r.Student_Photo, c.Class_Name
     FROM registrations r
     JOIN classes c ON r.Class_Id = c.Class_Id
     WHERE r.User_Id = ? AND r.Batch_Name <> ?
     ORDER BY r.Batch_Name DESC'
);
$stmt->bind_param('is', $user_id, $currentBatch);
$stmt->execute();
$stmt->bind_result($batch_name, $student_name, $student_photo, $class_name);

$registrationRows = [];
while ($stmt->fetch()) {
    $registrationRows[] = [
        'Batch_Name'    => $batch_name,
        'Student_Name'  => $student_name,
        'Student_Photo' => $student_photo,
        'Class_Name'    => $class_name,
    ];
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Account</title>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
  <style>
    .banner-wrapper {
      position: relative;
      width: 100vw;
      left: 50%;
      margin-left: -50vw;
      height: 200px;
      background: #fff;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0,0,0,.08);
    }
    .banner-wrapper img {
      position: absolute;
      top: 0; left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 0;
      box-shadow: none;
    }
    @media (max-width:700px) {
      .banner-wrapper { height: 207px; }
    }
    .banner-title {
      position: absolute;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      margin: 0;
      font-family: 'Montserrat',sans-serif;
      font-size: 3em;
      font-weight: 700;
      color: #252525;
      text-shadow: 0 2px 16px rgba(0,0,0,0.50);
      text-align: center;
      letter-spacing: 1px;
      z-index: 2;
    }
    body { font-family: Arial, sans-serif; margin:0; background:#f8f8f8; }
    .container { max-width:900px; margin:0 auto; background:#fff; padding:32px 26px; border-radius:10px; box-shadow:0 4px 18px rgba(0,0,0,.09);}
    h2 { color:#99d930; margin-bottom:8px;}
    .container img {
      max-width:120px;
      max-height:120px;
      border-radius:8px;
      box-shadow:0 2px 8px #ddd;
    }
    .active-class-label { font-weight:bold; color:#252525; font-size:1.1em; }
    .active-class-value { color:#337a20; }
    table { border-collapse:collapse; width:100%; margin-top:22px;}
    th,td { border:1px solid #ddd; padding:8px; text-align:center; background:#fafbf8;}
    th { background:#edfae5;}
    label { display:block; margin:10px 0 4px;}
    .btn { background:#99d930; border:none; border-radius:6px; color:#252525; padding:10px 22px; font-weight:700; cursor:pointer;}
    .btn:hover { background:#7da22b; color:#fff;}
    .message { color:#168d10; padding:12px 0;}
    .error { color:#b30000; padding:12px 0;}
    input[type="text"], input[type="email"], input[type="file"] { width: 100%; padding: 8px; }
    form { margin-bottom: 24px; }
    
    /* Payment Status Styles */
    .payment-status {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-weight: bold;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.9em;
    }
    .payment-status.pending {
      color: #d63384;
      background-color: #f8d7da;
      border: 1px solid #f5c2c7;
    }
    .payment-status.partial {
      color: #fd7e14;
      background-color: #fff3cd;
      border: 1px solid #ffecb5;
    }
    .payment-status.paid {
      color: #198754;
      background-color: #d1e7dd;
      border: 1px solid #badbcc;
    }
    .payment-status.free {
      color: #0d6efd;
      background-color: #cff4fc;
      border: 1px solid #b6effb;
    }
    .payment-status-icon {
      font-size: 1.1em;
    }
    
    /* Payment Instructions Box */
    .payment-instructions {
      background: #fff3cd;
      border: 1px solid #ffecb5;
      border-radius: 8px;
      padding: 20px;
      margin-top: 20px;
      color: #664d03;
    }
    .payment-instructions h3 {
      color: #664d03;
      margin-top: 0;
      margin-bottom: 15px;
      font-size: 1.2em;
    }
    .payment-instructions ul {
      margin: 10px 0;
      padding-left: 25px;
    }
    .payment-instructions li {
      margin-bottom: 8px;
    }
    .payment-highlight {
      background: #ffeaa7;
      padding: 2px 4px;
      border-radius: 3px;
      font-weight: bold;
    }
    
    /* Family Access Styles */
    .family-access-section {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 20px;
      margin: 24px 0;
    }
    .family-access-section h3 {
      color: #495057;
      margin-top: 0;
      margin-bottom: 15px;
    }
    .family-status {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 12px;
      border-radius: 6px;
      font-weight: bold;
      margin-bottom: 15px;
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
    .checkbox-container {
      display: flex;
      align-items: center;
      gap: 8px;
      margin: 15px 0;
    }
    .checkbox-container input[type="checkbox"] {
      width: auto;
    }
    .info-box {
      background: #e7f3ff;
      border: 1px solid #b3d7ff;
      border-radius: 6px;
      padding: 15px;
      margin: 15px 0;
      color: #0c5460;
    }
    .info-box h4 {
      margin-top: 0;
      color: #0c5460;
    }
  </style>
</head>
<body>
<?php if (isset($_GET['msg'])): ?>
<style>
.alert-success { background:#d4edda; color:#155724; padding:10px; margin:10px 0; border-radius:5px; }
.alert-error   { background:#f8d7da; color:#721c24; padding:10px; margin:10px 0; border-radius:5px; }
.alert-info    { background:#d1ecf1; color:#0c5460; padding:10px; margin:10px 0; border-radius:5px; }
</style>
<div class="alert-<?php echo htmlspecialchars($_GET['type'] ?? 'info'); ?>">
    <?php echo htmlspecialchars($_GET['msg']); ?>
</div>
<?php endif; ?>

<?php include 'show-navbar.php'; show_navbar(); ?>

<div class="banner-wrapper">
  <img src="images/banner_images/account2.jpg" alt="Account banner">
  <h1 class="banner-title">My Account</h1>
   <h2> Use this page to update your profile and password, and to view your registrations.</h2>
</div>

<?php
/* Flash message handling */
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']); // clear it so it shows only once

    $flashClass = ($flash['type'] === 'success') ? 'alert-success' : 'alert-danger';
    echo "<div class='alert $flashClass' style='margin:15px; padding:10px; border-radius:5px;'>
            {$flash['message']}
          </div>";
}
?>

<div class="container">
  <?php foreach ($messages as $msg): ?>
    <div class="message"><?= htmlspecialchars($msg) ?></div>
  <?php endforeach; ?>
  <?php foreach ($errors as $err): ?>
    <div class="error"><?= htmlspecialchars($err) ?></div>
  <?php endforeach; ?>

  <div class="account-section">
    <h3>Profile Management</h3>
    <p>If you want to update your profile, use this form</p>
  </div>

  <form method="post">
    <label for="first_name">First Name:</label>
    <input type="text" name="first_name" id="first_name" required value="<?= htmlspecialchars($user['First_Name'] ?? '') ?>">

    <label for="last_name">Last Name:</label>
    <input type="text" name="last_name" id="last_name" required value="<?= htmlspecialchars($user['Last_Name'] ?? '') ?>">

    <label for="email">Email:</label>
    <input type="email" name="email" id="email" required value="<?= htmlspecialchars($user['Email'] ?? '') ?>">

    <label for="phone">Phone:</label>
    <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($user['Phone'] ?? '') ?>">

    <button type="submit" class="btn" name="update_profile">Update Profile</button>
  </form>

  <div class="account-section">
    <h3>Update Address</h3>
    <p>Enter or update your mailing/postal address</p>
  </div>

  <form method="post">
    <label for="address">Address:</label>
    <textarea name="address" id="address" rows="4" placeholder="Enter your complete mailing address" style="width: 100%; box-sizing: border-box;"><?= htmlspecialchars($user['Address'] ?? '') ?></textarea>
    
    <button type="submit" class="btn" name="update_address">Update Address</button>
  </form>
  
  <!-- Family Access Section -->
  <div class="family-access-section">
    <h3>👨‍👩‍👧‍👦 Family Access Management</h3>
    
    <?php if ($user['secondary_contact_active']): ?>
      <div class="family-status enabled">
        <span>✅ Family access is currently enabled</span>
      </div>
      <?php if (!empty($user['secondary_contact_name'])): ?>
        <p><strong>Secondary Contact:</strong> <?= htmlspecialchars($user['secondary_contact_name']) ?>
        <?php if (!empty($user['secondary_contact_email'])): ?>
          (<?= htmlspecialchars($user['secondary_contact_email']) ?>)
        <?php endif; ?>
        </p>
      <?php endif; ?>
    <?php else: ?>
      <div class="family-status disabled">
        <span>❌ Family access is currently disabled</span>
      </div>
    <?php endif; ?>
    
    <div class="info-box">
      <h4>About Family Access</h4>
      <p>Enable your spouse or partner to receive important registration updates and notifications. 
      When enabled, your secondary contact will receive emails about:</p>
      <ul>
        <li>Registration confirmations and updates</li>
        <li>Payment reminders and confirmations</li>
        <li>Important school announcements</li>
        <li>Class schedules and event notifications</li>
      </ul>
      <p><strong>Note:</strong> This does not give them login access to your account. They will only receive email notifications.</p>
    </div>

    <form method="post">
      <label for="secondary_contact_name">Secondary Contact Name (Spouse/Partner):</label>
      <input type="text" name="secondary_contact_name" id="secondary_contact_name" 
             value="<?= htmlspecialchars($user['secondary_contact_name'] ?? '') ?>" 
             placeholder="e.g., Jane Smith">

      <label for="secondary_contact_email">Secondary Contact Email:</label>
      <input type="email" name="secondary_contact_email" id="secondary_contact_email" 
             value="<?= htmlspecialchars($user['secondary_contact_email'] ?? '') ?>" 
             placeholder="e.g., jane@example.com">

      <label for="secondary_contact_phone">Secondary Contact Phone (Optional):</label>
      <input type="text" name="secondary_contact_phone" id="secondary_contact_phone" 
             value="<?= htmlspecialchars($user['secondary_contact_phone'] ?? '') ?>" 
             placeholder="e.g., (555) 123-4567">

      <div class="checkbox-container">
        <input type="checkbox" name="secondary_contact_active" id="secondary_contact_active" 
               <?= $user['secondary_contact_active'] ? 'checked' : '' ?>>
        <label for="secondary_contact_active" style="margin:0;">Enable family access and email notifications</label>
      </div>

      <button type="submit" class="btn" name="update_family_access">Update Family Access</button>
    </form>
  </div>

  <!-- Password Management Section -->
  <div class="account-section">
    <h3>Password Management</h3>
    <p>If you want to update your password, click the button below:</p>
    <form action="account_password_change.php" method="get">
        <button type="submit" class="btn btn-warning">Change Password</button>
    </form>
  </div>

  <h2 style="margin-top:28px;">Active Registration (<?= htmlspecialchars($currentBatch) ?>)</h2>
  <?php if (!empty($activeRegs)): ?>
    <table>
      <thead>
        <tr>
          <th>Year</th>
          <th>Student Name</th>
          <th>Class</th>
          <th>Payment Status</th>
          <th>Student Photo</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($activeRegs as $reg): ?>
  <tr>
    <td><?= htmlspecialchars($currentBatch) ?></td>
    <td><?= htmlspecialchars($reg['Student_Name'] ?? '-') ?></td>
    <td><?= htmlspecialchars($reg['Class_Name'] ?? '-') ?></td>
    <td>
      <?php 
      $status = strtolower($reg['Payment_Status'] ?? 'pending');
      $statusIcons = [
        'pending' => '⚠️',
        'paid' => '✅',
        'free' => 'ℹ️',
        'partial' => '⏳'
      ];
      $statusTexts = [
        'pending' => 'Payment Required',
        'paid' => 'Paid',
        'free' => 'Free Registration',
        'partial' => 'Partial Payment'
      ];
      ?>
      <span class="payment-status <?= $status ?>">
        <span class="payment-status-icon"><?= $statusIcons[$status] ?? '⚠️' ?></span>
        <?= $statusTexts[$status] ?? 'Payment Required' ?>
      </span>
    </td>
    <td>
      <?php if (!empty($reg['Student_Photo']) && file_exists($reg['Student_Photo'])): ?>
        <img src="<?= htmlspecialchars($reg['Student_Photo']) ?>" alt="Photo" style="max-width:80px;max-height:80px;border-radius:8px;">
      <?php else: ?>
        <img src="images/banner_images/Classes/avatar.jpg" alt="Photo" style="max-width:80px;max-height:80px;border-radius:8px;">
      <?php endif; ?>
    </td>
  </tr>
<?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($hasPendingPayments || $hasPartialPayments): ?>
    <div class="payment-instructions">
      <h3>⚠️ Payment Required</h3>
      <?php if ($hasPendingPayments): ?>
        <p><strong>You have pending payments for the current registration period.</strong></p>
      <?php endif; ?>
      <?php if ($hasPartialPayments): ?>
        <p><strong>You have partial payments that need to be completed.</strong></p>
      <?php endif; ?>
      
      <h4>How to Pay:</h4>
      <ul>
        <?php if ($hasPendingPayments): ?>
          <li>For <strong>pending registrations</strong>: Use zelle or venmo (651.276.4671) to pay <strong>$600</strong></li>
        <?php endif; ?>
        <?php if ($hasPartialPayments): ?>
          <li>For <strong>partial payments</strong>: Use zelle or venmo (651.276.4671) to pay remaining <strong>$300</strong></li>
        <?php endif; ?>
        <li>For Check or Bank Transfers, please contact Siva.Jasthi@gmail.com (651 276 4671) for details</li>
      </ul>
      
      
      <h4>Registration Fee</h4>
<p><strong>$600</strong> if paid on or before <strong>September 12</strong>.</p>
<p>Beginning <strong>September 13</strong>, a <strong>$50</strong> late fee will apply.</p>
<p><br>Registration will be cancelled on <strong>September 22</strong> for non-payment.</p>
      <ul>
        <li>For questions, contact the office at <span class="payment-highlight">siva.jasthi@gmail.com</span> or <span class="payment-highlight">(651) 276-2671</span></li>
      </ul>
    </div>
    <?php endif; ?>

<?php else: ?>
    <p style="color:#555;">You have no active registration for the current year. (<?= htmlspecialchars($currentBatch) ?>).</p>
<?php endif; ?>
  
  <h2 style="margin-top:34px;">Past Registrations</h2>
  <?php if (!empty($registrationRows)): ?>
    <table>
      <thead>
        <tr>
          <th>Year</th>
          <th>Student Name</th>
          <th>Class</th>
          <th>Student Photo</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($registrationRows as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['Batch_Name'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['Student_Name'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['Class_Name'] ?? '-') ?></td>
            <td>
              <?php if (!empty($user['Student_Photo'] ?? '') && file_exists($user['Student_Photo'])): ?>
                <img src="<?= htmlspecialchars($user['Student_Photo']) ?>" alt="Photo" style="max-width:80px;max-height:80px;border-radius:8px;">
              <?php else: ?>
                <img src="images/banner_images/Classes/avatar.jpg" alt="Photo" style="max-width:80px;max-height:80px;border-radius:8px;">
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>No registrations found.</p>
  <?php endif; ?>

  <?php if (!empty($activeReg)): ?>
    <div style="margin-top:34px;">
      <form method="POST" enctype="multipart/form-data">
        <label for="student_name" style="margin-top:5px;">Change Student Name:</label>
        <input type="text" id="student_name" name="student_name" value="<?= htmlspecialchars($activeReg['Student_Name'] ?? '') ?>" required>
        <label for="student_photo" style="margin-top:10px;">Change Photo:</label>
        <input type="file" id="student_photo" name="student_photo" accept="image/*">
        <button class="btn" type="submit" name="update_active" style="margin-top:10px;">Update Registration</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>