<?php
/* ───── session + DB connection ─────────────────────────────────── */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/db_configuration.php';   // provides $db
session_write_close();

/* ───── configurable tiles per row ──────────────────────────────── */
$number_of_tiles_per_row = 3;   // 👈 change to 2, 3, 4 etc.

/* ───── fetch partners ──────────────────────────────────────────── */
$sql = "SELECT partner_id, partner_name, partner_type, logo_image, website_url, impact_description
        FROM community_partners
        WHERE status = 'approved'
        ORDER BY partner_id DESC";
        
$result = $db->query($sql);
$partners = [];
if ($result) {
  while ($row = $result->fetch_assoc()) { $partners[] = $row; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Our Impact — Learn and Help</title>
  <?php
    $main_css = 'css/main.css';
    $main_css_v = (file_exists($main_css) ? filemtime($main_css) : '1');
  ?>
  <link href="css/main.css?v=<?= $main_css_v ?>" rel="stylesheet">
  <style>
    .impact-grid{
      display:grid !important;
      grid-template-columns: repeat(<?= $number_of_tiles_per_row ?>, 1fr) !important;
      gap:22px !important;
      margin:28px 0 40px;
      width:100% !important;
    }
    .impact-grid > * { width:auto !important; flex:none !important; }
    .impact-card{
      background:#fff;border-radius:18px;box-shadow:0 4px 18px rgba(0,0,0,.08);
      display:flex;flex-direction:column;overflow:hidden;min-height:380px;
      transition:transform .2s;
    }
    .impact-card:hover{ transform:translateY(-4px); }
    .logo-well{ aspect-ratio:1/1; display:flex;align-items:center;justify-content:center; background:#fff; border-bottom:1px solid #eee; }
    .logo-well img{ max-width:80%; max-height:80%; object-fit:contain; }
    .info{ padding:16px; flex-grow:1; display:flex; flex-direction:column; gap:8px; }
    .name{ font-weight:700; }
    .impact{ font-size:0.95rem; line-height:1.3; }
  </style>
</head>
<body>
<?php include 'show-navbar.php'; if (function_exists('show_navbar')) show_navbar(); ?>

<section class="intro-banner">
  <h1><span class="accent-text">Our Partners</span></h1>
  <p>Explore some causes we supported through these organizations</p>
</section>

<div class="container">
  <div class="impact-grid">
    <?php foreach ($partners as $p): ?>
      <div class="impact-card">
        <a class="logo-well" href="<?= htmlspecialchars($p['website_url']) ?>" target="_blank">
          <img src="images/community_partners/<?= htmlspecialchars($p['logo_image'] ?: 'default_logo.png') ?>"
               alt="<?= htmlspecialchars($p['partner_name']) ?>"
               onerror="this.src='images/community_partners/default_logo.png'">
        </a>
        <div class="info">
          <div class="name"><?= htmlspecialchars($p['partner_name']) ?></div>
          <div class="impact"><?= htmlspecialchars($p['impact_description']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php $db->close(); include 'footer.php'; ?>
</body>
</html>
