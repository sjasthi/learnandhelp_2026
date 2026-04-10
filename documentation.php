<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>End-User Guide | Learn and Help</title>
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
        .doc h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #333;
            margin: 18px 0 4px;
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
        .doc .note {
            background: #fffbe6;
            border-left: 3px solid #99d930;
            padding: 8px 14px;
            font-size: 0.88rem;
            color: #555;
            margin: 10px 0;
        }
        .doc hr {
            border: none;
            border-top: 1px solid #e0e0e0;
            margin: 32px 0;
        }
        .doc a { color: #274606; }
        .doc a:hover { text-decoration: underline; }
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
 
    <h1>User Guide</h1>
    <p class="doc-subtitle">Learn and Help &nbsp;|&nbsp; Empowering Minds, Inspiring Generosity! &nbsp;|&nbsp; 2026</p>
 
    <h2>1. About Learn and Help</h2>
    <p>Learn and Help is a nonprofit program (EIN: 39-5158217) where middle and high school students learn computational thinking and computer programming. Proceeds support schools in need by providing books, educational materials, and resources.</p>
    <p>Donations are tax-deductible as permitted by law. Visit <a href="http://www.learnandhelp.com" target="_blank">www.learnandhelp.com</a> to learn more.</p>
    <ul>
        <li>200 Schools Supported</li>
        <li>3,455 Books Shipped</li>
        <li>151 Students Empowered</li>
    </ul>
 
    <hr>
 
    <h2>2. Navigating the Website</h2>
    <p>The navigation bar at the top of every page gives you quick access to all sections:</p>
    <ul>
        <li><strong>About Us</strong> — Learn about our mission, team, and nonprofit status.</li>
        <li><strong>Learn</strong> — Browse available classes and course information.</li>
        <li><strong>Blog</strong> — Read announcements, news, and updates.</li>
        <li><strong>Enroll Now</strong> — Sign up for a class.</li>
        <li><strong>FAQs</strong> — Find answers to frequently asked questions.</li>
        <li><strong>Events</strong> — View upcoming events and activities.</li>
        <li><strong>Help</strong> — See our impact, nominate schools, recommend nonprofits, and use our chatbot.</li>
        <li><strong>Login</strong> — Log in to your account. Once logged in, this changes to <strong>My Account</strong> and <strong>Log Off</strong>.</li>
    </ul>
 
    <hr>
 
    <h2>3. Creating an Account &amp; Logging In</h2>
    <h3>3.1 Creating an Account</h3>
    <ol>
        <li>Click <strong>Login</strong> in the top-right corner of the navigation bar.</li>
        <li>Below the password field, look for the option to create a new account.</li>
        <li>Fill in your details and submit the form.</li>
        <li>You now have a regular user account.</li>
    </ol>
    <h3>3.2 Logging In</h3>
    <ol>
        <li>Click <strong>Login</strong> in the navigation bar.</li>
        <li>Enter your email and password.</li>
        <li>Click the <strong>Login</strong> button.</li>
        <li>Once logged in, the nav bar will show <strong>My Account</strong> and <strong>Log Off</strong>.</li>
    </ol>
    <div class="note">To create a new account, go to the Login page and click <strong>Create Account</strong> at the bottom of the form.</div>
 
    <hr>
 
    <h2>4. Browsing Classes</h2>
    <p>Click <strong>Learn</strong> in the navigation bar to explore available courses.</p>
    <h3>Python 101</h3>
    <p>Introduction to Python — perfect for absolute beginners. Students can prepare for the AP CS Principles exam and earn PCEP certification. Next batch: September 2026 – May 2027 on weekends.</p>
    <h3>Python DS (Data Science)</h3>
    <p>Advanced course for students who completed Python 101. Covers OOP, NumPy, Pandas, and Matplotlib. Students complete a capstone solo project. <em>Prerequisite: Python 101.</em></p>
 
    <hr>
 
    <h2>5. Enrolling in a Class</h2>
    <ol>
        <li>Click <strong>Enroll Now</strong> in the navigation bar.</li>
        <li>Review available classes and select the one you want.</li>
        <li>Complete the enrollment form with your details.</li>
        <li>Submit your enrollment request.</li>
    </ol>
 
    <hr>
 
    <h2>6. Help &amp; Community Impact</h2>
    <p>Click <strong>Help</strong> in the navigation bar to access:</p>
    <ul>
        <li><strong>See Our Impact</strong> — Statistics on schools supported, books shipped, and students empowered.</li>
        <li><strong>Schools We Supported</strong> — Browse supported schools.</li>
        <li><strong>Books We Shipped</strong> — See delivered educational materials.</li>
        <li><strong>Nominate a School</strong> — Submit a nomination for a school in need.</li>
        <li><strong>Recommend a Non-Profit</strong> — Suggest a nonprofit for partnership.</li>
        <li><strong>Schools Chat Bot</strong> — Get quick answers using live database data.</li>
        <li><strong>User Guide</strong> — This user guide (the page you are reading now).</li>
    </ul>
    <div class="note">
        Example chatbot questions: "How many schools does Learn and Help support?", "Which schools are in Andhra Pradesh?", "Who are the board members?", "What classes are offered?", "Tell me about upcoming events."
    </div>
 
    <hr>
 
    <h2>7. About Us</h2>
    <ul>
        <li><strong>Mission</strong> — Our mission statement, vision, and core values.</li>
        <li><strong>Governing Board</strong> — Active board members with roles, locations, and bios.</li>
        <li><strong>LEAD Council</strong> — Student leadership opportunity for advanced Python students.</li>
        <li><strong>Bylaws</strong> — The organization's governing bylaws.</li>
        <li><strong>Contact Us</strong> — Contact information for the Learn and Help team.</li>
    </ul>
 
    <hr>
 
    <h2>8. Books &amp; Library</h2>
    <p>Go to <strong>Help → Books We Shipped</strong> to browse over 620 books organized by grade level (Primary School, Upper Primary, High School, and more). Each entry shows the title, author, publisher, year, page count, and availability status.</p>
 
    <hr>
 
    <h2>9. Events &amp; Blog</h2>
    <p><strong>Events:</strong> Click <strong>Events</strong> in the nav to see upcoming activities, workshops, and community gatherings.</p>
    <p><strong>Blog:</strong> Click <strong>Blog</strong> to read the latest announcements and news, including milestone updates like our 501(c)(3) nonprofit status.</p>
 
    <hr>
 
    <h2>10. Contact Us</h2>
    <ul>
        <li>📧 <strong>Email:</strong> <a href="mailto:Siva.Jasthi@gmail.com">Siva.Jasthi@gmail.com</a></li>
        <li>🌐 <strong>Website:</strong> <a href="http://www.learnandhelp.com" target="_blank">www.learnandhelp.com</a></li>
        <li>📱 <strong>Social Media:</strong> Follow us on Facebook, X (Twitter), and Instagram — links are in the footer.</li>
    </ul>
 
</div>
 
<?php include 'footer.php'; ?>
</body>
</html>