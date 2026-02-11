<?php
/*******************************************************
 * admin_email_distribution.php (Refactored - Single Column Layout)
 * Learn & Help â€" Email Distribution Lists
 *
 * Goal:
 *  - Establish a proper mysqli connection using the SAME pattern
 *    used elsewhere (e.g., admin_registrations.php).
 *  - If $conn already exists and is a mysqli, reuse it.
 *  - Otherwise, try to include a DB bootstrap from common locations.
 *
 * HOW TO ADAPT (if needed):
 *  - If your project uses a specific bootstrap (e.g., classes.php or includes/db_connect.php)
 *    edit the $DB_BOOTSTRAP_PATHS array below so the first existing file is required.
 *
 * This file is defensive:
 *  - It will attempt multiple common bootstrap paths.
 *  - It checks for missing columns and skips them gracefully.
 *******************************************************/

// -------------------------------
// Session & simple admin gating
// -------------------------------
$status = session_status();
if ($status === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Forbidden');
}

// db connection check
require 'db_configuration.php';

$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



// -----------------------------------------
// 1) Ensure we have a valid $conn (mysqli)
// -----------------------------------------
if (!isset($conn) || !($conn instanceof mysqli)) {
    // Adjust these candidates to match your project structure.
    $DB_BOOTSTRAP_PATHS = [
        __DIR__ . '/includes/db_connect.php',
        __DIR__ . '/includes/db.php',
        __DIR__ . '/config/db.php',
        __DIR__ . '/config.php',
        __DIR__ . '/classes.php',
        __DIR__ . '/db.php',
    ];
    foreach ($DB_BOOTSTRAP_PATHS as $bootstrap) {
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
            break;
        }
    }
}

// After attempting bootstrap, validate $conn
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    die('Error: $conn (mysqli) is not initialized. Please ensure your DB bootstrap is included before this file.');
}

// -----------------------------------------
// 2) Helpers
// -----------------------------------------
/**
 * Execute a query and return all rows as associative arrays.
 */
function fetchAllAssoc(mysqli $conn, string $sql, array $params = []): array {
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        // Dynamically bind params if provided as [types, val1, val2, ...]
        $types = array_shift($params);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    } else {
        $res = $conn->query($sql);
        if ($res === false) return [];
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $res->close();
        return $rows;
    }
}

/**
 * Format a list of emails: distinct, trimmed, sorted, CSV.
 */
function formatEmailList(array $emails): string {
    $clean = [];
    foreach ($emails as $e) {
        $e = trim((string)$e);
        if ($e !== '') $clean[] = strtolower($e);
    }
    $clean = array_values(array_unique($clean));
    sort($clean, SORT_STRING);
    return implode(', ', $clean);
}

/**
 * Safely fetch a column from row if it exists.
 */
function col(array $row, string $key): ?string {
    return array_key_exists($key, $row) ? $row[$key] : null;
}

// -----------------------------------------
// 3) Build email groups
// -----------------------------------------
// We will not assume optional columns exist; we probe and collect only if present.

// Discover which email columns exist in registrations.
$registrationColumns = [];
$columnsRes = $conn->query("SHOW COLUMNS FROM registrations");
if ($columnsRes) {
    while ($c = $columnsRes->fetch_assoc()) {
        $registrationColumns[] = $c['Field'];
    }
    $columnsRes->close();
}
$hasParentEmail     = in_array('Parent_Email', $registrationColumns, true);
$hasSecondaryEmail  = in_array('Secondary_Email', $registrationColumns, true);
$hasStudentEmail    = in_array('Student_Email', $registrationColumns, true);
$hasUserId          = in_array('User_Id', $registrationColumns, true);
$hasClassId         = in_array('Class_Id', $registrationColumns, true);

// We may also join users for parent emails if User_Id exists.
$userColumns = [];
if ($hasUserId) {
    $uCols = $conn->query("SHOW COLUMNS FROM users");
    if ($uCols) {
        while ($c = $uCols->fetch_assoc()) $userColumns[] = $c['Field'];
        $uCols->close();
    }
}
$hasUsersEmail      = in_array('Email', $userColumns, true);
$hasUsersSecondary  = in_array('secondary_contact_email', $userColumns, true);

// Fetch all registrations (minimal fields)
$selectPieces = [];
if ($hasParentEmail)    $selectPieces[] = 'r.Parent_Email';
if ($hasSecondaryEmail) $selectPieces[] = 'r.Secondary_Email';
if ($hasStudentEmail)   $selectPieces[] = 'r.Student_Email';
if ($hasClassId)        $selectPieces[] = 'r.Class_Id';
if ($hasUserId)         $selectPieces[] = 'r.User_Id';
if (empty($selectPieces)) {
    http_response_code(500);
    die('registrations table does not contain any expected email columns.');
}

$baseSql = "SELECT DISTINCT " . implode(', ', $selectPieces) . " FROM registrations r";
$registrations = fetchAllAssoc($conn, $baseSql);

