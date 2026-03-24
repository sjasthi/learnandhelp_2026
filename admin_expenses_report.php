<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/db_configuration.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}

$all         = $db->query("SELECT * FROM expenses ORDER BY date DESC")->fetch_all(MYSQLI_ASSOC);
$grand_total = array_sum(array_column($all, 'amount'));
$total_count = count($all);

$cat_totals = [];
foreach ($all as $e) {
    $c = $e['category'] ?: 'Uncategorized';
    $cat_totals[$c] = ($cat_totals[$c] ?? 0) + $e['amount'];
}
arsort($cat_totals);

$person_totals  = [];
$person_counts  = [];
foreach ($all as $e) {
    $p = trim($e['paid_by']) ?: 'Unspecified';
    $person_totals[$p] = ($person_totals[$p] ?? 0) + $e['amount'];
    $person_counts[$p] = ($person_counts[$p] ?? 0) + 1;
}
arsort($person_totals);

$monthly = [];
foreach ($all as $e) {
    $key = date('Y-m', strtotime($e['date']));
    $monthly[$key] = ($monthly[$key] ?? 0) + $e['amount'];
}
krsort($monthly);
$monthly_12 = array_slice($monthly, 0, 12, true);
ksort($monthly_12);

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Expense Report | Learn and Help</title>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    :root { --accent:#99D930; }
    body { font-family:'Montserrat',sans-serif; background:#f8f8f8; margin:0; color:#252525; }
    .page-header { background:#1a1a1a; color:#fff; padding:20px 30px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
    .page-header h1 { margin:0; font-size:1.8rem; font-weight:900; }
    .page-header h1 span { color:var(--accent); }
    .header-btns { display:flex; gap:10px; flex-wrap:wrap; }
    .back-btn { background:var(--accent); color:#000; padding:8px 18px; border-radius:8px; text-decoration:none; font-weight:700; font-size:.9rem; }
    .export-btn { background:#fff; color:#1a1a1a; padding:8px 18px; border-radius:8px; text-decoration:none; font-weight:700; font-size:.9rem; border:2px solid var(--accent); }
    .container { width:95%; max-width:none; margin:30px auto; padding:0 20px; box-sizing:border-box; }
    .tiles { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:20px; margin-bottom:30px; }
    .tile { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.08); padding:24px; text-align:center; border-top:4px solid var(--accent); }
    .tile.dark { border-top-color:#1a1a1a; background:#1a1a1a; color:#fff; }
    .tile-label { font-size:.85rem; font-weight:700; color:#888; margin-bottom:8px; text-transform:uppercase; letter-spacing:.5px; }
    .tile.dark .tile-label { color:#aaa; }
    .tile-amount { font-size:1.9rem; font-weight:900; color:#252525; }
    .tile.dark .tile-amount { color:var(--accent); }
    .tile-sub { font-size:.8rem; color:#aaa; margin-top:4px; }
    .charts-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:30px; }
    .chart-card { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.08); padding:28px 30px; }
    .chart-card h3 { margin:0 0 20px; font-size:1.1rem; font-weight:900; }
    .table-card { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.08); overflow:hidden; margin-bottom:30px; }
    .table-card h2 { margin:0; padding:20px 25px; font-size:1.2rem; font-weight:900; border-bottom:1px solid #f0f0f0; }
    table { width:100%; border-collapse:collapse; }
    th { background:#f8f8f8; padding:11px 16px; text-align:left; font-size:.83rem; color:#888; font-weight:700; border-bottom:1px solid #eee; }
    td { padding:12px 16px; border-bottom:1px solid #f5f5f5; font-size:.88rem; vertical-align:middle; }
    tr:last-child td { border-bottom:none; }
    tr:hover td { background:#fafff0; }
    .amount-cell { font-weight:900; }
    .category-badge { background:#f0f8e0; color:#4a7c00; padding:3px 10px; border-radius:20px; font-size:.8rem; font-weight:700; }
    .receipt-thumb { width:36px; height:36px; object-fit:cover; border-radius:6px; border:1px solid #ddd; }
    .total-row td { background:#f8fbe9; font-weight:900; border-top:2px solid var(--accent); }
    @media(max-width:900px){ .charts-grid{grid-template-columns:1fr;} }
  </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<div class="page-header">
  <h1><span>Expense</span> Report</h1>
  <div class="header-btns">
    <a href="admin_expenses.php?export=csv" class="export-btn">⬇ Export CSV</a>
    <a href="admin_expenses.php" class="back-btn">← Back to Expenses</a>
  </div>
</div>

<div class="container">

  <div class="tiles">
    <div class="tile dark">
      <div class="tile-label">Total Spent (All Time)</div>
      <div class="tile-amount">$<?= number_format($grand_total, 2) ?></div>
      <div class="tile-sub"><?= $total_count ?> expense<?= $total_count != 1 ? 's' : '' ?></div>
    </div>
    <div class="tile dark">
      <div class="tile-label">Avg per Expense</div>
      <div class="tile-amount">$<?= $total_count ? number_format($grand_total / $total_count, 2) : '0.00' ?></div>
      <div class="tile-sub">across all records</div>
    </div>
    <?php foreach ($cat_totals as $cat => $total): ?>
    <div class="tile">
      <div class="tile-label"><?= htmlspecialchars($cat) ?></div>
      <div class="tile-amount">$<?= number_format($total, 2) ?></div>
      <div class="tile-sub"><?= $grand_total > 0 ? number_format(($total / $grand_total) * 100, 1) . '%' : '' ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="charts-grid">
    <div class="chart-card">
      <h3>Spending by Category</h3>
      <canvas id="catChart" style="max-height:280px;"></canvas>
    </div>
    <div class="chart-card">
      <h3>Monthly Spending (Last 12 Months)</h3>
      <canvas id="monthChart" style="max-height:280px;"></canvas>
    </div>
  </div>

  <?php if (!empty($person_totals)): ?>
  <div class="table-card">
    <h2>By Person</h2>
    <table>
      <thead><tr><th>Person</th><th>Total Spent</th><th>% of Total</th><th># Expenses</th></tr></thead>
      <tbody>
      <?php foreach ($person_totals as $person => $total): ?>
      <tr>
        <td><strong><?= htmlspecialchars($person) ?></strong></td>
        <td class="amount-cell">$<?= number_format($total, 2) ?></td>
        <td><?= $grand_total > 0 ? number_format(($total / $grand_total) * 100, 1) . '%' : '—' ?></td>
        <td><?= $person_counts[$person] ?? 0 ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <div class="table-card">
    <h2>All Expenses</h2>
    <table>
      <thead>
        <tr><th>Date</th><th>Description</th><th>Category</th><th>Paid By</th><th>Amount</th><th>Receipt</th></tr>
      </thead>
      <tbody>
        <?php if (empty($all)): ?>
        <tr><td colspan="6" style="text-align:center;color:#aaa;padding:40px;">No expenses recorded yet.</td></tr>
        <?php else: ?>
        <?php foreach ($all as $e): ?>
        <tr>
          <td style="white-space:nowrap;"><?= date('M d, Y', strtotime($e['date'])) ?></td>
          <td><?= htmlspecialchars($e['description'] ?? '') ?></td>
          <td><?php if ($e['category']): ?><span class="category-badge"><?= htmlspecialchars($e['category']) ?></span><?php endif; ?></td>
          <td><?= htmlspecialchars($e['paid_by'] ?? '') ?></td>
          <td class="amount-cell">$<?= number_format($e['amount'], 2) ?></td>
          <td>
            <?php if (!empty($e['receipt_image'])): ?>
              <?php if (str_ends_with(strtolower($e['receipt_image']), '.pdf')): ?>
                <a href="<?= htmlspecialchars($e['receipt_image']) ?>" target="_blank" style="color:#007bff;font-size:.85rem;">PDF</a>
              <?php else: ?>
                <a href="<?= htmlspecialchars($e['receipt_image']) ?>" target="_blank">
                  <img src="<?= htmlspecialchars($e['receipt_image']) ?>" class="receipt-thumb" alt="Receipt">
                </a>
              <?php endif; ?>
            <?php else: ?>
              <span style="color:#ccc;font-size:.85rem;">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
          <td colspan="4" style="text-align:right;">Grand Total:</td>
          <td class="amount-cell">$<?= number_format($grand_total, 2) ?></td>
          <td></td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<script>
(function() {
  const green = ['#99D930','#4a9e0f','#b8e95a','#2d6e00','#d4f094','#7bc423','#3a8000','#c6f040'];
  new Chart(document.getElementById('catChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_keys($cat_totals)) ?>,
      datasets: [{ data: <?= json_encode(array_values($cat_totals)) ?>, backgroundColor: green, borderRadius: 7, borderSkipped: false }]
    },
    options: {
      indexAxis: 'y', responsive: true,
      plugins: { legend:{display:false}, tooltip:{ callbacks:{ label: c => ' $'+c.parsed.x.toLocaleString('en-US',{minimumFractionDigits:2}) } } },
      scales: { x:{ ticks:{callback: v=>'$'+v.toLocaleString()}, grid:{color:'#f0f0f0'} }, y:{grid:{display:false}} }
    }
  });
  const months = <?= json_encode(array_keys($monthly_12)) ?>;
  const mLabels = months.map(m => { const [y,mo] = m.split('-'); return new Date(y,mo-1).toLocaleString('default',{month:'short',year:'2-digit'}); });
  new Chart(document.getElementById('monthChart'), {
    type: 'bar',
    data: {
      labels: mLabels,
      datasets: [{ data: <?= json_encode(array_values($monthly_12)) ?>, backgroundColor: '#99D930', borderRadius: 6, borderSkipped: false }]
    },
    options: {
      responsive: true,
      plugins: { legend:{display:false}, tooltip:{ callbacks:{ label: c => ' $'+c.parsed.y.toLocaleString('en-US',{minimumFractionDigits:2}) } } },
      scales: { y:{ ticks:{callback: v=>'$'+v.toLocaleString()}, grid:{color:'#f0f0f0'} }, x:{grid:{display:false}} }
    }
  });
})();
</script>

<?php include 'footer.php'; ?>
</body>
</html>
