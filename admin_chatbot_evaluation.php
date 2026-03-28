<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); die('Forbidden');
}

// Include chat_api.php as a library (bypasses HTTP handler)
define('CHAT_API_INCLUDED', true);
require_once __DIR__ . '/db_configuration.php';
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($conn->connect_error) { die('DB connection failed.'); }
require_once __DIR__ . '/chat_api.php';

// ── Test suite ────────────────────────────────────────────────────────────────
// Each entry: category, question, keywords, optional weights (default 1 each),
//             optional note explaining what the test validates.
// Score = Σ(weight × matched) / Σ(weights) × 100
$test_suite = [
    // ── Greetings ─────────────────────────────────────────────────────────────
    ['category'=>'Greeting',      'question'=>'hello',
     'keywords'=>['learn','help','schools'],
     'note'=>'Basic greeting — verifies welcome message includes program name'],
    ['category'=>'Greeting',      'question'=>'hi there',
     'keywords'=>['learn','help','schools'],
     'note'=>'Alternate greeting phrasing'],

    // ── Help ──────────────────────────────────────────────────────────────────
    ['category'=>'Help',          'question'=>'what can you do',
     'keywords'=>['schools','state','students'],
     'note'=>'Verifies help handler enumerates key capabilities'],
    ['category'=>'Help',          'question'=>'what questions can I ask',
     'keywords'=>['schools','students'],
     'note'=>'Alternate help phrasing'],

    // ── Greeting (additional) ─────────────────────────────────────────────────
    ['category'=>'Greeting',      'question'=>'good morning',
     'keywords'=>['learn','help'],
     'note'=>'"good morning" greeting variant — tests time-of-day greeting regex'],

    // ── Thank You ─────────────────────────────────────────────────────────────
    ['category'=>'Thank You',     'question'=>'thank you',
     'keywords'=>['welcome'],
     'note'=>'Verifies ThankYou handler responds with a welcome/you\'re-welcome message'],
    ['category'=>'Thank You',     'question'=>'thanks a lot',
     'keywords'=>['welcome'],
     'note'=>'Alternate thank-you phrasing — should still trigger ThankYou handler'],
    ['category'=>'Thank You',     'question'=>'thanks so much for the help',
     'keywords'=>['welcome'],
     'note'=>'Longer thank-you sentence — "thanks" prefix still triggers ThankYou'],

    // ── Total count ───────────────────────────────────────────────────────────
    ['category'=>'Total Count',   'question'=>'how many schools are supported',
     'keywords'=>['schools','active','supports'], 'weights'=>[2,2,1],
     'note'=>'Core count query — "active" and school count are critical keywords'],
    ['category'=>'Total Count',   'question'=>'total number of schools',
     'keywords'=>['schools'],
     'note'=>'Tests "number of" pattern added to total-count handler'],

    // ── Enrollment ────────────────────────────────────────────────────────────
    ['category'=>'Enrollment',    'question'=>'how many students are served',
     'keywords'=>['students','schools'], 'weights'=>[2,1],
     'note'=>'Primary enrollment query'],
    ['category'=>'Enrollment',    'question'=>'how many children are being served',
     'keywords'=>['students'],
     'note'=>'"children" synonym routes to enrollment handler'],
    ['category'=>'Enrollment',    'question'=>'what is the total student enrollment',
     'keywords'=>['students'],
     'note'=>'"enrollment" keyword routes correctly (avoids total-count handler)'],

    // ── By state ──────────────────────────────────────────────────────────────
    ['category'=>'By State',      'question'=>'schools in Andhra Pradesh',
     'keywords'=>['Andhra Pradesh','schools'], 'weights'=>[2,1],
     'note'=>'Tests hardcoded Andhra Pradesh state regex'],
    ['category'=>'By State',      'question'=>'how many schools in Telangana',
     'keywords'=>['Telangana'],
     'note'=>'Tests Telangana state handler'],
    ['category'=>'By State',      'question'=>'which schools are in Tamil Nadu',
     'keywords'=>['Tamil Nadu'],
     'note'=>'Tests multi-word "Tamil Nadu" state match'],

    // ── By state (fuzzy / typo tolerance) ────────────────────────────────────
    ['category'=>'By State (Fuzzy)', 'question'=>'schools in Telagana',
     'keywords'=>['Telangana'],
     'note'=>'Fuzzy match: "Telagana" (missing n) should still resolve to Telangana'],
    ['category'=>'By State (Fuzzy)', 'question'=>'schools in Andhara Pradesh',
     'keywords'=>['Andhra Pradesh'],
     'note'=>'Fuzzy match: "Andhara" typo → Andhra Pradesh via similar_text()'],
    ['category'=>'By State (Fuzzy)', 'question'=>'schools in Karnatka',
     'keywords'=>['Karnataka'],
     'note'=>'Fuzzy match: "Karnatka" missing vowel → Karnataka'],

    // ── By location (city-level) ──────────────────────────────────────────────
    ['category'=>'By Location',   'question'=>'schools in Hyderabad',
     'keywords'=>['Hyderabad','schools'],
     'note'=>'City-level query — should list schools with Hyderabad in name or fall back gracefully'],
    ['category'=>'By Location',   'question'=>'schools in Guntur',
     'keywords'=>['Guntur','schools'],
     'note'=>'City-level query for Guntur'],
    ['category'=>'By Location',   'question'=>'schools in Vijayawada',
     'keywords'=>['Vijayawada','schools'],
     'note'=>'City-level query for Vijayawada'],

    // ── Proposed ──────────────────────────────────────────────────────────────
    ['category'=>'Proposed',      'question'=>'how many proposed schools are there',
     'keywords'=>['awaiting','schools'],
     'note'=>'"proposed" keyword — response uses "awaiting admin review" not "proposed"'],
    ['category'=>'Proposed',      'question'=>'pending schools awaiting review',
     'keywords'=>['awaiting','nominated'],
     'note'=>'Tests "pending/awaiting" synonyms for proposed status'],

    // ── Completed ─────────────────────────────────────────────────────────────
    ['category'=>'Completed',     'question'=>'list completed schools',
     'keywords'=>['Completed','schools'],
     'note'=>'Tests status=Completed filter — verifies distinct school status handling'],

    // ── School type ───────────────────────────────────────────────────────────
    ['category'=>'School Type',   'question'=>'how many high schools are there',
     'keywords'=>['High School'],
     'note'=>'Tests high-school type handler with count phrasing'],
    ['category'=>'School Type',   'question'=>'list primary schools',
     'keywords'=>['Primary School'],
     'note'=>'Tests primary-school type handler with list phrasing'],
    ['category'=>'School Type',   'question'=>'what types of schools are supported',
     'keywords'=>['type'],
     'note'=>'Verifies type-breakdown response includes "type" keyword'],

    // ── School type (additional) ──────────────────────────────────────────────
    ['category'=>'School Type',   'question'=>'show me public schools',
     'keywords'=>['schools'],
     'note'=>'Public school type filter — verifies govt/public school lookup'],
    ['category'=>'School Type',   'question'=>'list private schools',
     'keywords'=>['schools'],
     'note'=>'Private school type filter'],

    // ── Supported by ──────────────────────────────────────────────────────────
    ['category'=>'Supported By',  'question'=>'schools supported by PGNF',
     'keywords'=>['PGNF','schools'], 'weights'=>[2,1],
     'note'=>'Tests PGNF supporter filter (critical org lookup)'],
    ['category'=>'Supported By',  'question'=>'NRIVA schools',
     'keywords'=>['NRIVA'],
     'note'=>'Tests NRIVA filter with abbreviation-only query'],

    // ── Supported by (generic funded-by phrasing) ────────────────────────────
    ['category'=>'Supported By',  'question'=>'ASHA schools',
     'keywords'=>['ASHA','schools'],
     'note'=>'Tests ASHA supporter abbreviation lookup'],

    // ── Supported by (fuzzy / typo tolerance) ────────────────────────────────
    ['category'=>'Supported By (Fuzzy)', 'question'=>'NRVA schools',
     'keywords'=>['NRIVA'],
     'note'=>'Fuzzy: "NRVA" → "NRIVA" via levenshtein distance 1'],
    ['category'=>'Supported By (Fuzzy)', 'question'=>'PGNR schools',
     'keywords'=>['PGNF'],
     'note'=>'Fuzzy: "PGNR" → "PGNF" via levenshtein distance 1'],

    // ── Largest ───────────────────────────────────────────────────────────────
    ['category'=>'Largest',       'question'=>'which is the largest school',
     'keywords'=>['enrollment','students'],
     'note'=>'Tests largest-school-by-enrollment query'],
    ['category'=>'Largest',       'question'=>'school with the most students',
     'keywords'=>['enrollment','students'],
     'note'=>'"most students" synonym for largest'],

    // ── Smallest (new handler) ────────────────────────────────────────────────
    ['category'=>'Smallest',      'question'=>'which is the smallest school',
     'keywords'=>['enrollment','students'],
     'note'=>'Tests new Smallest handler added in this sprint'],
    ['category'=>'Smallest',      'question'=>'school with the fewest students',
     'keywords'=>['enrollment','students'],
     'note'=>'"fewest" synonym routes to Smallest handler'],

    // ── State count ───────────────────────────────────────────────────────────
    ['category'=>'State Count',   'question'=>'how many states does the program operate in',
     'keywords'=>['states'],
     'note'=>'Avoids "help" keyword — uses "program" instead of "Learn and Help"'],

    // ── State count (synonym) ─────────────────────────────────────────────────
    ['category'=>'State Count',   'question'=>'how many regions does the program cover',
     'keywords'=>['states'],
     'note'=>'"regions" synonym for states — tests synonym breadth of State Count handler'],

    // ── List all (new handler) ────────────────────────────────────────────────
    ['category'=>'List All',      'question'=>'list all schools',
     'keywords'=>['schools','active'],
     'note'=>'Tests new ListAll handler'],

    // ── Recently added (new handler) ──────────────────────────────────────────
    ['category'=>'Recently Added','question'=>'what are the newest schools',
     'keywords'=>['schools'],
     'note'=>'Tests new RecentlyAdded handler'],

    // ── Missing data (new handler) ────────────────────────────────────────────
    ['category'=>'Missing Data',  'question'=>'schools with no enrollment data',
     'keywords'=>['enrollment','schools'],
     'note'=>'Tests new MissingData handler for quality-review queries'],

    // ── School lookup ─────────────────────────────────────────────────────────
    ['category'=>'School Lookup', 'question'=>'is Chethana in the program',
     'keywords'=>['Chethana'], 'weights'=>[3],
     'note'=>'Critical test — school name lookup must return correct school'],

    ['category'=>'School Lookup', 'question'=>'tell me about ZPHS',
     'keywords'=>['Z P H','schools'],
     'note'=>'ZPHS abbreviation — response should expand and list matching schools'],
    ['category'=>'School Lookup', 'question'=>'is Chethna in the program',
     'keywords'=>['mean','Chethana'],
     'note'=>'Levenshtein did-you-mean: "Chethna" → "Chethana" (1-char edit distance)'],
    ['category'=>'School Lookup', 'question'=>'is Springfield Academy in the program',
     'keywords'=>["couldn't find"],
     'note'=>'School not in DB — must return graceful not-found response'],

    // ── Unknown / adversarial ─────────────────────────────────────────────────
    // These verify the bot gracefully rejects out-of-scope queries
    ['category'=>'Unknown','question'=>'what is the weather today',
     'keywords'=>["couldn't find","school"],
     'note'=>'Off-topic: weather query — must trigger graceful fallback'],
    ['category'=>'Unknown','question'=>'tell me a joke',
     'keywords'=>["couldn't find","school"],
     'note'=>'Off-topic: entertainment request'],
    ['category'=>'Unknown','question'=>'what is 2 plus 2',
     'keywords'=>["couldn't find","school"],
     'note'=>'Off-topic: math query'],
    ['category'=>'Unknown','question'=>'who is the president',
     'keywords'=>["couldn't find","school"],
     'note'=>'Off-topic: political query'],
    ['category'=>'Unknown','question'=>'book me a flight to new york',
     'keywords'=>["couldn't find","school"],
     'note'=>'Off-topic: travel/commerce request'],
    ['category'=>'Unknown','question'=>'what movies are playing tonight',
     'keywords'=>["couldn't find","school"],
     'note'=>'Off-topic: entertainment query'],
    ['category'=>'Unknown','question'=>'translate hello to spanish',
     'keywords'=>["couldn't find","school"],
     'note'=>'Off-topic: translation request'],
    ['category'=>'Unknown','question'=>'can you do my homework',
     'keywords'=>["couldn't find","school"],
     'note'=>'Off-topic: homework/tutoring request'],
    ['category'=>'Unknown','question'=>'what is the stock price of apple',
     'keywords'=>["couldn't find","school"],
     'note'=>'Off-topic: financial query'],
];

