<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    http_response_code(403);
    die('Forbidden');
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>README | Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
    <style>
        .doc {
            max-width: 820px;
            margin: 40px auto 60px auto;
            padding: 48px 56px;
            background: #fff;
            border: 1px solid #ddd;
            font-family: 'Roboto', Arial, sans-serif;
            color: #222;
            line-height: 1.7;
        }
        .doc-back {
            display: inline-block;
            margin-bottom: 28px;
            color: #3d6b0a;
            font-weight: 700;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .doc-back:hover { text-decoration: underline; }
        .doc h1 {
            font-size: 2.2rem;
            font-weight: 900;
            color: #1a1a1a;
            margin: 0 0 4px;
            border-bottom: 3px solid #99d930;
            padding-bottom: 10px;
        }
        .doc .doc-subtitle {
            font-size: 1rem;
            color: #777;
            margin: 0 0 32px;
        }
        .doc h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #274606;
            margin: 32px 0 8px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 4px;
        }
        .doc p, .doc li {
            font-size: 1.05rem;
            color: #333;
        }
        .doc ol, .doc ul {
            padding-left: 22px;
            margin: 6px 0 12px;
        }
        .doc li { margin-bottom: 4px; }
        .doc code {
            background: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 1px 6px;
            font-family: monospace;
            font-size: 1rem;
            color: #c7254e;
        }
        .doc .note {
            background: #fffbe6;
            border-left: 3px solid #99d930;
            padding: 8px 14px;
            font-size: 1rem;
            color: #555;
            margin: 10px 0;
        }
        .doc hr {
            border: none;
            border-top: 1px solid #e0e0e0;
            margin: 32px 0;
        }
        .doc .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        .doc .tag {
            background: #f4f4f4;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 3px 10px;
            font-size: 0.82rem;
            color: #333;
        }
        @media (max-width: 680px) {
            .doc { padding: 24px 18px; }
        }
    </style>
</head>
<body>
<?php
include 'show-navbar.php';
show_navbar();
?>
 
<div class="doc">
    <a class="doc-back" href="administration.php">&#8592; Back to Administration</a>
 
    <h1>README</h1>
    <p class="doc-subtitle">learnandhelp_2026 &nbsp;|&nbsp; Admin Reference</p>
 
    <h2>Overview</h2>
    <p>Learn and Help — Learn programming and help different causes.</p>
 
    <h2>Prerequisites</h2>
    <ul>
        <li>XAMPP installed (Apache + MySQL)</li>
        <li>PHP 8.0 or higher (included with XAMPP 8.x)</li>
        <li>cURL extension enabled in PHP (required for LLM/Claude mode)</li>
    </ul>
 
    <h2>Deployment Steps</h2>
    <ol>
        <li>Clone or download this repo as a ZIP file.</li>
        <li>Extract to: <code>xampp/htdocs/learnandhelp_2026</code></li>
        <li>Start Apache and MySQL in the XAMPP Control Panel.</li>
        <li>In phpMyAdmin, create a new database called <code>learnandhelp_db</code>.</li>
        <li>Import <code>sql/learnandhelp_db.sql</code> into that database.</li>
        <li>Open your browser and go to: <code>http://localhost/learnandhelp_2026</code></li>
    </ol>
 
    <h2>Admin Access</h2>
    <ol>
        <li>Create a regular user account through the app.</li>
        <li>In phpMyAdmin → <code>learnandhelp_db</code> → <code>users</code> table, find your account.</li>
        <li>Change the <code>role</code> field from <code>user</code> to <code>admin</code>.</li>
        <li>Log in — an <strong>Administration</strong> option will appear in the navigation bar.</li>
    </ol>
 
    <h2>Enable cURL (Required for LLM Mode)</h2>
    <ol>
        <li>Open XAMPP Control Panel → Apache → Config → <code>php.ini</code>.</li>
        <li>Find <code>;extension=curl</code> and remove the semicolon so it reads: <code>extension=curl</code></li>
        <li>Save the file and restart Apache.</li>
    </ol>
 
    <h2>Optional: LLM (Claude AI) Mode</h2>
    <ol>
        <li>Obtain an Anthropic API key from <code>console.anthropic.com</code> (requires adding credits).</li>
        <li>In phpMyAdmin → <code>preferences</code> table, set <code>ANTHROPIC_API_KEY</code> to your key.</li>
        <li>Log in as admin → Chat page → click <strong>"LLM (Claude)"</strong> to switch modes.</li>
    </ol>
    <div class="note">Without a valid API key, the chatbot runs in rule-based mode by default — no API key required.</div>
 
    <h2>Optional: Email (Progress Reports)</h2>
    <p>Edit <code>sendemail.php</code> and update: <code>MAIL_USERNAME</code>, <code>MAIL_PASSWORD</code>, <code>MAIL_FROM</code>, <code>MAIL_FROM_NAME</code>.</p>
    <p>In phpMyAdmin → <code>preferences</code> table, set <code>Email_Mode</code> to <code>DEV</code> (testing) or <code>PROD</code> (live).</p>
 
    <h2>Troubleshooting</h2>
    <ul>
        <li><strong>Apache won't start</strong> — Port 80 is in use. In <code>httpd.conf</code>, change <code>Listen 80</code> to <code>Listen 8080</code>, then access at <code>http://localhost:8080/learnandhelp_2026</code>.</li>
        <li><strong>MySQL won't start</strong> — Port 3306 is in use. Stop the conflicting service via Task Manager or change MySQL port in <code>my.ini</code>.</li>
        <li><strong>Blank page or PHP errors</strong> — Confirm PHP version is 8.0+. Check Apache → Logs → <code>error.log</code>.</li>
        <li><strong>LLM mode not working</strong> — Confirm cURL is enabled and <code>ANTHROPIC_API_KEY</code> is set correctly in the database.</li>
        <li><strong>Email not sending</strong> — Confirm Gmail App Password is correct and 2-Step Verification is enabled on the Gmail account.</li>
    </ul>
 
    <hr>
 
    <h2>Recently Updated Features</h2>
    <div class="tags">
        <span class="tag">Board Members</span>
        <span class="tag">Patron</span>
        <span class="tag">Assets</span>
        <span class="tag">Assets Report</span>
        <span class="tag">Expenses</span>
        <span class="tag">Chatbot (FP9)</span>
        <span class="tag">Chatbot Eval</span>
        <span class="tag">Chat Log</span>
    </div>
</div>
 
<?php include 'footer.php'; ?>
</body>
</html>
 