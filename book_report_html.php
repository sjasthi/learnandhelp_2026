<?php
// Print error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
include 'db_configuration.php';
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Query to fetch all books
$query = "SELECT * FROM books where callNumber < 10";
$result = $conn->query($query);

// Create an HTML output
$html = '<html>
<head>
    <meta charset="UTF-8">
    <title>Book Details</title>
    <style>
        @page {
            size: A4;
            margin: 20px;
        }
        body {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            margin: 20px;
            page-break-before: always;
            font-family: Arial, sans-serif;
        }
        .container {
            width: 600px;
            height: fit-content;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 10px;
            padding: 30px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .header {
            text-align: center;
        }
        img {
            width: 200px;
            height: 300px;
        }
        h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .info {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
        }
        .column {
            flex: 1;
            text-align: left;
            margin: 4px 10px;
        }
    </style>
</head>
<body>';

// Loop through the results and generate the HTML content for books
while ($row = $result->fetch_assoc()) {

    $html .= '<div class="container">';
    $html .= '<div class="header">';
    $html .= '<img src="images/books/default.png" alt="book image">';

    $html .= '<h2>' . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') .' &#169; </h2>'; // HTML entity encode the title

    $html .= '</div>';
    $html .= '<div class="info">';
    $html .= '<div class="column">';

    $html .= '<p>Author: ' . htmlspecialchars($row['author'], ENT_QUOTES, 'UTF-8') .' &#169; </p>'; // HTML entity encode author
    $html .= '<p>Publisher: ' . htmlspecialchars($row['publisher'], ENT_QUOTES, 'UTF-8') . '</p>'; // HTML entity encode publisher
    $html .= '<p>Publish Year: ' . htmlspecialchars($row['publishYear'], ENT_QUOTES, 'UTF-8') . '</p>'; // HTML entity encode publishYear



    $html .= '<p>Number of Pages: ' . $row['numPages'] . '</p>';
    $html .= '</div>';
    $html .= '<div class="column">';
    $html .= '<p>Price: ' . $row['price'] . '</p>';


    $html .= '<p>Grade Level: ' . $row['grade_level'] . '</p>';
    $html .= '<p>Availability: ' . $row['available'] . '</p>';
    $html .= '<p>Date Created: ' . $row['date_created'] . '</p>';
     
    $html .= '<p>Publisher: ' . htmlspecialchars($row['publisher'], ENT_QUOTES, 'UTF-8') . ' &#169;</p>'; // HTML entity encode publisher
    $html .= '<p>Publish Year: ' . htmlspecialchars($row['publishYear'], ENT_QUOTES, 'UTF-8') . '</p>'; // HTML entity encode publishYear


$html .= '<p>Number of Pages: ' . $row['numPages'] . '</p>';
$html .= '<p>Price: ' . $row['price'] . ' </p>';
    



    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    // Page break before the next book
    $html .= '<div style="page-break-before: always;"></div>';
}

$html .= '</body>
</html>';

// Close the database connection
$conn->close();

// Create an HTML file for books
file_put_contents('books_details.html', $html);

header("Location: books_details.html");

echo 'Book details HTML file generated successfully.';
?>
