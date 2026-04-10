<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

// Block unauthorized users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Forbidden');
}

require_once 'db_configuration.php'; // provides $db (mysqli)

// ── Helper ────────────────────────────────────────────────────
function h($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$errors  = [];
$success = '';

// ── Determine Reg_Id ──────────────────────────────────────────
$reg_id = null;
if (isset($_GET['reg_id']))   $reg_id = (int)$_GET['reg_id'];
elseif (isset($_GET['Reg_Id']))  $reg_id = (int)$_GET['Reg_Id'];
elseif (isset($_POST['Reg_Id'])) $reg_id = (int)$_POST['Reg_Id'];

if (!$reg_id) {
    header('Location: admin_registrations.php');
    exit();
}

// ── Handle POST (update) ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['__save'] ?? '') === '1') {

    $offering_id         = intval($_POST['offering_id']           ?? 0);
    $sponsor1_name       = trim($_POST['Sponsor1_Name']           ?? '');
    $sponsor1_email      = trim($_POST['Sponsor1_Email']          ?? '');
    $sponsor1_phone      = trim($_POST['Sponsor1_Phone_Number']   ?? '');
    $student_name        = trim($_POST['Student_Name']            ?? '');
    $student_email       = trim($_POST['Student_Email']           ?? '');
    $student_phone       = trim($_POST['Student_Phone_Number']    ?? '');
    $class_id            = intval($_POST['Class_Id']              ?? 0) ?: null;
    $batch_name          = trim($_POST['Batch_Name']              ?? '');
    $user_id             = intval($_POST['User_Id']               ?? 0) ?: null;
    $student_photo       = trim($_POST['Student_Photo']           ?? '');
    $has_python_cert     = in_array($_POST['Has_Python_Cert'] ?? '', ['Yes','No']) ? $_POST['Has_Python_Cert'] : 'No';
    $classes_taken       = trim($_POST['classes_taken']           ?? '');
    $library_setup       = trim($_POST['Library_setup']           ?? '');
    $school_support      = trim($_POST['School_support']          ?? '');
    $payment_method      = trim($_POST['Payment_method']          ?? '');
    $referral_name       = trim($_POST['Referral_Name']           ?? '');
    $payment_status      = in_array($_POST['payment_status'] ?? '', ['paid','pending','free','partial','void','withdrawn'])
                           ? $_POST['payment_status'] : 'pending';
    $payment_amount      = max(0, min(9999, intval($_POST['payment_amount'] ?? 0)));
    $notes               = trim($_POST['notes']                   ?? '');
    $modified_time       = date('Y');

    if (!$offering_id) $errors[] = 'Offering ID is required.';
    if (!$batch_name)  $errors[] = 'Batch Name is required.';

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE registrations SET
            offering_id = ?, Sponsor1_Name = ?, Sponsor1_Email = ?, Sponsor1_Phone_Number = ?,
            Student_Name = ?, Student_Email = ?, Student_Phone_Number = ?,
            Class_Id = ?, Modified_Time = ?, Batch_Name = ?, User_Id = ?, Student_Photo = ?,
            Has_Python_Cert = ?, classes_taken = ?, Library_setup = ?, School_support = ?,
            Payment_method = ?, Referral_Name = ?, payment_status = ?, payment_amount = ?, notes = ?
            WHERE Reg_Id = ?");

        $stmt->bind_param(
            "issssssisssissssssssii",
            $offering_id, $sponsor1_name, $sponsor1_email, $sponsor1_phone,
            $student_name, $student_email, $student_phone,
            $class_id, $modified_time, $batch_name, $user_id, $student_photo,
            $has_python_cert, $classes_taken, $library_setup, $school_support,
            $payment_method, $referral_name, $payment_status, $payment_amount, $notes,
            $reg_id
        );

        if ($stmt->execute()) {
            $success = "Registration #{$reg_id} updated successfully.";
        } else {
            $errors[] = 'Update failed. Please try again.';
        }
        $stmt->close();
    }
}

