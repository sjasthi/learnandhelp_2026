<?php
/* ───── session + DB connection ─────────────────────────────────── */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/db_configuration.php';   // provides $db

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel'])) {
        header('Location: my_account.php');
        exit();
    }
    
    if (isset($_POST['submit'])) {
        $school_name = trim($_POST['school_name'] ?? '');
        $contact_name = trim($_POST['contact_name'] ?? '');
        $contact_mobile = trim($_POST['contact_mobile'] ?? '');
        $commitment_statement = trim($_POST['commitment_statement'] ?? '');
        
        // Basic validation
        if (empty($school_name)) {
            $message = 'School name is required.';
            $messageType = 'error';
        } elseif (empty($contact_name)) {
            $message = 'Contact name is required.';
            $messageType = 'error';
        } else {
            // Insert into schools table with status 'Proposed'
            $stmt = $db->prepare("INSERT INTO schools (name, contact_name, contact_phone, commitment_statement, status) VALUES (?, ?, ?, ?, 'Proposed')");
            $stmt->bind_param("ssss", $school_name, $contact_name, $contact_mobile, $commitment_statement);
            
            if ($stmt->execute()) {
                // Success - redirect to index.php with flash message
                $_SESSION['flash_message'] = 'Success: Thank you for suggesting a School. You may nominate another school!';
                $_SESSION['flash_type'] = 'success';
                $stmt->close();
                $db->close();
                header('Location: index.php');
                exit();
            } else {
                if ($db->errno == 1062) { // MySQL duplicate entry error
                    $message = 'This school has already been suggested. Thank you for your suggestion!';
                } else {
                    $message = 'Error submitting suggestion. Please try again.';
                }
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

// Don't close the session here - remove session_write_close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Suggest a School | Learn and Help</title>

  <!-- shared assets -->
  <link rel="icon" href="images/icon_logo.png" type="image/icon type">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&family=Montserrat:wght@300;700;900&display=swap" rel="stylesheet">
  <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

  <style>
    :root { --accent:#99D930; }
    .accent-text { color: var(--accent); }

    /* Intro banner */
    .intro-banner { background:#1a1a1a; color:#fff; text-align:center; padding:24px 20px 20px; }
    .intro-banner h1 { font-family:'Montserrat',sans-serif; font-size:3rem; font-weight:900; margin:0 0 0px; }
    .intro-banner h1 .accent-text { color:var(--accent); }
    .intro-banner p { max-width:820px; margin:0 auto; font-size:1.5rem; line-height:1.65; }

    body{margin:0;font-family:'Montserrat',sans-serif;background:#f8f8f8;color:#252525;}

    /* Form container */
    .form-container {
        max-width: 800px;
        margin: 60px auto;
        padding: 0 20px;
    }

    .form-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 24px rgba(0,0,0,.08);
        padding: 40px;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-group label {
        display: block;
        font-weight: 700;
        margin-bottom: 8px;
        color: #252525;
        font-size: 1.1em;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1em;
        font-family: 'Montserrat', sans-serif;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--accent);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 120px;
    }

    .form-group small {
        display: block;
        color: #666;
        margin-top: 4px;
        font-size: 0.9em;
    }

    .button-group {
        display: flex;
        gap: 16px;
        justify-content: center;
        margin-top: 40px;
    }

    .btn {
        padding: 12px 32px;
        border: none;
        border-radius: 8px;
        font-size: 1.1em;
        font-weight: 700;
        font-family: 'Montserrat', sans-serif;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .btn-primary {
        background: var(--accent);
        color: #252525;
    }

    .btn-primary:hover {
        background: #8cc428;
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: #f0f0f0;
        color: #252525;
    }

    .btn-secondary:hover {
        background: #e0e0e0;
        transform: translateY(-2px);
    }

    .message {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        text-align: center;
        font-weight: 600;
    }

    .message.error {
        background: #ffe6e6;
        color: #d32f2f;
        border: 1px solid #ffcdd2;
    }

    .message.success {
        background: #e8f5e8;
        color: #2e7d32;
        border: 1px solid #c8e6c9;
    }

    @media(max-width: 700px) {
        .form-card {
            padding: 24px;
        }
        
        .button-group {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
        }
    }
  </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<section class="intro-banner">
  <h1>Suggest a <span class="accent-text">School</span></h1>
  <p>Help us connect with schools that could benefit from our library programs.</p>
</section>

<div class="form-container">
    <div class="form-card">
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="school_name">School Name *</label>
                <input type="text" id="school_name" name="school_name" required 
                       value="<?= htmlspecialchars($_POST['school_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="contact_name">Name of Contact at School (Teacher or Head Master) *</label>
                <input type="text" id="contact_name" name="contact_name" required 
                       value="<?= htmlspecialchars($_POST['contact_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="contact_mobile">Mobile Number of Contact</label>
                <input type="tel" id="contact_mobile" name="contact_mobile" 
                       placeholder="e.g., +1 (555) 123-4567"
                       value="<?= htmlspecialchars($_POST['contact_mobile'] ?? '') ?>">
                <small>Contact phone number for the school representative</small>
            </div>

            <div class="form-group">
                <label for="commitment_statement">Statement of Commitment</label>
                <textarea id="commitment_statement" name="commitment_statement" 
                          placeholder="Indicate how you want to administer the library and your commitment to the program..."><?= htmlspecialchars($_POST['commitment_statement'] ?? '') ?></textarea>
                <small>Describe your plans for managing and maintaining the library</small>
            </div>

            <div class="button-group">
                <button type="submit" name="submit" class="btn btn-primary">Submit Suggestion</button>
                <button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php $db->close(); include 'footer.php'; ?>
</body>
</html>