// Helper to collect parents for a given registration row:
function collectParentsFromRow(array $row, bool $hasParentEmail, bool $hasSecondaryEmail, bool $hasUserId, bool $hasUsersEmail, bool $hasUsersSecondary, mysqli $conn): array {
    $parents = [];
    if ($hasParentEmail && !empty(col($row,'Parent_Email')))    $parents[] = col($row,'Parent_Email');
    if ($hasSecondaryEmail && !empty(col($row,'Secondary_Email'))) $parents[] = col($row,'Secondary_Email');

    // Also consider users table via User_Id, if available
    if ($hasUserId && $hasUsersEmail) {
        $uid = col($row, 'User_Id');
        if ($uid) {
            $sqlU = "SELECT " . ($hasUsersEmail ? "Email" : "NULL") .
                    ($hasUsersSecondary ? ", secondary_contact_email" : "") .
                    " FROM users WHERE User_Id = ?";
            $types = "i";
            $params = [$types, $uid];
            $uRows = fetchAllAssoc($conn, $sqlU, $params);
            foreach ($uRows as $u) {
                if ($hasUsersEmail && !empty(col($u,'Email'))) $parents[] = col($u,'Email');
                if ($hasUsersSecondary && !empty(col($u,'secondary_contact_email'))) $parents[] = col($u,'secondary_contact_email');
            }
        }
    }
    return $parents;
}

// --- Master lists ---
$allParentsEmails  = [];
$allStudentsEmails = [];

foreach ($registrations as $row) {
    // Parents
    $allParentsEmails = array_merge(
        $allParentsEmails,
        collectParentsFromRow($row, $hasParentEmail, $hasSecondaryEmail, $hasUserId, $hasUsersEmail, $hasUsersSecondary, $conn)
    );
    // Students
    if ($hasStudentEmail && !empty(col($row,'Student_Email'))) {
        $allStudentsEmails[] = col($row, 'Student_Email');
    }
}

// Combine
$allParentsList   = formatEmailList($allParentsEmails);
$allStudentsList  = formatEmailList($allStudentsEmails);
$allBothList      = formatEmailList(array_merge($allParentsEmails, $allStudentsEmails));

// --- Per-class lists ---
$classWiseParents  = [];
$classWiseStudents = [];
$classWiseBoth     = [];

// Get class names
$classes = fetchAllAssoc($conn, "SELECT Class_Id, Class_Name FROM classes");
$classNames = [];
foreach ($classes as $c) {
    $cid = (string)$c['Class_Id'];
    $classNames[$cid] = $c['Class_Name'];
}

foreach ($registrations as $row) {
    $cid = $hasClassId ? (string)col($row, 'Class_Id') : null;
    $cname = $cid !== null && isset($classNames[$cid]) ? $classNames[$cid] : 'Unspecified Class';

    // Parents
    $p = collectParentsFromRow($row, $hasParentEmail, $hasSecondaryEmail, $hasUserId, $hasUsersEmail, $hasUsersSecondary, $conn);
    if (!isset($classWiseParents[$cname])) $classWiseParents[$cname] = [];
    $classWiseParents[$cname] = array_merge($classWiseParents[$cname], $p);

    // Students
    if (!isset($classWiseStudents[$cname])) $classWiseStudents[$cname] = [];
    if ($hasStudentEmail && !empty(col($row,'Student_Email'))) {
        $classWiseStudents[$cname][] = col($row,'Student_Email');
    }
}

// Deduplicate/sort per-class and combine
foreach ($classWiseParents as $name => $arr) {
    $classWiseParents[$name] = array_values(array_unique(array_map('strtolower', array_filter(array_map('trim',$arr))))); sort($classWiseParents[$name], SORT_STRING);
}
foreach ($classWiseStudents as $name => $arr) {
    $classWiseStudents[$name] = array_values(array_unique(array_map('strtolower', array_filter(array_map('trim',$arr))))); sort($classWiseStudents[$name], SORT_STRING);
}
foreach ($classNames as $cid => $name) {
    $p = $classWiseParents[$name]  ?? [];
    $s = $classWiseStudents[$name] ?? [];
    $classWiseBoth[$name] = array_values(array_unique(array_merge($p, $s)));
    sort($classWiseBoth[$name], SORT_STRING);
}

