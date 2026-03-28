<?php
/**
 * chat_api.php — Database-driven chat handler for Schools Chat Bot
 *
 * Parses user questions, runs SQL queries against the schools table,
 * and returns formatted responses. No external API needed.
 */

// Allow this file to be included by other pages (e.g. evaluation page)
// without triggering the HTTP request handler.
if (!defined('CHAT_API_INCLUDED')) {
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

    // Ensure chat_log table exists (with rating column)
    $conn->query("CREATE TABLE IF NOT EXISTS chat_log (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        asked_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
        question    TEXT NOT NULL,
        handler     VARCHAR(60),
        response    TEXT,
        response_ms INT,
        rating      TINYINT DEFAULT NULL
    )");
    // Add rating column to existing tables that predate this column
    $rc = $conn->query("SHOW COLUMNS FROM chat_log LIKE 'rating'");
    if ($rc && $rc->num_rows === 0) {
        $conn->query("ALTER TABLE chat_log ADD COLUMN rating TINYINT DEFAULT NULL");
    }

    // ── Check mode + API key ──────────────────────────────────────────────────
    $llm_key = ''; $llm_temp = 0.2; $llm_topp = 0.9; $llm_tokens = 300;
    $llm_keywords = ''; $llm_mode = 'rule';
    $pref_r = $conn->query("SELECT Preference_Name, Value FROM preferences
        WHERE Preference_Name IN ('ANTHROPIC_API_KEY','KEYWORDS','CHATBOT_TEMPERATURE','CHATBOT_TOP_P','CHATBOT_MAX_TOKENS','CHATBOT_MODE')");
    if ($pref_r) {
        while ($pr = $pref_r->fetch_assoc()) {
            switch ($pr['Preference_Name']) {
                case 'ANTHROPIC_API_KEY':   $llm_key      = trim($pr['Value']);   break;
                case 'KEYWORDS':            $llm_keywords  = $pr['Value'];        break;
                case 'CHATBOT_TEMPERATURE': $llm_temp      = (float)$pr['Value']; break;
                case 'CHATBOT_TOP_P':       $llm_topp      = (float)$pr['Value']; break;
                case 'CHATBOT_MAX_TOKENS':  $llm_tokens    = (int)$pr['Value'];   break;
                case 'CHATBOT_MODE':        $llm_mode      = $pr['Value'];        break;
            }
        }
    }
    $use_llm = ($llm_mode === 'llm') && ($llm_key !== '' && $llm_key !== 'test' && $llm_key !== 'key');

    if ($use_llm) {
        // LLM mode: proxy to Anthropic Claude via cURL so the API key never touches the browser
        $system_prompt = 'You are a helpful assistant for the Learn and Help school support program. Only answer questions related to the program and its supported schools.'
            . ($llm_keywords !== '' ? ' Context: ' . $llm_keywords : '');
        $payload = json_encode([
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => $llm_tokens,
            'system'     => $system_prompt,
            'messages'   => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ]);
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $llm_key,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT        => 20,
        ]);
        $start    = microtime(true);
        $raw      = curl_exec($ch);
        $ms       = (int) round((microtime(true) - $start) * 1000);
        $curl_err = curl_errno($ch);
        curl_close($ch);

        if (!$curl_err && $raw) {
            $oai = json_decode($raw, true);
            if (isset($oai['content'][0]['text'])) {
                $reply = $oai['content'][0]['text'];
            } elseif (isset($oai['error']['message'])) {
                // Anthropic returned an API error (auth, quota, bad request, etc.)
                $reply = 'AI error: ' . $oai['error']['message'];
            } else {
                $reply = 'Sorry, I could not get a response. Please try again.';
            }
        } else {
            $reply = 'The AI service is currently unavailable. Please try again later.';
        }
        $handler = 'LLM';
        $log_id  = 0;
        $stmt = $conn->prepare("INSERT INTO chat_log (question, handler, response, response_ms) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssi", $userMessage, $handler, $reply, $ms);
            $stmt->execute();
            $log_id = (int) $conn->insert_id;
            $stmt->close();
        }
        $conn->close();
        echo json_encode(['reply' => '<p>' . htmlspecialchars($reply, ENT_QUOTES, 'UTF-8') . '</p>', 'log_id' => $log_id]);
        exit;
    }

    // ── Rule-based mode (no API key configured) ───────────────────────────────
    $start  = microtime(true);
    $result = get_chat_response($conn, $userMessage);
    $ms     = (int) round((microtime(true) - $start) * 1000);

    // Log question to DB
    $log_id = 0;
    $stmt = $conn->prepare("INSERT INTO chat_log (question, handler, response, response_ms) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssi", $userMessage, $result['handler'], $result['reply'], $ms);
        $stmt->execute();
        $log_id = (int) $conn->insert_id;
        $stmt->close();
    }

    $conn->close();

    echo json_encode(['reply' => formatResponseHtml($result['reply']), 'log_id' => $log_id]);
    exit;
}

