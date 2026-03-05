<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    http_response_code(403);
    die('Forbidden');
}

require_once 'db_configuration.php'; // provides $db (mysqli)

$message      = '';
$message_type = '';

// ── DELETE ───────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $del_id = intval($_POST['patron_id']);
    $stmt   = $db->prepare("DELETE FROM patrons WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $stmt->close();
    $message      = "Patron deleted successfully.";
    $message_type = "success";
}

// ── ADD ──────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $name         = trim($_POST['name']);
    $email        = trim($_POST['email'])        ?: null;
    $mobile       = trim($_POST['mobile'])       ?: null;
    $event_info   = trim($_POST['event_info'])   ?: null;
    $date_created = trim($_POST['date_created']) ?: date('Y-m-d H:i:s');

    if ($name === '') {
        $message      = "Name is required.";
        $message_type = "error";
    } else {
        $stmt = $db->prepare("INSERT INTO patrons (name, email, mobile, event_info, date_created) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $mobile, $event_info, $date_created);
        $stmt->execute();
        $stmt->close();
        $message      = "Patron added successfully.";
        $message_type = "success";
    }
}

// ── EDIT (save) ──────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $edit_id      = intval($_POST['patron_id']);
    $name         = trim($_POST['name']);
    $email        = trim($_POST['email'])        ?: null;
    $mobile       = trim($_POST['mobile'])       ?: null;
    $event_info   = trim($_POST['event_info'])   ?: null;
    $date_created = trim($_POST['date_created']) ?: date('Y-m-d H:i:s');

    if ($name === '') {
        $message      = "Name is required.";
        $message_type = "error";
    } else {
        $stmt = $db->prepare("UPDATE patrons SET name = ?, email = ?, mobile = ?, event_info = ?, date_created = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $email, $mobile, $event_info, $date_created, $edit_id);
        $stmt->execute();
        $stmt->close();
        $message      = "Patron updated successfully.";
        $message_type = "success";
    }
}

// ── FETCH edit target ────────────────────────────────────────
$edit_patron = null;
if (isset($_GET['edit'])) {
    $eid  = intval($_GET['edit']);
    $stmt = $db->prepare("SELECT * FROM patrons WHERE id = ?");
    $stmt->bind_param("i", $eid);
    $stmt->execute();
    $result      = $stmt->get_result();
    $edit_patron = $result->fetch_assoc();
    $stmt->close();
}

// ── SEARCH / LIST ────────────────────────────────────────────
$search  = isset($_GET['search']) ? trim($_GET['search']) : '';
$patrons = [];