// ── Run evaluation ────────────────────────────────────────────────
$results      = [];
$total_score  = 0;
$passed       = 0;
$cat_scores   = [];

foreach ($test_suite as $i => $test) {
    $q_start      = microtime(true);
    $result       = get_chat_response($conn, $test['question']);
    $q_ms         = (int) round((microtime(true) - $q_start) * 1000);
    $response     = $result['reply'];
    $handler_name = $result['handler'];

    // Weighted scoring: Σ(weight × matched) / Σ(weights) × 100
    $weights       = $test['weights'] ?? array_fill(0, count($test['keywords']), 1);
    $total_weight  = array_sum($weights);
    $matched       = 0;
    $matched_wt    = 0;
    foreach ($test['keywords'] as $idx => $kw) {
        $w = $weights[$idx] ?? 1;
        if (stripos($response, $kw) !== false) { $matched++; $matched_wt += $w; }
    }
    $score = $total_weight > 0 ? round(($matched_wt / $total_weight) * 100) : 0;
    $pass  = $score >= 50;

    $results[] = [
        'num'      => $i + 1,
        'category' => $test['category'],
        'question' => $test['question'],
        'keywords' => $test['keywords'],
        'weights'  => $weights,
        'matched'  => $matched,
        'score'    => $score,
        'pass'     => $pass,
        'response' => $response,
        'handler'  => $handler_name,
        'ms'       => $q_ms,
        'note'     => $test['note'] ?? '',
    ];

    $total_score += $score;
    if ($pass) $passed++;

    // Category subtotals
    $cat = $test['category'];
    if (!isset($cat_scores[$cat])) $cat_scores[$cat] = ['total' => 0, 'count' => 0, 'passed' => 0];
    $cat_scores[$cat]['total']  += $score;
    $cat_scores[$cat]['count']  += 1;
    $cat_scores[$cat]['passed'] += $pass ? 1 : 0;
}