// ── Callable wrapper for internal use (e.g. evaluation page) ──────────────
function get_chat_response($conn, $message) {
    $handler = 'Unknown';
    $reply   = processQuestion($conn, strtolower($message), $message, $handler);
    return ['reply' => $reply, 'handler' => $handler];
}

// ─────────────────────────────────────────────────────────────────────────────
// Convert plain-text bullet responses to HTML for the chat UI.
// The evaluation page uses get_chat_response() directly (plain text), so
// keyword matching is unaffected by this formatting step.
// ─────────────────────────────────────────────────────────────────────────────
function formatResponseHtml($text) {
    $lines  = explode("\n", $text);
    $html   = '';
    $inList = false;
    foreach ($lines as $line) {
        $trimmed = trim($line);
        // Bullet lines start with the • character (U+2022) followed by a space
        if (strpos($trimmed, '• ') === 0) {
            if (!$inList) { $html .= '<ul>'; $inList = true; }
            $content = htmlspecialchars(mb_substr($trimmed, 2), ENT_QUOTES, 'UTF-8');
            $html .= '<li>' . $content . '</li>';
        } else {
            if ($inList) { $html .= '</ul>'; $inList = false; }
            if ($trimmed !== '') {
                $html .= '<p>' . htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8') . '</p>';
            }
        }
    }
    if ($inList) $html .= '</ul>';
    return $html;
}

