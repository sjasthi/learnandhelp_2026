<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to your database
include 'db_configuration.php';
$conn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

// Query to fetch all schools
$query = "SELECT * FROM schools";
$result = $conn->query($query);

// Create an HTML output
$html = '<html>
<head>
    <title>School Details</title>
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
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .header {
            text-align: center;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            flex-wrap: wrap;
            width: 100%;
        }
        img {
            width: 100px;
            height: 100px;
        }
        h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .info {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .column {
            flex: 1;
            text-align: left;
            margin: 4px 10px;
            max-width: 50%;
        }
        .id {
            font-size: 2rem;
            margin: 0;
        }
    </style>
</head>
<body>';

// Loop through the results and generate the HTML content
while ($row = $result->fetch_assoc()) {
    $supported_by = $row['supported_by'] ?? '';
    $html .= '<div class="container">';
    $html .= '<div class="header">';
    $html .= '<img src="images/learn_n_help_logo.png" alt="logo">';
    $html .= '<div>';
    $html .= '<img src="images/admin_icons/school.png" alt="logo">';
    $html .= '<h2>' . htmlspecialchars($row['name'] ?? '') . '</h2>';
    $html .= '</div>';
    $html .= '<p class="id">' . htmlspecialchars($row['Reg_Id'] ?? '') . '</p>'; // FIXED LINE
    $html .= '</div>';
    $html .= '<div class="info">';
    $html .= '<div class="column">';
    $html .= '<p>Type: ' . htmlspecialchars($row['type'] ?? '') . '</p>';
    $html .= '<p>Category: ' . htmlspecialchars($row['category'] ?? '') . '</p>';
    $html .= '<p>Grade Level: ' . htmlspecialchars($row['grade_level_start'] ?? '') . ' - ' . htmlspecialchars($row['grade_level_end'] ?? '') . '</p>';
    $html .= '<p>Current Enrollment: ' . htmlspecialchars($row['current_enrollment'] ?? '') . '</p>';
    $html .= '<p>Address: ' . htmlspecialchars($row['address_text'] ?? '') . '</p>';
    $html .= '</div>';
    $html .= '<div class="column">';
    $html .= '<p>State: ' . htmlspecialchars($row['state_name'] ?? '') . ' (' . htmlspecialchars($row['state_code'] ?? '') . ')</p>';
    $html .= '<p>Pin Code: ' . htmlspecialchars($row['pin_code'] ?? '') . '</p>';
    $html .= '<p>Contact Name: ' . htmlspecialchars($row['contact_name'] ?? '') . '</p>';
    $html .= '<p>Contact Designation: ' . htmlspecialchars($row['contact_designation'] ?? '') . '</p>';
    $html .= '<p>Contact Phone: ' . htmlspecialchars($row['contact_phone'] ?? '') . '</p>';
    $html .= '<p>Contact Email: ' . htmlspecialchars($row['contact_email'] ?? '') . '</p>';
    $html .= '<p>Status: ' . htmlspecialchars($row['status'] ?? '') . '</p>';
    $html .= '<p>Notes: ' . htmlspecialchars($row['notes'] ?? '') . '</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="column">';
    $html .= '<p>Referenced By: ' . htmlspecialchars($row['referenced_by'] ?? '') . '</p>';
    $html .= '<p>Supported By: </p>';
    // supported_by company image logo
    if (strtolower($supported_by) == 'learn and help') {
        $html .= '<img src="images/supported_by/learn and help.png" alt="logo">';
    } else {
        $html .= '<img src="images/supported_by/' . htmlspecialchars($supported_by) . '.png" alt="' . htmlspecialchars($supported_by) . ' logo">';
    }
    $html .= '</div>';
    $html .= '</div>';
    // Page break before the next school
    $html .= '<div style="page-break-before: always;"></div>';
}

$html .= '</body>
</html>';

// Close the database connection
$conn->close();

// Create an HTML file
file_put_contents('schools_details.html', $html);

header("Location: schools_details.html");

echo 'School details HTML file generated successfully.';
?>
