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

// ── Helpers ───────────────────────────────────────────────────
function fetchAllAssoc(mysqli $db, string $sql, array $params = []): array {
    if (!empty($params)) {
        $stmt = $db->prepare($sql);
        if (!$stmt) return [];
        $types = array_shift($params);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res  = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }
    $res = $db->query($sql);
    if (!$res) return [];
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $res->close();
    return $rows;
}

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

function col(array $row, string $key): ?string {
    return array_key_exists($key, $row) ? $row[$key] : null;
}

function countCsv(string $csv): int {
    $csv = trim($csv);
    if ($csv === '') return 0;
    return substr_count($csv, ',') + 1;
}

// ── Discover columns ──────────────────────────────────────────
$registrationColumns = [];
$columnsRes = $db->query("SHOW COLUMNS FROM registrations");
if ($columnsRes) {
    while ($c = $columnsRes->fetch_assoc()) $registrationColumns[] = $c['Field'];
    $columnsRes->close();
}
$hasParentEmail    = in_array('Parent_Email',    $registrationColumns, true);
$hasSecondaryEmail = in_array('Secondary_Email', $registrationColumns, true);
$hasStudentEmail   = in_array('Student_Email',   $registrationColumns, true);
$hasUserId         = in_array('User_Id',         $registrationColumns, true);
$hasClassId        = in_array('Class_Id',        $registrationColumns, true);

$userColumns = [];
if ($hasUserId) {
    $uCols = $db->query("SHOW COLUMNS FROM users");
    if ($uCols) {
        while ($c = $uCols->fetch_assoc()) $userColumns[] = $c['Field'];
        $uCols->close();
    }
}
$hasUsersEmail     = in_array('Email',                   $userColumns, true);
$hasUsersSecondary = in_array('secondary_contact_email', $userColumns, true);

// ── Fetch registrations ───────────────────────────────────────
$selectPieces = [];
if ($hasParentEmail)    $selectPieces[] = 'r.Parent_Email';
if ($hasSecondaryEmail) $selectPieces[] = 'r.Secondary_Email';
if ($hasStudentEmail)   $selectPieces[] = 'r.Student_Email';
if ($hasClassId)        $selectPieces[] = 'r.Class_Id';
if ($hasUserId)         $selectPieces[] = 'r.User_Id';

$registrations = [];
if (!empty($selectPieces)) {
    $baseSql       = "SELECT DISTINCT " . implode(', ', $selectPieces) . " FROM registrations r";
    $registrations = fetchAllAssoc($db, $baseSql);
}

// ── Collect emails per row ────────────────────────────────────
function collectParentsFromRow(array $row, bool $hasParentEmail, bool $hasSecondaryEmail,
                                bool $hasUserId, bool $hasUsersEmail, bool $hasUsersSecondary,
                                mysqli $db): array {
    $parents = [];
    if ($hasParentEmail    && !empty(col($row, 'Parent_Email')))    $parents[] = col($row, 'Parent_Email');
    if ($hasSecondaryEmail && !empty(col($row, 'Secondary_Email'))) $parents[] = col($row, 'Secondary_Email');
    if ($hasUserId && $hasUsersEmail) {
        $uid = col($row, 'User_Id');
        if ($uid) {
            $sel    = "Email" . ($hasUsersSecondary ? ", secondary_contact_email" : "");
            $uRows  = fetchAllAssoc($db, "SELECT $sel FROM users WHERE User_Id = ?", ["i", $uid]);
            foreach ($uRows as $u) {
                if ($hasUsersEmail     && !empty(col($u, 'Email')))                   $parents[] = col($u, 'Email');
                if ($hasUsersSecondary && !empty(col($u, 'secondary_contact_email'))) $parents[] = col($u, 'secondary_contact_email');
            }
        }
    }
    return $parents;
}

$allParentsEmails  = [];
$allStudentsEmails = [];

foreach ($registrations as $row) {
    $allParentsEmails = array_merge(
        $allParentsEmails,
        collectParentsFromRow($row, $hasParentEmail, $hasSecondaryEmail, $hasUserId, $hasUsersEmail, $hasUsersSecondary, $db)
    );
    if ($hasStudentEmail && !empty(col($row, 'Student_Email'))) {
        $allStudentsEmails[] = col($row, 'Student_Email');
    }
}

$allParentsList  = formatEmailList($allParentsEmails);
$allStudentsList = formatEmailList($allStudentsEmails);
$allBothList     = formatEmailList(array_merge($allParentsEmails, $allStudentsEmails));

// ── Per-class lists ───────────────────────────────────────────
$classWiseParents  = [];
$classWiseStudents = [];
$classWiseBoth     = [];

$classes    = fetchAllAssoc($db, "SELECT Class_Id, Class_Name FROM classes");
$classNames = [];
foreach ($classes as $c) {
    $classNames[(string)$c['Class_Id']] = $c['Class_Name'];
}

foreach ($registrations as $row) {
    $cid   = $hasClassId ? (string)col($row, 'Class_Id') : null;
    $cname = ($cid !== null && isset($classNames[$cid])) ? $classNames[$cid] : 'Unspecified Class';

    $p = collectParentsFromRow($row, $hasParentEmail, $hasSecondaryEmail, $hasUserId, $hasUsersEmail, $hasUsersSecondary, $db);
    $classWiseParents[$cname]  = array_merge($classWiseParents[$cname]  ?? [], $p);
    $classWiseStudents[$cname] = $classWiseStudents[$cname] ?? [];
    if ($hasStudentEmail && !empty(col($row, 'Student_Email'))) {
        $classWiseStudents[$cname][] = col($row, 'Student_Email');
    }
}

