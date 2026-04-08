<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Block unauthorized users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Forbidden');
}

require 'db_configuration.php'; // provides $db (mysqli)

// Fetch proposed schools
$result = $db->query("SELECT * FROM schools WHERE status = 'Proposed' ORDER BY id DESC");
$rows   = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Suggested Schools – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

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
            max-width: 1400px;
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

        /* ── Pending badge ── */
        .pending-badge {
            display: inline-block;
            background: #e65100;
            color: #fff;
            border-radius: 20px;
            padding: 2px 14px;
            font-size: .85em;
            font-weight: 700;
            margin-left: 10px;
            vertical-align: middle;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: .88em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .18s, transform .12s;
            white-space: nowrap;
        }
        .btn-green   { background: #99d930; color: #274606; box-shadow: 0 2px 8px rgba(153,217,48,.25); }
        .btn-green:hover   { background: #85c220; transform: translateY(-1px); color: #274606; }
        .btn-edit    { background: #f0fad8; color: #274606; border: 1.5px solid #99d930; }
        .btn-edit:hover    { background: #e2f5b2; color: #274606; }
        .btn-danger  { background: #fff0f0; color: #c00; border: 1.5px solid #f99; }
        .btn-danger:hover  { background: #ffe0e0; }

        /* ── Action buttons column ── */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 140px;
        }
        .action-buttons form { display: block; width: 100%; }
        .action-buttons .btn { width: 100%; box-sizing: border-box; justify-content: center; }

        /* ── Commitment text truncation ── */
        .commitment-text {
            max-width: 260px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }
        .commitment-text:hover {
            white-space: normal;
            overflow: visible;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
            position: relative;
            z-index: 10;
            padding: 4px 6px;
            border-radius: 4px;
        }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            color: #888;
            padding: 50px 20px;
            font-size: 1.05em;
        }
        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            display: block;
            margin-bottom: 16px;
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
</head>
<body>

<?php
include 'show-navbar.php';
show_navbar();
?>

<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Admin banner">
    <h1 class="banner-title">
        Suggested Schools
        <?php if (count($rows) > 0): ?>
            <span class="pending-badge"><?= count($rows) ?> pending</span>
        <?php endif; ?>
    </h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <div class="card">

        <!-- Toolbar -->
        <div class="toolbar">
            <span style="font-size:.97em;color:#274606;font-weight:700;">
                Proposed schools awaiting review
            </span>
            <button id="exportCsv" class="btn btn-green">
                <i class="fas fa-file-excel"></i> Export to CSV
            </button>
        </div>

        <?php if (!empty($rows)): ?>
        <div style="overflow-x:auto;">
            <table id="suggestionsTable" class="display compact" style="width:100%">
                <thead>
                    <tr>
                        <th>Actions</th>
                        <th>School Name</th>
                        <th>Contact Name</th>
                        <th>Contact Mobile</th>
                        <th>Commitment Statement</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <div class="action-buttons">
                                <form action="move_to_schools.php" method="POST"
                                      onsubmit="return confirm('Mark this school as Completed?');">
                                    <input type="hidden" name="school_id"
                                           value="<?= intval($row['id']) ?>">
                                    <button type="submit" class="btn btn-green">
                                        <i class="fas fa-check"></i> Mark Completed
                                    </button>
                                </form>

                                <a href="update_suggestion.php?id=<?= intval($row['id']) ?>"
                                   class="btn btn-edit">
                                    <i class="fas fa-edit"></i> Update
                                </a>

                                <form action="delete_suggestion.php" method="POST"
                                      onsubmit="return confirm('Delete this suggestion? This cannot be undone.');">
                                    <input type="hidden" name="school_id"
                                           value="<?= intval($row['id']) ?>">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($row['name']           ?? '', ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($row['contact_name']   ?? '', ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($row['contact_phone']  ?? '', ENT_QUOTES) ?></td>
                        <td>
                            <div class="commitment-text"
                                 title="<?= htmlspecialchars($row['commitment_statement'] ?? '', ENT_QUOTES) ?>">
                                <?= htmlspecialchars($row['commitment_statement'] ?? '—', ENT_QUOTES) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                No suggested schools found.
            </div>
        <?php endif; ?>

    </div><!-- /card -->

</div><!-- /page-wrap -->

<script>
    $(document).ready(function () {
        var table = $('#suggestionsTable').DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            autoWidth: false,
            order: [],
            columnDefs: [{ orderable: false, targets: 0 }],
            language: {
                info:         "Showing _START_ to _END_ of _TOTAL_ suggestions",
                infoEmpty:    "No suggestions to show",
                infoFiltered: "(filtered from _MAX_ total)"
            }
        });

        // CSV export
        $('#exportCsv').on('click', function () {
            var tableData = [['School Name', 'Contact Name', 'Contact Mobile', 'Commitment Statement']];
            table.rows().every(function () {
                var d = this.data();
                tableData.push([d[1], d[2], d[3], d[4]]);
            });
            var csv = tableData.map(function (row) {
                return row.map(function (cell) {
                    var text = $('<div>').html(cell).text();
                    return (text.includes(',') || text.includes('"'))
                        ? '"' + text.replace(/"/g, '""') + '"'
                        : text;
                }).join(',');
            }).join('\n');
            var link = document.createElement('a');
            link.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv));
            link.setAttribute('download', 'suggested_schools_' + new Date().toISOString().split('T')[0] + '.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });
</script>

<?php include 'footer.php'; ?>
</body>
</html>