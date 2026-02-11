<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) { session_start(); }

// Block unauthorized users from accessing the page
if (isset($_SESSION['role'])) {
  if ($_SESSION['role'] != 'admin') {
    http_response_code(403); die('Forbidden');
  }
} else {
  http_response_code(403); die('Forbidden');
}

require 'db_configuration.php';
// Create connection
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
// Check connection
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// ---------------- Existing library stats ----------------
$sql = "SELECT count(*) AS num_schools, sum(current_enrollment) AS num_beneficiaries FROM `schools`";
$result = $conn->query($sql);
$total_array = $result->fetch_assoc();
$num_schools = (int)$total_array['num_schools'];
$num_beneficiaries = (int)$total_array['num_beneficiaries'];

// Total registrations (existing stat)
$sql = "SELECT count(*) AS num_registrations FROM `registrations`";
$result = $conn->query($sql);
$total_array = $result->fetch_assoc();
$num_registrations = (int)$total_array['num_registrations'];

// ---------------- NEW: Total number of users ----------------
$sql = "SELECT COUNT(*) AS num_users FROM `users`";
$resUsers = $conn->query($sql);
$num_users = 0;
if ($resUsers) {
  $row = $resUsers->fetch_assoc();
  $num_users = (int)($row['num_users'] ?? 0);
}

// ---------------- Helpers: column exists checks ----------------
if (!function_exists('col_exists')) {
  function col_exists($conn, $table, $col) {
    // Sanitize table name - only allow alphanumeric and underscores
    $safeTable = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    // Sanitize column name - only allow alphanumeric and underscores
    $safeCol = preg_replace('/[^A-Za-z0-9_]/', '', $col);
    
    // Use INFORMATION_SCHEMA which is more reliable for checking column existence
    $sql = "SELECT COUNT(*) as col_count FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND COLUMN_NAME = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
      return false; // Failed to prepare statement
    }
    
    $stmt->bind_param('ss', $safeTable, $safeCol);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
      $row = $result->fetch_assoc();
      $exists = ($row && (int)$row['col_count'] > 0);
    } else {
      $exists = false;
    }
    
    $stmt->close();
    return $exists;
  }
}

// ---------------- NEW: Registration summary by Class with emails ----------------
$registration_by_class = [];
$total_reg_count = 0;

// Check if we have the necessary tables and columns for proper joins
$registrations_has_offering_id = col_exists($conn, 'registrations', 'Offering_Id');
$registrations_has_user_id = col_exists($conn, 'registrations', 'User_Id');
$offerings_table_exists = col_exists($conn, 'offerings', 'Offering_Id');
$offerings_has_class_id = col_exists($conn, 'offerings', 'Class_Id');
$classes_table_exists = col_exists($conn, 'classes', 'Class_Id');
$classes_has_name = col_exists($conn, 'classes', 'Class_Name');
$users_has_email = col_exists($conn, 'users', 'Email');

if ($registrations_has_offering_id && $offerings_table_exists && $offerings_has_class_id && 
    $classes_table_exists && $classes_has_name && $registrations_has_user_id && $users_has_email) {
  
  // Get class registrations with emails
  $sql = "SELECT 
            COALESCE(c.Class_Name, CONCAT('Unknown Class (Offering ', r.Offering_Id, ')')) AS class_name, 
            COUNT(*) AS cnt,
            GROUP_CONCAT(DISTINCT u.Email SEPARATOR '; ') AS emails
          FROM `registrations` r
          LEFT JOIN `offerings` o ON r.Offering_Id = o.Offering_Id
          LEFT JOIN `classes` c ON o.Class_Id = c.Class_Id
          LEFT JOIN `users` u ON r.User_Id = u.User_Id
          WHERE u.Email IS NOT NULL AND u.Email != ''
          GROUP BY c.Class_Name, r.Offering_Id
          ORDER BY c.Class_Name";
  
  if ($r = $conn->query($sql)) {
    while ($row = $r->fetch_assoc()) {
      $name = $row['class_name'] !== '' ? $row['class_name'] : 'Unspecified';
      $cnt = (int)$row['cnt'];
      $emails = $row['emails'] ?? '';
      
      // If multiple offerings have the same class name, combine them
      if (isset($registration_by_class[$name])) {
        $registration_by_class[$name]['count'] += $cnt;
        // Merge emails and remove duplicates
        $existing_emails = explode('; ', $registration_by_class[$name]['emails']);
        $new_emails = explode('; ', $emails);
        $all_emails = array_unique(array_merge($existing_emails, $new_emails));
        $registration_by_class[$name]['emails'] = implode('; ', array_filter($all_emails));
      } else {
        $registration_by_class[$name] = [
          'count' => $cnt,
          'emails' => $emails
        ];
      }
      $total_reg_count += $cnt;
    }
  }
  
} else if (col_exists($conn, 'registrations', 'Class_Name') && $registrations_has_user_id && $users_has_email) {
  // Fallback: Direct Class_Name in registrations table with emails
  $sql = "SELECT 
            r.Class_Name AS class_name, 
            COUNT(*) AS cnt,
            GROUP_CONCAT(DISTINCT u.Email SEPARATOR '; ') AS emails
          FROM `registrations` r
          LEFT JOIN `users` u ON r.User_Id = u.User_Id
          WHERE u.Email IS NOT NULL AND u.Email != ''
          GROUP BY r.Class_Name
          ORDER BY r.Class_Name";
  if ($r = $conn->query($sql)) {
    while ($row = $r->fetch_assoc()) {
      $name = $row['class_name'] !== '' ? $row['class_name'] : 'Unspecified';
      $registration_by_class[$name] = [
        'count' => (int)$row['cnt'],
        'emails' => $row['emails'] ?? ''
      ];
      $total_reg_count += (int)$row['cnt'];
    }
  }
}

