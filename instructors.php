<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Instructors | Learn & Help</title>
  <link rel="icon" href="images/icon_logo.png" type="image/png">

  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap"  rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

  <style>
    :root { --accent:#99D930; --card-shadow:0 4px 6px rgba(0,0,0,.1); }
    body { font-family:'Roboto',sans-serif; background:#fafafa; margin:0; }

    .intro-banner {
      background:#1a1a1a; color:#fff; text-align:center; padding:24px 20px 20px;
    }

    .intro-banner { background:#1a1a1a; color:#fff; text-align:center; padding:24px 20px 20px; }
    .intro-banner h1 { font-family:'Montserrat',sans-serif; font-size:3rem; font-weight:900; margin:0 0 0px; }
    .intro-banner h1 .accent-text { color:var(--accent); }
    .intro-banner p { max-width:820px; margin:0 auto; font-size:1.5rem; line-height:1.65; }

    .list-container {
      max-width:1000px;
      margin:1.5rem auto;      
      padding:0 20px;         
    }

    .search-bar {
      display:flex;
      justify-content:center;  
      align-items:center;
      gap:12px;
      margin: 10px 0 24px;     
      flex-wrap:wrap;          
    }
    .search-bar #instructor-search {
      flex:0 0 auto;
      width:auto;
      max-width:420px;
      padding:.6rem 1rem;
      font-size:1rem;
      border:1px solid #ccc;
      border-radius:6px;
    }

    .admin-btn {
      background:#1976d2;
      color:#fff;
      border:none;
      padding:.25rem .9rem;
      font-size:.8rem;
      border-radius:20px;
      cursor:pointer;
      margin-right:4px;
      text-decoration:none;
      display:inline-block;
      text-align:center;
    }
    .admin-btn:hover { background:#1259a3; }

    .admin-btn-large {
      font-size:1rem;
      padding:.5rem 1.4rem;
    }

    /* Back button styling */
    .back-btn {
      background:var(--accent);
      color:#fff;
      border:none;
      padding:.75rem 1.5rem;
      font-size:1rem;
      border-radius:6px;
      cursor:pointer;
      text-decoration:none;
      display:inline-block;
      margin-bottom:20px;
      font-family:'Montserrat',sans-serif;
      font-weight:500;
    }
    .back-btn:hover { background:#7fc225; }

    .cards-wrapper {
      display:flex;
      flex-direction:column;
      gap:40px;
    }
    .instructor-card {
      display:flex; flex-direction:row; background:#fff; border-radius:10px;
      box-shadow:var(--card-shadow); overflow:hidden; transition:transform .25s;
    }
    .instructor-card:hover { transform:translateY(-5px); }
    .card-img { width:45%; flex-shrink:0; overflow:hidden; }
    .card-img img { width:100%; height:100%; object-fit:cover; display:block; }
    .card-body {
      width:55%; padding:24px 28px;
      display:flex; flex-direction:column; justify-content:space-between;
    }
    .card-body h3 {
      font-family:'Montserrat',sans-serif; margin:0 0 12px; font-size:1.6rem;
    }
    
    /* Make instructor names clickable */
    .instructor-name {
      color:#1976d2;
      cursor:pointer;
      text-decoration:none;
      transition:color .25s;
    }
    .instructor-name:hover { color:var(--accent); }
    
    .card-body .bio {
      font-size:.85em; line-height:1.6; color:#333;
      font-family:'Montserrat',sans-serif; white-space:pre-wrap;
    }
    .admin-controls { margin-top:14px; }

    /* Individual instructor view styles */
    .individual-view {
      display:none;
    }
    .individual-view.active {
      display:block;
    }
    .individual-instructor {
      background:#fff;
      border-radius:10px;
      box-shadow:var(--card-shadow);
      overflow:hidden;
      margin-bottom:20px;
    }
    .individual-header {
      display:flex;
      flex-direction:row;
      min-height:300px;
    }
    .individual-img {
      width:40%;
      flex-shrink:0;
      overflow:hidden;
    }
    .individual-img img {
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
    }
    .individual-info {
      width:60%;
      padding:32px 36px;
      display:flex;
      flex-direction:column;
      justify-content:center;
    }
    .individual-info h2 {
      font-family:'Montserrat',sans-serif;
      font-size:2.5rem;
      margin:0 0 20px;
      color:#1a1a1a;
    }
    .individual-bio {
      font-size:1.1rem;
      line-height:1.7;
      color:#333;
      font-family:'Montserrat',sans-serif;
      white-space:pre-wrap;
    }

    @media (max-width:768px) {
      .instructor-card { flex-direction:column; }
      .card-img, .card-body { width:100%; }
      .card-img { height:150px; }
      
      .individual-header { flex-direction:column; min-height:auto; }
      .individual-img, .individual-info { width:100%; }
      .individual-img { height:200px; }
      .individual-info { padding:24px; }
      .individual-info h2 { font-size:2rem; }
    }

    /* Hide all instructors view when individual is shown */
    .all-instructors-view.hidden {
      display:none;
    }
  </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<section class="intro-banner">
  <h1><span class="accent-text">Meet&nbsp;our&nbsp;Instructors</span></h1>
  <p> Empowering Minds, Inspiring Generosity!  </p>
</section>

<div class="list-container">

  <!-- Back to All Instructors button (hidden by default) -->
  <button id="back-to-all" class="back-btn" style="display:none;" onclick="showAllInstructors()">
    ← Back to All Instructors
  </button>

  <!-- All Instructors View -->
  <div id="all-instructors-view" class="all-instructors-view">
    <div class="search-bar">
      <input id="instructor-search" type="text" placeholder="Search instructors…">
      <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="add_instructor.php" class="admin-btn admin-btn-large">Add Instructor</a>
      <?php endif; ?>
    </div>

    <div class="cards-wrapper" id="cards-wrapper">
      <?php
      require 'db_configuration.php';
      $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
      if ($conn->connect_error) { die("Connection failed: ".$conn->connect_error); }

      $res = $conn->query("SELECT * FROM instructor ORDER BY instructor_ID ASC");
      while($row = $res->fetch_assoc()):
        $id    = $row['instructor_ID'];
        $first = htmlspecialchars($row['First_name']);
        $last  = htmlspecialchars($row['Last_name']);
        $bio   = nl2br(htmlspecialchars($row['Bio_data']));
        $img   = htmlspecialchars(trim(explode(',', $row['Image'])[0]));
      ?>
        <article class="instructor-card" 
                 data-filter="<?= strtolower("$first $last ".$row['Bio_data']) ?>"
                 data-id="<?= $id ?>"
                 data-firstname="<?= htmlspecialchars($first) ?>"
                 data-lastname="<?= htmlspecialchars($last) ?>"
                 data-bio="<?= htmlspecialchars($row['Bio_data']) ?>"
                 data-image="<?= htmlspecialchars($img) ?>">
          <div class="card-img"><img src="<?= $img ?>" alt="<?= "$first $last" ?>"></div>
          <div class="card-body">
            <div>
              <h3>
                <a href="instructors.php?id=<?= $id ?>" class="instructor-name" onclick="showInstructorFromCard(this); return false;">
                  <?= "$first $last" ?>
                </a>
              </h3>
              <div class="bio"><?= $bio ?></div>
            </div>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="admin-controls">
              <form action="admin_edit_instructors.php" method="POST" style="display:inline;">
                <input type="hidden" name="instructor_ID" value="<?= $id ?>">
                <button type="submit" name="edit" class="admin-btn">Edit</button>
              </form>
              <form action="admin_delete_instructor.php" method="POST"
                    onsubmit="return confirm('Delete this instructor?');" style="display:inline;">
                <input type="hidden" name="instructor_ID" value="<?= $id ?>">
                <button type="submit" name="delete" class="admin-btn">Delete</button>
              </form>
            </div>
            <?php endif; ?>
          </div>
        </article>
      <?php endwhile; $conn->close(); ?>
    </div>
  </div>

  <!-- Individual Instructor View -->
  <div id="individual-view" class="individual-view">
    <div class="individual-instructor">
      <div class="individual-header">
        <div class="individual-img">
          <img id="individual-img" src="" alt="">
        </div>
        <div class="individual-info">
          <h2 id="individual-name"></h2>
          <div id="individual-bio" class="individual-bio"></div>
        </div>
      </div>
    </div>
  </div>

</div>

<?php include 'footer.php'; ?>

<script>
  const qInput = document.getElementById('instructor-search');
  const cards  = document.querySelectorAll('.instructor-card');
  const allView = document.getElementById('all-instructors-view');
  const individualView = document.getElementById('individual-view');
  const backBtn = document.getElementById('back-to-all');

  // Search functionality
  qInput.addEventListener('input', e => {
    const q = e.target.value.toLowerCase().trim();
    cards.forEach(c =>
      c.style.display = c.dataset.filter.includes(q) ? '' : 'none'
    );
  });

  // Show individual instructor from card data
  function showInstructorFromCard(linkElement) {
    const card = linkElement.closest('.instructor-card');
    const instructorId = card.dataset.id;
    const firstName = card.dataset.firstname;
    const lastName = card.dataset.lastname;
    const bio = card.dataset.bio;
    const image = card.dataset.image;

    // Update URL without page reload
    const newUrl = 'instructors.php?id=' + instructorId;
    window.history.pushState({instructorId: instructorId}, '', newUrl);

    // Update individual view content
    document.getElementById('individual-name').textContent = firstName + ' ' + lastName;
    document.getElementById('individual-bio').innerHTML = bio.replace(/\n/g, '<br>');
    document.getElementById('individual-img').src = image;
    document.getElementById('individual-img').alt = firstName + ' ' + lastName;

    // Switch views
    allView.style.display = 'none';
    individualView.style.display = 'block';
    backBtn.style.display = 'inline-block';

    // Scroll to top
    window.scrollTo(0, 0);
  }

  // Show all instructors
  function showAllInstructors() {
    // Update URL to remove instructor ID
    window.history.pushState({}, '', 'instructors.php');

    allView.style.display = 'block';
    individualView.style.display = 'none';
    backBtn.style.display = 'none';

    // Clear search
    qInput.value = '';
    cards.forEach(c => c.style.display = '');

    // Scroll to top
    window.scrollTo(0, 0);
  }

  // Handle browser back/forward buttons
  window.addEventListener('popstate', function(event) {
    const urlParams = new URLSearchParams(window.location.search);
    const instructorId = urlParams.get('id');
    
    if (instructorId) {
      // Find and show the instructor
      const card = document.querySelector(`[data-id="${instructorId}"]`);
      if (card) {
        const firstName = card.dataset.firstname;
        const lastName = card.dataset.lastname;
        const bio = card.dataset.bio;
        const image = card.dataset.image;

        document.getElementById('individual-name').textContent = firstName + ' ' + lastName;
        document.getElementById('individual-bio').innerHTML = bio.replace(/\n/g, '<br>');
        document.getElementById('individual-img').src = image;
        document.getElementById('individual-img').alt = firstName + ' ' + lastName;

        allView.style.display = 'none';
        individualView.style.display = 'block';
        backBtn.style.display = 'inline-block';
      }
    } else {
      showAllInstructors();
    }
  });

  // Check if page loaded with instructor ID
  document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const instructorId = urlParams.get('id');
    
    if (instructorId) {
      // Find and show the instructor
      const card = document.querySelector(`[data-id="${instructorId}"]`);
      if (card) {
        const firstName = card.dataset.firstname;
        const lastName = card.dataset.lastname;
        const bio = card.dataset.bio;
        const image = card.dataset.image;

        document.getElementById('individual-name').textContent = firstName + ' ' + lastName;
        document.getElementById('individual-bio').innerHTML = bio.replace(/\n/g, '<br>');
        document.getElementById('individual-img').src = image;
        document.getElementById('individual-img').alt = firstName + ' ' + lastName;

        allView.style.display = 'none';
        individualView.style.display = 'block';
        backBtn.style.display = 'inline-block';
      }
    }
  });
</script>
</body>
</html>