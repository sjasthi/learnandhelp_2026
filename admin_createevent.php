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

// Initialize form variables with defaults
$title = '';
$date = '';
$start_time = '';
$end_time = '';
$presenter = '';
$description = '';
$status = 'proposed'; // Default status
$error_message = '';
$success_message = '';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $title = trim($_POST['title']);
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $presenter = trim($_POST['presenter']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    
    // Basic validation
    if (empty($title) || empty($date) || empty($start_time) || empty($end_time) || empty($presenter)) {
        $error_message = "Please fill in all required fields.";
    } elseif ($start_time >= $end_time) {
        $error_message = "End time must be after start time.";
    } else {
        // Handle file upload for event image
        $event_image_filename = 'default_event.png'; // Default image
        
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'images/events/';
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            
            $fileType = $_FILES['event_image']['type'];
            $fileSize = $_FILES['event_image']['size'];
            $fileName = $_FILES['event_image']['name'];
            
            if (in_array($fileType, $allowedTypes) && $fileSize <= $maxFileSize) {
                // Generate unique filename to prevent conflicts
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $uniqueFileName = 'event_' . time() . '_' . uniqid() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $uniqueFileName;
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                if (move_uploaded_file($_FILES['event_image']['tmp_name'], $uploadPath)) {
                    $event_image_filename = $uniqueFileName;
                } else {
                    $error_message = "Failed to upload image file.";
                }
            } else {
                $error_message = "Invalid file type or size. Please upload an image under 5MB.";
            }
        }
        
        // If no errors, proceed with insertion
        if (empty($error_message)) {
            // Use prepared statement for security
            $insertQuery = "INSERT INTO events (title, date, start_time, end_time, presenter, description, event_image, status)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($connection, $insertQuery);
            mysqli_stmt_bind_param($stmt, "ssssssss", 
                $title, $date, $start_time, $end_time, $presenter, $description, $event_image_filename, $status);
            
            $insertResult = mysqli_stmt_execute($stmt);

            // Check if the query was successful
            if ($insertResult) {
                // Redirect back to the main events admin page after successful creation
                header("Location: admin_events.php?msg=Event created successfully&type=success");
                exit;
            } else {
                // If there was an error, display the error message
                $error_message = "Error creating event: " . mysqli_error($connection);
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Close the database connection
mysqli_close($connection);

// Helper function for status colors
function getStatusColor($status) {
    switch($status) {
        case 'proposed': return '#ff9800';
        case 'scheduled': return '#4caf50';
        case 'completed': return '#757575';
        default: return '#99D930';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <title>Create Event</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            text-align: left !important;
            background: #f8f8f8;
        }

        header {
            background-color: #333;
            color: #fff;
            padding: 20px;
        }

        main {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 18px rgba(0,0,0,.09);
            margin-top: 20px;
            margin-bottom: 40px;
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }

        .form {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #fafafa;
        }

        .form-section h3 {
            margin-top: 0;
            color: #99d930;
            border-bottom: 2px solid #99d930;
            padding-bottom: 5px;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        input[type="text"], input[type="date"], input[type="time"], input[type="email"], 
        input[type="file"], select, textarea {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        select {
            padding: 10px;
        }

        .time-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .radio-group {
            display: flex;
            gap: 15px;
            margin-top: 5px;
            flex-wrap: wrap;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            margin-top: 0;
            font-weight: normal;
            padding: 8px 15px;
            border-radius: 20px;
            border: 2px solid #ddd;
            cursor: pointer;
            transition: all 0.3s;
        }

        .radio-group input[type="radio"] {
            width: auto;
            margin-right: 8px;
        }

        .radio-group input[type="radio"]:checked + span {
            font-weight: bold;
        }

        .radio-group label:has(input:checked) {
            border-color: var(--status-color, #99d930);
            background-color: var(--status-bg, rgba(153, 217, 48, 0.1));
        }

        .createBtn {
            padding: 12px 30px;
            margin-top: 20px;
            display: block;
            font-size: 16px;
            background-color: #99D930;
            color: #000;
            border: none;
            cursor: pointer;
            width: 100%;
            border-radius: 6px;
            font-weight: bold;
        }

        .createBtn:hover {
            background-color: #7da22b;
            color: #fff;
        }

        .info-text {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }

        .error-message {
            background-color: #f8d7da;
            color: #842029;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c2c7;
        }

        .success-message {
            background-color: #d1e7dd;
            color: #0f5132;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #badbcc;
        }

        .status-proposed { --status-color: #ff9800; --status-bg: rgba(255, 152, 0, 0.1); }
        .status-scheduled { --status-color: #4caf50; --status-bg: rgba(76, 175, 80, 0.1); }
        .status-completed { --status-color: #757575; --status-bg: rgba(117, 117, 117, 0.1); }

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
    </style>
</head>
<body>
    <?php 
    include 'show-navbar.php'; 
    show_navbar();
    ?>

    <main>
        <a href="admin_events.php" class="back-link">← Back to Events</a>
        
        <h1>➕ Create New Event</h1>
        
        <?php if ($error_message): ?>
            <div class="error-message">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message">
                <strong>Success:</strong> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <form class="form" method="POST" action="" enctype="multipart/form-data" autocomplete="on">
            
            <!-- Basic Information Section -->
            <div class="form-section">
                <h3>📋 Event Information</h3>
                
                <label for="title">Event Title *</label>
                <input type="text" name="title" id="title" required placeholder="Enter event title" value="<?php echo htmlspecialchars($title); ?>">
                
                <label for="presenter">Presenter *</label>
                <input type="text" name="presenter" id="presenter" required placeholder="Presenter name" value="<?php echo htmlspecialchars($presenter); ?>">
                
                <label for="description">Event Description</label>
                <textarea name="description" id="description" placeholder="Describe the event, topics covered, target audience, learning objectives..."><?php echo htmlspecialchars($description); ?></textarea>
                <div class="info-text">This description will be visible to users on the events page</div>
            </div>

            <!-- Schedule Section -->
            <div class="form-section">
                <h3>🗓️ Schedule</h3>
                
                <label for="date">Event Date *</label>
                <input type="date" name="date" id="date" required value="<?php echo htmlspecialchars($date); ?>">
                
                <div class="time-row">
                    <div>
                        <label for="start_time">Start Time *</label>
                        <input type="time" name="start_time" id="start_time" required value="<?php echo htmlspecialchars($start_time); ?>">
                    </div>
                    
                    <div>
                        <label for="end_time">End Time *</label>
                        <input type="time" name="end_time" id="end_time" required value="<?php echo htmlspecialchars($end_time); ?>">
                    </div>
                </div>
                <div class="info-text">Times are displayed in CST to users</div>
            </div>

            <!-- Status Section -->
            <div class="form-section">
                <h3>📊 Event Status</h3>
                
                <label>Initial Status *</label>
                <div class="radio-group">
                    <label for="status_proposed" class="status-proposed">
                        <input type="radio" name="status" id="status_proposed" value="proposed" <?php if ($status == 'proposed') { echo 'checked'; } ?>>
                        <span>Proposed</span>
                    </label>
                    
                    <label for="status_scheduled" class="status-scheduled">
                        <input type="radio" name="status" id="status_scheduled" value="scheduled" <?php if ($status == 'scheduled') { echo 'checked'; } ?>>
                        <span>Scheduled</span>
                    </label>
                    
                    <label for="status_completed" class="status-completed">
                        <input type="radio" name="status" id="status_completed" value="completed" <?php if ($status == 'completed') { echo 'checked'; } ?>>
                        <span>Completed</span>
                    </label>
                </div>
                <div class="info-text">
                    <strong>Proposed:</strong> Event idea under consideration<br>
                    <strong>Scheduled:</strong> Confirmed and visible to users<br>
                    <strong>Completed:</strong> Event has finished
                </div>
            </div>

            <!-- Image Section -->
            <div class="form-section">
                <h3>🖼️ Event Image</h3>
                
                <label for="event_image">Upload Event Image (Optional)</label>
                <input type="file" name="event_image" id="event_image" accept="image/*">
                <div class="info-text">
                    Accepted formats: JPEG, PNG, GIF, WebP | Maximum size: 5MB<br>
                    If no image is uploaded, a default image will be used
                </div>
                
                <!-- Preview area will be added by JavaScript -->
            </div>
            
            <button class="createBtn" type="submit">✅ Create Event</button>
        </form>
    </main>

    <script>
        // Set default date to today
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('date');
            if (!dateInput.value) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.value = today;
            }
            
            // Set default times if empty
            const startTimeInput = document.getElementById('start_time');
            const endTimeInput = document.getElementById('end_time');
            if (!startTimeInput.value) {
                startTimeInput.value = '10:00';
            }
            if (!endTimeInput.value) {
                endTimeInput.value = '11:00';
            }
        });

        // Add visual feedback for status selection
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Remove all status classes from radio group
                document.querySelectorAll('.radio-group label').forEach(label => {
                    label.classList.remove('status-proposed', 'status-scheduled', 'status-completed');
                });
                
                // Add appropriate class based on selection
                if (this.checked) {
                    this.parentElement.classList.add('status-' + this.value);
                }
            });
        });

        // Initialize the default status styling
        document.querySelector('input[name="status"]:checked').parentElement.classList.add('status-' + document.querySelector('input[name="status"]:checked').value);

        // Validate time range
        document.getElementById('start_time').addEventListener('change', validateTimeRange);
        document.getElementById('end_time').addEventListener('change', validateTimeRange);

        function validateTimeRange() {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if (startTime && endTime && startTime >= endTime) {
                alert('End time must be after start time');
                document.getElementById('end_time').focus();
            }
        }

        // File upload preview
        document.getElementById('event_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Create or update preview
                    let preview = document.querySelector('.upload-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'upload-preview';
                        preview.style.marginTop = '15px';
                        preview.style.textAlign = 'center';
                        preview.innerHTML = '<p><strong>Image Preview:</strong></p>';
                        document.getElementById('event_image').parentNode.appendChild(preview);
                    }
                    
                    let img = preview.querySelector('img');
                    if (!img) {
                        img = document.createElement('img');
                        img.alt = 'Event image preview';
                        img.style.maxWidth = '200px';
                        img.style.maxHeight = '150px';
                        img.style.objectFit = 'contain';
                        img.style.border = '1px solid #ddd';
                        img.style.borderRadius = '8px';
                        img.style.background = '#f9f9f9';
                        img.style.padding = '8px';
                        preview.appendChild(img);
                    }
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                // Remove preview if no file selected
                const preview = document.querySelector('.upload-preview');
                if (preview) {
                    preview.remove();
                }
            }
        });

        // Form validation before submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const presenter = document.getElementById('presenter').value.trim();
            const date = document.getElementById('date').value;
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if (!title || !presenter || !date || !startTime || !endTime) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return false;
            }
            
            if (startTime >= endTime) {
                e.preventDefault();
                alert('End time must be after start time');
                return false;
            }
        });
    </script>
</body>
</html>