// ---------------- NEW: Payment Summary with emails ----------------
$payment_summary = [];
$payment_col = null;
if (col_exists($conn, 'registrations', 'payment_status')) { $payment_col = 'payment_status'; }
else if (col_exists($conn, 'registrations', 'Payment_Status')) { $payment_col = 'Payment_Status'; }

if ($payment_col && $registrations_has_user_id && $users_has_email) {
  $sql = "SELECT 
            LOWER(TRIM(r.$payment_col)) AS payment_status, 
            COUNT(*) AS cnt,
            GROUP_CONCAT(DISTINCT u.Email SEPARATOR '; ') AS emails
          FROM `registrations` r
          LEFT JOIN `users` u ON r.User_Id = u.User_Id
          WHERE u.Email IS NOT NULL AND u.Email != ''
          GROUP BY LOWER(TRIM(r.$payment_col))
          ORDER BY payment_status";
  
  if ($r = $conn->query($sql)) {
    while ($row = $r->fetch_assoc()) {
      $status = $row['payment_status'] ?? 'other';
      $payment_summary[$status] = [
        'count' => (int)$row['cnt'],
        'emails' => $row['emails'] ?? ''
      ];
    }
  }
}

// ---------------- NEW: Users Not Registered Yet ----------------
$unregistered_users = [];
$total_unregistered = 0;

// Check if both tables exist and have User_Id columns
$users_has_user_id = col_exists($conn, 'users', 'User_Id');

if ($users_has_user_id && $registrations_has_user_id) {
  // Get user details for users not in registrations table
  $user_display_fields = [];
  
  // Check what fields are available in users table for display
  if (col_exists($conn, 'users', 'First_Name')) $user_display_fields[] = 'u.First_Name';
  if (col_exists($conn, 'users', 'Last_Name')) $user_display_fields[] = 'u.Last_Name';
  if (col_exists($conn, 'users', 'Email')) $user_display_fields[] = 'u.Email';
  if (col_exists($conn, 'users', 'Phone')) $user_display_fields[] = 'u.Phone';
  
  // If no common display fields found, just use User_Id
  if (empty($user_display_fields)) {
    $user_display_fields[] = 'u.User_Id';
  }
  
  $select_fields = 'u.User_Id, ' . implode(', ', $user_display_fields);
  
  $sql = "SELECT $select_fields
          FROM `users` u
          LEFT JOIN `registrations` r ON u.User_Id = r.User_Id
          WHERE r.User_Id IS NULL
          ORDER BY u.User_Id";
  
  if ($r = $conn->query($sql)) {
    while ($row = $r->fetch_assoc()) {
      $unregistered_users[] = $row;
      $total_unregistered++;
    }
  }
}