// ─────────────────────────────────────────────────────────────────────────────
// Main dispatcher — matches the question to a handler
// $handler is set by reference so callers know which branch matched.
// ─────────────────────────────────────────────────────────────────────────────
function processQuestion($conn, $msg, $original, &$handler = null) {

    // ── Greeting ──────────────────────────────────────────────────────────────
    if (preg_match('/^(hi|hello|hey|greetings|good\s*(morning|afternoon|evening))/', $msg)) {
        $handler = 'Greeting';
        return "Hello! I'm the Learn and Help Schools Assistant. You can ask me about the schools we support — try questions like:\n\n"
             . "• How many schools are supported?\n"
             . "• Which schools are in Andhra Pradesh?\n"
             . "• Is [school name] supported?\n"
             . "• How many students are served?";
    }

    // ── Thank you ─────────────────────────────────────────────────────────────
    if (preg_match('/^(thanks|thank you|thx)/', $msg)) {
        $handler = 'ThankYou';
        return "You're welcome! Feel free to ask more questions about our schools.";
    }

    // ── Help / what can you do ────────────────────────────────────────────────
    if (preg_match('/(what can you|help|what do you|how do i|what questions)/', $msg)) {
        $handler = 'Help';
        return "I can answer questions about Learn and Help schools! Try:\n\n"
             . "• How many schools are supported?\n"
             . "• How many schools are in Proposed/Completed state?\n"
             . "• How many students are served?\n"
             . "• Which schools are in [state name]?\n"
             . "• Is [school name] supported?\n"
             . "• How many schools are supported by PGNF/NRIVA?\n"
             . "• What types of schools are supported?\n"
             . "• Which school has the most students?\n"
             . "• List all schools\n"
             . "• Show recently added schools";
    }

    // ── List ALL schools ──────────────────────────────────────────────────────
    if (preg_match('/(list all|show all|all schools|every school|view all|see all)/', $msg)) {
        $handler = 'ListAll';
        $r     = $conn->query("SELECT name, state_name, supported_by FROM schools WHERE status = 'Completed' ORDER BY name");
        $count = $r->num_rows;
        if ($count === 0) return "No active schools found in the program.";
        $list  = [];
        while ($row = $r->fetch_assoc()) {
            $list[] = $row['name'] . " (" . ($row['state_name'] ?? 'N/A') . ", " . ($row['supported_by'] ?? 'N/A') . ")";
        }
        $display = $count <= 30
            ? "\n\n• " . implode("\n• ", $list)
            : "\n\n• " . implode("\n• ", array_slice($list, 0, 30)) . "\n\n...and " . ($count - 30) . " more. Ask about a specific state for a focused list.";
        return "There are $count active schools in the Learn and Help program:$display";
    }

    // ── Recently added schools ────────────────────────────────────────────────
    if (preg_match('/(recent|newest|latest|new school|just added|last added|newly)/', $msg)) {
        $handler = 'RecentlyAdded';
        $r = $conn->query("SELECT name, state_name, type, current_enrollment FROM schools WHERE status = 'Completed' ORDER BY id DESC LIMIT 5");
        if (!$r || $r->num_rows === 0) {
            return "I'm not able to determine which schools were most recently added. Try asking about schools in a specific state.";
        }
        $list = [];
        while ($row = $r->fetch_assoc()) {
            $enroll = $row['current_enrollment'] > 0 ? ", " . number_format($row['current_enrollment']) . " students" : "";
            $list[] = $row['name'] . " (" . ($row['state_name'] ?? 'N/A') . $enroll . ")";
        }
        return "Most recently added schools:\n\n• " . implode("\n• ", $list);
    }

    // ── Total school count ────────────────────────────────────────────────────
    if (preg_match('/(how many|total|count|number of).*(school|librar)/', $msg) && !containsFilter($msg)) {
        $handler = 'TotalCount';
        $r       = $conn->query("SELECT COUNT(*) as cnt FROM schools WHERE status = 'Completed'");
        $total   = $r->fetch_assoc()['cnt'];
        $r2      = $conn->query("SELECT COUNT(*) as cnt FROM schools WHERE status = 'Proposed'");
        $proposed = $r2->fetch_assoc()['cnt'];
        $extra   = $proposed > 0 ? " with $proposed additional schools nominated and under review." : ".";
        return "Learn and Help currently supports $total active schools in the program$extra";
    }

    // ── Schools by status: Proposed ───────────────────────────────────────────
    if (preg_match('/(proposed|pending|suggested|awaiting)/', $msg)) {
        $handler = 'Proposed';
        $r   = $conn->query("SELECT COUNT(*) as cnt FROM schools WHERE status = 'Proposed'");
        $cnt = $r->fetch_assoc()['cnt'];
        return $cnt > 0
            ? "There are currently $cnt schools nominated and awaiting admin review."
            : "There are currently no schools awaiting review.";
    }

    // ── Schools by status: Completed ──────────────────────────────────────────
    if (preg_match('/(completed|established|approved|active|done)/', $msg) && preg_match('/(school|status|how many|which|list)/', $msg)) {
        $handler = 'Completed';
        return queryByStatus($conn, 'Completed');
    }

    // ── Schools missing data (checked before Enrollment to avoid keyword collision) ──
    if (preg_match('/(missing|no|without|lack|empty)\s*(enrollment|contact|data|information)/', $msg) ||
        preg_match('/(no enrollment|no contact|no data|missing data|unreported)/', $msg)) {
        $handler = 'MissingData';
        if (preg_match('/contact/', $msg)) {
            $r     = $conn->query("SELECT name, state_name FROM schools WHERE (contact_name IS NULL OR contact_name = '') AND status = 'Completed' ORDER BY name LIMIT 20");
            $label = "no contact information";
        } else {
            $r     = $conn->query("SELECT name, state_name FROM schools WHERE (current_enrollment IS NULL OR current_enrollment = 0) AND status = 'Completed' ORDER BY name LIMIT 20");
            $label = "no enrollment data reported";
        }
        $count = $r->num_rows;
        if ($count === 0) return "All active schools have the requested data on file.";
        $list = [];
        while ($row = $r->fetch_assoc()) {
            $list[] = $row['name'] . " (" . ($row['state_name'] ?? 'N/A') . ")";
        }
        $more = $count >= 20 ? "\n\nShowing first 20 results." : "";
        return "Found $count schools with $label:\n\n• " . implode("\n• ", $list) . $more;
    }

    // ── Student count / enrollment ────────────────────────────────────────────
    if (preg_match('/(student|enrollment|enrol|served|children|kids)/', $msg)) {
        $handler = 'Enrollment';
        $r   = $conn->query("SELECT SUM(current_enrollment) as total, COUNT(*) as cnt FROM schools WHERE current_enrollment > 0 AND status = 'Completed'");
        $row = $r->fetch_assoc();
        $total = number_format($row['total'] ?? 0);
        $cnt   = $row['cnt'];
        $r2    = $conn->query("SELECT name, current_enrollment FROM schools WHERE current_enrollment > 0 AND status = 'Completed' ORDER BY current_enrollment DESC LIMIT 5");
        $top   = [];
        while ($s = $r2->fetch_assoc()) {
            $top[] = $s['name'] . " (" . number_format($s['current_enrollment']) . " students)";
        }
        return "Learn and Help schools serve a total of $total students across $cnt schools with reported enrollment.\n\nTop 5 by enrollment:\n• " . implode("\n• ", $top);
    }

    // ── Schools by state (direct match) ──────────────────────────────────────
    if (preg_match('/(andhra pradesh|telangana|tamil\s*nadu|tamilnadu|karnataka|maharashtra|kerala|odisha|gujarat|rajasthan|west bengal|uttar pradesh|bihar|punjab|haryana|madhya pradesh|chhattisgarh|jharkhand|assam)/', $msg, $stateMatch)) {
        $handler = 'ByState';
        return queryByState($conn, $stateMatch[1]);
    }

    // ── Schools by state (fuzzy — handles typos like "Andhara Pradesh") ──────
    $knownStates = [
        'andhra pradesh', 'telangana', 'tamil nadu', 'karnataka', 'maharashtra',
        'kerala', 'odisha', 'gujarat', 'rajasthan', 'west bengal', 'uttar pradesh',
        'bihar', 'punjab', 'haryana', 'madhya pradesh', 'chhattisgarh', 'jharkhand', 'assam',
    ];
    $words = preg_split('/\s+/', $msg);
    foreach ($knownStates as $state) {
        if (strpos($state, ' ') === false) {
            // Single-word state: compare against individual words
            foreach ($words as $word) {
                if (strlen($word) >= 5) {
                    similar_text($word, $state, $pct);
                    if ($pct >= 80) { $handler = 'ByState(fuzzy)'; return queryByState($conn, $state); }
                }
            }
        } else {
            // Multi-word state: compare against bigrams
            for ($i = 0; $i < count($words) - 1; $i++) {
                $bigram = $words[$i] . ' ' . $words[$i + 1];
                similar_text($bigram, $state, $pct);
                if ($pct >= 82) { $handler = 'ByState(fuzzy)'; return queryByState($conn, $state); }
            }
        }
    }

    if (preg_match('/(?:schools?\s+(?:in|from|at)\s+)([a-z\s]+?)(?:\?|$|\.)/i', $msg, $m)) {
        $loc = trim($m[1]);
        if (strlen($loc) > 2 && !preg_match('/^(the |this |our |your )?(program|list|database)$/', $loc)) {
            $handler = 'ByLocation';
            return queryByLocation($conn, $loc);
        }
    }
    if (preg_match('/(?:in|from|at)\s+(guntur|hyderabad|vijayawada|eluru|kurnool|rayachoti|rajahmundry|chennai|bangalore|mumbai|delhi)/i', $msg, $m)) {
        $handler = 'ByLocation';
        return queryByLocation($conn, $m[1]);
    }

    // ── How many states ───────────────────────────────────────────────────────
    if (preg_match('/(how many|number of)\s*(states|regions)/', $msg)) {
        $handler = 'StateCount';
        $r      = $conn->query("SELECT state_name, COUNT(*) as cnt FROM schools WHERE state_name IS NOT NULL AND status = 'Completed' GROUP BY state_name ORDER BY cnt DESC");
        $states = [];
        while ($row = $r->fetch_assoc()) {
            $states[] = ($row['state_name'] ?? 'Unknown') . " (" . $row['cnt'] . ")";
        }
        return "Learn and Help operates in " . count($states) . " states:\n\n• " . implode("\n• ", $states);
    }

    // ── Supported by — dynamic DB lookup + fuzzy word matching ───────────────
    $supp_r = $conn->query("SELECT DISTINCT supported_by FROM schools WHERE supported_by IS NOT NULL AND supported_by != '' ORDER BY supported_by");
    $all_supporters = [];
    while ($sr = $supp_r->fetch_assoc()) $all_supporters[] = $sr['supported_by'];

    // Exact case-insensitive match of any supporter name in the message
    foreach ($all_supporters as $sup) {
        if (stripos($msg, $sup) !== false) {
            $handler = 'SupportedBy';
            return queryBySupportedBy($conn, $sup);
        }
    }

    // Fuzzy: each word in the message vs. each supporter (levenshtein for short, similar_text for long)
    $best_sup = null;
    $best_lev = PHP_INT_MAX;
    foreach (preg_split('/\s+/', $msg) as $word) {
        if (strlen($word) < 3) continue;
        foreach ($all_supporters as $sup) {
            $wl = strtolower($word);
            $sl = strtolower($sup);
            if ($wl === $sl) continue; // exact already handled
            if (strlen($sl) <= 6) {
                // Short supporter names: levenshtein ≤ 1
                if (abs(strlen($wl) - strlen($sl)) <= 1) {
                    $d = levenshtein($wl, $sl);
                    if ($d <= 1 && $d < $best_lev) { $best_lev = $d; $best_sup = $sup; }
                }
            } else {
                // Longer names: similar_text ≥ 80%
                similar_text($wl, $sl, $pct);
                if ($pct >= 80 && $best_sup === null) $best_sup = $sup;
            }
        }
    }
    if ($best_sup !== null) {
        $handler = 'SupportedBy';
        return queryBySupportedBy($conn, $best_sup);
    }

    if (preg_match('/(supported|sponsored|funded)\s*by/', $msg)) {
        $handler = 'SupportedBy';
        $r    = $conn->query("SELECT supported_by, COUNT(*) as cnt FROM schools WHERE supported_by IS NOT NULL AND status = 'Completed' GROUP BY supported_by ORDER BY cnt DESC");
        $list = [];
        while ($row = $r->fetch_assoc()) {
            $list[] = ($row['supported_by'] ?? 'Unknown') . ": " . $row['cnt'] . " schools";
        }
        return "Schools by supporter:\n\n• " . implode("\n• ", $list);
    }

    // ── School types ──────────────────────────────────────────────────────────
    if (preg_match('/(type|types|kind|kinds)\s*(of)?\s*(school)?/', $msg)) {
        $handler = 'SchoolType';
        $r     = $conn->query("SELECT type, COUNT(*) as cnt FROM schools WHERE type IS NOT NULL AND status = 'Completed' GROUP BY type ORDER BY cnt DESC");
        $types = [];
        while ($row = $r->fetch_assoc()) {
            $types[] = $row['type'] . ": " . $row['cnt'];
        }
        return "Types of schools in the program:\n\n• " . implode("\n• ", $types);
    }

    // ── High schools ──────────────────────────────────────────────────────────
    if (preg_match('/high\s*school/', $msg) && preg_match('/(which|list|how many|are there|show|find|get|all)/', $msg)) {
        $handler = 'HighSchool';
        return queryByType($conn, 'High School');
    }

    // ── Primary schools ───────────────────────────────────────────────────────
    if (preg_match('/primary\s*school/', $msg) && preg_match('/(which|list|how many|are there|show|find|get|all)/', $msg)) {
        $handler = 'PrimarySchool';
        return queryByType($conn, 'Primary School');
    }

    // ── Public schools ────────────────────────────────────────────────────────
    if (preg_match('/public\s*school/', $msg)) {
        $handler = 'PublicSchool';
        return queryByCategory($conn, 'Public');
    }

    // ── Private schools ───────────────────────────────────────────────────────
    if (preg_match('/private\s*school/', $msg)) {
        $handler = 'PrivateSchool';
        return queryByCategory($conn, 'Private');
    }

    // ── Largest school / most students ───────────────────────────────────────
    if (preg_match('/(largest|biggest|most\s*(students|enrolled|enrollment))/', $msg)) {
        $handler = 'Largest';
        $r    = $conn->query("SELECT name, state_name, current_enrollment FROM schools WHERE status = 'Completed' AND current_enrollment > 0 ORDER BY current_enrollment DESC LIMIT 5");
        $list = [];
        while ($row = $r->fetch_assoc()) {
            $list[] = $row['name'] . " — " . number_format($row['current_enrollment']) . " students (" . ($row['state_name'] ?? 'N/A') . ")";
        }
        return "Largest schools by enrollment:\n\n• " . implode("\n• ", $list);
    }

    // ── Smallest school / fewest students ────────────────────────────────────
    if (preg_match('/(smallest|fewest|least\s*students|minimum\s*enrollment|school with (the )?fewest)/', $msg)) {
        $handler = 'Smallest';
        $r    = $conn->query("SELECT name, state_name, current_enrollment FROM schools WHERE status = 'Completed' AND current_enrollment > 0 ORDER BY current_enrollment ASC LIMIT 5");
        $list = [];
        while ($row = $r->fetch_assoc()) {
            $list[] = $row['name'] . " — " . number_format($row['current_enrollment']) . " students (" . ($row['state_name'] ?? 'N/A') . ")";
        }
        if (empty($list)) return "No schools with reported enrollment found.";
        return "Smallest schools by enrollment:\n\n• " . implode("\n• ", $list);
    }

    // ── Is [school] supported? (specific school lookup) ───────────────────────
    if (preg_match('/(is|does|do|are|about|status\s*of|tell\s*me\s*about|info\s*on|information\s*on|find|search|look\s*up|supported)\b/i', $msg)) {
        $handler = 'SchoolLookup';
        return searchSchool($conn, $msg, $original);
    }

    // ── Fallback: try school name search, then did-you-mean ───────────────────
    $handler = 'Fallback';
    return searchSchool($conn, $msg, $original);
}

