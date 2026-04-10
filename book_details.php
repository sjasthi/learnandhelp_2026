<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

require 'db_configuration.php'; // provides $db (mysqli)

// ── Validate book_id ──────────────────────────────────────────
$book_id = (isset($_GET['book_id']) && ctype_digit($_GET['book_id']))
           ? intval($_GET['book_id']) : 0;

if (!$book_id) {
    header('Location: books.php');
    exit();
}

// ── Fetch book by ID ──────────────────────────────────────────
$stmt = $db->prepare("SELECT id, title, author, publisher, grade_level, image, numPages, publishYear, available FROM books WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    header('Location: books.php');
    exit();
}

$title      = htmlspecialchars($book['title']       ?? '', ENT_QUOTES);
$author     = htmlspecialchars($book['author']      ?? '', ENT_QUOTES);
$publisher  = htmlspecialchars($book['publisher']   ?? '', ENT_QUOTES);
$grade      = htmlspecialchars($book['grade_level'] ?? '', ENT_QUOTES);
$pages      = htmlspecialchars($book['numPages']    ?? '', ENT_QUOTES);
$year       = htmlspecialchars($book['publishYear'] ?? '', ENT_QUOTES);
$available  = ($book['available'] ?? '0') == '1' ? 'Available' : 'Not Available';
$imgSrc     = !empty($book['image'])
              ? htmlspecialchars($book['image'], ENT_QUOTES)
              : 'images/books/default.png';
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title><?= $title ?> – Learn and Help</title>
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
        .banner-wrapper img.banner-bg {
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
            max-width: 860px;
            margin: 40px auto 60px auto;
            padding: 0 18px;
        }

        /* ── Back link ── */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 20px;
            font-weight: 700;
            color: #274606;
            text-decoration: none;
            font-size: .97em;
        }
        .back-link:hover { color: #99d930; }

        /* ── Book detail card ── */
        .book-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(80,120,180,0.09);
            border: 2px solid #99d930;
            padding: 36px;
            display: flex;
            gap: 36px;
            align-items: flex-start;
        }

        /* ── Cover image ── */
        .book-cover {
            flex-shrink: 0;
            width: 180px;
        }
        .book-cover img {
            width: 100%;
            border-radius: 8px;
            border: 1px solid #cde8a0;
            box-shadow: 0 4px 16px rgba(0,0,0,.1);
            display: block;
        }

        /* ── Book info ── */
        .book-info { flex: 1; }
        .book-info h2 {
            margin: 0 0 6px 0;
            font-size: 1.7em;
            font-weight: 900;
            color: #252525;
            line-height: 1.25;
        }
        .book-info .book-author {
            font-size: 1.05em;
            color: #555;
            margin: 0 0 20px 0;
        }

        .detail-row {
            display: flex;
            gap: 8px;
            align-items: baseline;
            margin-bottom: 10px;
            font-size: .97em;
            color: #333;
        }
        .detail-label {
            font-weight: 700;
            color: #274606;
            min-width: 110px;
            flex-shrink: 0;
        }

        /* ── Availability badge ── */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: .85em;
            font-weight: 700;
        }
        .badge-available   { background: #edfae5; color: #2a6006; border: 1px solid #99d930; }
        .badge-unavailable { background: #fff0f0; color: #c00;    border: 1px solid #f99; }

        @media (max-width: 640px) {
            .book-card { flex-direction: column; align-items: center; }
            .book-cover { width: 140px; }
            .book-info h2 { font-size: 1.3em; text-align: center; }
            .book-info .book-author { text-align: center; }
            .banner-title { font-size: 2em; }
        }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<!-- ── Banner ── -->
<div class="banner-wrapper">
    <img class="banner-bg" src="images/banner_images/Admin/block-pattern.jpg" alt="Book Details banner">
    <h1 class="banner-title">Book Details</h1>
</div>

<div class="page-wrap">

    <a href="javascript:history.back()" class="back-link">&#8592; Back</a>

    <div class="book-card">
        <!-- Cover image -->
        <div class="book-cover">
            <img src="<?= $imgSrc ?>"
                 alt="<?= $title ?>"
                 onerror="this.src='images/books/default.png'">
        </div>

        <!-- Details -->
        <div class="book-info">
            <h2><?= $title ?></h2>
            <p class="book-author">by <?= $author ?></p>

            <div class="detail-row">
                <span class="detail-label">Publisher</span>
                <span><?= $publisher ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Grade Level</span>
                <span><?= $grade ?></span>
            </div>
            <?php if ($year): ?>
            <div class="detail-row">
                <span class="detail-label">Year Published</span>
                <span><?= $year ?></span>
            </div>
            <?php endif; ?>
            <?php if ($pages): ?>
            <div class="detail-row">
                <span class="detail-label">Pages</span>
                <span><?= $pages ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <span class="detail-label">Availability</span>
                <span class="badge <?= $book['available'] == '1' ? 'badge-available' : 'badge-unavailable' ?>">
                    <?= $available ?>
                </span>
            </div>
        </div>
    </div>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>