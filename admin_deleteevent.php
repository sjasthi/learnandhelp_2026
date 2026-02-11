<?php
session_start();

// Include the database configuration file
require 'db_configuration.php';

$connection = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

// Check if the connection is established
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check admin authentication
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    http_response_code(403);
    die('Forbidden');
}

// Retrieve the Event ID from the query parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_events.php?msg=Invalid event ID&type=error");
    exit;
}

$event_id = (int)$_GET['id'];
$confirmation = isset($_GET['confirm']) ? $_GET['confirm'] : '';

// Retrieve event data for display
$selectQuery = "SELECT * FROM events WHERE id = ?";
$stmt = mysqli_prepare($connection, $selectQuery);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    header("Location: admin_events.php?msg=Event not found&type=error");
    exit;
}

$event = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Handle the deletion confirmation
if ($confirmation === 'yes') {
    // Delete the event image file if it exists and is not default
    if ($event['event_image'] && $event['event_image'] !== 'default_event.png') {
        $imagePath = 'images/events/' . $event['event_image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    // Delete the event from database
    $deleteQuery = "DELETE FROM events WHERE id = ?";
    $stmt = mysqli_prepare($connection, $deleteQuery);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    $deleteResult = mysqli_stmt_execute($stmt);
    
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
    
    if ($deleteResult) {
        header("Location: admin_events.php?msg=Event deleted successfully&type=success");
        exit;
    } else {
        header("Location: admin_events.php?msg=Error deleting event&type=error");
        exit;
    }
} elseif ($confirmation === 'no') {
    // User canceled, redirect back
    mysqli_close($connection);
    header("Location: admin_events.php");
    exit;
}

// Close database connection
mysqli_close($connection);

// Helper functions
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

function formatTimeCST($time) {
    return date('g:i A', strtotime($time)) . ' CST';
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
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <title>Delete Event - Confirmation</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            text-align: left !important;
            background: #f8f8f8;
        }

        main {
            padding: 20px;
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 18px rgba(0,0,0,.09);
        }

        .warning-header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 25px;
            border-radius: 10px 10px 0 0;
            margin: -20px -20px 25px -20px;
            text-align: center;
        }

        .warning-header h1 {
            margin: 0;
            font-size: 2.2em;
            font-weight: 900;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .warning-header .icon {
            font-size: 3em;
            margin-bottom: 10px;
            display: block;
        }

        .confirmation-message {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 1.1em;
            text-align: center;
        }

        .event-details {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }

        .event-details h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #99d930;
            padding-bottom: 8px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 12px;
            align-items: flex-start;
        }

        .detail-label {
            font-weight: bold;
            color: #555;
            min-width: 120px;
            margin-right: 15px;
        }

        .detail-value {
            color: #333;
            flex: 1;
        }

        .event-status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            color: white;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
        }

        .event-image-preview {
            text-align: center;
            margin-top: 15px;
        }

        .event-image-preview img {
            max-width: 200px;
            max-height: 150px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #f9f9f9;
            padding: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #eee;
        }

        .btn {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 140px;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #ff5252, #e74c3c);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #99d930;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .warning-list {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #721c24;
        }

        .warning-list ul {
            margin: 0;
            padding-left: 20px;
        }

        .warning-list li {
            margin-bottom: 5px;
        }

        @media (max-width: 600px) {
            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php 
    include 'show-navbar.php'; 
    show_navbar();
    ?>

    <main>
        <a href="admin_events.php" class="back-link">← Back to Events</a>
        
        <div class="warning-header">
            <span class="icon">⚠️</span>
            <h1>Delete Event Confirmation</h1>
        </div>

        <div class="confirmation-message">
            <strong>You are about to permanently delete this event.</strong><br>
            This action cannot be undone!
        </div>

        <div class="event-details">
            <h3>📅 Event Details</h3>
            
            <div class="detail-row">
                <span class="detail-label">Title:</span>
                <span class="detail-value"><strong><?php echo htmlspecialchars($event['title']); ?></strong></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value"><?php echo formatDate($event['date']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Time:</span>
                <span class="detail-value">
                    <?php echo formatTimeCST($event['start_time']); ?> - <?php echo formatTimeCST($event['end_time']); ?>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Presenter:</span>
                <span class="detail-value"><?php echo htmlspecialchars($event['presenter']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value">
                    <span class="event-status-badge" style="background-color: <?php echo getStatusColor($event['status']); ?>">
                        <?php echo getStatusText($event['status']); ?>
                    </span>
                </span>
            </div>
            
            <?php if ($event['description']): ?>
            <div class="detail-row">
                <span class="detail-label">Description:</span>
                <span class="detail-value"><?php echo nl2br(htmlspecialchars($event['description'])); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($event['event_image'] && $event['event_image'] !== 'default_event.png'): ?>
            <div class="event-image-preview">
                <span class="detail-label">Event Image:</span>
                <?php
                $imagePath = '';
                $possiblePaths = [
                    "images/events/" . $event['event_image'],
                    "images/" . $event['event_image'],
                    $event['event_image']
                ];
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) { 
                        $imagePath = $path; 
                        break; 
                    }
                }
                if ($imagePath): ?>
                    <br>
                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Event image" />
                    <div style="font-size: 0.9em; color: #666; margin-top: 5px;">
                        <?php echo htmlspecialchars($event['event_image']); ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="warning-list">
            <strong>⚠️ Warning: This will permanently:</strong>
            <ul>
                <li>Remove the event from the database</li>
                <li>Delete the associated image file (if not default)</li>
                <li>Remove all event data and cannot be recovered</li>
            </ul>
        </div>

        <div class="action-buttons">
            <a href="?id=<?php echo $event_id; ?>&confirm=yes" class="btn btn-danger" 
               onclick="return confirm('Are you absolutely sure you want to delete this event? This action cannot be undone!');">
                🗑️ Yes, Delete Event
            </a>
            
            <a href="admin_events.php" class="btn btn-secondary">
                ❌ Cancel
            </a>
        </div>
    </main>

    <script>
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Escape key = cancel
            if (e.key === 'Escape') {
                window.location.href = 'admin_events.php';
            }
            
            // Enter key = focus on delete button (but don't auto-click for safety)
            if (e.key === 'Enter') {
                document.querySelector('.btn-danger').focus();
            }
        });

        // Add a final confirmation dialog
        document.querySelector('.btn-danger').addEventListener('click', function(e) {
            const eventTitle = '<?php echo addslashes($event['title']); ?>';
            const confirmMessage = `Are you absolutely sure you want to permanently delete "${eventTitle}"?\n\nThis action cannot be undone!`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        });

        // Auto-focus on cancel button for safety
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.btn-secondary').focus();
        });
    </script>
</body>
</html>