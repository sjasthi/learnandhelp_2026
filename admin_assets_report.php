<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); die('Forbidden');
}

require_once 'db_configuration.php';

$today = date('Y-m-d');

// ── Fetch all assets ──────────────────────────────────────────
$assets = $db->query("SELECT * FROM assets ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// ── Fetch audit log (last 25) ─────────────────────────────────
$audit_logs = [];
$log_res = $db->query("SELECT * FROM asset_logs ORDER BY created_at DESC LIMIT 25");
if ($log_res) $audit_logs = $log_res->fetch_all(MYSQLI_ASSOC);

// ── Summary totals ────────────────────────────────────────────
$total_count      = count($assets);
$total_value      = 0;
$active_count     = 0;
$warranty_expired = 0;
$warranty_expiring = 0; // within 90 days

$by_category  = [];
$by_condition = [];
$by_status    = [];
$by_type      = ['Physical' => 0, 'Digital' => 0];
$by_assigned  = [];
$by_warranty  = ['Expired' => 0, 'Expiring Soon' => 0, 'OK' => 0, 'No Warranty' => 0];

foreach ($assets as $a) {
    $price = (float)($a['purchase_price'] ?? 0);
    $total_value += $price;

    if ($a['status'] === 'Active') $active_count++;
    $by_type[$a['asset_type']] = ($by_type[$a['asset_type']] ?? 0) + 1;

    $cat  = $a['category'] ?: 'Uncategorized';
    $cond = $a['condition_status'];
    $stat = $a['status'];

    $by_category[$cat]   = ($by_category[$cat]   ?? 0) + 1;
    $by_condition[$cond] = ($by_condition[$cond]  ?? 0) + 1;
    $by_status[$stat]    = ($by_status[$stat]     ?? 0) + 1;

    // Assigned to breakdown
    $person = $a['assigned_to'] ?: 'Unassigned';
    $by_assigned[$person] = ($by_assigned[$person] ?? 0) + 1;

    // Warranty breakdown
    if (!$a['warranty_expiry']) {
        $by_warranty['No Warranty']++;
    } else {
        $days = (strtotime($a['warranty_expiry']) - strtotime($today)) / 86400;
        if ($days < 0)       { $by_warranty['Expired']++;      if ($a['status'] === 'Active') $warranty_expired++; }
        elseif ($days <= 90) { $by_warranty['Expiring Soon']++; if ($a['status'] === 'Active') $warranty_expiring++; }
        else                 { $by_warranty['OK']++; }
    }
}
arsort($by_category);
arsort($by_assigned);

$condition_colours = [
    'New'     => ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'bar' => '#4caf50'],
    'Good'    => ['bg' => '#f1f8e9', 'color' => '#558b2f', 'bar' => '#8bc34a'],
    'Fair'    => ['bg' => '#fff8e1', 'color' => '#f57f17', 'bar' => '#ffc107'],
    'Poor'    => ['bg' => '#fff3e0', 'color' => '#e65100', 'bar' => '#ff9800'],
    'Retired' => ['bg' => '#fce4ec', 'color' => '#c62828', 'bar' => '#ef5350'],
];
$status_colours = [
    'Active'   => ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'bar' => '#4caf50'],
    'Inactive' => ['bg' => '#f3f3f3', 'color' => '#757575', 'bar' => '#9e9e9e'],
    'Disposed' => ['bg' => '#fce4ec', 'color' => '#c62828', 'bar' => '#ef5350'],
];
$warranty_colours = [
    'Expired'      => ['bar' => '#ef5350', 'color' => '#c62828'],
    'Expiring Soon'=> ['bar' => '#ffc107', 'color' => '#f57f17'],
    'OK'           => ['bar' => '#4caf50', 'color' => '#2e7d32'],
    'No Warranty'  => ['bar' => '#9e9e9e', 'color' => '#616161'],
];
$log_colours = [
    'Added'      => ['bg' => '#e8f5e9', 'color' => '#2e7d32'],
    'Updated'    => ['bg' => '#e3f2fd', 'color' => '#1565c0'],
    'Deleted'    => ['bg' => '#fce4ec', 'color' => '#c62828'],
    'Duplicated' => ['bg' => '#fff8e1', 'color' => '#f57f17'],
];
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Assets Report – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
    <style>
        body { background: #f8f8f8; margin: 0; font-family: 'Roboto', Arial, sans-serif; }

        .banner-wrapper {
            width: 100vw; left: 50%; margin-left: -50vw;
            height: 220px; background: #fff; overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,.08); position: relative;
        }
        .banner-wrapper img { position: absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; }
        .banner-title {
            position: absolute; top:50%; left:50%;
            transform: translate(-50%,-50%); margin:0;
            font-family: 'Roboto', sans-serif; font-size: 3em; font-weight: 900;
            color: #99d930; text-shadow: 0 2px 16px rgba(0,0,0,.44);
            letter-spacing: 1px; z-index: 2; white-space: nowrap;
        }

        .page-wrap { max-width: 1500px; margin: 36px auto 60px; padding: 0 18px; }

        .back-link { display: inline-flex; align-items: center; gap: 6px; margin-bottom: 18px; font-weight: 700; color: #274606; text-decoration: none; font-size: .97em; }
        .back-link:hover { color: #99d930; }

        /* ── Summary tiles ── */
        .tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 20px; margin-bottom: 36px; }
        .tile {
            background: #fff; border-radius: 14px;
            box-shadow: 0 4px 20px rgba(80,120,180,.09);
            border-left: 6px solid #99d930;
            padding: 22px 20px; display: flex; flex-direction: column; gap: 6px;
        }
        .tile.tile-green  { border-left-color: #4caf50; }
        .tile.tile-blue   { border-left-color: #1976d2; }
        .tile.tile-orange { border-left-color: #f57c00; }
        .tile.tile-purple { border-left-color: #7b1fa2; }
        .tile.tile-red    { border-left-color: #c62828; }
        .tile.tile-yellow { border-left-color: #f9a825; }
        .tile-label { font-size: .82em; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #888; }
        .tile-value { font-size: 2.2em; font-weight: 900; color: #274606; line-height: 1; }
        .tile-sub   { font-size: .85em; color: #999; }

        /* ── Cards ── */
        .card { background: #fff; border-radius: 16px; box-shadow: 0 6px 32px rgba(80,120,180,.09); border: 2px solid #99d930; padding: 28px 30px; margin-bottom: 28px; }
        .card h2 { margin: 0 0 20px; font-size: 1.2em; color: #274606; font-weight: 900; }

        /* ── Breakdown grid ── */
        .breakdown-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
        .breakdown-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-bottom: 28px; }

        /* ── Bar rows ── */
        .bar-row { margin-bottom: 14px; }
        .bar-label { display: flex; justify-content: space-between; font-size: .88em; font-weight: 700; color: #333; margin-bottom: 5px; }
        .bar-track { background: #e8f5c8; border-radius: 20px; height: 12px; overflow: hidden; }
        .bar-fill  { height: 100%; border-radius: 20px; transition: width .4s ease; }

        /* ── Asset table ── */
        .table-wrap { overflow-x: auto; border-radius: 10px; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; font-size: .88em; }
        thead tr { background: #99d930; color: #274606; }
        thead th { padding: 10px 12px; text-align: left; font-weight: 900; font-size: .8em; text-transform: uppercase; letter-spacing: .4px; white-space: nowrap; }
        tbody tr { border-bottom: 1px solid #e8f5c8; }
        tbody tr:hover { background: #f4fce6; }
        tbody tr:last-child { border-bottom: none; }
        td { padding: 9px 12px; color: #333; vertical-align: middle; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .78em; font-weight: 700; }

        /* ── Warranty badge ── */
        .w-expired  { background:#fce4ec; color:#c62828; }
        .w-expiring { background:#fff8e1; color:#f57f17; }
        .w-ok       { background:#e8f5e9; color:#2e7d32; }
        .w-none     { color:#aaa; }

        /* ── Actions row ── */
        .report-actions { display: flex; gap: 12px; margin-bottom: 28px; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 7px; padding: 10px 22px; border-radius: 8px; font-size: .97em; font-weight: 700; font-family: 'Roboto', sans-serif; border: none; cursor: pointer; text-decoration: none; transition: background .18s, transform .12s; }
        .btn-green   { background: #99d930; color: #274606; }
        .btn-green:hover { background: #85c220; transform: translateY(-1px); }
        .btn-outline { background: #fff; color: #274606; border: 2px solid #99d930; }
        .btn-outline:hover { background: #f0fad8; }

        /* ── Assigned table ── */
        .assigned-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 10px 32px; }

        @media (max-width: 900px) {
            .breakdown-grid, .breakdown-grid-3 { grid-template-columns: 1fr; }
        }
        @media (max-width: 720px) {
            .tiles { grid-template-columns: 1fr 1fr; }
        }
        @media print {
            .banner-wrapper, .back-link, .report-actions { display: none; }
            .card { box-shadow: none; border: 1px solid #ccc; }
        }
    </style>
</head>
<body>
<?php include 'show-navbar.php'; show_navbar(); ?>

<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Admin banner">
    <h1 class="banner-title">Assets Report</h1>
</div>

<div class="page-wrap">

    <a href="admin_assets.php" class="back-link">&#8592; Back to Assets</a>

    <div class="report-actions">
        <a href="admin_assets.php?export=csv" class="btn btn-green">&#11015; Export Assets CSV</a>
        <a href="admin_assets.php?export=log_csv" class="btn btn-outline">&#11015; Export Audit Log CSV</a>
        <button onclick="window.print()" class="btn btn-outline">&#128424; Print Report</button>
    </div>

    <!-- ── Summary Tiles ── -->
    <div class="tiles">
        <div class="tile tile-green">
            <div class="tile-label">Total Assets</div>
            <div class="tile-value"><?= $total_count ?></div>
            <div class="tile-sub"><?= $active_count ?> active</div>
        </div>
        <div class="tile tile-blue">
            <div class="tile-label">Total Value</div>
            <div class="tile-value">$<?= number_format($total_value, 2) ?></div>
            <div class="tile-sub">purchase prices</div>
        </div>
        <div class="tile tile-orange">
            <div class="tile-label">Physical</div>
            <div class="tile-value"><?= $by_type['Physical'] ?? 0 ?></div>
            <div class="tile-sub">hardware &amp; equipment</div>
        </div>
        <div class="tile tile-purple">
            <div class="tile-label">Digital</div>
            <div class="tile-value"><?= $by_type['Digital'] ?? 0 ?></div>
            <div class="tile-sub">licenses &amp; subscriptions</div>
        </div>
        <?php if ($warranty_expired > 0): ?>
        <div class="tile tile-red">
            <div class="tile-label">Warranty Expired</div>
            <div class="tile-value"><?= $warranty_expired ?></div>
            <div class="tile-sub">active assets</div>
        </div>
        <?php endif; ?>
        <?php if ($warranty_expiring > 0): ?>
        <div class="tile tile-yellow">
            <div class="tile-label">Expiring ≤90 days</div>
            <div class="tile-value"><?= $warranty_expiring ?></div>
            <div class="tile-sub">active assets</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Condition / Status ── -->
    <div class="breakdown-grid">

        <!-- By Condition -->
        <div class="card">
            <h2>By Condition</h2>
            <?php foreach (['New','Good','Fair','Poor','Retired'] as $cond):
                $count = $by_condition[$cond] ?? 0;
                $pct   = $total_count > 0 ? round($count / $total_count * 100) : 0;
                $c     = $condition_colours[$cond];
            ?>
            <div class="bar-row">
                <div class="bar-label">
                    <span style="color:<?= $c['color'] ?>"><?= $cond ?></span>
                    <span><?= $count ?> &nbsp;<small style="color:#aaa;">(<?= $pct ?>%)</small></span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $c['bar'] ?>;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- By Status -->
        <div class="card">
            <h2>By Status</h2>
            <?php foreach (['Active','Inactive','Disposed'] as $stat):
                $count = $by_status[$stat] ?? 0;
                $pct   = $total_count > 0 ? round($count / $total_count * 100) : 0;
                $c     = $status_colours[$stat];
            ?>
            <div class="bar-row">
                <div class="bar-label">
                    <span style="color:<?= $c['color'] ?>"><?= $stat ?></span>
                    <span><?= $count ?> &nbsp;<small style="color:#aaa;">(<?= $pct ?>%)</small></span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $c['bar'] ?>;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- ── Category / Warranty ── -->
    <div class="breakdown-grid">

        <!-- By Category -->
        <div class="card">
            <h2>By Category</h2>
            <?php foreach ($by_category as $cat => $count):
                $pct = $total_count > 0 ? round($count / $total_count * 100) : 0;
            ?>
            <div class="bar-row">
                <div class="bar-label">
                    <span><?= htmlspecialchars($cat) ?></span>
                    <span><?= $count ?> &nbsp;<small style="color:#aaa;">(<?= $pct ?>%)</small></span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width:<?= $pct ?>%;background:#99d930;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- By Warranty Status -->
        <div class="card">
            <h2>By Warranty Status</h2>
            <?php foreach (['OK','Expiring Soon','Expired','No Warranty'] as $ws):
                $count = $by_warranty[$ws] ?? 0;
                $pct   = $total_count > 0 ? round($count / $total_count * 100) : 0;
                $c     = $warranty_colours[$ws];
            ?>
            <div class="bar-row">
                <div class="bar-label">
                    <span style="color:<?= $c['color'] ?>"><?= $ws ?></span>
                    <span><?= $count ?> &nbsp;<small style="color:#aaa;">(<?= $pct ?>%)</small></span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $c['bar'] ?>;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- ── Assigned To ── -->
    <div class="card">
        <h2>By Assigned To</h2>
        <div class="assigned-grid">
        <?php foreach ($by_assigned as $person => $count):
            $pct = $total_count > 0 ? round($count / $total_count * 100) : 0;
        ?>
        <div class="bar-row">
            <div class="bar-label">
                <span><?= htmlspecialchars($person) ?></span>
                <span><?= $count ?> &nbsp;<small style="color:#aaa;">(<?= $pct ?>%)</small></span>
            </div>
            <div class="bar-track">
                <div class="bar-fill" style="width:<?= $pct ?>%;background:#1976d2;"></div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>

    <!-- ── Full asset table ── -->
    <div class="card">
        <h2>All Assets</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Serial #</th>
                        <th>Purchase Date</th>
                        <th>Price</th>
                        <th>Condition</th>
                        <th>Location</th>
                        <th>Assigned To</th>
                        <th>Warranty Expiry</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($assets as $a):
                    $cond_c   = $condition_colours[$a['condition_status']] ?? ['bg'=>'#eee','color'=>'#333'];
                    $status_c = $status_colours[$a['status']] ?? ['bg'=>'#eee','color'=>'#333'];

                    // Warranty display
                    $w_html = '<span class="w-none">—</span>';
                    if ($a['warranty_expiry']) {
                        $days = (strtotime($a['warranty_expiry']) - strtotime($today)) / 86400;
                        $w_date = date('M j, Y', strtotime($a['warranty_expiry']));
                        if ($days < 0)
                            $w_html = '<span class="badge w-expired">Expired ' . $w_date . '</span>';
                        elseif ($days <= 90)
                            $w_html = '<span class="badge w-expiring">Exp. ' . $w_date . '</span>';
                        else
                            $w_html = '<span class="badge w-ok">' . $w_date . '</span>';
                    }
                ?>
                    <tr>
                        <td><?= intval($a['id']) ?></td>
                        <td><strong><?= htmlspecialchars($a['name']) ?></strong></td>
                        <td><?= htmlspecialchars($a['asset_type']) ?></td>
                        <td><?= htmlspecialchars($a['category'] ?: '—') ?></td>
                        <td style="font-family:monospace;"><?= htmlspecialchars($a['serial_number'] ?: '—') ?></td>
                        <td style="white-space:nowrap;"><?= $a['purchase_date'] ? date('M j, Y', strtotime($a['purchase_date'])) : '—' ?></td>
                        <td style="white-space:nowrap;"><?= $a['purchase_price'] !== null ? '$' . number_format($a['purchase_price'], 2) : '—' ?></td>
                        <td><span class="badge" style="background:<?= $cond_c['bg'] ?>;color:<?= $cond_c['color'] ?>;"><?= htmlspecialchars($a['condition_status']) ?></span></td>
                        <td><?= htmlspecialchars($a['location'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($a['assigned_to'] ?: '—') ?></td>
                        <td style="white-space:nowrap;"><?= $w_html ?></td>
                        <td><span class="badge" style="background:<?= $status_c['bg'] ?>;color:<?= $status_c['color'] ?>;"><?= htmlspecialchars($a['status']) ?></span></td>
                        <td style="max-width:200px;"><?= htmlspecialchars($a['notes'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Audit Log ── -->
    <?php if ($audit_logs): ?>
    <div class="card">
        <h2>Recent Activity (Last 25 Entries)</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date / Time</th>
                        <th>Action</th>
                        <th>Asset</th>
                        <th>Changed By</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($audit_logs as $log):
                    $lc = $log_colours[$log['action']] ?? ['bg'=>'#f3f3f3','color'=>'#333'];
                ?>
                    <tr>
                        <td style="white-space:nowrap;"><?= date('M j, Y g:i a', strtotime($log['created_at'])) ?></td>
                        <td><span class="badge" style="background:<?= $lc['bg'] ?>;color:<?= $lc['color'] ?>;"><?= htmlspecialchars($log['action']) ?></span></td>
                        <td><?= htmlspecialchars($log['asset_name']) ?> <small style="color:#aaa;">#<?= intval($log['asset_id']) ?></small></td>
                        <td><?= htmlspecialchars($log['changed_by'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($log['details'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:14px;">
            <a href="admin_assets.php?export=log_csv" class="btn btn-outline" style="font-size:.9em;padding:7px 16px;">&#11015; Export Full Audit Log CSV</a>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php include 'footer.php'; ?>
</body>
</html>