// Cleanup
if (isset($result) && $result instanceof mysqli_result) { $result->free(); }
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <title>Reports</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
  <link href="css/main.css" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  
  <!-- DataTables CSS -->
  <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
  <!-- DataTables Buttons CSS -->
  <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
  
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <!-- DataTables Buttons JS -->
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
  <!-- JSZip for Excel export -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <!-- Buttons HTML5 export -->
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
  
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  
  <style>
    body { margin: auto; max-width: 100%; }
    .regular-table { 
      border-collapse: collapse; 
      width: 75%; 
      margin: 3rem auto; 
      table-layout: fixed; 
    }
    .regular-table th, .regular-table td { 
      border: 1px solid #ddd; 
      padding: 8px; 
      text-align: center; 
    }
    .regular-table th { 
      background-color: #f2f2f2; 
    }
    .regular-table .total-row td { 
      font-weight: 700; 
    }
    
    h2 { 
      margin-top: 4rem; 
      text-align: left; 
      width: 90%; 
      margin-left: auto; 
      margin-right: auto; 
    }
    
    /* DataTables styling */
    .dataTables_wrapper {
      width: 90%;
      margin: 2rem auto;
    }
    
    .dt-buttons {
      margin-bottom: 10px;
      float: right;
      margin-left: 10px;
    }
    
    .dt-button {
      background-color: #28a745 !important;
      color: white !important;
      border: 1px solid #28a745 !important;
      padding: 8px 16px !important;
      border-radius: 4px !important;
      font-weight: 500 !important;
      margin-right: 5px !important;
      font-size: 14px !important;
    }
    
    .dt-button:hover {
      background-color: #1e7e34 !important;
      border-color: #1e7e34 !important;
    }
    
    /* Copy email button styling */
    .copy-btn {
      background-color: #007bff;
      color: white;
      border: 1px solid #007bff;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 12px;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .copy-btn:hover {
      background-color: #0056b3;
      border-color: #0056b3;
    }
    
    .copy-btn i {
      margin-right: 4px;
    }
    
    /* Toast notification */
    .toast {
      position: fixed;
      top: 20px;
      right: 20px;
      background-color: #28a745;
      color: white;
      padding: 12px 20px;
      border-radius: 4px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      z-index: 9999;
      opacity: 0;
      transform: translateX(100%);
      transition: all 0.3s ease;
    }
    
    .toast.show {
      opacity: 1;
      transform: translateX(0);
    }
  </style>

  <script>
    function copyEmails(emails, source) {
      if (!emails || emails.trim() === '') {
        showToast('No emails to copy!', 'error');
        return;
      }
      
      // Create a temporary textarea to copy emails
      const textarea = document.createElement('textarea');
      textarea.value = emails;
      document.body.appendChild(textarea);
      textarea.select();
      
      try {
        document.execCommand('copy');
        showToast(`Emails copied to clipboard from ${source}!`, 'success');
      } catch (err) {
        showToast('Failed to copy emails', 'error');
      }
      
      document.body.removeChild(textarea);
    }
    
    function showToast(message, type) {
      // Remove existing toast if any
      const existingToast = document.querySelector('.toast');
      if (existingToast) {
        existingToast.remove();
      }
      
      // Create new toast
      const toast = document.createElement('div');
      toast.className = `toast ${type === 'error' ? 'toast-error' : ''}`;
      toast.textContent = message;
      
      if (type === 'error') {
        toast.style.backgroundColor = '#dc3545';
      }
      
      document.body.appendChild(toast);
      
      // Show toast
      setTimeout(() => toast.classList.add('show'), 100);
      
      // Hide toast after 3 seconds
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }

    $(document).ready(function() {
      // Initialize DataTables for Registration Summary
      $('#registration-table').DataTable({
        "pageLength": 25,
        "order": [[ 1, "desc" ]], // Order by count descending
        "dom": 'Blfrtip',
        "buttons": [
          {
            extend: 'excel',
            text: '<i class="fas fa-file-excel"></i> Export to Excel',
            title: 'Registration Summary - ' + new Date().toLocaleDateString(),
            filename: function() {
              return 'registration_summary_' + new Date().toISOString().slice(0,10);
            },
            exportOptions: {
              columns: [0, 1] // Exclude the Copy Emails column
            }
          }
        ],
        "columnDefs": [
          { "orderable": false, "targets": 2 } // Disable sorting on Copy Emails column
        ]
      });
      
      // Initialize DataTables for Payment Summary
      $('#payment-table').DataTable({
        "pageLength": 25,
        "order": [[ 1, "desc" ]], // Order by count descending
        "dom": 'Blfrtip',
        "buttons": [
          {
            extend: 'excel',
            text: '<i class="fas fa-file-excel"></i> Export to Excel',
            title: 'Payment Summary - ' + new Date().toLocaleDateString(),
            filename: function() {
              return 'payment_summary_' + new Date().toISOString().slice(0,10);
            },
            exportOptions: {
              columns: [0, 1] // Exclude the Copy Emails column
            }
          }
        ],
        "columnDefs": [
          { "orderable": false, "targets": 2 } // Disable sorting on Copy Emails column
        ]
      });
      
      // Initialize DataTables for Unregistered Users
      $('#unregistered-table').DataTable({
        "pageLength": 25,
        "order": [[ 0, "desc" ]], // Order by User ID descending
        "dom": 'Blfrtip',
        "buttons": [
          {
            extend: 'excel',
            text: '<i class="fas fa-file-excel"></i> Export to Excel',
            title: 'Unregistered Users - ' + new Date().toLocaleDateString(),
            filename: function() {
              return 'unregistered_users_' + new Date().toISOString().slice(0,10);
            }
          }
        ]
      });
    });
  </script>
