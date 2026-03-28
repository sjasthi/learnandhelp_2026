<!DOCTYPE html>
<html lang="en">

<?php
	require 'db_configuration.php';
	$status = session_status();
	if ($status == PHP_SESSION_NONE) {
		session_start();
	}
	
	$connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
	
	if ($connection === false) {
		die("Failed to connect to database: " . mysqli_connect_error());
	}
	

	$api_key_query = "SELECT Value FROM preferences WHERE Preference_Name = 'OPENAI_API_KEY';";
	$api_key_result = $connection->query($api_key_query);
	$api_key_array = $api_key_result->fetch_assoc();
	$api_key = $api_key_array["Value"];
	mysqli_free_result($api_key_result);

	$keywords_query = "SELECT Value FROM preferences WHERE Preference_Name = 'KEYWORDS';";
	$keywords_result = $connection->query($keywords_query);
	$keywords_array = $keywords_result->fetch_assoc();
	$keywords = $keywords_array["Value"];
	mysqli_free_result($keywords_result);

	// LLM parameter defaults
	$chatbot_temperature = 0.2;
	$chatbot_top_p       = 0.9;
	$chatbot_max_tokens  = 300;

	$param_rows = $connection->query("SELECT Preference_Name, Value FROM preferences WHERE Preference_Name IN ('CHATBOT_TEMPERATURE','CHATBOT_TOP_P','CHATBOT_MAX_TOKENS')");
	while ($pr = $param_rows->fetch_assoc()) {
		if ($pr['Preference_Name'] === 'CHATBOT_TEMPERATURE') $chatbot_temperature = (float)$pr['Value'];
		if ($pr['Preference_Name'] === 'CHATBOT_TOP_P')       $chatbot_top_p       = (float)$pr['Value'];
		if ($pr['Preference_Name'] === 'CHATBOT_MAX_TOKENS')  $chatbot_max_tokens  = (int)$pr['Value'];
	}

	// Admin: save parameters via POST
	$is_admin      = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
	$param_saved   = false;
	if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_llm_params'])) {
		$new_temp   = max(0.0, min(2.0, (float)$_POST['chatbot_temperature']));
		$new_topp   = max(0.0, min(1.0, (float)$_POST['chatbot_top_p']));
		$new_tokens = max(50,  min(2000, (int)$_POST['chatbot_max_tokens']));
		$upserts = [
			'CHATBOT_TEMPERATURE' => (string)$new_temp,
			'CHATBOT_TOP_P'       => (string)$new_topp,
			'CHATBOT_MAX_TOKENS'  => (string)$new_tokens,
		];
		foreach ($upserts as $pname => $pval) {
			$stmt = $connection->prepare("INSERT INTO preferences (Preference_Name, Value) VALUES (?, ?) ON DUPLICATE KEY UPDATE Value = ?");
			$stmt->bind_param("sss", $pname, $pval, $pval);
			$stmt->execute(); $stmt->close();
		}
		$chatbot_temperature = $new_temp;
		$chatbot_top_p       = $new_topp;
		$chatbot_max_tokens  = $new_tokens;
		$param_saved = true;
	}
	?>


