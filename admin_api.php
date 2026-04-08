<?php
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}

include 'determine_paths.php'; // sets path for linked files

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
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>API Support – Administration</title>
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
            max-width: 1100px;
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
            padding: 30px 32px;
            margin-bottom: 28px;
        }
        .card h2 {
            margin: 0 0 16px 0;
            font-size: 1.25em;
            color: #274606;
            font-weight: 900;
        }
        .card h3 {
            margin: 0 0 12px 0;
            font-size: 1.05em;
            color: #274606;
            font-weight: 700;
        }
        .card p {
            margin: 0 0 12px 0;
            color: #444;
            line-height: 1.65;
            font-size: .97em;
        }

        /* ── Endpoint box ── */
        .endpoint {
            background: #f8fbe9;
            border: 1.5px solid #cde8a0;
            border-radius: 8px;
            padding: 12px 16px;
            font-family: monospace;
            font-size: .95em;
            color: #274606;
            word-break: break-all;
            margin-bottom: 14px;
        }
        .endpoint mark {
            background: #99d930;
            color: #274606;
            padding: 1px 5px;
            border-radius: 4px;
        }
        .endpoint a {
            color: #274606;
            text-decoration: underline;
        }
        .endpoint a:hover { color: #85c220; }

        /* ── Example images ── */
        .api-example-img {
            max-width: 100%;
            border-radius: 10px;
            border: 1.5px solid #cde8a0;
            margin-top: 14px;
            display: block;
        }

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
    <h1 class="banner-title">API Support</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <!-- ── Endpoints ── -->
    <div class="card">
        <h2>API Documentation</h2>

        <h3>School Information</h3>
        <p>To retrieve school information, use the following endpoint with a specific school ID:</p>
        <div class="endpoint">
            https://learnandhelp.jasthi.com/API/get_school_info.php?id=<mark>{id}</mark>
        </div>

        <h3>Book Information</h3>
        <p>To retrieve book information, use the following endpoint with a specific book ID:</p>
        <div class="endpoint">
            https://learnandhelp.jasthi.com/API/get_book_info.php?id=<mark>{id}</mark>
        </div>
    </div>

    <!-- ── Parameters ── -->
    <div class="card">
        <h2>Parameters</h2>
        <p>
            The value after the <code>=</code> sign is the ID of the record you want to retrieve.
            Replace <mark style="background:#99d930;color:#274606;padding:1px 6px;border-radius:4px;">{id}</mark> with any valid numeric ID from the database.
        </p>
        <div class="endpoint">
            https://learnandhelp.jasthi.com/API/get_school_info.php?id=<mark>{id}</mark>
        </div>
    </div>

    <!-- ── Examples ── -->
    <div class="card">
        <h2>Examples</h2>

        <h3>School — ID 10</h3>
        <p>The link below fetches the school record with ID 10. Change the number to retrieve a different school.</p>
        <div class="endpoint">
            <a href="<?php echo $baseUrl ?>API/get_school_info.php?id=10">
                https://learnandhelp.jasthi.com/API/get_school_info.php?id=10
            </a>
        </div>
        <img src="images/api_example1.png" alt="School API example" class="api-example-img">

        <h3 style="margin-top:24px;">Book — ID 1389</h3>
        <p>The link below fetches the book record with ID 1389. Change the number to retrieve a different book.</p>
        <div class="endpoint">
            <a href="<?php echo $baseUrl ?>API/get_book_info.php?id=1389">
                https://learnandhelp.jasthi.com/API/get_book_info.php?id=1389
            </a>
        </div>
        <img src="images/books_api.png" alt="Book API example" class="api-example-img">
    </div>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>