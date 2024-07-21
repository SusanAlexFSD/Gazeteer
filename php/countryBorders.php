<?php
// Function to handle errors
function handleError($code, $message, $details = '') {
    http_response_code($code);
    header('Content-Type: application/json');
    // Log error details
    error_log("Error [$code]: $message. Details: $details");
    echo json_encode(['error' => $message]);
    exit();
}

// Path to the GeoJSON file
$geojsonFilePath = '../assets/countryBorders.geo.json';

// Check if the file exists
if (!file_exists($geojsonFilePath)) {
    handleError(404, 'GeoJSON file not found');
}

// Check if the file is readable
if (!is_readable($geojsonFilePath)) {
    handleError(403, 'GeoJSON file is not readable');
}

// Attempt to read the GeoJSON file
$geojson = @file_get_contents($geojsonFilePath);

// Check if the file_get_contents call was successful
if ($geojson === false) {
    handleError(500, 'Failed to read GeoJSON file', error_get_last()['message'] ?? 'Unknown error');
}

// Validate the GeoJSON content
$jsonData = json_decode($geojson);
if (json_last_error() !== JSON_ERROR_NONE) {
    handleError(500, 'Invalid GeoJSON data', json_last_error_msg());
}

// Output the GeoJSON data with the correct Content-Type header
header('Content-Type: application/json');
echo json_encode($jsonData);
