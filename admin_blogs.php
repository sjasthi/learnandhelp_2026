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
    <title>Blogs – Administration</title>
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

        /* ── Description preview ── */
        .desc-preview {
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #555;
            font-size: .9em;
        }

        /* ── DataTables overrides ── */
        .dataTables_wrapper .dataTables_length   { float: left; }
        .dataTables_wrapper .dataTables_filter   { float: right; clear: none; }
        .dataTables_wrapper .dataTables_info     { clear: both; float: left; padding-top: 8px; }
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
        function deleteBlog(blogId, title) {
            if (confirm('Delete "' + title + '"? This cannot be undone.')) {
                window.location.href = 'admin_delete_blog.php?id=' + blogId;
            }
        }

        $(document).ready(function () {
            // Clone header for per-column search inputs
            $('#Blog_table thead tr').clone(true).appendTo('#Blog_table thead');
            $('#Blog_table thead tr:eq(1) th').each(function () {
                var title = $(this).text();
                if (title.toLowerCase() === 'actions') {
                    $(this).html('');
                } else {
                    $(this).html('<input type="text" placeholder="Search ' + title + '" />');
                }
            });

            var table = $('#Blog_table').DataTable({
                order: [[6, 'desc']], // Sort by Created Time descending
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
    <h1 class="banner-title">Blogs</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <div class="card">

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="toggle-cols">
                Toggle column:
                <a class="toggle-vis" data-column="0">Blog ID</a> -
                <a class="toggle-vis" data-column="1">Title</a> -
                <a class="toggle-vis" data-column="2">Author</a> -
                <a class="toggle-vis" data-column="3">Description</a> -
                <a class="toggle-vis" data-column="4">Video Link</a> -
                <a class="toggle-vis" data-column="5">Modified Time</a> -
                <a class="toggle-vis" data-column="6">Created Time</a>
            </div>
            <a href="admin_create_blog.php" class="btn btn-green">➕ Create Blog</a>
        </div>

        <!-- Blogs Table -->
        <table id="Blog_table" class="display compact" style="width:100%">
            <thead>
                <tr>
                    <th>Blog ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Description</th>
                    <th>Video Link</th>
                    <th>Modified Time</th>
                    <th>Created Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $result = $db->query("SELECT * FROM blogs ORDER BY Created_Time DESC");

            if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                    $blogId      = intval($row['Blog_Id']);
                    $title       = htmlspecialchars($row['Title']         ?? '', ENT_QUOTES);
                    $author      = htmlspecialchars($row['Author']        ?? '', ENT_QUOTES);
                    $description = htmlspecialchars($row['Description']   ?? '', ENT_QUOTES);
                    $videoLink   = htmlspecialchars($row['Video_Link']    ?? '', ENT_QUOTES);
                    $modTime     = htmlspecialchars($row['Modified_Time'] ?? '', ENT_QUOTES);
                    $createdTime = htmlspecialchars($row['Created_Time']  ?? '', ENT_QUOTES);

                    // Truncate description for table display
                    $descPreview = strlen($description) > 100
                        ? substr($description, 0, 97) . '...'
                        : $description;

                    // Video link as clickable icon
                    $videoDisplay = $videoLink
                        ? "<a href='{$videoLink}' target='_blank' rel='noopener noreferrer'>▶ Watch</a>"
                        : '<span style="color:#bbb;">—</span>';
            ?>
                <tr>
                    <td><?= $blogId ?></td>
                    <td><strong><?= $title ?></strong></td>
                    <td><?= $author ?></td>
                    <td>
                        <div class="desc-preview" title="<?= $description ?>">
                            <?= $descPreview ?>
                        </div>
                    </td>
                    <td><?= $videoDisplay ?></td>
                    <td style="white-space:nowrap;"><?= $modTime ?></td>
                    <td style="white-space:nowrap;"><?= $createdTime ?></td>
                    <td style="white-space:nowrap;">
                        <a href="admin_edit_blog.php?id=<?= $blogId ?>"
                           class="btn btn-edit">✏️ Edit</a>
                        <button type="button" class="btn btn-danger"
                                onclick="deleteBlog(<?= $blogId ?>, '<?= htmlspecialchars(addslashes($row['Title'] ?? '')) ?>')">
                            🗑 Delete
                        </button>
                    </td>
                </tr>
            <?php
                endwhile;
            else:
            ?>
                <tr>
                    <td colspan="8" style="text-align:center;color:#888;padding:30px;">
                        No blogs found.
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