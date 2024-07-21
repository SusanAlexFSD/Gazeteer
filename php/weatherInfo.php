<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$executionStartTime = microtime(true);

// Function to report error with a consistent structure
function reportError($code, $name, $description) {
    global $executionStartTime;
    $output['status']['code'] = $code;
    $output['status']['name'] = $name;
    $output['status']['description'] = $description;
    $output['status']['returnedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = null;
    echo json_encode($output);
    exit();
}

// Check if 'lat' and 'lon' parameters are provided
if (!isset($_REQUEST['lat']) || !isset($_REQUEST['lon'])) {
    reportError("400", "Bad Request", "Latitude and/or longitude parameters are missing");
}

$lat = $_REQUEST['lat'];
$lon = $_REQUEST['lon'];

// Validate latitude and longitude
if (!is_numeric($lat) || $lat < -90 || $lat > 90) {
    reportError("400", "Bad Request", "Invalid latitude value");
}
if (!is_numeric($lon) || $lon < -180 || $lon > 180) {
    reportError("400", "Bad Request", "Invalid longitude value");
}

// Your WeatherAPI key
$apiKey = "888e08976fc446e380d202926240806"; // Replace with your WeatherAPI key

$url = 'http://api.weatherapi.com/v1/forecast.json?key=' . $apiKey . '&q=' . $lat . ',' . $lon . '&days=3';

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, $url);

$result = curl_exec($ch);

if (curl_errno($ch)) {
    reportError("500", "Internal Server Error", "cURL error: " . curl_error($ch));
}

curl_close($ch);

$response = json_decode($result, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    reportError("500", "Internal Server Error", "JSON decode error: " . json_last_error_msg());
}

function roundArrayValues($array) {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $array[$key] = roundArrayValues($value);
        } elseif (is_numeric($value)) {
            $array[$key] = round($value);
        }
    }
    return $array;
}

if ($response && isset($response['forecast'])) {
    $response = roundArrayValues($response);

    $output['status']['code'] = "200";
    $output['status']['name'] = "OK";
    $output['status']['description'] = "Weather information retrieved successfully";
    $output['status']['returnedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = $response;
    echo json_encode($output);
} else {
    if (isset($response['error'])) {
        // Handle specific WeatherAPI error
        $errorCode = $response['error']['code'] ?? "Unknown";
        $errorMessage = $response['error']['message'] ?? "Unknown error occurred";
        reportError("500", "WeatherAPI Error", "WeatherAPI error ($errorCode): $errorMessage");
    } else {
        reportError("500", "Internal Server Error", "Error retrieving weather information from WeatherAPI");
    }
}
