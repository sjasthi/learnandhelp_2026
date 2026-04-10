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

// Sanitize Class_Id — accept from POST or GET, must be positive integer
$Class_Id = intval($_POST['Class_Id'] ?? $_GET['Class_Id'] ?? 0);
if ($Class_Id <= 0) {
    header('Location: admin_classes.php');
    exit();
}

include 'admin_fill.php';
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Edit Class – Administration</title>
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
            max-width: 900px;
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
            margin: 0 0 20px 0;
            font-size: 1.2em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Submit button ── */
        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 16px;
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
    <h1 class="banner-title">Edit Class</h1>
</div>

<div class="page-wrap">

    <a href="admin_classes.php" class="back-link">&#8592; Back to Classes</a>

    <?php
    // Show and clear session message if set
    if (!empty($_SESSION['message'])) {
        $msgText = $_SESSION['message'];
        unset($_SESSION['message']);
        echo '<div class="flash success">' . htmlspecialchars($msgText) . '</div>';
    }
    ?>

    <div class="card">
        <h2>✏️ Edit Class Details</h2>

        <?php
        // admin_class_form($Class_Id) opens a <form> tag internally.
        // The hidden action + submit button must be inside that same form.
        admin_class_form($Class_Id);
        ?>

        <input type="hidden" name="action" value="admin_edit_class">
        <br>
        <button type="submit" id="submit-class" name="submit" class="btn-submit">
            💾 Save Changes
        </button>

        </form><!-- closes form opened by admin_class_form() -->
    </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>