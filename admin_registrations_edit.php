<?php
/**
 * Refactored admin_registrations_edit.php
 * - Displays a form to edit a single registration record
 * - Prefills all fields from DB
 * - Updates on POST using prepared statements
 * - Removed Sponsor2_Name, Sponsor2_Email, Sponsor2_Phone_Number fields
 * - Added navigation bar and banner similar to classes.php
 *
 * Expects: ?reg_id=<Reg_Id>
 */

session_start();

// Debug (optional)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once 'db_configuration.php';

function h($v) {
    return htmlspecialchars((string)$v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$dsn_ok = true;
$errors = [];
$info = [];

$mysqli = @new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($mysqli->connect_errno) {
    $dsn_ok = false;
    $errors[] = "Database connection failed: " . h($mysqli->connect_error);
}

// Determine Reg_Id
$reg_id = null;
if (isset($_GET['reg_id'])) {
    $reg_id = (int)$_GET['reg_id'];
} elseif (isset($_POST['Reg_Id'])) {
    $reg_id = (int)$_POST['Reg_Id'];
}

if (!$dsn_ok) {
    // Render minimal error page
    echo "<h2>Registration Editor</h2>";
    foreach ($errors as $e) {
        echo '<div style=\"color:#b00020;\">' . $e . '</div>';
    }
    exit;
}

$table = 'registrations'; // Change here if your table name differs

// Handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__save']) && $_POST['__save'] === '1') {
    // Collect and sanitize inputs
    $fields = [
        'Reg_Id' => FILTER_VALIDATE_INT,
        'offering_id' => FILTER_VALIDATE_INT,
        'Sponsor1_Name' => FILTER_UNSAFE_RAW,
        'Sponsor1_Email' => FILTER_UNSAFE_RAW,
        'Sponsor1_Phone_Number' => FILTER_UNSAFE_RAW,
        'Student_Name' => FILTER_UNSAFE_RAW,
        'Student_Email' => FILTER_UNSAFE_RAW,
        'Student_Phone_Number' => FILTER_UNSAFE_RAW,
        'Class_Id' => FILTER_VALIDATE_INT,
        'Modified_Time' => FILTER_UNSAFE_RAW,
        'Created_Time' => FILTER_UNSAFE_RAW,
        'Batch_Name' => FILTER_UNSAFE_RAW,
        'User_Id' => FILTER_VALIDATE_INT,
        'Student_Photo' => FILTER_UNSAFE_RAW,
        'Has_Python_Cert' => FILTER_UNSAFE_RAW,
        'classes_taken' => FILTER_UNSAFE_RAW,
        'Library_setup' => FILTER_UNSAFE_RAW,
        'School_support' => FILTER_UNSAFE_RAW,
        'Payment_method' => FILTER_UNSAFE_RAW,
        'Referral_Name' => FILTER_UNSAFE_RAW,
        'payment_status' => FILTER_UNSAFE_RAW,
        'payment_amount' => FILTER_VALIDATE_INT,
        'notes' => FILTER_UNSAFE_RAW,
    ];
    $input = filter_input_array(INPUT_POST, $fields);

    // Basic validations
    if (empty($input['Reg_Id'])) $errors[] = "Reg_Id is required.";
    if (empty($input['offering_id'])) $errors[] = "offering_id is required.";
    if (empty($input['Batch_Name'])) $errors[] = "Batch_Name is required.";
    if (!in_array($input['payment_status'], ['paid','pending','free','partial','void','withdrawn'], true)) {
        $errors[] = "payment_status must be one of paid, pending, free, partial, void, withdrawn.";
    }
    if ($input['payment_amount'] !== null && $input['payment_amount'] !== false) {
        if ($input['payment_amount'] < 0 || $input['payment_amount'] > 9999) {
            $errors[] = "payment_amount must be between 0 and 9999.";
        }
    } else {
        // normalize null/false to 0
        $input['payment_amount'] = 0;
    }
    if (!in_array($input['Has_Python_Cert'], ['Yes','No'], true)) {
        $errors[] = "Has_Python_Cert must be Yes or No.";
    }
    // Dates: allow empty; if provided, accept YYYY-MM-DD format
    $date_regex = '/^\d{4}-\d{2}-\d{2}$/';
    if (!empty($input['Created_Time']) && !preg_match($date_regex, $input['Created_Time'])) {
        $errors[] = "Created_Time must be YYYY-MM-DD.";
    }
    // We will automatically set Modified_Time to current year on update
    $input['Modified_Time'] = date('Y');

    if (!$errors) {
        $sql = "UPDATE `$table` SET
            `offering_id` = ?,
            `Sponsor1_Name` = ?,
            `Sponsor1_Email` = ?,
            `Sponsor1_Phone_Number` = ?,
            `Student_Name` = ?,
            `Student_Email` = ?,
            `Student_Phone_Number` = ?,
            `Class_Id` = ?,
            `Modified_Time` = ?,
            `Batch_Name` = ?,
            `User_Id` = ?,
            `Student_Photo` = ?,
            `Has_Python_Cert` = ?,
            `classes_taken` = ?,
            `Library_setup` = ?,
            `School_support` = ?,
            `Payment_method` = ?,
            `Referral_Name` = ?,
            `payment_status` = ?,
            `payment_amount` = ?,
            `notes` = ?
            WHERE `Reg_Id` = ?
        ";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            $errors[] = "Prepare failed: " . h($mysqli->error);
        } else {
            
            // Build types and params dynamically to guarantee match count
            $update_spec = [
                ['offering_id','i', $input['offering_id']],
                ['Sponsor1_Name','s', $input['Sponsor1_Name']],
                ['Sponsor1_Email','s', $input['Sponsor1_Email']],
                ['Sponsor1_Phone_Number','s', $input['Sponsor1_Phone_Number']],
                ['Student_Name','s', $input['Student_Name']],
                ['Student_Email','s', $input['Student_Email']],
                ['Student_Phone_Number','s', $input['Student_Phone_Number']],
                ['Class_Id','i', $input['Class_Id']],
                ['Modified_Time','s', $input['Modified_Time']],
                ['Batch_Name','s', $input['Batch_Name']],
                ['User_Id','i', $input['User_Id']],
                ['Student_Photo','s', $input['Student_Photo']],
                ['Has_Python_Cert','s', $input['Has_Python_Cert']],
                ['classes_taken','s', $input['classes_taken']],
                ['Library_setup','s', $input['Library_setup']],
                ['School_support','s', $input['School_support']],
                ['Payment_method','s', $input['Payment_method']],
                ['Referral_Name','s', $input['Referral_Name']],
                ['payment_status','s', $input['payment_status']],
                ['payment_amount','i', $input['payment_amount']],
                ['notes','s', $input['notes']],
                // WHERE
                ['Reg_Id','i', $input['Reg_Id']],
            ];

            // Normalize ints to null when empty
            foreach (['Class_Id','User_Id','payment_amount'] as $ik) {
                $idx = array_search($ik, array_column($update_spec, 0), true);
                if ($idx !== false) {
                    if ($update_spec[$idx][2] === '' || $update_spec[$idx][2] === false) {
                        $update_spec[$idx][2] = null;
                    } elseif ($update_spec[$idx][2] !== null) {
                        $update_spec[$idx][2] = (int)$update_spec[$idx][2];
                    }
                }
            }

            // Build types string and params array
            $types = '';
            $params = [];
            foreach ($update_spec as $triple) {
                $types .= $triple[1];
                $params[] = $triple[2];
            }

            // Prepare bind_param call with references
            $bind_params = array_merge([$types], $params);
            $refs = [];
            foreach ($bind_params as $k => $v) {
                $refs[$k] =& $bind_params[$k];
            }

            // Sanity-check: number of '?' should equal count($params) - 1 for WHERE included
            $qm = substr_count($sql, '?');
            if ($qm !== count($params)) {
                $errors[] = "Internal mismatch: placeholders ($qm) vs params (" . count($params) . ").";
            } else {
                if (!call_user_func_array([$stmt, 'bind_param'], $refs)) {
                    $errors[] = "bind_param failed. Types=$types Count=" . strlen($types);
                } else {
                    if (!$stmt->execute()) {
                        $errors[] = "Update failed: " . h($stmt->error);
                    } else {
                        $info[] = "Registration (Reg_Id=" . h($input['Reg_Id']) . ") updated successfully.";
                        $reg_id = (int)$input['Reg_Id'];
                    }
                }
            }

            $stmt->close();
        }
    }
}

