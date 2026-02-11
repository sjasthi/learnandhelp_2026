<?php
/* ───── session + DB connection ─────────────────────────────────── */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/db_configuration.php';   // provides $db
session_write_close();

/* ───── check if viewing single event ───────────── */
$eventId = isset($_GET['event_id']) && ctype_digit($_GET['event_id']) ? (int)$_GET['event_id'] : null;
$singleEvent = null;

if ($eventId) {
    // Fetch single event
    $stmt = $db->prepare("SELECT id, title, date, start_time, end_time, presenter, description, event_image, status FROM events WHERE id = ?");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();
    $singleEvent = $result->fetch_assoc();
    $stmt->close();
    
    // If event not found, redirect to events list
    if (!$singleEvent) {
        header("Location: events.php");
        exit;
    }
}

/* ───── pagination variables for scheduled events (only for listing view) ───────────── */
if (!$eventId) {
    $perPage = 10;
    $page = (isset($_GET['page']) && ctype_digit($_GET['page']) && $_GET['page'] > 0)
            ? (int)$_GET['page'] : 1;

    // Count scheduled events only
    $totalRows  = $db->query("SELECT COUNT(*) AS cnt FROM events WHERE status = 'scheduled'")
                     ->fetch_assoc()['cnt'];
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    $page       = min($page, $totalPages);
    $offset     = ($page - 1) * $perPage;

    /* ───── fetch scheduled events (earliest first) ───────────── */
    $scheduledSql = "
      SELECT id, title, date, start_time, end_time, presenter, description, event_image, status
        FROM events
        WHERE status = 'scheduled'
    ORDER BY date ASC, start_time ASC
       LIMIT $perPage OFFSET $offset";
    $scheduledResult = $db->query($scheduledSql);

    /* ───── fetch completed events (latest first) ───────────── */
    $completedSql = "
      SELECT id, title, date, start_time, end_time, presenter, description, event_image, status
        FROM events
        WHERE status = 'completed'
    ORDER BY date DESC, start_time DESC";
    $completedResult = $db->query($completedSql);
}

/* ───── helper functions ───────────── */
function formatTimeCST($time) {
    return date('g:i A', strtotime($time)) . ' CST';
}

function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

function getStatusColor($status) {
    switch($status) {
        case 'proposed': return '#ff9800';
        case 'scheduled': return '#4caf50';
        case 'completed': return '#757575';
        default: return '#99D930';
    }
}

function getStatusText($status) {
    return ucfirst($status);
}