// ─────────────────────────────────────────────────────────────────────────────
// Helper: check if the message has a filtering keyword
// ─────────────────────────────────────────────────────────────────────────────
function containsFilter($msg) {
    return preg_match('/(proposed|completed|pgnf|nriva|andhra|telangana|tamil|high school|primary|public|private)/', $msg);
}

// ─────────────────────────────────────────────────────────────────────────────
// Query helpers
// ─────────────────────────────────────────────────────────────────────────────
function queryByStatus($conn, $status) {
    $stmt = $conn->prepare("SELECT name, state_name, supported_by FROM schools WHERE status = ? ORDER BY name");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $r     = $stmt->get_result();
    $count = $r->num_rows;
    if ($count === 0) { $stmt->close(); return "There are currently no schools with status '$status'."; }
    $list  = [];
    while ($row = $r->fetch_assoc()) {
        $list[] = $row['name'] . " (" . ($row['state_name'] ?? '') . ")";
    }
    $stmt->close();
    $display = $count <= 20 ? "\n\n• " . implode("\n• ", $list) : "\n\n• " . implode("\n• ", array_slice($list, 0, 20)) . "\n\n...and " . ($count - 20) . " more.";
    return "There are $count schools with status '$status'.$display";
}

function queryByState($conn, $stateInput) {
    $search = '%' . trim($stateInput) . '%';
    $stmt   = $conn->prepare("SELECT name, type, status, current_enrollment FROM schools WHERE LOWER(state_name) LIKE ? AND status = 'Completed' ORDER BY name");
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $r     = $stmt->get_result();
    $count = $r->num_rows;
    if ($count === 0) { $stmt->close(); return "No schools found in '$stateInput'."; }
    $list  = [];
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
    $stmt   = $conn->prepare("SELECT name, state_name, type, status, current_enrollment FROM schools WHERE (LOWER(name) LIKE ? OR LOWER(address_text) LIKE ? OR LOWER(state_name) LIKE ?) AND status = 'Completed' ORDER BY name");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $r     = $stmt->get_result();
    $count = $r->num_rows;
    if ($count === 0) { $stmt->close(); return "No schools found in or near '$location'."; }
    $list  = [];
    while ($row = $r->fetch_assoc()) {
        $enroll = $row['current_enrollment'] > 0 ? ", " . $row['current_enrollment'] . " students" : "";
        $list[] = $row['name'] . " (" . ($row['state_name'] ?? '') . ", " . $row['status'] . $enroll . ")";
    }
    $stmt->close();
    $display = $count <= 20 ? "\n\n• " . implode("\n• ", $list) : "\n\n• " . implode("\n• ", array_slice($list, 0, 20)) . "\n\n...and " . ($count - 20) . " more.";
    return "Found $count schools matching '$location':$display";
}

