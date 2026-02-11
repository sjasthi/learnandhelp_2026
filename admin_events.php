<?php
// Print errors (match your pattern)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session + admin-gate (modeled on admin_usersList.php)
$status = session_status();
if ($status == PHP_SESSION_NONE) {
  session_start();
}
if (isset($_SESSION['role'])) {
  if ($_SESSION['role'] != 'admin') {
    http_response_code(403);
    die('Forbidden');
  }
} else {
  http_response_code(403);
  die('Forbidden');
}

// Helper functions for status display
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
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Administration — Events</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>

    <script>
      function deleteEvent(eventId){
        var confirmation = confirm("Are you sure you want to delete this event?");
        if (confirmation){
          window.location.href = 'admin_deleteevent.php?id=' + eventId;
        }
      }

      $(document).ready(function () {
        // Clone header row to add per-column search inputs (like users list)
        $('#EventsTable thead tr').clone(true).appendTo('#EventsTable thead');
        $('#EventsTable thead tr:eq(1) th').each(function () {
          var title = $(this).text();
          // Skip "Actions" column search box
          if (title.toLowerCase() === 'actions') {
            $(this).html('');
          } else {
            $(this).html('<input type="text" placeholder="Search ' + title + '" />');
          }
        });

        var table = $('#EventsTable').DataTable({
          orderCellsTop: true,
          fixedHeader: true,
          order: [[2, 'desc']], // Order by date descending by default
          initComplete: function () {
            this.api().columns().every(function () {
              var that = this;
              $('input', this.header()).on('keyup change clear', function () {
                if (that.search() !== this.value) {
                  that.search(this.value).draw();
                }
              });
            });
          }
        });

        $('a.toggle-vis').on('click', function (e) {
          e.preventDefault();
          var column = table.column($(this).attr('data-column'));
          column.visible(!column.visible());
        });
      });
    </script>

    <style>
      .thumb {
        width: 48px; height: 48px; object-fit: contain; border-radius: 6px; border: 1px solid #eee;
      }
      .btn {
        width: fit-content; height: 44px; border-radius: 5px; padding: 0 20px; cursor: pointer; margin: 2px;
      }
      .btn-primary {
        background-color: #99D930; color: white; border: solid 0px;
      }
      .btn-outline {
        background-color: transparent; color: black; border: solid 1px black;
      }
      .btn-danger {
        background-color: transparent; color: red; border: solid 1px red;
      }
      .toolbar { padding: 10px 0 30px 0; width: 90%; margin: auto; overflow: auto; }
      .toggle_columns { padding: 10px 0; width: 90%; margin: auto; overflow: auto; }
      table.dataTable thead input { width: 100%; box-sizing: border-box; }
      
      .event-status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        color: white;
        font-size: 0.8em;
        font-weight: 600;
        text-transform: uppercase;
      }
      
      .description-preview {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      .time-display {
        font-size: 0.9em;
        color: #666;
      }
    </style>
  </head>
  <body>
    <?php include 'show-navbar.php'; ?>
    <?php show_navbar(); ?>

    <header class="inverse">
      <div class="container">
        <h1><span class="accent-text">Events Administration</span></h1>
      </div>
    </header>

    <!-- Toggle columns (optional) -->
    <div class="toggle_columns">
      Toggle column:
      <a class="toggle-vis" data-column="0">Event ID</a> -
      <a class="toggle-vis" data-column="1">Title</a> -
      <a class="toggle-vis" data-column="2">Date</a> -
      <a class="toggle-vis" data-column="3">Time</a> -
      <a class="toggle-vis" data-column="4">Presenter</a> -
      <a class="toggle-vis" data-column="5">Status</a> -
      <a class="toggle-vis" data-column="6">Image</a> -
      <a class="toggle-vis" data-column="7">Description</a>
    </div>

    <!-- Create Event -->
    <div class="toolbar">
      <button type="button" class="btn btn-primary" onclick="location.href='admin_createevent.php'">Create Event</button>
    </div>

    <!-- Events Table -->
    <div class="toolbar">
      <table id="EventsTable" class="display compact" style="width:100%">
        <thead>
          <tr>
            <th>Event ID</th>
            <th>Title</th>
            <th>Date</th>
            <th>Time</th>
            <th>Presenter</th>
            <th>Status</th>
            <th>Image</th>
            <th>Description</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
          require 'db_configuration.php';
          $conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
          if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
          }

          // Pull all events (including all statuses for admin view)
          $sql = "SELECT id, title, date, start_time, end_time, presenter, description, event_image, status
                  FROM events
                  ORDER BY date DESC, start_time DESC";
          $result = $conn->query($sql);

          if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              // Build an image thumbnail if we can resolve a path; otherwise show filename
              $imageFile = htmlspecialchars($row['event_image'] ?? '');
              $possiblePaths = [
                "images/events/$imageFile",
                "images/$imageFile",
                $imageFile
              ];
              $imageSrc = "";
              foreach ($possiblePaths as $path) {
                if ($imageFile && file_exists($path)) { $imageSrc = $path; break; }
              }
              if (!$imageSrc) {
                $imageSrc = "images/events/default_event.png"; // fallback image if present
              }

              $eventId     = (int)$row['id'];
              $title       = htmlspecialchars($row['title']);
              $date        = htmlspecialchars($row['date']);
              $startTime   = htmlspecialchars($row['start_time']);
              $endTime     = htmlspecialchars($row['end_time']);
              $presenter   = htmlspecialchars($row['presenter']);
              $description = htmlspecialchars($row['description']);
              $status      = htmlspecialchars($row['status']);
              
              // Format date and time for display
              $formattedDate = date('M j, Y', strtotime($date));
              $timeRange = date('g:i A', strtotime($startTime)) . ' - ' . date('g:i A', strtotime($endTime));
              
              // Truncate description for table display
              $descPreview = strlen($description) > 100 ? substr($description, 0, 97) . '...' : $description;
              
              // Status badge
              $statusColor = getStatusColor($status);
              $statusBadge = "<span class=\"event-status-badge\" style=\"background-color: $statusColor\">" . getStatusText($status) . "</span>";

              echo "<tr>
                      <td>{$eventId}</td>
                      <td><strong>{$title}</strong></td>
                      <td>{$formattedDate}</td>
                      <td class=\"time-display\">{$timeRange}</td>
                      <td>{$presenter}</td>
                      <td>{$statusBadge}</td>
                      <td>" . ($imageSrc ? "<img class=\"thumb\" src=\"{$imageSrc}\" alt=\"{$title} image\"/>" : htmlspecialchars($imageFile)) . "</td>
                      <td><div class=\"description-preview\" title=\"{$description}\">{$descPreview}</div></td>
                      <td>
                        <button type=\"button\" class=\"btn btn-outline\" onclick=\"location.href='admin_updateevent.php?id={$eventId}'\">Update</button>
                        <button type=\"button\" class=\"btn btn-danger\" onclick=\"deleteEvent({$eventId})\">Delete</button>
                      </td>
                    </tr>";
            }
          } else {
            echo "<tr><td colspan='9'>No events found.</td></tr>";
          }
          $conn->close();
        ?>
        </tbody>
      </table>
    </div>
  </body>
</html>