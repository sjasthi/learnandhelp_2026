<?php
/**
 * chat_api.php — Database-driven chat handler for Schools Chat Bot
 *
 * Parses user questions, runs SQL queries against the schools table,
 * and returns formatted responses. No external API needed.
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['reply' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');

if ($userMessage === '') {
    echo json_encode(['reply' => 'Please enter a message.']);
    exit;
}

require_once __DIR__ . '/db_configuration.php';
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['reply' => 'Database connection failed.']);
    exit;
}

$msg = strtolower($userMessage);
$reply = processQuestion($conn, $msg, $userMessage);
$conn->close();

echo json_encode(['reply' => $reply]);
exit;

// ─────────────────────────────────────────────────────────────
// Main dispatcher — matches the question to a handler
// ─────────────────────────────────────────────────────────────
function processQuestion($conn, $msg, $original) {

    // ── Greeting ──
    if (preg_match('/^(hi|hello|hey|greetings|good\s*(morning|afternoon|evening))/', $msg)) {
        return "Hello! I'm the Learn and Help Schools Assistant. You can ask me about the schools we support — try questions like:\n\n"
             . "• How many schools are supported?\n"
             . "• Which schools are in Andhra Pradesh?\n"
             . "• Is [school name] supported?\n"
             . "• How many students are served?";
    }

    // ── Help / what can you do ──
    if (preg_match('/(what can you|help|what do you|how do i|what questions)/', $msg)) {
        return "I can answer questions about Learn and Help schools! Try:\n\n"
             . "• How many schools are supported?\n"
             . "• How many schools are in Proposed/Completed state?\n"
             . "• How many students are served?\n"
             . "• Which schools are in [state name]?\n"
             . "• Is [school name] supported?\n"
             . "• How many schools are supported by PGNF/NRIVA?\n"
             . "• What types of schools are supported?\n"
             . "• Which school has the most students?";
    }

    // ── Thank you ──
    if (preg_match('/^(thanks|thank you|thx)/', $msg)) {
        return "You're welcome! Feel free to ask more questions about our schools.";
    }

    // ── Total school count ──
    if (preg_match('/(how many|total|count).*(school|librar)/', $msg) && !containsFilter($msg)) {
        $r = $conn->query("SELECT COUNT(*) as cnt FROM schools");
        $total = $r->fetch_assoc()['cnt'];
        $r2 = $conn->query("SELECT status, COUNT(*) as cnt FROM schools GROUP BY status ORDER BY cnt DESC");
        $parts = [];
        while ($row = $r2->fetch_assoc()) {
            $parts[] = ($row['status'] ?? 'Unknown') . ": " . $row['cnt'];
        }
        return "Learn and Help currently has $total schools in the program.\n\nBreakdown by status:\n• " . implode("\n• ", $parts);
    }

    // ── Schools by status: Proposed ──
    if (preg_match('/(proposed|pending|suggested|awaiting)/', $msg)) {
        return queryByStatus($conn, 'Proposed');
    }

    // ── Schools by status: Completed ──
    if (preg_match('/(completed|established|approved|active|done)/', $msg) && preg_match('/(school|status|how many|which|list)/', $msg)) {
        return queryByStatus($conn, 'Completed');
    }

    // ── Student count / enrollment ──
    if (preg_match('/(student|enrollment|enrol|served|children|kids)/', $msg)) {
        $r = $conn->query("SELECT SUM(current_enrollment) as total, COUNT(*) as cnt FROM schools WHERE current_enrollment > 0");
        $row = $r->fetch_assoc();
        $total = number_format($row['total'] ?? 0);
        $cnt = $row['cnt'];
        $r2 = $conn->query("SELECT name, current_enrollment FROM schools WHERE current_enrollment > 0 ORDER BY current_enrollment DESC LIMIT 5");
        $top = [];
        while ($s = $r2->fetch_assoc()) {
            $top[] = $s['name'] . " (" . number_format($s['current_enrollment']) . " students)";
        }
        return "Learn and Help schools serve a total of $total students across $cnt schools with reported enrollment.\n\nTop 5 by enrollment:\n• " . implode("\n• ", $top);
    }

    // ── Schools by state ──
    if (preg_match('/(andhra pradesh|telangana|tamil\s*nadu|tamilnadu|karnataka|maharashtra|kerala)/', $msg, $stateMatch)) {
        return queryByState($conn, $stateMatch[1]);
    }
    if (preg_match('/(?:schools?\s+(?:in|from|at)\s+)([a-z\s]+?)(?:\?|$|\.)/i', $msg, $m)) {
        $loc = trim($m[1]);
        if (strlen($loc) > 2 && !preg_match('/^(the |this |our |your )?(program|list|database)$/', $loc)) {
            return queryByLocation($conn, $loc);
        }
    }
    if (preg_match('/(?:in|from|at)\s+(guntur|hyderabad|vijayawada|eluru|kurnool|rayachoti|rajahmundry|chennai|bangalore|mumbai|delhi)/i', $msg, $m)) {
        return queryByLocation($conn, $m[1]);
    }

    // ── How many states ──
    if (preg_match('/(how many|number of)\s*(states|regions)/', $msg)) {
        $r = $conn->query("SELECT state_name, COUNT(*) as cnt FROM schools WHERE state_name IS NOT NULL GROUP BY state_name ORDER BY cnt DESC");
        $states = [];
        while ($row = $r->fetch_assoc()) {
            $states[] = ($row['state_name'] ?? 'Unknown') . " (" . $row['cnt'] . ")";
        }
        return "Learn and Help operates in " . count($states) . " states:\n\n• " . implode("\n• ", $states);
    }

    // ── Supported by PGNF/NRIVA ──
    if (preg_match('/(pgnf|nriva)/', $msg, $m)) {
        $sponsor = strtoupper($m[1]);
        return queryBySupportedBy($conn, $sponsor);
    }
    if (preg_match('/(supported|sponsored|funded)\s*by/', $msg)) {
        $r = $conn->query("SELECT supported_by, COUNT(*) as cnt FROM schools WHERE supported_by IS NOT NULL GROUP BY supported_by ORDER BY cnt DESC");
        $list = [];
        while ($row = $r->fetch_assoc()) {
            $list[] = ($row['supported_by'] ?? 'Unknown') . ": " . $row['cnt'] . " schools";
        }
        return "Schools by supporter:\n\n• " . implode("\n• ", $list);
    }

    // ── School types ──
    if (preg_match('/(type|types|kind|kinds)\s*(of)?\s*(school)?/', $msg)) {
        $r = $conn->query("SELECT type, COUNT(*) as cnt FROM schools WHERE type IS NOT NULL GROUP BY type ORDER BY cnt DESC");
        $types = [];
        while ($row = $r->fetch_assoc()) {
            $types[] = $row['type'] . ": " . $row['cnt'];
        }
        return "Types of schools in the program:\n\n• " . implode("\n• ", $types);
    }

    // ── High schools ──
    if (preg_match('/high\s*school/', $msg) && preg_match('/(which|list|how many|are there)/', $msg)) {
        return queryByType($conn, 'High School');
    }

    // ── Primary schools ──
    if (preg_match('/primary\s*school/', $msg) && preg_match('/(which|list|how many|are there)/', $msg)) {
        return queryByType($conn, 'Primary School');
    }

    // ── Public schools ──
    if (preg_match('/public\s*school/', $msg)) {
        return queryByCategory($conn, 'Public');
    }

    // ── Private schools ──
    if (preg_match('/private\s*school/', $msg)) {
        return queryByCategory($conn, 'Private');
    }

    // ── Largest school / most students ──
    if (preg_match('/(largest|biggest|most\s*(students|enrolled|enrollment))/', $msg)) {
        $r = $conn->query("SELECT name, state_name, current_enrollment, status FROM schools ORDER BY current_enrollment DESC LIMIT 5");
        $list = [];
        while ($row = $r->fetch_assoc()) {
            $list[] = $row['name'] . " — " . number_format($row['current_enrollment']) . " students (" . $row['state_name'] . ", " . $row['status'] . ")";
        }
        return "Largest schools by enrollment:\n\n• " . implode("\n• ", $list);
    }

    // ── Is [school] supported? (specific school lookup) ──
    // This should be near the end as a catch-all for school name searches
    if (preg_match('/(is|does|do|are|about|status\s*of|tell\s*me\s*about|info\s*on|information\s*on|find|search|look\s*up|supported)\b/i', $msg)) {
        return searchSchool($conn, $msg, $original);
    }

    // ── Fallback: try to find a school name anyway ──
    return searchSchool($conn, $msg, $original);
}

// ─────────────────────────────────────────────────────────────
// Helper: check if the message has a filtering keyword
// ─────────────────────────────────────────────────────────────
function containsFilter($msg) {
    return preg_match('/(proposed|completed|pgnf|nriva|andhra|telangana|tamil|high school|primary|public|private)/', $msg);
}

// ─────────────────────────────────────────────────────────────
// Query helpers
// ─────────────────────────────────────────────────────────────
function queryByStatus($conn, $status) {
    $stmt = $conn->prepare("SELECT name, state_name, supported_by FROM schools WHERE status = ? ORDER BY name");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $r = $stmt->get_result();
    $count = $r->num_rows;
    if ($count === 0) {
        $stmt->close();
        return "There are currently no schools with status '$status'.";
    }
    $list = [];
    while ($row = $r->fetch_assoc()) {
        $list[] = $row['name'] . " (" . ($row['state_name'] ?? '') . ")";
    }
    $stmt->close();
    $display = $count <= 20 ? "\n\n• " . implode("\n• ", $list) : "\n\n• " . implode("\n• ", array_slice($list, 0, 20)) . "\n\n...and " . ($count - 20) . " more.";
    return "There are $count schools with status '$status'.$display";
}

function queryByState($conn, $stateInput) {
    $search = '%' . trim($stateInput) . '%';
    $stmt = $conn->prepare("SELECT name, type, status, current_enrollment FROM schools WHERE LOWER(state_name) LIKE ? ORDER BY name");
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $r = $stmt->get_result();
    $count = $r->num_rows;
    if ($count === 0) {
        $stmt->close();
        return "No schools found in '$stateInput'.";
    }
    $list = [];
    while ($row = $r->fetch_assoc()) {
        $enroll = $row['current_enrollment'] > 0 ? ", " . $row['current_enrollment'] . " students" : "";
        $list[] = $row['name'] . " (" . ($row['type'] ?? 'N/A') . ", " . $row['status'] . $enroll . ")";
    }
    $stmt->close();
    $display = $count <= 20 ? "\n\n• " . implode("\n• ", $list) : "\n\n• " . implode("\n• ", array_slice($list, 0, 20)) . "\n\n...and " . ($count - 20) . " more.";
    return "Found $count schools in the '$stateInput' area:$display";
}

function queryByLocation($conn, $location) {
    $search = '%' . trim($location) . '%';
    $stmt = $conn->prepare("SELECT name, state_name, type, status, current_enrollment FROM schools WHERE LOWER(name) LIKE ? OR LOWER(address_text) LIKE ? OR LOWER(state_name) LIKE ? ORDER BY name");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $r = $stmt->get_result();
    $count = $r->num_rows;
    if ($count === 0) {
        $stmt->close();
        return "No schools found in or near '$location'.";
    }
    $list = [];
    while ($row = $r->fetch_assoc()) {
        $enroll = $row['current_enrollment'] > 0 ? ", " . $row['current_enrollment'] . " students" : "";
        $list[] = $row['name'] . " (" . ($row['state_name'] ?? '') . ", " . $row['status'] . $enroll . ")";
    }
    $stmt->close();
    $display = $count <= 20 ? "\n\n• " . implode("\n• ", $list) : "\n\n• " . implode("\n• ", array_slice($list, 0, 20)) . "\n\n...and " . ($count - 20) . " more.";
    return "Found $count schools matching '$location':$display";
}

function queryBySupportedBy($conn, $sponsor) {
    $stmt = $conn->prepare("SELECT name, state_name, status FROM schools WHERE supported_by = ? ORDER BY name");
    $stmt->bind_param("s", $sponsor);
    $stmt->execute();
    $r = $stmt->get_result();
    $count = $r->num_rows;
    if ($count === 0) {
        $stmt->close();
        return "No schools found supported by '$sponsor'.";
    }
    $list = [];
    while ($row = $r->fetch_assoc()) {
        $list[] = $row['name'] . " (" . ($row['state_name'] ?? '') . ", " . $row['status'] . ")";
    }
    $stmt->close();
    $display = $count <= 20 ? "\n\n• " . implode("\n• ", $list) : "\n\n• " . implode("\n• ", array_slice($list, 0, 20)) . "\n\n...and " . ($count - 20) . " more.";
    return "$count schools are supported by $sponsor:$display";
}

function queryByType($conn, $type) {
    $stmt = $conn->prepare("SELECT name, state_name, status, current_enrollment FROM schools WHERE type = ? ORDER BY name");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $r = $stmt->get_result();
    $count = $r->num_rows;
    if ($count === 0) {
        $stmt->close();
        return "No schools of type '$type' found.";
    }
    $list = [];
    while ($row = $r->fetch_assoc()) {
        $list[] = $row['name'] . " (" . ($row['state_name'] ?? '') . ")";
    }
    $stmt->close();
    $display = $count <= 20 ? "\n\n• " . implode("\n• ", $list) : "\n\n• " . implode("\n• ", array_slice($list, 0, 20)) . "\n\n...and " . ($count - 20) . " more.";
    return "There are $count ${type}s in the program:$display";
}

function queryByCategory($conn, $category) {
    $stmt = $conn->prepare("SELECT name, state_name, type, status FROM schools WHERE category = ? ORDER BY name");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $r = $stmt->get_result();
    $count = $r->num_rows;
    if ($count === 0) {
        $stmt->close();
        return "No $category schools found in the program.";
    }
    $list = [];
    while ($row = $r->fetch_assoc()) {
        $list[] = $row['name'] . " (" . ($row['type'] ?? 'N/A') . ", " . ($row['state_name'] ?? '') . ")";
    }
    $stmt->close();
    $display = $count <= 20 ? "\n\n• " . implode("\n• ", $list) : "\n\n• " . implode("\n• ", array_slice($list, 0, 20)) . "\n\n...and " . ($count - 20) . " more.";
    return "There are $count $category schools in the program:$display";
}

// ─────────────────────────────────────────────────────────────
// School name search — handles abbreviations and fuzzy matching
// ─────────────────────────────────────────────────────────────
function searchSchool($conn, $msg, $original) {
    // Common abbreviations
    $abbreviations = [
        'mvhs' => 'Mounds View High School',
        'mv high' => 'Mounds View High School',
        'mounds view' => 'Mounds View High School',
        'zphs' => 'Z P H School',
        'zph' => 'Z P H School',
        'mpps' => 'MPPS',
        'mpup' => 'M P U P',
        'mpp' => 'MPP',
    ];

    // Check abbreviations first
    foreach ($abbreviations as $abbr => $fullName) {
        if (strpos($msg, $abbr) !== false) {
            // Search for the full name
            $search = '%' . $fullName . '%';
            $stmt = $conn->prepare("SELECT name, state_name, type, category, status, supported_by, current_enrollment, contact_name FROM schools WHERE name LIKE ?");
            $stmt->bind_param("s", $search);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r->num_rows > 0) {
                return formatSchoolResults($r, $fullName);
            }
            $stmt->close();
            // If the abbreviation maps to a known name but it's not in DB
            return "'$fullName' is not currently in the Learn and Help program. Our schools are primarily located in India, across states like Andhra Pradesh, Telangana, and Tamil Nadu.";
        }
    }

    // Extract potential school name from the question
    $cleanMsg = preg_replace('/\b(is|does|do|are|has|have|was|were|the|a|an|this|that|school|supported|support|in|by|learn\s*and\s*help|program|status|of|about|tell|me|info|information|on|find|search|look|up|for|what|please|can|you|check)\b/i', ' ', $original);
    $cleanMsg = preg_replace('/[?\.\!,]/', '', $cleanMsg);
    $cleanMsg = preg_replace('/\s+/', ' ', trim($cleanMsg));

    if (strlen($cleanMsg) < 2) {
        return "I'm not sure what you're asking. Try questions like:\n\n"
             . "• How many schools are supported?\n"
             . "• Is [school name] in the program?\n"
             . "• Which schools are in Andhra Pradesh?\n"
             . "• How many students are served?";
    }

    // Search by name, address, contact
    $search = '%' . $cleanMsg . '%';
    $stmt = $conn->prepare("SELECT name, state_name, type, category, status, supported_by, current_enrollment, contact_name FROM schools WHERE name LIKE ? OR address_text LIKE ? OR contact_name LIKE ? LIMIT 10");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $r = $stmt->get_result();

    if ($r->num_rows > 0) {
        return formatSchoolResults($r, $cleanMsg);
    }
    $stmt->close();

    // Try searching individual words (at least 3 chars)
    $words = array_filter(explode(' ', $cleanMsg), function($w) { return strlen($w) >= 3; });
    if (!empty($words)) {
        foreach ($words as $word) {
            $search = '%' . $word . '%';
            $stmt = $conn->prepare("SELECT name, state_name, type, category, status, supported_by, current_enrollment, contact_name FROM schools WHERE name LIKE ? LIMIT 10");
            $stmt->bind_param("s", $search);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r->num_rows > 0) {
                return formatSchoolResults($r, $word);
            }
            $stmt->close();
        }
    }

    return "I couldn't find any schools matching your query. Try:\n\n"
         . "• Searching by school name (e.g., 'Is Chethana supported?')\n"
         . "• Searching by state (e.g., 'Schools in Telangana')\n"
         . "• Asking about totals (e.g., 'How many schools are supported?')";
}

function formatSchoolResults($result, $searchTerm) {
    $schools = [];
    while ($row = $result->fetch_assoc()) {
        $schools[] = $row;
    }

    if (count($schools) === 1) {
        $s = $schools[0];
        $enroll = $s['current_enrollment'] > 0 ? number_format($s['current_enrollment']) . " students" : "Not reported";
        return "Yes! " . $s['name'] . " is in the Learn and Help program.\n\n"
             . "Details:\n"
             . "• State: " . ($s['state_name'] ?? 'N/A') . "\n"
             . "• Type: " . ($s['type'] ?? 'N/A') . "\n"
             . "• Category: " . ($s['category'] ?? 'N/A') . "\n"
             . "• Status: " . ($s['status'] ?? 'N/A') . "\n"
             . "• Supported by: " . ($s['supported_by'] ?? 'N/A') . "\n"
             . "• Enrollment: " . $enroll . "\n"
             . "• Contact: " . ($s['contact_name'] ?? 'N/A');
    }

    $reply = "Found " . count($schools) . " schools matching '$searchTerm':\n";
    foreach ($schools as $s) {
        $reply .= "\n• " . $s['name'] . " — " . ($s['state_name'] ?? '') . ", " . $s['status'] . " (" . ($s['supported_by'] ?? '') . ")";
    }
    return $reply;
}
