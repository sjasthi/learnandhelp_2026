<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

//Pull in database credentials
require_once 'db_configuration.php';

$mysqli = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_DATABASE);

// $host = "localhost";
// $dbname = "learn_and_help_db";
// $username = "root";
// $password = "";

// $mysqli = new mysqli($host,$username,$password,$dbname);

if ($mysqli->connect_errno) {
    die("Connection error: " . $mysqli->connect_error);
}

return $mysqli;
