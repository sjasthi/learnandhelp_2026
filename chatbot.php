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
		border: 3px solid #666666;
		border-radius: 10px;
		margin: 50px auto;
		overflow: hidden;
		width: 500px;
		overflow-y: clip;
		height: 600px;
		background: rgb(255, 255, 255);
		background-size: contain;
		box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
		background-repeat: no-repeat;
		background-position: center;
	}
 
	header {
		background-color: #666666;
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
		width: 500px;
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
		width: 522px;
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
	</style>
</head>
 
<body>
	<header style="display:none;" id="closedheader">
		<h2 onClick="showBot()">ChatBot</h2>
		</header>
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
