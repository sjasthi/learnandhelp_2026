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

// ── Fetch registrations ───────────────────────────────────────
$sql = "SELECT r.Reg_Id, r.Sponsor1_Name, r.Sponsor1_Email, r.Sponsor1_Phone_Number,
               r.Student_Name, r.Student_Email, r.Student_Phone_Number,
               c.Class_Name, r.current_grade, r.payment_status, r.payment_amount
        FROM registrations r
        JOIN classes c ON r.Class_Id = c.Class_Id
        ORDER BY r.Reg_Id DESC";
$result       = $db->query($sql);
$registrations = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Registrations – Administration</title>
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
    <!-- HTML5 export -->
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>

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

        /* ── Card ── */
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 24px 28px;
            margin-bottom: 28px;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 13px;
            border-radius: 7px;
            font-size: .83em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .18s;
            white-space: nowrap;
        }
        .btn-edit   { background: #f0fad8; color: #274606; border: 1.5px solid #99d930; }
        .btn-edit:hover   { background: #e2f5b2; }
        .btn-danger { background: #fff0f0; color: #c00; border: 1.5px solid #f99; }
        .btn-danger:hover { background: #ffe0e0; }

        /* ── Export buttons (DataTables) ── */
        .dt-button {
            background: #99d930 !important;
            color: #274606 !important;
            border: 1px solid #85c220 !important;
            padding: 7px 16px !important;
            border-radius: 7px !important;
            font-weight: 700 !important;
            font-size: .88em !important;
            margin-right: 6px !important;
        }
        .dt-button:hover { background: #85c220 !important; }
        .dt-buttons { margin-bottom: 12px; }

        /* ── Editable cells ── */
        .editable {
            cursor: pointer;
            background: #f8fbe9;
            border: 1px solid transparent;
            padding: 8px;
            transition: background .15s, border-color .15s;
        }
        .editable:hover {
            background: #edfae5;
            border: 1px solid #99d930;
        }
        .cell-edit-input {
            width: 100%;
            border: 2px solid #99d930;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: .9em;
            font-family: 'Roboto', sans-serif;
            background: #f8fbe9;
            outline: none;
        }
        .updated {
            background: #edfae5 !important;
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
        $(document).ready(function () {
            // Clone header for per-column search
            $('#registration_table thead tr').clone(true).appendTo('#registration_table thead');
            $('#registration_table thead tr:eq(1) th').each(function () {
                var title = $(this).text();
                if (title.toLowerCase() === 'options') {
                    $(this).html('');
                } else {
                    $(this).html('<input type="text" placeholder="Search ' + title + '" />');
                }
            });

            var table = $('#registration_table').DataTable({
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                pageLength: 50,
                dom: 'Blfrtip',
                buttons: [
                    {
                        extend: 'csvHtml5',
                        text: '⬇ Export CSV',
                        title: 'Registrations_Export',
                        exportOptions: { columns: [1,2,3,4,5,6,7,8,9,10,11] }
                    },
                    {
                        extend: 'excelHtml5',
                        text: '⬇ Export Excel',
                        title: 'Registrations_Export',
                        exportOptions: { columns: [1,2,3,4,5,6,7,8,9,10,11] }
                    }
                ],
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

            // ── In-cell editing ──────────────────────────────
            $('#registration_table tbody').on('click', 'td.editable', function () {
                var cell          = $(this);
                var originalValue = cell.text().trim();
                var columnIndex   = cell.index();
                var inputElement;

                if (columnIndex === 9) { // Current Grade
                    inputElement = $('<select class="cell-edit-input">');
                    inputElement.append('<option value="">Select Grade</option>');
                    for (var g = 1; g <= 13; g++) {
                        inputElement.append('<option value="' + g + '">' + g + '</option>');
                    }
                    inputElement.val(originalValue);
                } else if (columnIndex === 10) { // Payment Status
                    inputElement = $('<select class="cell-edit-input">' +
                        '<option value="pending">Pending</option>' +
                        '<option value="paid">Paid</option>' +
                        '<option value="free">Free</option>' +
                        '<option value="partial">Partial</option>' +
                        '<option value="void">Void</option>' +
                        '<option value="withdrawn">Withdrawn</option>' +
                        '</select>');
                    inputElement.val(originalValue);
                } else { // Payment Amount
                    inputElement = $('<input type="number" step="0.01" class="cell-edit-input">');
                    inputElement.val(originalValue);
                }

                cell.html(inputElement);
                inputElement.focus();

                inputElement.on('blur keypress', function (e) {
                    if (e.type === 'blur' || e.which === 13) {
                        var newValue  = $(this).val();
                        var regId     = cell.closest('tr').find('td').eq(1).text().trim();
                        var colName   = columnIndex === 9  ? 'current_grade'
                                      : columnIndex === 10 ? 'payment_status'
                                      : 'payment_amount';

                        cell.html(newValue);

                        $.ajax({
                            url: 'admin_registrations_in_cell_update.php',
                            method: 'POST',
                            data: { reg_id: regId, column: colName, value: newValue },
                            success: function () {
                                cell.addClass('updated');
                                setTimeout(function () { cell.removeClass('updated'); }, 2000);
                            },
                            error: function () {
                                cell.html(originalValue);
                                alert('Failed to update. Please try again.');
                            }
                        });
                    }
                });
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
    <h1 class="banner-title">Registrations</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <div class="card">
        <table id="registration_table" class="display compact" style="width:100%">
            <thead>
                <tr>
                    <th>Options</th>
                    <th>Reg ID</th>
                    <th>Sponsor1 Name</th>
                    <th>Sponsor1 Email</th>
                    <th>Sponsor1 Phone</th>
                    <th>Student Name</th>
                    <th>Student Email</th>
                    <th>Student Phone</th>
                    <th>Class</th>
                    <th>Current Grade</th>
                    <th>Payment Status</th>
                    <th>Payment Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($registrations)): ?>
                <?php foreach ($registrations as $row):
                    $regId    = intval($row['Reg_Id']);
                    $sp1Name  = htmlspecialchars($row['Sponsor1_Name']         ?? '', ENT_QUOTES);
                    $sp1Email = htmlspecialchars($row['Sponsor1_Email']        ?? '', ENT_QUOTES);
                    $sp1Phone = htmlspecialchars($row['Sponsor1_Phone_Number'] ?? '', ENT_QUOTES);
                    $stName   = htmlspecialchars($row['Student_Name']          ?? '', ENT_QUOTES);
                    $stEmail  = htmlspecialchars($row['Student_Email']         ?? '', ENT_QUOTES);
                    $stPhone  = htmlspecialchars($row['Student_Phone_Number']  ?? '', ENT_QUOTES);
                    $class    = htmlspecialchars($row['Class_Name']            ?? '', ENT_QUOTES);
                    $grade    = htmlspecialchars($row['current_grade']         ?? '', ENT_QUOTES);
                    $payStatus= htmlspecialchars($row['payment_status']        ?? '', ENT_QUOTES);
                    $payAmt   = htmlspecialchars($row['payment_amount']        ?? '', ENT_QUOTES);
                ?>
                <tr>
                    <td style="white-space:nowrap;">
                        <a href="admin_registrations_edit.php?Reg_Id=<?= $regId ?>"
                           class="btn btn-edit">✏️ Edit</a>
                        <form action="admin_registrations_delete.php" method="POST"
                              style="display:inline;"
                              onsubmit="return confirm('Delete registration #<?= $regId ?>? This cannot be undone.');">
                            <input type="hidden" name="Reg_Id" value="<?= $regId ?>">
                            <button type="submit" name="delete" class="btn btn-danger">🗑 Delete</button>
                        </form>
                    </td>
                    <td><?= $regId ?></td>
                    <td><?= $sp1Name ?></td>
                    <td><?= $sp1Email ?></td>
                    <td><?= $sp1Phone ?></td>
                    <td><?= $stName ?></td>
                    <td><?= $stEmail ?></td>
                    <td><?= $stPhone ?></td>
                    <td><?= $class ?></td>
                    <td class="editable"><?= $grade ?></td>
                    <td class="editable"><?= $payStatus ?></td>
                    <td class="editable"><?= $payAmt ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="12" style="text-align:center;color:#888;padding:30px;">
                        No registrations found.
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