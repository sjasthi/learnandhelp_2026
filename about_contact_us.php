<?php
session_start();

include 'determine_paths.php';

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/db_configuration.php'; // provides $db (mysqli)

// ── Load email credentials from config (never hardcode) ───────
// Create email_config.php with:
//   define('SMTP_USER',     'your@gmail.com');
//   define('SMTP_PASSWORD', 'your_app_password');
if (file_exists(__DIR__ . '/email_config.php')) {
    require __DIR__ . '/email_config.php';
}
$smtp_user     = defined('SMTP_USER')     ? SMTP_USER     : '';
$smtp_password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';

$message_sent  = false;
$message_error = '';

if (isset($_POST['submit'])) {
    $name         = trim($_POST['user_name']  ?? '');
    $phone        = trim($_POST['user_phone'] ?? '');
    $body         = trim($_POST['message']    ?? '');
    $visitorEmail = trim($_POST['user_email'] ?? '');

    // Fetch admin emails from $db
    $adminEmails = [];
    $res = $db->query("SELECT Email FROM users WHERE Role = 'admin'");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $adminEmails[] = $row['Email'];
        }
    }

    if (empty($adminEmails)) {
        $message_error = 'No admin email addresses found. Please contact us directly.';
    } elseif (!$smtp_user || !$smtp_password) {
        $message_error = 'Email is not configured on this server. Please email us directly at <a href="mailto:Siva.Jasthi@gmail.com">Siva.Jasthi@gmail.com</a>.';
    } else {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = 465;
            $mail->Username   = $smtp_user;
            $mail->Password   = $smtp_password;
            $mail->setFrom('contact_us_message@learnandhelp.com', 'Learn and Help Contact Us');
            foreach ($adminEmails as $adminEmail) {
                $mail->addAddress($adminEmail);
            }
            $mail->addReplyTo($visitorEmail, $name);
            $mail->Subject = 'Contact Us Message';
            $mail->Body    = "Name: $name\nEmail: $visitorEmail\nPhone: $phone\n\nMessage:\n$body";
            $mail->send();
            $message_sent = true;
        } catch (Exception $e) {
            $message_error = 'Message could not be sent. Please try again or email us directly.';
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
    <title>Contact Us – Learn and Help</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f8f8f8;
            margin: 0;
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
            max-width: 760px;
            margin: 40px auto 60px auto;
            padding: 0 18px;
        }

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
            padding: 32px;
            margin-bottom: 24px;
        }
        .card h2 {
            margin: 0 0 20px 0;
            font-size: 1.2em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Form ── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 16px;
        }
        .form-group label {
            font-size: .85em;
            font-weight: 700;
            color: #274606;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .form-group input,
        .form-group textarea {
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
        .form-group textarea:focus {
            border-color: #99d930;
            box-shadow: 0 0 0 3px rgba(153,217,48,.15);
        }
        .form-group textarea { resize: vertical; min-height: 120px; }

        /* ── Buttons ── */
        .btn-row { display: flex; gap: 12px; margin-top: 8px; flex-wrap: wrap; }
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

        /* ── Contact info box ── */
        .contact-info {
            background: #f8fbe9;
            border: 1.5px solid #cde8a0;
            border-radius: 10px;
            padding: 20px 24px;
            text-align: center;
            color: #333;
            font-size: .97em;
            line-height: 1.7;
        }
        .contact-info a { color: #4a8500; font-weight: 700; text-decoration: none; }
        .contact-info a:hover { color: #99d930; }

        /* ── Notice banner ── */
        .notice-banner {
            background: #fff8e1;
            border: 1.5px solid #ffcc02;
            border-radius: 9px;
            padding: 12px 18px;
            color: #856404;
            font-weight: 700;
            font-size: .93em;
            margin-bottom: 22px;
        }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .card { padding: 18px 14px; }
        }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<!-- ── Banner ── -->
<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Contact Us banner">
    <h1 class="banner-title">Contact Us</h1>
</div>

<div class="page-wrap">

    <!-- Temporary notice -->
    <div class="notice-banner">
        ⚠️ This form is not fully functional yet. Please send your message directly to
        <a href="mailto:Siva.Jasthi@gmail.com" style="color:#856404;">Siva.Jasthi@gmail.com</a>. Thank you.
    </div>

    <?php if ($message_sent): ?>
        <div class="flash success">✅ Your message was sent successfully! We'll get back to you soon.</div>
    <?php endif; ?>

    <?php if ($message_error): ?>
        <div class="flash error">❌ <?= $message_error ?></div>
    <?php endif; ?>

    <!-- ── Contact Form ── -->
    <div class="card">
        <h2>✉️ Send Us a Message</h2>
        <form method="POST" action="">

            <div class="form-group">
                <label for="user_name">Your Name <span style="color:#c00">*</span></label>
                <input type="text" id="user_name" name="user_name" required
                       placeholder="e.g. Jane Smith"
                       value="<?= htmlspecialchars($_POST['user_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="user_email">Your Email</label>
                <input type="email" id="user_email" name="user_email"
                       placeholder="jane@example.com"
                       value="<?= htmlspecialchars($_POST['user_email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="user_phone">Your Phone <span style="color:#c00">*</span></label>
                <input type="tel" id="user_phone" name="user_phone" required
                       placeholder="(555) 000-0000"
                       value="<?= htmlspecialchars($_POST['user_phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="message">Message <span style="color:#c00">*</span></label>
                <textarea id="message" name="message" rows="5" required
                          placeholder="Write your message here…"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            </div>

            <div class="btn-row">
                <button type="submit" name="submit" class="btn btn-green">📨 Send Message</button>
                <a href="index.php" class="btn btn-outline">✕ Cancel</a>
            </div>

        </form>
    </div>

    <!-- ── Direct contact info ── -->
    <div class="contact-info">
        <p>Have a question? We'd love to hear from you.</p>
        <p>
            📧 <a href="mailto:Siva.Jasthi@gmail.com">Siva.Jasthi@gmail.com</a>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            📞 <a href="tel:+16512764671">651.276.4671</a>
        </p>
    </div>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>