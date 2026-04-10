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

// Sanitize Blog_Id — must be a positive integer
$Blog_Id = intval($_POST['Blog_Id'] ?? $_GET['id'] ?? 0);
if ($Blog_Id <= 0) {
    header('Location: admin_blogs.php');
    exit();
}

// ── Fetch blog row ────────────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM blogs WHERE Blog_Id = ?");
$stmt->bind_param("i", $Blog_Id);
$stmt->execute();
$blog = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$blog) {
    header('Location: admin_blogs.php');
    exit();
}

// ── Fetch blog pictures ───────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM blog_pictures WHERE Blog_Id = ?");
$stmt->bind_param("i", $Blog_Id);
$stmt->execute();
$picturesResult = $stmt->get_result();
$pictures       = $picturesResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Edit Blog – Administration</title>
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

        /* ── Card ── */
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 26px 30px;
            margin-bottom: 28px;
        }
        .card h2 {
            margin: 0 0 20px 0;
            font-size: 1.2em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Pictures grid ── */
        .pictures-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 16px;
        }
        .picture-tile {
            background: #f8fbe9;
            border: 1.5px solid #cde8a0;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
        }
        .picture-tile img {
            width: 100%;
            height: 130px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            display: block;
            margin-bottom: 10px;
        }

        /* ── Form ── */
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
        .form-group textarea,
        .form-group input[type="file"] {
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
        .form-group textarea { resize: vertical; min-height: 140px; }
        .form-group .read-only {
            padding: 10px 13px;
            background: #f0f0f0;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            color: #888;
            font-weight: 700;
        }
        .form-group input[type="file"] {
            background: #fff;
            border-style: dashed;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 20px;
            border-radius: 8px;
            font-size: .9em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background .18s, transform .12s;
            white-space: nowrap;
        }
        .btn-green  { background: #99d930; color: #274606; box-shadow: 0 2px 8px rgba(153,217,48,.25); }
        .btn-green:hover  { background: #85c220; transform: translateY(-1px); }
        .btn-danger { background: #fff0f0; color: #c00; border: 1.5px solid #f99; }
        .btn-danger:hover { background: #ffe0e0; }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .card { padding: 16px 12px; }
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
    <h1 class="banner-title">Edit Blog</h1>
</div>

<div class="page-wrap">

    <a href="admin_blogs.php" class="back-link">&#8592; Back to Blogs</a>

    <!-- ── Blog pictures ── -->
    <?php if (!empty($pictures)): ?>
    <div class="card">
        <h2>🖼️ Blog Pictures</h2>
        <div class="pictures-grid">
            <?php foreach ($pictures as $pic):
                $picId   = intval($pic['Picture_Id']);
                $loc     = htmlspecialchars($pic['Location'] ?? '', ENT_QUOTES);
            ?>
            <div class="picture-tile">
                <img src="<?= $loc ?>"
                     alt="Blog picture"
                     onerror="this.src='images/default_blog.png'">
                <form method="POST" action="admin_delete_blog_pictures.php"
                      onsubmit="return confirm('Delete this picture? This cannot be undone.');">
                    <input type="hidden" name="Picture_Id" value="<?= $picId ?>">
                    <input type="hidden" name="Blog_Id"    value="<?= $Blog_Id ?>">
                    <button type="submit" name="btnDelete" class="btn btn-danger" style="width:100%;">
                        🗑 Delete
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Edit blog form ── -->
    <div class="card">
        <h2>✏️ Edit Blog Details</h2>

        <form action="admin_blog_submit.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="Blog_Id" value="<?= $Blog_Id ?>">

            <div class="form-group">
                <label>Blog ID</label>
                <div class="read-only"><?= $Blog_Id ?></div>
            </div>

            <div class="form-group">
                <label for="Title">Title</label>
                <input type="text" id="Title" name="Title"
                       value="<?= htmlspecialchars($blog['Title'] ?? '', ENT_QUOTES) ?>"
                       placeholder="Blog title">
            </div>

            <div class="form-group">
                <label for="Author">Author</label>
                <input type="text" id="Author" name="Author"
                       value="<?= htmlspecialchars($blog['Author'] ?? '', ENT_QUOTES) ?>"
                       placeholder="Author name">
            </div>

            <div class="form-group">
                <label for="Description">Description</label>
                <textarea id="Description" name="Description"
                          placeholder="Blog description…"><?= htmlspecialchars($blog['Description'] ?? '', ENT_QUOTES) ?></textarea>
            </div>

            <div class="form-group">
                <label for="Video_Link">Video Link</label>
                <input type="url" id="Video_Link" name="Video_Link"
                       value="<?= htmlspecialchars($blog['Video_Link'] ?? '', ENT_QUOTES) ?>"
                       placeholder="https://youtube.com/...">
            </div>

            <div class="form-group">
                <label for="Location">Upload Additional Pictures</label>
                <input type="file" id="Location" name="Location[]" multiple accept="image/*">
            </div>

            <button type="submit" name="btnSave" class="btn btn-green">💾 Save Changes</button>
        </form>
    </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>