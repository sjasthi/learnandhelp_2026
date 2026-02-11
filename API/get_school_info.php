<?php

// print error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define the database connection parameters
// $hostname = 'localhost';
// $username = 'root';
// $password = '';
// $database = 'learn_and_help_db';

// Initialize the response array
$response = array();

// Check if the 'id' parameter is provided in the request
if (isset($_GET['id'])) {
    // Get the school ID from the request
    $schoolId = $_GET['id'];


    //Establish connection to database
    $connection = require_once __DIR__ . "/../database_connection.php";


    // Create a new MySQLi object for the database connection
    // $connection = new mysqli($hostname, $username, $password, $database);

    // Check for a successful database connection
    if ($connection->connect_error) {
        $response['response_code'] = 500;
        $response['message'] = "Failed to connect to the database: " . $connection->connect_error;
    } else {
        // Prepare an SQL statement to retrieve school information by ID
        $sql = "SELECT * FROM schools WHERE id = ?";

        // Use prepared statements to prevent SQL injection
        $stmt = $connection->prepare($sql);

        if ($stmt === false) {
            $response['response_code'] = 500;
            $response['message'] = "Failed to prepare the statement: " . $connection->error;
        } else {
            // Bind the parameter and execute the statement
            $stmt->bind_param("s", $schoolId);

            if ($stmt->execute()) {
                // Fetch the result
                $result = $stmt->get_result();

                // Check if a record was found
                if ($result->num_rows > 0) {
                    // Fetch the school data as an associative array
                    $schoolData = $result->fetch_assoc();
                    $response['response_code'] = 200;
                    $response['message'] = "School found";
                    $response['data'] = $schoolData;
                } else {
                    // School not found
                    $response['response_code'] = 400;
                    $response['message'] = "School not found";
                }
            } else {
                // Error executing the statement
                $response['response_code'] = 500;
                $response['message'] = "Database error";
            }

            // Close the database connection
            $stmt->close();
        }
        $connection->close();
    }
} else {
    // 'id' parameter is missing
    $response['response_code'] = 400;
    $response['message'] = "Missing 'id' parameter";
}

// Return the response as JSON
echo json_encode($response);
