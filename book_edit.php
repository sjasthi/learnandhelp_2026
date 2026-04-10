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

// Sanitize inputs — book_id must be a positive integer or null
$book_id    = null;
$book_image = '';

$raw_id = $_POST['book_id'] ?? $_GET['book_id'] ?? null;
if ($raw_id !== null && $raw_id !== '') {
    $candidate = intval($raw_id);
    if ($candidate > 0) $book_id = $candidate;
}
$book_image = htmlspecialchars(trim($_POST['book_image'] ?? ''), ENT_QUOTES, 'UTF-8');

include 'book_fill.php';
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title><?= $book_id ? 'Edit Book' : 'Add Book' ?> – Administration</title>
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
            padding: 28px 32px;
            margin-bottom: 28px;
        }
        .card h2 {
            margin: 0 0 20px 0;
            font-size: 1.2em;
            color: #274606;
            font-weight: 900;
        }

        /* ── File upload ── */
        .file-input-wrap {
            margin-top: 18px;
            padding: 12px;
            border: 2px dashed #cde8a0;
            border-radius: 8px;
            background: #f8fbe9;
        }
        .file-input-wrap label {
            display: block;
            font-size: .83em;
            font-weight: 700;
            color: #274606;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 8px;
        }
        .file-input-wrap input[type="file"] {
            font-family: 'Roboto', sans-serif;
            font-size: .95em;
            width: 100%;
        }

        /* ── Submit button ── */
        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 18px;
            padding: 11px 28px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            background: #99d930;
            color: #274606;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(153,217,48,.25);
            transition: background .18s, transform .12s;
        }
        .btn-submit:hover { background: #85c220; transform: translateY(-1px); }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .card { padding: 18px 14px; }
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
    <h1 class="banner-title"><?= $book_id ? 'Edit Book' : 'Add Book' ?></h1>
</div>

<div class="page-wrap">

    <a href="books.php" class="back-link">&#8592; Back to Books</a>

    <div class="card">
        <h2><?= $book_id ? '✏️ Edit Book Details' : '➕ Add New Book' ?></h2>

        <?php
        // fill_book_form() opens a <form> tag internally.
        // Hidden inputs + submit must sit inside that same form.
        fill_book_form($book_id);
        ?>

        <!-- Cover image upload -->
        <div class="file-input-wrap">
            <label for="media_upload">📎 Select Cover Image</label>
            <input id="media_upload" type="file" name="file" accept="image/*">
        </div>

        <!-- Pass through book_id and book_image -->
        <input type="hidden" name="book_id"    value="<?= intval($book_id) ?>">
        <input type="hidden" name="book_image" value="<?= $book_image ?>">

        <!-- Action flag -->
        <?php if ($book_id): ?>
            <input type="hidden" name="action" value="admin_edit_book">
        <?php else: ?>
            <input type="hidden" name="action" value="admin_add_book">
        <?php endif; ?>

        <br>
        <button type="submit" id="submit-book" name="submit" class="btn-submit">
            💾 <?= $book_id ? 'Save Changes' : 'Add Book' ?>
        </button>

        </form><!-- closes form opened by fill_book_form() -->
    </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>