// Fetch row to prefill
$row = null;
if ($reg_id !== null) {
    $stmt = $mysqli->prepare("SELECT
        `Reg_Id`,
        `offering_id`,
        `Sponsor1_Name`,
        `Sponsor1_Email`,
        `Sponsor1_Phone_Number`,
        `Student_Name`,
        `Student_Email`,
        `Student_Phone_Number`,
        `Class_Id`,
        `Modified_Time`,
        `Created_Time`,
        `Batch_Name`,
        `User_Id`,
        `Student_Photo`,
        `Has_Python_Cert`,
        `classes_taken`,
        `Library_setup`,
        `School_support`,
        `Payment_method`,
        `Referral_Name`,
        `payment_status`,
        `payment_amount`,
        `notes`
        FROM `$table`
        WHERE `Reg_Id` = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('i', $reg_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
        } else {
            $errors[] = "Select failed: " . h($stmt->error);
        }
        $stmt->close();
    } else {
        $errors[] = "Prepare failed: " . h($mysqli->error);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Registration | Learn and Help</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- shared assets -->
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <style>
    :root { --accent:#99D930; }
    .accent-text { color: var(--accent); }

    /* Intro banner (from classes.php) */
    .intro-banner { 
      background:#1a1a1a; 
      color:#fff; 
      text-align:center; 
      padding:24px 20px 20px; 
    }
    .intro-banner h1 { 
      font-family:'Montserrat',sans-serif; 
      font-size:3rem; 
      font-weight:900; 
      margin:0 0 0px; 
    }
    .intro-banner h1 .accent-text { color:var(--accent); }
    .intro-banner p { 
      max-width:820px; 
      margin:0 auto; 
      font-size:1.5rem; 
      line-height:1.65; 
    }

    body {
      margin:0;
      font-family:'Montserrat',sans-serif;
      background:#f8f8f8;
      color:#252525;
    }

    /* Content styling */
    .content-wrapper {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }

    .form-section { 
      padding: 2rem; 
      background: #fff; 
      border-radius: 18px; 
      margin-bottom: 2rem; 
      box-shadow: 0 4px 24px rgba(0,0,0,.08);
    }
    
    .required::after { content: " *"; color: #b00020; }
    .monospace { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    
    /* Custom button styling to match site theme */
    .btn-primary {
      background-color: var(--accent);
      border-color: var(--accent);
      color: #252525;
      font-weight: 700;
    }
    .btn-primary:hover {
      background-color: #88c020;
      border-color: #88c020;
      color: #252525;
    }
    
    .btn-outline-secondary {
      color: #252525;
      border-color: #252525;
    }
    .btn-outline-secondary:hover {
      background-color: #252525;
      border-color: #252525;
      color: #fff;
    }

    /* Alert styling */
    .alert-danger {
      border-left: 4px solid #b00020;
    }
    .alert-success {
      border-left: 4px solid: var(--accent);
    }
    .alert-warning {
      border-left: 4px solid #ff9800;
    }
  </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<section class="intro-banner">
  <h1><span class="accent-text">Edit Registration</span></h1>
  <p>Modify registration details and information.</p>
</section>

<div class="content-wrapper">
  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <strong>Errors:</strong>
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?= $e ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($info): ?>
    <div class="alert alert-success">
      <?php foreach ($info as $i): ?>
        <div><?= $i ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!$row): ?>
    <div class="alert alert-warning">No record found for Reg_Id <?= h($reg_id) ?>.</div>
  <?php else: ?>
  <form method="post" class="form-section needs-validation" novalidate>
    <div class="row g-3">

      <div class="col-md-3">
        <label class="form-label required">Reg_Id</label>
        <input type="number" class="form-control" name="Reg_Id" value="<?= h($row['Reg_Id']) ?>" readonly>
      </div>

      <div class="col-md-3">
        <label class="form-label required">offering_id</label>
        <input type="number" class="form-control" name="offering_id" value="<?= h($row['offering_id']) ?>" required>
      </div>

      <div class="col-md-3">
        <label class="form-label">Class_Id</label>
        <input type="number" class="form-control" name="Class_Id" value="<?= h($row['Class_Id']) ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label required">Batch_Name</label>
        <input type="text" class="form-control" name="Batch_Name" value="<?= h($row['Batch_Name']) ?>" required>
      </div>

      <hr class="mt-4">

      <div class="col-md-4">
        <label class="form-label">Sponsor1_Name</label>
        <input type="text" class="form-control" name="Sponsor1_Name" value="<?= h($row['Sponsor1_Name']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Sponsor1_Email</label>
        <input type="email" class="form-control" name="Sponsor1_Email" value="<?= h($row['Sponsor1_Email']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Sponsor1_Phone_Number</label>
        <input type="text" class="form-control" name="Sponsor1_Phone_Number" value="<?= h($row['Sponsor1_Phone_Number']) ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Student_Name</label>
        <input type="text" class="form-control" name="Student_Name" value="<?= h($row['Student_Name']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Student_Email</label>
        <input type="email" class="form-control" name="Student_Email" value="<?= h($row['Student_Email']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Student_Phone_Number</label>
        <input type="text" class="form-control" name="Student_Phone_Number" value="<?= h($row['Student_Phone_Number']) ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">User_Id</label>
        <input type="number" class="form-control" name="User_Id" value="<?= h($row['User_Id']) ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Student_Photo</label>
        <input type="text" class="form-control" name="Student_Photo" value="<?= h($row['Student_Photo']) ?>">
        <div class="form-text">Path/URL. Current default: images/banner_images/Classes/avatar.jpg</div>
      </div>

      <div class="col-md-4">
        <label class="form-label">Has_Python_Cert</label>
        <select class="form-select" name="Has_Python_Cert">
          <option value="No" <?= ($row['Has_Python_Cert'] === 'No' ? 'selected' : '') ?>>No</option>
          <option value="Yes" <?= ($row['Has_Python_Cert'] === 'Yes' ? 'selected' : '') ?>>Yes</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">classes_taken</label>
        <input type="text" class="form-control" name="classes_taken" value="<?= h($row['classes_taken']) ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Library_setup</label>
        <input type="text" class="form-control" name="Library_setup" value="<?= h($row['Library_setup']) ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">School_support</label>
        <input type="text" class="form-control" name="School_support" value="<?= h($row['School_support']) ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Payment_method</label>
        <input type="text" class="form-control" name="Payment_method" value="<?= h($row['Payment_method']) ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Referral_Name</label>
        <input type="text" class="form-control" name="Referral_Name" value="<?= h($row['Referral_Name']) ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">payment_status</label>
        <select class="form-select" name="payment_status" required>
          <?php
            $opts = ['paid','pending','free','partial','void','withdrawn'];
            foreach ($opts as $opt) {
                $sel = ($row['payment_status'] === $opt) ? 'selected' : '';
                echo '<option value="' . h($opt) . '" ' . $sel . '>' . h($opt) . '</option>';
            }
          ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">payment_amount</label>
        <input type="number" class="form-control" name="payment_amount" min="0" max="9999" value="<?= h($row['payment_amount']) ?>">
      </div>

      <div class="col-md-8">
        <label class="form-label">notes</label>
        <input type="text" class="form-control" name="notes" value="<?= h($row['notes']) ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Created_Time</label>
        <input type="text" class="form-control" name="Created_Time" value="<?= h($row['Created_Time']) ?>" readonly style="background-color: #e9ecef; opacity: 1;">
        <div class="form-text">Read-only: Record creation date.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Modified_Time</label>
        <input type="text" class="form-control" name="Modified_Time" value="<?= h($row['Modified_Time']) ?>" readonly style="background-color: #e9ecef; opacity: 1;">
        <div class="form-text">Read-only: Auto-updated on save.</div>
      </div>

    </div>

    <input type="hidden" name="__save" value="1">
    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary">Save Changes</button>
      <a href="admin_registrations.php" class="btn btn-outline-secondary">Back to List</a>
    </div>

  </form>
  <?php endif; ?>

</div>

<?php $mysqli->close(); include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>