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

require 'db_configuration.php'; // provides $db (mysqli)

$message      = '';
$message_type = '';

// ── ADD ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $pref_name = trim($_POST['Preference_Name'] ?? '');
    $value     = trim($_POST['Value']           ?? '');

    if ($pref_name === '') {
        $message      = "Preference name is required.";
        $message_type = "error";
    } else {
        $stmt = $db->prepare("INSERT INTO preferences (Preference_Name, Value) VALUES (?, ?)");
        $stmt->bind_param("ss", $pref_name, $value);
        $stmt->execute();
        $stmt->close();
        $message      = "Preference added successfully.";
        $message_type = "success";
    }
}

// ── EDIT ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $pref_name = trim($_POST['Preference_Name'] ?? '');
    $value     = trim($_POST['Value']           ?? '');

    if ($pref_name === '') {
        $message      = "Preference name is required.";
        $message_type = "error";
    } else {
        $stmt = $db->prepare("UPDATE preferences SET Value = ? WHERE Preference_Name = ?");
        $stmt->bind_param("ss", $value, $pref_name);
        $stmt->execute();
        $stmt->close();
        $message      = "Preference updated successfully.";
        $message_type = "success";
    }
}

// ── DELETE ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $pref_name = trim($_POST['Preference_Name'] ?? '');

    if ($pref_name !== '') {
        $stmt = $db->prepare("DELETE FROM preferences WHERE Preference_Name = ?");
        $stmt->bind_param("s", $pref_name);
        $stmt->execute();
        $stmt->close();
        $message      = "Preference deleted successfully.";
        $message_type = "success";
    }
}

// ── Current action & edit target ─────────────────────────────
$action    = $_GET['action']          ?? '';
$pref_name = $_GET['preference_name'] ?? '';

