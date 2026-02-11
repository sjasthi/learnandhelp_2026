<?php
/* ───── session + DB connection ─────────────────────────────────── */
if (session_status() === PHP_SESSION_NONE) { session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
}
require_once __DIR__ . '/db_configuration.php';   // provides $db
session_write_close();


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Password Change | Learn and Help</title>

  <!-- shared assets -->
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

  <style>
.flash{margin:16px auto;max-width:860px;padding:12px 14px;border-radius:10px;border:1px solid #ddd;font-weight:600}
.flash-success{background:#f0fff0;border-color:#b2e2b2}
.flash-error{background:#fff5f5;border-color:#f5b5b5}
:root { --accent:#99D930; }
.accent-text { color: var(--accent); }
/* Intro banner (from instructors.php) */
.intro-banner { background:#1a1a1a; color:#fff; text-align:center; padding:24px 20px 20px; }
.intro-banner h1 { font-family:'Montserrat',sans-serif; font-size:3rem; font-weight:900; margin:0 0 0px; }
.intro-banner h1 .accent-text { color:var(--accent); }
.intro-banner p { max-width:820px; margin:0 auto; font-size:1.5rem; line-height:1.65; }

    body{margin:0;font-family:'Montserrat',sans-serif;background:#f8f8f8;color:#252525;}

    /* static banner (same height as blog banner) */
    .banner-wrapper{position:relative;width:100vw;left:50%;margin-left:-50vw;height:200px;background:#fff;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);}
    .banner-wrapper img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;}
    @media(max-width:700px){.banner-wrapper{height:207px;}}

    /* blog-style title, but lighter (700) */
    .page-title{
      font-family:'Montserrat',sans-serif;
      font-size:3em;          /* same size Blog uses */
      font-weight:700;        /* lighter than 900 */
      text-align:center;
      margin:60px 0 30px;
      color:#252525;
    }

    /* two-column grid */
    .classes-grid{
      display:grid;
      grid-template-columns:repeat(2,1fr);
      gap:30px;
      max-width:1100px;
      margin:60px auto;
      padding:0 20px;
    }
    @media(max-width:700px){.classes-grid{grid-template-columns:1fr;}}

    .class-card{
      background:#fff;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,.08);
      overflow:hidden;transition:transform .2s;display:flex;flex-direction:column;
    }
    .class-card:hover{transform:translateY(-6px);}
    .class-image{
  display:block;
  width:100%;
  max-height:256px;      /* optional cap; remove if you want no limit */
  height:auto;           /* let the image define its height */
  object-fit:contain;    /* show the whole image */
  background:#fff5e6;    /* subtle backdrop behind transparent/letterboxed areas */
  padding:8px;           /* small breathing room inside the card */
  border-bottom:1px solid #f0f0f0;
}
    .class-info{padding:22px;text-align:center;flex-grow:1;display:flex;flex-direction:column;}
    .class-info h3{margin:0 0 12px;font-size:1.35em;font-weight:900;color:#252525;}
    .class-desc{font-size:0.95em;color:#444;flex-grow:1;}

    /* pager links */
    .pager{text-align:center;font-weight:bold;margin:40px 0;}
    .pager a{margin:0 18px;color:#252525;text-decoration:none;}
    .pager a:hover{color:#99d930;}
  </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>


<section class="intro-banner">
  <h1><span class="accent-text">Change Password</span></h1>
  <p>Change the current password.</p>
</section>

<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<?php if (!empty($_SESSION['flash'])): ?>
  <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="flash <?= $flash['type'] === 'success' ? 'flash-success' : 'flash-error' ?>">
    <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
  </div>
<?php endif; ?>

<section class="container" style="max-width:720px;margin:30px auto;">
  <div class="card" style="background:#fff;border-radius:16px;box-shadow:0 10px 25px rgba(0,0,0,0.08);padding:24px;">
    <h3 style="margin-top:0;margin-bottom:12px;">Update Your Password</h3>
    <p style="color:#666;margin-top:0;">For your security, please confirm your current password first.</p>

    <form method="post" action="my_account_password_change_process.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo isset($csrf_token) ? htmlspecialchars($csrf_token) : ''; ?>">

      <div class="mb-3" style="margin-bottom:14px;">
        <label for="current_password" class="form-label" style="display:block;margin-bottom:6px;">Current Password</label>
        <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
               style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;">
      </div>

      <div class="mb-3" style="margin-bottom:14px;">
        <label for="new_password" class="form-label" style="display:block;margin-bottom:6px;">New Password</label>
        <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password"
               style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;">
        <small style="display:block;color:#777;margin-top:4px;">Use at least 8 characters. Mix letters, numbers, and symbols for a stronger password.</small>
      </div>

      <div class="mb-4" style="margin-bottom:18px;">
        <label for="confirm_password" class="form-label" style="display:block;margin-bottom:6px;">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password"
               style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;">
      </div>

      <div class="actions" style="display:flex;gap:10px;align-items:center;">
        <button type="submit" class="btn btn-primary" style="background:#2b66f6;color:#fff;border:none;padding:10px 16px;border-radius:10px;cursor:pointer;">Update Password</button>
        <a href="my_account.php" class="btn btn-secondary" style="text-decoration:none;border:1px solid #ccc;padding:10px 16px;border-radius:10px;color:#333;background:#fafafa;">Cancel</a>
      </div>
    </form>
  </div>
</section>





<?php $db->close(); include 'footer.php'; ?>
</body>
</html>