<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>ChatBot</title>
	<style>
	* {
		box-sizing: border-box;
		margin: 0;
		padding: 0;
	}
	
	body {
		font-family: Arial, Helvetica, sans-serif;
		font-weight: 400;
		font-style: normal;
		background-color: #ffffff;
	}
 
	.chatBot {
		border: 3px solid #99d930;
		border-radius: 10px;
		margin: 50px auto;
		overflow: hidden;
		width: min(500px, calc(100% - 24px));
		overflow-y: clip;
		height: 600px;
		background: rgb(255, 255, 255);
		background-size: contain;
		box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
		background-repeat: no-repeat;
		background-position: center;
		box-sizing: border-box;
	}

	header {
		background-color: #1a1a1a;
		text-align: center;
		padding: 10px 0;
		border-radius: 7px 7px 0 0;
	}
 
	header h2 {
		color: #fff;
		margin: 0;
	}
	
	#closedheader {
		margin: 50px auto;
		width: min(500px, calc(100% - 24px));
		box-sizing: border-box;
	}
 
	.chatbox {
		padding: 15px;
		list-style: none;
		overflow-y: auto;
		height: 400px;
	}
 
	.chatbox li {
		margin-bottom: 10px;
	}
 
	.chat p {
		padding: 10px;
		border-radius: 10px;
		max-width: 70%;
		word-wrap: break-word;
	}
 
	.chat-outgoing p {
		background-color: #162887;
		align-self: flex-end;
		color: #fff;
	}
 
	.chat-incoming p {
		background-color: #eaeaea;
	}
 
	.chat-input {
		padding: 10px;
		border-top: 1px solid #ccc;
	}
 
	.chat-input textarea {
		width: 100%;
		padding: 10px;
		border: 1px solid #ccc;
		border-radius: 7px;
		resize: none;
		outline: none;
		overflow-y: scroll;
		background-color: #dcdcdc85;
		font-size: 16px;
		color: green;
		font-weight: 600;
		margin-top: -10px;
		margin-left: -15px;
		height: 71px;
	}
 
	#cross {
		float: right;
		position: relative;
		top: -30px;
		left: -15px;
		cursor: pointer;
		color: white;
		font-weight: bolder;
		font-size: 28px;
	}
 
	#cross:hover {
		color: red;
		transition: all .5s;
	}
 
	.chatbox .chat p.error {
		color: #ffffff;
		background-color: #ff3737e8;
	}
 
	#sendBTN {
		width: 100%;
		padding: 8px;
		border: 0;
		outline: none;
		font-size: 20px;
		font-weight: 600;
		border-radius: 7px;
		background-color: #99d930;
		cursor: pointer;
		color: white;
		margin-top: 12px;
	}
 
	.lastMessage {
		margin-top: 50px;
		font-size: 35px;
		font-weight: 600;
		color: darkgreen;
		margin-left: 550px;
	}
	/* Admin LLM parameter panel */
	.llm-params-panel {
		width: min(500px, calc(100% - 24px));
		margin: 0 auto 18px auto;
		box-sizing: border-box;
		background: #1a1a1a;
		border: 2px solid #99d930;
		border-radius: 10px;
		padding: 14px 20px;
		font-family: Arial, sans-serif;
	}
	.llm-params-panel h4 {
		color: #99d930;
		margin: 0 0 12px;
		font-size: .9em;
		letter-spacing: .5px;
		text-transform: uppercase;
	}
	.llm-params-panel .param-row {
		display: flex;
		align-items: center;
		gap: 10px;
		margin-bottom: 8px;
	}
	.llm-params-panel label {
		color: #ccc;
		font-size: .82em;
		width: 110px;
		flex-shrink: 0;
	}
	.llm-params-panel input[type=number] {
		width: 80px;
		padding: 4px 8px;
		border: 1px solid #99d930;
		border-radius: 6px;
		background: #333;
		color: #fff;
		font-size: .85em;
	}
	.llm-params-panel .param-hint {
		color: #888;
		font-size: .75em;
	}
	.llm-params-panel .save-btn {
		background: #99d930;
		color: #1a1a1a;
		border: none;
		border-radius: 6px;
		padding: 6px 18px;
		font-weight: 700;
		font-size: .85em;
		cursor: pointer;
		margin-top: 6px;
	}
	.llm-params-panel .save-btn:hover { background: #7db020; }
	.llm-params-panel .saved-msg { color: #99d930; font-size: .82em; margin-left: 8px; }
	</style>
</head>
 
<body>
	<header style="display:none;" id="closedheader">
		<h2 onClick="showBot()">ChatBot</h2>
		</header>
    <?php if ($is_admin): ?>
    <div class="llm-params-panel">
        <h4>&#9881; LLM Parameters (Admin)</h4>
        <form method="post">
            <div class="param-row">
                <label for="cb_temp">temperature</label>
                <input type="number" id="cb_temp" name="chatbot_temperature" value="<?= $chatbot_temperature ?>" min="0" max="2" step="0.05">
                <span class="param-hint">0 = focused &nbsp; 2 = creative</span>
            </div>
            <div class="param-row">
                <label for="cb_topp">top_p</label>
                <input type="number" id="cb_topp" name="chatbot_top_p" value="<?= $chatbot_top_p ?>" min="0" max="1" step="0.05">
                <span class="param-hint">nucleus sampling (0–1)</span>
            </div>
            <div class="param-row">
                <label for="cb_tokens">max_tokens</label>
                <input type="number" id="cb_tokens" name="chatbot_max_tokens" value="<?= $chatbot_max_tokens ?>" min="50" max="2000" step="50">
                <span class="param-hint">max reply length</span>
            </div>
            <button type="submit" name="save_llm_params" class="save-btn">Save</button>
            <?php if ($param_saved): ?><span class="saved-msg">&#10003; Saved</span><?php endif; ?>
        </form>
    </div>
    <?php endif; ?>

    <div class="chatBot">
        <header>
            <h2>ChatBot</h2>
           <span alt="Close"
                  id="cross"
                  onclick="cancel()">X</span>
        </header>
        <ul class="chatbox">
            <li class="chat-incoming chat">
                <p>Hello! How can I assist you today?</p>
            </li>
        </ul>
        <div class="chat-input">
            <textarea rows="0" cols="17"
                      placeholder="Enter a message..."></textarea>
            <button id="sendBTN">Send</button>
        </div>
    </div>

    <script>
		const chatInput = document.querySelector('.chat-input textarea');
		const sendChatBtn = document.querySelector('.chat-input button');
		const chatbox = document.querySelector(".chatbox");
	 
		let userMessage;
		
		const API_KEY = "<?php echo $api_key; ?>";
		const API_KEY_STR = "Bearer " + API_KEY;

		const keywords = "<?php echo $keywords; ?>";
		const CHATBOT_TEMPERATURE = <?= json_encode($chatbot_temperature) ?>;
		const CHATBOT_TOP_P       = <?= json_encode($chatbot_top_p) ?>;
		const CHATBOT_MAX_TOKENS  = <?= json_encode($chatbot_max_tokens) ?>;
	 
		const createChatLi = (message, className) => {
		const chatLi = document.createElement("li");
		chatLi.classList.add("chat", className);
		let chatContent = className === "chat-outgoing" ? `<p>${message}</p>` : `<p>${message}</p>`;
			chatLi.innerHTML = chatContent;
		return chatLi;
	}
	 
	const generateResponse = (incomingChatLi) => {
		const API_URL = "https://api.openai.com/v1/chat/completions";
		const messageElement = incomingChatLi.querySelector("p");
		const requestOptions = {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
				"Authorization": API_KEY_STR
			},
			body: JSON.stringify({
				"model": "gpt-4o-mini",
				"temperature": CHATBOT_TEMPERATURE,
				"top_p": CHATBOT_TOP_P,
				"max_tokens": CHATBOT_MAX_TOKENS,
				"messages": [
					{
						role: "system",
						content: "Please answer the following question in the context of " + keywords
					},
					{
						role: "user",
						content: userMessage
					}
				]
			})
		};
	 
		//Test to showcase functionality, in case of API problems.
		if(API_KEY === "" || API_KEY === "test" || API_KEY === "key"){
			messageElement.textContent = "You typed " + userMessage + ", key: " + API_KEY + ", keywords: " + keywords;
		}
		else{
		//Fetches result from API using requestOptions.
		fetch(API_URL, requestOptions).then(res => {
				if (!res.ok) {
					throw new Error("Network response was not ok");
				}
				return res.json();
			}).then(data => {
				messageElement.textContent = data.choices[0].message.content;
			}).catch((error) => {
				messageElement.classList.add("error");
				messageElement.textContent = "Oops! Something went wrong. Please try again!";
			}).finally(() => chatbox.scrollTo(0, chatbox.scrollHeight));
		}
	};
	 
	
	const handleChat = () => {
		userMessage = chatInput.value.trim();
		if (!userMessage) {
			return;
		}
		chatbox.appendChild(createChatLi(userMessage, "chat-outgoing"));
		chatbox.scrollTo(0, chatbox.scrollHeight);
		chatInput.value = "";
	 
		setTimeout(() => {
			const incomingChatLi = createChatLi("Thinking...", "chat-incoming")
			chatbox.appendChild(incomingChatLi);
			chatbox.scrollTo(0, chatbox.scrollHeight);
			generateResponse(incomingChatLi);
		}, 600);
	}
	 
	sendChatBtn.addEventListener("click", handleChat);

	chatInput.addEventListener('keydown', (e) => {
		if (e.key === 'Enter' && !e.shiftKey) {
			e.preventDefault();
			handleChat();
		}
	});

	function cancel() {
		let chatbotcomplete = document.querySelector(".chatBot");
		let closedheader = document.querySelector("#closedheader");

		if (chatbotcomplete.style.display != 'none') {
			chatbotcomplete.style.display = "none";
			if(closedheader.style.display == 'none'){
				closedheader.style.display = "";
			}
			/*
			let lastMsg = document.createElement("p");
			lastMsg.textContent = 'Thanks for using our Chatbot!';
			lastMsg.classList.add('lastMessage');
			document.body.appendChild(lastMsg)
			*/
		}
		
	}
	
	function showBot() {
		let chatbotcomplete = document.querySelector(".chatBot");
		let closedheader = document.querySelector("#closedheader");
		
		if (chatbotcomplete.style.display == 'none') {
			chatbotcomplete.style.display = "";
			if(closedheader.style.display != 'none'){
				closedheader.style.display = "none";
			}
		}
	}
	</script>
</body>
 
</html>
