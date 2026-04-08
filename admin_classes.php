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

// ── ADD CLASS ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $class_status = in_array($_POST['status'] ?? '', ['Proposed','Approved','Inactive'])
                    ? $_POST['status'] : 'Proposed';

    if ($name === '') {
        $message      = "Class name is required.";
        $message_type = "error";
    } else {
        $stmt = $db->prepare("INSERT INTO classes (Class_Name, Description, Status) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $description, $class_status);
        $stmt->execute();
        $stmt->close();
        $message      = "Class added successfully.";
        $message_type = "success";
    }
}

// ── FETCH classes ─────────────────────────────────────────────
$result  = $db->query("SELECT * FROM classes ORDER BY Class_Id ASC");
$classes = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Classes – Administration</title>
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

        /* ── Form ── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 16px;
        }
        .form-group label {
            font-size: .85em;
            font-weight: 700;
            color: #274606;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .form-group input[type="text"],
        .form-group select,
        .form-group textarea {
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
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #99d930;
            box-shadow: 0 0 0 3px rgba(153,217,48,.15);
        }
        .form-group textarea { resize: vertical; min-height: 100px; }

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
        .btn-green  { background: #99d930; color: #274606; box-shadow: 0 2px 8px rgba(153,217,48,.25); }
        .btn-green:hover  { background: #85c220; transform: translateY(-1px); }
        .btn-edit   { background: #f0fad8; color: #274606; border: 1.5px solid #99d930; padding: 6px 14px; font-size: .85em; }
        .btn-edit:hover   { background: #e2f5b2; }
        .btn-danger { background: #fff0f0; color: #c00; border: 1.5px solid #f99; padding: 6px 14px; font-size: .85em; }
        .btn-danger:hover { background: #ffe0e0; }

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

        /* ── Status badge ── */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .8em;
            font-weight: 700;
        }
        .status-proposed { background: #fff8e1; color: #f57f17; }
        .status-approved { background: #edfae5; color: #2a6006; }
        .status-inactive { background: #f3f3f3; color: #757575; }

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
        function deleteClass(classId, className) {
            if (confirm('Delete class "' + className + '"? This cannot be undone.')) {
                window.location.href = 'admin_delete_record_warning.php?Class_Id=' + classId;
            }
        }

        $(document).ready(function () {
            $('#classes_table thead tr').clone(true).appendTo('#classes_table thead');
            $('#classes_table thead tr:eq(1) th').each(function () {
                var title = $(this).text();
                if (title.toLowerCase() === 'actions') {
                    $(this).html('');
                } else {
                    $(this).html('<input type="text" placeholder="Search ' + title + '" />');
                }
            });

            var table = $('#classes_table').DataTable({
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
    <h1 class="banner-title">Classes</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <?php if ($message): ?>
        <div class="flash <?= htmlspecialchars($message_type) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- ── Add Class Form ── -->
    <div class="card">
        <h2>➕ Add New Class</h2>
        <form method="POST" action="admin_classes.php">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label for="name">Class Name <span style="color:#c00">*</span></label>
                <input type="text" id="name" name="name" required maxlength="150"
                       placeholder="e.g. Introduction to Programming">
            </div>

            <div class="form-group">
                <label for="description">Description <span style="color:#c00">*</span></label>
                <textarea id="description" name="description" required
                          placeholder="Class description…"></textarea>
            </div>

            <div class="form-group" style="max-width:300px;">
                <label for="status">Status <span style="color:#c00">*</span></label>
                <select id="status" name="status" required>
                    <option value="Proposed">Proposed</option>
                    <option value="Approved">Approved</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-green">➕ Add Class</button>
        </form>
    </div>

    <!-- ── Classes Table ── -->
    <div class="card">
        <div class="toolbar">
            <div class="toggle-cols">
                Toggle column:
                <a class="toggle-vis" data-column="0">Class ID</a> -
                <a class="toggle-vis" data-column="1">Class Name</a> -
                <a class="toggle-vis" data-column="2">Description</a> -
                <a class="toggle-vis" data-column="3">Status</a>
            </div>
        </div>

        <table id="classes_table" class="display compact" style="width:100%">
            <thead>
                <tr>
                    <th>Class ID</th>
                    <th>Class Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($classes)): ?>
                <?php foreach ($classes as $row):
                    $classId   = intval($row['Class_Id']);
                    $className = htmlspecialchars($row['Class_Name']   ?? '', ENT_QUOTES);
                    $desc      = htmlspecialchars($row['Description']  ?? '', ENT_QUOTES);
                    $cstatus   = htmlspecialchars($row['Status']       ?? '', ENT_QUOTES);
                    $badgeClass = 'status-' . strtolower($cstatus);
                ?>
                <tr>
                    <td><?= $classId ?></td>
                    <td><strong><?= $className ?></strong></td>
                    <td><?= $desc ?></td>
                    <td>
                        <span class="status-badge <?= $badgeClass ?>">
                            <?= $cstatus ?>
                        </span>
                    </td>
                    <td style="white-space:nowrap;">
                        <a href="admin_edit_class.php?Class_Id=<?= $classId ?>"
                           class="btn btn-edit">✏️ Edit</a>
                        <button type="button" class="btn btn-danger"
                                onclick="deleteClass(<?= $classId ?>, '<?= htmlspecialchars(addslashes($row['Class_Name'] ?? '')) ?>')">
                            🗑 Delete
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center;color:#888;padding:30px;">
                        No classes found.
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