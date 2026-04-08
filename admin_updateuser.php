<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

require 'db_configuration.php'; // provides $db (mysqli)

// Block unauthorized users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Forbidden');
}

// Retrieve the User_ID from the query parameter
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$user_id) {
    echo "Invalid user ID.";
    exit;
}

// Retrieve user data using $db from db_configuration.php
$stmt = $db->prepare("SELECT * FROM users WHERE User_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row                     = $result->fetch_assoc();
    $firstname               = $row['First_Name'];
    $lastname                = $row['Last_Name'];
    $email                   = $row['Email'];
    $phone                   = $row['Phone'];
    $active                  = $row['Active'];
    $UserRole                = $row['Role'];
    $notes                   = $row['notes']                   ?? '';
    $secondary_contact_name  = $row['secondary_contact_name']  ?? '';
    $secondary_contact_email = $row['secondary_contact_email'] ?? '';
    $secondary_contact_phone = $row['secondary_contact_phone'] ?? '';
    $secondary_contact_active = $row['secondary_contact_active'] ?? 0;
    $modified_time           = $row['Modified_Time'];
} else {
    echo "User data not found.";
    exit;
}
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname               = trim($_POST['First_Name']);
    $lastname                = trim($_POST['Last_Name']);
    $email                   = trim($_POST['email']);
    $phone                   = trim($_POST['Phone']);
    $active                  = $_POST['Active'];
    $UserRole                = $_POST['Role'];
    $notes                   = trim($_POST['notes']);
    $secondary_contact_name  = trim($_POST['secondary_contact_name']);
    $secondary_contact_email = trim($_POST['secondary_contact_email']);
    $secondary_contact_phone = trim($_POST['secondary_contact_phone']);
    $secondary_contact_active = isset($_POST['secondary_contact_active']) ? 1 : 0;

    date_default_timezone_set('America/Chicago');
    $modified_time = date("Y-m-d H:i:s");

    $stmt = $db->prepare("
        UPDATE users
        SET
            First_Name               = ?,
            Last_Name                = ?,
            Email                    = ?,
            Phone                    = ?,
            Active                   = ?,
            Role                     = ?,
            notes                    = ?,
            secondary_contact_name   = ?,
            secondary_contact_email  = ?,
            secondary_contact_phone  = ?,
            secondary_contact_active = ?,
            Modified_Time            = ?
        WHERE User_ID = ?
    ");
    $stmt->bind_param(
        "ssssssssssssi",
        $firstname, $lastname, $email, $phone, $active, $UserRole, $notes,
        $secondary_contact_name, $secondary_contact_email, $secondary_contact_phone,
        $secondary_contact_active, $modified_time, $user_id
    );

    if ($stmt->execute()) {
        header("Location: admin_usersList.php?msg=User+updated+successfully&type=success");
        exit;
    } else {
        $error_message = "Error updating user: " . $db->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Update User – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background: #f8f8f8;
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
            max-width: 800px;
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

        /* ── Error flash ── */
        .flash.error {
            padding: 13px 20px;
            border-radius: 9px;
            margin-bottom: 22px;
            font-weight: 700;
            background: #fff0f0;
            color: #a00;
            border: 1.5px solid #f88;
        }

        /* ── Form card ── */
        .form-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 30px 32px;
            margin-bottom: 28px;
        }
        .form-card h2 {
            margin: 0 0 22px 0;
            font-size: 1.2em;
            color: #274606;
            font-weight: 900;
            padding-bottom: 10px;
            border-bottom: 2px solid #99d930;
        }

        /* ── Form fields ── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 16px;
        }
        .form-group label {
            font-size: .85em;
            font-weight: 700;
            color: #274606;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .form-group input[type="text"],
        .form-group input[type="tel"],
        .form-group input[type="email"],
        .form-group select,
        .form-group textarea {
            padding: 10px 13px;
            border: 1.5px solid #cde8a0;
            border-radius: 8px;
            font-size: 1em;
            font-family: 'Roboto', sans-serif;
            background: #f8fbe9;
            color: #222;
            transition: border-color .18s, box-shadow .18s;
            outline: none;
            box-sizing: border-box;
            width: 100%;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #99d930;
            box-shadow: 0 0 0 3px rgba(153,217,48,.15);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .hint {
            font-size: .78em;
            color: #888;
            font-style: italic;
            margin-top: 2px;
        }

        /* ── Radio & checkbox ── */
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 4px;
            flex-wrap: wrap;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: normal;
            text-transform: none;
            letter-spacing: 0;
            font-size: 1em;
            color: #333;
            cursor: pointer;
        }
        .radio-group input[type="radio"] {
            width: auto;
            accent-color: #99d930;
        }
        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 6px;
        }
        .checkbox-row input[type="checkbox"] {
            accent-color: #99d930;
            width: 16px;
            height: 16px;
        }
        .checkbox-row label {
            font-size: .95em;
            color: #333;
            cursor: pointer;
        }

        /* ── Family status badge ── */
        .family-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 700;
            font-size: .88em;
            margin-bottom: 16px;
        }
        .family-badge.enabled  { background: #edfae5; color: #2a6006; border: 1.5px solid #99d930; }
        .family-badge.disabled { background: #fff0f0; color: #a00;    border: 1.5px solid #f88; }

        /* ── Submit button ── */
        .btn-submit {
            width: 100%;
            padding: 13px;
            margin-top: 8px;
            font-size: 1em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            background: #99d930;
            color: #274606;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background .18s, transform .12s;
            box-shadow: 0 2px 8px rgba(153,217,48,.25);
        }
        .btn-submit:hover {
            background: #85c220;
            transform: translateY(-1px);
        }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .form-card { padding: 18px 14px; }
        }
    </style>
</head>
<body>

<?php
include 'show-navbar.php';
show_navbar();
?>

<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Admin banner">
    <h1 class="banner-title">Update User</h1>
</div>

<div class="page-wrap">

    <a href="admin_usersList.php" class="back-link">&#8592; Back to Users List</a>

    <?php if (!empty($error_message)): ?>
        <div class="flash error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="on">
        <input type="hidden" name="User_ID" value="<?= htmlspecialchars($user_id) ?>">

        <!-- ── Basic Information ── -->
        <div class="form-card">
            <h2>📋 Basic Information</h2>

            <div class="form-group">
                <label for="First_Name">First Name <span style="color:#c00">*</span></label>
                <input type="text" name="First_Name" id="First_Name" required
                       placeholder="First Name"
                       value="<?= htmlspecialchars($firstname) ?>">
            </div>

            <div class="form-group">
                <label for="Last_Name">Last Name <span style="color:#c00">*</span></label>
                <input type="text" name="Last_Name" id="Last_Name" required
                       placeholder="Last Name"
                       value="<?= htmlspecialchars($lastname) ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address <span style="color:#c00">*</span></label>
                <input type="email" name="email" id="email" required
                       placeholder="Email"
                       value="<?= htmlspecialchars($email) ?>">
            </div>

            <div class="form-group">
                <label for="Phone">Phone Number <span style="color:#c00">*</span></label>
                <input type="tel" name="Phone" id="Phone" required
                       placeholder="Phone"
                       value="<?= htmlspecialchars($phone) ?>">
            </div>
        </div>

        <!-- ── Account Settings ── -->
        <div class="form-card">
            <h2>⚙️ Account Settings</h2>

            <div class="form-group">
                <label>Account Status <span style="color:#c00">*</span></label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="Active" value="Yes"
                               <?= $active === 'Yes' ? 'checked' : '' ?>> Active
                    </label>
                    <label>
                        <input type="radio" name="Active" value="No"
                               <?= $active === 'No' ? 'checked' : '' ?>> Inactive
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="Role">User Role <span style="color:#c00">*</span></label>
                <select name="Role" id="Role" required>
                    <option value="admin"      <?= $UserRole === 'admin'      ? 'selected' : '' ?>>Admin</option>
                    <option value="student"    <?= $UserRole === 'student'    ? 'selected' : '' ?>>Student</option>
                    <option value="instructor" <?= $UserRole === 'instructor' ? 'selected' : '' ?>>Instructor</option>
                </select>
            </div>
        </div>

        <!-- ── Family Access ── -->
        <div class="form-card">
            <h2>👨‍👩‍👧‍👦 Family Access Management</h2>

            <div class="family-badge <?= $secondary_contact_active ? 'enabled' : 'disabled' ?>">
                <?= $secondary_contact_active ? '✅ Family access is currently enabled' : '❌ Family access is currently disabled' ?>
            </div>

            <div class="form-group">
                <label for="secondary_contact_name">Secondary Contact Name</label>
                <input type="text" name="secondary_contact_name" id="secondary_contact_name"
                       placeholder="e.g. Jane Smith"
                       value="<?= htmlspecialchars($secondary_contact_name) ?>">
                <span class="hint">Name of spouse or partner who should receive notifications.</span>
            </div>

            <div class="form-group">
                <label for="secondary_contact_email">Secondary Contact Email</label>
                <input type="email" name="secondary_contact_email" id="secondary_contact_email"
                       placeholder="e.g. jane@example.com"
                       value="<?= htmlspecialchars($secondary_contact_email) ?>">
                <span class="hint">Email address for registration and payment notifications.</span>
            </div>

            <div class="form-group">
                <label for="secondary_contact_phone">Secondary Contact Phone</label>
                <input type="tel" name="secondary_contact_phone" id="secondary_contact_phone"
                       placeholder="e.g. (555) 123-4567"
                       value="<?= htmlspecialchars($secondary_contact_phone) ?>">
            </div>

            <div class="checkbox-row">
                <input type="checkbox" name="secondary_contact_active" id="secondary_contact_active"
                       <?= $secondary_contact_active ? 'checked' : '' ?>>
                <label for="secondary_contact_active">Enable family access and email notifications</label>
            </div>
            <p class="hint" style="margin-top:6px;">
                When enabled, the secondary contact will receive emails about registrations, payments, and important announcements.
            </p>
        </div>

        <!-- ── Admin Notes ── -->
        <div class="form-card">
            <h2>📝 Admin Notes</h2>

            <div class="form-group">
                <label for="notes">Internal Notes</label>
                <textarea name="notes" id="notes" rows="4"
                          placeholder="Add any internal notes about this user account..."><?= htmlspecialchars($notes) ?></textarea>
                <span class="hint">These notes are only visible to administrators and are not shown to the user.</span>
            </div>
        </div>

        <button type="submit" class="btn-submit">✅ Update User Profile</button>

    </form>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>