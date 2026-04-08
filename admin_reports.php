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

// ── Library stats ─────────────────────────────────────────────
$result          = $db->query("SELECT COUNT(*) AS num_schools, SUM(current_enrollment) AS num_beneficiaries FROM schools");
$total_array     = $result->fetch_assoc();
$num_schools     = (int)($total_array['num_schools']      ?? 0);
$num_beneficiaries = (int)($total_array['num_beneficiaries'] ?? 0);

// ── Total registrations ───────────────────────────────────────
$result           = $db->query("SELECT COUNT(*) AS num_registrations FROM registrations");
$total_array      = $result->fetch_assoc();
$num_registrations = (int)($total_array['num_registrations'] ?? 0);

// ── Total users ───────────────────────────────────────────────
$result    = $db->query("SELECT COUNT(*) AS num_users FROM users");
$num_users = (int)($result->fetch_assoc()['num_users'] ?? 0);

// ── Column-exists helper (uses $db) ───────────────────────────
if (!function_exists('col_exists')) {
    function col_exists($db, $table, $col) {
        $safeTable = preg_replace('/[^A-Za-z0-9_]/', '', $table);
        $safeCol   = preg_replace('/[^A-Za-z0-9_]/', '', $col);
        $sql  = "SELECT COUNT(*) AS col_count
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME   = ?
                   AND COLUMN_NAME  = ?";
        $stmt = $db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('ss', $safeTable, $safeCol);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row && (int)$row['col_count'] > 0;
    }
}

// ── Registration summary by class ─────────────────────────────
$registration_by_class = [];
$total_reg_count       = 0;

$reg_has_offering = col_exists($db, 'registrations', 'Offering_Id');
$reg_has_user     = col_exists($db, 'registrations', 'User_Id');
$off_exists       = col_exists($db, 'offerings',     'Offering_Id');
$off_has_class    = col_exists($db, 'offerings',     'Class_Id');
$cls_exists       = col_exists($db, 'classes',       'Class_Id');
$cls_has_name     = col_exists($db, 'classes',       'Class_Name');
$usr_has_email    = col_exists($db, 'users',         'Email');

if ($reg_has_offering && $off_exists && $off_has_class && $cls_exists && $cls_has_name && $reg_has_user && $usr_has_email) {
    $sql = "SELECT
                COALESCE(c.Class_Name, CONCAT('Unknown Class (Offering ', r.Offering_Id, ')')) AS class_name,
                COUNT(*) AS cnt,
                GROUP_CONCAT(DISTINCT u.Email SEPARATOR '; ') AS emails
            FROM registrations r
            LEFT JOIN offerings o ON r.Offering_Id = o.Offering_Id
            LEFT JOIN classes   c ON o.Class_Id    = c.Class_Id
            LEFT JOIN users     u ON r.User_Id     = u.User_Id
            WHERE u.Email IS NOT NULL AND u.Email != ''
            GROUP BY c.Class_Name, r.Offering_Id
            ORDER BY c.Class_Name";
    if ($r = $db->query($sql)) {
        while ($row = $r->fetch_assoc()) {
            $name = $row['class_name'] !== '' ? $row['class_name'] : 'Unspecified';
            $cnt  = (int)$row['cnt'];
            $emails = $row['emails'] ?? '';
            if (isset($registration_by_class[$name])) {
                $registration_by_class[$name]['count'] += $cnt;
                $merged = array_unique(array_merge(
                    explode('; ', $registration_by_class[$name]['emails']),
                    explode('; ', $emails)
                ));
                $registration_by_class[$name]['emails'] = implode('; ', array_filter($merged));
            } else {
                $registration_by_class[$name] = ['count' => $cnt, 'emails' => $emails];
            }
            $total_reg_count += $cnt;
        }
    }
} elseif (col_exists($db, 'registrations', 'Class_Name') && $reg_has_user && $usr_has_email) {
    $sql = "SELECT r.Class_Name AS class_name, COUNT(*) AS cnt,
                   GROUP_CONCAT(DISTINCT u.Email SEPARATOR '; ') AS emails
            FROM registrations r
            LEFT JOIN users u ON r.User_Id = u.User_Id
            WHERE u.Email IS NOT NULL AND u.Email != ''
            GROUP BY r.Class_Name
            ORDER BY r.Class_Name";
    if ($r = $db->query($sql)) {
        while ($row = $r->fetch_assoc()) {
            $name = $row['class_name'] !== '' ? $row['class_name'] : 'Unspecified';
            $registration_by_class[$name] = ['count' => (int)$row['cnt'], 'emails' => $row['emails'] ?? ''];
            $total_reg_count += (int)$row['cnt'];
        }
    }
}

