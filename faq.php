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
    <title>FAQs – Learn and Help</title>
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
            max-width: 860px;
            margin: 40px auto 60px auto;
            padding: 0 18px;
        }

        /* ── FAQ accordion ── */
        details {
            background: #fff;
            border: 2px solid #cde8a0;
            border-radius: 12px;
            margin: 12px 0;
            transition: border-color .2s, box-shadow .2s;
            overflow: hidden;
        }
        details[open] {
            border-color: #99d930;
            box-shadow: 0 6px 24px rgba(153,217,48,0.14);
        }

        summary {
            cursor: pointer;
            padding: 16px 20px;
            font-weight: 700;
            font-size: 1.02em;
            color: #274606;
            list-style: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            text-align: left;
            user-select: none;
        }
        summary::-webkit-details-marker { display: none; }

        /* Arrow indicator */
        summary::after {
            content: '▶';
            font-size: .75em;
            color: #99d930;
            flex-shrink: 0;
            transition: transform .2s;
        }
        details[open] summary::after {
            transform: rotate(90deg);
        }
        details[open] summary {
            border-bottom: 1.5px solid #e8f5c8;
            background: #f8fbe9;
        }

        /* Answer */
        .faq-answer {
            padding: 16px 20px 18px 20px;
            line-height: 1.7;
            color: #333;
            font-size: .97em;
            text-align: left;
        }

        /* Meta */
        .faq-meta {
            font-size: .82em;
            color: #aaa;
            margin-top: 18px;
            text-align: right;
        }

        /* Empty state */
        .faq-empty {
            background: #fff;
            border: 2px solid #cde8a0;
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            color: #888;
            font-size: 1.05em;
        }

        @media (max-width: 680px) {
            .banner-title { font-size: 2em; }
            .banner-subtitle { font-size: .85em; white-space: normal; text-align: center; width: 90%; }
        }
    </style>
</head>
<body>

<?php include 'show-navbar.php'; show_navbar(); ?>

<!-- ── Banner ── -->
<div class="banner-wrapper">
    <img src="images/banner_images/Admin/block-pattern.jpg" alt="FAQs banner">
    <h1 class="banner-title">FAQs</h1>
    <p class="banner-subtitle">Click a question to see the answer</p>
</div>

<div class="page-wrap">

    <?php
    $faqsPath = __DIR__ . '/faqs.txt';

    /**
     * Parse faqs.txt where questions start with "Q:" and answers start with "A:".
     * Supports multi-line answers.
     */
    function parseFaqFile(string $path): array {
        if (!is_file($path)) return [];
        $content = file_get_contents($path);
        if ($content === false) return [];

        $lines    = preg_split("/\R/", $content);
        $faqs     = [];
        $currentQ = null;
        $currentA = [];

        foreach ($lines as $raw) {
            $line = trim($raw);

            if ($line === '') {
                if ($currentQ !== null && !empty($currentA)) {
                    $currentA[] = '';
                }
                continue;
            }

            if (stripos($line, 'Q:') === 0) {
                if ($currentQ !== null) {
                    $faqs[] = ['q' => $currentQ, 'a' => rtrim(implode("\n", $currentA))];
                    $currentA = [];
                }
                $currentQ = ltrim(substr($line, 2));
                continue;
            }

            if (stripos($line, 'A:') === 0) {
                $currentA[] = ltrim(substr($line, 2));
            } else {
                if ($currentQ !== null) $currentA[] = $line;
            }
        }

        if ($currentQ !== null) {
            $faqs[] = ['q' => $currentQ, 'a' => rtrim(implode("\n", $currentA))];
        }
        return $faqs;
    }

    $faqs = parseFaqFile($faqsPath);

    if (empty($faqs)) {
        echo '<div class="faq-empty">No FAQs available right now. Please check back later.</div>';
    } else {
        foreach ($faqs as $idx => $item) {
            $q = htmlspecialchars($item['q'] ?? '', ENT_QUOTES, 'UTF-8');
            $a = nl2br(htmlspecialchars($item['a'] ?? '', ENT_QUOTES, 'UTF-8'));
            echo '<details' . ($idx === 0 ? ' open' : '') . '>';
            echo '  <summary>' . $q . '</summary>';
            echo '  <div class="faq-answer">' . $a . '</div>';
            echo '</details>';
        }

        $mtime = @filemtime($faqsPath);
        if ($mtime) {
            echo '<div class="faq-meta">Last updated: ' . date('F j, Y, g:i a', $mtime) . '</div>';
        }
    }
    ?>

</div><!-- /page-wrap -->

<?php include 'footer.php'; ?>
</body>
</html>