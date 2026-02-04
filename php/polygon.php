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

// Validate the country code parameter
if (!isset($_GET['countryCode'])) {
    reportError('Country code parameter is missing.', 400);
}

$countryCode = $_GET['countryCode'];

// Basic validation for the country code format (2 uppercase letters)
if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
    reportError('Invalid country code format.', 400);
}

// GeoJSON file path
$geojsonFilePath = 'countryBorders.geo.json';

// Check if the GeoJSON file exists and is readable
if (!file_exists($geojsonFilePath)) {
    reportError('GeoJSON file does not exist.', 500);
}
if (!is_readable($geojsonFilePath)) {
    reportError('GeoJSON file is not readable.', 500);
}

// Read the GeoJSON file
$geojsonData = @file_get_contents($geojsonFilePath);

// Check if the GeoJSON file was read successfully
if ($geojsonData === false) {
    reportError('Failed to read GeoJSON file.', 500);
}

// Decode the GeoJSON data
$geojson = json_decode($geojsonData, true);

// Check if JSON decoding succeeded
if (json_last_error() !== JSON_ERROR_NONE) {
    reportError('JSON decode error: ' . json_last_error_msg(), 500);
}

// Check if the GeoJSON structure is valid
if (!isset($geojson['features']) || !is_array($geojson['features'])) {
    reportError('Invalid GeoJSON structure.', 500);
}

// Loop through the GeoJSON data to find the coordinates of the selected country
$found = false;
foreach ($geojson['features'] as $feature) {
    if (isset($feature['properties']['iso_a2']) && $feature['properties']['iso_a2'] === $countryCode) {
        if (isset($feature['geometry']['coordinates'])) {
            $coordinates = $feature['geometry']['coordinates'];
            // Return the coordinates as JSON
            echo json_encode([
                'status' => 'success',
                'coordinates' => $coordinates
            ]);
            $found = true;
            break;
        } else {
            reportError('Coordinates not found for the specified country code.', 404);
        }
    }
}

// If the country code is not found, return an error message
if (!$found) {
    reportError('Country code not found in the GeoJSON data.', 404);
}