// ── Payment summary ───────────────────────────────────────────
$payment_summary = [];
$payment_col     = null;
if      (col_exists($db, 'registrations', 'payment_status'))  $payment_col = 'payment_status';
elseif  (col_exists($db, 'registrations', 'Payment_Status'))  $payment_col = 'Payment_Status';

if ($payment_col && $reg_has_user && $usr_has_email) {
    $sql = "SELECT LOWER(TRIM(r.$payment_col)) AS payment_status,
                   COUNT(*) AS cnt,
                   GROUP_CONCAT(DISTINCT u.Email SEPARATOR '; ') AS emails
            FROM registrations r
            LEFT JOIN users u ON r.User_Id = u.User_Id
            WHERE u.Email IS NOT NULL AND u.Email != ''
            GROUP BY LOWER(TRIM(r.$payment_col))
            ORDER BY payment_status";
    if ($r = $db->query($sql)) {
        while ($row = $r->fetch_assoc()) {
            $payment_summary[$row['payment_status'] ?? 'other'] = [
                'count'  => (int)$row['cnt'],
                'emails' => $row['emails'] ?? '',
            ];
        }
    }
}

// ── Unregistered users ────────────────────────────────────────
$unregistered_users = [];
$usr_has_id         = col_exists($db, 'users', 'User_Id');

