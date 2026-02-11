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
        $suggester_id = $_SESSION['user_id'];
        $org_name = trim($_POST['org_name'] ?? '');
        $cause_category = trim($_POST['cause_category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $website_url = trim($_POST['website_url'] ?? '');
        $org_email = trim($_POST['org_email'] ?? '');
        
        // Basic validation
        if (empty($org_name)) {
            $message = 'Organization name is required.';
            $messageType = 'error';
        } else {
            // Insert into database
            $stmt = $db->prepare("INSERT INTO recommended_orgs (suggester_id, org_name, cause_category, description, website_url, org_email) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $suggester_id, $org_name, $cause_category, $description, $website_url, $org_email);
            
            if ($stmt->execute()) {
                // Success - redirect to index.php with flash message
                $_SESSION['flash_message'] = 'Success: Thank you for recommending a Non-Profit';
                $_SESSION['flash_type'] = 'success';
                $stmt->close();
                $db->close();
                header('Location: index.php');
                exit();
            } else {
                if ($db->errno == 1062) { // MySQL duplicate entry error
                    $message = 'This organization has already been recommended. Thank you for your suggestion!';
                } else {
                    $message = 'Error submitting recommendation. Please try again.';
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
  <title>Recommend a Non-Profit | Learn and Help</title>

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
  <h1>Recommend a <span class="accent-text">Non-Profit</span></h1>
  <p>Help us discover amazing organizations making a difference in our community.</p>
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
                <label for="org_name">Organization Name *</label>
                <input type="text" id="org_name" name="org_name" required 
                       value="<?= htmlspecialchars($_POST['org_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="cause_category">Cause Category</label>
                <select id="cause_category" name="cause_category">
                    <option value="">Select a category (optional)</option>
                    <option value="Education" <?= ($_POST['cause_category'] ?? '') === 'Education' ? 'selected' : '' ?>>Education</option>
                    <option value="Health" <?= ($_POST['cause_category'] ?? '') === 'Health' ? 'selected' : '' ?>>Health</option>
                    <option value="Environment" <?= ($_POST['cause_category'] ?? '') === 'Environment' ? 'selected' : '' ?>>Environment</option>
                    <option value="Hunger Relief" <?= ($_POST['cause_category'] ?? '') === 'Hunger Relief' ? 'selected' : '' ?>>Hunger Relief</option>
                    <option value="Housing" <?= ($_POST['cause_category'] ?? '') === 'Housing' ? 'selected' : '' ?>>Housing</option>
                    <option value="Youth Services" <?= ($_POST['cause_category'] ?? '') === 'Youth Services' ? 'selected' : '' ?>>Youth Services</option>
                    <option value="Senior Services" <?= ($_POST['cause_category'] ?? '') === 'Senior Services' ? 'selected' : '' ?>>Senior Services</option>
                    <option value="Animal Welfare" <?= ($_POST['cause_category'] ?? '') === 'Animal Welfare' ? 'selected' : '' ?>>Animal Welfare</option>
                    <option value="Arts & Culture" <?= ($_POST['cause_category'] ?? '') === 'Arts & Culture' ? 'selected' : '' ?>>Arts & Culture</option>
                    <option value="Community Development" <?= ($_POST['cause_category'] ?? '') === 'Community Development' ? 'selected' : '' ?>>Community Development</option>
                    <option value="Other" <?= ($_POST['cause_category'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" 
                          placeholder="Tell us about this organization and why you recommend them..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="website_url">Website URL</label>
                <input type="url" id="website_url" name="website_url" 
                       placeholder="https://example.org"
                       value="<?= htmlspecialchars($_POST['website_url'] ?? '') ?>">
                <small>Include the full URL (starting with http:// or https://)</small>
            </div>

            <div class="form-group">
                <label for="org_email">Organization Email</label>
                <input type="email" id="org_email" name="org_email" 
                       placeholder="contact@organization.org"
                       value="<?= htmlspecialchars($_POST['org_email'] ?? '') ?>">
                <small>Contact email for the organization (if known)</small>
            </div>

            <div class="button-group">
                <button type="submit" name="submit" class="btn btn-primary">Submit Recommendation</button>
                <button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php $db->close(); include 'footer.php'; ?>
</body>
</html>