<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
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
    }
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
    }
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

    @media (max-width: 700px) {
      .chat-container { height: 550px; }
      .msg { max-width: 90%; }
      .chat-input-area { padding: 12px; }
      .intro-banner h1 { font-size: 2rem; }
    }
  </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<section class="intro-banner">
  <h1>Schools <span class="accent-text">Chat Bot</span></h1>
  <p>Ask questions about the schools supported by Learn and Help.</p>
</section>

<div class="chat-page">
  <!-- Chat widget -->
  <div class="chat-container">
    <div class="chat-header">
      <span class="dot-indicator"></span>
      Learn and Help Schools Assistant
    </div>
    <div class="chat-messages" id="chatMessages">
      <div class="msg msg-bot">Hello! I can answer questions about the schools supported by Learn and Help. Try asking me something like "How many schools are supported?" or click one of the sample questions below.</div>
    </div>
    <div class="chat-input-area">
      <textarea id="chatInput" rows="1" placeholder="Ask a question about schools..." onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMessage();}"></textarea>
      <button id="sendBtn" onclick="sendMessage()">Send</button>
    </div>
  </div>

  <!-- Sample questions -->
  <div class="sample-section">
    <h3>Try asking:</h3>
    <div class="sample-questions">
      <span class="sample-q" onclick="askSample(this)">How many schools does Learn and Help support?</span>
      <span class="sample-q" onclick="askSample(this)">Is Mounds View High School supported?</span>
      <span class="sample-q" onclick="askSample(this)">How many schools are in Proposed state?</span>
      <span class="sample-q" onclick="askSample(this)">How many schools have Completed status?</span>
      <span class="sample-q" onclick="askSample(this)">How many students are served by these libraries?</span>
      <span class="sample-q" onclick="askSample(this)">Which schools are in Andhra Pradesh?</span>
      <span class="sample-q" onclick="askSample(this)">Which schools are in Telangana?</span>
      <span class="sample-q" onclick="askSample(this)">How many schools are supported by PGNF?</span>
      <span class="sample-q" onclick="askSample(this)">How many schools are supported by NRIVA?</span>
      <span class="sample-q" onclick="askSample(this)">What is the largest school by enrollment?</span>
      <span class="sample-q" onclick="askSample(this)">Are there any schools in Tamil Nadu?</span>
      <span class="sample-q" onclick="askSample(this)">Which schools are high schools?</span>
      <span class="sample-q" onclick="askSample(this)">Which schools are primary schools?</span>
      <span class="sample-q" onclick="askSample(this)">Is MVHS supported by Learn and Help?</span>
      <span class="sample-q" onclick="askSample(this)">Is MV High School in the program?</span>
      <span class="sample-q" onclick="askSample(this)">How many states does Learn and Help operate in?</span>
      <span class="sample-q" onclick="askSample(this)">Are there any public schools in the program?</span>
      <span class="sample-q" onclick="askSample(this)">Are there any private schools supported?</span>
      <span class="sample-q" onclick="askSample(this)">Which school has the most students enrolled?</span>
      <span class="sample-q" onclick="askSample(this)">Is Z P H School Boggaram supported?</span>
      <span class="sample-q" onclick="askSample(this)">What is the status of Chethana school?</span>
      <span class="sample-q" onclick="askSample(this)">How many schools are in Guntur?</span>
      <span class="sample-q" onclick="askSample(this)">Are there any schools in Hyderabad?</span>
      <span class="sample-q" onclick="askSample(this)">What types of schools does Learn and Help support?</span>
      <span class="sample-q" onclick="askSample(this)">Tell me about the schools in Vijayawada area.</span>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

<script>
const chatMessages = document.getElementById('chatMessages');
const chatInput    = document.getElementById('chatInput');
const sendBtn      = document.getElementById('sendBtn');

function addMessage(text, type) {
  const div = document.createElement('div');
  div.className = 'msg ' + type;
  div.textContent = text;
  chatMessages.appendChild(div);
  chatMessages.scrollTop = chatMessages.scrollHeight;
  return div;
}

async function sendMessage() {
  const text = chatInput.value.trim();
  if (!text) return;

  // Show user message
  addMessage(text, 'msg-user');
  chatInput.value = '';
  sendBtn.disabled = true;

  // Show thinking indicator
  const thinkingEl = addMessage('Thinking...', 'msg-thinking');

  try {
    const res = await fetch('chat_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: text })
    });

    const data = await res.json();

    // Replace thinking with actual response
    thinkingEl.remove();
    addMessage(data.reply, 'msg-bot');
  } catch (err) {
    thinkingEl.remove();
    addMessage('Oops! Something went wrong. Please try again.', 'msg-bot msg-error');
  }

  sendBtn.disabled = false;
  chatInput.focus();
}

function askSample(el) {
  chatInput.value = el.textContent;
  sendMessage();
}
</script>
</body>
</html>
