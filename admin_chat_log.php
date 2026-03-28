<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); die('Forbidden');
}

require_once __DIR__ . '/db_configuration.php';
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($conn->connect_error) { die('DB connection failed.'); }

// Ensure table exists (in case chat_api.php hasn't been called yet)
$conn->query("CREATE TABLE IF NOT EXISTS chat_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    asked_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    question    TEXT NOT NULL,
    handler     VARCHAR(60),
    response    TEXT,
    response_ms INT,
    rating      TINYINT DEFAULT NULL
)");
// Add rating column to tables created before this column was introduced
$rc = $conn->query("SHOW COLUMNS FROM chat_log LIKE 'rating'");
if ($rc && $rc->num_rows === 0) {
    $conn->query("ALTER TABLE chat_log ADD COLUMN rating TINYINT DEFAULT NULL");
}

// CSV export (before any HTML output)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $export_handler = $_GET['handler'] ?? '';
    if ($export_handler !== '') {
        $stmt = $conn->prepare("SELECT id, asked_at, handler, question, response_ms, rating FROM chat_log WHERE handler = ? ORDER BY id DESC LIMIT 5000");
        $stmt->bind_param("s", $export_handler); $stmt->execute();
        $export_result = $stmt->get_result();
    } else {
        $export_result = $conn->query("SELECT id, asked_at, handler, question, response_ms, rating FROM chat_log ORDER BY id DESC LIMIT 5000");
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="chat_log_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Asked At', 'Handler', 'Question', 'Response Time (ms)', 'Rating']);
    while ($row = $export_result->fetch_assoc()) {
        $rating_label = $row['rating'] === null ? '' : ($row['rating'] == 1 ? 'thumbs_up' : 'thumbs_down');
        fputcsv($out, [$row['id'], $row['asked_at'], $row['handler'], $row['question'], $row['response_ms'], $rating_label]);
    }
    fclose($out);
    $conn->close();
    exit;
}

// Handle delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $did = (int) $_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM chat_log WHERE id = ?");
        $stmt->bind_param("i", $did); $stmt->execute(); $stmt->close();
    } elseif (isset($_POST['delete_all'])) {
        $conn->query("TRUNCATE TABLE chat_log");
    }
}

