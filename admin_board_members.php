<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Forbidden');
}

require_once __DIR__ . '/db_configuration.php'; // provides $db (mysqli)

$message = '';
$error   = '';

// ── Image upload helper ───────────────────────────────────────
function handle_image_upload(array $file): string {
    if ($file['error'] !== UPLOAD_ERR_OK) return '';
    $upload_dir = __DIR__ . '/images/board/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) return '';
    $filename = uniqid('board_', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
        return 'images/board/' . $filename;
    }
    return '';
}

// ── Handle POST actions ───────────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id     = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($action === 'add' || $action === 'edit') {
        $name       = trim($_POST['name']       ?? '');
        $email      = trim($_POST['email']      ?? '');
        $mobile     = trim($_POST['mobile']     ?? '');
        $role       = trim($_POST['role']       ?? '');
        $bio        = trim($_POST['bio']        ?? '');
        $notes      = trim($_POST['notes']      ?? '');
        $date_from  = $_POST['date_from']       ?: null;
        $date_to    = $_POST['date_to']         ?: null;
        $status     = $_POST['status']          ?? 'active';
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        $image_path = $_POST['existing_image'] ?? '';
        if (!empty($_FILES['image']['name'])) {
            $uploaded = handle_image_upload($_FILES['image']);
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
            $stmt->close();
        }
    }

    if ($action === 'delete') {
        $stmt = $db->prepare("DELETE FROM board_members WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) $message = 'Board member deleted.';
        else $error = 'Error deleting member.';
        $stmt->close();
    }
}

// ── Fetch all board members ───────────────────────────────────
$members = [];
$res = $db->query("SELECT * FROM board_members ORDER BY sort_order ASC, id ASC");
if ($res) while ($row = $res->fetch_assoc()) $members[] = $row;

