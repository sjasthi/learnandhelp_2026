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

function get_admin_notes(): string {
    $filename = 'admin_notes.txt';
    return file_exists($filename) ? file_get_contents($filename) : '';
}
?>
<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Admin Notes – Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">

    <!-- jQuery (needed for change-detection script) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
            padding: 30px 32px;
            margin-bottom: 28px;
        }
        .card h2 {
            margin: 0 0 18px 0;
            font-size: 1.2em;
            color: #274606;
            font-weight: 900;
        }

        /* ── Textarea ── */
        .notes-textarea {
            width: 100%;
            height: 420px;
            padding: 14px;
            font-size: 1em;
            font-family: 'Roboto', Arial, sans-serif;
            border: 1.5px solid #cde8a0;
            border-radius: 10px;
            background: #f8fbe9;
            color: #222;
            resize: vertical;
            outline: none;
            box-sizing: border-box;
            transition: border-color .18s, box-shadow .18s;
            line-height: 1.6;
        }
        .notes-textarea:focus {
            border-color: #99d930;
            box-shadow: 0 0 0 3px rgba(153,217,48,.15);
        }

        /* ── Save button ── */
        .btn-save {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 16px;
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
        .btn-save:hover { background: #85c220; transform: translateY(-1px); }

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
    <h1 class="banner-title">Admin Notes</h1>
</div>

<div class="page-wrap">

    <a href="administration.php" class="back-link">&#8592; Back to Administration</a>

    <div class="card">
        <h2>📝 Internal Notes</h2>
        <p style="color:#888;font-size:.9em;margin:0 0 16px 0;">
            These notes are only visible to administrators and are saved to the server.
        </p>
        <form method="POST" action="update_admin_notes.php">
            <textarea
                name="admin_notes"
                id="admin_notes"
                class="notes-textarea"><?php echo htmlspecialchars(get_admin_notes()); ?></textarea>
            <br>
            <button type="submit" class="btn-save">💾 Save Notes</button>
        </form>
    </div>

</div><!-- /page-wrap -->

<script>
    $(document).ready(function () {
        var originalContent = $('#admin_notes').val();
        $('form').on('submit', function (e) {
            if ($('#admin_notes').val() === originalContent) {
                e.preventDefault();
                alert('No changes were made. Please edit the notes before saving.');
            }
        });
    });
</script>

<?php include 'footer.php'; ?>
</body>
</html>