function renderEventCard($row, $isSingle = false) {
    $id = (int)$row['id'];
    $title = htmlspecialchars($row['title']);
    $date = htmlspecialchars($row['date']);
    $startTime = htmlspecialchars($row['start_time']);
    $endTime = htmlspecialchars($row['end_time']);
    $presenter = htmlspecialchars($row['presenter']);
    $description = $row['description']; // Don't escape HTML in description
    $imageFilename = htmlspecialchars($row['event_image']);
    $status = htmlspecialchars($row['status']);
    
    // Build image path
    $imagePath = $imageFilename ? "images/events/" . $imageFilename : "images/events/default_event.png";
    
    $cardClass = $isSingle ? "event-card single-event" : "event-card";
    ?>
    <div class="<?= $cardClass ?>">
      <div class="event-status" style="background-color: <?= getStatusColor($status) ?>">
        <?= getStatusText($status) ?>
      </div>
      
      <img class="event-image"
           src="<?= $imagePath ?>"
           alt="<?= $title ?>"
           loading="lazy"
           onerror="if(!this.dataset.fallback){this.dataset.fallback='y';this.src='images/events/default_event.png';}">
      
      <div class="event-info">
        <h3>
          <?php if (!$isSingle): ?>
            <a href="events.php?event_id=<?= $id ?>" class="event-link" title="View event details"><?= $title ?></a>
          <?php else: ?>
            <?= $title ?>
          <?php endif; ?>
        </h3>
        
        <div class="event-meta">
          <div class="event-date"><?= formatDate($date) ?></div>
          <div class="event-time">
            <?= formatTimeCST($startTime) ?> - <?= formatTimeCST($endTime) ?>
          </div>
        </div>
        
        <div class="event-presenter">
          Presented by <?= $presenter ?>
        </div>
        
        <div class="event-desc">
          <?= $description ?>
        </div>
      </div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $singleEvent ? htmlspecialchars($singleEvent['title']) . ' | ' : '' ?>Events | Learn and Help</title>

  <!-- shared assets -->
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

  <style>:root { --accent:#99D930; }
.accent-text { color: var(--accent); }
/* Intro banner (from instructors.php) */
.intro-banner { background:#1a1a1a; color:#fff; text-align:center; padding:24px 20px 20px; }
.intro-banner h1 { font-family:'Montserrat',sans-serif; font-size:3rem; font-weight:900; margin:0 0 0px; }
.intro-banner h1 .accent-text { color:var(--accent); }
.intro-banner p { max-width:820px; margin:0 auto; font-size:1.5rem; line-height:1.65; }

    body{margin:0;font-family:'Montserrat',sans-serif;background:#f8f8f8;color:#252525;}

    /* static banner (same height as blog banner) */
    .banner-wrapper{position:relative;width:100vw;left:50%;margin-left:-50vw;height:200px;background:#fff;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);}
    .banner-wrapper img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;}
    @media(max-width:700px){.banner-wrapper{height:207px;}}

    /* section headers */
    .section-header {
      font-family:'Montserrat',sans-serif;
      font-size:2.2em;
      font-weight:700;
      text-align:center;
      margin:50px 0 30px;
      color:#252525;
    }

    /* single column grid */
    .events-grid{
      display:grid;
      grid-template-columns:1fr;
      gap:30px;
      max-width:1200px;
      margin:30px auto 60px;
      padding:0 20px;
    }

    .event-card{
      background:#fff;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,.08);
      overflow:hidden;transition:transform .2s;display:flex;flex-direction:column;
      position:relative;
    }
    .event-card:hover{transform:translateY(-6px);}
    
    /* Single event styling */
    .single-event {
      max-width: 800px;
      margin: 30px auto;
    }
    
    .single-event .event-image {
      max-height: 400px;
    }
    
    .single-event .event-info h3 {
      font-size: 2em;
    }
    
    .single-event .event-desc {
      font-size: 1.1em;
      line-height: 1.8;
    }
    
    .event-status{
      position:absolute;
      top:15px;
      right:15px;
      padding:6px 12px;
      border-radius:20px;
      color:#fff;
      font-size:0.8em;
      font-weight:700;
      text-transform:uppercase;
      z-index:2;
    }
    
    .event-image{
      display:block;
      width:100%;
      max-height:256px;
      height:auto;
      object-fit:contain;
      background:#fff5e6;
      padding:8px;
      border-bottom:1px solid #f0f0f0;
    }
    
    .event-info{padding:22px;text-align:center;flex-grow:1;display:flex;flex-direction:column;}
    .event-info h3{margin:0 0 8px;font-size:1.35em;font-weight:900;color:#252525;}
    
    .event-link {
      color: #252525;
      text-decoration: none;
      transition: color 0.3s;
      cursor: pointer;
      display: block;
    }
    
    .event-link:hover {
      color: #99D930;
      text-decoration: underline;
    }
    
    .event-meta{
      margin:8px 0 12px;
      font-size:0.9em;
      color:#666;
    }
    
    .event-date{
      font-weight:700;
      color:#252525;
      display:block;
      margin-bottom:4px;
    }
    
    .event-time{
      color:#99D930;
      font-weight:600;
    }
    
    .event-presenter{
      font-style:italic;
      color:#777;
      margin:8px 0;
    }
    
    .event-desc{
      font-size:0.95em;
      color:#444;
      flex-grow:1;
      text-align:left;
      line-height:1.6;
      white-space:pre-wrap;  /* Preserves whitespace and line breaks */
      word-wrap:break-word;
    }

    /* Back button */
    .back-btn {
      display: inline-block;
      background: #99D930;
      color: #fff;
      padding: 10px 20px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 700;
      margin: 20px auto;
      transition: background-color 0.3s;
    }
    
    .back-btn:hover {
      background: #7ab82a;
      color: #fff;
    }
    
    .back-btn-container {
      text-align: center;
      margin: 20px 0;
    }

    /* past events section */
    .past-events-toggle {
      text-align:center;
      margin:40px 0;
    }
    
    .toggle-btn {
      background:#99D930;
      color:#fff;
      border:none;
      padding:12px 30px;
      border-radius:25px;
      font-size:1.1em;
      font-weight:700;
      cursor:pointer;
      transition:background-color 0.3s;
    }
    
    .toggle-btn:hover {
      background:#7ab82a;
    }
    
    .past-events-section {
      display:none;
    }
    
    .past-events-section.show {
      display:block;
    }
    
    .no-events-message {
      width:100%;
      text-align:center;
      font-size:1.2em;
      color:#777;
      grid-column:1 / -1;
    }

    /* pager links */
    .pager{text-align:center;font-weight:bold;margin:40px 0;}
    .pager a{margin:0 18px;color:#252525;text-decoration:none;}
    .pager a:hover{color:#99d930;}
  </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<?php if ($singleEvent): ?>
    <!-- Single Event View -->
    <section class="intro-banner">
      <h1><span class="accent-text">Event Details</span></h1>
      <p>Learn more about this event.</p>
    </section>

    <div class="back-btn-container">
      <a href="events.php" class="back-btn">← Back to All Events</a>
    </div>

    <div class="events-grid">
      <?php renderEventCard($singleEvent, true); ?>
    </div>

<?php else: ?>
    <!-- Events Listing View -->
    <section class="intro-banner">
      <h1><span class="accent-text">Events</span></h1>
      <p>Discover upcoming workshops, seminars, and learning opportunities.</p>
    </section>

    <!-- Scheduled Events Section -->
    <h2 class="section-header">Upcoming Events</h2>

    <div class="events-grid">
    <?php
    if ($scheduledResult && $scheduledResult->num_rows){
      while($row = $scheduledResult->fetch_assoc()){
        renderEventCard($row);
      }
    }else{
      echo '<div class="no-events-message">No upcoming events scheduled.</div>';
    }
    ?>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="pager">
        <?php if ($page > 1): ?>
          <a href="?page=<?= $page-1 ?>">&laquo; Previous</a>
        <?php endif; ?>
        Page <?= $page ?> of <?= $totalPages ?>
        <?php if ($page < $totalPages): ?>
          <a href="?page=<?= $page+1 ?>">Next &raquo;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Past Events Toggle -->
    <div class="past-events-toggle">
      <button class="toggle-btn" onclick="togglePastEvents()">View Past Events</button>
    </div>

    <!-- Past Events Section -->
    <div class="past-events-section" id="pastEvents">
      <h2 class="section-header">Past Events</h2>
      
      <div class="events-grid">
      <?php
      if ($completedResult && $completedResult->num_rows){
        while($row = $completedResult->fetch_assoc()){
          renderEventCard($row);
        }
      }else{
        echo '<div class="no-events-message">No past events to display.</div>';
      }
      ?>
      </div>
    </div>

    <script>
    function togglePastEvents() {
      const pastEventsSection = document.getElementById('pastEvents');
      const toggleBtn = document.querySelector('.toggle-btn');
      
      if (pastEventsSection.classList.contains('show')) {
        pastEventsSection.classList.remove('show');
        toggleBtn.textContent = 'View Past Events';
      } else {
        pastEventsSection.classList.add('show');
        toggleBtn.textContent = 'Hide Past Events';
        // Scroll to past events section
        pastEventsSection.scrollIntoView({ behavior: 'smooth' });
      }
    }
    </script>

<?php endif; ?>

<?php 
if (isset($scheduledResult) && $scheduledResult) mysqli_free_result($scheduledResult);
if (isset($completedResult) && $completedResult) mysqli_free_result($completedResult);
$db->close(); 
include 'footer.php'; 
?>
</body>
</html>