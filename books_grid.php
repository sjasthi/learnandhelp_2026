<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

require 'db_configuration.php'; // provides $db (mysqli)

// ── Fetch books ───────────────────────────────────────────────
$result = $db->query("SELECT id, title, author, grade_level, publisher, image FROM books ORDER BY title ASC");
$books  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Books – Learn and Help</title>
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
            max-width: 1400px;
            margin: 36px auto 60px auto;
            padding: 0 18px;
        }

        /* ── View toggle ── */
        .view-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 22px;
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

        /* ── Search bar ── */
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .search-bar input[type="search"] {
            flex: 1 1 260px;
            padding: 10px 13px;
            border: 1.5px solid #cde8a0;
            border-radius: 8px;
            font-size: .97em;
            font-family: 'Roboto', sans-serif;
            background: #f8fbe9;
            outline: none;
            transition: border-color .18s;
        }
        .search-bar input[type="search"]:focus { border-color: #99d930; }
        .search-bar button {
            padding: 10px 20px;
            background: #99d930;
            color: #274606;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-family: 'Roboto', sans-serif;
            cursor: pointer;
            transition: background .18s;
        }
        .search-bar button:hover { background: #85c220; }

        /* ── Book grid ── */
        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        /* ── Book card ── */
        .book-card {
            background: #fff;
            border: 2px solid #cde8a0;
            border-radius: 12px;
            padding: 16px 12px;
            text-align: center;
            cursor: pointer;
            transition: transform .2s, box-shadow .2s, border-color .2s;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 28px rgba(153,217,48,0.18);
            border-color: #99d930;
        }
        .book-card img {
            width: 100px;
            height: 130px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            margin-bottom: 10px;
        }
        .book-card h3 {
            font-size: .95em;
            font-weight: 700;
            color: #252525;
            margin: 0 0 6px 0;
            line-height: 1.3;
        }
        .book-card .book-author {
            font-size: .82em;
            color: #666;
            margin: 0 0 4px 0;
        }
        .book-card .book-grade {
            font-size: .78em;
            color: #99d930;
            font-weight: 700;
            margin: 0;
        }

        /* ── Hidden data for search ── */
        .hidden { display: none; }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            color: #888;
            padding: 50px 0;
            font-size: 1.05em;
            grid-column: 1 / -1;
        }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .book-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
        }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<!-- ── Banner ── -->
<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="Books banner">
    <h1 class="banner-title">Books</h1>
</div>

<div class="page-wrap">

    <!-- ── View toggle ── -->
    <div class="view-toggle">
        <a href="books_grid.php" class="btn btn-green">⊞ Grid View</a>
        <a href="books.php"      class="btn btn-outline">☰ List View</a>
    </div>

    <!-- ── Search ── -->
    <div class="search-bar">
        <input type="search" id="search-input" placeholder="Search by title, author, grade or publisher…">
        <button id="search-button">🔍 Search</button>
    </div>

    <!-- ── Book grid ── -->
    <div class="book-grid" id="book-grid">
        <?php if (!empty($books)): ?>
            <?php foreach ($books as $row):
                $id        = intval($row['id']);
                $title     = htmlspecialchars($row['title']       ?? '', ENT_QUOTES);
                $author    = htmlspecialchars($row['author']      ?? '', ENT_QUOTES);
                $grade     = htmlspecialchars($row['grade_level'] ?? '', ENT_QUOTES);
                $publisher = htmlspecialchars($row['publisher']   ?? '', ENT_QUOTES);
                $imgSrc    = !empty($row['image'])
                             ? htmlspecialchars($row['image'], ENT_QUOTES)
                             : 'images/books/default.png';
            ?>
            <div class="book-card"
                 onclick="openBookDetails(<?= $id ?>)"
                 data-title="<?= strtolower($title) ?>"
                 data-author="<?= strtolower($author) ?>"
                 data-grade="<?= strtolower($grade) ?>"
                 data-publisher="<?= strtolower($publisher) ?>">
                <img src="<?= $imgSrc ?>"
                     alt="<?= $title ?>"
                     onerror="this.src='images/books/default.png'">
                <h3><?= $title ?></h3>
                <p class="book-author"><?= $author ?></p>
                <p class="book-grade"><?= $grade ?></p>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">No books found.</div>
        <?php endif; ?>
    </div>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>

<script>
    function openBookDetails(bookId) {
        window.location.href = 'book_details.php?book_id=' + bookId;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var searchInput  = document.getElementById('search-input');
        var searchButton = document.getElementById('search-button');
        var bookCards    = document.querySelectorAll('.book-card');

        function filterBooks(q) {
            q = q.toLowerCase().trim();
            bookCards.forEach(function (card) {
                var match = card.dataset.title.includes(q)
                         || card.dataset.author.includes(q)
                         || card.dataset.grade.includes(q)
                         || card.dataset.publisher.includes(q);
                card.style.display = match ? '' : 'none';
            });
        }

        searchButton.addEventListener('click', function () {
            filterBooks(searchInput.value);
        });
        searchInput.addEventListener('input', function () {
            filterBooks(searchInput.value);
        });
    });
</script>
</body>
</html>