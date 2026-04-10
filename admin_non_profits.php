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

// ── AJAX inline-edit handler ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_field') {
    $org_id = intval($_POST['org_id']);
    $field  = $_POST['field']  ?? '';
    $value  = $_POST['value']  ?? '';

    $allowed_fields = ['org_name','cause_category','description','website_url','org_email','status','address','notes'];

    if (!in_array($field, $allowed_fields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid field']);
        exit;
    }

    $sql  = "UPDATE recommended_orgs SET `$field` = ?, updated_at = NOW() WHERE org_id = ?";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
        exit;
    }
    $stmt->bind_param('si', $value, $org_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Field updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update field']);
    }
    $stmt->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Non-Profits – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- DataTables Buttons CSS -->
    <link href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" rel="stylesheet">

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <!-- DataTables Buttons JS -->
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <!-- JSZip -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <!-- Buttons HTML5 -->
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

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
            max-width: 1600px;
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
        .flash-success { background: #edfae5; color: #2a6006; border: 1.5px solid #99d930; }
        .flash-error   { background: #fff0f0; color: #a00;    border: 1.5px solid #f88; }

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

        /* ── Inline edit hint ── */
        .edit-hint {
            font-size: .82em;
            color: #888;
            font-style: italic;
        }

        /* ── Action buttons ── */
        .action-btn {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            margin: 2px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            transition: all 0.2s ease;
            position: relative;
        }
        .btn-update { background: #99d930; color: #274606; }
        .btn-update:hover { background: #85c220; transform: scale(1.1); }
        .btn-delete { background: #fff0f0; color: #c00; border: 1.5px solid #f99; }
        .btn-delete:hover { background: #ffe0e0; transform: scale(1.1); }

        /* Tooltips */
        .action-btn::after {
            content: attr(title);
            position: absolute;
            bottom: -26px;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: #fff;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            z-index: 1000;
        }
        .action-btn:hover::after { opacity: 1; }

        /* ── Status badges ── */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .8em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .status-pending     { background: #fff8e1; color: #f57f17; }
        .status-approved    { background: #edfae5; color: #2a6006; }
        .status-rejected    { background: #fff0f0; color: #c00; }
        .status-researching { background: #e3f2fd; color: #1565c0; }

        /* ── Inline editing ── */
        .editable {
            cursor: pointer;
            position: relative;
            padding: 6px;
            border-radius: 4px;
            transition: background .2s;
        }
        .editable:hover   { background: #f4fce6; }
        .editable.editing { background: #edfae5; }

        .edit-input, .edit-select, .edit-textarea {
            width: 100%;
            padding: 4px 8px;
            border: 2px solid #99d930;
            border-radius: 6px;
            font-size: inherit;
            font-family: inherit;
            background: #fff;
            outline: none;
            box-sizing: border-box;
        }
        .edit-textarea { min-height: 60px; resize: vertical; }

        .saving { opacity: .6; pointer-events: none; }
        .save-indicator  { position: absolute; top: 2px; right: 2px; color: #2a6006; font-size: 11px; }
        .error-indicator { position: absolute; top: 2px; right: 2px; color: #c00;    font-size: 11px; }

        /* ── Description preview ── */
        .description-cell {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* ── Overflow-safe cells ── */
        .cell-wrap {
            max-width: 160px;
            word-break: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }

        /* ── DataTables overrides ── */
        .dt-button {
            background-color: #99d930 !important;
            color: #274606 !important;
            border: 1px solid #85c220 !important;
            padding: 8px 16px !important;
            border-radius: 6px !important;
            font-weight: 700 !important;
            font-size: .9em !important;
        }
        .dt-button:hover { background-color: #85c220 !important; }
        .dt-buttons { margin-bottom: 10px; }
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
        function deleteNonProfit(orgId) {
            if (confirm("Are you sure you want to delete this non-profit recommendation? This cannot be undone.")) {
                window.location.href = 'admin_delete_nonprofit.php?id=' + orgId;
            }
        }

        function makeEditable() {
            $('.editable').off('click').on('click', function () {
                if ($(this).hasClass('editing')) return;
                var $cell        = $(this);
                var orgId        = $cell.closest('tr').find('.org-id').text();
                var field        = $cell.data('field');
                var currentValue = $cell.data('original-value') || $cell.text().trim();
                var cellType     = $cell.data('type') || 'text';

                $cell.data('original-value', currentValue).addClass('editing');

                var inputEl;
                if (cellType === 'select' && field === 'status') {
                    inputEl = $('<select class="edit-select">' +
                        '<option value="pending">Pending</option>' +
                        '<option value="approved">Approved</option>' +
                        '<option value="rejected">Rejected</option>' +
                        '<option value="researching">Researching</option>' +
                        '</select>');
                    inputEl.val(currentValue.toLowerCase());
                } else if (cellType === 'textarea') {
                    inputEl = $('<textarea class="edit-textarea"></textarea>').val(currentValue);
                } else {
                    inputEl = $('<input type="text" class="edit-input">').val(currentValue);
                }

                $cell.html(inputEl);
                inputEl.focus();

                inputEl.on('blur keypress', function (e) {
                    if (e.type === 'keypress' && e.which !== 13) return;
                    if (e.type === 'keypress' && e.shiftKey) return;
                    saveField(orgId, field, $(this).val(), $cell);
                });

                inputEl.on('keydown', function (e) {
                    if (e.which === 27) cancelEdit($cell, currentValue);
                });
            });
        }

        function saveField(orgId, field, newValue, $cell) {
            if ($cell.hasClass('saving')) return;
            $cell.addClass('saving').append('<i class="fas fa-spinner fa-spin save-indicator"></i>');

            $.ajax({
                url: '', method: 'POST',
                data: { action: 'update_field', org_id: orgId, field: field, value: newValue },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        if (field === 'status') {
                            var cls = 'status-' + newValue.toLowerCase();
                            $cell.html("<span class='status-badge " + cls + "'>" + newValue + "</span>");
                        } else if ($cell.hasClass('description-cell')) {
                            var short = newValue.length > 100 ? newValue.substring(0, 100) + '...' : newValue;
                            $cell.html(short).attr('title', newValue);
                        } else {
                            $cell.html(newValue);
                        }
                        $cell.data('original-value', newValue)
                             .append('<i class="fas fa-check save-indicator"></i>');
                        setTimeout(function () { $cell.find('.save-indicator').remove(); }, 2000);
                    } else {
                        $cell.append('<i class="fas fa-times error-indicator"></i>');
                        setTimeout(function () { $cell.find('.error-indicator').remove(); }, 3000);
                    }
                },
                error: function () {
                    $cell.append('<i class="fas fa-times error-indicator"></i>');
                    setTimeout(function () { $cell.find('.error-indicator').remove(); }, 3000);
                },
                complete: function () { $cell.removeClass('saving editing'); }
            });
        }

        function cancelEdit($cell, originalValue) {
            $cell.removeClass('editing');
            if ($cell.data('field') === 'status') {
                var cls = 'status-' + originalValue.toLowerCase();
                $cell.html("<span class='status-badge " + cls + "'>" + originalValue + "</span>");
            } else {
                $cell.html(originalValue);
            }
        }

        $(document).ready(function () {
            $('#Blog_table thead tr').clone(true).appendTo('#Blog_table thead');
            $('#Blog_table thead tr:eq(1) th').each(function () {
                var title = $(this).text();
                $(this).html(title !== 'Actions'
                    ? '<input type="text" placeholder="Search ' + title + '">'
                    : '');
            });

            var table = $('#Blog_table').DataTable({
                dom: 'Blfrtip',
                buttons: [{
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Export to Excel',
                    filename: 'recommended_nonprofits_' + new Date().toISOString().split('T')[0]
                }],
                initComplete: function () {
                    this.api().columns().every(function () {
                        var that = this;
                        $('input', this.header()).on('keyup change clear', function () {
                            if (that.search() !== this.value) that.search(this.value).draw();
                        });
                    });
                    makeEditable();
                }
            });

            $('a.toggle-vis').on('click', function (e) {
                e.preventDefault();
                table.column($(this).attr('data-column')).visible(function (vis) { return !vis; });
            });

            table.on('draw', function () { makeEditable(); });
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
    <h1 class="banner-title">Non-Profits</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <?php if (!empty($_SESSION['flash'])): ?>
        <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="flash <?= $flash['type'] === 'success' ? 'flash-success' : 'flash-error' ?>">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="card">

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="toggle-cols">
                Toggle column:
                <a class="toggle-vis" data-column="1">Org ID</a> -
                <a class="toggle-vis" data-column="2">Suggester ID</a> -
                <a class="toggle-vis" data-column="3">Organization Name</a> -
                <a class="toggle-vis" data-column="4">Category</a> -
                <a class="toggle-vis" data-column="5">Description</a> -
                <a class="toggle-vis" data-column="6">Website</a> -
                <a class="toggle-vis" data-column="7">Email</a> -
                <a class="toggle-vis" data-column="8">Status</a> -
                <a class="toggle-vis" data-column="9">Address</a> -
                <a class="toggle-vis" data-column="10">Created</a> -
                <a class="toggle-vis" data-column="11">Updated</a> -
                <a class="toggle-vis" data-column="12">Notes</a>
            </div>
            <span class="edit-hint">
                <i class="fas fa-info-circle"></i> Click any cell to edit inline. Enter to save, Esc to cancel.
            </span>
        </div>

        <!-- Non-Profits Table -->
        <table id="Blog_table" class="display compact" style="width:100%">
            <thead>
                <tr>
                    <th>Actions</th>
                    <th>Org ID</th>
                    <th>Suggester ID</th>
                    <th>Organization Name</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Website</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Address</th>
                    <th>Created Date</th>
                    <th>Updated Date</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $result = $db->query("SELECT * FROM recommended_orgs ORDER BY org_id DESC");

            if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                    $orgId       = intval($row['org_id']);
                    $statusRaw   = strtolower($row['status'] ?? 'pending');
                    $statusLabel = ucfirst($statusRaw);
                    $statusBadge = "<span class='status-badge status-{$statusRaw}'>{$statusLabel}</span>";

                    $description      = htmlspecialchars($row['description'] ?? '', ENT_QUOTES);
                    $shortDescription = strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;

                    $notes      = htmlspecialchars($row['notes'] ?? '', ENT_QUOTES);
                    $shortNotes = strlen($notes) > 50 ? substr($notes, 0, 50) . '...' : $notes;

                    $website    = htmlspecialchars($row['website_url'] ?? '', ENT_QUOTES);
                    $websiteLink = $website
                        ? "<a href='{$website}' target='_blank' rel='noopener noreferrer'><i class='fas fa-external-link-alt'></i></a>"
                        : '—';
            ?>
                <tr>
                    <td style="text-align:center;white-space:nowrap;">
                        <button class="action-btn btn-update" title="Edit Non-Profit"
                                onclick="location.href='admin_update_nonprofit.php?id=<?= $orgId ?>'">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn btn-delete" title="Delete Non-Profit"
                                onclick="deleteNonProfit(<?= $orgId ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                    <td class="org-id"><?= $orgId ?></td>
                    <td><?= htmlspecialchars($row['suggester_id'] ?? '', ENT_QUOTES) ?></td>
                    <td class="editable cell-wrap" data-field="org_name"><?= htmlspecialchars($row['org_name'] ?? '', ENT_QUOTES) ?></td>
                    <td class="editable cell-wrap" data-field="cause_category"><?= htmlspecialchars($row['cause_category'] ?? '', ENT_QUOTES) ?></td>
                    <td class="editable description-cell" data-field="description" data-type="textarea"
                        title="<?= $description ?>"><?= $shortDescription ?></td>
                    <td class="editable cell-wrap" data-field="website_url"><?= $website ?></td>
                    <td class="editable cell-wrap" data-field="org_email"><?= htmlspecialchars($row['org_email'] ?? '', ENT_QUOTES) ?></td>
                    <td class="editable" data-field="status" data-type="select"><?= $statusBadge ?></td>
                    <td class="editable cell-wrap" data-field="address" data-type="textarea"><?= htmlspecialchars($row['address'] ?? '', ENT_QUOTES) ?></td>
                    <td style="white-space:nowrap;"><?= htmlspecialchars($row['created_at'] ?? '', ENT_QUOTES) ?></td>
                    <td style="white-space:nowrap;"><?= htmlspecialchars($row['updated_at'] ?? '', ENT_QUOTES) ?></td>
                    <td class="editable description-cell" data-field="notes" data-type="textarea"
                        title="<?= $notes ?>"><?= $shortNotes ?></td>
                </tr>
            <?php
                endwhile;
            else:
            ?>
                <tr>
                    <td colspan="13" style="text-align:center;color:#888;padding:30px;">
                        No non-profit recommendations found.
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