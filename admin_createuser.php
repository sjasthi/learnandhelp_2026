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

require 'db_configuration.php'; // provides $db (mysqli)

$message     = '';
$messageType = '';

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['First_Name'] ?? '');
    $lastname  = trim($_POST['Last_Name']  ?? '');
    $email     = trim($_POST['email']      ?? '');
    $phone     = trim($_POST['Phone']      ?? '');
    $password  = $_POST['Hash']            ?? '';
    $active    = in_array($_POST['Active'] ?? '', ['Yes','No']) ? $_POST['Active'] : 'Yes';
    $role      = in_array($_POST['Role']   ?? '', ['admin','student','instructor']) ? $_POST['Role'] : '';

    if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($role)) {
        $message     = 'All required fields must be filled in.';
        $messageType = 'error';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (First_Name, Last_Name, Email, Phone, Hash, Active, Role, Created_Time, Modified_Time) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("sssssss", $firstname, $lastname, $email, $phone, $hash, $active, $role);

        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['flash_message'] = "User {$firstname} {$lastname} created successfully.";
            $_SESSION['flash_type']    = 'success';
            header('Location: admin_usersList.php');
            exit();
        } else {
            if ($db->errno === 1062) {
                $message = 'A user with this email already exists.';
            } else {
                $message = 'Error creating user. Please try again.';
            }
            $messageType = 'error';
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Create User – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">

    <style>
        body {
            background: #f8f8f8;
            margin: 0;
            font-family: 'Roboto', Arial, sans-serif;
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
            max-width: 600px;
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
            padding: 30px 32px;
            margin-bottom: 28px;
        }
        .card h2 {
            margin: 0 0 22px 0;
            font-size: 1.2em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Form groups ── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 18px;
        }
        .form-group label {
            font-size: .83em;
            font-weight: 700;
            color: #274606;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="tel"],
        .form-group select {
            padding: 10px 13px;
            border: 1.5px solid #cde8a0;
            border-radius: 8px;
            font-size: 1em;
            font-family: 'Roboto', sans-serif;
            background: #f8fbe9;
            color: #222;
            outline: none;
            transition: border-color .18s, box-shadow .18s;
            box-sizing: border-box;
            width: 100%;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #99d930;
            box-shadow: 0 0 0 3px rgba(153,217,48,.15);
        }

        /* ── Radio group ── */
        .radio-group {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-top: 4px;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: .95em;
            font-weight: 400;
            color: #333;
            text-transform: none;
            letter-spacing: 0;
            cursor: pointer;
        }
        .radio-group input[type="radio"] {
            accent-color: #99d930;
            width: auto;
        }

        /* ── Submit button ── */
        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 10px;
            padding: 11px 28px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            background: #99d930;
            color: #274606;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(153,217,48,.25);
            transition: background .18s, transform .12s;
            width: 100%;
            justify-content: center;
        }
        .btn-submit:hover { background: #85c220; transform: translateY(-1px); }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .card { padding: 18px 14px; }
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
    <h1 class="banner-title">Create User</h1>
</div>

<div class="page-wrap">

    <a href="admin_usersList.php" class="back-link">&#8592; Back to Users</a>

    <?php if ($message): ?>
        <div class="flash <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>👤 New User Details</h2>

        <form method="POST" action="" autocomplete="on">

            <div class="form-group">
                <label for="First_Name">First Name <span style="color:#c00">*</span></label>
                <input type="text" id="First_Name" name="First_Name" required
                       placeholder="First name"
                       value="<?= htmlspecialchars($_POST['First_Name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="Last_Name">Last Name <span style="color:#c00">*</span></label>
                <input type="text" id="Last_Name" name="Last_Name" required
                       placeholder="Last name"
                       value="<?= htmlspecialchars($_POST['Last_Name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Email <span style="color:#c00">*</span></label>
                <input type="email" id="email" name="email" required
                       placeholder="user@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="Phone">Phone</label>
                <input type="tel" id="Phone" name="Phone"
                       placeholder="(555) 000-0000"
                       value="<?= htmlspecialchars($_POST['Phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="Hash">Password <span style="color:#c00">*</span></label>
                <input type="password" id="Hash" name="Hash" required
                       placeholder="Create a password">
            </div>

            <div class="form-group">
                <label>Active</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="Active" value="Yes"
                               <?= ($_POST['Active'] ?? 'Yes') === 'Yes' ? 'checked' : '' ?>>
                        Yes
                    </label>
                    <label>
                        <input type="radio" name="Active" value="No"
                               <?= ($_POST['Active'] ?? '') === 'No' ? 'checked' : '' ?>>
                        No
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="Role">User Role <span style="color:#c00">*</span></label>
                <select id="Role" name="Role" required>
                    <option value="">— Select a role —</option>
                    <option value="admin"      <?= ($_POST['Role'] ?? '') === 'admin'      ? 'selected' : '' ?>>Admin</option>
                    <option value="student"    <?= ($_POST['Role'] ?? '') === 'student'    ? 'selected' : '' ?>>Student</option>
                    <option value="instructor" <?= ($_POST['Role'] ?? '') === 'instructor' ? 'selected' : '' ?>>Instructor</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">➕ Create User</button>

        </form>
    </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>