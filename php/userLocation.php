<?php
header('Content-Type: application/json');

// Function to log errors
function logError($message) {
    error_log($message);
}

// Function to report error with a consistent structure and HTTP status code
function reportError($message, $statusCode = 500) {
    logError($message);
    http_response_code($statusCode);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit();
}

// Check if latitude and longitude are provided
if (!isset($_GET['lat']) || !isset($_GET['lon'])) {
    reportError('Latitude or longitude is missing.', 400);
}

$latitude = $_GET['lat'];
$longitude = $_GET['lon'];

// Validate latitude and longitude
if (!is_numeric($latitude) || $latitude < -90 || $latitude > 90) {
    reportError('Invalid latitude value.', 400);
}
if (!is_numeric($longitude) || $longitude < -180 || $longitude > 180) {
    reportError('Invalid longitude value.', 400);
}

// GeoNames API URL
$url = 'http://api.geonames.org/countryCodeJSON?lat=' . urlencode($latitude) . '&lng=' . urlencode($longitude) . '&username=susan.alexander';

$response = @file_get_contents($url);

// Check if the response is valid
if ($response === false) {
    $error = error_get_last();
    reportError('Failed to fetch reverse geocoding data: ' . $error['message'], 500);
}

// Decode the response
$data = json_decode($response, true);

// Check if JSON decoding succeeded
if (json_last_error() !== JSON_ERROR_NONE) {
    reportError('JSON decode error: ' . json_last_error_msg(), 500);
}

// Check if country code is present
if (isset($data['countryCode'])) {
    echo json_encode([
        'status' => 'success',
        'countryCode' => $data['countryCode']
    ]);
} else {
    reportError('Unable to determine country code.', 404);
}
