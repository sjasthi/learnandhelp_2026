<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$status = session_status();
if ($status == PHP_SESSION_NONE) {
    session_start();
}
function get_profile_image($id)
{
    $image_name = glob('schools/' . $id . '/profile_image.*');
    if (count($image_name) == 1) {
        return $image_name[0];
    } else {
        return "images/admin_icons/school.png";
    }
}
function get_summary_data($connection, $field)
{
    $summary_data = [];
    $sql = "SELECT $field, COUNT(*) as count FROM schools GROUP BY $field";
    $result = mysqli_query($connection, $sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $key = $row[$field];
            if (empty($key)) {
                $key = "Null/Not Specified";
            }
            $summary_data[$key] = $row['count'];
        }
    }
    return $summary_data;
}
require 'db_configuration.php';
$connection = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);
if ($connection === false) {
    die("Failed to connect to database: " . mysqli_connect_error());
}
$search = isset($_GET['search']) ? $_GET['search'] : '';
$summary_by_state = get_summary_data($connection, 'state_name');
$summary_by_sponsor = get_summary_data($connection, 'category');
$summary_by_supported_by = get_summary_data($connection, 'supported_by');
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="images/icon_logo.png" type="image/icon type">
    <title>Learn and Help</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;900&display=swap" rel="stylesheet">
    <link href="css/main.css?v=2025-08-22a" rel="stylesheet">
    <style>
        .search-container { text-align: center; margin-bottom: 20px; }
        .search-input { width: 300px; padding: 10px; font-size: 16px; }
        .search-button { padding: 10px 20px; background-color: #99D930; color: white; border: none; cursor: pointer; font-size: 16px; }
        .school-icon { text-align: center; vertical-align: top; padding: 10px; }
        .school-icon img { max-width: 100px; max-height: 100px; }
        .school-info p { font-size: 14px; margin: 0; color: #333; }
        .dot { cursor: pointer; height: 10px; width: 10px; margin: 0 2px; background-color: #FFFFFF; border-radius: 50%; display: inline-block; transition: background-color 0.6s ease; }
        .active, .dot:hover { background-color: #717171; }
        .slideshow-container { width: 100%; height: 100%; position: absolute; top: 0; left: 0; overflow: hidden; }
        .inverse { position: relative; background-size: cover; height: 150px; overflow: hidden; }
        .inverse h1 { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 3; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7); color: white; font-size: 3em; text-align: center; width: 100%; }
        .banner_slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: none; }
        .banner_slide img { width: 100%; height: 100%; object-fit: cover; }
        .dots-container { position: absolute; bottom: 20px; left: 0; right: 0; text-align: center; z-index: 2; }
        .summary-table { display: none; margin: 20px auto; width: 80%; }
        .summary-table th, .summary-table td { padding: 10px; text-align: left; }
        .tooltip { display: none; position: absolute; background-color: #333; color: #fff; padding: 5px; border-radius: 3px; white-space: nowrap; z-index: 100; opacity: 0; transition: opacity 0.3s; font-size: 15px; }
        .school-icon:hover .tooltip { display: block; opacity: 1; }
    </style>
    <script>
        function toggleSummary() {
            var summaryTable = document.getElementById('summary-table');
            if (summaryTable.style.display === 'none' || summaryTable.style.display === '') {
                summaryTable.style.display = 'block';
            } else {
                summaryTable.style.display = 'none';
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const searchButton = document.getElementById('search-button');
            const sortIdButton = document.getElementById('sort-id-button');
            const showNrivaButton = document.getElementById('show-nriva-button');
            const showPgnfButton = document.getElementById('show-pgnf-button');
            function updateUrl(params) {
                const currentUrl = new URL(window.location.href);
                for (let [key, value] of Object.entries(params)) {
                    if (value === null) currentUrl.searchParams.delete(key);
                    else currentUrl.searchParams.set(key, value);
                }
                return currentUrl.toString();
            }
            searchButton.addEventListener('click', function() {
                window.location.href = 'schools.php?search=' + encodeURIComponent(searchInput.value);
            });
            searchInput.addEventListener('keydown', function(event) {
                if (event.keyCode === 13) searchButton.click();
            });
            sortIdButton.addEventListener('click', function() {
                const currentSort = new URLSearchParams(window.location.search).get('sort');
                window.location.href = updateUrl({
                    sort: currentSort === 'id_asc' ? 'id_desc' : 'id_asc'
                });
            });
            showNrivaButton.addEventListener('click', function() {
                window.location.href = updateUrl({
                    show_nriva: 'true', show_pgnf: null
                });
            });
            showPgnfButton.addEventListener('click', function() {
                window.location.href = updateUrl({
                    show_pgnf: 'true', show_nriva: null
                });
            });
            // Set button active classes
            const showNriva = new URLSearchParams(window.location.search).get('show_nriva');
            const showPgnf = new URLSearchParams(window.location.search).get('show_pgnf');
            showNrivaButton.classList.toggle('active', showNriva === 'true');
            showPgnfButton.classList.toggle('active', showPgnf === 'true');
        });
        // Slideshow controls
        let slideIndex = 1; showSlides(slideIndex);
        function plusSlides(n) { showSlides(slideIndex += n); }
        function currentSlide(n) { showSlides(slideIndex = n); }
        function showSlides(n) {
            let i;
            let slides = document.getElementsByClassName("banner_slide");
            let dots = document.getElementsByClassName("dot");
            if (n > slides.length) slideIndex = 1
            if (n < 1) slideIndex = slides.length
            for (i = 0; i < slides.length; i++) slides[i].style.display = "none";
            for (i = 0; i < dots.length; i++) dots[i].className = dots[i].className.replace(" active", "");
            slides[slideIndex - 1].style.display = "block";
            dots[slideIndex - 1].className += " active";
        }
    </script>
</head>
<body>
<?php include 'show-navbar.php'; show_navbar(); ?>
<header class="inverse">
    <div class="slideshow-container">
        <?php
        $images_dir = "./images/banner_images/Schools/";
        $images = glob($images_dir . "*.{jpg,png}", GLOB_BRACE);

        if (empty($images)) {
            $default_image = "./images/admin_icons/school.png";
            if (file_exists($default_image)) {
                $images = [$default_image];
            } else {
                $images = ["https://via.placeholder.com/800x400.png?text=No+Image+Available"];
            }
        }

        foreach ($images as $index => $image) {
            $safe_image_path = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
            echo "<div class='banner_slide'>
                <img src='{$safe_image_path}' alt='School banner image'>
                </div>";
        }
        ?>
    </div>
    <h1><span class="accent-text">Schools</span></h1>
    <div class="dots-container">
        <?php
        foreach ($images as $index => $image) {
            $slide_number = $index + 1;
            echo "<span class='dot' onclick='currentSlide($slide_number)'></span>";
        }
        ?>
    </div>
</header>
<div class="search-container">
    <input type="search" id="search-input" class="search-input" placeholder="Search by name or ID">
    <button id="search-button" class="search-button">Search</button>
    <button id="sort-id-button" class="search-button">Sort by ID</button>
    <button id="show-nriva-button" class="search-button">Show NRIVA Schools</button>
    <button id="show-pgnf-button" class="search-button">Show PGNF Schools</button>
    <button onclick="window.location.href='school_location.php'">Google Pins</button>
    <button onclick="toggleSummary()">Summary</button>
</div>
<div id="summary-table" class="summary-table">
    <h2>Summary by State</h2>
    <table border="1">
        <tr>
            <th>State</th>
            <th>Count</th>
        </tr>
        <?php foreach ($summary_by_state as $state => $count) : ?>
            <tr>
                <td><?php echo htmlspecialchars($state ?? ''); ?></td>
                <td><?php echo htmlspecialchars($count ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <h2>Summary by Type of School</h2>
    <table border="1">
        <tr>
            <th>Type</th>
            <th>Count</th>
        </tr>
        <?php foreach ($summary_by_sponsor as $sponsor => $count) : ?>
            <tr>
                <td><?php echo htmlspecialchars($sponsor ?? ''); ?></td>
                <td><?php echo htmlspecialchars($count ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <h2>Summary by Supported By</h2>
    <table border="1">
        <tr>
            <th>Supported By</th>
            <th>Count</th>
        </tr>
        <?php foreach ($summary_by_supported_by as $support => $count) : ?>
            <tr>
                <td><?php echo htmlspecialchars($support ?? ''); ?></td>
                <td><?php echo htmlspecialchars($count ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<div style="padding-top: 10px; padding-bottom: 30px; width:90%; margin:auto; overflow:auto">
    <table id="school_icons">
        <?php
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'id_desc';
        $show_nriva = isset($_GET['show_nriva']) ? $_GET['show_nriva'] : '';
        $show_pgnf = isset($_GET['show_pgnf']) ? $_GET['show_pgnf'] : '';
        $sql = "SELECT id, name, type, category, state_name, state_code, address_text, supported_by, contact_name FROM schools WHERE 1=1";
        if ($search) {
            $escaped = $connection->real_escape_string($search);
            $sql .= " AND (id LIKE '%$escaped%' OR name LIKE '%$escaped%' OR type LIKE '%$escaped%' OR state_name LIKE '%$escaped%' OR state_code LIKE '%$escaped%' OR address_text LIKE '%$escaped%' OR category LIKE '%$escaped%')";
        }
        if ($show_nriva === 'true') {
            $sql .= " AND supported_by = 'NRIVA'";
        } elseif ($show_pgnf === 'true') {
            $sql .= " AND supported_by = 'PGNF'";
        }
        switch ($sort_order) {
            case 'id_asc':
                $sql .= " ORDER BY id ASC";
                break;
            case 'id_desc':
                $sql .= " ORDER BY id DESC";
                break;
            default:
                $sql .= " ORDER BY id DESC";
        }
        $result = mysqli_query($connection, $sql);
        if ($result && $result->num_rows > 0) {
            $counter = 0;
            while ($row = $result->fetch_assoc()) {
                $time = time();
                $counter++;
                if ($counter % 5 === 1) {
                    echo "<tr>";
                }

                $reg_id = htmlspecialchars($row["id"] ?? '');
                $name = htmlspecialchars($row["name"] ?? '');
                $type = htmlspecialchars($row["type"] ?? '');
                $address = htmlspecialchars($row["address_text"] ?? '');
                $state = htmlspecialchars($row["state_name"] ?? '');
                $state_code = htmlspecialchars($row["state_code"] ?? '');
                $category = htmlspecialchars($row["category"] ?? '');
                $supported_by = htmlspecialchars($row["supported_by"] ?? '');
                $contact_name = !empty($row["contact_name"]) ? htmlspecialchars($row["contact_name"]) : 'Not Available';

                $profile_image = get_profile_image($reg_id);

                echo "<td class=\"school-icon\">
                        <a href=\"school_details.php?id=$reg_id\">
                            <img src=\"$profile_image?v=$time\" alt=\"school image\"><br>
                            <div class=\"school-info\">
                                <p>$reg_id</p>
                                <p>$name</p>
                            </div>
                            <div class=\"tooltip\">Contact: $contact_name</div>
                        </a>
                    </td>";
                if ($counter % 5 == 0) {
                    echo "</tr>";
                }
            }
            if ($counter % 5 !== 0) {
                echo "</tr>";
            }
        }
        ?>
    </table>
</div>
</body>
</html>
