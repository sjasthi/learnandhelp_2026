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

// ── Fetch users using $db (mysqli) ────────────────────────────
$result = $db->query("SELECT User_Id, First_Name, Last_Name, Email, Phone FROM users ORDER BY First_Name ASC");
$users  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// ── Twilio configuration ──────────────────────────────────────
// Store credentials in a config file or environment variable — never hardcode in source.
// Create a file called twilio_config.php with:
//   define('TWILIO_SID',   'your_sid_here');
//   define('TWILIO_TOKEN', 'your_token_here');
if (file_exists(__DIR__ . '/twilio_config.php')) {
    require __DIR__ . '/twilio_config.php';
}
$sid                    = defined('TWILIO_SID')   ? TWILIO_SID   : '';
$token                  = defined('TWILIO_TOKEN') ? TWILIO_TOKEN : '';
$twilio_whatsapp_number = '+14155238886';

$twilio = null;
if ($sid && $token && file_exists(__DIR__ . '/twilio-php-main/src/Twilio/autoload.php')) {
    require __DIR__ . '/twilio-php-main/src/Twilio/autoload.php';
    $twilio = new Twilio\Rest\Client($sid, $token);
}

// ── Send WhatsApp message ─────────────────────────────────────
function sendWhatsAppMessage(string $to, string $message): array {
    global $twilio, $twilio_whatsapp_number;
    if (!$twilio) {
        return ['success' => false, 'error' => 'Twilio is not configured.'];
    }
    try {
        $to = preg_replace('/[^0-9+]/', '', $to);
        if (substr($to, 0, 1) !== '+') $to = '+' . $to;
        $msg = $twilio->messages->create(
            "whatsapp:$to",
            ['from' => "whatsapp:$twilio_whatsapp_number", 'body' => $message]
        );
        return ['success' => true, 'sid' => $msg->sid];
    } catch (Exception $e) {
        error_log("WhatsApp send failed to $to: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ── AJAX: send message ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_whatsapp') {
    $message    = trim($_POST['message'] ?? '');
    $recipients = json_decode($_POST['recipient'] ?? '[]', true);

    if (!is_array($recipients) || $message === '') {
        echo json_encode(['success' => false, 'error' => 'Invalid request.']);
        exit;
    }

    $results = [];
    $success = true;

    if (in_array('all', $recipients)) {
        foreach ($users as $user) {
            $r        = sendWhatsAppMessage($user['Phone'], $message);
            $success  = $success && $r['success'];
            $results[] = ['phone' => $user['Phone'], 'result' => $r];
        }
    } else {
        foreach ($recipients as $phone) {
            $r        = sendWhatsAppMessage($phone, $message);
            $success  = $success && $r['success'];
            $results[] = ['phone' => $phone, 'result' => $r];
        }
    }

    echo json_encode(['success' => $success, 'results' => $results]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>WhatsApp – Administration</title>
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
            max-width: 1100px;
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
            margin: 0 0 16px 0;
            font-size: 1.15em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Layout ── */
        .chat-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 24px;
            align-items: start;
        }

        /* ── Sidebar ── */
        .sidebar-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 20px;
        }
        .sidebar-card h2 {
            margin: 0 0 12px 0;
            font-size: 1em;
            color: #274606;
            font-weight: 900;
        }
        .sidebar-card label {
            display: block;
            font-size: .82em;
            font-weight: 700;
            color: #274606;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 6px;
        }
        #user-select {
            width: 100%;
            min-height: 200px;
            border: 1.5px solid #cde8a0;
            border-radius: 8px;
            background: #f8fbe9;
            font-family: 'Roboto', sans-serif;
            font-size: .9em;
            padding: 6px;
            outline: none;
            color: #222;
        }
        #user-select:focus { border-color: #99d930; }
        #user-select option { padding: 4px 6px; }

        /* ── Chat main ── */
        .chat-main {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 20px;
        }
        .chat-main h2 {
            margin: 0 0 14px 0;
            font-size: 1em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Chat area ── */
        .chat-area {
            height: 320px;
            overflow-y: auto;
            border: 1.5px solid #cde8a0;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 14px;
            background: #e5ddd5;
        }
        .message {
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 8px;
            max-width: 70%;
            clear: both;
            word-wrap: break-word;
        }
        .message.sent {
            background: #dcf8c6;
            float: right;
        }
        .message.received {
            background: #fff;
            float: left;
        }
        .message::after { content: ''; display: table; clear: both; }
        .timestamp {
            font-size: .75em;
            color: #888;
            margin-top: 4px;
        }

        /* ── Message input ── */
        .message-input {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }
        .message-input input[type="text"] {
            flex: 1;
            padding: 10px 14px;
            border: 1.5px solid #cde8a0;
            border-radius: 20px;
            font-family: 'Roboto', sans-serif;
            font-size: .95em;
            background: #f8fbe9;
            outline: none;
            transition: border-color .18s;
        }
        .message-input input[type="text"]:focus { border-color: #99d930; }
        .message-input button {
            padding: 10px 22px;
            background: #25D366;
            color: #fff;
            border: none;
            border-radius: 20px;
            font-family: 'Roboto', sans-serif;
            font-weight: 700;
            font-size: .95em;
            cursor: pointer;
            transition: background .18s;
        }
        .message-input button:hover { background: #1da851; }

        /* ── Twilio warning ── */
        .twilio-warning {
            background: #fff0f0;
            border: 1.5px solid #f99;
            border-radius: 10px;
            padding: 12px 16px;
            color: #c00;
            font-size: .88em;
            font-weight: 700;
            margin-bottom: 18px;
        }

        @media (max-width: 768px) {
            .chat-layout { grid-template-columns: 1fr; }
            .banner-title { font-size: 2em; }
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
    <h1 class="banner-title">WhatsApp</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <?php if (!$twilio): ?>
    <div class="twilio-warning">
        ⚠️ Twilio is not configured. Create a <code>twilio_config.php</code> file with
        <code>TWILIO_SID</code> and <code>TWILIO_TOKEN</code> constants to enable sending.
    </div>
    <?php endif; ?>

    <div class="chat-layout">

        <!-- ── Sidebar: recipient selector ── -->
        <div class="sidebar-card">
            <h2>📋 Contacts</h2>
            <label for="user-select">Select recipients:</label>
            <select id="user-select" multiple>
                <option value="all">All Users</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['Phone'], ENT_QUOTES) ?>">
                        <?= htmlspecialchars($user['First_Name'] . ' ' . $user['Last_Name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p style="font-size:.78em;color:#888;margin:10px 0 0 0;">
                Hold Ctrl / Cmd to select multiple. Choose "All Users" to message everyone.
            </p>
        </div>

        <!-- ── Main: chat ── -->
        <div class="chat-main">
            <h2 id="selected-user">Select users to start chatting</h2>

            <div class="chat-area" id="chat-area"></div>

            <form id="message-form">
                <div class="message-input">
                    <input type="text" id="message-text" name="message"
                           placeholder="Type a WhatsApp message…" required>
                    <button type="submit" id="send-button">Send</button>
                </div>
            </form>
        </div>

    </div><!-- /chat-layout -->

</div><!-- /page-wrap -->

<script>
    var selectedUsers = [];

    document.getElementById('user-select').addEventListener('change', function () {
        selectedUsers = Array.from(this.selectedOptions).map(opt => opt.value);
        if (selectedUsers.includes('all')) selectedUsers = ['all'];
        updateSelectedUserDisplay();
    });

    function updateSelectedUserDisplay() {
        var el = document.getElementById('selected-user');
        if (selectedUsers.length === 0) {
            el.textContent = 'Select users to start chatting';
        } else if (selectedUsers.includes('all')) {
            el.textContent = 'Chat with All Users';
        } else {
            el.textContent = 'Chat with ' + selectedUsers.length + ' selected user(s)';
        }
    }

    document.getElementById('message-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var messageText = document.getElementById('message-text').value.trim();
        if (!messageText) return;
        if (selectedUsers.length === 0) {
            alert('Please select at least one user to message.');
            return;
        }
        sendWhatsAppMessage(selectedUsers, messageText);
    });

    function addMessage(text, type) {
        var chatArea = document.getElementById('chat-area');
        var wrap = document.createElement('div');
        wrap.style.overflow = 'hidden';

        var msg = document.createElement('div');
        msg.classList.add('message', type);

        var textEl = document.createElement('div');
        textEl.textContent = text;
        msg.appendChild(textEl);

        var ts = document.createElement('div');
        ts.classList.add('timestamp');
        ts.textContent = new Date().toLocaleString();
        msg.appendChild(ts);

        wrap.appendChild(msg);
        chatArea.appendChild(wrap);
        chatArea.scrollTop = chatArea.scrollHeight;
    }

    function sendWhatsAppMessage(recipients, message) {
        var sendBtn = document.getElementById('send-button');
        sendBtn.disabled = true;
        sendBtn.textContent = 'Sending…';

        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action:    'send_whatsapp',
                recipient: JSON.stringify(recipients),
                message:   message
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                addMessage(message, 'sent');
                document.getElementById('message-text').value = '';
            } else {
                alert('Failed to send message to some recipients. Check the browser console for details.');
                console.error('Send results:', data.results);
            }
        })
        .catch(function (err) {
            console.error('Fetch error:', err);
            alert('An error occurred while sending the message.');
        })
        .finally(function () {
            sendBtn.disabled = false;
            sendBtn.textContent = 'Send';
        });
    }
</script>

<?php include 'footer.php'; ?>
</body>
</html>