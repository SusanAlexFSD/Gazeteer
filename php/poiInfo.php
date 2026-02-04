<?php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Function to log errors
function logError($message) {
    error_log($message);
}

// Function to report error with a consistent structure
function reportError($message, $code = 500) {
    logError($message);
    echo json_encode([
        'status' => 'error',
        'code' => $code,
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

// GeoNames API endpoint for POI searches
$apiEndpoint = 'http://api.geonames.org/searchJSON';
// GeoNames API endpoint for country information
$countryEndpoint = 'http://api.geonames.org/countryInfoJSON';

// Parameters for the API request for country information
$paramsCountry = array(
    'country' => $countryCode,
    'username' => 'susan.alexander'
);

// Function to make API request and return response
function makeAPIRequest($endpoint, $params, $retryCount = 3) {
    $queryString = http_build_query($params);
    $requestUrl = $endpoint . '?' . $queryString;
    
    while ($retryCount > 0) {
        // Make the API request
        $response = @file_get_contents($requestUrl);
        
        // Check if the response is valid
        if ($response !== false) {
            // Decode the response
            $data = json_decode($response, true);
            
            // Check if JSON decoding succeeded
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            } else {
                logError('JSON decode error: ' . json_last_error_msg());
            }
        } else {
            logError('Failed to fetch data from ' . $endpoint);
        }
        
        $retryCount--;
        if ($retryCount > 0) {
            sleep(1); // Wait before retrying
        }
    }
    
    reportError('Failed to fetch data from ' . $endpoint . ' after multiple attempts.', 500);
}

// Make API request for country information
$countryInfo = makeAPIRequest($countryEndpoint, $paramsCountry);

// Check if the country information was retrieved successfully
if (isset($countryInfo['geonames'][0])) {
    $countryBounds = array(
        floatval($countryInfo['geonames'][0]['south']),
        floatval($countryInfo['geonames'][0]['west']),
        floatval($countryInfo['geonames'][0]['north']),
        floatval($countryInfo['geonames'][0]['east'])
    );
} else {
    reportError('Country information not found for the specified country code.', 404);
}

// Parameters for the API request for airports
$paramsAirport = array(
    'formatted' => true,
    'q' => 'airport',
    'country' => $countryCode,
    'maxRows' => 100,
    'lang' => 'en',
    'username' => 'susan.alexander',
    'style' => 'full'
);

// Parameters for the API request for restaurants
$paramsRestaurant = array(
    'formatted' => true,
    'q' => 'restaurant',
    'country' => $countryCode,
    'maxRows' => 100,
    'lang' => 'en',
    'username' => 'susan.alexander',
    'style' => 'full'
);

// Parameters for the API request for museums
$paramsMuseum = array(
    'formatted' => true,
    'q' => 'museum',
    'country' => $countryCode,
    'maxRows' => 100,
    'lang' => 'en',
    'username' => 'susan.alexander',
    'style' => 'full'
);

// Make API requests for airports, restaurants, and museums
$airportData = makeAPIRequest($apiEndpoint, $paramsAirport);
$restaurantData = makeAPIRequest($apiEndpoint, $paramsRestaurant);
$museumData = makeAPIRequest($apiEndpoint, $paramsMuseum);

// Validate API responses
if (!isset($airportData['geonames']) || !is_array($airportData['geonames'])) {
    reportError('Unexpected response structure for airport data.', 500);
}

if (!isset($restaurantData['geonames']) || !is_array($restaurantData['geonames'])) {
    reportError('Unexpected response structure for restaurant data.', 500);
}

if (!isset($museumData['geonames']) || !is_array($museumData['geonames'])) {
    reportError('Unexpected response structure for museum data.', 500);
}

// Combine responses into a single JSON object
$responseData = array(
    'status' => 'success',
    'countryBounds' => $countryBounds,
    'airport' => $airportData,
    'restaurant' => $restaurantData,
    'museum' => $museumData
);

// Return the combined response data
echo json_encode($responseData);