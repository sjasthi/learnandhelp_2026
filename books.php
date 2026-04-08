<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Books – Learn and Help</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">

    <!-- jQuery (single instance) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

    <!-- Book functions -->
    <script type="text/javascript" src="js/book_functions.js"></script>

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
        .banner-wrapper img.banner-img {
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

        /* Slideshow dots */
        .dots-container {
            position: absolute;
            bottom: 14px;
            left: 0; right: 0;
            text-align: center;
            z-index: 3;
        }
        .dot {
            cursor: pointer;
            height: 10px; width: 10px;
            margin: 0 3px;
            background: rgba(255,255,255,.5);
            border-radius: 50%;
            display: inline-block;
            transition: background .3s;
        }
        .dot.active, .dot:hover { background: #99d930; }
        .banner-slide { display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .banner-slide img { width: 100%; height: 100%; object-fit: cover; }

        /* ── Page wrapper ── */
        .page-wrap {
            max-width: 1400px;
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
            padding: 24px 28px;
            margin-bottom: 28px;
        }

        /* ── Toolbar ── */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
        }
        .toggle-cols {
            font-size: .88em;
            color: #555;
        }
        .toggle-cols a {
            color: #274606;
            font-weight: 700;
            text-decoration: none;
            margin: 0 3px;
        }
        .toggle-cols a:hover { color: #99d930; }

        /* ── Buttons ── */
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
        .btn-green {
            background: #99d930;
            color: #274606;
            box-shadow: 0 2px 8px rgba(153,217,48,.25);
        }
        .btn-green:hover { background: #85c220; transform: translateY(-1px); }
        .btn-outline {
            background: #fff;
            color: #274606;
            border: 2px solid #99d930;
        }
        .btn-outline:hover { background: #f0fad8; }

        /* ── View toggle ── */
        .view-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        /* ── Grade level form ── */
        .grade-form {
            background: #f8fbe9;
            border: 1.5px solid #cde8a0;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 22px;
        }
        .grade-form h4 {
            margin: 0 0 12px 0;
            color: #274606;
            font-weight: 700;
        }
        .grade-form label {
            margin-right: 20px;
            font-size: .95em;
            color: #333;
            cursor: pointer;
        }
        .grade-form label input {
            margin-right: 5px;
            accent-color: #99d930;
        }
        .grade-form .btn-green {
            margin-top: 12px;
        }

        /* ── Admin add button ── */
        .admin-actions {
            margin-bottom: 16px;
        }

        /* ── Loading state ── */
        #loading {
            text-align: center;
            padding: 40px 0;
            color: #888;
        }
        #loading img { width: 48px; margin-top: 10px; }

        /* ── DataTables overrides ── */
        .dataTables_wrapper .dataTables_length   { float: left; }
        .dataTables_wrapper .dataTables_filter   { float: right; clear: none; }
        .dataTables_wrapper .dataTables_info     { clear: both; float: left; padding-top: 8px; }
        .dataTables_wrapper .dataTables_paginate { float: right; padding-top: 8px; }
        table.dataTable thead input {
            width: 100%;
            box-sizing: border-box;
            padding: 3px 6px;
        }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .card { padding: 14px 10px; }
            .view-toggle { flex-wrap: wrap; }
        }
    </style>

    <script>
        // ── Slideshow (defined globally so onclick handlers work) ──
        var slideIndex = 1;
        function plusSlides(n)   { showSlides(slideIndex += n); }
        function currentSlide(n) { showSlides(slideIndex = n); }
        function showSlides(n) {
            var slides = document.getElementsByClassName('banner-slide');
            var dots   = document.getElementsByClassName('dot');
            if (!slides.length) return;
            if (n > slides.length) slideIndex = 1;
            if (n < 1)             slideIndex = slides.length;
            for (var i = 0; i < slides.length; i++) slides[i].style.display = 'none';
            for (var i = 0; i < dots.length;   i++) dots[i].classList.remove('active');
            slides[slideIndex - 1].style.display = 'block';
            if (dots[slideIndex - 1]) dots[slideIndex - 1].classList.add('active');
        }

        $(document).ready(function () {
            // Start slideshow once DOM is ready
            showSlides(slideIndex);

            // ── Grade level checkboxes ────────────────────────
            var checkboxes = $('.grade-checkbox');
            checkboxes.on('change', function () {
                if ($('.grade-checkbox:checked').length > 0) {
                    checkboxes.removeAttr('required');
                } else {
                    checkboxes.attr('required', 'required');
                }
            });

            // ── Load books asynchronously (non-blocking) ──────
            // Previously used async:false which froze the browser until
            // books_get_all.php finished — now truly async with spinner.
            $.ajax({
                url: 'books_get_all.php',
                type: 'GET',
                success: function (data) {
                    var bookList = JSON.parse(data);

                    // Insert rows first, THEN clone header & init DataTable
                    $('#book_body').append(bookList.data);

                    // Clone header row for per-column search inputs
                    $('#books_table thead tr').clone(true).appendTo('#books_table thead');
                    $('#books_table thead tr:eq(1) th').each(function () {
                        var title = $(this).text();
                        $(this).html('<input type="text" placeholder="Search ' + title + '" />');
                    });

                    // Hide spinner and reveal table
                    $('#loading').attr('hidden', 'true');
                    $('#books_page').removeAttr('hidden');

                    // Init DataTable only after rows are in the DOM
                    var table = $('#books_table').DataTable({
                        initComplete: function () {
                            this.api().columns().every(function () {
                                var that = this;
                                $('input', this.header()).on('keyup change clear', function () {
                                    if (that.search() !== this.value) {
                                        that.search(this.value).draw();
                                    }
                                });
                            });
                        }
                    });

                    $('a.toggle-vis').on('click', function (e) {
                        e.preventDefault();
                        var column = table.column($(this).attr('data-column'));
                        column.visible(!column.visible());
                    });
                },
                error: function () {
                    $('#loading').html(
                        '<p style="color:#c00;font-weight:700;padding:20px;">Failed to load books. Please refresh the page.</p>'
                    );
                }
            });
        });
    </script>
</head>
<body>

<?php
include 'show-navbar.php';
show_navbar();
?>

<!-- ── Banner with slideshow ── -->
<div class="banner-wrapper">
    <?php
    $images_dir = './images/banner_images/Books/';
    $images     = glob($images_dir . '*.{jpg,png}', GLOB_BRACE);
    foreach ($images as $index => $image) {
        $safe = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
        echo "<div class='banner-slide'><img src='{$safe}' alt='Books banner'></div>";
    }
    ?>
    <h1 class="banner-title">Books</h1>
    <div class="dots-container">
        <?php
        foreach ($images as $index => $image) {
            $n = $index + 1;
            echo "<span class='dot' onclick='currentSlide($n)'></span>";
        }
        ?>
    </div>
</div>

<div class="page-wrap">

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="administration.php" class="back-link">&#8592; Back to Administration</a>
    <?php endif; ?>

    <!-- ── View toggle buttons ── -->
    <div class="view-toggle">
        <a href="books_grid.php" class="btn btn-outline">⊞ Grid View</a>
        <a href="books.php"      class="btn btn-green">☰ List View</a>
    </div>

    <!-- ── Admin: Add New Book ── -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="admin-actions">
        <form action="book_edit.php" method="post" enctype="multipart/form-data" style="display:inline;">
            <input type="hidden" name="book_id" value="">
            <button type="submit" name="edit_book" class="btn btn-green">➕ Add New Book</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- ── Grade level filter ── -->
    <div class="grade-form">
        <form action="book_create_list_by_grade.php" method="post">
            <h4>Select Books by Grade Level</h4>
            <label><input class="grade-checkbox" type="checkbox" name="high_school" value="True" required> High School</label>
            <label><input class="grade-checkbox" type="checkbox" name="primary_school_upper" value="True"> Primary School Upper</label>
            <label><input class="grade-checkbox" type="checkbox" name="primary_school" value="True"> Primary School</label>
            <br>
            <button type="submit" class="btn btn-green" style="margin-top:12px;">Get Selections</button>
        </form>
    </div>

    <!-- ── Books Table ── -->
    <div class="card">
        <!-- Loading state -->
        <div id="loading">
            <h2>Loading, please wait…</h2>
            <img src="images/loadingIcon.gif" alt="Loading">
        </div>

        <div id="books_page" hidden>
            <!-- Toggle columns -->
            <div class="toolbar">
                <div class="toggle-cols">
                    Toggle column:
                    <a class="toggle-vis" data-column="0">Grade Level</a> -
                    <a class="toggle-vis" data-column="1">Image</a> -
                    <a class="toggle-vis" data-column="2">Title</a> -
                    <a class="toggle-vis" data-column="3">Author</a> -
                    <a class="toggle-vis" data-column="4">Publisher</a> -
                    <a class="toggle-vis" data-column="5">Year Published</a> -
                    <a class="toggle-vis" data-column="6">Page Count</a> -
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a class="toggle-vis" data-column="7">Price</a> -
                        <a class="toggle-vis" data-column="8">Available</a> -
                        <a class="toggle-vis" data-column="9">Edit</a>
                    <?php else: ?>
                        <a class="toggle-vis" data-column="7">Available</a>
                    <?php endif; ?>
                </div>
            </div>

            <table id="books_table" class="display compact" style="width:100%">
                <thead>
                    <tr>
                        <th>Grade Level</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Publisher</th>
                        <th>Year Published</th>
                        <th>Page Count</th>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <th>Price</th>
                        <?php endif; ?>
                        <th>Available</th>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <th>Edit</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="book_body">
                    <!-- Populated via AJAX from books_get_all.php -->
                </tbody>
            </table>
        </div><!-- /books_page -->

    </div><!-- /card -->

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>