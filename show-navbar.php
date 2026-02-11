<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function show_navbar() {
    // Detect login status from any historical keys
    //<a href="students.php">Students</a> Shut this off due to privacy reasons
    
    $sessionId = $_SESSION['user_id'] ?? $_SESSION['User_Id'] ?? $_SESSION['user_Id'] ?? null;
    $loggedIn  = !empty($sessionId);
    $role      = strtolower($_SESSION['role'] ?? '');

    echo '
    <div class="navbar">
      <div class="navbar-center">
        <a href="index.php" id="nav-logo">
            <img id="logo" src="images/learnandhelplogo-white.avif" alt="Learn N Help Logo">
        </a>


        <div class="dropdown">
          <a href="#" class="dropbtn" onclick="return false;">About Us</a>
          <div class="dropdown-content">
            <a href="about_mission.php">Mission and Vision</a>
            <a href="about_bylaws.php">Bylaws</a>
            <a href="about_governing_board.php">Governing Board</a>
            <a href="about_lead_council.php">Lead Council</a>
            <a href="about_contact_us.php">Contact Us</a>
          </div>
        </div>
        
        <div class="dropdown">
          <a href="#" class="dropbtn" onclick="return false;">Learn</a>
          <div class="dropdown-content">
            <a href="classes.php">Classes</a>
            <a href="instructors.php">Instructors</a>
          </div>
        </div>
        
        
        <div class="dropdown">
          <a href="#" class="dropbtn" onclick="return false;">Help</a>
          <div class="dropdown-content">
            <a href="our_impact.php">See Our Impact</a>
            <a href="schools.php">Schools We Supported</a>
            <a href="books.php">Books We shipped</a>
            <a href="suggest_school.php">Nominate a School</a>
            <a href="recommend_non_profit.php">Recommend a Non-Profit</a>
          </div>
        </div>
        

        <a href="blog.php">Blog</a>
        <a href="enroll.php" id="register">Enroll Now</a>
        <a href="faq.php">FAQs</a>
        <a href="events.php">Events</a>';
        

    // When logged in, show My Account [+ Administration if admin] + Log Off
    if ($loggedIn) {
        echo '<a href="my_account.php" class="navbar-btn" style="margin-left:12px;">My Account</a>';
        if ($role === "admin") {
            echo '<a href="administration.php">Administration</a>';
        }
        echo '<a href="logoff.php" class="navbar-btn" style="margin-left:12px;">Log Off</a>';
    } else {
        // When logged out, show Login (optionally Create Account)
        echo '<a href="login.php" class="navbar-btn" style="margin-left:12px;">Login</a>';
        // echo '<a href="create_account.php" class="navbar-btn" style="margin-left:12px;">Create Account</a>';
    }

    echo '
      </div>
    </div>';
}
?>