foreach ($classNames as $cid => $name) {
    $p = $classWiseParents[$name]  ?? [];
    $s = $classWiseStudents[$name] ?? [];
    $classWiseBoth[$name] = array_values(array_unique(array_merge($p, $s)));
    sort($classWiseBoth[$name], SORT_STRING);
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Email Distribution – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">

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
            max-width: 1000px;
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

        /* ── Summary tiles ── */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .summary-tile {
            background: #fff;
            border: 2px solid #99d930;
            border-radius: 14px;
            padding: 20px 14px;
            text-align: center;
            box-shadow: 0 4px 16px rgba(153,217,48,.1);
        }
        .summary-tile .tile-number {
            font-size: 2.2em;
            font-weight: 900;
            color: #274606;
            line-height: 1;
        }
        .summary-tile .tile-label {
            font-size: .82em;
            color: #666;
            margin-top: 6px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        /* ── Section heading ── */
        .section-heading {
            font-size: 1.25em;
            font-weight: 900;
            color: #274606;
            margin: 32px 0 16px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #99d930;
        }

        /* ── Email card ── */
        .email-card {
            background: #fff;
            border: 2px solid #99d930;
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 16px;
            box-shadow: 0 4px 16px rgba(80,120,180,.07);
        }
        .email-card h3 {
            margin: 0 0 12px 0;
            font-size: 1em;
            color: #274606;
            font-weight: 900;
        }
        .email-card textarea {
            width: 100%;
            min-height: 110px;
            border: 1.5px solid #cde8a0;
            border-radius: 8px;
            padding: 10px 13px;
            font-family: 'Roboto', monospace, sans-serif;
            font-size: .88em;
            resize: vertical;
            background: #f8fbe9;
            color: #333;
            box-sizing: border-box;
            outline: none;
            transition: border-color .18s;
        }
        .email-card textarea:focus { border-color: #99d930; }
        .email-card .count {
            margin-top: 8px;
            font-size: .82em;
            color: #888;
            font-weight: 700;
        }
        .email-card .count span {
            color: #274606;
            font-weight: 900;
        }

        /* ── Class separator ── */
        .class-separator { height: 12px; }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .email-card { padding: 14px 12px; }
        }
    </style>
</head>
<body>

<?php
include 'show-navbar.php';
show_navbar();
?>

<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Admin banner">
    <h1 class="banner-title">Email Distribution</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <p style="color:#555;margin-bottom:24px;font-size:.97em;">
        Copy and paste from the boxes below. All lists are de-duplicated and sorted alphabetically.
    </p>

    <!-- ── Summary Tiles ── -->
    <div class="summary-grid">
        <div class="summary-tile">
            <div class="tile-number"><?= countCsv($allParentsList) ?></div>
            <div class="tile-label">Total Parents</div>
        </div>
        <div class="summary-tile">
            <div class="tile-number"><?= countCsv($allStudentsList) ?></div>
            <div class="tile-label">Total Students</div>
        </div>
        <div class="summary-tile">
            <div class="tile-number"><?= countCsv($allBothList) ?></div>
            <div class="tile-label">Total Combined</div>
        </div>
        <div class="summary-tile">
            <div class="tile-number"><?= count($classNames) ?></div>
            <div class="tile-label">Classes</div>
        </div>
    </div>

    <!-- ── Master Lists ── -->
    <div class="section-heading">Master Distribution Lists</div>

    <?php
    function emailCard(string $id, string $label, string $value): void {
        $count = countCsv($value);
        echo "<div class='email-card'>";
        echo   "<h3>" . htmlspecialchars($label) . "</h3>";
        echo   "<textarea id='" . htmlspecialchars($id) . "' readonly>" . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</textarea>";
        echo   "<div class='count'>Total: <span>{$count}</span></div>";
        echo "</div>";
    }
    ?>

    <?php emailCard('parents_all',  'All Parents',                $allParentsList); ?>
    <?php emailCard('students_all', 'All Students',               $allStudentsList); ?>
    <?php emailCard('both_all',     'All Combined (Parents + Students)', $allBothList); ?>

    <!-- ── Per-Class Lists ── -->
    <?php
    $sortedClassNames = $classNames;
    asort($sortedClassNames);

    $hasAnyClassLists = false;
    foreach ($sortedClassNames as $cid => $className) {
        $p = formatEmailList($classWiseParents[$className]  ?? []);
        $s = formatEmailList($classWiseStudents[$className] ?? []);
        $b = formatEmailList($classWiseBoth[$className]     ?? []);
        if (countCsv($p) > 0 || countCsv($s) > 0 || countCsv($b) > 0) {
            $hasAnyClassLists = true;
            break;
        }
    }
    ?>

    <?php if ($hasAnyClassLists): ?>
        <div class="section-heading">Per-Class Distribution Lists</div>

        <?php foreach ($sortedClassNames as $cid => $className):
            $emailsParents  = formatEmailList($classWiseParents[$className]  ?? []);
            $emailsStudents = formatEmailList($classWiseStudents[$className] ?? []);
            $emailsBoth     = formatEmailList($classWiseBoth[$className]     ?? []);

            if (countCsv($emailsParents) === 0 && countCsv($emailsStudents) === 0 && countCsv($emailsBoth) === 0) continue;
            $safe = md5($className);
        ?>
            <?php emailCard('p_' . $safe, htmlspecialchars($className) . ' — Parents',              $emailsParents); ?>
            <?php emailCard('s_' . $safe, htmlspecialchars($className) . ' — Students',             $emailsStudents); ?>
            <?php emailCard('b_' . $safe, htmlspecialchars($className) . ' — Both (Parents + Students)', $emailsBoth); ?>
            <div class="class-separator"></div>
        <?php endforeach; ?>
    <?php endif; ?>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>