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
    <title>Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
    <style>
        body {
            background: #f8f8f8;
            margin: 0;
            font-family: 'Roboto', Arial, sans-serif;
        }
        .banner-wrapper {
            width: 100vw;
            left: 50%;
            margin-left: -50vw;
            height: 270px;
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
            font-family: 'Montserrat', sans-serif;
            font-size: 3.2em;
            font-weight: 700;
            color: #99d930;
            text-shadow: 0 2px 16px rgba(0,0,0,0.44);
            letter-spacing: 1px;
            z-index: 2;
        }

      
        #admin_icons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
            gap: 32px;
            max-width: 1080px;
            margin: 38px auto 60px auto;
            padding: 38px 22px 42px 22px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            position: relative;
            z-index: 2;
        }
        .admin_icon {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 7px 14px 7px;
            border-radius: 13px;
            background: #f8fbe9;
            box-shadow: 0 2px 12px rgba(153, 217, 48, 0.05);
            border: 2px solid transparent;
            transition: box-shadow 0.2s, background 0.18s, border-color 0.22s;
        }
        .admin_icon:hover, .admin_icon:focus-within {
            background: #edfae5;
            box-shadow: 0 6px 32px rgba(153, 217, 48, 0.10);
            border-color: #99d930;
        }
        .admin_icon img {
            max-width: 56px;
            max-height: 56px;
            margin-bottom: 15px;
            filter: drop-shadow(0 4px 8px #99d93022);
            transition: transform .19s;
        }
        .admin_icon:hover img {
            transform: scale(1.10) rotate(-3deg);
        }
        .admin_icon label {
            font-size: 1.12em;
            color: #274606;
            font-weight: 700;
            text-align: center;
            letter-spacing: 0.18px;
            background: none;
            border: none;
            margin-top: 0;
        }
        @media (max-width: 680px) {
            #admin_icons { padding: 14px 0 22px 0; gap: 12px;}
            .banner-title { font-size: 2.1em; }
        }
        .icon-attribution {
            margin: 44px auto 25px auto;
            text-align: center;
            font-size: 15px;
            color: #567;
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
    <h1 class="banner-title">Administration</h1>
</div>

<div id="admin_icons">
    
       <div class="admin_icon">
        <a href="admin_registrations.php"><img src="images/admin_icons/registrations_icon.png" alt="Registrations"></a>
        <label>Registrations</label>
    </div>
    
        <div class="admin_icon">
        <a href="admin_usersList.php"><img src="images/admin_icons/users_icon.png" alt="Users"></a>
        <label>Users</label>
    </div>
    
    <div class="admin_icon">
    <a href="admin_partners.php"><img src="images/admin_icons/partners_icon.png" alt="Partners"></a>
    <label>Partners</label>
   </div>
    
    <div class="admin_icon">
        <a href="admin_reports.php"><img src="images/admin_icons/reports_icon.png" alt="Reports"></a>
        <label>Reports</label>
    </div>
    
    
    <div class="admin_icon">
        <a href="admin_events.php"><img src="images/admin_icons/events_icon.png" alt="Events"></a>
        <label>Events</label>
    </div>
    
    <div class="admin_icon">
        <a href="admin_non_profits.php"><img src="images/admin_icons/non_profits_icon.png" alt="Non-Profits"></a>
        <label>Non-Profits</label>
    </div>
    
    
    <div class="admin_icon">
        <a href="admin_api.php"><img src="images/admin_icons/api.png" alt="API"></a>
        <label>API</label>
    </div>
   
    <div class="admin_icon">
        <a href="admin_blogs.php"><img src="images/admin_icons/blogs_icon.png" alt="Blogs"></a>
        <label>Blogs</label>
    </div>
    <div class="admin_icon">
        <a href="books.php"><img src="images/admin_icons/books_icon.png" alt="Books"></a>
        <label>Books</label>
    </div>
    <div class="admin_icon">
        <a href="book_report_html.php"><img src="images/admin_icons/books_icon.png" alt="Books Report(HTML)"></a>
        <label>Books Report (HTML)</label>
    </div>
    <div class="admin_icon">
        <a href="admin_classes.php"><img src="images/admin_icons/class.png" alt="Classes"></a>
        <label>Classes</label>
    </div>
    <div class="admin_icon">
        <a href="admin_email_distribution.php"><img src="images/admin_icons/email.png" alt="Email Distribution"></a>
        <label>Email Distribution</label>
    </div>
    <div class="admin_icon">
        <a href="admin_notes.php"><img src="images/admin_icons/admin_notes.png" alt="Admin Notes"></a>
        <label>Admin Notes</label>
    </div>
    <div class="admin_icon">
        <a href="admin_offerings_CRUD.php"><img src="images/admin_icons/counting.png" alt="Offerings"></a>
        <label>Offerings</label>
    </div>
    <div class="admin_icon">
        <a href="admin_preferences_CRUD.php"><img src="images/admin_icons/control.png" alt="Preferences"></a>
        <label>Preferences</label>
    </div>
 
    
    <div class="admin_icon">
        <a href="admin_review_suggestions.php"><img src="images/admin_icons/review.jpg" alt="Suggested Schools"></a>
        <label>Suggested Schools</label>
    </div>
    <div class="admin_icon">
        <a href="admin_schools.php"><img src="images/admin_icons/school.png" alt="Schools"></a>
        <label>Schools</label>
    </div>
    <div class="admin_icon">
        <a href="school_report_html.php"><img src="images/admin_icons/school.png" alt="Schools Report (HTML)"></a>
        <label>Schools Report (HTML)</label>
    </div>
    <div class="admin_icon">
        <a href="admin_upload_csv.php"><img src="images/admin_icons/upload.png" alt="Upload"></a>
        <label>Upload</label>
    </div>

    <div class="admin_icon">
        <a href="whats_app.php"><img src="images/admin_icons/whats_app.png" alt="Whats App"></a>
        <label>Whats App</label>
    </div>
    <div class="admin_icon">
        <a href="instructors.php"><img src="images/admin_icons/instructor.png" alt="Instructors"></a>
        <label>Instructors</label>
    </div>
</div>

<div class="icon-attribution">
    <a href="https://www.flaticon.com/authors/freepik" title="freepik icons" id="icon_attribution" style="color:#5c80ad;">Icons created by Freepik - Flaticon</a>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
