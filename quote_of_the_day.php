<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Learn and Help</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/quote_style.css" rel="stylesheet">
	<link href="css/main.css" rel="stylesheet">
	
</head>

<body>
    <?php include 'show-navbar.php'; ?>
    <?php show_navbar(); ?>
    <header class="inverse">
        <div class="container">
            <h1><span class="accent-text">Quote of the Day</span></h1>
        </div>
    </header>
    <section class="quote-section">
        <div class="container">
            <div class="quote-container">
                <h1>Quote of the Day</h1>
                <p id="quote">Loading...</p>
            </div>
        </div>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('fetch_quote.php')
                .then(response => response.text())
                .then(quote => {
                    document.getElementById('quote').textContent = quote;
                })
                .catch(error => {
                    document.getElementById('quote').textContent = 'Failed to fetch quote. Please try again later.';
                });
        });
    </script>
</body>

</html>
