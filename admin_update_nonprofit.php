<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

// Block unauthorized users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Forbidden');
}

require 'db_configuration.php'; // provides $db (mysqli)

// ── Validate org ID ───────────────────────────────────────────
$orgId = (isset($_GET['id']) && ctype_digit($_GET['id'])) ? (int)$_GET['id'] : 0;
if (!$orgId) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid organization ID.'];
    header('Location: admin_non_profits.php');
    exit();
}

$message     = '';
$messageType = '';

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['cancel'])) {
        header('Location: admin_non_profits.php');
        exit();
    }

    if (isset($_POST['update'])) {
        $org_name       = trim($_POST['org_name']       ?? '');
        $cause_category = trim($_POST['cause_category'] ?? '');
        $description    = trim($_POST['description']    ?? '');
        $website_url    = trim($_POST['website_url']    ?? '');
        $org_email      = trim($_POST['org_email']      ?? '');
        $org_status     = in_array($_POST['status'] ?? '', ['pending','approved','rejected','researching'])
                          ? $_POST['status'] : 'pending';
        $address        = trim($_POST['address']        ?? '');
        $notes          = trim($_POST['notes']          ?? '');

        if (empty($org_name)) {
            $message     = 'Organization name is required.';
            $messageType = 'error';
        } else {
            $stmt = $db->prepare("UPDATE recommended_orgs SET org_name=?, cause_category=?, description=?, website_url=?, org_email=?, status=?, address=?, notes=?, updated_at=NOW() WHERE org_id=?");
            $stmt->bind_param("ssssssssi", $org_name, $cause_category, $description, $website_url, $org_email, $org_status, $address, $notes, $orgId);

            if ($stmt->execute()) {
                $stmt->close();
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Organization updated successfully.'];
                header('Location: admin_non_profits.php');
                exit();
            } else {
                $message     = ($db->errno === 1062)
                    ? 'This website URL already exists for another organization.'
                    : 'Error updating organization. Please try again.';
                $messageType = 'error';
                $stmt->close();
            }
        }
    }
}

// ── Fetch org data ────────────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM recommended_orgs WHERE org_id = ?");
$stmt->bind_param("i", $orgId);
$stmt->execute();
$org = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$org) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Organization not found.'];
    header('Location: admin_non_profits.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Update Non-Profit – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body {
            background: #f8f8f8;
            margin: 0;
            font-family: 'Roboto', Arial, sans-serif;
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

        /* ── Page wrapper ── */
        .page-wrap {
            max-width: 900px;
            margin: 36px auto 60px auto;
            padding: 0 18px;
        }

        /* ── Back link ── */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 18px;
            font-weight: 700;
            color: #274606;
            text-decoration: none;
            font-size: .97em;
        }
        .back-link:hover { color: #99d930; }

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
            padding: 30px 32px;
            margin-bottom: 28px;
        }
        .card h2 {
            margin: 0 0 20px 0;
            font-size: 1.2em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Org info box ── */
        .org-info {
            background: #f8fbe9;
            border-left: 4px solid #99d930;
            border-radius: 0 10px 10px 0;
            padding: 14px 18px;
            margin-bottom: 24px;
            font-size: .92em;
        }
        .org-info h3 { margin: 0 0 6px 0; color: #274606; font-size: 1em; font-weight: 900; }
        .org-info p  { margin: 3px 0; color: #555; }

        /* ── Form grid ── */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 0;
        }

        /* ── Form groups ── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 18px;
        }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label {
            font-size: .83em;
            font-weight: 700;
            color: #274606;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="url"],
        .form-group select,
        .form-group textarea {
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
            width: 100%;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #99d930;
            box-shadow: 0 0 0 3px rgba(153,217,48,.15);
        }
        .form-group textarea { resize: vertical; min-height: 120px; }
        .form-group small { font-size: .8em; color: #888; margin-top: 3px; }

        /* ── Buttons ── */
        .btn-row {
            display: flex;
            gap: 14px;
            justify-content: center;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 11px 28px;
            border-radius: 8px;
            font-size: .97em;
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

        @media (max-width: 700px) {
            .form-row { grid-template-columns: 1fr; }
            .banner-title { font-size: 2em; }
            .card { padding: 18px 14px; }
            .btn-row { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<?php
include 'show-navbar.php';
show_navbar();
?>

<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Admin banner">
    <h1 class="banner-title">Update Non-Profit</h1>
</div>

<div class="page-wrap">

    <a href="admin_non_profits.php" class="back-link">&#8592; Back to Non-Profits</a>

    <?php if ($message): ?>
        <div class="flash <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>✏️ Edit Organization</h2>

        <!-- Org info box -->
        <div class="org-info">
            <h3><i class="fas fa-info-circle"></i> Organization ID: <?= intval($org['org_id']) ?></h3>
            <p><strong>Suggested by User ID:</strong> <?= intval($org['suggester_id']) ?></p>
            <p>
                <strong>Created:</strong> <?= htmlspecialchars($org['created_at'] ?? 'N/A') ?>
                &nbsp;|&nbsp;
                <strong>Last Updated:</strong> <?= htmlspecialchars($org['updated_at'] ?? 'N/A') ?>
            </p>
        </div>

        <form method="POST" action="">

            <div class="form-row">
                <div class="form-group">
                    <label for="org_name">Organization Name <span style="color:#c00">*</span></label>
                    <input type="text" id="org_name" name="org_name" required
                           value="<?= htmlspecialchars($org['org_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="cause_category">Cause Category</label>
                    <select id="cause_category" name="cause_category">
                        <option value="">— Select a category —</option>
                        <?php
                        $categories = ['Education','Health','Environment','Hunger Relief','Housing',
                                       'Youth Services','Senior Services','Animal Welfare',
                                       'Arts & Culture','Community Development','Other'];
                        foreach ($categories as $cat):
                            $sel = ($org['cause_category'] === $cat) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $sel ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="description">Description</label>
                <textarea id="description" name="description"
                          placeholder="Describe what this organization does…"><?= htmlspecialchars($org['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="website_url">Website URL</label>
                    <input type="url" id="website_url" name="website_url"
                           placeholder="https://example.org"
                           value="<?= htmlspecialchars($org['website_url'] ?? '') ?>">
                    <small>Include the full URL starting with https://</small>
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
                    <label for="status">Status <span style="color:#c00">*</span></label>
                    <select id="status" name="status" required>
                        <?php
                        $statuses = ['pending','approved','rejected','researching'];
                        foreach ($statuses as $s):
                            $sel = ($org['status'] === $s) ? 'selected' : '';
                        ?>
                            <option value="<?= $s ?>" <?= $sel ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
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
                          placeholder="Internal notes about this organization…"><?= htmlspecialchars($org['notes'] ?? '') ?></textarea>
                <small>These notes are for internal use only</small>
            </div>

            <div class="btn-row">
                <button type="submit" name="update" class="btn btn-green">
                    <i class="fas fa-save"></i> Update Organization
                </button>
                <button type="submit" name="cancel" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>

        </form>
    </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>