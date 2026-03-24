<?php
/* ───── session + DB connection ─────────────────────────────────── */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/db_configuration.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$error   = '';

// ── Image upload helper ──────────────────────────────────────────
function handle_receipt_upload($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) return '';
    $upload_dir = __DIR__ . '/images/receipts/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $ext          = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg','jpeg','png','gif','webp','pdf'];
    $allowed_mime = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'];
    if (!in_array($ext, $allowed_exts)) return '';
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, $allowed_mime)) return '';
    $filename = uniqid('receipt_', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
        return 'images/receipts/' . $filename;
    }
    return '';
}

// ── Receipt file cleanup helper ───────────────────────────────────
function delete_receipt_file($path) {
    if (!empty($path) && file_exists(__DIR__ . '/' . $path)) {
        unlink(__DIR__ . '/' . $path);
    }
}

// ── Handle POST actions ──────────────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id     = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($action === 'add' || $action === 'edit') {
        $date        = $_POST['date'] ?? '';
        $amount      = floatval($_POST['amount'] ?? 0);
        $category    = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $paid_by     = trim($_POST['paid_by'] ?? '');

        $receipt_image = $_POST['existing_image'] ?? '';
        if (!empty($_FILES['receipt_image']['name'])) {
            $uploaded = handle_receipt_upload($_FILES['receipt_image']);
            if ($uploaded) $receipt_image = $uploaded;
        }
        // Handle remove receipt checkbox
        if (!empty($_POST['remove_receipt']) && empty($_FILES['receipt_image']['name'])) {
            delete_receipt_file($_POST['existing_image'] ?? '');
            $receipt_image = '';
        }

        if (empty($date) || $amount <= 0) {
            $error = 'Date and a valid amount are required.';
        } else {
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO expenses (date, amount, category, description, paid_by, receipt_image) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param('sdssss', $date, $amount, $category, $description, $paid_by, $receipt_image);
                if ($stmt->execute()) $message = 'Expense added successfully!';
                else $error = 'Error adding expense: ' . $db->error;
            } else {
                // Delete old receipt file if a new one was uploaded
                $old_receipt = $_POST['existing_image'] ?? '';
                if (!empty($_FILES['receipt_image']['name']) && $receipt_image !== $old_receipt) {
                    delete_receipt_file($old_receipt);
                }
                $stmt = $db->prepare("UPDATE expenses SET date=?, amount=?, category=?, description=?, paid_by=?, receipt_image=? WHERE id=?");
                $stmt->bind_param('sdssssi', $date, $amount, $category, $description, $paid_by, $receipt_image, $id);
                if ($stmt->execute()) $message = 'Expense updated successfully!';
                else $error = 'Error updating expense: ' . $db->error;
            }
        }
    }

    if ($action === 'delete') {
        // Fetch receipt path before deleting so we can clean up the file
        $stmt = $db->prepare("SELECT receipt_image FROM expenses WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $del_row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $stmt = $db->prepare("DELETE FROM expenses WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            delete_receipt_file($del_row['receipt_image'] ?? '');
            $message = 'Expense deleted.';
        } else {
            $error = 'Error deleting expense.';
        }
    }
}

// ── Date range filter ────────────────────────────────────────────
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // first of current month
$date_to   = $_GET['date_to']   ?? date('Y-m-d');  // today
$filter_cat = isset($_GET['filter_cat']) ? trim($_GET['filter_cat']) : '';
$sort         = $_GET['sort'] ?? 'date';
$dir          = strtoupper($_GET['dir']  ?? 'DESC');
$allowed_sort = ['date','amount','category','paid_by','description'];
$allowed_dir  = ['ASC','DESC'];
if (!in_array($sort, $allowed_sort)) $sort = 'date';
if (!in_array($dir,  $allowed_dir))  $dir  = 'DESC';

// ── Fetch expenses for table ─────────────────────────────────────
$expenses = [];
$where  = "date BETWEEN ? AND ?";
$params = [$date_from, $date_to];
$types  = 'ss';
if ($filter_cat !== '') {
    $where  .= " AND category = ?";
    $params[] = $filter_cat;
    $types  .= 's';
}
$stmt = $db->prepare("SELECT * FROM expenses WHERE $where ORDER BY $sort $dir");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $expenses[] = $row;

