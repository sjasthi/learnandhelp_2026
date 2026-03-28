<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/db_configuration.php';
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Read LLM parameters, API key, and current mode from preferences
$llm_key             = '';
$chatbot_temperature = 0.2;
$chatbot_top_p       = 0.9;
$chatbot_max_tokens  = 300;
$chatbot_mode        = 'rule'; // default: rule-based
$pr = $conn->query("SELECT Preference_Name, Value FROM preferences
    WHERE Preference_Name IN ('ANTHROPIC_API_KEY','CHATBOT_TEMPERATURE','CHATBOT_TOP_P','CHATBOT_MAX_TOKENS','CHATBOT_MODE')");
if ($pr) {
    while ($row = $pr->fetch_assoc()) {
        switch ($row['Preference_Name']) {
            case 'ANTHROPIC_API_KEY':   $llm_key             = trim($row['Value']);  break;
            case 'CHATBOT_TEMPERATURE': $chatbot_temperature = (float)$row['Value']; break;
            case 'CHATBOT_TOP_P':       $chatbot_top_p       = (float)$row['Value']; break;
            case 'CHATBOT_MAX_TOKENS':  $chatbot_max_tokens  = (int)$row['Value'];   break;
            case 'CHATBOT_MODE':        $chatbot_mode        = $row['Value'];        break;
        }
    }
}
$has_api_key = ($llm_key !== '' && $llm_key !== 'test' && $llm_key !== 'key');
$llm_active  = $has_api_key && $chatbot_mode === 'llm';

// Admin: handle POST actions
$param_saved = false;
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // Toggle mode
    if (isset($_POST['toggle_mode']) && $has_api_key) {
        $new_mode = ($_POST['toggle_mode'] === 'llm') ? 'llm' : 'rule';
        $stmt = $conn->prepare("INSERT INTO preferences (Preference_Name, Value) VALUES ('CHATBOT_MODE', ?) ON DUPLICATE KEY UPDATE Value = ?");
        $stmt->bind_param("ss", $new_mode, $new_mode);
        $stmt->execute(); $stmt->close();
        $chatbot_mode = $new_mode;
        $llm_active   = $has_api_key && $chatbot_mode === 'llm';
    }

    // Save LLM parameters
    if (isset($_POST['save_llm_params'])) {
        $new_temp   = max(0.0, min(2.0, (float)($_POST['chatbot_temperature'] ?? 0.2)));
        $new_topp   = max(0.0, min(1.0, (float)($_POST['chatbot_top_p']       ?? 0.9)));
        $new_tokens = max(50,  min(2000, (int)($_POST['chatbot_max_tokens']    ?? 300)));
        foreach (['CHATBOT_TEMPERATURE' => (string)$new_temp, 'CHATBOT_TOP_P' => (string)$new_topp, 'CHATBOT_MAX_TOKENS' => (string)$new_tokens] as $pname => $pval) {
            $stmt = $conn->prepare("INSERT INTO preferences (Preference_Name, Value) VALUES (?, ?) ON DUPLICATE KEY UPDATE Value = ?");
            $stmt->bind_param("sss", $pname, $pval, $pval);
            $stmt->execute(); $stmt->close();
        }
        $chatbot_temperature = $new_temp;
        $chatbot_top_p       = $new_topp;
        $chatbot_max_tokens  = $new_tokens;
        $param_saved         = true;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Schools Chat Bot | Learn and Help</title>
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
  <style>
    :root { --accent: #99D930; }
    .accent-text { color: var(--accent); }

    body { margin: 0; font-family: 'Montserrat', sans-serif; background: #f8f8f8; color: #252525; }

    /* Banner */
    .intro-banner { background: #1a1a1a; color: #fff; text-align: center; padding: 24px 20px 20px; }
    .intro-banner h1 { font-family: 'Montserrat', sans-serif; font-size: 3rem; font-weight: 900; margin: 0; }
    .intro-banner p { max-width: 820px; margin: 8px auto 0; font-size: 1.2rem; line-height: 1.5; }

    /* Page layout */
    .chat-page { max-width: 1000px; margin: 40px auto; padding: 0 20px; }

    /* Chat container */
    .chat-container {
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 24px rgba(0,0,0,.08);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      height: 700px;
    }

    .chat-header {
      background: #1a1a1a;
      color: #fff;
      padding: 16px 24px;
      font-weight: 700;
      font-size: 1.1rem;
      display: flex;
      align-items: center;
      gap: 10px;
      justify-content: space-between;
    }
    .chat-header-left { display: flex; align-items: center; gap: 10px; }
    .btn-clear-chat {
      background: transparent;
      border: 1px solid rgba(255,255,255,0.35);
      color: #fff;
      border-radius: 6px;
      padding: 4px 12px;
      font-size: 0.78rem;
      font-family: 'Montserrat', sans-serif;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn-clear-chat:hover { background: rgba(255,255,255,0.15); }
    .chat-header .dot-indicator {
      width: 10px; height: 10px;
      background: var(--accent);
      border-radius: 50%;
      display: inline-block;
    }

    /* Messages area */
    .chat-messages {
      flex: 1;
      overflow-y: auto;
      padding: 20px 24px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .msg {
      max-width: 80%;
      padding: 12px 16px;
      border-radius: 14px;
      font-size: 0.95rem;
      line-height: 1.5;
      word-wrap: break-word;
      white-space: pre-wrap;
    }
    .msg-bot {
      align-self: flex-start;
      background: #f0f0f0;
      color: #252525;
      border-bottom-left-radius: 4px;
      white-space: normal;
    }
    .msg-bot p  { margin: 4px 0; }
    .msg-bot p:first-child { margin-top: 0; }
    .msg-bot p:last-child  { margin-bottom: 0; }
    .msg-bot ul { margin: 6px 0; padding-left: 20px; }
    .msg-bot li { margin-bottom: 3px; }
    .msg-user {
      align-self: flex-end;
      background: #1a1a1a;
      color: #fff;
      border-bottom-right-radius: 4px;
    }
    .msg-thinking {
      align-self: flex-start;
      background: #f0f0f0;
      color: #888;
      font-style: italic;
      border-bottom-left-radius: 4px;
    }
    .msg-error {
      background: #ffe6e6;
      color: #d32f2f;
    }

    /* Input area */
    .chat-input-area {
      border-top: 2px solid #f0f0f0;
      padding: 16px 20px;
      display: flex;
      gap: 12px;
      align-items: flex-end;
    }
    .chat-input-area textarea {
      flex: 1;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      padding: 12px 14px;
      font-family: 'Montserrat', sans-serif;
      font-size: 0.95rem;
      resize: none;
      outline: none;
      min-height: 44px;
      max-height: 100px;
    }
    .chat-input-area textarea:focus {
      border-color: var(--accent);
    }
    .chat-input-area button {
      background: var(--accent);
      color: #252525;
      border: none;
      border-radius: 10px;
      padding: 12px 24px;
      font-family: 'Montserrat', sans-serif;
      font-weight: 700;
      font-size: 0.95rem;
      cursor: pointer;
      transition: background 0.2s;
      white-space: nowrap;
    }
    .chat-input-area button:hover { background: #8cc428; }
    .chat-input-area button:disabled {
      background: #ccc;
      cursor: not-allowed;
    }

    /* Sample questions */
    .sample-section { margin-top: 30px; }
    .sample-section h3 {
      font-size: 1.1rem;
      font-weight: 700;
      margin-bottom: 14px;
      color: #252525;
    }
    .sample-questions {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    .sample-q {
      background: #fff;
      border: 2px solid #e0e0e0;
      border-radius: 20px;
      padding: 8px 16px;
      font-size: 0.85rem;
      font-family: 'Montserrat', sans-serif;
      cursor: pointer;
      transition: all 0.2s;
      color: #252525;
    }
    .sample-q:hover {
      border-color: var(--accent);
      background: #f6ffe0;
      transform: translateY(-1px);
    }

    /* Timestamp */
    .msg-time { font-size: 0.68rem; margin-top: 5px; opacity: 0.55; text-align: right; }
    .msg-user .msg-time { color: #fff; }
    .msg-bot  .msg-time { color: #555; }

    /* Copy button */
    .copy-btn {
      display: none;
      position: absolute; top: 8px; right: 8px;
      background: rgba(0,0,0,.06); border: none; border-radius: 5px;
      padding: 3px 7px; cursor: pointer; font-size: 0.75rem; color: #555;
      transition: background 0.15s;
    }
    .msg-bot:hover .copy-btn { display: block; }
    .copy-btn:hover { background: rgba(0,0,0,.14); }
    .msg-bot { position: relative; }

    /* Rating buttons */
    .msg-rating {
      display: flex;
      gap: 6px;
      margin-top: 6px;
      align-items: center;
    }
    .rate-btn {
      background: none;
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 2px 8px;
      font-size: 0.82rem;
      cursor: pointer;
      transition: all 0.15s;
      line-height: 1.4;
      color: #888;
    }
    .rate-btn:hover { background: #f0f0f0; border-color: #bbb; }
    .rate-btn.rated-up   { background: #e8f5e9; border-color: #66bb6a; color: #2e7d32; }
    .rate-btn.rated-down { background: #fce4ec; border-color: #ef9a9a; color: #c62828; }
    .rate-label { font-size: 0.72rem; color: #aaa; }

    /* Typing animation */
    .msg-typing { padding: 12px 18px !important; }
    .msg-typing span {
      display: inline-block; width: 7px; height: 7px;
      background: #aaa; border-radius: 50%; margin: 0 2px;
      animation: typingBounce 1.1s infinite ease-in-out;
    }
    .msg-typing span:nth-child(2) { animation-delay: 0.18s; }
    .msg-typing span:nth-child(3) { animation-delay: 0.36s; }
    @keyframes typingBounce {
      0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
      30%            { transform: translateY(-7px); opacity: 1; }
    }

    /* Resize grip */
    .resize-grip {
      height: 14px;
      background: #f4f4f4;
      cursor: ns-resize;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      border-top: 1px solid #e8e8e8;
      transition: background 0.15s;
      user-select: none;
      border-radius: 0 0 18px 18px;
    }
    .resize-grip:hover { background: #eaf7d0; }
    .resize-grip:active { background: #d5f0a0; }
    .resize-grip .grip-dots {
      width: 36px;
      height: 3px;
      background: #d0d0d0;
      border-radius: 2px;
      position: relative;
    }
    .resize-grip .grip-dots::before,
    .resize-grip .grip-dots::after {
      content: '';
      position: absolute;
      left: 0; right: 0;
      height: 3px;
      background: #d0d0d0;
      border-radius: 2px;
    }
    .resize-grip .grip-dots::before { top: -5px; }
    .resize-grip .grip-dots::after  { top:  5px; }
    .resize-grip:hover .grip-dots,
    .resize-grip:hover .grip-dots::before,
    .resize-grip:hover .grip-dots::after { background: #99d930; }
    .chat-container.resizing { user-select: none; }

    @media (max-width: 700px) {
      .chat-container { height: 550px; }
      .msg { max-width: 90%; }
      .chat-input-area { padding: 12px; }
      .intro-banner h1 { font-size: 2rem; }
    }

    /* Admin LLM parameter panel */
    .llm-admin-panel {
      max-width: 860px;
      margin: 0 auto 16px;
      background: #1a1a1a;
      border: 2px solid var(--accent);
      border-radius: 14px;
      padding: 16px 24px;
    }
    .llm-admin-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 14px;
      flex-wrap: wrap;
      gap: 8px;
    }
    .llm-admin-title {
      color: var(--accent);
      font-weight: 700;
      font-size: .9rem;
      text-transform: uppercase;
      letter-spacing: .5px;
    }
    .llm-mode-pill {
      font-size: .78rem;
      font-weight: 700;
      padding: 3px 12px;
      border-radius: 20px;
    }
    .llm-mode-pill.active   { background: #1b5e20; color: #a5d6a7; }
    .llm-mode-pill.inactive { background: #424242; color: #bdbdbd; }
    .llm-param-row {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 10px;
      flex-wrap: wrap;
    }
    .llm-param-row label {
      color: #ccc;
      font-size: .82rem;
      width: 110px;
      flex-shrink: 0;
    }
    .llm-param-row input[type=number] {
      width: 85px;
      padding: 5px 9px;
      border: 1.5px solid var(--accent);
      border-radius: 7px;
      background: #2e2e2e;
      color: #fff;
      font-size: .85rem;
    }
    .llm-param-hint { color: #777; font-size: .75rem; }
    .llm-save-btn {
      background: var(--accent);
      color: #1a1a1a;
      border: none;
      border-radius: 8px;
      padding: 7px 22px;
      font-weight: 700;
      font-size: .87rem;
      cursor: pointer;
      font-family: 'Montserrat', sans-serif;
      margin-top: 4px;
    }
    .llm-save-btn:hover { background: #8cc428; }
    .llm-saved-msg { color: var(--accent); font-size: .82rem; margin-left: 10px; }
    /* Mode toggle switch */
    .mode-toggle-row {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 16px;
      padding-bottom: 14px;
      border-bottom: 1px solid #333;
      flex-wrap: wrap;
    }
    .mode-toggle-label { color: #aaa; font-size: .82rem; }
    .toggle-track {
      display: flex;
      background: #333;
      border-radius: 30px;
      padding: 3px;
      gap: 3px;
      border: 1.5px solid #555;
    }
    .toggle-opt {
      padding: 5px 16px;
      border-radius: 24px;
      font-size: .8rem;
      font-weight: 700;
      font-family: 'Montserrat', sans-serif;
      cursor: pointer;
      border: none;
      transition: background 0.2s, color 0.2s;
      color: #888;
      background: transparent;
    }
    .toggle-opt.selected-rule { background: #555; color: #fff; }
    .toggle-opt.selected-llm  { background: var(--accent); color: #1a1a1a; }
    .toggle-opt:disabled { opacity: 0.4; cursor: not-allowed; }
    .no-key-note { color: #e57373; font-size: .75rem; }
  </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<section class="intro-banner">
  <h1>Schools <span class="accent-text">Chat Bot</span></h1>
  <p>Ask questions about the schools supported by Learn and Help.</p>
</section>

<div class="chat-page">

  <?php if ($is_admin): ?>
  <!-- Admin LLM parameter panel -->
  <div class="llm-admin-panel">
    <div class="llm-admin-top">
      <span class="llm-admin-title">&#9881; Chatbot Settings (Admin)</span>
      <?php if ($llm_active): ?>
        <span class="llm-mode-pill active">&#11044; LLM Mode &mdash; Claude (Anthropic)</span>
      <?php else: ?>
        <span class="llm-mode-pill inactive">&#11044; Rule-based Mode</span>
      <?php endif; ?>
    </div>

    <!-- Mode toggle -->
    <div class="mode-toggle-row">
      <span class="mode-toggle-label">Mode:</span>
      <?php if ($has_api_key): ?>
        <div class="toggle-track">
          <form method="post" style="display:contents;">
            <button type="submit" name="toggle_mode" value="rule"
              class="toggle-opt <?= $chatbot_mode !== 'llm' ? 'selected-rule' : '' ?>">
              Rule-based
            </button>
            <button type="submit" name="toggle_mode" value="llm"
              class="toggle-opt <?= $chatbot_mode === 'llm'  ? 'selected-llm'  : '' ?>">
              LLM (Claude)
            </button>
          </form>
        </div>
      <?php else: ?>
        <div class="toggle-track">
          <button class="toggle-opt selected-rule" disabled>Rule-based</button>
          <button class="toggle-opt" disabled title="No API key configured">LLM (Claude)</button>
        </div>
        <span class="no-key-note">&#9888; Set OPENAI_API_KEY in preferences to enable LLM mode</span>
      <?php endif; ?>
    </div>

    <!-- LLM parameters (only relevant when LLM mode available) -->
    <form method="post">
      <div class="llm-param-row">
        <label for="lp_temp">temperature</label>
        <input type="number" id="lp_temp" name="chatbot_temperature" value="<?= $chatbot_temperature ?>" min="0" max="2" step="0.05">
        <span class="llm-param-hint">0 = focused &nbsp; 2 = creative</span>
      </div>
      <div class="llm-param-row">
        <label for="lp_topp">top_p</label>
        <input type="number" id="lp_topp" name="chatbot_top_p" value="<?= $chatbot_top_p ?>" min="0" max="1" step="0.05">
        <span class="llm-param-hint">nucleus sampling (0–1)</span>
      </div>
      <div class="llm-param-row">
        <label for="lp_tokens">max_tokens</label>
        <input type="number" id="lp_tokens" name="chatbot_max_tokens" value="<?= $chatbot_max_tokens ?>" min="50" max="2000" step="50">
        <span class="llm-param-hint">max reply length</span>
      </div>
      <button type="submit" name="save_llm_params" class="llm-save-btn">Save Parameters</button>
      <?php if ($param_saved): ?><span class="llm-saved-msg">&#10003; Saved</span><?php endif; ?>
    </form>
  </div>
  <?php endif; ?>

  <!-- Chat widget -->
  <div class="chat-container">
    <div class="chat-header">
      <div class="chat-header-left">
        <span class="dot-indicator"></span>
        Learn and Help Schools Assistant
      </div>
      <button class="btn-clear-chat" onclick="clearChat()">Clear Chat</button>
    </div>
    <div class="chat-messages" id="chatMessages">
      <div class="msg msg-bot">Hello! I can answer questions about the schools supported by Learn and Help. Try asking me something like "How many schools are supported?" or click one of the sample questions below.</div>
    </div>
    <div class="chat-input-area">
      <textarea id="chatInput" rows="1" placeholder="Ask a question… (Shift+Enter for new line)" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMessage();}" oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px'"></textarea>
      <button id="sendBtn" onclick="sendMessage()">Send</button>
    </div>
    <div class="resize-grip" id="chatResizeGrip" title="Drag to resize">
      <div class="grip-dots"></div>
    </div>
  </div>

  <!-- Sample questions -->
  <div class="sample-section">
    <h3>Try asking:</h3>
    <div class="sample-questions">
      <span class="sample-q" onclick="askSample(this)">How many schools does Learn and Help support?</span>
      <span class="sample-q" onclick="askSample(this)">How many students are served by these libraries?</span>
      <span class="sample-q" onclick="askSample(this)">Which schools are in Andhra Pradesh?</span>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

<script>
const chatMessages = document.getElementById('chatMessages');
const chatInput    = document.getElementById('chatInput');
const sendBtn      = document.getElementById('sendBtn');

const INITIAL_MSG = "Hello! I can answer questions about the schools supported by Learn and Help. Try asking me something like \"How many schools are supported?\" or click one of the sample questions below.";
const STORAGE_KEY  = 'lnh_chat_history';

function saveHistory() {
  const msgs = [];
  document.querySelectorAll('#chatMessages .msg').forEach(el => {
    msgs.push({ html: el.innerHTML, cls: el.className });
  });
  try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(msgs)); } catch(e) {}
}

function loadHistory() {
  try {
    const stored = sessionStorage.getItem(STORAGE_KEY);
    if (!stored) return false;
    const msgs = JSON.parse(stored);
    if (!msgs || msgs.length === 0) return false;
    chatMessages.innerHTML = '';
    msgs.forEach(m => {
      const div = document.createElement('div');
      div.className = m.cls;
      div.innerHTML = m.html;
      chatMessages.appendChild(div);
    });
    chatMessages.scrollTop = chatMessages.scrollHeight;
    return true;
  } catch(e) { return false; }
}

function clearChat() {
  chatMessages.innerHTML = '';
  addMessage(INITIAL_MSG, 'msg-bot');
  chatInput.value = '';
  try { sessionStorage.removeItem(STORAGE_KEY); } catch(e) {}
}

function getTimeStr() {
  return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function addTypingIndicator() {
  const div = document.createElement('div');
  div.className = 'msg msg-bot msg-thinking msg-typing';
  div.id = 'typingIndicator';
  div.innerHTML = '<span></span><span></span><span></span>';
  chatMessages.appendChild(div);
  chatMessages.scrollTop = chatMessages.scrollHeight;
  return div;
}

// isHtml=true uses innerHTML (for server-generated HTML responses)
// isHtml=false uses textContent (for user messages and plain-text system messages)
// logId (optional) — if provided, adds thumbs up/down rating buttons on bot messages
function addMessage(text, type, isHtml = false, logId = 0) {
  const div = document.createElement('div');
  div.className = 'msg ' + type;
  if (isHtml) div.innerHTML = text;
  else        div.textContent = text;

  // Timestamp (skip for thinking indicator)
  if (!type.includes('thinking')) {
    const ts = document.createElement('div');
    ts.className = 'msg-time';
    ts.textContent = getTimeStr();
    div.appendChild(ts);
  }

  // Copy button on bot messages
  if (type.includes('msg-bot') && !type.includes('thinking') && !type.includes('error')) {
    const btn = document.createElement('button');
    btn.className = 'copy-btn';
    btn.title = 'Copy response';
    btn.textContent = '⧉';
    btn.addEventListener('click', () => {
      const tmp = document.createElement('div');
      tmp.innerHTML = isHtml ? text : div.textContent;
      const plain = tmp.textContent || tmp.innerText || '';
      navigator.clipboard.writeText(plain).then(() => {
        btn.textContent = '✓';
        setTimeout(() => btn.textContent = '⧉', 2000);
      });
    });
    div.appendChild(btn);
  }

  // Rating buttons (thumbs up/down) on real bot responses with a log ID
  if (type.includes('msg-bot') && !type.includes('thinking') && !type.includes('error') && logId > 0) {
    const ratingRow = document.createElement('div');
    ratingRow.className = 'msg-rating';
    ratingRow.innerHTML = '<span class="rate-label">Helpful?</span>';

    const upBtn   = document.createElement('button');
    upBtn.className = 'rate-btn';
    upBtn.title   = 'Helpful';
    upBtn.textContent = '👍';

    const downBtn = document.createElement('button');
    downBtn.className = 'rate-btn';
    downBtn.title   = 'Not helpful';
    downBtn.textContent = '👎';

    function submitRating(rating, activeBtn, inactiveBtn) {
      fetch('chat_rate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ log_id: logId, rating })
      });
      activeBtn.classList.add(rating === 1 ? 'rated-up' : 'rated-down');
      inactiveBtn.disabled = true;
      inactiveBtn.style.opacity = '0.35';
    }

    upBtn.addEventListener('click',   () => submitRating( 1, upBtn,   downBtn));
    downBtn.addEventListener('click', () => submitRating(-1, downBtn, upBtn));

    ratingRow.appendChild(upBtn);
    ratingRow.appendChild(downBtn);
    div.appendChild(ratingRow);
  }

  chatMessages.appendChild(div);
  chatMessages.scrollTop = chatMessages.scrollHeight;
  saveHistory();
  return div;
}

async function sendMessage() {
  const text = chatInput.value.trim();
  if (!text) return;

  addMessage(text, 'msg-user');
  chatInput.value = '';
  sendBtn.disabled = true;

  const typingEl = addTypingIndicator();

  try {
    const res = await fetch('chat_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: text })
    });

    const data = await res.json();
    typingEl.remove();
    addMessage(data.reply, 'msg-bot', true, data.log_id || 0);
  } catch (err) {
    typingEl.remove();
    addMessage('Oops! Something went wrong. Please try again.', 'msg-bot msg-error');
  }

  sendBtn.disabled = false;
  chatInput.focus();
}

// ── Resize grip ─────────────────────────────────────────────────────────────
(function() {
  const grip      = document.getElementById('chatResizeGrip');
  const container = document.querySelector('.chat-container');
  const MIN_H = 320;
  const MAX_H = () => Math.floor(window.innerHeight * 0.92);

  // Restore saved height
  try {
    const saved = sessionStorage.getItem('chatContainerHeight');
    if (saved) container.style.height = saved;
  } catch(e) {}

  let dragging = false, startY = 0, startH = 0;

  function startDrag(y) {
    dragging = true;
    startY   = y;
    startH   = container.getBoundingClientRect().height;
    container.classList.add('resizing');
    document.body.style.cursor     = 'ns-resize';
    document.body.style.userSelect = 'none';
  }
  function doDrag(y) {
    if (!dragging) return;
    const newH = Math.min(MAX_H(), Math.max(MIN_H, startH + (y - startY)));
    container.style.height = newH + 'px';
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }
  function endDrag() {
    if (!dragging) return;
    dragging = false;
    container.classList.remove('resizing');
    document.body.style.cursor     = '';
    document.body.style.userSelect = '';
    try { sessionStorage.setItem('chatContainerHeight', container.style.height); } catch(e) {}
  }

  // Mouse
  grip.addEventListener('mousedown', e => { startDrag(e.clientY); e.preventDefault(); });
  document.addEventListener('mousemove', e => doDrag(e.clientY));
  document.addEventListener('mouseup', endDrag);

  // Touch
  grip.addEventListener('touchstart', e => { startDrag(e.touches[0].clientY); e.preventDefault(); }, { passive: false });
  document.addEventListener('touchmove', e => { if (dragging) { doDrag(e.touches[0].clientY); e.preventDefault(); } }, { passive: false });
  document.addEventListener('touchend', endDrag);
})();

// Restore history on page load
loadHistory();

function askSample(el) {
  chatInput.value = el.textContent;
  sendMessage();
}
</script>
</body>
</html>
