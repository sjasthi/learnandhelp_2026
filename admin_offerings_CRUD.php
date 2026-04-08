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
    $batch_name = trim($_POST['Batch_Name'] ?? '');
    $class_id   = intval($_POST['Class_Id'] ?? 0);

    if ($batch_name === '' || $class_id === 0) {
        $message      = "Batch name and class are required.";
        $message_type = "error";
    } else {
        $stmt = $db->prepare("INSERT INTO offerings (Batch_Name, Class_Id) VALUES (?, ?)");
        $stmt->bind_param("si", $batch_name, $class_id);
        $stmt->execute();
        $stmt->close();
        $message      = "Offering added successfully.";
        $message_type = "success";
    }
}

// ── EDIT ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $batch_name = trim($_POST['Batch_Name'] ?? '');
    $class_id   = intval($_POST['Class_Id'] ?? 0);

    if ($batch_name === '' || $class_id === 0) {
        $message      = "Batch name and class are required.";
        $message_type = "error";
    } else {
        $stmt = $db->prepare("UPDATE offerings SET Class_Id = ? WHERE Batch_Name = ?");
        $stmt->bind_param("is", $class_id, $batch_name);
        $stmt->execute();
        $stmt->close();
        $message      = "Offering updated successfully.";
        $message_type = "success";
    }
}

// ── DELETE ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $batch_name = trim($_POST['Batch_Name'] ?? '');

    if ($batch_name !== '') {
        $stmt = $db->prepare("DELETE FROM offerings WHERE Batch_Name = ?");
        $stmt->bind_param("s", $batch_name);
        $stmt->execute();
        $stmt->close();
        $message      = "Offering deleted successfully.";
        $message_type = "success";
    }
}

// ── Current action & edit target ─────────────────────────────
$action     = $_GET['action']     ?? '';
$batch_name = $_GET['batch_name'] ?? '';

$edit_row = null;
if ($action === 'edit' && $batch_name !== '') {
    $stmt = $db->prepare("SELECT * FROM offerings WHERE Batch_Name = ?");
    $stmt->bind_param("s", $batch_name);
    $stmt->execute();
    $edit_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ── Fetch all offerings ───────────────────────────────────────
$offerings_result = $db->query("
    SELECT offerings.Batch_Name, offerings.Class_Id, classes.Class_Name
    FROM offerings
    JOIN classes ON offerings.Class_Id = classes.Class_Id
    ORDER BY offerings.Batch_Name ASC
");
$offerings = $offerings_result ? $offerings_result->fetch_all(MYSQLI_ASSOC) : [];

// ── Fetch classes for dropdowns ───────────────────────────────
$classes_result = $db->query("SELECT Class_Id, Class_Name FROM classes ORDER BY Class_Name ASC");
$classes        = $classes_result ? $classes_result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Offerings – Administration</title>
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
            max-width: 400px;
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
            margin-bottom: 12px;
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
            $('#offerings_table thead tr').clone(true).appendTo('#offerings_table thead');
            $('#offerings_table thead tr:eq(1) th').each(function () {
                var title = $(this).text();
                if (title.toLowerCase() === 'actions') {
                    $(this).html('');
                } else {
                    $(this).html('<input type="text" placeholder="Search ' + title + '" />');
                }
            });

            var table = $('#offerings_table').DataTable({
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
    <h1 class="banner-title">Offerings</h1>
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
        <h2>➕ Add Offering</h2>
        <form method="POST" action="admin_offerings_CRUD.php">
            <div class="form-group">
                <label for="Batch_Name">Batch Name <span style="color:#c00">*</span></label>
                <input type="text" id="Batch_Name" name="Batch_Name" required
                       placeholder="e.g. 2024-2025">
            </div>
            <div class="form-group">
                <label for="Class_Id">Class <span style="color:#c00">*</span></label>
                <select id="Class_Id" name="Class_Id" required>
                    <option value="">— Select a Class —</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= intval($c['Class_Id']) ?>">
                            <?= htmlspecialchars($c['Class_Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" name="add" class="btn btn-green">➕ Add Offering</button>
                <a href="admin_offerings_CRUD.php" class="btn btn-outline">✕ Cancel</a>
            </div>
        </form>
    </div>

    <!-- ── EDIT FORM ── -->
    <?php elseif ($action === 'edit' && $edit_row): ?>
    <div class="card">
        <h2>✏️ Edit Offering — <strong><?= htmlspecialchars($batch_name) ?></strong></h2>
        <form method="POST" action="admin_offerings_CRUD.php">
            <input type="hidden" name="Batch_Name" value="<?= htmlspecialchars($edit_row['Batch_Name']) ?>">
            <div class="form-group">
                <label for="Class_Id">Class <span style="color:#c00">*</span></label>
                <select id="Class_Id" name="Class_Id" required>
                    <option value="">— Select a Class —</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= intval($c['Class_Id']) ?>"
                            <?= $c['Class_Id'] == $edit_row['Class_Id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['Class_Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" name="edit" class="btn btn-green">💾 Save Changes</button>
                <a href="admin_offerings_CRUD.php" class="btn btn-outline">✕ Cancel</a>
            </div>
        </form>
    </div>

    <!-- ── DELETE CONFIRM ── -->
    <?php elseif ($action === 'delete' && $batch_name !== ''): ?>
    <div class="card">
        <h2>🗑 Delete Offering</h2>
        <div class="delete-confirm">
            Are you sure you want to delete the offering for batch
            "<strong><?= htmlspecialchars($batch_name) ?></strong>"?
            This cannot be undone.
        </div>
        <form method="POST" action="admin_offerings_CRUD.php">
            <input type="hidden" name="Batch_Name" value="<?= htmlspecialchars($batch_name) ?>">
            <div class="form-actions">
                <button type="submit" name="delete" class="btn btn-danger">🗑 Yes, Delete</button>
                <a href="admin_offerings_CRUD.php" class="btn btn-outline">✕ Cancel</a>
            </div>
        </form>
    </div>

    <?php endif; ?>

    <!-- ── OFFERINGS TABLE ── -->
    <div class="card">
        <div class="toolbar">
            <div class="toggle-cols">
                Toggle column:
                <a class="toggle-vis" data-column="0">Batch Name</a> -
                <a class="toggle-vis" data-column="1">Class ID</a> -
                <a class="toggle-vis" data-column="2">Class Name</a>
            </div>
            <a href="admin_offerings_CRUD.php?action=add" class="btn btn-green">➕ Add Offering</a>
        </div>

        <table id="offerings_table" class="display compact" style="width:100%">
            <thead>
                <tr>
                    <th>Batch Name</th>
                    <th>Class ID</th>
                    <th>Class Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($offerings)): ?>
                <?php foreach ($offerings as $row):
                    $bname     = htmlspecialchars($row['Batch_Name'],  ENT_QUOTES);
                    $classId   = intval($row['Class_Id']);
                    $className = htmlspecialchars($row['Class_Name'],  ENT_QUOTES);
                    $bnameUrl  = urlencode($row['Batch_Name']);
                ?>
                <tr>
                    <td><?= $bname ?></td>
                    <td><?= $classId ?></td>
                    <td><?= $className ?></td>
                    <td style="white-space:nowrap;">
                        <a href="admin_offerings_CRUD.php?action=edit&batch_name=<?= $bnameUrl ?>"
                           class="btn btn-edit">✏️ Edit</a>
                        <a href="admin_offerings_CRUD.php?action=delete&batch_name=<?= $bnameUrl ?>"
                           class="btn btn-danger">🗑 Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align:center;color:#888;padding:30px;">
                        No offerings found.
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