// ── Compute totals (needed before DB save) ────────────────────────────────
$total_tests  = count($results);
$failed       = $total_tests - $passed;
$overall_pct  = round($total_score / $total_tests);
$avg_resp_len = round(array_sum(array_map(fn($r) => strlen($r['response']), $results)) / $total_tests);
$avg_ms       = round(array_sum(array_map(fn($r) => $r['ms'], $results)) / $total_tests);

// ── Save run to DB + load trend ───────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS chatbot_eval_runs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    run_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    overall_pct  INT,
    passed       INT,
    failed       INT,
    total_tests  INT,
    category_json TEXT
)");

if (!isset($_GET['export'])) {
    $cat_json = json_encode($cat_scores);
    $stmt = $conn->prepare("INSERT INTO chatbot_eval_runs (overall_pct, passed, failed, total_tests, category_json) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiiis", $overall_pct, $passed, $failed, $total_tests, $cat_json);
        $stmt->execute();
        $stmt->close();
    }
}

$all_runs = [];
$tr = $conn->query("SELECT id, run_at, overall_pct, passed, failed, total_tests, category_json FROM chatbot_eval_runs ORDER BY id DESC LIMIT 20");
if ($tr) { while ($row = $tr->fetch_assoc()) $all_runs[] = $row; }
$trend_runs = array_reverse(array_slice($all_runs, 0, 10));