function queryBySupportedBy($conn, $sponsor) {
    $stmt = $conn->prepare("SELECT name, state_name, status FROM schools WHERE supported_by = ? AND status = 'Completed' ORDER BY name");
    $stmt->bind_param("s", $sponsor);
    $stmt->execute();
    $r     = $stmt->get_result();
    $count = $r->num_rows;
    if ($count === 0) { $stmt->close(); return "No schools found supported by '$sponsor'."; }
    $list  = [];
    while ($row = $r->fetch_assoc()) {
        $list[] = $row['name'] . " (" . ($row['state_name'] ?? '') . ", " . $row['status'] . ")";
    }
    $stmt->close();
    $display = $count <= 20 ? "\n\n• " . implode("\n• ", $list) : "\n\n• " . implode("\n• ", array_slice($list, 0, 20)) . "\n\n...and " . ($count - 20) . " more.";
    return "$count schools are supported by $sponsor:$display";
}

function queryByType($conn, $type) {
    $stmt = $conn->prepare("SELECT name, state_name, status, current_enrollment FROM schools WHERE type = ? AND status = 'Completed' ORDER BY name");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $r     = $stmt->get_result();
    $count = $r->num_rows;
    if ($count === 0) { $stmt->close(); return "No schools of type '$type' found."; }
    $list  = [];
    while ($row = $r->fetch_assoc()) {
        $list[] = $row['name'] . " (" . ($row['state_name'] ?? '') . ")";
    }
    $stmt->close();
    $display = $count <= 20 ? "\n\n• " . implode("\n• ", $list) : "\n\n• " . implode("\n• ", array_slice($list, 0, 20)) . "\n\n...and " . ($count - 20) . " more.";
    return "There are $count " . $type . "s in the program:$display";
}

