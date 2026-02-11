<?php
// Print errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

// Block unauthorized users from accessing the page
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] != 'admin') {
        http_response_code(403);
        die('Forbidden');
    }
} else {
    http_response_code(403);
    die('Forbidden');
}

require 'db_configuration.php';

// Get organization ID
$orgId = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$orgId) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid organization ID.'];
    header('Location: admin_non_profits.php');
    exit();
}

// Create connection
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel'])) {
        header('Location: admin_non_profits.php');
        exit();
    }
    
    if (isset($_POST['update'])) {
        $org_name = trim($_POST['org_name'] ?? '');
        $cause_category = trim($_POST['cause_category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $website_url = trim($_POST['website_url'] ?? '');
        $org_email = trim($_POST['org_email'] ?? '');
        $org_status = $_POST['status'] ?? 'pending';
        $address = trim($_POST['address'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        // Basic validation
        if (empty($org_name)) {
            $message = 'Organization name is required.';
            $messageType = 'error';
        } else {
            // Update organization
            $stmt = $conn->prepare("UPDATE recommended_orgs SET org_name = ?, cause_category = ?, description = ?, website_url = ?, org_email = ?, status = ?, address = ?, notes = ?, updated_at = CURDATE() WHERE org_id = ?");
            $stmt->bind_param("ssssssssi", $org_name, $cause_category, $description, $website_url, $org_email, $org_status, $address, $notes, $orgId);
            
            if ($stmt->execute()) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Organization updated successfully.'];
                header('Location: admin_non_profits.php');
                exit();
            } else {
                if ($conn->errno == 1062) { // Duplicate entry error
                    $message = 'This website URL already exists for another organization.';
                } else {
                    $message = 'Error updating organization. Please try again.';
                }
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

// Fetch organization data
$stmt = $conn->prepare("SELECT * FROM recommended_orgs WHERE org_id = ?");
$stmt->bind_param("i", $orgId);
$stmt->execute();
$result = $stmt->get_result();
$org = $result->fetch_assoc();
$stmt->close();

if (!$org) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Organization not found.'];
    header('Location: admin_non_profits.php');
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Update Non-Profit | Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root { --accent:#99D930; }
        .accent-text { color: var(--accent); }

        body{margin:0;font-family:'Montserrat',sans-serif;background:#f8f8f8;color:#252525;}

        /* Form container */
        .form-container {
            max-width: 900px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .form-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 40px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
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

        .org-info {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            border-left: 4px solid var(--accent);
        }

        .org-info h3 {
            margin: 0 0 8px 0;
            color: #252525;
        }

        .org-info p {
            margin: 4px 0;
            color: #666;
            font-size: 0.9em;
        }

        @media(max-width: 700px) {
            .form-card {
                padding: 24px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
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

<?php include 'show-navbar.php'; ?>
<?php show_navbar(); ?>

<header class="inverse">
    <div class="container">
        <h1><span class="accent-text">Update Non-Profit</span></h1>
    </div>
</header>

<div class="form-container">
    <div class="form-card">
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="org-info">
            <h3><i class="fas fa-info-circle"></i> Organization ID: <?= $org['org_id'] ?></h3>
            <p><strong>Suggested by User ID:</strong> <?= $org['suggester_id'] ?></p>
            <p><strong>Created:</strong> <?= $org['created_at'] ?? 'N/A' ?> | <strong>Last Updated:</strong> <?= $org['updated_at'] ?? 'N/A' ?></p>
        </div>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="org_name">Organization Name *</label>
                    <input type="text" id="org_name" name="org_name" required 
                           value="<?= htmlspecialchars($org['org_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="cause_category">Cause Category</label>
                    <select id="cause_category" name="cause_category">
                        <option value="">Select a category</option>
                        <?php 
                        $categories = ['Education', 'Health', 'Environment', 'Hunger Relief', 'Housing', 'Youth Services', 'Senior Services', 'Animal Welfare', 'Arts & Culture', 'Community Development', 'Other'];
                        foreach ($categories as $category) {
                            $selected = ($org['cause_category'] === $category) ? 'selected' : '';
                            echo "<option value=\"$category\" $selected>$category</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="description">Description</label>
                <textarea id="description" name="description" 
                          placeholder="Describe what this organization does..."><?= htmlspecialchars($org['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="website_url">Website URL</label>
                    <input type="url" id="website_url" name="website_url" 
                           placeholder="https://example.org"
                           value="<?= htmlspecialchars($org['website_url'] ?? '') ?>">
                    <small>Include the full URL (starting with http:// or https://)</small>
                </div>

                <div class="form-group">
                    <label for="org_email">Organization Email</label>
                    <input type="email" id="org_email" name="org_email" 
                           placeholder="contact@organization.org"
                           value="<?= htmlspecialchars($org['org_email'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <?php 
                        $statuses = ['pending', 'approved', 'rejected', 'researching'];
                        foreach ($statuses as $status_option) {
                            $selected = ($org['status'] === $status_option) ? 'selected' : '';
                            echo "<option value=\"$status_option\" $selected>" . ucfirst($status_option) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" 
                           placeholder="Organization address (optional)"
                           value="<?= htmlspecialchars($org['address'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group full-width">
                <label for="notes">Admin Notes</label>
                <textarea id="notes" name="notes" 
                          placeholder="Internal notes about this organization..."><?= htmlspecialchars($org['notes'] ?? '') ?></textarea>
                <small>These notes are for internal use only</small>
            </div>

            <div class="button-group">
                <button type="submit" name="update" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Organization
                </button>
                <button type="submit" name="cancel" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>