// ── Totals ───────────────────────────────────────────────────────
$grand_total = 0;
$category_totals = [];
foreach ($expenses as $e) {
    $grand_total += $e['amount'];
    $cat = $e['category'] ?: 'Uncategorized';
    $category_totals[$cat] = ($category_totals[$cat] ?? 0) + $e['amount'];
}
arsort($category_totals);

$paidby_totals = [];
foreach ($expenses as $e) {
    $person = trim($e['paid_by']) ?: 'Unspecified';
    $paidby_totals[$person] = ($paidby_totals[$person] ?? 0) + $e['amount'];
}
arsort($paidby_totals);

// ── Fetch for edit ───────────────────────────────────────────────
$edit_expense = null;
if ($action === 'edit' && $id && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT * FROM expenses WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit_expense = $stmt->get_result()->fetch_assoc();
}

// ── CSV export ───────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $res = $db->query("SELECT date, amount, category, description, paid_by, receipt_image FROM expenses ORDER BY date DESC");
    $db->close();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="expenses_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Amount', 'Category', 'Description', 'Paid By', 'Receipt']);
    while ($row = $res->fetch_assoc()) {
        fputcsv($out, [$row['date'], number_format($row['amount'], 2), $row['category'], $row['description'], $row['paid_by'], $row['receipt_image']]);
    }
    fclose($out);
    exit;
}

// Common categories
$categories = ['Supplies', 'Technology', 'Education', 'Charity', 'Projects', 'Other'];

