<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$executionStartTime = microtime(true);

// Function to handle errors
function handleError($code, $name, $description, $additionalData = null) {
    global $executionStartTime;
    http_response_code($code);
    $output['status']['code'] = (string)$code;
    $output['status']['name'] = $name;
    $output['status']['description'] = $description;
    $output['status']['returnedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = $additionalData;
    echo json_encode($output);
    exit();
}

// Check if 'country' parameter is provided
if (!isset($_REQUEST['country']) || empty(trim($_REQUEST['country']))) {
    handleError(400, "Bad Request", "Country parameter is missing or empty");
}

$country = $_REQUEST['country'];

// Construct the URL for the Nominatim API request
$url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($country);

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, $url);

// Set custom user agent string
curl_setopt($ch, CURLOPT_USERAGENT, 'YourAppName/1.0 (your_email@example.com)');

// Execute cURL request
$result = curl_exec($ch);

// Check for cURL errors
if ($result === false) {
    handleError(500, "Internal Server Error", "cURL Error: " . curl_error($ch));
}

curl_close($ch);



// Decode the JSON response from Nominatim API
$response = json_decode($result, true);

// Check for JSON decode errors
if (json_last_error() !== JSON_ERROR_NONE) {
    handleError(500, "Internal Server Error", "JSON Decode Error: " . json_last_error_msg(), ['raw_response' => $result]);
}

// Check if the response is valid and has at least one result
if (!empty($response) && isset($response[0])) {
    // Extract latitude and longitude from the first result
    $latitude = $response[0]['lat'] ?? null;
    $longitude = $response[0]['lon'] ?? null;

    if ($latitude && $longitude) {
        // Construct the response data
        $output['status']['code'] = "200";
        $output['status']['name'] = "OK";
        $output['status']['description'] = "Coordinates found for $country";
        $output['status']['returnedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
        $output['data']['latitude'] = $latitude;
        $output['data']['longitude'] = $longitude;
    } else {
        // Handle the case where no location is found for the specified query
        handleError(404, "Not Found", "No location found for $country");
    }
} else {
    // Handle the case where the response is empty or invalid
    handleError(404, "Not Found", "No location found for $country");
}

// Output the JSON response
echo json_encode($output);