// ── Fetch single member for edit ─────────────────────────────
$edit_member = null;
if ($action === 'edit' && $id && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT * FROM board_members WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit_member = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Board Members – Administration</title>
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
            max-width: 1200px;
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
            margin-bottom: 32px;
        }
        .card h2 {
            margin: 0 0 22px 0;
            font-size: 1.25em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Form grid ── */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label {
            font-size: .83em;
            font-weight: 700;
            color: #274606;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px 13px;
            border: 1.5px solid #cde8a0;
            border-radius: 8px;
            font-size: .95em;
            font-family: 'Roboto', sans-serif;
            background: #f8fbe9;
            color: #222;
            outline: none;
            transition: border-color .18s, box-shadow .18s;
            box-sizing: border-box;
            width: 100%;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #99d930;
            box-shadow: 0 0 0 3px rgba(153,217,48,.15);
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .current-image { margin-top: 8px; }
        .current-image img {
            width: 80px; height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1.5px solid #cde8a0;
        }
        .hint { font-size: .78em; color: #888; margin-top: 3px; }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 22px;
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
        .btn-edit    { background: #f0fad8; color: #274606; border: 1.5px solid #99d930; padding: 6px 13px; font-size: .83em; }
        .btn-edit:hover    { background: #e2f5b2; }
        .btn-danger  { background: #fff0f0; color: #c00; border: 1.5px solid #f99; padding: 6px 13px; font-size: .83em; }
        .btn-danger:hover  { background: #ffe0e0; }

        .btn-row { display: flex; gap: 12px; margin-top: 22px; flex-wrap: wrap; }

        /* ── Table ── */
        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
        }
        .table-wrap { overflow-x: auto; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: .93em; }
        thead tr { background: #99d930; color: #274606; }
        thead th {
            padding: 12px 14px;
            text-align: left;
            font-weight: 900;
            font-size: .83em;
            text-transform: uppercase;
            letter-spacing: .4px;
            white-space: nowrap;
        }
        tbody tr { border-bottom: 1px solid #e8f5c8; transition: background .13s; }
        tbody tr:hover { background: #f4fce6; }
        tbody tr:last-child { border-bottom: none; }
        td { padding: 11px 14px; color: #333; vertical-align: middle; }
        td.actions { white-space: nowrap; display: flex; gap: 8px; align-items: center; }

        .member-thumb {
            width: 50px; height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #cde8a0;
            display: block;
        }
        .no-photo {
            width: 50px; height: 50px;
            background: #f0f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .7rem;
            color: #aaa;
            text-align: center;
            border: 1px solid #e0e0e0;
        }

        /* ── Status badges ── */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .78em;
            font-weight: 700;
        }
        .status-active   { background: #edfae5; color: #2a6006; border: 1px solid #99d930; }
        .status-inactive { background: #fff0f0; color: #c00;    border: 1px solid #f99; }
        .status-tbd      { background: #fff8e1; color: #f57f17; border: 1px solid #ffcc02; }

        @media (max-width: 700px) {
            .form-grid { grid-template-columns: 1fr; }
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
    <h1 class="banner-title">Board Members</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <?php if ($message): ?>
        <div class="flash success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="flash error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ── Add / Edit Form ── -->
    <div class="card">
        <h2><?= $edit_member ? '✏️ Edit Board Member' : '➕ Add New Board Member' ?></h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $edit_member ? 'edit' : 'add' ?>">
            <?php if ($edit_member): ?>
                <input type="hidden" name="id" value="<?= intval($edit_member['id']) ?>">
                <input type="hidden" name="existing_image"
                       value="<?= htmlspecialchars($edit_member['image'] ?? '') ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-group">
                    <label>Name <span style="color:#c00">*</span></label>
                    <input type="text" name="name" required
                           value="<?= htmlspecialchars($edit_member['name'] ?? '') ?>"
                           placeholder="e.g. Jane Smith">
                </div>
                <div class="form-group">
                    <label>Role / Title</label>
                    <input type="text" name="role"
                           value="<?= htmlspecialchars($edit_member['role'] ?? '') ?>"
                           placeholder="e.g. Chairperson">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email"
                           value="<?= htmlspecialchars($edit_member['email'] ?? '') ?>"
                           placeholder="jane@example.com">
                </div>
                <div class="form-group">
                    <label>Mobile</label>
                    <input type="text" name="mobile"
                           value="<?= htmlspecialchars($edit_member['mobile'] ?? '') ?>"
                           placeholder="(555) 000-0000">
                </div>
                <div class="form-group">
                    <label>Date From</label>
                    <input type="date" name="date_from"
                           value="<?= htmlspecialchars($edit_member['date_from'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Date To</label>
                    <input type="date" name="date_to"
                           value="<?= htmlspecialchars($edit_member['date_to'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active"   <?= ($edit_member['status'] ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($edit_member['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="tbd"      <?= ($edit_member['status'] ?? '') === 'tbd'      ? 'selected' : '' ?>>TBD (Future Position)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order"
                           value="<?= htmlspecialchars($edit_member['sort_order'] ?? '0') ?>">
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
                            <p class="hint" style="margin:0 0 6px;">Current photo:</p>
                            <img src="<?= htmlspecialchars($edit_member['image']) ?>" alt="Current photo">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*"
                           style="background:#fff;border-style:dashed;margin-top:8px;">
                    <span class="hint">
                        Images stored in <code>images/board/</code>. Leave blank to keep current photo.
                    </span>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-green">
                    <?= $edit_member ? '💾 Update Member' : '➕ Add Member' ?>
                </button>
                <?php if ($edit_member): ?>
                    <a href="admin_board_members.php" class="btn btn-outline">✕ Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ── Members Table ── -->
    <div class="card">
        <div class="table-header">
            <h2 style="margin:0;">All Board Members
                <span style="background:#99d930;color:#274606;border-radius:20px;padding:2px 12px;font-size:.8em;margin-left:8px;">
                    <?= count($members) ?>
                </span>
            </h2>
            <a href="admin_board_members.php" class="btn btn-green">➕ Add New</a>
        </div>

        <div class="table-wrap">
            <?php if (empty($members)): ?>
                <div style="text-align:center;color:#888;padding:38px 0;">
                    No board members found.
                </div>
            <?php else: ?>
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
                <?php foreach ($members as $m): ?>
                <tr>
                    <td>
                        <?php if (!empty($m['image'])): ?>
                            <img src="<?= htmlspecialchars($m['image']) ?>"
                                 class="member-thumb"
                                 alt="<?= htmlspecialchars($m['name']) ?>">
                        <?php else: ?>
                            <div class="no-photo">No Photo</div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                    <td><?= htmlspecialchars($m['role'] ?? '') ?></td>
                    <td style="max-width:160px;word-break:break-word;overflow-wrap:break-word;">
                        <?php if (!empty($m['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($m['email']) ?>"
                               style="color:#4a8500;">
                                <?= htmlspecialchars($m['email']) ?>
                            </a>
                        <?php else: ?>
                            <span style="color:#bbb;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?= htmlspecialchars($m['status']) ?>">
                            <?= ucfirst(htmlspecialchars($m['status'])) ?>
                        </span>
                    </td>
                    <td><?= intval($m['sort_order']) ?></td>
                    <td class="actions">
                        <a href="admin_board_members.php?action=edit&id=<?= intval($m['id']) ?>"
                           class="btn btn-edit">✏️ Edit</a>
                        <form method="POST" action="admin_board_members.php"
                              onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($m['name'])) ?>? This cannot be undone.');"
                              style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= intval($m['id']) ?>">
                            <button type="submit" class="btn btn-danger">🗑 Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>