if ($usr_has_id && $reg_has_user) {
    $fields = ['u.User_Id'];
    foreach (['First_Name','Last_Name','Email','Phone'] as $f) {
        if (col_exists($db, 'users', $f)) $fields[] = "u.$f";
    }
    $select = implode(', ', $fields);
    $sql = "SELECT $select
            FROM users u
            LEFT JOIN registrations r ON u.User_Id = r.User_Id
            WHERE r.User_Id IS NULL
            ORDER BY u.User_Id";
    if ($r = $db->query($sql)) {
        while ($row = $r->fetch_assoc()) {
            $unregistered_users[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Reports – Administration</title>
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
            margin: 0 0 18px 0;
            font-size: 1.25em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Summary stats table ── */
        .regular-table {
            border-collapse: collapse;
            width: 100%;
        }
        .regular-table th,
        .regular-table td {
            border: 1px solid #e8f5c8;
            padding: 10px 14px;
            text-align: center;
        }
        .regular-table th {
            background: #99d930;
            color: #274606;
            font-weight: 900;
            font-size: .88em;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .regular-table tr:nth-child(even) td { background: #f4fce6; }
        .regular-table tr:hover td { background: #edfae5; }

        /* ── Copy email button ── */
        .copy-btn {
            background: #99d930;
            color: #274606;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: .82em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            transition: background .18s;
        }
        .copy-btn:hover { background: #85c220; }
        .copy-btn i { margin-right: 4px; }

        /* ── Toast ── */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #99d930;
            color: #274606;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,.15);
            z-index: 9999;
            opacity: 0;
            transform: translateX(100%);
            transition: all .3s ease;
            font-weight: 700;
        }
        .toast.show { opacity: 1; transform: translateX(0); }
        .toast.error { background: #fff0f0; color: #c00; }

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
        .dt-button:hover {
            background-color: #85c220 !important;
            border-color: #85c220 !important;
        }
        .dt-buttons { margin-bottom: 10px; }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .card { padding: 14px 10px; }
        }
    </style>

    <script>
        function copyEmails(emails, source) {
            if (!emails || emails.trim() === '') {
                showToast('No emails to copy!', 'error');
                return;
            }
            const textarea = document.createElement('textarea');
            textarea.value = emails;
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                showToast('Emails copied from ' + source + '!', 'success');
            } catch (err) {
                showToast('Failed to copy emails', 'error');
            }
            document.body.removeChild(textarea);
        }

        function showToast(message, type) {
            const existing = document.querySelector('.toast');
            if (existing) existing.remove();
            const toast = document.createElement('div');
            toast.className = 'toast' + (type === 'error' ? ' error' : '');
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        $(document).ready(function () {
            $('#registration-table').DataTable({
                pageLength: 25,
                order: [[1, 'desc']],
                dom: 'Blfrtip',
                buttons: [{
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Export to Excel',
                    title: 'Registration Summary - ' + new Date().toLocaleDateString(),
                    filename: function () { return 'registration_summary_' + new Date().toISOString().slice(0, 10); },
                    exportOptions: { columns: [0, 1] }
                }],
                columnDefs: [{ orderable: false, targets: 2 }]
            });

            $('#payment-table').DataTable({
                pageLength: 25,
                order: [[1, 'desc']],
                dom: 'Blfrtip',
                buttons: [{
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Export to Excel',
                    title: 'Payment Summary - ' + new Date().toLocaleDateString(),
                    filename: function () { return 'payment_summary_' + new Date().toISOString().slice(0, 10); },
                    exportOptions: { columns: [0, 1] }
                }],
                columnDefs: [{ orderable: false, targets: 2 }]
            });

            $('#unregistered-table').DataTable({
                pageLength: 25,
                order: [[0, 'desc']],
                dom: 'Blfrtip',
                buttons: [{
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Export to Excel',
                    title: 'Unregistered Users - ' + new Date().toLocaleDateString(),
                    filename: function () { return 'unregistered_users_' + new Date().toISOString().slice(0, 10); }
                }]
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
    <h1 class="banner-title">Reports</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <!-- ── Registration Summary ── -->
    <div class="card">
        <h2>Registration Summary</h2>
        <?php if (!empty($registration_by_class)): ?>
        <table id="registration-table" class="display compact" style="width:100%">
            <thead>
                <tr>
                    <th>Class Name</th>
                    <th>Count</th>
                    <th>Copy Emails</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registration_by_class as $className => $data): ?>
                <tr>
                    <td><?= htmlspecialchars($className) ?></td>
                    <td><?= (int)$data['count'] ?></td>
                    <td style="text-align:center;">
                        <button class="copy-btn"
                                onclick="copyEmails('<?= htmlspecialchars($data['emails'], ENT_QUOTES) ?>',
                                                    '<?= htmlspecialchars($className, ENT_QUOTES) ?>')">
                            <i class="fas fa-copy"></i> Copy Emails
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align:center;color:#888;padding:20px 0;">No class-level registration data found.</p>
        <?php endif; ?>
    </div>

    <!-- ── Payment Summary ── -->
    <div class="card">
        <h2>Payment Summary</h2>
        <?php if (!empty($payment_summary)): ?>
        <table id="payment-table" class="display compact" style="width:100%">
            <thead>
                <tr>
                    <th>Payment Status</th>
                    <th>Count</th>
                    <th>Copy Emails</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payment_summary as $pstatus => $data): ?>
                <tr>
                    <td><?= htmlspecialchars(ucfirst($pstatus)) ?></td>
                    <td><?= (int)$data['count'] ?></td>
                    <td style="text-align:center;">
                        <button class="copy-btn"
                                onclick="copyEmails('<?= htmlspecialchars($data['emails'], ENT_QUOTES) ?>',
                                                    '<?= htmlspecialchars($pstatus, ENT_QUOTES) ?> payments')">
                            <i class="fas fa-copy"></i> Copy Emails
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align:center;color:#888;padding:20px 0;">No payment data found.</p>
        <?php endif; ?>
    </div>

    <!-- ── Unregistered Users ── -->
    <div class="card">
        <h2>Users Not Registered Yet</h2>
        <?php if (!empty($unregistered_users)): ?>
        <table id="unregistered-table" class="display compact" style="width:100%">
            <thead>
                <tr>
                    <th>User ID</th>
                    <?php
                    $sample = $unregistered_users[0];
                    foreach ($sample as $field => $value) {
                        if ($field !== 'User_Id') {
                            echo '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $field))) . '</th>';
                        }
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($unregistered_users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['User_Id']) ?></td>
                    <?php foreach ($user as $field => $value): ?>
                        <?php if ($field !== 'User_Id'): ?>
                            <td><?= htmlspecialchars($value ?? '—') ?></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align:center;color:#888;padding:20px 0;">All users have registrations, or unable to determine unregistered users.</p>
        <?php endif; ?>
    </div>

    <!-- ── Library Information ── -->
    <div class="card">
        <h2>Library Information</h2>
        <table class="regular-table">
            <tr><th>Statistic</th><th>Total</th></tr>
            <tr><td>Schools With Libraries</td><td><?= $num_schools ?></td></tr>
            <tr><td>Student Beneficiaries</td><td><?= $num_beneficiaries ?></td></tr>
            <tr><td>Books Given To Schools</td><td>N/A</td></tr>
            <tr><td>Cost / Support Provided</td><td>N/A</td></tr>
        </table>
    </div>

    <!-- ── Total Users Registered ── -->
    <div class="card">
        <h2>Total Number of Users Registered</h2>
        <table class="regular-table">
            <tr><th>Statistic</th><th>Total</th></tr>
            <tr><td>Users</td><td><?= $num_users ?></td></tr>
        </table>
    </div>

    <!-- ── Student Information ── -->
    <div class="card">
        <h2>Student Information</h2>
        <table class="regular-table">
            <tr><th>Statistic</th><th>Total</th></tr>
            <tr><td>Class Registrations</td><td><?= $num_registrations ?></td></tr>
            <tr><td>Earned Certification</td><td>N/A</td></tr>
            <tr><td>Success Rate</td><td>N/A</td></tr>
        </table>
    </div>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>