function queryByCategory($conn, $category) {
    $stmt = $conn->prepare("SELECT name, state_name, type, status FROM schools WHERE category = ? AND status = 'Completed' ORDER BY name");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $r     = $stmt->get_result();
    $count = $r->num_rows;
    if ($count === 0) { $stmt->close(); return "No $category schools found in the program."; }
    $list  = [];
    while ($row = $r->fetch_assoc()) {
        $list[] = $row['name'] . " (" . ($row['type'] ?? 'N/A') . ", " . ($row['state_name'] ?? '') . ")";
    }
    $stmt->close();
    $display = $count <= 20 ? "\n\n• " . implode("\n• ", $list) : "\n\n• " . implode("\n• ", array_slice($list, 0, 20)) . "\n\n...and " . ($count - 20) . " more.";
    return "There are $count $category schools in the program:$display";
}

// ─────────────────────────────────────────────────────────────────────────────
// School name search — handles abbreviations and fuzzy word matching
// ─────────────────────────────────────────────────────────────────────────────
function searchSchool($conn, $msg, $original) {
    // Common abbreviations
    $abbreviations = [
        'mvhs'        => 'Mounds View High School',
        'mv high'     => 'Mounds View High School',
        'mounds view' => 'Mounds View High School',
        'zphs'        => 'Z P H School',
        'z.p.h.s'     => 'Z P H School',
        'zph'         => 'Z P H School',
        'mpps'        => 'MPPS',
        'mpup'        => 'M P U P',
        'mpp'         => 'MPP',
        'kgbv'        => 'KGBV',
        'zppss'       => 'ZPPSS',
        'mps'         => 'MPS',
        'ghs'         => 'Government High School',
        'ghss'        => 'Government Higher Secondary School',
        'govt high'   => 'Government High School',
    ];

    foreach ($abbreviations as $abbr => $fullName) {
        if (strpos($msg, $abbr) !== false) {
            $search = '%' . $fullName . '%';
            $stmt   = $conn->prepare("SELECT name, state_name, type, category, status, supported_by, current_enrollment, contact_name FROM schools WHERE name LIKE ? AND status = 'Completed'");
            $stmt->bind_param("s", $search);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r->num_rows > 0) return formatSchoolResults($r, $fullName);
            $stmt->close();
            return "'$fullName' is not currently in the Learn and Help program. Our schools are primarily located in India, across states like Andhra Pradesh, Telangana, and Tamil Nadu.";
        }
    }

    // Strip common words to isolate the school name
    $cleanMsg = preg_replace('/\b(is|does|do|are|has|have|was|were|the|a|an|this|that|school|supported|support|in|by|learn\s*and\s*help|program|status|of|about|tell|me|info|information|on|find|search|look|up|for|what|please|can|you|check)\b/i', ' ', $original);
    $cleanMsg = preg_replace('/[?\.\!,]/', '', $cleanMsg);
    $cleanMsg = preg_replace('/\s+/', ' ', trim($cleanMsg));

    if (strlen($cleanMsg) < 2) {
        return suggestDidYouMean($msg);
    }

    // Full phrase search
    $search = '%' . $cleanMsg . '%';
    $stmt   = $conn->prepare("SELECT name, state_name, type, category, status, supported_by, current_enrollment, contact_name FROM schools WHERE (name LIKE ? OR address_text LIKE ? OR contact_name LIKE ?) AND status = 'Completed' LIMIT 10");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r->num_rows > 0) return formatSchoolResults($r, $cleanMsg);
    $stmt->close();

    // Word-by-word search (words ≥ 3 chars)
    $words = array_filter(explode(' ', $cleanMsg), function($w) { return strlen($w) >= 3; });
    foreach ($words as $word) {
        $search = '%' . $word . '%';
        $stmt   = $conn->prepare("SELECT name, state_name, type, category, status, supported_by, current_enrollment, contact_name FROM schools WHERE name LIKE ? AND status = 'Completed' LIMIT 10");
        $stmt->bind_param("s", $search);
        $stmt->execute();
        $r = $stmt->get_result();
        if ($r->num_rows > 0) return formatSchoolResults($r, $word);
        $stmt->close();
    }

    // ── Levenshtein fuzzy school name match (catches typos like "Chethna") ───
    $stopWords = ['school', 'learn', 'help', 'program', 'supported', 'about', 'find'];
    $fuzzyWords = array_values(array_filter(
        array_map('strtolower', explode(' ', $cleanMsg)),
        fn($w) => strlen($w) >= 4 && !in_array($w, $stopWords)
    ));
    if (!empty($fuzzyWords)) {
        $nr = $conn->query("SELECT name FROM schools WHERE status = 'Completed' ORDER BY name");
        $bestName = null;
        $bestDist = PHP_INT_MAX;
        while ($nameRow = $nr->fetch_assoc()) {
            $schoolWords = array_filter(explode(' ', strtolower($nameRow['name'])), fn($w) => strlen($w) >= 4);
            foreach ($fuzzyWords as $qw) {
                foreach ($schoolWords as $sw) {
                    if (abs(strlen($qw) - strlen($sw)) > 2) continue;
                    $d = levenshtein($qw, $sw);
                    $maxDist = strlen($qw) <= 5 ? 1 : 2;
                    if ($d > 0 && $d <= $maxDist && $d < $bestDist) {
                        $bestDist = $d;
                        $bestName = $nameRow['name'];
                    }
                }
            }
        }
        if ($bestName) {
            return "I couldn't find an exact match. Did you mean \"$bestName\"?\n\nTry asking: Is $bestName in the program?";
        }
    }

    return suggestDidYouMean($msg);
}