// ── Fetch row to prefill ──────────────────────────────────────
$row = null;
$stmt = $db->prepare("SELECT * FROM registrations WHERE Reg_Id = ? LIMIT 1");
$stmt->bind_param("i", $reg_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    header('Location: admin_registrations.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Edit Registration – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

    <style>
        body {
            background: #f8f8f8;
            margin: 0;
            font-family: 'Roboto', Arial, sans-serif;
            color: #252525;
        }

        /* ── Banner ── */
        .banner-wrapper {
            width: 100vw;
            left: 50%;
            margin-left: -50vw;
            height: 220px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            position: relative;
        }
        .banner-wrapper img {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;
        }
        .banner-title {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            margin: 0;
            font-family: 'Roboto', sans-serif;
            font-size: 3em;
            font-weight: 900;
            color: #99d930;
            text-shadow: 0 2px 16px rgba(0,0,0,0.44);
            letter-spacing: 1px;
            z-index: 2;
            white-space: nowrap;
        }

        /* ── Page wrapper ── */
        .page-wrap {
            max-width: 1200px;
            margin: 36px auto 60px auto;
            padding: 0 18px;
        }

        /* ── Back link ── */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 18px;
            font-weight: 700;
            color: #274606;
            text-decoration: none;
            font-size: .97em;
        }
        .back-link:hover { color: #99d930; }

        /* ── Flash messages ── */
        .flash {
            padding: 13px 20px;
            border-radius: 9px;
            margin-bottom: 22px;
            font-weight: 700;
            font-size: 1em;
        }
        .flash.success { background: #edfae5; color: #2a6006; border: 1.5px solid #99d930; }
        .flash.error   { background: #fff0f0; color: #a00;    border: 1.5px solid #f88; }

        /* ── Card ── */
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 28px 32px;
            margin-bottom: 28px;
        }
        .card h2 {
            margin: 0 0 22px 0;
            font-size: 1.2em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Form grid ── */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        .form-grid .span-2 { grid-column: span 2; }
        .form-grid .span-3 { grid-column: 1 / -1; }

        /* ── Section divider ── */
        .form-section-title {
            grid-column: 1 / -1;
            font-size: .82em;
            font-weight: 900;
            color: #99d930;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1.5px solid #e8f5c8;
            padding-bottom: 6px;
            margin-top: 10px;
        }

        /* ── Form groups ── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .form-group label {
            font-size: .8em;
            font-weight: 700;
            color: #274606;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .form-group .hint {
            font-size: .75em;
            color: #aaa;
            margin-top: 2px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 9px 12px;
            border: 1.5px solid #cde8a0;
            border-radius: 8px;
            font-size: .95em;
            font-family: 'Roboto', sans-serif;
            background: #f8fbe9;
            color: #222;
            outline: none;
            transition: border-color .18s, box-shadow .18s;
            box-sizing: border-box;
            width: 100%;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #99d930;
            box-shadow: 0 0 0 3px rgba(153,217,48,.15);
        }
        .form-group input[readonly] {
            background: #f0f0f0;
            border-color: #ddd;
            color: #888;
            cursor: not-allowed;
        }

        /* ── Buttons ── */
        .btn-row {
            display: flex;
            gap: 12px;
            margin-top: 22px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 11px 26px;
            border-radius: 8px;
            font-size: .97em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .18s, transform .12s;
        }
        .btn-green   { background: #99d930; color: #274606; box-shadow: 0 2px 8px rgba(153,217,48,.25); }
        .btn-green:hover   { background: #85c220; transform: translateY(-1px); }
        .btn-outline { background: #fff; color: #274606; border: 2px solid #99d930; }
        .btn-outline:hover { background: #f0fad8; }

        @media (max-width: 900px) {
            .form-grid { grid-template-columns: 1fr 1fr; }
            .form-grid .span-3 { grid-column: 1 / -1; }
        }
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .span-2,
            .form-grid .span-3 { grid-column: 1; }
            .banner-title { font-size: 2em; }
            .card { padding: 16px 12px; }
        }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Admin banner">
    <h1 class="banner-title">Edit Registration</h1>
</div>

<div class="page-wrap">

    <a href="admin_registrations.php" class="back-link">&#8592; Back to Registrations</a>

    <?php if (!empty($errors)): ?>
        <div class="flash error">
            <?php foreach ($errors as $e): ?>
                <div><?= h($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="flash success"><?= h($success) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>✏️ Registration #<?= intval($reg_id) ?></h2>

        <form method="POST" action="">
            <input type="hidden" name="__save"  value="1">
            <input type="hidden" name="Reg_Id"  value="<?= intval($row['Reg_Id']) ?>">

            <div class="form-grid">

                <!-- IDs & Batch -->
                <div class="form-section-title">IDs &amp; Batch</div>

                <div class="form-group">
                    <label>Reg ID</label>
                    <input type="number" value="<?= intval($row['Reg_Id']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Offering ID <span style="color:#c00">*</span></label>
                    <input type="number" name="offering_id"
                           value="<?= h($row['offering_id']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Class ID</label>
                    <input type="number" name="Class_Id"
                           value="<?= h($row['Class_Id']) ?>">
                </div>
                <div class="form-group">
                    <label>Batch Name <span style="color:#c00">*</span></label>
                    <input type="text" name="Batch_Name"
                           value="<?= h($row['Batch_Name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>User ID</label>
                    <input type="number" name="User_Id"
                           value="<?= h($row['User_Id']) ?>">
                </div>

                <!-- Sponsor -->
                <div class="form-section-title">Sponsor</div>

                <div class="form-group">
                    <label>Sponsor1 Name</label>
                    <input type="text" name="Sponsor1_Name"
                           value="<?= h($row['Sponsor1_Name']) ?>">
                </div>
                <div class="form-group">
                    <label>Sponsor1 Email</label>
                    <input type="email" name="Sponsor1_Email"
                           value="<?= h($row['Sponsor1_Email']) ?>">
                </div>
                <div class="form-group">
                    <label>Sponsor1 Phone</label>
                    <input type="text" name="Sponsor1_Phone_Number"
                           value="<?= h($row['Sponsor1_Phone_Number']) ?>">
                </div>

                <!-- Student -->
                <div class="form-section-title">Student</div>

                <div class="form-group">
                    <label>Student Name</label>
                    <input type="text" name="Student_Name"
                           value="<?= h($row['Student_Name']) ?>">
                </div>
                <div class="form-group">
                    <label>Student Email</label>
                    <input type="email" name="Student_Email"
                           value="<?= h($row['Student_Email']) ?>">
                </div>
                <div class="form-group">
                    <label>Student Phone</label>
                    <input type="text" name="Student_Phone_Number"
                           value="<?= h($row['Student_Phone_Number']) ?>">
                </div>
                <div class="form-group span-2">
                    <label>Student Photo</label>
                    <input type="text" name="Student_Photo"
                           value="<?= h($row['Student_Photo']) ?>"
                           placeholder="images/banner_images/Classes/avatar.jpg">
                    <span class="hint">Path or URL to student photo</span>
                </div>
                <div class="form-group">
                    <label>Has Python Cert</label>
                    <select name="Has_Python_Cert">
                        <option value="No"  <?= ($row['Has_Python_Cert'] === 'No'  ? 'selected' : '') ?>>No</option>
                        <option value="Yes" <?= ($row['Has_Python_Cert'] === 'Yes' ? 'selected' : '') ?>>Yes</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Classes Taken</label>
                    <input type="text" name="classes_taken"
                           value="<?= h($row['classes_taken']) ?>">
                </div>
                <div class="form-group">
                    <label>Library Setup</label>
                    <input type="text" name="Library_setup"
                           value="<?= h($row['Library_setup']) ?>">
                </div>
                <div class="form-group">
                    <label>School Support</label>
                    <input type="text" name="School_support"
                           value="<?= h($row['School_support']) ?>">
                </div>
                <div class="form-group">
                    <label>Referral Name</label>
                    <input type="text" name="Referral_Name"
                           value="<?= h($row['Referral_Name']) ?>">
                </div>

                <!-- Payment -->
                <div class="form-section-title">Payment</div>

                <div class="form-group">
                    <label>Payment Status</label>
                    <select name="payment_status" required>
                        <?php foreach (['paid','pending','free','partial','void','withdrawn'] as $opt): ?>
                            <option value="<?= $opt ?>"
                                <?= ($row['payment_status'] === $opt ? 'selected' : '') ?>>
                                <?= ucfirst($opt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Payment Amount</label>
                    <input type="number" name="payment_amount"
                           min="0" max="9999"
                           value="<?= h($row['payment_amount']) ?>">
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <input type="text" name="Payment_method"
                           value="<?= h($row['Payment_method']) ?>">
                </div>

                <!-- Notes & Timestamps -->
                <div class="form-section-title">Notes &amp; Timestamps</div>

                <div class="form-group span-3">
                    <label>Notes</label>
                    <input type="text" name="notes"
                           value="<?= h($row['notes']) ?>">
                </div>
                <div class="form-group">
                    <label>Created Time</label>
                    <input type="text" value="<?= h($row['Created_Time']) ?>" readonly>
                    <span class="hint">Read-only — record creation date</span>
                </div>
                <div class="form-group">
                    <label>Modified Time</label>
                    <input type="text" value="<?= h($row['Modified_Time']) ?>" readonly>
                    <span class="hint">Read-only — auto-updated on save</span>
                </div>

            </div><!-- /form-grid -->

            <div class="btn-row">
                <button type="submit" class="btn btn-green">💾 Save Changes</button>
                <a href="admin_registrations.php" class="btn btn-outline">✕ Cancel</a>
            </div>

        </form>
    </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>