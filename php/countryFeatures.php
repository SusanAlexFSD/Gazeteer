<?php
// Function to log errors
function logError($message) {
    error_log($message);
}

// Path to the GeoJSON file
$geojsonFilePath = '../assets/countryBorders.geo.json';

// Initialize an array to store error messages
$errors = [];

// Check if the file exists
if (!file_exists($geojsonFilePath)) {
    $errors[] = 'GeoJSON file not found';
    logError('GeoJSON file not found: ' . $geojsonFilePath);
}

// Check if the file is readable
if (!is_readable($geojsonFilePath)) {
    $errors[] = 'GeoJSON file is not readable';
    logError('GeoJSON file is not readable: ' . $geojsonFilePath);
}

// Proceed only if there are no errors
if (empty($errors)) {
    // Try to read the GeoJSON file
    $geojson = @file_get_contents($geojsonFilePath);

    // Check if file_get_contents was successful
    if ($geojson === false) {
        $errors[] = 'Error reading GeoJSON file';
        logError('Error reading GeoJSON file: ' . error_get_last()['message']);
    } else {
        // Check if the content is valid JSON
        json_decode($geojson);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Invalid JSON format in GeoJSON file: ' . json_last_error_msg();
            logError('Invalid JSON format in GeoJSON file: ' . json_last_error_msg());
        }
    }
}

// If there are errors, return them as JSON
if (!empty($errors)) {
    header('Content-Type: application/json', true, 500);
    echo json_encode(['error' => $errors]);
} else {
    // Output the GeoJSON data with the correct Content-Type header
    header('Content-Type: application/json');
    echo $geojson;
}