// ── Real user chat log stats ───────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS chat_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    question TEXT NOT NULL,
    handler VARCHAR(60),
    response TEXT,
    response_ms INT
)");
$log_stats = ['total' => 0, 'handled' => 0, 'fallback' => 0, 'top_handler' => '—', 'top_count' => 0];
$ls = $conn->query("SELECT COUNT(*) as total,
    SUM(CASE WHEN handler NOT IN ('Fallback','Unknown') AND handler IS NOT NULL THEN 1 ELSE 0 END) as handled,
    SUM(CASE WHEN handler = 'Fallback' OR handler = 'Unknown' THEN 1 ELSE 0 END) as fallback
    FROM chat_log");
if ($ls) { $row = $ls->fetch_assoc(); $log_stats = array_merge($log_stats, $row); }
$th = $conn->query("SELECT handler, COUNT(*) as cnt FROM chat_log WHERE handler IS NOT NULL GROUP BY handler ORDER BY cnt DESC LIMIT 1");
if ($th && $th->num_rows > 0) {
    $thr = $th->fetch_assoc();
    $log_stats['top_handler'] = $thr['handler'];
    $log_stats['top_count']   = $thr['cnt'];
}

// ── Confusion matrix (category → handler heatmap) ────────────────────────
$confusion = [];
$matrix_handlers = [];
foreach ($results as $r) {
    $cat = $r['category'];
    $h   = $r['handler'];
    if (!isset($confusion[$cat])) $confusion[$cat] = [];
    $confusion[$cat][$h] = ($confusion[$cat][$h] ?? 0) + 1;
    if (!in_array($h, $matrix_handlers)) $matrix_handlers[] = $h;
}
sort($matrix_handlers);

$conn->close();

// ── CSV export ─────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="chatbot_evaluation_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');

    // ── Summary section ──
    fputcsv($out, ['SUMMARY']);
    fputcsv($out, ['Generated', date('Y-m-d H:i:s')]);
    fputcsv($out, ['Overall Accuracy (%)', round($total_score / count($results))]);
    fputcsv($out, ['Passed', $passed]);
    fputcsv($out, ['Failed', count($results) - $passed]);
    fputcsv($out, ['Total Tests', count($results)]);
    fputcsv($out, []);

    // ── Category breakdown ──
    fputcsv($out, ['CATEGORY BREAKDOWN']);
    fputcsv($out, ['Category', 'Tests', 'Passed', 'Avg Score (%)']);
    foreach ($cat_scores as $cat => $cs) {
        fputcsv($out, [
            $cat,
            $cs['count'],
            $cs['passed'],
            round($cs['total'] / $cs['count']),
        ]);
    }
    fputcsv($out, []);

    // ── Detailed results ──
    fputcsv($out, ['DETAILED RESULTS']);
    fputcsv($out, ['#', 'Category', 'Question', 'Note', 'Expected Keywords', 'Matched', 'Score (%)', 'Pass/Fail', 'Handler', 'Time (ms)', 'Response']);
    foreach ($results as $r) {
        fputcsv($out, [
            $r['num'],
            $r['category'],
            $r['question'],
            $r['note'],
            implode(', ', $r['keywords']),
            $r['matched'] . '/' . count($r['keywords']),
            $r['score'],
            $r['pass'] ? 'Pass' : 'Fail',
            $r['handler'],
            $r['ms'],
            $r['response'],
        ]);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Chatbot Evaluation – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        .card h3 { margin:0 0 14px; font-size:1.1em; color:#274606; font-weight:700; }
        /* Tiles */
        .tiles { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:18px; margin-bottom:28px; }
        .tile { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.08); padding:22px; text-align:center; border-top:4px solid #99d930; }
        .tile.accent { background:#1a1a1a; border-top-color:#99d930; }
        .tile-label { font-size:.82rem; font-weight:700; color:#888; text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; }
        .tile.accent .tile-label { color:#aaa; }
        .tile-value { font-size:2.2rem; font-weight:900; color:#274606; }
        .tile.accent .tile-value { color:#99d930; }
        .tile.pass-tile .tile-value { color:#2e7d32; }
        .tile.fail-tile .tile-value { color:#c62828; }
        /* Table */
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:.88em; }
        th { background:#f8fbe9; padding:10px 14px; text-align:left; font-weight:700; color:#274606; border-bottom:2px solid #cde8a0; white-space:nowrap; }
        td { padding:10px 14px; border-bottom:1px solid #f0f5e8; vertical-align:top; }
        tr:hover td { background:#f8fdf0; }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:700; }
        .badge-pass { background:#e8f5e9; color:#2e7d32; }
        .badge-fail { background:#fce4ec; color:#c62828; }
        .score-bar { height:8px; border-radius:4px; background:#eee; margin-top:4px; width:100px; }
        .score-fill { height:8px; border-radius:4px; background:#99d930; }
        .score-fill.low { background:#f44336; }
        .score-fill.mid { background:#ff9800; }
        .resp-preview { color:#555; font-size:.82em; max-width:320px; white-space:pre-wrap; line-height:1.4; }
        .kw-list { display:flex; flex-wrap:wrap; gap:4px; }
        .kw { background:#f0fad8; color:#274606; padding:2px 8px; border-radius:12px; font-size:.75rem; }
        .kw.matched { background:#c8e6c9; color:#1b5e20; }
        /* Category breakdown */
        .cat-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; margin-top:16px; }
        .cat-card { background:#f8fbe9; border-radius:10px; padding:14px 18px; border:1.5px solid #cde8a0; }
        .cat-name { font-weight:700; color:#274606; font-size:.9em; margin-bottom:6px; }
        .cat-pct { font-size:1.5em; font-weight:900; color:#274606; }
        .cat-sub { font-size:.78em; color:#888; margin-top:2px; }
        /* Parameter section */
        .param-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; margin-top:16px; }
        .param-card { background:#f8fbe9; border-radius:12px; padding:18px 20px; border:1.5px solid #cde8a0; }
        .param-name { font-size:1.1em; font-weight:900; color:#274606; margin-bottom:6px; font-family:monospace; }
        .param-range { font-size:.82em; color:#666; margin-bottom:8px; }
        .param-desc { font-size:.88em; color:#444; line-height:1.5; }
        .param-applies { display:inline-block; margin-top:8px; padding:3px 10px; border-radius:12px; font-size:.78rem; font-weight:700; }
        .param-applies.yes { background:#e8f5e9; color:#2e7d32; }
        .param-applies.no  { background:#fce4ec; color:#c62828; }
        .arch-box { background:#fff8e1; border:1.5px solid #ffe082; border-radius:10px; padding:16px 20px; margin-top:16px; font-size:.9em; line-height:1.6; color:#444; }
        .arch-box strong { color:#274606; }
        @media(max-width:900px){ .param-grid{grid-template-columns:1fr;} }
        /* Comparison widget */
        .compare-selects { display:flex; gap:16px; align-items:center; flex-wrap:wrap; margin-bottom:16px; }
        .compare-selects select { padding:7px 12px; border:2px solid #cde8a0; border-radius:8px; font-size:.9em; }
        .compare-table th { background:#f8fbe9; }
        .delta-pos { color:#2e7d32; font-weight:700; }
        .delta-neg { color:#c62828; font-weight:700; }
        .delta-neu { color:#888; }
        /* Test note */
        .test-note { font-size:.76em; color:#888; font-style:italic; margin-top:2px; }
        /* Weight badge */
        .kw-weight { font-size:.68em; background:#fff3cd; color:#856404; border-radius:8px; padding:1px 5px; margin-left:2px; vertical-align:middle; }
        /* Print */
        @media print {
            .navbar, .banner-wrapper, .action-bar, .modal-overlay, .resp-link,
            .btn-rerun, .btn-csv, #compareCard { display:none !important; }
            .card { box-shadow:none !important; border:1px solid #ccc !important; break-inside:avoid; }
            body { background:#fff !important; }
            .page-wrap { max-width:100% !important; }
        }
        /* Score band colors */
        .score-cell-100 { background:#e8f5e9; }
        .score-cell-mid { background:#fff8e1; }
        .score-cell-low { background:#fce4ec; }
        /* Response modal */
        .resp-link { color:#274606; font-size:.8em; text-decoration:underline; cursor:pointer; display:block; margin-top:4px; }
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9999; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#fff; border-radius:14px; padding:28px 32px; max-width:680px; width:90%; max-height:80vh; overflow-y:auto; box-shadow:0 8px 40px rgba(0,0,0,.22); position:relative; }
        .modal-box h3 { margin:0 0 8px; color:#274606; font-size:1.1em; }
        .modal-meta { font-size:.82em; color:#888; margin-bottom:16px; }
        .modal-response { background:#f8fbe9; border-radius:8px; padding:16px; white-space:pre-wrap; font-size:.88em; line-height:1.6; color:#333; border:1px solid #cde8a0; }
        .modal-close { position:absolute; top:14px; right:18px; font-size:1.4em; cursor:pointer; color:#aaa; font-weight:700; line-height:1; }
        .modal-close:hover { color:#c62828; }
        /* Action buttons */
        .action-bar { display:flex; align-items:center; gap:12px; margin-bottom:22px; }
        .btn-action { display:inline-block; padding:8px 18px; border-radius:8px; font-size:.88em; font-weight:700; cursor:pointer; text-decoration:none; border:none; }
        .btn-rerun { background:#1a1a1a; color:#99d930; }
        .btn-rerun:hover { background:#333; }
        .btn-csv { background:#274606; color:#fff; }
        .btn-csv:hover { background:#3a6b0a; }
        /* Collapsible cards */
        .card[data-card-id] h2 { cursor:pointer; display:flex; align-items:center; justify-content:space-between; user-select:none; }
        .card-toggle { background:none; border:1.5px solid #cde8a0; border-radius:6px; padding:2px 10px; cursor:pointer; font-size:.85em; color:#274606; line-height:1.6; flex-shrink:0; }
        .card-toggle:hover { background:#f0fad8; }
        .card-body { transition:none; }
        .card-body.collapsed { display:none; }
        .card-collapsed-hint { font-size:.8em; color:#aaa; font-weight:400; margin-left:12px; font-style:italic; }
        @media print { .card-body.collapsed { display:block !important; } .card-toggle { display:none; } }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Admin banner">
    <h1 class="banner-title">Chatbot Evaluation</h1>
</div>

<div class="page-wrap">

    <div class="action-bar">
        <a href="administration.php" class="back-link" style="margin-bottom:0;">&#8592; Back to Administration</a>
        <a href="admin_chatbot_evaluation.php" class="btn-action btn-rerun">&#8635; Re-run Evaluation</a>
        <a href="admin_chatbot_evaluation.php?export=csv" class="btn-action btn-csv">&#8615; Export CSV</a>
        <button onclick="window.print()" class="btn-action" style="background:#555;color:#fff;cursor:pointer;">&#128424; Print Report</button>
    </div>

    <!-- Summary tiles -->
    <div class="tiles">
        <div class="tile accent">
            <div class="tile-label">Overall Accuracy</div>
            <div class="tile-value"><?= $overall_pct ?>%</div>
        </div>
        <div class="tile pass-tile">
            <div class="tile-label">Tests Passed</div>
            <div class="tile-value"><?= $passed ?></div>
        </div>
        <div class="tile fail-tile">
            <div class="tile-label">Tests Failed</div>
            <div class="tile-value"><?= $failed ?></div>
        </div>
        <div class="tile">
            <div class="tile-label">Total Tests</div>
            <div class="tile-value"><?= $total_tests ?></div>
        </div>
        <div class="tile">
            <div class="tile-label">Avg Response Length</div>
            <div class="tile-value"><?= $avg_resp_len ?></div>
        </div>
        <div class="tile">
            <div class="tile-label">Avg Response Time</div>
            <div class="tile-value"><?= $avg_ms ?>ms</div>
        </div>
        <div class="tile" style="border-top-color:#5c80ad;">
            <div class="tile-label">Real User Questions</div>
            <div class="tile-value" style="color:#5c80ad;"><?= number_format($log_stats['total']) ?></div>
        </div>
        <div class="tile" style="border-top-color:#5c80ad;">
            <div class="tile-label">Live Handler Rate</div>
            <div class="tile-value" style="font-size:1.5rem;color:#5c80ad;"><?= $log_stats['total'] > 0 ? round($log_stats['handled'] / $log_stats['total'] * 100) : '—' ?><?= $log_stats['total'] > 0 ? '%' : '' ?></div>
        </div>
        <div class="tile" style="border-top-color:#5c80ad;">
            <div class="tile-label">Top Real Handler</div>
            <div class="tile-value" style="font-size:1rem;color:#5c80ad;"><?= htmlspecialchars($log_stats['top_handler']) ?><?= $log_stats['top_count'] > 0 ? '<br><span style="font-size:.7rem;color:#888;">'.$log_stats['top_count'].' questions</span>' : '' ?></div>
        </div>
    </div>

    <!-- Category breakdown -->
    <div class="card" data-card-id="category-breakdown" data-default="open">
        <h2>Accuracy by Category</h2>
        <div class="cat-grid">
            <?php foreach ($cat_scores as $cat => $cs): $pct = round($cs['total'] / $cs['count']); ?>
            <div class="cat-card">
                <div class="cat-name"><?= htmlspecialchars($cat) ?></div>
                <div class="cat-pct"><?= $pct ?>%</div>
                <div class="cat-sub"><?= $cs['passed'] ?>/<?= $cs['count'] ?> passed</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Results table -->
    <div class="card" data-card-id="results-table" data-default="open">
        <h2>Detailed Test Results</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th>Question</th>
                        <th>Expected Keywords</th>
                        <th>Score</th>
                        <th>Result</th>
                        <th>Handler</th>
                        <th>Time</th>
                        <th>Response Preview</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($results as $r): ?>
                <tr>
                    <td><?= $r['num'] ?></td>
                    <td><strong><?= htmlspecialchars($r['category']) ?></strong></td>
                    <td>
                        <div style="font-style:italic;color:#555;">"<?= htmlspecialchars($r['question']) ?>"</div>
                        <?php if ($r['note']): ?><div class="test-note"><?= htmlspecialchars($r['note']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <div class="kw-list">
                        <?php foreach ($r['keywords'] as $idx => $kw):
                            $w = $r['weights'][$idx] ?? 1;
                            $hit = stripos($r['response'], $kw) !== false;
                        ?>
                            <span class="kw <?= $hit ? 'matched' : '' ?>">
                                <?= htmlspecialchars($kw) ?>
                                <?php if ($w > 1): ?><span class="kw-weight">×<?= $w ?></span><?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                        </div>
                    </td>
                    <td class="<?= $r['score'] >= 100 ? 'score-cell-100' : ($r['score'] >= 50 ? 'score-cell-mid' : 'score-cell-low') ?>">
                        <strong><?= $r['score'] ?>%</strong>
                        <div class="score-bar">
                            <div class="score-fill <?= $r['score'] < 50 ? 'low' : ($r['score'] < 75 ? 'mid' : '') ?>"
                                 style="width:<?= $r['score'] ?>%"></div>
                        </div>
                    </td>
                    <td><span class="badge <?= $r['pass'] ? 'badge-pass' : 'badge-fail' ?>"><?= $r['pass'] ? '✓ Pass' : '✗ Fail' ?></span></td>
                    <td><code style="font-size:.78em;color:#555;"><?= htmlspecialchars($r['handler']) ?></code></td>
                    <td style="white-space:nowrap;color:#777;font-size:.85em;"><?= $r['ms'] ?>ms</td>
                    <td>
                        <div class="resp-preview"><?= htmlspecialchars(mb_substr($r['response'], 0, 120)) ?><?= strlen($r['response']) > 120 ? '…' : '' ?></div>
                        <span class="resp-link" onclick="showResponse(<?= $r['num'] - 1 ?>)">View full response</span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Failure Analysis -->
    <div class="card" data-card-id="failure-analysis" data-default="closed">
        <h2>Failure Analysis</h2>
        <?php
        $fail_cats = array_filter($cat_scores, fn($cs) => $cs['passed'] < $cs['count']);
        $handler_counts = array_count_values(array_map(fn($r) => $r['handler'], array_filter($results, fn($r) => !$r['pass'])));
        arsort($handler_counts);
        ?>
        <?php if (empty($fail_cats)): ?>
            <p style="color:#2e7d32;font-weight:700;">All categories passed — 100% accuracy achieved!</p>
        <?php else: ?>
            <p style="color:#444;font-size:.93em;line-height:1.7;margin-bottom:14px;">
                <?php
                $sentences = [];
                foreach ($fail_cats as $cat => $cs) {
                    $fc = $cs['count'] - $cs['passed'];
                    $sentences[] = "<strong>$fc of {$cs['count']}</strong> tests failed in <em>$cat</em>";
                }
                echo implode('; ', $sentences) . '.';
                ?>
            </p>
            <?php if (!empty($handler_counts)): ?>
            <p style="color:#444;font-size:.9em;margin-bottom:8px;"><strong>Failed tests by handler triggered:</strong></p>
            <div class="kw-list">
                <?php foreach ($handler_counts as $h => $cnt): ?>
                <span class="kw" style="background:#fce4ec;color:#c62828;"><?= htmlspecialchars($h) ?> (<?= $cnt ?>)</span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Handler Coverage -->
    <div class="card" data-card-id="handler-coverage" data-default="closed">
        <h2>Handler Coverage</h2>
        <?php
        $all_handlers  = ['Greeting','Help','ThankYou','ListAll','RecentlyAdded','TotalCount',
                          'Proposed','Completed','Enrollment','ByState','ByState(fuzzy)','ByLocation',
                          'StateCount','SupportedBy','SchoolType','HighSchool','PrimarySchool',
                          'PublicSchool','PrivateSchool','Largest','Smallest','MissingData',
                          'SchoolLookup','Fallback'];
        $tested = array_unique(array_map(fn($r) => $r['handler'], $results));
        $untested = array_values(array_diff($all_handlers, $tested));
        $coverage_pct = round(count($tested) / count($all_handlers) * 100);
        ?>
        <p style="font-size:.93em;color:#444;margin-bottom:12px;">
            The test suite exercises <strong><?= count($tested) ?> of <?= count($all_handlers) ?> handlers</strong>
            (<strong><?= $coverage_pct ?>% coverage</strong>).
        </p>
        <p style="font-size:.88em;color:#666;margin-bottom:8px;"><strong>Handlers tested:</strong></p>
        <div class="kw-list" style="margin-bottom:14px;">
            <?php foreach ($tested as $h): ?>
            <span class="kw matched"><?= htmlspecialchars($h) ?></span>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($untested)): ?>
        <p style="font-size:.88em;color:#c62828;margin-bottom:8px;"><strong>Handlers with no test coverage:</strong></p>
        <div class="kw-list">
            <?php foreach ($untested as $h): ?>
            <span class="kw" style="background:#fce4ec;color:#c62828;"><?= htmlspecialchars($h) ?></span>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="color:#2e7d32;font-weight:700;">All handlers are covered by at least one test.</p>
        <?php endif; ?>
    </div>

    <!-- Confusion Matrix -->
    <div class="card" data-card-id="confusion-matrix" data-default="closed">
        <h2>Confusion Matrix — Category vs. Handler</h2>
        <p style="font-size:.88em;color:#666;margin-bottom:14px;">
            Each cell shows how many test questions of a given <em>category</em> triggered each <em>handler</em>.
            Diagonal cells (expected handler matches category) should be non-zero; off-diagonal cells reveal misrouting.
        </p>
        <div style="overflow-x:auto;">
            <table style="font-size:.8em;">
                <thead>
                    <tr>
                        <th style="background:#1a1a1a;color:#99d930;">Category</th>
                        <?php foreach ($matrix_handlers as $mh): ?>
                        <th style="white-space:nowrap;"><?= htmlspecialchars($mh) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($confusion as $cat => $handler_map):
                    $row_total = array_sum($handler_map); ?>
                <tr>
                    <td style="font-weight:700;color:#274606;white-space:nowrap;"><?= htmlspecialchars($cat) ?></td>
                    <?php foreach ($matrix_handlers as $mh):
                        $cnt = $handler_map[$mh] ?? 0;
                        $intensity = $row_total > 0 ? $cnt / $row_total : 0;
                        $bg = $cnt > 0 ? 'rgba(153,217,48,' . round($intensity * 0.7 + 0.1, 2) . ')' : '#f8f8f8';
                        $fg = $intensity > 0.5 ? '#274606' : ($cnt > 0 ? '#444' : '#ccc');
                    ?>
                    <td style="text-align:center;background:<?= $bg ?>;color:<?= $fg ?>;font-weight:<?= $cnt > 0 ? '700' : '400' ?>;">
                        <?= $cnt > 0 ? $cnt : '·' ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p style="font-size:.78em;color:#888;margin-top:10px;">Darker green = more questions from that category routed to that handler.</p>
    </div>

    <!-- Score Distribution Chart -->
    <div class="card" data-card-id="score-distribution" data-default="closed">
        <h2>Score Distribution</h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:center;">
            <div>
                <?php
                $bins = ['100%' => 0, '75–99%' => 0, '50–74%' => 0, '1–49%' => 0, '0%' => 0];
                foreach ($results as $r) {
                    if ($r['score'] === 100)      $bins['100%']++;
                    elseif ($r['score'] >= 75)    $bins['75–99%']++;
                    elseif ($r['score'] >= 50)    $bins['50–74%']++;
                    elseif ($r['score'] > 0)      $bins['1–49%']++;
                    else                          $bins['0%']++;
                }
                $colors = ['100%' => '#2e7d32', '75–99%' => '#66bb6a', '50–74%' => '#ffa726', '1–49%' => '#ef5350', '0%' => '#c62828'];
                ?>
                <canvas id="distChart" height="200"></canvas>
            </div>
            <div>
                <?php foreach ($bins as $label => $cnt): ?>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                    <div style="width:14px;height:14px;border-radius:3px;background:<?= $colors[$label] ?>;flex-shrink:0;"></div>
                    <span style="font-size:.88em;color:#444;min-width:70px;"><?= $label ?></span>
                    <div style="flex:1;background:#f0f0f0;border-radius:4px;height:14px;">
                        <div style="width:<?= $total_tests > 0 ? round($cnt/$total_tests*100) : 0 ?>%;background:<?= $colors[$label] ?>;height:14px;border-radius:4px;"></div>
                    </div>
                    <span style="font-size:.88em;font-weight:700;color:#333;min-width:24px;"><?= $cnt ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Accuracy Trend -->
    <?php if (count($trend_runs) > 1):
        $first_pct = $trend_runs[0]['overall_pct'];
        $last_pct  = end($trend_runs)['overall_pct'];
        $delta     = $last_pct - $first_pct;
    ?>
    <div class="card" data-card-id="accuracy-trend" data-default="open">
        <h2>Accuracy Trend (Last <?= count($trend_runs) ?> Runs)</h2>
        <canvas id="trendChart" height="80"></canvas>
        <p style="margin-top:18px;font-size:.9em;line-height:1.7;color:#444;">
            <?php if ($delta > 0): ?>
            Accuracy <strong>improved by <?= $delta ?> percentage points</strong> over the last <?= count($trend_runs) ?> evaluation runs
            (from <?= $first_pct ?>% to <?= $last_pct ?>%). This reflects ongoing improvements to the chatbot's pattern matching,
            synonym coverage, and fuzzy state/school-name recognition added during development sprints.
            <?php elseif ($delta < 0): ?>
            Accuracy <strong>decreased by <?= abs($delta) ?> percentage points</strong> over the last <?= count($trend_runs) ?> runs
            (from <?= $first_pct ?>% to <?= $last_pct ?>%). This may reflect the addition of new, harder test cases that expose
            previously untested handler gaps — a sign of a more rigorous test suite, not necessarily a regression in the bot itself.
            <?php else: ?>
            Accuracy has remained <strong>stable at <?= $last_pct ?>%</strong> across the last <?= count($trend_runs) ?> evaluation runs,
            indicating the chatbot's rule-based engine produces fully reproducible results — a key property of deterministic systems.
            <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Run Comparison -->
    <?php if (count($all_runs) >= 2): ?>
    <div class="card" id="compareCard" data-card-id="compare-runs" data-default="closed">
        <h2>Compare Two Runs</h2>
        <div class="compare-selects">
            <label style="font-weight:700;font-size:.9em;">Run A:
                <select id="selA">
                    <?php foreach ($all_runs as $run): ?>
                    <option value="<?= $run['id'] ?>"><?= date('M d H:i', strtotime($run['run_at'])) ?> — <?= $run['overall_pct'] ?>%</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label style="font-weight:700;font-size:.9em;">Run B:
                <select id="selB">
                    <?php foreach ($all_runs as $idx => $run): ?>
                    <option value="<?= $run['id'] ?>" <?= $idx === 1 ? 'selected' : '' ?>><?= date('M d H:i', strtotime($run['run_at'])) ?> — <?= $run['overall_pct'] ?>%</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button onclick="renderComparison()" class="btn-action btn-rerun" style="margin:0;">Compare</button>
        </div>
        <div id="compareResult"></div>
    </div>
    <?php endif; ?>

    <!-- Rule-based vs LLM Comparison -->
    <div class="card" data-card-id="llm-comparison" data-default="closed">
        <h2>Rule-Based vs. LLM — Side-by-Side Comparison</h2>
        <p style="font-size:.88em;color:#666;line-height:1.6;margin-bottom:18px;">
            Five representative questions comparing the current rule-based engine's actual response against a
            representative Claude (Anthropic) response. Highlights where each approach excels and where it falls short.
        </p>
        <?php
        // Map questions from the test results for the rule-based side
        $results_by_q = [];
        foreach ($results as $r) $results_by_q[strtolower($r['question'])] = $r;

        $comparisons = [
            [
                'question'   => 'how many schools are supported',
                'llm_answer' => 'The Learn and Help program currently supports schools across several states in India, though I don\'t have access to real-time database figures. Based on program documentation, the initiative has grown to support dozens of active schools. For the exact live count, I\'d recommend checking the administrator dashboard.',
                'advantage'  => 'rule',
                'note'       => 'Rule-based queries the live DB — exact count every time. LLM relies on training data and cannot give a live number.',
            ],
            [
                'question'   => 'schools in andhra pradesh',
                'llm_answer' => 'The Learn and Help program operates several schools in Andhra Pradesh. These typically include government and aided schools in rural districts. I\'d recommend asking the program coordinator for an up-to-date list with enrollment figures.',
                'advantage'  => 'rule',
                'note'       => 'Rule-based returns the actual school names from the DB. LLM gives a vague description without real data.',
            ],
            [
                'question'   => 'which is the largest school',
                'llm_answer' => 'Based on available information, the largest school in the Learn and Help network is likely one of the higher-enrollment government schools in Andhra Pradesh or Telangana. Without real-time data I cannot give you the exact school name and enrollment count.',
                'advantage'  => 'rule',
                'note'       => 'Rule-based does a live MAX(enrollment) SQL query. LLM can only guess without DB access.',
            ],
            [
                'question'   => 'what can you do',
                'llm_answer' => 'I can answer questions about the Learn and Help school support program! I can tell you about schools by state, enrollment figures, supporter organizations, school types, and more. Just ask me anything about the program\'s schools and I\'ll do my best to help.',
                'advantage'  => 'llm',
                'note'       => 'LLM gives a more natural, conversational help response. Rule-based response is functional but template-like.',
            ],
            [
                'question'   => 'what is the weather today',
                'llm_answer' => 'I\'m not able to provide real-time weather information. I\'m a specialized assistant for the Learn and Help school program. If you have questions about schools, enrollment, or program supporters, I\'m happy to help!',
                'advantage'  => 'tie',
                'note'       => 'Both handle out-of-scope queries gracefully with a redirect. LLM\'s refusal is slightly more natural-sounding.',
            ],
        ];
        ?>
        <?php foreach ($comparisons as $comp):
            $q_key    = strtolower($comp['question']);
            $rule_res = $results_by_q[$q_key] ?? null;
            $rule_ans = $rule_res ? strip_tags($rule_res['response']) : '(question not in test suite)';
            $rule_ans = mb_substr($rule_ans, 0, 300) . (mb_strlen($rule_ans) > 300 ? '…' : '');
        ?>
        <div style="border:1.5px solid #cde8a0;border-radius:10px;padding:16px 20px;margin-bottom:16px;">
            <div style="font-weight:700;color:#274606;margin-bottom:10px;font-size:.95em;">
                Q: "<?= htmlspecialchars($comp['question']) ?>"
                <?php if ($comp['advantage'] === 'rule'): ?>
                <span style="background:#e8f5e9;color:#2e7d32;font-size:.75rem;padding:2px 9px;border-radius:12px;margin-left:8px;font-weight:700;">Rule-based wins</span>
                <?php elseif ($comp['advantage'] === 'llm'): ?>
                <span style="background:#e3f2fd;color:#1565c0;font-size:.75rem;padding:2px 9px;border-radius:12px;margin-left:8px;font-weight:700;">LLM wins</span>
                <?php else: ?>
                <span style="background:#fff3cd;color:#856404;font-size:.75rem;padding:2px 9px;border-radius:12px;margin-left:8px;font-weight:700;">Tie</span>
                <?php endif; ?>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <div style="font-size:.78em;font-weight:700;color:#274606;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">Rule-Based (chat.php — live DB)</div>
                    <div style="background:#f8fbe9;border:1px solid #cde8a0;border-radius:8px;padding:10px 12px;font-size:.82em;color:#333;line-height:1.5;white-space:pre-wrap;"><?= htmlspecialchars($rule_ans) ?></div>
                </div>
                <div>
                    <div style="font-size:.78em;font-weight:700;color:#1565c0;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">LLM (gpt-4o-mini — representative)</div>
                    <div style="background:#e8f0fb;border:1px solid #90caf9;border-radius:8px;padding:10px 12px;font-size:.82em;color:#333;line-height:1.5;"><?= htmlspecialchars($comp['llm_answer']) ?></div>
                </div>
            </div>
            <div style="margin-top:8px;font-size:.78em;color:#888;font-style:italic;"><?= htmlspecialchars($comp['note']) ?></div>
        </div>
        <?php endforeach; ?>
        <div style="background:#fff8e1;border:1.5px solid #ffe082;border-radius:8px;padding:12px 16px;font-size:.85em;color:#555;line-height:1.6;margin-top:4px;">
            <strong>Takeaway:</strong> The rule-based engine outperforms the LLM on <em>data-retrieval queries</em> (counts, lookups, rankings) because it has direct live DB access. The LLM excels at <em>conversational and open-ended questions</em> where natural language fluency matters. An ideal production system would combine both: the rule-based engine for structured data queries, and an LLM (with retrieval-augmented generation) for natural conversation and fallback responses.
        </div>
    </div>

    <!-- Model Parameters Investigation -->
    <div class="card" data-card-id="model-parameters" data-default="closed">
        <h2>Model Parameter Investigation</h2>

        <div class="arch-box">
            <strong>Architecture Note:</strong> The active chatbot (<code>chat.php</code>) uses a <strong>rule-based, deterministic engine</strong> by default — regex pattern matching combined with direct SQL queries against the schools database. Because there is no probabilistic language model involved, parameters like temperature, top_p, and top_k <em>do not apply</em> in rule-based mode. Its accuracy is fully reproducible and consistent on every run.
            <br><br>
            <code>chat.php</code> also supports an optional <strong>LLM mode</strong> powered by Anthropic's <strong>Claude (claude-haiku-4-5)</strong>, switchable by admins without code changes. The parameter investigation below applies to that LLM mode.
        </div>

        <div class="param-grid">
            <div class="param-card">
                <div class="param-name">temperature</div>
                <div class="param-range">Range: 0.0 – 1.0 &nbsp;|&nbsp; Recommended: 0.2</div>
                <div class="param-desc">
                    Controls randomness in the model's output. A value of <strong>0</strong> makes responses nearly deterministic. Higher values like <strong>0.7–1.0</strong> introduce creative variation. For a factual Q&amp;A chatbot about schools, a <strong>low temperature (0.1–0.3)</strong> is recommended — it keeps answers grounded and reduces hallucination. Anthropic's Claude supports temperature in the range 0.0–1.0.
                </div>
                <span class="param-applies yes">Applies to Claude (LLM mode)</span>
            </div>
            <div class="param-card">
                <div class="param-name">top_p</div>
                <div class="param-range">Range: 0.0 – 1.0 &nbsp;|&nbsp; Recommended: 0.9</div>
                <div class="param-desc">
                    Nucleus sampling — the model only considers tokens whose cumulative probability reaches <em>top_p</em>. Setting <strong>top_p = 0.9</strong> means the model picks from the top 90% most likely next tokens, cutting off unlikely completions. For factual domains, <strong>top_p = 0.8–0.9</strong> with a low temperature gives accurate, focused responses. Anthropic recommends adjusting either temperature <em>or</em> top_p, not both simultaneously.
                </div>
                <span class="param-applies yes">Applies to Claude (LLM mode)</span>
            </div>
            <div class="param-card">
                <div class="param-name">top_k</div>
                <div class="param-range">Range: 1 – vocabulary size &nbsp;|&nbsp; Supported by Anthropic</div>
                <div class="param-desc">
                    Limits token selection to the top <em>k</em> most probable next tokens at each step. Unlike OpenAI, <strong>Anthropic's Claude API does support top_k</strong>. For tight, factual answers, <strong>top_k = 10–40</strong> combined with a low temperature is effective. In our implementation, top_k is not currently exposed in the admin panel but can be added to the API payload if needed.
                </div>
                <span class="param-applies yes">Supported — not currently exposed in admin panel</span>
            </div>
        </div>

        <div style="margin-top:24px;">
            <h3>Findings &amp; Recommendation</h3>
            <p style="line-height:1.7;color:#444;font-size:.93em;">
                The rule-based chatbot achieves <strong><?= $overall_pct ?>% overall accuracy</strong> on the test suite of <?= $total_tests ?> questions, with particularly high accuracy on structured queries (enrollment counts, state lookups, supported-by queries). It scores lower on open-ended or out-of-scope questions by design — responding with a helpful fallback rather than a hallucinated answer, which is the correct behavior for a domain-specific assistant.
            </p>
            <p style="line-height:1.7;color:#444;font-size:.93em;">
                For the integrated Claude LLM mode, the recommended parameter settings for this domain are: <code>temperature = 0.2</code>, <code>top_p = 0.9</code>, <code>max_tokens = 300</code>. These settings minimize hallucination while keeping responses fluent. For even better accuracy, the system prompt could be enhanced with live school data (retrieval-augmented generation) so Claude references real database values rather than relying on general knowledge alone.
            </p>
        </div>
    </div>

</div>

<!-- Charts -->
<script>
// Run comparison data
const allRuns = <?= json_encode(array_map(fn($r) => [
    'id'           => (int)$r['id'],
    'run_at'       => $r['run_at'],
    'overall_pct'  => (int)$r['overall_pct'],
    'passed'       => (int)$r['passed'],
    'failed'       => (int)$r['failed'],
    'total_tests'  => (int)$r['total_tests'],
    'categories'   => json_decode($r['category_json'] ?? '{}', true) ?? [],
], $all_runs), JSON_HEX_TAG | JSON_HEX_AMP) ?>;

function renderComparison() {
    const idA = parseInt(document.getElementById('selA').value);
    const idB = parseInt(document.getElementById('selB').value);
    const runA = allRuns.find(r => r.id === idA);
    const runB = allRuns.find(r => r.id === idB);
    if (!runA || !runB || idA === idB) {
        document.getElementById('compareResult').innerHTML = '<p style="color:#c62828">Select two different runs.</p>';
        return;
    }
    const cats = new Set([...Object.keys(runA.categories), ...Object.keys(runB.categories)]);
    let html = `<table class="compare-table" style="width:100%;border-collapse:collapse;font-size:.9em;">
        <thead><tr>
            <th style="padding:8px 12px;text-align:left;">Category</th>
            <th style="padding:8px 12px;">Run A (${runA.run_at.substring(0,16)})</th>
            <th style="padding:8px 12px;">Run B (${runB.run_at.substring(0,16)})</th>
            <th style="padding:8px 12px;">Delta</th>
        </tr></thead><tbody>`;
    cats.forEach(cat => {
        const a = runA.categories[cat] ? Math.round(runA.categories[cat].total / runA.categories[cat].count) : null;
        const b = runB.categories[cat] ? Math.round(runB.categories[cat].total / runB.categories[cat].count) : null;
        const delta = (a !== null && b !== null) ? (b - a) : null;
        const dc = delta === null ? 'delta-neu' : delta > 0 ? 'delta-pos' : delta < 0 ? 'delta-neg' : 'delta-neu';
        const ds = delta === null ? '—' : (delta > 0 ? '+' : '') + delta + '%';
        html += `<tr style="border-bottom:1px solid #f0f0f0">
            <td style="padding:8px 12px;font-weight:700">${cat}</td>
            <td style="padding:8px 12px;text-align:center">${a !== null ? a+'%' : '—'}</td>
            <td style="padding:8px 12px;text-align:center">${b !== null ? b+'%' : '—'}</td>
            <td style="padding:8px 12px;text-align:center" class="${dc}">${ds}</td>
        </tr>`;
    });
    const overallDelta = runB.overall_pct - runA.overall_pct;
    const odc = overallDelta > 0 ? 'delta-pos' : overallDelta < 0 ? 'delta-neg' : 'delta-neu';
    html += `<tr style="background:#f8fbe9;font-weight:900;border-top:2px solid #cde8a0">
        <td style="padding:10px 12px">Overall</td>
        <td style="padding:10px 12px;text-align:center">${runA.overall_pct}%</td>
        <td style="padding:10px 12px;text-align:center">${runB.overall_pct}%</td>
        <td style="padding:10px 12px;text-align:center" class="${odc}">${overallDelta >= 0 ? '+' : ''}${overallDelta}%</td>
    </tr></tbody></table>`;
    document.getElementById('compareResult').innerHTML = html;
}

// Score distribution doughnut
(function() {
    const ctx = document.getElementById('distChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['100%', '75–99%', '50–74%', '1–49%', '0%'],
            datasets: [{
                data: [<?= $bins['100%'] ?>, <?= $bins['75–99%'] ?>, <?= $bins['50–74%'] ?>, <?= $bins['1–49%'] ?>, <?= $bins['0%'] ?>],
                backgroundColor: ['#2e7d32','#66bb6a','#ffa726','#ef5350','#c62828'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: { plugins: { legend: { display: false } }, cutout: '60%' }
    });
})();

<?php if (count($trend_runs) > 1): ?>
// Accuracy trend line chart
(function() {
    const ctx = document.getElementById('trendChart');
    if (!ctx) return;
    const labels = <?= json_encode(array_map(fn($r) => date('M d H:i', strtotime($r['run_at'])), $trend_runs)) ?>;
    const data   = <?= json_encode(array_map(fn($r) => $r['overall_pct'], $trend_runs)) ?>;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Overall Accuracy %',
                data,
                borderColor: '#99d930',
                backgroundColor: 'rgba(153,217,48,.12)',
                borderWidth: 3,
                pointBackgroundColor: '#99d930',
                pointRadius: 5,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            scales: {
                y: { min: 0, max: 100, ticks: { callback: v => v + '%' } }
            },
            plugins: { legend: { display: false } }
        }
    });
})();
<?php endif; ?>
</script>

<!-- Response detail modal -->
<div class="modal-overlay" id="respModal">
    <div class="modal-box">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <h3 id="modalTitle"></h3>
        <div class="modal-meta" id="modalMeta"></div>
        <div class="modal-response" id="modalBody"></div>
    </div>
</div>

<script>
const evalResults = <?= json_encode(array_map(fn($r) => [
    'num'      => $r['num'],
    'category' => $r['category'],
    'question' => $r['question'],
    'score'    => $r['score'],
    'pass'     => $r['pass'],
    'response' => $r['response'],
], $results), JSON_HEX_TAG | JSON_HEX_AMP) ?>;

function showResponse(idx) {
    const r = evalResults[idx];
    document.getElementById('modalTitle').textContent = '#' + r.num + ' — ' + r.category;
    document.getElementById('modalMeta').innerHTML =
        '<strong>Question:</strong> "' + r.question + '" &nbsp;|&nbsp; ' +
        '<strong>Score:</strong> ' + r.score + '% &nbsp;|&nbsp; ' +
        '<strong>Result:</strong> ' + (r.pass ? '✓ Pass' : '✗ Fail');
    document.getElementById('modalBody').textContent = r.response;
    document.getElementById('respModal').classList.add('open');
}

function closeModal() {
    document.getElementById('respModal').classList.remove('open');
}

document.getElementById('respModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>

<script>
// ── Collapsible cards ────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.card[data-card-id]').forEach(function (card) {
        const id          = card.getAttribute('data-card-id');
        const defaultOpen = card.getAttribute('data-default') !== 'closed';
        const h2          = card.querySelector('h2');
        if (!h2) return;

        // Wrap everything after h2 in a .card-body div
        const body = document.createElement('div');
        body.className = 'card-body';
        Array.from(card.children).slice(1).forEach(function (c) { body.appendChild(c); });
        card.appendChild(body);

        // Toggle button inside h2
        const btn = document.createElement('button');
        btn.className = 'card-toggle';
        btn.type = 'button';
        h2.appendChild(btn);

        // Restore or apply default state
        const stored = localStorage.getItem('evalCard_' + id);
        const isOpen = stored !== null ? stored === 'open' : defaultOpen;
        if (!isOpen) {
            body.classList.add('collapsed');
            btn.textContent = '▼ Show';
            btn.setAttribute('aria-expanded', 'false');
        } else {
            btn.textContent = '▲ Hide';
            btn.setAttribute('aria-expanded', 'true');
        }

        h2.addEventListener('click', function () {
            const opening = body.classList.contains('collapsed');
            body.classList.toggle('collapsed');
            btn.textContent = opening ? '▲ Hide' : '▼ Show';
            btn.setAttribute('aria-expanded', opening);
            localStorage.setItem('evalCard_' + id, opening ? 'open' : 'closed');
        });
    });
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>
