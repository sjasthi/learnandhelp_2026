<?php
$api_url = 'https://favqs.com/api/qotd';

// Initialize cURL session
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute cURL request
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    echo 'cURL Error:' . curl_error($ch);
    exit;
}

// Close cURL session
curl_close($ch);

// Decode JSON response
$data = json_decode($response, true);

// Check if quote is available
if (isset($data['quote']['body']) && isset($data['quote']['author'])) {
    $quote = $data['quote']['body'];
    $author = $data['quote']['author'];
    echo "$quote - $author";
} else {
    echo 'No quote found. Response: ' . $response;
}
?>
