<?php

// Enable error reporting
ini_set('display_errors', 'On');
error_reporting(E_ALL);

// Set headers for CORS and JSON content
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Start timer to measure execution time
$executionStartTime = microtime(true);

// Replace 'your_geonames_username' with your actual Geonames username
$username = 'susan.alexander';

// Check if 'countryCode' parameter is provided
if (!isset($_REQUEST['countryCode'])) {
    // Log the missing parameter
    error_log("Country code parameter is missing");
    
    // Handle the case where the parameter is missing
    $output['status']['code'] = "400";
    $output['status']['name'] = "Bad Request";
    $output['status']['description'] = "Country code parameter is missing";
    $output['status']['returnedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = null;
    echo json_encode($output);
    exit();
}

// Get the country code from the request
$countryCode = $_REQUEST['countryCode'];

// Construct the URL for the GeoNames API
$countryInfoUrl = 'http://api.geonames.org/countryInfoJSON?country=' . $countryCode . '&username=' . $username;

// Initialize cURL session
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, $countryInfoUrl);

// Execute the cURL request
$result = curl_exec($ch);

// Check for errors during the cURL request
if ($result === false) {
    // Log the cURL error
    $error = curl_error($ch);
    error_log("cURL Error: " . $error);
    
    // Return an error response
    $output['status']['code'] = "500";
    $output['status']['name'] = "Internal Server Error";
    $output['status']['description'] = "Error retrieving data from GeoNames API";
    $output['status']['returnedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = null;
    echo json_encode($output);
    exit();
}

// Close the cURL session
curl_close($ch);

// Decode the JSON response from GeoNames API
$response = json_decode($result, true);

// Check if the response is valid
if ($response && isset($response['geonames'][0])) {
    // Extract country data
    $countryData = $response['geonames'][0];

    // Fetch the capital city's coordinates
    $capital = $countryData['capital'];
    $capitalUrl = "http://api.geonames.org/searchJSON?q=$capital&country=$countryCode&maxRows=1&username=$username";
    
    // Initialize cURL session for capital city coordinates
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $capitalUrl);
    
    // Execute the cURL request for capital city coordinates
    $capitalResult = curl_exec($ch);
    
    // Check for errors during the cURL request
    if ($capitalResult === false) {
        // Log the cURL error
        $error = curl_error($ch);
        error_log("cURL Error: " . $error);
        
        // Return an error response
        $output['status']['code'] = "500";
        $output['status']['name'] = "Internal Server Error";
        $output['status']['description'] = "Error retrieving capital city coordinates from GeoNames API";
        $output['status']['returnedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
        $output['data'] = null;
        echo json_encode($output);
        exit();
    }
    
    // Close the cURL session
    curl_close($ch);

    // Decode the JSON response for capital city coordinates
    $capitalResponse = json_decode($capitalResult, true);

    // Check if the capital city coordinates response is valid
    if ($capitalResponse && isset($capitalResponse['geonames'][0])) {
        $capitalCoords = $capitalResponse['geonames'][0];
        $countryData['capitalLatitude'] = $capitalCoords['lat'];
        $countryData['capitalLongitude'] = $capitalCoords['lng'];
    } else {
        error_log("Invalid response from GeoNames API for capital city coordinates: " . $capitalResult);
        $countryData['capitalLatitude'] = null;
        $countryData['capitalLongitude'] = null;
    }
    
    // Format the population
    if (isset($countryData['population'])) {
        $countryData['population'] = number_format($countryData['population']);
    }
    
    // Structure the output as expected by the JavaScript code
    $output['status']['code'] = "200";
    $output['status']['name'] = "OK";
    $output['status']['description'] = "Country information retrieved successfully";
    $output['status']['returnedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = ['geonames' => [$countryData]];
    echo json_encode($output);
} else {
    // Log the invalid response
    error_log("Invalid response from GeoNames API: " . $result);
    
    // Handle the case where the response is invalid or missing expected data
    $output['status']['code'] = "500";
    $output['status']['name'] = "Internal Server Error";
    $output['status']['description'] = "Error retrieving country information from GeoNames API";
    $output['status']['returnedIn'] = intval((microtime(true) - $executionStartTime) * 1000) . " ms";
    $output['data'] = null;
    echo json_encode($output);
}

// Log the execution time
$executionEndTime = microtime(true);
$executionTime = $executionEndTime - $executionStartTime;
error_log("Execution time: " . $executionTime . " seconds");