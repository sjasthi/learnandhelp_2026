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
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Partners – Administration</title>
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

        /* ── Overflow-safe cells ── */
        .table-wrap { overflow-x: auto; }
        .cell-wrap {
            max-width: 180px;
            word-break: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }

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
        .toggle-cols {
            font-size: .88em;
            color: #555;
        }
        .toggle-cols a {
            color: #274606;
            font-weight: 700;
            text-decoration: none;
            margin: 0 3px;
        }
        .toggle-cols a:hover { color: #99d930; }

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
        .btn-green {
            background: #99d930;
            color: #274606;
            box-shadow: 0 2px 8px rgba(153,217,48,.25);
        }
        .btn-green:hover { background: #85c220; transform: translateY(-1px); }

        .btn-edit {
            background: #f0fad8;
            color: #274606;
            border: 1.5px solid #99d930;
            padding: 6px 14px;
            font-size: .85em;
        }
        .btn-edit:hover { background: #e2f5b2; }

        .btn-danger {
            background: #fff0f0;
            color: #c00;
            border: 1.5px solid #f99;
            padding: 6px 14px;
            font-size: .85em;
        }
        .btn-danger:hover { background: #ffe0e0; }

        /* ── Logo thumbnail ── */
        .thumb {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border-radius: 6px;
            border: 1px solid #cde8a0;
        }

        /* ── DataTables overrides ── */
        .dataTables_wrapper .dataTables_length { float: left; }
        .dataTables_wrapper .dataTables_filter { float: right; clear: none; }
        .dataTables_wrapper .dataTables_info   { clear: both; float: left; padding-top: 8px; }
        .dataTables_wrapper .dataTables_paginate { float: right; padding-top: 8px; }
        table.dataTable thead input {
            width: 100%;
            box-sizing: border-box;
            padding: 3px 6px;
        }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .card { padding: 14px 10px; }
        }
    </style>

    <script>
        function deletePartner(partnerId) {
            if (confirm("Are you sure you want to delete this partner? This cannot be undone.")) {
                window.location.href = 'admin_deletepartner.php?id=' + partnerId;
            }
        }

        $(document).ready(function () {
            // Clone header for per-column search inputs
            $('#PartnersTable thead tr').clone(true).appendTo('#PartnersTable thead');
            $('#PartnersTable thead tr:eq(1) th').each(function () {
                var title = $(this).text();
                if (title.toLowerCase() === 'actions') {
                    $(this).html('');
                } else {
                    $(this).html('<input type="text" placeholder="Search ' + title + '" />');
                }
            });

            var table = $('#PartnersTable').DataTable({
                orderCellsTop: true,
                fixedHeader: true,
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
    <h1 class="banner-title">Partners</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <div class="card">

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="toggle-cols">
                Toggle column:
                <a class="toggle-vis" data-column="0">Partner ID</a> -
                <a class="toggle-vis" data-column="1">Name</a> -
                <a class="toggle-vis" data-column="2">Type</a> -
                <a class="toggle-vis" data-column="3">Logo</a> -
                <a class="toggle-vis" data-column="4">Website</a> -
                <a class="toggle-vis" data-column="5">Impact</a> -
                <a class="toggle-vis" data-column="6">Created</a> -
                <a class="toggle-vis" data-column="7">Updated</a>
            </div>
            <a href="admin_createpartner.php" class="btn btn-green">➕ Create Partner</a>
        </div>

        <!-- Partners Table -->
        <div class="table-wrap">
        <table id="PartnersTable" class="display compact" style="width:100%">
            <thead>
                <tr>
                    <th>Partner ID</th>
                    <th>Partner Name</th>
                    <th>Partner Type</th>
                    <th>Logo</th>
                    <th>Website URL</th>
                    <th>Impact Description</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sql    = "SELECT partner_id, partner_name, partner_type, logo_image, website_url,
                              impact_description, created_at, updated_at
                       FROM community_partners
                       ORDER BY created_at DESC";
            $result = $db->query($sql);

            if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                    $partnerId = intval($row['partner_id']);
                    $name      = htmlspecialchars($row['partner_name']        ?? '', ENT_QUOTES);
                    $type      = htmlspecialchars($row['partner_type']        ?? '', ENT_QUOTES);
                    $logoFile  = htmlspecialchars($row['logo_image']          ?? '', ENT_QUOTES);
                    $website   = htmlspecialchars($row['website_url']         ?? '', ENT_QUOTES);
                    $impact    = htmlspecialchars($row['impact_description']  ?? '', ENT_QUOTES);
                    $created   = htmlspecialchars($row['created_at']          ?? '', ENT_QUOTES);
                    $updated   = htmlspecialchars($row['updated_at']          ?? '', ENT_QUOTES);

                    // Resolve logo path
                    $logoSrc = '';
                    foreach (["images/community_partners/$logoFile", "images/$logoFile", $logoFile] as $path) {
                        if ($logoFile && file_exists($path)) {
                            $logoSrc = $path;
                            break;
                        }
                    }
                    if (!$logoSrc) {
                        $logoSrc = 'images/community_partners/default_logo.png';
                    }
            ?>
                <tr>
                    <td><?= $partnerId ?></td>
                    <td><?= $name ?></td>
                    <td><?= $type ?></td>
                    <td>
                        <img class="thumb" src="<?= htmlspecialchars($logoSrc) ?>"
                             alt="<?= $name ?> logo">
                    </td>
                    <td class="cell-wrap">
                        <?php if ($website): ?>
                            <a href="<?= $website ?>" target="_blank" rel="noopener noreferrer">
                                <?= $website ?>
                            </a>
                        <?php else: ?>
                            <span style="color:#bbb;">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="cell-wrap"><?= $impact ?: '<span style="color:#bbb;">—</span>' ?></td>
                    <td style="white-space:nowrap;"><?= $created ?></td>
                    <td style="white-space:nowrap;"><?= $updated ?></td>
                    <td style="white-space:nowrap;">
                        <a href="admin_updatepartner.php?id=<?= $partnerId ?>"
                           class="btn btn-edit">✏️ Edit</a>
                        <button type="button" class="btn btn-danger"
                                onclick="deletePartner(<?= $partnerId ?>)">🗑 Delete</button>
                    </td>
                </tr>
            <?php
                endwhile;
            else:
            ?>
                <tr>
                    <td colspan="9" style="text-align:center;color:#888;padding:30px;">
                        No partners found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div><!-- /table-wrap -->

    </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>