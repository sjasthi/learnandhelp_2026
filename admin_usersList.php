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
    <title>Users List – Administration</title>
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
    <!-- JSZip for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <!-- Buttons HTML5 export -->
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

        /* ── Toolbar row ── */
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

        /* ── Create user button ── */
        .btn-create {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 22px;
            border-radius: 8px;
            font-size: .97em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            background: #99d930;
            color: #274606;
            border: none;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(153,217,48,.25);
            transition: background .18s, transform .12s;
        }
        .btn-create:hover { background: #85c220; transform: translateY(-1px); }

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
        .btn-update  { background: #007bff; color: #fff; }
        .btn-update:hover  { background: #0056b3; transform: scale(1.1); }
        .btn-password { background: #28a745; color: #fff; }
        .btn-password:hover { background: #1e7e34; transform: scale(1.1); }
        .btn-register { background: #ff6b35; color: #fff; }
        .btn-register:hover { background: #e55a2b; transform: scale(1.1); }
        .btn-delete  { background: #dc3545; color: #fff; }
        .btn-delete:hover  { background: #c82333; transform: scale(1.1); }

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

        /* ── DataTables overrides ── */
        .dt-buttons { margin-bottom: 10px; }
        .dt-button {
            background-color: #99d930 !important;
            color: #274606 !important;
            border: 1px solid #85c220 !important;
            padding: 8px 16px !important;
            border-radius: 6px !important;
            font-weight: 700 !important;
            font-size: .9em !important;
        }
        .dt-button:hover {
            background-color: #85c220 !important;
            border-color: #85c220 !important;
        }
        .dataTables_wrapper .dataTables_length { float: left; }
        .dataTables_wrapper .dataTables_filter { float: right; clear: none; }
        .dataTables_wrapper .dataTables_info   { clear: both; float: left; padding-top: 8px; }
        .dataTables_wrapper .dataTables_paginate { float: right; padding-top: 8px; }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .card { padding: 14px 10px; }
        }
    </style>

    <script>
        function deleteUser(userId) {
            if (confirm("Are you sure you want to delete this user? This cannot be undone.")) {
                window.location.href = 'admin_deleteuser.php?id=' + userId;
            }
        }
        function changePassword(userId) {
            window.location.href = 'admin_change_user_password.php?id=' + userId;
        }
        function registerStudent(userId) {
            window.location.href = 'admin_register_student_form.php?id=' + userId;
        }

        $(document).ready(function () {
            // Clone header row for per-column search inputs
            $('#Blog_table thead tr').clone(true).appendTo('#Blog_table thead');
            $('#Blog_table thead tr:eq(1) th').each(function () {
                var title = $(this).text();
                if (title !== 'Actions') {
                    $(this).html('<input type="text" placeholder="Search ' + title + '" style="width:100%;box-sizing:border-box;padding:3px;">');
                } else {
                    $(this).html('');
                }
            });

            var table = $('#Blog_table').DataTable({
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                pageLength: 50,
                order: [[0, "desc"]],
                dom: 'Blfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Export to Excel',
                        title: 'Users List - ' + new Date().toLocaleDateString(),
                        filename: function () {
                            return 'users_list_' + new Date().toISOString().slice(0, 10);
                        },
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
                    }
                ],
                columnDefs: [
                    { orderable: false, targets: 9 }
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
    <h1 class="banner-title">Users List</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <?php if (!empty($_SESSION['flash'])): ?>
        <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="flash <?= $flash['type'] === 'success' ? 'flash-success' : 'flash-error' ?>">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- Handle msg from redirect (e.g. after update) -->
    <?php if (!empty($_GET['msg'])): ?>
        <div class="flash <?= ($_GET['type'] ?? '') === 'success' ? 'flash-success' : 'flash-error' ?>">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <!-- Toolbar -->
        <div class="toolbar">
            <div class="toggle-cols">
                Toggle column:
                <a class="toggle-vis" data-column="0">User ID</a> -
                <a class="toggle-vis" data-column="1">First Name</a> -
                <a class="toggle-vis" data-column="2">Last Name</a> -
                <a class="toggle-vis" data-column="3">Email</a> -
                <a class="toggle-vis" data-column="4">Phone</a> -
                <a class="toggle-vis" data-column="5">Active</a> -
                <a class="toggle-vis" data-column="6">Role</a> -
                <a class="toggle-vis" data-column="7">Created Date</a> -
                <a class="toggle-vis" data-column="8">Last Modified</a>
            </div>
            <a href="admin_createuser.php" class="btn-create">➕ Create User</a>
        </div>

        <!-- Users Table -->
        <table id="Blog_table" class="display compact" style="width:100%">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Active</th>
                    <th>Role</th>
                    <th>Created Date</th>
                    <th>Last Modified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql    = "SELECT * FROM users ORDER BY User_Id DESC";
                $result = $db->query($sql);

                if ($result && $result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        $id        = intval($row['User_Id']);
                        $firstName = htmlspecialchars($row['First_Name']    ?? '', ENT_QUOTES);
                        $lastName  = htmlspecialchars($row['Last_Name']     ?? '', ENT_QUOTES);
                        $email     = htmlspecialchars($row['Email']         ?? '', ENT_QUOTES);
                        $phone     = htmlspecialchars($row['Phone']         ?? '', ENT_QUOTES);
                        $active    = htmlspecialchars($row['Active']        ?? '', ENT_QUOTES);
                        $role      = htmlspecialchars($row['Role']          ?? '', ENT_QUOTES);
                        $created   = htmlspecialchars($row['Created_Time']  ?? '', ENT_QUOTES);
                        $modified  = htmlspecialchars($row['Modified_Time'] ?? '', ENT_QUOTES);
                ?>
                <tr>
                    <td><?= $id ?></td>
                    <td><?= $firstName ?></td>
                    <td><?= $lastName ?></td>
                    <td><?= $email ?></td>
                    <td><?= $phone ?></td>
                    <td><?= $active ?></td>
                    <td><?= $role ?></td>
                    <td><?= $created ?></td>
                    <td><?= $modified ?></td>
                    <td style="text-align:center;white-space:nowrap;">
                        <button class="action-btn btn-update" title="Update User"
                                onclick="location.href='admin_updateuser.php?id=<?= $id ?>'">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn btn-password" title="Change Password"
                                onclick="changePassword(<?= $id ?>)">
                            <i class="fas fa-key"></i>
                        </button>
                        <button class="action-btn btn-register" title="Register Student"
                                onclick="registerStudent(<?= $id ?>)">
                            <i class="fas fa-user-plus"></i>
                        </button>
                        <button class="action-btn btn-delete" title="Delete User"
                                onclick="deleteUser(<?= $id ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php
                    endwhile;
                else:
                    echo '<tr><td colspan="10" style="text-align:center;color:#888;padding:30px;">No users found.</td></tr>';
                endif;
                ?>
            </tbody>
        </table>
    </div>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>