if ($search !== '') {
    $like = "%$search%";
    $stmt = $db->prepare("
        SELECT * FROM patrons
        WHERE name LIKE ? OR email LIKE ? OR mobile LIKE ? OR event_info LIKE ?
        ORDER BY date_created DESC
    ");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
} else {
    $stmt = $db->prepare("SELECT * FROM patrons ORDER BY date_created DESC");
}

$stmt->execute();
$result  = $stmt->get_result();
$patrons = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Patrons – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
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
            max-width: 1100px;
            margin: 36px auto 60px auto;
            padding: 0 18px;
        }

        /* ── Flash message ── */
        .flash {
            padding: 13px 20px;
            border-radius: 9px;
            margin-bottom: 22px;
            font-weight: 700;
            font-size: 1em;
        }
        .flash.success { background: #edfae5; color: #2a6006; border: 1.5px solid #99d930; }
        .flash.error   { background: #fff0f0; color: #a00;    border: 1.5px solid #f88; }

        /* ── Card panel ── */
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 30px 32px;
            margin-bottom: 32px;
        }
        .card h2 {
            margin: 0 0 22px 0;
            font-size: 1.35em;
            color: #274606;
            font-weight: 900;
            letter-spacing: .3px;
        }

        /* ── Form grid ── */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px 24px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .form-group label {
            font-size: .88em;
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
            transition: border-color .18s, box-shadow .18s;
            outline: none;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #99d930;
            box-shadow: 0 0 0 3px rgba(153,217,48,.15);
        }
        .form-group textarea { resize: vertical; min-height: 68px; }
        .form-group .hint {
            font-size: .78em;
            color: #888;
            margin-top: 2px;
        }

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
            transition: background .18s, transform .12s, box-shadow .18s;
            text-decoration: none;
        }
        .btn-green   { background: #99d930; color: #274606; box-shadow: 0 2px 8px rgba(153,217,48,.25); }
        .btn-green:hover   { background: #85c220; transform: translateY(-1px); }
        .btn-outline { background: #fff; color: #274606; border: 2px solid #99d930; }
        .btn-outline:hover { background: #f0fad8; }
        .btn-danger  { background: #fff0f0; color: #c00; border: 1.5px solid #f99; }
        .btn-danger:hover  { background: #ffe0e0; }
        .btn-edit    { background: #f0fad8; color: #274606; border: 1.5px solid #99d930; }
        .btn-edit:hover    { background: #e2f5b2; }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        /* ── Search bar ── */
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 22px;
            flex-wrap: wrap;
            align-items: center;
        }
        .search-bar input {
            flex: 1;
            min-width: 200px;
            padding: 10px 14px;
            border: 1.5px solid #cde8a0;
            border-radius: 8px;
            font-size: 1em;
            font-family: 'Roboto', sans-serif;
            background: #f8fbe9;
            outline: none;
            transition: border-color .18s;
        }
        .search-bar input:focus { border-color: #99d930; }

        /* ── Table ── */
        .table-wrap { overflow-x: auto; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: .97em; }
        thead tr { background: #99d930; color: #274606; }
        thead th {
            padding: 12px 14px;
            text-align: left;
            font-weight: 900;
            font-size: .88em;
            text-transform: uppercase;
            letter-spacing: .4px;
            white-space: nowrap;
        }
        tbody tr { border-bottom: 1px solid #e8f5c8; transition: background .13s; }
        tbody tr:hover { background: #f4fce6; }
        tbody tr:last-child { border-bottom: none; }
        td { padding: 11px 14px; color: #333; vertical-align: middle; }
        td.actions { white-space: nowrap; display: flex; gap: 8px; align-items: center; }

        .badge-count {
            display: inline-block;
            background: #99d930;
            color: #274606;
            border-radius: 20px;
            padding: 2px 12px;
            font-size: .85em;
            font-weight: 700;
            margin-left: 10px;
        }
        .empty-state {
            text-align: center;
            color: #888;
            padding: 38px 0;
            font-size: 1.05em;
        }
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

        @media (max-width: 680px) {
            .form-grid { grid-template-columns: 1fr; }
            .card { padding: 18px 14px; }
            .banner-title { font-size: 2em; }
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
    <h1 class="banner-title">Patrons</h1>
</div>

<div class="page-wrap">

    <a href="admin.php" class="back-link">&#8592; Back to Administration</a>

    <?php if ($message): ?>
        <div class="flash <?= htmlspecialchars($message_type) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- ── ADD / EDIT FORM ── -->
    <div class="card">
        <h2><?= $edit_patron ? '✏️ Edit Patron' : '➕ Add New Patron' ?></h2>
        <form method="POST" action="admin_patrons.php<?= $edit_patron ? '?edit=' . intval($edit_patron['id']) : '' ?>">
            <input type="hidden" name="action" value="<?= $edit_patron ? 'edit' : 'add' ?>">
            <?php if ($edit_patron): ?>
                <input type="hidden" name="patron_id" value="<?= intval($edit_patron['id']) ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Full Name <span style="color:#c00">*</span></label>
                    <input type="text" id="name" name="name" required maxlength="150"
                           value="<?= htmlspecialchars($edit_patron['name'] ?? '') ?>"
                           placeholder="e.g. Jane Smith">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" maxlength="255"
                           value="<?= htmlspecialchars($edit_patron['email'] ?? '') ?>"
                           placeholder="jane@example.com">
                </div>

                <div class="form-group">
                    <label for="mobile">Mobile</label>
                    <input type="tel" id="mobile" name="mobile" maxlength="30"
                           value="<?= htmlspecialchars($edit_patron['mobile'] ?? '') ?>"
                           placeholder="(555) 000-0000">
                </div>

                <div class="form-group">
                    <label for="event_info">Event / Booth</label>
                    <input type="text" id="event_info" name="event_info" maxlength="255"
                           value="<?= htmlspecialchars($edit_patron['event_info'] ?? '') ?>"
                           placeholder="e.g. Downtown Farmers Market – June 2025">
                </div>

                <div class="form-group">
                    <label for="date_created">Date Met <span style="color:#c00">*</span></label>
                    <input type="date" id="date_created" name="date_created"
                           value="<?= htmlspecialchars(
                               $edit_patron
                                   ? date('Y-m-d', strtotime($edit_patron['date_created']))
                                   : date('Y-m-d')
                           ) ?>">
                    <span class="hint">Defaults to today if left unchanged.</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-green">
                    <?= $edit_patron ? '💾 Save Changes' : '➕ Add Patron' ?>
                </button>
                <?php if ($edit_patron): ?>
                    <a href="admin_patrons.php" class="btn btn-outline">✕ Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ── PATRON LIST ── -->
    <div class="card">
        <h2>
            All Patrons
            <span class="badge-count"><?= count($patrons) ?></span>
        </h2>

        <form method="GET" action="admin_patrons.php" class="search-bar">
            <input type="text" name="search"
                   placeholder="Search by name, email, mobile, or event…"
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-green">🔍 Search</button>
            <?php if ($search): ?>
                <a href="admin_patrons.php" class="btn btn-outline">✕ Clear</a>
            <?php endif; ?>
        </form>

        <div class="table-wrap">
            <?php if (empty($patrons)): ?>
                <div class="empty-state">
                    No patrons found<?= $search ? ' matching "' . htmlspecialchars($search) . '"' : '' ?>.
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Event / Booth</th>
                        <th>Date Met</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($patrons as $p): ?>
                    <tr>
                        <td><?= intval($p['id']) ?></td>
                        <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                        <td>
                            <?php if (!empty($p['email'])): ?>
                                <a href="mailto:<?= htmlspecialchars($p['email']) ?>" style="color:#4a8500;">
                                    <?= htmlspecialchars($p['email']) ?>
                                </a>
                            <?php else: ?>
                                <span style="color:#bbb;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($p['mobile'])): ?>
                                <a href="tel:<?= htmlspecialchars($p['mobile']) ?>" style="color:#4a8500;">
                                    <?= htmlspecialchars($p['mobile']) ?>
                                </a>
                            <?php else: ?>
                                <span style="color:#bbb;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= !empty($p['event_info'])
                                ? htmlspecialchars($p['event_info'])
                                : '<span style="color:#bbb;">—</span>' ?>
                        </td>
                        <td style="white-space:nowrap;">
                            <?= date('M j, Y', strtotime($p['date_created'])) ?>
                        </td>
                        <td class="actions">
                            <a href="admin_patrons.php?edit=<?= intval($p['id']) ?>"
                               class="btn btn-edit">✏️ Edit</a>
                            <form method="POST" action="admin_patrons.php"
                                  onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($p['name'])) ?>? This cannot be undone.');">
                                <input type="hidden" name="action"    value="delete">
                                <input type="hidden" name="patron_id" value="<?= intval($p['id']) ?>">
                                <button type="submit" class="btn btn-danger">🗑 Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>