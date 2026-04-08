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

// ── Profile image helper ──────────────────────────────────────
function get_profile_image(int $id): string {
    $matches = glob('schools/' . $id . '/profile_image.*');
    return (count($matches) === 1) ? $matches[0] : 'images/admin_icons/school.png';
}

// ── Fetch all schools ─────────────────────────────────────────
$sql = "SELECT id, name, type, category, grade_level_start, grade_level_end,
               current_enrollment, address_text, state_name, state_code,
               pin_code, contact_name, contact_designation, contact_phone,
               contact_email, status, notes, referenced_by, supported_by
        FROM schools";
$result = $db->query($sql);
$schools = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$time    = time();
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Schools – Administration</title>
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
            overflow-x: hidden;
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
            max-width: 1800px;
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

        /* ── Card ── */
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 24px 28px;
            margin-bottom: 28px;
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

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: .85em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .18s, transform .12s;
            white-space: nowrap;
        }
        .btn-green  { background: #99d930; color: #274606; box-shadow: 0 2px 8px rgba(153,217,48,.25); }
        .btn-green:hover  { background: #85c220; transform: translateY(-1px); }
        .btn-edit   { background: #f0fad8; color: #274606; border: 1.5px solid #99d930; }
        .btn-edit:hover   { background: #e2f5b2; }
        .btn-danger { background: #fff0f0; color: #c00; border: 1.5px solid #f99; }
        .btn-danger:hover { background: #ffe0e0; }

        /* ── Action buttons column ── */
        .action-col {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 80px;
        }
        .action-col form { display: block; }

        /* ── Profile image ── */
        .school-thumb {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #cde8a0;
            display: block;
        }

        /* ── Status badge ── */
        .badge-proposed {
            display: inline-block;
            background: #e65100;
            color: #fff;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: .78em;
            font-weight: 700;
            white-space: nowrap;
        }
        .badge-active {
            display: inline-block;
            background: #edfae5;
            color: #2a6006;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: .78em;
            font-weight: 700;
            white-space: nowrap;
            border: 1px solid #99d930;
        }

        /* ── Inline editing ── */
        [contenteditable="true"] {
            outline: none;
            border-radius: 4px;
            padding: 3px 5px;
            min-width: 60px;
            transition: background .15s;
        }
        [contenteditable="true"]:hover  { background: #f4fce6; }
        [contenteditable="true"]:focus  { background: #edfae5; box-shadow: 0 0 0 2px #99d930; }

        /* ── Search inputs in header ── */
        #schools_table thead tr:nth-child(2) th { padding: 4px 6px; }
        #schools_table thead tr:nth-child(2) input {
            width: 100%;
            box-sizing: border-box;
            padding: 4px 6px;
            font-size: .8em;
            border: 1px solid #cde8a0;
            border-radius: 4px;
            background: #f8fbe9;
            color: #222;
            outline: none;
        }
        #schools_table thead tr:nth-child(2) input:focus {
            border-color: #99d930;
        }

        /* ── DataTables overrides ── */
        .dataTables_wrapper .dataTables_length   { float: left; }
        .dataTables_wrapper .dataTables_filter   { float: right; clear: none; }
        .dataTables_wrapper .dataTables_info     { clear: both; float: left; padding-top: 8px; }
        .dataTables_wrapper .dataTables_paginate { float: right; padding-top: 8px; }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .card { padding: 14px 10px; }
        }
    </style>

    <script>
        // Inline-edit save
        function updateValue(element, column, id) {
            var value = element.innerText.trim();
            $.ajax({
                url: 'admin_edit_school_in_place.php',
                type: 'POST',
                data: { value: value, column: column, id: id },
                success: function (result) { console.log(result); }
            });
        }

        function deleteSchool(id, name) {
            if (confirm('Delete "' + name + '"? This cannot be undone.')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'admin_delete_school.php';
                var inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = 'id'; inp.value = id;
                form.appendChild(inp);
                document.body.appendChild(form);
                form.submit();
            }
        }

        $(document).ready(function () {
            // Clone header row for per-column search
            $('#schools_table thead tr').clone(true).appendTo('#schools_table thead');
            $('#schools_table thead tr:eq(1) th').each(function () {
                var title = $(this).text();
                if (title.toLowerCase() === 'options' || title.toLowerCase() === 'profile') {
                    $(this).html('');
                } else {
                    $(this).html('<input type="text" placeholder="Search ' + title + '" />');
                }
            });

            var table = $('#schools_table').DataTable({
                orderCellsTop: true,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                columnDefs: [{ orderable: false, targets: [0, 1] }]
            });

            // Bind column search inputs
            $('#schools_table thead tr:eq(1) th input').each(function (i) {
                $(this).on('keyup change clear', function () {
                    table.column(i).search(this.value).draw();
                });
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
    <h1 class="banner-title">Schools</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <!-- ── Add School ── -->
    <div class="card" style="padding: 18px 28px;">
        <form action="admin_edit_school.php" method="POST" style="display:inline;">
            <input type="hidden" name="id" value="">
            <input type="hidden" name="action" value="Add_School">
            <button type="submit" class="btn btn-green">➕ Add New School</button>
        </form>
    </div>

    <!-- ── Schools Table ── -->
    <div class="card">
        <div class="toolbar">
            <div class="toggle-cols">
                Toggle:
                <a class="toggle-vis" data-column="0">Options</a> -
                <a class="toggle-vis" data-column="1">Profile</a> -
                <a class="toggle-vis" data-column="2">Id</a> -
                <a class="toggle-vis" data-column="3">Name</a> -
                <a class="toggle-vis" data-column="4">Type</a> -
                <a class="toggle-vis" data-column="5">Category</a> -
                <a class="toggle-vis" data-column="6">Grade Start</a> -
                <a class="toggle-vis" data-column="7">Grade End</a> -
                <a class="toggle-vis" data-column="8">Enrollment</a> -
                <a class="toggle-vis" data-column="9">Address</a> -
                <a class="toggle-vis" data-column="10">State</a> -
                <a class="toggle-vis" data-column="11">State Code</a> -
                <a class="toggle-vis" data-column="12">Pin Code</a> -
                <a class="toggle-vis" data-column="13">Contact Name</a> -
                <a class="toggle-vis" data-column="14">Designation</a> -
                <a class="toggle-vis" data-column="15">Phone</a> -
                <a class="toggle-vis" data-column="16">Email</a> -
                <a class="toggle-vis" data-column="17">Status</a> -
                <a class="toggle-vis" data-column="18">Notes</a> -
                <a class="toggle-vis" data-column="19">Referenced By</a> -
                <a class="toggle-vis" data-column="20">Supported By</a>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table id="schools_table" class="display compact" style="width:100%">
                <thead>
                    <tr>
                        <th>Options</th>
                        <th>Profile</th>
                        <th>Id</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Grade Level Start</th>
                        <th>Grade Level End</th>
                        <th>Current Enrollment</th>
                        <th>Address Text</th>
                        <th>State Name</th>
                        <th>State Code</th>
                        <th>Pin Code</th>
                        <th>Contact Name</th>
                        <th>Contact Designation</th>
                        <th>Contact Phone</th>
                        <th>Contact Email</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Referenced By</th>
                        <th>Supported By</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($schools)): ?>
                    <?php foreach ($schools as $row):
                        $id            = intval($row['id']);
                        $name          = htmlspecialchars($row['name'] ?? '', ENT_QUOTES);
                        $profile_image = get_profile_image($id);
                        $statusVal     = $row['status'] ?? '';
                        if ($statusVal === 'Proposed') {
                            $statusDisplay = '<span class="badge-proposed">Proposed</span>';
                        } elseif ($statusVal === 'Active' || $statusVal === 'Completed') {
                            $statusDisplay = '<span class="badge-active">' . htmlspecialchars($statusVal) . '</span>';
                        } else {
                            $statusDisplay = htmlspecialchars($statusVal, ENT_QUOTES);
                        }
                    ?>
                    <tr>
                        <td>
                            <div class="action-col">
                                <form action="admin_edit_school.php" method="POST">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button type="submit" name="edit" class="btn btn-edit">✏️ Edit</button>
                                </form>
                                <button type="button" class="btn btn-danger"
                                        onclick="deleteSchool(<?= $id ?>, '<?= htmlspecialchars(addslashes($row['name'] ?? '')) ?>')">
                                    🗑 Delete
                                </button>
                            </div>
                        </td>
                        <td>
                            <img class="school-thumb"
                                 src="<?= htmlspecialchars($profile_image) ?>?v=<?= $time ?>"
                                 alt="School image">
                        </td>
                        <td><?= $id ?></td>
                        <?php
                        // Inline-editable fields (all except id, status, profile)
                        $editable_fields = [
                            'name','type','category','grade_level_start','grade_level_end',
                            'current_enrollment','address_text','state_name','state_code',
                            'pin_code','contact_name','contact_designation','contact_phone',
                            'contact_email'
                        ];
                        foreach ($editable_fields as $key):
                            $val = htmlspecialchars($row[$key] ?? '', ENT_QUOTES);
                        ?>
                        <td>
                            <div contenteditable="true"
                                 onblur="updateValue(this,'<?= $key ?>',<?= $id ?>)">
                                <?= $val ?>
                            </div>
                        </td>
                        <?php endforeach; ?>
                        <td><?= $statusDisplay ?></td>
                        <?php foreach (['notes','referenced_by','supported_by'] as $key):
                            $val = htmlspecialchars($row[$key] ?? '', ENT_QUOTES);
                        ?>
                        <td>
                            <div contenteditable="true"
                                 onblur="updateValue(this,'<?= $key ?>',<?= $id ?>)">
                                <?= $val ?>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="21" style="text-align:center;color:#888;padding:30px;">
                            No schools found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>