// Summary stats (with rating counts — graceful if rating column missing)
$stats = $conn->query("SELECT
    COUNT(*) as total,
    ROUND(AVG(response_ms)) as avg_ms,
    SUM(CASE WHEN handler = 'Fallback' THEN 1 ELSE 0 END) as fallback_count,
    MIN(asked_at) as first_at,
    MAX(asked_at) as last_at
FROM chat_log")->fetch_assoc();

$rating_stats = ['thumbs_up' => 0, 'thumbs_down' => 0];
$rs = $conn->query("SELECT SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) as thumbs_up, SUM(CASE WHEN rating=-1 THEN 1 ELSE 0 END) as thumbs_down FROM chat_log")->fetch_assoc();
$rating_stats = $rs ?? $rating_stats;

$top_handler = $conn->query("SELECT handler, COUNT(*) as cnt FROM chat_log WHERE handler IS NOT NULL GROUP BY handler ORDER BY cnt DESC LIMIT 1")->fetch_assoc();

// Handler filter list
$handlers = [];
$hr = $conn->query("SELECT DISTINCT handler FROM chat_log WHERE handler IS NOT NULL ORDER BY handler");
while ($row = $hr->fetch_assoc()) $handlers[] = $row['handler'];

// Fetch logs (DataTables will handle pagination client-side)
$filter_handler = $_GET['handler'] ?? '';
if ($filter_handler !== '') {
    $stmt = $conn->prepare("SELECT id, asked_at, handler, question, response_ms, response, rating FROM chat_log WHERE handler = ? ORDER BY id DESC LIMIT 1000");
    $stmt->bind_param("s", $filter_handler); $stmt->execute();
    $logs_result = $stmt->get_result();
} else {
    $logs_result = $conn->query("SELECT id, asked_at, handler, question, response_ms, response, rating FROM chat_log ORDER BY id DESC LIMIT 1000");
}
$logs = [];
while ($row = $logs_result->fetch_assoc()) $logs[] = $row;

$conn->close();

$fallback_rate = ($stats['total'] > 0) ? round($stats['fallback_count'] / $stats['total'] * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Chat Log – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        body { background:#f8f8f8; margin:0; font-family:'Roboto',Arial,sans-serif; }
        .banner-wrapper { width:100vw; left:50%; margin-left:-50vw; height:200px; background:#fff; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,.08); position:relative; }
        .banner-wrapper img { position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; }
        .banner-title { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); margin:0; font-size:2.8em; font-weight:900; color:#99d930; text-shadow:0 2px 16px rgba(0,0,0,.44); letter-spacing:1px; z-index:2; white-space:nowrap; }
        .page-wrap { max-width:1600px; margin:36px auto 60px; padding:0 18px; }
        .back-link { display:inline-block; margin-bottom:22px; color:#274606; font-weight:700; text-decoration:none; font-size:.95em; }
        .back-link:hover { text-decoration:underline; }
        .card { background:#fff; border-radius:16px; box-shadow:0 6px 32px rgba(80,120,180,.09); border:2px solid #99d930; padding:28px 32px; margin-bottom:28px; }
        .card h2 { margin:0 0 20px; font-size:1.3em; color:#274606; font-weight:900; }
        .tiles { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:18px; margin-bottom:28px; }
        .tile { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.08); padding:20px; text-align:center; border-top:4px solid #99d930; }
        .tile.accent { background:#1a1a1a; border-top-color:#99d930; }
        .tile.warn   { border-top-color:#ff9800; }
        .tile-label { font-size:.8rem; font-weight:700; color:#888; text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; }
        .tile.accent .tile-label { color:#aaa; }
        .tile-value { font-size:2rem; font-weight:900; color:#274606; }
        .tile.accent .tile-value { color:#99d930; }
        .tile.warn   .tile-value { color:#e65100; }
        .filter-bar { display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:16px; }
        .filter-bar select, .filter-bar input { padding:8px 12px; border:2px solid #cde8a0; border-radius:8px; font-size:.9em; }
        .btn { display:inline-block; padding:8px 18px; border-radius:8px; font-size:.88em; font-weight:700; cursor:pointer; text-decoration:none; border:none; }
        .btn-apply  { background:#274606; color:#fff; }
        .btn-clear  { background:#f0f0f0; color:#444; }
        .btn-danger { background:#c62828; color:#fff; }
        .badge-handler { display:inline-block; padding:2px 9px; border-radius:12px; font-size:.75rem; font-weight:700; background:#f0fad8; color:#274606; }
        .badge-fallback { background:#fce4ec; color:#c62828; }
        .resp-preview { max-width:320px; white-space:pre-wrap; font-size:.82em; color:#555; line-height:1.4; }
        .view-link { color:#274606; font-size:.8em; text-decoration:underline; cursor:pointer; }
        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9999; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#fff; border-radius:14px; padding:28px 32px; max-width:680px; width:90%; max-height:80vh; overflow-y:auto; position:relative; }
        .modal-close { position:absolute; top:14px; right:18px; font-size:1.4em; cursor:pointer; color:#aaa; }
        .modal-close:hover { color:#c62828; }
        .modal-response { background:#f8fbe9; border-radius:8px; padding:16px; white-space:pre-wrap; font-size:.88em; line-height:1.6; border:1px solid #cde8a0; }
        table.dataTable td { vertical-align:top; }
        .delete-form { display:inline; }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Admin banner">
    <h1 class="banner-title">Chat Log</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <!-- Summary tiles -->
    <div class="tiles">
        <div class="tile accent">
            <div class="tile-label">Total Questions</div>
            <div class="tile-value"><?= number_format($stats['total']) ?></div>
        </div>
        <div class="tile">
            <div class="tile-label">Avg Response Time</div>
            <div class="tile-value"><?= $stats['avg_ms'] ?? 0 ?>ms</div>
        </div>
        <div class="tile warn">
            <div class="tile-label">Fallback Rate</div>
            <div class="tile-value"><?= $fallback_rate ?>%</div>
        </div>
        <div class="tile">
            <div class="tile-label">Top Handler</div>
            <div class="tile-value" style="font-size:1.1rem;"><?= htmlspecialchars($top_handler['handler'] ?? '—') ?></div>
        </div>
        <div class="tile">
            <div class="tile-label">First Question</div>
            <div class="tile-value" style="font-size:.9rem;"><?= $stats['first_at'] ? date('M d', strtotime($stats['first_at'])) : '—' ?></div>
        </div>
        <div class="tile">
            <div class="tile-label">Latest Question</div>
            <div class="tile-value" style="font-size:.9rem;"><?= $stats['last_at'] ? date('M d H:i', strtotime($stats['last_at'])) : '—' ?></div>
        </div>
        <div class="tile" style="border-top-color:#2e7d32;">
            <div class="tile-label">👍 Helpful</div>
            <div class="tile-value" style="color:#2e7d32;"><?= (int)$rating_stats['thumbs_up'] ?></div>
        </div>
        <div class="tile" style="border-top-color:#c62828;">
            <div class="tile-label">👎 Not Helpful</div>
            <div class="tile-value" style="color:#c62828;"><?= (int)$rating_stats['thumbs_down'] ?></div>
        </div>
    </div>

    <!-- Filters + Danger zone -->
    <div class="card">
        <h2>Questions Log</h2>
        <div class="filter-bar">
            <form method="get" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <select name="handler">
                    <option value="">All Handlers</option>
                    <?php foreach ($handlers as $h): ?>
                    <option value="<?= htmlspecialchars($h) ?>" <?= $filter_handler === $h ? 'selected' : '' ?>><?= htmlspecialchars($h) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-apply">Filter</button>
                <?php if ($filter_handler): ?>
                <a href="admin_chat_log.php" class="btn btn-clear">Clear Filter</a>
                <?php endif; ?>
            </form>
            <?php if ($stats['total'] > 0): ?>
            <a href="admin_chat_log.php?export=csv<?= $filter_handler ? '&handler='.urlencode($filter_handler) : '' ?>" class="btn" style="background:#274606;color:#fff;">&#8615; Export CSV</a>
            <form method="post" class="delete-form" onsubmit="return confirm('Delete ALL chat logs? This cannot be undone.');">
                <button type="submit" name="delete_all" value="1" class="btn btn-danger">&#x1F5D1; Clear All Logs</button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (empty($logs)): ?>
            <p style="color:#888;font-style:italic;">No questions logged yet. Chat activity will appear here once users interact with the bot.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table id="logTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Asked At</th>
                        <th>Handler</th>
                        <th>Question</th>
                        <th>Time (ms)</th>
                        <th>Rating</th>
                        <th>Response</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $idx => $log): ?>
                <tr>
                    <td><?= $log['id'] ?></td>
                    <td style="white-space:nowrap;font-size:.85em;"><?= htmlspecialchars($log['asked_at']) ?></td>
                    <td>
                        <span class="badge-handler <?= $log['handler'] === 'Fallback' ? 'badge-fallback' : '' ?>">
                            <?= htmlspecialchars($log['handler'] ?? '—') ?>
                        </span>
                    </td>
                    <td style="font-style:italic;color:#333;">"<?= htmlspecialchars(mb_substr($log['question'], 0, 80)) ?><?= mb_strlen($log['question']) > 80 ? '…' : '' ?>"</td>
                    <td style="text-align:center;color:#777;"><?= $log['response_ms'] ?>ms</td>
                    <td style="text-align:center;font-size:1.1em;"><?php
                        if ($log['rating'] === '1')       echo '👍';
                        elseif ($log['rating'] === '-1')  echo '👎';
                        else                              echo '<span style="color:#ccc;">—</span>';
                    ?></td>
                    <td>
                        <div class="resp-preview"><?= htmlspecialchars(mb_substr($log['response'] ?? '', 0, 100)) ?><?= mb_strlen($log['response'] ?? '') > 100 ? '…' : '' ?></div>
                        <?php if (mb_strlen($log['response'] ?? '') > 0): ?>
                        <span class="view-link" onclick="viewLog(<?= $idx ?>)">View full</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" class="delete-form" onsubmit="return confirm('Delete this log entry?');">
                            <input type="hidden" name="delete_id" value="<?= $log['id'] ?>">
                            <button type="submit" class="btn btn-danger" style="padding:4px 10px;font-size:.8em;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- Response modal -->
<div class="modal-overlay" id="logModal">
    <div class="modal-box">
        <span class="modal-close" onclick="closeLogModal()">&times;</span>
        <h3 id="logModalTitle" style="margin:0 0 8px;color:#274606;"></h3>
        <div class="modal-response" id="logModalBody"></div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#logTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        columnDefs: [{ orderable: false, targets: [7] }],
        language: { search: 'Search questions:' }
    });
});

const logData = <?= json_encode(array_map(fn($l) => [
    'id'       => $l['id'],
    'asked_at' => $l['asked_at'],
    'handler'  => $l['handler'],
    'question' => $l['question'],
    'response' => $l['response'] ?? '',
], $logs), JSON_HEX_TAG | JSON_HEX_AMP) ?>;

function viewLog(idx) {
    const log = logData[idx];
    document.getElementById('logModalTitle').textContent = '#' + log.id + ' — ' + log.handler + ' — ' + log.asked_at;
    document.getElementById('logModalBody').textContent = log.response;
    document.getElementById('logModal').classList.add('open');
}
function closeLogModal() { document.getElementById('logModal').classList.remove('open'); }
document.getElementById('logModal').addEventListener('click', function(e) { if (e.target === this) closeLogModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLogModal(); });
</script>
</body>
</html>
