<!DOCTYPE html>
<html>
<head>
    <title>Align Content to Left</title>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Learn and Help</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <style>
        /* CSS Styles */
        body {
            font-family: Arial, sans-serif;
            font-size: 16px;
            margin: 0;
            background: #fff;
            color: #000;
        }

        .container {
            width: 80%;
            margin: 0 auto;
        }

        .form-container {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            padding: 10px;
            border-radius: 10px;
            color: #fff;
            background: #9ACD32;
            position: relative;
            float: right; 
            margin-right: 20%; 
        }

        .input-row {
            margin-bottom: 10px;
        }

        .input-row label {
            display: block;
            margin-bottom: 3px;
        }

        .input-row input,
        .input-row textarea {
            width: 80%;
            padding: 8px;
            border-radius: 3px;
            outline: 0;
            margin-bottom: 3px;
            font-size: 15px;
            font-family: Arial, sans-serif;
        }

        .input-row textarea {
            height: 100px;
        }

        .input-row input[type="submit"] {
            width: 100px;
            display: block;
            margin: 0 auto;
            text-align: center;
            color: #fff;
            cursor: pointer;
            background: #002f3a;
        }

        .input-row input[type="cancel"] {
            width: 100px;
            display: block;
            margin: 0 auto;
            text-align: center;
            color: #fff;
            cursor: pointer;
            background: #002f3a;
        }

        .success {
            background: #9fd2a1;
            padding: 5px 10px;
            text-align: center;
            color: #326b07;
            border-radius: 3px;
            font-size: 14px;
            margin-top: 10px;
        }

        /* Align content to the left */
        .contact-info {
            position: relative;
            left: 500px; 
            transform: translateY(-50%); 
           
        }




    



    </style>
</head>
<body>
<?php include 'show-navbar.php'; ?>
<?php show_navbar(); ?>

<header class="inverse">
    <div class="container">
        <h1> <span class="accent-text">About</span></h1>
    </div>
</header>
<section class="about-me">
    <div class="container">
        <h1><span class="accent-text" style="font-size:34px;">Any Questions?</span></h1>

        <div class="form-container">
            <form method="POST" name="emailContact">
                <div class="input-row">
                    <label>Name <em>*</em></label>
                    <input type="text" name="userName" required>
                </div>

                <div class="input-row">
                    <label>Email <em>*</em></label>
                    <input type="email" name="userEmail" required>
                </div>

                <div class="input-row">
                    <label>Phone <em>*</em></label>
                    <input type="tel" name="userPhone" required>
                </div>

                <div class="input-row">
                    <label>Message <em>*</em></label>
                    <textarea name="userMessage" required></textarea>
                </div>

                <div class="input-row">
                    <input type="submit" name="send" value="Submit">
                    <input type="button" value="Cancel" onclick="window.location.href='index.php'">
                </div>

                <?php if(isset($successMessage)): ?>
                    <div class="success">
                        <strong><?php echo $successMessage; ?></strong>
                    </div>
                <?php endif; ?>

                <?php if(isset($errorMessage)): ?>
                    <div class="error">
                        <strong><?php echo $errorMessage; ?></strong>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="contact-info">
            <p>Please contact <strong>Siva Jasthi</strong></p>
            <a href="mailto:Siva.Jasthi@gmail.com">Siva.Jasthi@gmail.com</a>
            <p>651.276.4671</p>
            <br>
        </div>

        <div class="website-creators">
        <h2>Website Creators</h2>
        <p>Learn and Help 1.0 development team:</p>
        <p><b>Daniel Duea</b>: DanielDuea@gmail.com</p>
        <p><b>Luis Duran-Enriquez</b>: duranluis320@gmail.com</p>
        <p><b>Michael Olson</b>: michaelolson01@gmail.com</p>
        <p><b>William Vicic</b>: william.vicic@gmail.com</p>
        <p><b>Seth Arndt</b>: 17seth.arndt@gmail.com</p>
        <p><b>Robert LaPrise</b>: lapriserobert1@gmail.com</p>
    </div>

</section>
<section class="about-me2">
    <div class="container22">
        <div class="info-box">
            <p><a href="http://localhost/learnandhelp.php">Learn and Help PDF</a></p>
        </div>
    </div>
</section>
</body>
</html>