// ─────────────────────────────────────────────────────────────────────────────
// Smart "did you mean?" fallback — detects clues in the question
// ─────────────────────────────────────────────────────────────────────────────
function suggestDidYouMean($msg) {
    $suggestions = [];
    if (preg_match('/state|region|area|province|location|where/', $msg)) {
        $suggestions[] = "Schools by state — try: 'Which schools are in Telangana?'";
    }
    if (preg_match('/type|kind|category|high|primary|middle|secondary/', $msg)) {
        $suggestions[] = "School types — try: 'What types of schools are supported?'";
    }
    if (preg_match('/student|children|enroll|count|number|many/', $msg)) {
        $suggestions[] = "Enrollment info — try: 'How many students are served?'";
    }
    if (preg_match('/support|fund|sponsor|pgnf|nriva|org|partner/', $msg)) {
        $suggestions[] = "By supporter — try: 'Schools supported by PGNF'";
    }
    if (preg_match('/big|large|small|most|top|best|rank/', $msg)) {
        $suggestions[] = "Largest schools — try: 'Which school has the most students?'";
    }
    if (empty($suggestions)) {
        $suggestions = [
            "Search by school name — try: 'Is Chethana supported?'",
            "Search by state — try: 'Schools in Andhra Pradesh'",
            "Ask about totals — try: 'How many schools are supported?'",
        ];
    }
    return "I couldn't find a match for your query. You might be looking for:\n\n• " . implode("\n• ", $suggestions);
}

