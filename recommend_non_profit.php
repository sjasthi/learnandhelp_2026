<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_configuration.php'; // provides $db (mysqli)

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message     = '';
$messageType = '';

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['cancel'])) {
        header('Location: my_account.php');
        exit();
    }

    if (isset($_POST['submit'])) {
        $suggester_id   = (int)$_SESSION['user_id'];
        $org_name       = trim($_POST['org_name']       ?? '');
        $cause_category = trim($_POST['cause_category'] ?? '');
        $description    = trim($_POST['description']    ?? '');
        $website_url    = trim($_POST['website_url']    ?? '');
        $org_email      = trim($_POST['org_email']      ?? '');

        if (empty($org_name)) {
            $message     = 'Organization name is required.';
            $messageType = 'error';
        } else {
            $stmt = $db->prepare("INSERT INTO recommended_orgs (suggester_id, org_name, cause_category, description, website_url, org_email) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $suggester_id, $org_name, $cause_category, $description, $website_url, $org_email);

            if ($stmt->execute()) {
                $stmt->close();
                $_SESSION['flash_message'] = 'Thank you for recommending a Non-Profit!';
                $_SESSION['flash_type']    = 'success';
                header('Location: index.php');
                exit();
            } else {
                $message = ($db->errno === 1062)
                    ? 'This organization has already been recommended. Thank you!'
                    : 'Error submitting recommendation. Please try again.';
                $messageType = 'error';
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Recommend a Non-Profit – Learn and Help</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

    <style>
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
            max-width: 780px;
            margin: 40px auto 60px auto;
            padding: 0 18px;
        }

        /* ── Flash messages ── */
        .flash {
            padding: 13px 20px;
            border-radius: 9px;
            margin-bottom: 22px;
            font-weight: 700;
            font-size: 1em;
        }
        .flash.success { background: #edfae5; color: #2a6006; border: 1.5px solid #99d930; }
        .flash.error   { background: #fff0f0; color: #a00;    border: 1.5px solid #f88; }

        /* ── Card ── */
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 36px 40px;
        }
        .card h2 {
            margin: 0 0 22px 0;
            font-size: 1.25em;
            font-weight: 900;
            color: #274606;
        }

        /* ── Form groups ── */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: .85em;
            font-weight: 700;
            color: #274606;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 6px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 13px;
            border: 1.5px solid #cde8a0;
            border-radius: 8px;
            font-size: 1em;
            font-family: 'Roboto', sans-serif;
            background: #f8fbe9;
            color: #222;
            outline: none;
            transition: border-color .18s, box-shadow .18s;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #99d930;
            box-shadow: 0 0 0 3px rgba(153,217,48,.15);
        }
        .form-group textarea { resize: vertical; min-height: 130px; }
        .form-group small {
            display: block;
            color: #888;
            margin-top: 5px;
            font-size: .83em;
        }

        /* ── Buttons ── */
        .btn-row {
            display: flex;
            gap: 14px;
            justify-content: center;
            margin-top: 28px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 11px 28px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .18s, transform .12s;
        }
        .btn-green   { background: #99d930; color: #274606; box-shadow: 0 2px 8px rgba(153,217,48,.25); }
        .btn-green:hover   { background: #85c220; transform: translateY(-1px); }
        .btn-outline { background: #fff; color: #274606; border: 2px solid #99d930; }
        .btn-outline:hover { background: #f0fad8; }

        @media (max-width: 680px) {
            .card { padding: 22px 16px; }
            .btn-row { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
            .banner-title { font-size: 2em; }
        }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<!-- ── Banner ── -->
<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Recommend a Non-Profit banner">
    <h1 class="banner-title">Recommend a Non-Profit</h1>
    <p class="banner-subtitle">Help us discover organizations making a difference in our community</p>
</div>

<div class="page-wrap">

    <?php if ($message): ?>
        <div class="flash <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>🤝 Non-Profit Recommendation Form</h2>

        <form method="POST" action="">

            <div class="form-group">
                <label for="org_name">Organization Name <span style="color:#c00">*</span></label>
                <input type="text" id="org_name" name="org_name" required
                       placeholder="e.g. Helping Hands Foundation"
                       value="<?= htmlspecialchars($_POST['org_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="cause_category">Cause Category</label>
                <select id="cause_category" name="cause_category">
                    <option value="">— Select a category (optional) —</option>
                    <?php
                    $categories = [
                        'Education','Health','Environment','Hunger Relief','Housing',
                        'Youth Services','Senior Services','Animal Welfare',
                        'Arts & Culture','Community Development','Other'
                    ];
                    foreach ($categories as $cat):
                        $sel = (($_POST['cause_category'] ?? '') === $cat) ? 'selected' : '';
                    ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $sel ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"
                          placeholder="Tell us about this organization and why you recommend them…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="website_url">Website URL</label>
                <input type="url" id="website_url" name="website_url"
                       placeholder="https://example.org"
                       value="<?= htmlspecialchars($_POST['website_url'] ?? '') ?>">
                <small>Include the full URL starting with https://</small>
            </div>

            <div class="form-group">
                <label for="org_email">Organization Email</label>
                <input type="email" id="org_email" name="org_email"
                       placeholder="contact@organization.org"
                       value="<?= htmlspecialchars($_POST['org_email'] ?? '') ?>">
                <small>Contact email for the organization (if known)</small>
            </div>

            <div class="btn-row">
                <button type="submit" name="submit" class="btn btn-green">📨 Submit Recommendation</button>
                <button type="submit" name="cancel" class="btn btn-outline">✕ Cancel</button>
            </div>

        </form>
    </div>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>