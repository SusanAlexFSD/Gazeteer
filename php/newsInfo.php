<?php
// Enable error reporting
ini_set('display_errors', 'On');
error_reporting(E_ALL);

// Set headers for CORS and JSON content
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Function to report error with a consistent structure
function reportError($message, $code = 500) {
    echo json_encode([
        'status' => 'error',
        'code' => $code,
        'message' => $message
    ]);
    exit();
}

// Check if it's an AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get the country code from the query parameters
    if (!isset($_GET['countryCode']) || empty($_GET['countryCode'])) {
        reportError('Country code parameter is missing or empty.', 400);
    }

    $countryCode = $_GET['countryCode'];

    // Validate the country code format
    if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
        reportError('Invalid country code format.', 400);
    }

    // News API endpoint
    $url = "https://newsdata.io/api/1/news";

    // Replace 'your_api_key_here' with your actual API key
    $api_key = 'pub_429015ca95c6734ed6744517e7a5585870dc5';

    // Construct query parameters
    $query_params = http_build_query([
        'apikey' => $api_key, // Set the API key in the query parameters
        'country' => $countryCode // Add the country code to the query parameters
    ]);

    // Construct final URL with query parameters
    $final_url = $url . '?' . $query_params;

    // Initialize cURL session
    $curl = curl_init();

    // Set cURL options
    curl_setopt($curl, CURLOPT_URL, $final_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    // Execute cURL request
    $response = curl_exec($curl);

    // Check for cURL errors
    if ($response === false) {
        $curlError = curl_error($curl);
        curl_close($curl);
        reportError("Failed to fetch news: $curlError", 500);
    }

    // Close cURL session
    curl_close($curl);

    // Log the response data for debugging
    error_log("Response Data: " . $response);

    // Parse the JSON response
    $newsData = json_decode($response, true);

    // Check for JSON decoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        $jsonError = json_last_error_msg();
        reportError("JSON decode error: $jsonError", 500);
    }

    // Log the parsed data for debugging
    error_log("News Data: " . print_r($newsData, true));

    // Initialize an empty array to store articles
    $articles = [];

    // Check if the response contains the expected structure
    if (isset($newsData['status']) && $newsData['status'] === 'success' && isset($newsData['results']) && is_array($newsData['results'])) {
        $articles = $newsData['results'];
        foreach ($articles as $article) {
            // Check if 'url' key exists in the current article
            if (isset($article['url'])) {
                // Access the 'url' value and log it for debugging
                $articleUrl = $article['url'];
                error_log("Article URL: " . $articleUrl);
            } else {
                // Handle the case where 'url' key is not set
                error_log("Warning: Undefined array key 'url'");
            }
        }

        // Return only the 'results' portion of the response
        echo json_encode(['status' => 'success', 'results' => $articles]);
    } else {
        // If the response structure is unexpected, return an error
        reportError('Unexpected response structure', 500);
    }
} else {
    // If it's not a valid AJAX request, return an error
    reportError('Invalid request method. Only GET requests are allowed.', 405);
}