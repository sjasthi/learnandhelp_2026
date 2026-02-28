<?php
/* ───── session + DB connection ─────────────────────────────────── */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/db_configuration.php';

// Admin check - adjust this to match your existing admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$error   = '';

// ── Image upload helper ──────────────────────────────────────────
function handle_image_upload($file, $db) {
    if ($file['error'] !== UPLOAD_ERR_OK) return '';
    $upload_dir = __DIR__ . '/images/board/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed  = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) return '';
    $filename = uniqid('board_', true) . '.' . $ext;
    $dest     = $upload_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'images/board/' . $filename;
    }
    return '';
}

// ── Handle POST actions ──────────────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id     = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($action === 'add' || $action === 'edit') {
        $name       = trim($_POST['name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $mobile     = trim($_POST['mobile'] ?? '');
        $role       = trim($_POST['role'] ?? '');
        $bio        = trim($_POST['bio'] ?? '');
        $notes      = trim($_POST['notes'] ?? '');
        $date_from  = $_POST['date_from'] ?? null;
        $date_to    = $_POST['date_to'] ?? null;
        $status     = $_POST['status'] ?? 'active';
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        // Handle image upload
        $image_path = $_POST['existing_image'] ?? '';
        if (!empty($_FILES['image']['name'])) {
            $uploaded = handle_image_upload($_FILES['image'], $db);
            if ($uploaded) $image_path = $uploaded;
        }

        if (empty($name)) {
            $error = 'Name is required.';
        } else {
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO board_members (name, email, mobile, role, bio, image, notes, date_from, date_to, status, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param('ssssssssssi', $name, $email, $mobile, $role, $bio, $image_path, $notes, $date_from, $date_to, $status, $sort_order);
                if ($stmt->execute()) $message = 'Board member added successfully!';
                else $error = 'Error adding member: ' . $db->error;
            } else {
                $stmt = $db->prepare("UPDATE board_members SET name=?, email=?, mobile=?, role=?, bio=?, image=?, notes=?, date_from=?, date_to=?, status=?, sort_order=? WHERE id=?");
                $stmt->bind_param('ssssssssssii', $name, $email, $mobile, $role, $bio, $image_path, $notes, $date_from, $date_to, $status, $sort_order, $id);
                if ($stmt->execute()) $message = 'Board member updated successfully!';
                else $error = 'Error updating member: ' . $db->error;
            }
        }
    }

    if ($action === 'delete') {
        $stmt = $db->prepare("DELETE FROM board_members WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) $message = 'Board member deleted.';
        else $error = 'Error deleting member.';
    }
}

// ── Fetch all board members ──────────────────────────────────────
$members = [];
$res = $db->query("SELECT * FROM board_members ORDER BY sort_order ASC, id ASC");
if ($res) while ($row = $res->fetch_assoc()) $members[] = $row;

// ── Fetch single member for edit ─────────────────────────────────
$edit_member = null;
if ($action === 'edit' && $id && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT * FROM board_members WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit_member = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin – Board Members | Learn and Help</title>
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

    /* ── Form ── */
    .form-card { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.08); padding:30px; margin-bottom:40px; width:100%; box-sizing:border-box; }
    .form-card h2 { margin:0 0 24px; font-size:1.4rem; font-weight:900; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
    .form-group { display:flex; flex-direction:column; gap:6px; }
    .form-group.full { grid-column:1/-1; }
    .form-group label { font-weight:700; font-size:.9rem; color:#555; }
    .form-group input, .form-group select, .form-group textarea {
      border:1px solid #ddd; border-radius:8px; padding:10px 14px;
      font-family:'Montserrat',sans-serif; font-size:.95rem; color:#252525;
      transition:border-color .2s;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
      outline:none; border-color:var(--accent);
    }
    .form-group textarea { resize:vertical; min-height:100px; }
    .current-image { margin-top:8px; }
    .current-image img { width:80px; height:80px; object-fit:cover; border-radius:8px; border:2px solid #ddd; }
    .btn-row { display:flex; gap:12px; margin-top:24px; }
    .btn-submit { background:var(--accent); color:#000; border:none; padding:12px 28px; border-radius:8px; font-weight:900; font-size:1rem; cursor:pointer; }
    .btn-cancel { background:#eee; color:#333; border:none; padding:12px 28px; border-radius:8px; font-weight:700; font-size:1rem; cursor:pointer; text-decoration:none; display:inline-block; }
    .btn-submit:hover { background:#80b520; }

    /* ── Table ── */
    .table-card { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.08); overflow:hidden; }
    .table-card h2 { margin:0; padding:20px 25px; font-size:1.3rem; font-weight:900; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center; }
    .btn-add { background:var(--accent); color:#000; padding:8px 20px; border-radius:8px; font-weight:900; font-size:.9rem; text-decoration:none; }
    table { width:100%; border-collapse:collapse; }
    th { background:#f8f8f8; padding:12px 16px; text-align:left; font-size:.85rem; color:#888; font-weight:700; border-bottom:1px solid #eee; }
    td { padding:14px 16px; border-bottom:1px solid #f5f5f5; font-size:.9rem; vertical-align:middle; }
    tr:last-child td { border-bottom:none; }
    tr:hover td { background:#fafff0; }
    .member-thumb { width:50px; height:50px; object-fit:cover; border-radius:8px; }
    .no-photo { width:50px; height:50px; background:#eee; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.7rem; color:#aaa; text-align:center; }
    .status-active   { background:#d4edda; color:#155724; padding:3px 10px; border-radius:20px; font-size:.8rem; font-weight:700; }
    .status-inactive { background:#f8d7da; color:#721c24; padding:3px 10px; border-radius:20px; font-size:.8rem; font-weight:700; }
    .status-tbd      { background:#fff3cd; color:#856404; padding:3px 10px; border-radius:20px; font-size:.8rem; font-weight:700; }
    .actions a { margin-right:10px; font-weight:700; font-size:.85rem; text-decoration:none; }
    .edit-link   { color:#007bff; }
    .delete-link { color:#dc3545; }
    .edit-link:hover   { text-decoration:underline; }
    .delete-link:hover { text-decoration:underline; }
    @media(max-width:700px){ .form-grid{grid-template-columns:1fr;} }
  </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<div class="page-header">
  <h1><span>Board</span> Members</h1>
  <a href="administration.php" class="back-btn">← Back to Admin</a>
</div>

<div class="container">

  <?php if ($message): ?><div class="msg-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="msg-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <!-- ── Add / Edit Form ── -->
  <div class="form-card">
    <h2><?= $edit_member ? 'Edit Board Member' : 'Add New Board Member' ?></h2>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="<?= $edit_member ? 'edit' : 'add' ?>">
      <?php if ($edit_member): ?>
        <input type="hidden" name="id" value="<?= $edit_member['id'] ?>">
        <input type="hidden" name="existing_image" value="<?= htmlspecialchars($edit_member['image'] ?? '') ?>">
      <?php endif; ?>

      <div class="form-grid">
        <div class="form-group">
          <label>Name *</label>
          <input type="text" name="name" required value="<?= htmlspecialchars($edit_member['name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Role / Title</label>
          <input type="text" name="role" value="<?= htmlspecialchars($edit_member['role'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($edit_member['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Mobile</label>
          <input type="text" name="mobile" value="<?= htmlspecialchars($edit_member['mobile'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Date From</label>
          <input type="date" name="date_from" value="<?= htmlspecialchars($edit_member['date_from'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Date To</label>
          <input type="date" name="date_to" value="<?= htmlspecialchars($edit_member['date_to'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <option value="active"   <?= (($edit_member['status'] ?? '') === 'active')   ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= (($edit_member['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
            <option value="tbd"      <?= (($edit_member['status'] ?? '') === 'tbd')      ? 'selected' : '' ?>>TBD (Future Position)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Sort Order</label>
          <input type="number" name="sort_order" value="<?= htmlspecialchars($edit_member['sort_order'] ?? '0') ?>">
        </div>
        <div class="form-group full">
          <label>Bio</label>
          <textarea name="bio"><?= htmlspecialchars($edit_member['bio'] ?? '') ?></textarea>
        </div>
        <div class="form-group full">
          <label>Notes / Credentials</label>
          <textarea name="notes" style="min-height:60px"><?= htmlspecialchars($edit_member['notes'] ?? '') ?></textarea>
        </div>
        <div class="form-group full">
          <label>Photo (JPG, PNG, GIF, WEBP)</label>
          <?php if (!empty($edit_member['image'])): ?>
            <div class="current-image">
              <p style="margin:0 0 6px;font-size:.85rem;color:#888;">Current photo:</p>
              <img src="<?= htmlspecialchars($edit_member['image']) ?>" alt="Current photo">
            </div>
          <?php endif; ?>
          <input type="file" name="image" accept="image/*">
          <small style="color:#888;">Images are stored in <code>images/board/</code> on the server. Leave blank to keep current photo.</small>
        </div>
      </div>

      <div class="btn-row">
        <button type="submit" class="btn-submit"><?= $edit_member ? 'Update Member' : 'Add Member' ?></button>
        <?php if ($edit_member): ?>
          <a href="admin_board_members.php" class="btn-cancel">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- ── Members Table ── -->
  <div class="table-card">
    <h2>
      All Board Members
      <a href="admin_board_members.php" class="btn-add">+ Add New</a>
    </h2>
    <table>
      <thead>
        <tr>
          <th>Photo</th>
          <th>Name</th>
          <th>Role</th>
          <th>Email</th>
          <th>Status</th>
          <th>Sort</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($members)): ?>
        <tr><td colspan="7" style="text-align:center;color:#aaa;padding:40px;">No board members found.</td></tr>
        <?php else: ?>
        <?php foreach ($members as $m): ?>
        <tr>
          <td>
            <?php if (!empty($m['image'])): ?>
              <img src="<?= htmlspecialchars($m['image']) ?>" class="member-thumb" alt="<?= htmlspecialchars($m['name']) ?>">
            <?php else: ?>
              <div class="no-photo">No Photo</div>
            <?php endif; ?>
          </td>
          <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
          <td><?= htmlspecialchars($m['role']) ?></td>
          <td><?= htmlspecialchars($m['email'] ?? '') ?></td>
          <td><span class="status-<?= $m['status'] ?>"><?= ucfirst($m['status']) ?></span></td>
          <td><?= (int)$m['sort_order'] ?></td>
          <td class="actions">
            <a href="admin_board_members.php?action=edit&id=<?= $m['id'] ?>" class="edit-link">Edit</a>
            <a href="#" class="delete-link"
               onclick="if(confirm('Delete <?= htmlspecialchars(addslashes($m['name'])) ?>? This cannot be undone.')) {
                 var f=document.createElement('form'); f.method='POST'; f.action='admin_board_members.php';
                 f.innerHTML='<input name=action value=delete><input name=id value=<?= $m['id'] ?>>';
                 document.body.appendChild(f); f.submit();
               } return false;">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<?php $db->close(); include 'footer.php'; ?>
</body>
</html>