function formatSchoolResults($result, $searchTerm) {
    $schools = [];
    while ($row = $result->fetch_assoc()) $schools[] = $row;

    if (count($schools) === 1) {
        $s      = $schools[0];
        $enroll = $s['current_enrollment'] > 0 ? number_format($s['current_enrollment']) . " students" : "Not reported";
        $status = ($s['status'] === 'Completed')
            ? "Yes! " . $s['name'] . " has an active library in the Learn and Help program."
            : $s['name'] . " is in the Learn and Help program (status: " . $s['status'] . ").";
        return $status . "\n\n\nDetails:\n"
             . "• State: "        . ($s['state_name']  ?? 'N/A') . "\n"
             . "• Type: "         . ($s['type']         ?? 'N/A') . "\n"
             . "• Category: "     . ($s['category']     ?? 'N/A') . "\n"
             . "• Status: "       . ($s['status']       ?? 'N/A') . "\n"
             . "• Supported by: " . ($s['supported_by'] ?? 'N/A') . "\n"
             . "• Enrollment: "   . $enroll . "\n"
             . "• Contact: "      . ($s['contact_name'] ?? 'N/A');
    }

    $reply = "Found " . count($schools) . " schools matching '$searchTerm':\n";
    foreach ($schools as $s) {
        $reply .= "\n• " . $s['name'] . " — " . ($s['state_name'] ?? '') . ", " . $s['status'] . " (" . ($s['supported_by'] ?? '') . ")";
    }
    return $reply;
}