$edit_row = null;
if ($action === 'edit' && $pref_name !== '') {
    $stmt = $db->prepare("SELECT * FROM preferences WHERE Preference_Name = ?");
    $stmt->bind_param("s", $pref_name);
    $stmt->execute();
    $edit_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ── Fetch all preferences ─────────────────────────────────────
$result      = $db->query("SELECT * FROM preferences ORDER BY Preference_Name ASC");
$preferences = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Preferences – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

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

        /* ── Card ── */
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 24px 28px;
            margin-bottom: 28px;
        }
        .card h2 {
            margin: 0 0 20px 0;
            font-size: 1.2em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Toolbar ── */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
        }
        .toggle-cols { font-size: .88em; color: #555; }
        .toggle-cols a { color: #274606; font-weight: 700; text-decoration: none; margin: 0 3px; }
        .toggle-cols a:hover { color: #99d930; }

        /* ── Form ── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 16px;
            max-width: 420px;
        }
        .form-group label {
            font-size: .85em;
            font-weight: 700;
            color: #274606;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .form-group input[type="text"],
        .form-group select {
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
        .form-group select:focus {
            border-color: #99d930;
            box-shadow: 0 0 0 3px rgba(153,217,48,.15);
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
            text-decoration: none;
            transition: background .18s, transform .12s;
        }
        .btn-green   { background: #99d930; color: #274606; box-shadow: 0 2px 8px rgba(153,217,48,.25); }
        .btn-green:hover   { background: #85c220; transform: translateY(-1px); }
        .btn-outline { background: #fff; color: #274606; border: 2px solid #99d930; }
        .btn-outline:hover { background: #f0fad8; }
        .btn-edit    { background: #f0fad8; color: #274606; border: 1.5px solid #99d930; padding: 6px 14px; font-size: .85em; }
        .btn-edit:hover    { background: #e2f5b2; }
        .btn-danger  { background: #fff0f0; color: #c00; border: 1.5px solid #f99; padding: 6px 14px; font-size: .85em; }
        .btn-danger:hover  { background: #ffe0e0; }

        .form-actions { display: flex; gap: 12px; margin-top: 8px; flex-wrap: wrap; }

        /* ── Delete confirm box ── */
        .delete-confirm {
            background: #fff0f0;
            border: 1.5px solid #f99;
            border-radius: 10px;
            padding: 18px 22px;
            margin-bottom: 16px;
            color: #c00;
            font-weight: 700;
        }

        /* ── DataTables overrides ── */
        .dataTables_wrapper .dataTables_length   { float: left; }
        .dataTables_wrapper .dataTables_filter   { float: right; clear: none; }
        .dataTables_wrapper .dataTables_info     { clear: both; float: left; padding-top: 8px; }
        .dataTables_wrapper .dataTables_paginate { float: right; padding-top: 8px; }
        table.dataTable thead input { width: 100%; box-sizing: border-box; padding: 3px 6px; }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .card { padding: 14px 10px; }
        }
    </style>

    <script>
        $(document).ready(function () {
            $('#preferences_table thead tr').clone(true).appendTo('#preferences_table thead');
            $('#preferences_table thead tr:eq(1) th').each(function () {
                var title = $(this).text();
                if (title.toLowerCase() === 'actions') {
                    $(this).html('');
                } else {
                    $(this).html('<input type="text" placeholder="Search ' + title + '" />');
                }
            });

            var table = $('#preferences_table').DataTable({
                initComplete: function () {
                    this.api().columns().every(function () {
                        var that = this;
                        $('input', this.header()).on('keyup change clear', function () {
                            if (that.search() !== this.value) {
                                that.search(this.value).draw();
                            }
                        });
                    });
                }
            });

            $('a.toggle-vis').on('click', function (e) {
                e.preventDefault();
                var column = table.column($(this).attr('data-column'));
                column.visible(!column.visible());
            });
        });
    </script>
</head>
<body>

<?php
include 'show-navbar.php';
show_navbar();
?>

<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Admin banner">
    <h1 class="banner-title">Preferences</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <?php if ($message): ?>
        <div class="flash <?= htmlspecialchars($message_type) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- ── ADD FORM ── -->
    <?php if ($action === 'add'): ?>
    <div class="card">
        <h2>➕ Add Preference</h2>
        <form method="POST" action="admin_preferences_CRUD.php">
            <div class="form-group">
                <label for="Preference_Name">Preference Name <span style="color:#c00">*</span></label>
                <input type="text" id="Preference_Name" name="Preference_Name" required
                       placeholder="e.g. Email_Mode">
            </div>
            <div class="form-group">
                <label for="Value">Value <span style="color:#c00">*</span></label>
                <input type="text" id="Value" name="Value" required
                       placeholder="e.g. PROD">
            </div>
            <div class="form-actions">
                <button type="submit" name="add" class="btn btn-green">➕ Add Preference</button>
                <a href="admin_preferences_CRUD.php" class="btn btn-outline">✕ Cancel</a>
            </div>
        </form>
    </div>

    <!-- ── EDIT FORM ── -->
    <?php elseif ($action === 'edit' && $edit_row): ?>
    <div class="card">
        <h2>✏️ Edit Preference — <strong><?= htmlspecialchars($pref_name) ?></strong></h2>
        <form method="POST" action="admin_preferences_CRUD.php">
            <input type="hidden" name="Preference_Name"
                   value="<?= htmlspecialchars($edit_row['Preference_Name']) ?>">
            <div class="form-group">
                <label for="Value">Value <span style="color:#c00">*</span></label>
                <?php if ($edit_row['Preference_Name'] === 'Email_Mode'): ?>
                    <select id="Value" name="Value" required>
                        <option value="DEV"  <?= $edit_row['Value'] === 'DEV'  ? 'selected' : '' ?>>
                            DEV — emails go to admin only
                        </option>
                        <option value="PROD" <?= $edit_row['Value'] === 'PROD' ? 'selected' : '' ?>>
                            PROD — emails go to parents &amp; student
                        </option>
                    </select>
                <?php else: ?>
                    <input type="text" id="Value" name="Value" required
                           value="<?= htmlspecialchars($edit_row['Value'] ?? '') ?>">
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <button type="submit" name="edit" class="btn btn-green">💾 Save Changes</button>
                <a href="admin_preferences_CRUD.php" class="btn btn-outline">✕ Cancel</a>
            </div>
        </form>
    </div>

    <!-- ── DELETE CONFIRM ── -->
    <?php elseif ($action === 'delete' && $pref_name !== ''): ?>
    <div class="card">
        <h2>🗑 Delete Preference</h2>
        <div class="delete-confirm">
            Are you sure you want to delete the preference
            "<strong><?= htmlspecialchars($pref_name) ?></strong>"?
            This cannot be undone.
        </div>
        <form method="POST" action="admin_preferences_CRUD.php">
            <input type="hidden" name="Preference_Name"
                   value="<?= htmlspecialchars($pref_name) ?>">
            <div class="form-actions">
                <button type="submit" name="delete" class="btn btn-danger">🗑 Yes, Delete</button>
                <a href="admin_preferences_CRUD.php" class="btn btn-outline">✕ Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- ── PREFERENCES TABLE ── -->
    <div class="card">
        <div class="toolbar">
            <div class="toggle-cols">
                Toggle column:
                <a class="toggle-vis" data-column="0">Preference Name</a> -
                <a class="toggle-vis" data-column="1">Value</a>
            </div>
            <a href="admin_preferences_CRUD.php?action=add" class="btn btn-green">➕ Add Preference</a>
        </div>

        <table id="preferences_table" class="display compact" style="width:100%">
            <thead>
                <tr>
                    <th>Preference Name</th>
                    <th>Value</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($preferences)): ?>
                <?php foreach ($preferences as $row):
                    $pname    = htmlspecialchars($row['Preference_Name'], ENT_QUOTES);
                    $pvalue   = htmlspecialchars($row['Value']           ?? '', ENT_QUOTES);
                    $pnameUrl = urlencode($row['Preference_Name']);
                ?>
                <tr>
                    <td><strong><?= $pname ?></strong></td>
                    <td><?= $pvalue ?></td>
                    <td style="white-space:nowrap;">
                        <a href="admin_preferences_CRUD.php?action=edit&preference_name=<?= $pnameUrl ?>"
                           class="btn btn-edit">✏️ Edit</a>
                        <a href="admin_preferences_CRUD.php?action=delete&preference_name=<?= $pnameUrl ?>"
                           class="btn btn-danger">🗑 Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align:center;color:#888;padding:30px;">
                        No preferences found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>