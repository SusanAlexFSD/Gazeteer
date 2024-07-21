<?php
// Set the content type to HTML for displaying error messages
header('Content-Type: text/html');

// Function to report error with consistent structure and HTTP status code
function reportError($message, $statusCode = 500) {
    http_response_code($statusCode);
    echo '<div class="error">' . htmlspecialchars($message) . '</div>';
    exit();
}

// Check if the country parameter is provided in the GET request
if (isset($_GET['country'])) {
    $selectedCountry = trim($_GET['country']);

    // Validate the country parameter
    if (empty($selectedCountry)) {
        reportError("Error: Country parameter is provided but empty.", 400);
    }

    // Replace spaces with no space or with a hyphen/underscore
    $selectedCountry = str_replace(' ', '', $selectedCountry);

    $apiUrl = "http://api.geonames.org/wikipediaSearchJSON?formatted=true&q=" . urlencode($selectedCountry) . "&maxRows=10&username=susan.alexander&style=full";

    // Fetch data from GeoNames API
    $wikipediaData = @file_get_contents($apiUrl);

    // Check if data is retrieved successfully
    if ($wikipediaData === false) {
        $error = error_get_last();
        reportError("Error: Unable to fetch data from GeoNames API. " . $error['message'], 500);
    }

    // Parse the JSON response
    $responseData = json_decode($wikipediaData, true);

    // Check if JSON decoding succeeded
    if (json_last_error() !== JSON_ERROR_NONE) {
        reportError("Error: Failed to decode JSON response. Error: " . json_last_error_msg(), 500);
    }

    // Extract the Wikipedia content
    if (isset($responseData['geonames']) && count($responseData['geonames']) > 0) {
        $content = $responseData['geonames'][0]['summary'];
        $thumbnailImg = isset($responseData['geonames'][0]['thumbnailImg']) ? $responseData['geonames'][0]['thumbnailImg'] : null;
        $wikipediaUrl = $responseData['geonames'][0]['wikipediaUrl'];

        // Check if the Wikipedia URL is complete, if not, prepend the protocol
        if (!parse_url($wikipediaUrl, PHP_URL_SCHEME)) {
            $wikipediaUrl = 'http://' . $wikipediaUrl;
        }

        // Output the content, thumbnail image, and Wikipedia URL
        $output = '<div id="wikipediaContent">';
        $output .= '<div id="wikipediaSummary">' . htmlspecialchars($content) . '</div>';

        // Check if thumbnail image exists before trying to display it
        if ($thumbnailImg) {
            $output .= '<div id="thumbnail"><img src="' . htmlspecialchars($thumbnailImg) . '" alt="Thumbnail"></div>';
        }

        $output .= '<div id="readMore"><a href="' . htmlspecialchars($wikipediaUrl) . '" target="_blank">Read More on Wikipedia</a></div>';
        $output .= '</div>';

        echo $output;

    } else {
        // Output error message if no Wikipedia content found
        reportError("Error: No Wikipedia content found for the specified country.", 404);
    }
} else {
    // Output error message if country parameter is not provided
    reportError("Error: Country parameter is missing in the request.", 400);
}
