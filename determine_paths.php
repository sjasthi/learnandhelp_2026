<?php
//Main place to set paths for local or remote deployment.

// Determine if running on localhost or remote server
$host = $_SERVER['HTTP_HOST'];
$local = ($host === 'localhost');


if ($local) {
    $baseUrl = 'http://localhost/learnandhelp/';   //sets base url if using href.
    $path = $_SERVER["DOCUMENT_ROOT"] . "/learnandhelp/";  //Sets local path if using folder structure (Option)

} else {
    // Modify the remote base URL as per your server configuration
    $baseUrl = 'https://learnandhelp.jasthi.com/'; //sets base url if using href.
    $path = $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/";  //Sets remote path if using folder structure (Option)
}
