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

$message     = '';
$messageType = '';

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['Title']       ?? '');
    $author      = trim($_POST['Author']      ?? '');
    $description = trim($_POST['Description'] ?? '');
    $video_link  = trim($_POST['Video_Link']  ?? '');

    if (empty($title)) {
        $message     = 'Title is required.';
        $messageType = 'error';
    } elseif (empty($author)) {
        $message     = 'Author is required.';
        $messageType = 'error';
    } else {
        $stmt = $db->prepare("INSERT INTO blogs (Title, Author, Description, Video_Link, Created_Time, Modified_Time) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("ssss", $title, $author, $description, $video_link);

        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $stmt->close();

            // Handle picture uploads if any
            if (!empty($_FILES['Location']['name'][0])) {
                $upload_dir = "blog_images/";
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                foreach ($_FILES['Location']['tmp_name'] as $i => $tmp) {
                    if ($_FILES['Location']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $ext      = strtolower(pathinfo($_FILES['Location']['name'][$i], PATHINFO_EXTENSION));
                    $allowed  = ['jpg','jpeg','png','gif','webp'];
                    if (!in_array($ext, $allowed)) continue;
                    $filename = uniqid("blog_{$new_id}_", true) . '.' . $ext;
                    $dest     = $upload_dir . $filename;
                    if (move_uploaded_file($tmp, $dest)) {
                        $pic_stmt = $db->prepare("INSERT INTO blog_pictures (Blog_Id, Location) VALUES (?, ?)");
                        $pic_stmt->bind_param("is", $new_id, $dest);
                        $pic_stmt->execute();
                        $pic_stmt->close();
                    }
                }
            }

            $_SESSION['flash_message'] = "Blog \"{$title}\" created successfully!";
            $_SESSION['flash_type']    = 'success';
            header('Location: admin_blogs.php');
            exit();
        } else {
            $message     = 'Error creating blog. Please try again.';
            $messageType = 'error';
            $stmt->close();
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
    <title>Create Blog – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">

    <style>
        body {
            background: #f8f8f8;
            margin: 0;
            font-family: 'Roboto', Arial, sans-serif;
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
            max-width: 800px;
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
            margin: 0 0 22px 0;
            font-size: 1.2em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Form groups ── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 18px;
        }
        .form-group label {
            font-size: .83em;
            font-weight: 700;
            color: #274606;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .form-group input[type="text"],
        .form-group input[type="url"],
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
        .form-group textarea:focus {
            border-color: #99d930;
            box-shadow: 0 0 0 3px rgba(153,217,48,.15);
        }
        .form-group textarea { resize: vertical; min-height: 160px; }
        .form-group input[type="file"] {
            padding: 10px;
            border: 2px dashed #cde8a0;
            border-radius: 8px;
            background: #f8fbe9;
            font-family: 'Roboto', sans-serif;
            width: 100%;
            box-sizing: border-box;
        }
        .form-group small {
            font-size: .8em;
            color: #888;
            margin-top: 3px;
        }

        /* ── Buttons ── */
        .btn-row {
            display: flex;
            gap: 12px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 11px 26px;
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

        @media (max-width: 680px) {
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
    <h1 class="banner-title">Create Blog</h1>
</div>

<div class="page-wrap">

    <a href="admin_blogs.php" class="back-link">&#8592; Back to Blogs</a>

    <?php if ($message): ?>
        <div class="flash <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>📝 New Blog Post</h2>

        <form method="POST" action="" enctype="multipart/form-data">

            <div class="form-group">
                <label for="Title">Title <span style="color:#c00">*</span></label>
                <input type="text" id="Title" name="Title" required
                       placeholder="Blog post title"
                       value="<?= htmlspecialchars($_POST['Title'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="Author">Author <span style="color:#c00">*</span></label>
                <input type="text" id="Author" name="Author" required
                       placeholder="Author name"
                       value="<?= htmlspecialchars($_POST['Author'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="Description">Description</label>
                <textarea id="Description" name="Description"
                          placeholder="Write your blog post content here…"><?= htmlspecialchars($_POST['Description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="Video_Link">Video Link</label>
                <input type="url" id="Video_Link" name="Video_Link"
                       placeholder="https://youtube.com/..."
                       value="<?= htmlspecialchars($_POST['Video_Link'] ?? '') ?>">
                <small>Optional — include a YouTube or video URL to accompany this post</small>
            </div>

            <div class="form-group">
                <label for="Location">Upload Pictures</label>
                <input type="file" id="Location" name="Location[]"
                       multiple accept="image/*">
                <small>You can select multiple images (JPG, PNG, GIF, WEBP)</small>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-green">➕ Create Blog</button>
                <a href="admin_blogs.php" class="btn btn-outline">✕ Cancel</a>
            </div>

        </form>
    </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>