<?php
// PHP script to fetch currency codes from ExchangeRate API

// Replace 'YOUR_API_KEY' with your actual API key
$apiKey = '3691d324884ed86cde1e1b66';

// Set the API endpoint
$url = 'https://api.exchangerate-api.com/v4/latest/USD'; // Example URL, replace with your actual URL

// Set up HTTP headers
$options = array(
    'http' => array(
        'method' => 'GET',
        'header' => 'X-RapidAPI-Key: ' . $apiKey
    )
);

// Create a stream context
$context = stream_context_create($options);

// Make the request to ExchangeRate API
$response = file_get_contents($url, false, $context);

// Check if the request was successful
if ($response === false) {
    // Handle error if the request fails
    $error = error_get_last();
    echo json_encode(array('result' => 'error', 'message' => $error['message']));
} else {
    // Decode the response JSON
    $data = json_decode($response, true);

    // Extract base code and rates
    $baseCode = $data['base']; // Get the base currency code
    $rates = $data['rates']; // Get the rates for different currencies

    // Prepare currencies array with base code and rates
    $currencies = array();
    foreach ($rates as $code => $rate) {
        $currencies[$code] = $rate;
    }

    // Set target code and conversion rate (example values, adjust based on your requirements)
    $targetCode = 'GBP'; // Example target currency code
    $conversionRate = $rates[$targetCode]; // Example conversion rate

    // Return the currency options in JSON format
    echo json_encode(array(
        'result' => 'success',
        'baseCode' => $baseCode,
        'targetCode' => $targetCode,
        'conversionRate' => $conversionRate,
        'currencies' => $currencies
    ));
}