</head>
<body>
<?php 
  include 'show-navbar.php';
  show_navbar();
?>
<header class="inverse">
  <div class="container">
    <h1><span class="accent-text">Reports</span></h1>
  </div>
</header>

<!-- MOVED TO TOP: Registration Summary -->
<h2><b>Registration Summary</b></h2>
<?php if (!empty($registration_by_class)): ?>
  <table id="registration-table" class="display compact" style="width:100%">
    <thead>
      <tr>
        <th>Class Name</th>
        <th>Count</th>
        <th>Copy Emails</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($registration_by_class as $className => $data): ?>
        <tr>
          <td><?php echo htmlspecialchars($className); ?></td>
          <td><?php echo (int)$data['count']; ?></td>
          <td style="text-align: center;">
            <button class="copy-btn" onclick="copyEmails('<?php echo htmlspecialchars($data['emails'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($className); ?>')">
              <i class="fas fa-copy"></i> Copy Emails
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <div style="width: 90%; margin: 2rem auto; text-align: center; padding: 20px; background-color: #f8f9fa; border-radius: 4px;">
    No class-level registration data found.
  </div>
<?php endif; ?>

<!-- Payment Summary with Copy Emails -->
<h2><b>Payment Summary</b></h2>
<?php if (!empty($payment_summary)): ?>
  <table id="payment-table" class="display compact" style="width:100%">
    <thead>
      <tr>
        <th>Payment Status</th>
        <th>Count</th>
        <th>Copy Emails</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($payment_summary as $status => $data): ?>
        <tr>
          <td><?php echo htmlspecialchars(ucfirst($status)); ?></td>
          <td><?php echo (int)$data['count']; ?></td>
          <td style="text-align: center;">
            <button class="copy-btn" onclick="copyEmails('<?php echo htmlspecialchars($data['emails'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($status); ?> payments')">
              <i class="fas fa-copy"></i> Copy Emails
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <div style="width: 90%; margin: 2rem auto; text-align: center; padding: 20px; background-color: #f8f9fa; border-radius: 4px;">
    No payment data found.
  </div>
<?php endif; ?>

<!-- Users Not Registered Yet as DataTable -->
<h2><b>Users Not Registered Yet</b></h2>
<?php if (!empty($unregistered_users)): ?>
  <table id="unregistered-table" class="display compact" style="width:100%">
    <thead>
      <tr>
        <th>User ID</th>
        <?php 
        // Dynamic headers based on available fields
        $sample_user = $unregistered_users[0];
        foreach ($sample_user as $field => $value) {
          if ($field !== 'User_Id') {
            $header = ucwords(str_replace('_', ' ', $field));
            echo "<th>$header</th>";
          }
        }
        ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($unregistered_users as $user): ?>
        <tr>
          <td><?php echo htmlspecialchars($user['User_Id']); ?></td>
          <?php foreach ($user as $field => $value): ?>
            <?php if ($field !== 'User_Id'): ?>
              <td><?php echo htmlspecialchars($value ?? 'N/A'); ?></td>
            <?php endif; ?>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <div style="width: 90%; margin: 2rem auto; text-align: center; padding: 20px; background-color: #f8f9fa; border-radius: 4px;">
    All users have registrations, or unable to determine unregistered users.
  </div>
<?php endif; ?>

<!-- Existing: Library Information (keeping as regular table) -->
<h2><b>Library Information</b></h2>
<table class="regular-table">
  <tr><th>Statistic</th><th>Total</th></tr>
  <tr><td>Schools With Libraries</td><td><?php echo $num_schools; ?></td></tr>
  <tr><td>Student Beneficiaries</td><td><?php echo $num_beneficiaries; ?></td></tr>
  <tr><td>Books Given To Schools</td><td>N/A</td></tr>
  <tr><td>Cost / Support Provided</td><td>N/A</td></tr>
</table>

<!-- Total Number of Users Registered (keeping as regular table) -->
<h2><b>Total Number of Users Registered</b></h2>
<table class="regular-table">
  <tr><th>Statistic</th><th>Total</th></tr>
  <tr><td>Users</td><td><?php echo $num_users; ?></td></tr>
</table>

<!-- Existing: Student Information (keeping as regular table) -->
<h2><b>Student Information</b></h2>
<table class="regular-table">
  <tr><th>Statistic</th><th>Total</th></tr>
  <tr><td>Class Registrations</td><td><?php echo $num_registrations; ?></td></tr>
  <tr><td>Earned Certification</td><td>N/A</td></tr>
  <tr><td>Success Rate</td><td>N/A</td></tr>
</table>

</body>
</html>