function expense_sort_link($col, $label, $sort, $dir, $date_from, $date_to, $filter_cat) {
    $new_dir = ($sort === $col && $dir === 'ASC') ? 'DESC' : 'ASC';
    $arrow   = ($sort === $col) ? ($dir === 'ASC' ? ' ▲' : ' ▼') : ' ↕';
    $url = 'admin_expenses.php?sort=' . $col . '&dir=' . $new_dir
         . '&date_from=' . urlencode($date_from) . '&date_to=' . urlencode($date_to)
         . ($filter_cat ? '&filter_cat=' . urlencode($filter_cat) : '');
    return '<a href="' . $url . '" style="color:inherit;text-decoration:none;white-space:nowrap;">' . htmlspecialchars($label) . $arrow . '</a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin – Expenses | Learn and Help</title>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    :root { --accent:#99D930; }
    body { font-family:'Montserrat',sans-serif; background:#f8f8f8; margin:0; color:#252525; }
    .page-header { background:#1a1a1a; color:#fff; padding:20px 30px; display:flex; align-items:center; justify-content:space-between; }
    .page-header h1 { margin:0; font-size:1.8rem; font-weight:900; }
    .page-header h1 span { color:var(--accent); }
    .back-btn { background:var(--accent); color:#000; padding:8px 18px; border-radius:8px; text-decoration:none; font-weight:700; font-size:.9rem; }
    .container { width:95%; max-width:none; margin:30px auto; padding:0 20px; box-sizing:border-box; }
    .msg-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; padding:12px 18px; border-radius:8px; margin-bottom:20px; }
    .msg-error   { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; padding:12px 18px; border-radius:8px; margin-bottom:20px; }

    /* ── Date Filter ── */
    .filter-card { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.08); padding:20px 30px; margin-bottom:30px; display:flex; align-items:center; gap:20px; flex-wrap:wrap; }
    .filter-card label { font-weight:700; font-size:.9rem; color:#555; }
    .filter-card input[type=date] { border:1px solid #ddd; border-radius:8px; padding:8px 14px; font-family:'Montserrat',sans-serif; font-size:.95rem; }
    .filter-btn { background:var(--accent); color:#000; border:none; padding:10px 24px; border-radius:8px; font-weight:900; font-size:.95rem; cursor:pointer; }
    .filter-btn:hover { background:#80b520; }

    /* ── Summary Tiles ── */
    .tiles { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:30px; }
    .tile { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.08); padding:24px; text-align:center; border-top:4px solid var(--accent); }
    .tile.grand { border-top-color:#1a1a1a; background:#1a1a1a; color:#fff; }
    .tile-label { font-size:.85rem; font-weight:700; color:#888; margin-bottom:8px; text-transform:uppercase; letter-spacing:.5px; }
    .tile.grand .tile-label { color:#aaa; }
    .tile-amount { font-size:1.9rem; font-weight:900; color:#252525; }
    .tile.grand .tile-amount { color:var(--accent); }
    .tile-count { font-size:.8rem; color:#aaa; margin-top:4px; }

    /* ── Form ── */
    .form-card { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.08); padding:0; margin-bottom:30px; overflow:hidden; }
    .form-card-header { display:flex; align-items:center; justify-content:space-between; padding:20px 30px; cursor:pointer; user-select:none; }
    .form-card-header h2 { margin:0; font-size:1.4rem; font-weight:900; }
    .form-card-header:hover { background:#fafff0; }
    .form-chevron { font-size:1.1rem; transition:transform .25s; color:#888; }
    .form-chevron.open { transform:rotate(180deg); }
    .form-body { max-height:0; overflow:hidden; transition:max-height .35s ease; padding:0 30px; }
    .form-body.open { max-height:1200px; padding:0 30px 30px; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:18px; }
    .form-group { display:flex; flex-direction:column; gap:6px; }
    .form-group.full { grid-column:1/-1; }
    .form-group label { font-weight:700; font-size:.9rem; color:#555; }
    .form-group input, .form-group select, .form-group textarea {
      border:1px solid #ddd; border-radius:8px; padding:10px 14px;
      font-family:'Montserrat',sans-serif; font-size:.95rem; color:#252525;
      transition:border-color .2s;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline:none; border-color:var(--accent); }
    .form-group textarea { resize:vertical; min-height:80px; }
    .btn-row { display:flex; gap:12px; margin-top:24px; }
    .btn-submit { background:var(--accent); color:#000; border:none; padding:12px 28px; border-radius:8px; font-weight:900; font-size:1rem; cursor:pointer; }
    .btn-cancel { background:#eee; color:#333; border:none; padding:12px 28px; border-radius:8px; font-weight:700; font-size:1rem; cursor:pointer; text-decoration:none; display:inline-block; }
    .btn-submit:hover { background:#80b520; }

    /* ── Table ── */
    .table-card { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.08); overflow:hidden; }
    .table-card h2 { margin:0; padding:20px 25px; font-size:1.3rem; font-weight:900; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center; }
    table { width:100%; border-collapse:collapse; }
    th { background:#f8f8f8; padding:12px 16px; text-align:left; font-size:.85rem; color:#888; font-weight:700; border-bottom:1px solid #eee; }
    td { padding:14px 16px; border-bottom:1px solid #f5f5f5; font-size:.9rem; vertical-align:middle; }
    tr:last-child td { border-bottom:none; }
    tr:hover td { background:#fafff0; }
    .amount-cell { font-weight:900; color:#252525; font-size:1.05rem; }
    .category-badge { background:#f0f8e0; color:#4a7c00; padding:3px 10px; border-radius:20px; font-size:.8rem; font-weight:700; }
    .receipt-thumb { width:40px; height:40px; object-fit:cover; border-radius:6px; border:1px solid #ddd; }
    .actions a { margin-right:10px; font-weight:700; font-size:.85rem; text-decoration:none; }
    .edit-link   { color:#007bff; }
    .delete-link { color:#dc3545; }
    .edit-link:hover   { text-decoration:underline; }
    .delete-link:hover { text-decoration:underline; }
    .total-row td { background:#f8fbe9; font-weight:900; border-top:2px solid var(--accent); }
    @media(max-width:700px){ .form-grid{grid-template-columns:1fr;} .tiles{grid-template-columns:1fr 1fr;} }
    .shortcut-btns { display:flex; gap:8px; flex-wrap:wrap; align-self:flex-end; }
    .shortcut-btn { background:#f0f0f0; color:#444; border:1.5px solid #ddd; padding:8px 14px; border-radius:8px; font-weight:700; font-size:.82rem; cursor:pointer; font-family:'Montserrat',sans-serif; transition:background .15s,border-color .15s; }
    .shortcut-btn:hover { background:#e8f5d0; border-color:var(--accent); color:#274606; }
    .chart-card { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.08); padding:28px 30px; margin-bottom:30px; }
    .chart-card h3 { margin:0 0 20px; font-size:1.1rem; font-weight:900; color:#252525; }
  </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<div class="page-header">
  <h1><span>Expenses</span> & Receipts</h1>
  <a href="administration.php" class="back-btn">← Back to Admin</a>
</div>

<div class="container">

  <?php if ($message): ?><div class="msg-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="msg-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <!-- ── Date Range Filter ── -->
  <form method="GET" class="filter-card">
    <div>
      <label>From</label><br>
      <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
    </div>
    <div>
      <label>To</label><br>
      <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
    </div>
    <div class="shortcut-btns">
      <button type="button" class="shortcut-btn" onclick="setDateRange('this_month')">This Month</button>
      <button type="button" class="shortcut-btn" onclick="setDateRange('last_month')">Last Month</button>
      <button type="button" class="shortcut-btn" onclick="setDateRange('this_year')">This Year</button>
      <button type="button" class="shortcut-btn" onclick="setDateRange('all_time')">All Time</button>
    </div>
    <div>
      <label>Category</label><br>
      <select name="filter_cat" style="border:1px solid #ddd;border-radius:8px;padding:8px 14px;font-family:'Montserrat',sans-serif;font-size:.95rem;">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?= htmlspecialchars($cat) ?>" <?= $filter_cat === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="align-self:flex-end;">
      <button type="submit" class="filter-btn">Filter</button>
    </div>
    <div style="align-self:flex-end;margin-left:auto;color:#888;font-size:.9rem;">
      Showing: <strong><?= date('M d, Y', strtotime($date_from)) ?></strong> → <strong><?= date('M d, Y', strtotime($date_to)) ?></strong>
    </div>
  </form>

  <!-- ── Summary Tiles ── -->
  <div class="tiles">
    <div class="tile grand">
      <div class="tile-label">Grand Total</div>
      <div class="tile-amount">$<?= number_format($grand_total, 2) ?></div>
      <div class="tile-count"><?= count($expenses) ?> expense<?= count($expenses) != 1 ? 's' : '' ?></div>
    </div>
    <?php foreach ($category_totals as $cat => $total): ?>
    <div class="tile">
      <div class="tile-label"><?= htmlspecialchars($cat) ?></div>
      <div class="tile-amount">$<?= number_format($total, 2) ?></div>
      <div class="tile-count"><?= number_format(($total / $grand_total) * 100, 1) ?>% of total</div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($expenses)): ?>
    <div class="tile">
      <div class="tile-label">No Data</div>
      <div class="tile-amount">$0.00</div>
      <div class="tile-count">No expenses in range</div>
    </div>
    <?php endif; ?>
  </div>

  <?php if (!empty($paidby_totals) && count($paidby_totals) > 1): ?>
  <div style="margin-bottom:6px;font-weight:700;font-size:.85rem;color:#888;text-transform:uppercase;letter-spacing:.5px;">By Person</div>
  <div class="tiles" style="margin-bottom:30px;">
    <?php foreach ($paidby_totals as $person => $total): ?>
    <div class="tile">
      <div class="tile-label"><?= htmlspecialchars($person) ?></div>
      <div class="tile-amount">$<?= number_format($total, 2) ?></div>
      <div class="tile-count"><?= $grand_total > 0 ? number_format(($total / $grand_total) * 100, 1) . '% of total' : '' ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($category_totals)): ?>
  <div class="chart-card">
    <h3>Spending by Category</h3>
    <canvas id="categoryChart" style="max-height:260px;"></canvas>
  </div>
  <script>
  (function() {
    const labels = <?= json_encode(array_keys($category_totals)) ?>;
    const data   = <?= json_encode(array_values($category_totals)) ?>;
    const colors = ['#99D930','#4a9e0f','#b8e95a','#2d6e00','#d4f094','#7bc423','#3a8000','#c6f040'];
    new Chart(document.getElementById('categoryChart'), {
      type: 'bar',
      data: { labels, datasets: [{ label: 'Amount ($)', data, backgroundColor: colors.slice(0, data.length), borderRadius: 7, borderSkipped: false }] },
      options: {
        indexAxis: 'y', responsive: true,
        plugins: { legend:{display:false}, tooltip:{ callbacks:{ label: c => ' $'+c.parsed.x.toLocaleString('en-US',{minimumFractionDigits:2}) } } },
        scales: { x:{ ticks:{callback: v=>'$'+v.toLocaleString()}, grid:{color:'#f0f0f0'} }, y:{grid:{display:false}} }
      }
    });
  })();
  </script>
  <?php endif; ?>

  <!-- ── Add / Edit Form ── -->
  <div class="form-card">
    <div class="form-card-header" onclick="toggleExpenseForm()">
      <h2><?= $edit_expense ? '✏️ Edit Expense' : '➕ Add New Expense' ?></h2>
      <span class="form-chevron<?= $edit_expense ? ' open' : '' ?>" id="expense-chevron">▼</span>
    </div>
    <div class="form-body<?= $edit_expense ? ' open' : '' ?>" id="expense-form-body">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="<?= $edit_expense ? 'edit' : 'add' ?>">
      <?php if ($edit_expense): ?>
        <input type="hidden" name="id" value="<?= $edit_expense['id'] ?>">
        <input type="hidden" name="existing_image" value="<?= htmlspecialchars($edit_expense['receipt_image'] ?? '') ?>">
      <?php endif; ?>

      <div class="form-grid">
        <div class="form-group">
          <label>Date *</label>
          <input type="date" name="date" required value="<?= htmlspecialchars($edit_expense['date'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="form-group">
          <label>Amount ($) *</label>
          <input type="number" name="amount" step="0.01" min="0.01" required value="<?= htmlspecialchars($edit_expense['amount'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Category</label>
          <select name="category">
            <option value="">-- Select Category --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>" <?= (($edit_expense['category'] ?? '') === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Paid By</label>
          <input type="text" name="paid_by" placeholder="Who paid?" value="<?= htmlspecialchars($edit_expense['paid_by'] ?? '') ?>">
        </div>
        <div class="form-group full">
          <label>Description</label>
          <textarea name="description" placeholder="What was this expense for?"><?= htmlspecialchars($edit_expense['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group full">
          <label>Receipt (JPG, PNG, PDF)</label>
          <?php if (!empty($edit_expense['receipt_image'])): ?>
            <div style="margin-bottom:10px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
              <?php if (str_ends_with(strtolower($edit_expense['receipt_image']), '.pdf')): ?>
                <a href="<?= htmlspecialchars($edit_expense['receipt_image']) ?>" target="_blank" style="color:#007bff;font-size:.9rem;">📄 View PDF Receipt</a>
              <?php else: ?>
                <a href="<?= htmlspecialchars($edit_expense['receipt_image']) ?>" target="_blank">
                  <img src="<?= htmlspecialchars($edit_expense['receipt_image']) ?>" class="receipt-thumb" style="width:80px;height:80px;" alt="Receipt">
                </a>
              <?php endif; ?>
              <label style="display:inline-flex;align-items:center;gap:6px;font-size:.88rem;color:#dc3545;cursor:pointer;font-weight:700;">
                <input type="checkbox" name="remove_receipt" value="1"> Remove current receipt
              </label>
            </div>
          <?php endif; ?>
          <input type="file" name="receipt_image" accept="image/*,.pdf">
          <small style="color:#888;">Images and PDFs stored in <code>images/receipts/</code> on the server.</small>
        </div>
      </div>

      <div class="btn-row">
        <button type="submit" class="btn-submit"><?= $edit_expense ? 'Update Expense' : 'Add Expense' ?></button>
        <?php if ($edit_expense): ?>
          <a href="admin_expenses.php?date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" class="btn-cancel">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
    </div><!-- /form-body -->
  </div>

  <!-- ── Expenses Table ── -->
  <div class="table-card">
    <h2>
      Expense Records
      <span style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <span style="font-size:.9rem;font-weight:400;color:#888;"><?= date('M d, Y', strtotime($date_from)) ?> – <?= date('M d, Y', strtotime($date_to)) ?></span>
        <a href="admin_expenses.php?export=csv" class="filter-btn" style="font-size:.82rem;padding:7px 16px;text-decoration:none;">⬇ Export CSV</a>
        <a href="admin_expenses_report.php" style="background:#fff;color:#1a1a1a;padding:7px 16px;border-radius:8px;text-decoration:none;font-weight:700;font-size:.82rem;border:2px solid var(--accent);">📊 View Report</a>
      </span>
    </h2>
    <table>
      <thead>
        <tr>
          <th><?= expense_sort_link('date','Date',$sort,$dir,$date_from,$date_to,$filter_cat) ?></th>
          <th><?= expense_sort_link('description','Description',$sort,$dir,$date_from,$date_to,$filter_cat) ?></th>
          <th><?= expense_sort_link('category','Category',$sort,$dir,$date_from,$date_to,$filter_cat) ?></th>
          <th><?= expense_sort_link('paid_by','Paid By',$sort,$dir,$date_from,$date_to,$filter_cat) ?></th>
          <th><?= expense_sort_link('amount','Amount',$sort,$dir,$date_from,$date_to,$filter_cat) ?></th>
          <th>Receipt</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($expenses)): ?>
        <tr><td colspan="7" style="text-align:center;color:#aaa;padding:40px;">No expenses found for this date range.</td></tr>
        <?php else: ?>
        <?php foreach ($expenses as $e): ?>
        <tr>
          <td><?= date('M d, Y', strtotime($e['date'])) ?></td>
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
              <span style="color:#ccc;font-size:.85rem;">None</span>
            <?php endif; ?>
          </td>
          <td class="actions">
            <a href="admin_expenses.php?action=edit&id=<?= $e['id'] ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" class="edit-link">Edit</a>
            <a href="#" class="delete-link"
               onclick="if(confirm('Delete this expense? This cannot be undone.')) {
                 var f=document.createElement('form'); f.method='POST'; f.action='admin_expenses.php';
                 var a=document.createElement('input'); a.name='action'; a.value='delete';
                 var i=document.createElement('input'); i.name='id'; i.value='<?= intval($e['id']) ?>';
                 f.appendChild(a); f.appendChild(i); document.body.appendChild(f); f.submit();
               } return false;">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
          <td colspan="4" style="text-align:right;">Total:</td>
          <td class="amount-cell">$<?= number_format($grand_total, 2) ?></td>
          <td colspan="2"></td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<?php $db->close(); ?>
<?php include 'footer.php'; ?>

<script>
function toggleExpenseForm() {
    const body    = document.getElementById('expense-form-body');
    const chevron = document.getElementById('expense-chevron');
    body.classList.toggle('open');
    chevron.classList.toggle('open');
}
function setDateRange(range) {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const lastDay = new Date(y, now.getMonth() + 1, 0).getDate();
    let from, to;
    if (range === 'this_month') {
        from = y + '-' + m + '-01';
        to   = y + '-' + m + '-' + String(lastDay).padStart(2, '0');
    } else if (range === 'last_month') {
        const d = new Date(y, now.getMonth() - 1, 1);
        const lm = String(d.getMonth() + 1).padStart(2, '0');
        const ly = d.getFullYear();
        const ld = new Date(ly, d.getMonth() + 1, 0).getDate();
        from = ly + '-' + lm + '-01';
        to   = ly + '-' + lm + '-' + String(ld).padStart(2, '0');
    } else if (range === 'this_year') {
        from = y + '-01-01';
        to   = y + '-12-31';
    } else {
        from = '2000-01-01';
        to   = y + '-12-31';
    }
    document.querySelector('[name=date_from]').value = from;
    document.querySelector('[name=date_to]').value   = to;
    document.querySelector('[name=date_from]').closest('form').submit();
}
</script>
</body>
</html>