// -----------------------------------------
// 4) Render
// -----------------------------------------
function countCsv(string $csv): int {
    $csv = trim($csv);
    if ($csv === '') return 0;
    // Count items by counting separators + 1
    return substr_count($csv, ',') + 1;
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Learn & Help â€" Email Distribution</title>
<style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Helvetica,Arial,sans-serif;margin:16px;color:#111;max-width:800px}
    h1{font-size:22px;margin:0 0 8px}
    .note{font-size:13px;color:#444;margin-bottom:16px}
    .card{border:1px solid #e5e7eb;border-radius:12px;padding:16px;box-shadow:0 1px 2px rgba(0,0,0,.04);margin-bottom:16px}
    .card h3{font-size:16px;margin:0 0 8px}
    textarea{width:100%;min-height:120px;border:1px solid #d1d5db;border-radius:8px;padding:8px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;resize:vertical;box-sizing:border-box}
    .kvs{display:flex;justify-content:space-between;gap:8px;margin-top:6px;font-size:13px;color:#555}
    .section-title{margin:32px 0 16px 0;font-size:18px;border-bottom:2px solid #e5e7eb;padding-bottom:8px}
    .section-note{font-size:12px;color:#666;margin-top:6px}
    .summary-section{background:#f8fafc;border-radius:8px;padding:12px;margin-bottom:24px}
    .summary-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-top:8px}
    .stat-item{text-align:center;padding:8px;background:white;border-radius:6px;border:1px solid #e5e7eb}
    .stat-number{font-size:18px;font-weight:bold;color:#059669}
    .stat-label{font-size:12px;color:#666;margin-top:2px}
</style>
</head>
<body>
    <h1>Email Distribution Lists</h1>
    <div class="note">Copy/paste from the boxes below. Lists are de-duplicated and sorted.</div>

    <!-- Summary Statistics -->
    <div class="summary-section">
        <h3 style="margin:0 0 8px 0;">Summary Statistics</h3>
        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-number"><?= countCsv($allParentsList) ?></div>
                <div class="stat-label">Total Parents</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= countCsv($allStudentsList) ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= countCsv($allBothList) ?></div>
                <div class="stat-label">Total Combined</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= count($classNames) ?></div>
                <div class="stat-label">Classes</div>
            </div>
        </div>
    </div>

    <?php
    function textArea(string $id, string $value, string $label){
        echo "<div class='card'>";
        echo "<h3>{$label}</h3>";
        echo "<textarea id='{$id}' readonly>" . htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</textarea>";
        echo "<div class='kvs'><b>Total:</b><span>" . countCsv($value) . "</span></div>";
        echo "</div>";
    }
    ?>

    <!-- Master Lists -->
    <h2 class="section-title">Master Distribution Lists</h2>
    
    <?php textArea('parents_all', $allParentsList, 'All Parents'); ?>
    <div class="section-note" style="margin-top:-8px;margin-bottom:16px;">From registrations (Parent_Email, Secondary_Email) and users (Email, secondary_contact_email).</div>
    
    <?php textArea('students_all', $allStudentsList, 'All Students'); ?>
    <div class="section-note" style="margin-top:-8px;margin-bottom:16px;">From registrations.Student_Email.</div>
    
    <?php textArea('both_all', $allBothList, 'All (Parents + Students)'); ?>
    <div class="section-note" style="margin-top:-8px;margin-bottom:16px;">Union of All Parents and All Students.</div>

    <!-- Per-Class Lists -->
    <?php 
    // Sort class names for consistent display
    $sortedClassNames = $classNames;
    asort($sortedClassNames);
    
    // Check if we have any per-class lists to display
    $hasAnyClassLists = false;
    foreach ($sortedClassNames as $cid => $className) {
        $emails_parents = $classWiseParents[$className] ?? [];
        $emails_students = $classWiseStudents[$className] ?? [];
        $emails_both = $classWiseBoth[$className] ?? [];
        
        if (countCsv(formatEmailList($emails_parents)) > 0 || 
            countCsv(formatEmailList($emails_students)) > 0 || 
            countCsv(formatEmailList($emails_both)) > 0) {
            $hasAnyClassLists = true;
            break;
        }
    }
    ?>
    
    <?php if ($hasAnyClassLists): ?>
    <h2 class="section-title">Per-Class Distribution Lists</h2>
    
    <?php 
    foreach ($sortedClassNames as $cid => $className): 
        $emails_parents = $classWiseParents[$className] ?? [];
        $emails_students = $classWiseStudents[$className] ?? [];
        $emails_both = $classWiseBoth[$className] ?? [];
        
        // Check if this class has any emails at all before showing anything
        $hasClassEmails = countCsv(formatEmailList($emails_parents)) > 0 || 
                         countCsv(formatEmailList($emails_students)) > 0 || 
                         countCsv(formatEmailList($emails_both)) > 0;
        
        if ($hasClassEmails):
    ?>
        
        <!-- Parents for this class -->
        <?php textArea('p_' . md5($className), formatEmailList($emails_parents), htmlspecialchars($className) . ' — Parents'); ?>
        
        <!-- Students for this class -->
        <?php textArea('s_' . md5($className), formatEmailList($emails_students), htmlspecialchars($className) . ' — Students'); ?>
        
        <!-- Combined for this class -->
        <?php textArea('b_' . md5($className), formatEmailList($emails_both), htmlspecialchars($className) . ' — Both (Parents + Students)'); ?>
        
        <!-- Small separator between classes -->
        <div style="height:8px;"></div>
        
    <?php 
        endif;
    endforeach; 
    ?>
    <?php endif; ?>
    
    <div style="height:32px;"></div> <!-- Bottom spacing -->
</body>
</html>