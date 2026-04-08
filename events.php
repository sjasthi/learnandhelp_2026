<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_configuration.php'; // provides $db (mysqli)

// ── Single event view ─────────────────────────────────────────
$eventId     = (isset($_GET['event_id']) && ctype_digit($_GET['event_id'])) ? (int)$_GET['event_id'] : null;
$singleEvent = null;

if ($eventId) {
    $stmt = $db->prepare("SELECT id, title, date, start_time, end_time, presenter, description, event_image, status FROM events WHERE id = ?");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $singleEvent = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$singleEvent) {
        header("Location: events.php");
        exit;
    }
}

// ── Listing view: pagination + fetch ─────────────────────────
$scheduledEvents = [];
$completedEvents = [];
$totalPages      = 1;
$page            = 1;

if (!$eventId) {
    $perPage = 10;
    $page    = (isset($_GET['page']) && ctype_digit($_GET['page']) && $_GET['page'] > 0)
               ? (int)$_GET['page'] : 1;

    $totalRows  = (int)$db->query("SELECT COUNT(*) AS cnt FROM events WHERE status = 'scheduled'")
                           ->fetch_assoc()['cnt'];
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    $page       = min($page, $totalPages);
    $offset     = ($page - 1) * $perPage;

    // Scheduled — prepared statement
    $stmt = $db->prepare("SELECT id, title, date, start_time, end_time, presenter, description, event_image, status FROM events WHERE status = 'scheduled' ORDER BY date ASC, start_time ASC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $scheduledEvents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Completed — no pagination needed
    $res = $db->query("SELECT id, title, date, start_time, end_time, presenter, description, event_image, status FROM events WHERE status = 'completed' ORDER BY date DESC, start_time DESC");
    $completedEvents = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// ── Helpers ───────────────────────────────────────────────────
function formatTimeCST(string $time): string {
    return date('g:i A', strtotime($time)) . ' CST';
}
function formatDate(string $date): string {
    return date('F j, Y', strtotime($date));
}
function getStatusColor(string $status): string {
    switch ($status) {
        case 'proposed':  return '#f57f17';
        case 'scheduled': return '#2e7d32';
        case 'completed': return '#757575';
        default:          return '#274606';
    }
}
function getStatusBg(string $status): string {
    switch ($status) {
        case 'proposed':  return '#fff3e0';
        case 'scheduled': return '#edfae5';
        case 'completed': return '#f3f3f3';
        default:          return '#edfae5';
    }
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title><?= $singleEvent ? htmlspecialchars($singleEvent['title']) . ' – ' : '' ?>Events – Learn and Help</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

    <style>
        :root { --accent: #99D930; }

        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background: #f8f8f8;
            color: #252525;
        }

        /* ── Banner ── */
        .banner-wrapper {
            width: 100vw;
            left: 50%;
            margin-left: -50vw;
            height: 220px;
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
            font-family: 'Roboto', sans-serif;
            font-size: 3em;
            font-weight: 900;
            color: #99d930;
            text-shadow: 0 2px 16px rgba(0,0,0,0.44);
            letter-spacing: 1px;
            z-index: 2;
            white-space: nowrap;
        }
        .banner-subtitle {
            position: absolute;
            bottom: 18px;
            left: 50%;
            transform: translateX(-50%);
            margin: 0;
            font-size: 1.05em;
            color: #fff;
            text-shadow: 0 1px 6px rgba(0,0,0,.5);
            z-index: 2;
            white-space: nowrap;
        }

        /* ── Page wrapper ── */
        .page-wrap {
            max-width: 1100px;
            margin: 40px auto 60px auto;
            padding: 0 20px;
        }

        /* ── Back link ── */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 22px;
            font-weight: 700;
            color: #274606;
            text-decoration: none;
            font-size: .97em;
        }
        .back-link:hover { color: #99d930; }

        /* ── Section heading ── */
        .section-heading {
            font-size: 1.7em;
            font-weight: 900;
            text-align: center;
            margin: 36px 0 24px 0;
            color: #252525;
        }
        .section-heading::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: #99d930;
            border-radius: 2px;
            margin: 12px auto 0;
        }

        /* ── Events grid ── */
        .events-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        /* ── Event card ── */
        .event-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
            display: flex;
            flex-direction: row;
            position: relative;
        }
        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 36px rgba(153,217,48,0.18);
        }

        /* Single event view */
        .event-card.single-event {
            flex-direction: column;
            max-width: 820px;
            margin: 0 auto;
        }
        .event-card.single-event .event-image { max-height: 380px; }
        .event-card.single-event .event-info h3 { font-size: 1.9em; text-align: left; }
        .event-card.single-event .event-desc { font-size: 1.05em; line-height: 1.8; }

        /* ── Status badge ── */
        .event-status {
            position: absolute;
            top: 14px;
            right: 14px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: .78em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            z-index: 2;
        }

        /* ── Event image ── */
        .event-image {
            display: block;
            width: 260px;
            flex-shrink: 0;
            height: 100%;
            min-height: 200px;
            object-fit: cover;
            background: #f8fbe9;
            border-right: 1.5px solid #e8f5c8;
        }
        .event-card.single-event .event-image {
            width: 100%;
            height: auto;
            max-height: 380px;
            object-fit: contain;
            border-right: none;
            border-bottom: 1.5px solid #e8f5c8;
            padding: 8px;
            box-sizing: border-box;
        }

        /* ── Event info ── */
        .event-info {
            padding: 22px 24px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .event-info h3 {
            margin: 0 0 10px 0;
            font-size: 1.3em;
            font-weight: 900;
            color: #252525;
        }
        .event-link {
            color: #252525;
            text-decoration: none;
            transition: color .2s;
        }
        .event-link:hover { color: #99d930; }

        .event-meta { margin: 8px 0 12px 0; font-size: .9em; color: #666; }
        .event-date {
            font-weight: 700;
            color: #274606;
            display: block;
            margin-bottom: 4px;
        }
        .event-time { color: #99d930; font-weight: 700; }
        .event-presenter {
            font-style: italic;
            color: #888;
            margin: 6px 0 10px 0;
            font-size: .93em;
        }
        .event-desc {
            font-size: .95em;
            color: #444;
            flex-grow: 1;
            line-height: 1.65;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            color: #888;
            padding: 40px 0;
            font-size: 1.05em;
        }

        /* ── Toggle button ── */
        .toggle-wrap { text-align: center; margin: 32px 0; }
        .btn-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 28px;
            border-radius: 8px;
            background: #99d930;
            color: #274606;
            border: none;
            font-size: 1em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            cursor: pointer;
            transition: background .18s, transform .12s;
            box-shadow: 0 2px 8px rgba(153,217,48,.25);
        }
        .btn-toggle:hover { background: #85c220; transform: translateY(-1px); }

        .past-events-section { display: none; }
        .past-events-section.show { display: block; }

        /* ── Pager ── */
        .pager {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 24px;
            font-weight: 700;
            margin: 8px 0 32px 0;
        }
        .pager a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 20px;
            border-radius: 8px;
            background: #99d930;
            color: #274606;
            text-decoration: none;
            font-size: .95em;
            transition: background .18s;
            box-shadow: 0 2px 8px rgba(153,217,48,.25);
        }
        .pager a:hover { background: #85c220; }
        .pager .page-info { color: #555; font-size: .95em; }

        @media (max-width: 700px) {
            .event-card { flex-direction: column; }
            .event-image { width: 100%; min-height: 180px; border-right: none; border-bottom: 1.5px solid #e8f5c8; }
            .banner-title { font-size: 2em; }
        }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<?php if ($singleEvent):
    $statusBg    = getStatusBg($singleEvent['status']);
    $statusColor = getStatusColor($singleEvent['status']);
    $imgPath     = !empty($singleEvent['event_image'])
                   ? 'images/events/' . htmlspecialchars($singleEvent['event_image'])
                   : 'images/events/default_event.png';
?>

<!-- ── Single Event View ── -->
<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Event banner">
    <h1 class="banner-title">Event Details</h1>
    <p class="banner-subtitle">Learn more about this event</p>
</div>

<div class="page-wrap">
    <a href="events.php" class="back-link">&#8592; Back to All Events</a>

    <div class="events-grid">
        <div class="event-card single-event">
            <span class="event-status"
                  style="background:<?= $statusBg ?>;color:<?= $statusColor ?>;">
                <?= ucfirst(htmlspecialchars($singleEvent['status'])) ?>
            </span>
            <img class="event-image"
                 src="<?= $imgPath ?>"
                 alt="<?= htmlspecialchars($singleEvent['title']) ?>"
                 loading="lazy"
                 onerror="if(!this.dataset.fallback){this.dataset.fallback='y';this.src='images/events/default_event.png';}">
            <div class="event-info">
                <h3><?= htmlspecialchars($singleEvent['title']) ?></h3>
                <div class="event-meta">
                    <span class="event-date"><?= formatDate($singleEvent['date']) ?></span>
                    <span class="event-time">
                        <?= formatTimeCST($singleEvent['start_time']) ?> – <?= formatTimeCST($singleEvent['end_time']) ?>
                    </span>
                </div>
                <div class="event-presenter">Presented by <?= htmlspecialchars($singleEvent['presenter']) ?></div>
                <div class="event-desc"><?= htmlspecialchars($singleEvent['description'] ?? '') ?></div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>

<!-- ── Events Listing View ── -->
<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Events banner">
    <h1 class="banner-title">Events</h1>
    <p class="banner-subtitle">Discover upcoming workshops, seminars, and learning opportunities</p>
</div>

<div class="page-wrap">

    <!-- Scheduled events -->
    <h2 class="section-heading">Upcoming Events</h2>

    <div class="events-grid">
        <?php if (!empty($scheduledEvents)): ?>
            <?php foreach ($scheduledEvents as $row):
                $statusBg    = getStatusBg($row['status']);
                $statusColor = getStatusColor($row['status']);
                $imgPath     = !empty($row['event_image'])
                               ? 'images/events/' . htmlspecialchars($row['event_image'])
                               : 'images/events/default_event.png';
            ?>
            <div class="event-card">
                <span class="event-status"
                      style="background:<?= $statusBg ?>;color:<?= $statusColor ?>;">
                    <?= ucfirst(htmlspecialchars($row['status'])) ?>
                </span>
                <img class="event-image"
                     src="<?= $imgPath ?>"
                     alt="<?= htmlspecialchars($row['title']) ?>"
                     loading="lazy"
                     onerror="if(!this.dataset.fallback){this.dataset.fallback='y';this.src='images/events/default_event.png';}">
                <div class="event-info">
                    <h3>
                        <a href="events.php?event_id=<?= intval($row['id']) ?>"
                           class="event-link"><?= htmlspecialchars($row['title']) ?></a>
                    </h3>
                    <div class="event-meta">
                        <span class="event-date"><?= formatDate($row['date']) ?></span>
                        <span class="event-time">
                            <?= formatTimeCST($row['start_time']) ?> – <?= formatTimeCST($row['end_time']) ?>
                        </span>
                    </div>
                    <div class="event-presenter">Presented by <?= htmlspecialchars($row['presenter']) ?></div>
                    <div class="event-desc"><?= htmlspecialchars($row['description'] ?? '') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">No upcoming events scheduled.</div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pager">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">&laquo; Previous</a>
            <?php endif; ?>
            <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Past events toggle -->
    <div class="toggle-wrap">
        <button class="btn-toggle" onclick="togglePastEvents()" id="toggle-btn">
            &#9660; View Past Events
        </button>
    </div>

    <div class="past-events-section" id="pastEvents">
        <h2 class="section-heading">Past Events</h2>
        <div class="events-grid">
            <?php if (!empty($completedEvents)): ?>
                <?php foreach ($completedEvents as $row):
                    $statusBg    = getStatusBg($row['status']);
                    $statusColor = getStatusColor($row['status']);
                    $imgPath     = !empty($row['event_image'])
                                   ? 'images/events/' . htmlspecialchars($row['event_image'])
                                   : 'images/events/default_event.png';
                ?>
                <div class="event-card">
                    <span class="event-status"
                          style="background:<?= $statusBg ?>;color:<?= $statusColor ?>;">
                        <?= ucfirst(htmlspecialchars($row['status'])) ?>
                    </span>
                    <img class="event-image"
                         src="<?= $imgPath ?>"
                         alt="<?= htmlspecialchars($row['title']) ?>"
                         loading="lazy"
                         onerror="if(!this.dataset.fallback){this.dataset.fallback='y';this.src='images/events/default_event.png';}">
                    <div class="event-info">
                        <h3>
                            <a href="events.php?event_id=<?= intval($row['id']) ?>"
                               class="event-link"><?= htmlspecialchars($row['title']) ?></a>
                        </h3>
                        <div class="event-meta">
                            <span class="event-date"><?= formatDate($row['date']) ?></span>
                            <span class="event-time">
                                <?= formatTimeCST($row['start_time']) ?> – <?= formatTimeCST($row['end_time']) ?>
                            </span>
                        </div>
                        <div class="event-presenter">Presented by <?= htmlspecialchars($row['presenter']) ?></div>
                        <div class="event-desc"><?= htmlspecialchars($row['description'] ?? '') ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">No past events to display.</div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /page-wrap -->

<script>
    function togglePastEvents() {
        var section = document.getElementById('pastEvents');
        var btn     = document.getElementById('toggle-btn');
        var showing = section.classList.toggle('show');
        btn.textContent = showing ? '▲ Hide Past Events' : '▼ View Past Events';
        if (showing) section.scrollIntoView({ behavior: 'smooth' });
    }
</script>

<?php endif; ?>

<?php include 'footer.php'; ?>
</body>
</html>