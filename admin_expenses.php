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
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp','pdf'];
    if (!in_array($ext, $allowed)) return '';
    $filename = uniqid('receipt_', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
        return 'images/receipts/' . $filename;
    }
    return '';
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

        if (empty($date) || $amount <= 0) {
            $error = 'Date and a valid amount are required.';
        } else {
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO expenses (date, amount, category, description, paid_by, receipt_image) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param('sdssss', $date, $amount, $category, $description, $paid_by, $receipt_image);
                if ($stmt->execute()) $message = 'Expense added successfully!';
                else $error = 'Error adding expense: ' . $db->error;
            } else {
                $stmt = $db->prepare("UPDATE expenses SET date=?, amount=?, category=?, description=?, paid_by=?, receipt_image=? WHERE id=?");
                $stmt->bind_param('sdssssi', $date, $amount, $category, $description, $paid_by, $receipt_image, $id);
                if ($stmt->execute()) $message = 'Expense updated successfully!';
                else $error = 'Error updating expense: ' . $db->error;
            }
        }
    }

    if ($action === 'delete') {
        $stmt = $db->prepare("DELETE FROM expenses WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) $message = 'Expense deleted.';
        else $error = 'Error deleting expense.';
    }
}

// ── Date range filter ────────────────────────────────────────────
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // first of current month
$date_to   = $_GET['date_to']   ?? date('Y-m-d');  // today

// ── Fetch expenses for table ─────────────────────────────────────
$expenses = [];
$stmt = $db->prepare("SELECT * FROM expenses WHERE date BETWEEN ? AND ? ORDER BY date DESC");
$stmt->bind_param('ss', $date_from, $date_to);
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

// ── Fetch for edit ───────────────────────────────────────────────
$edit_expense = null;
if ($action === 'edit' && $id && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT * FROM expenses WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit_expense = $stmt->get_result()->fetch_assoc();
}

// Common categories
$categories = ['Supplies', 'Technology', 'Education', 'Charity', 'Projects', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin – Expenses | Learn and Help</title>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
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
    .form-card { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.08); padding:30px; margin-bottom:30px; }
    .form-card h2 { margin:0 0 24px; font-size:1.4rem; font-weight:900; }
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

  <!-- ── Add / Edit Form ── -->
  <div class="form-card">
    <h2><?= $edit_expense ? 'Edit Expense' : 'Add New Expense' ?></h2>
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
            <div style="margin-bottom:8px;">
              <p style="margin:0 0 4px;font-size:.85rem;color:#888;">Current receipt:</p>
              <?php if (str_ends_with(strtolower($edit_expense['receipt_image']), '.pdf')): ?>
                <a href="<?= htmlspecialchars($edit_expense['receipt_image']) ?>" target="_blank" style="color:#007bff;font-size:.9rem;">View PDF Receipt</a>
              <?php else: ?>
                <img src="<?= htmlspecialchars($edit_expense['receipt_image']) ?>" class="receipt-thumb" style="width:80px;height:80px;" alt="Receipt">
              <?php endif; ?>
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
  </div>

  <!-- ── Expenses Table ── -->
  <div class="table-card">
    <h2>
      Expense Records
      <span style="font-size:.9rem;font-weight:400;color:#888;"><?= date('M d, Y', strtotime($date_from)) ?> – <?= date('M d, Y', strtotime($date_to)) ?></span>
    </h2>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Description</th>
          <th>Category</th>
          <th>Paid By</th>
          <th>Amount</th>
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
                 f.innerHTML='<input name=action value=delete><input name=id value=<?= $e['id'] ?>>';
                 document.body.appendChild(f); f.submit();
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

<?php $db->close(); include 'footer.php'; ?>
</body>
</html>