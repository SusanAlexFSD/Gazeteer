<?php

// Check if the necessary parameters are present
if (isset($_POST['amount'], $_POST['baseCode'], $_POST['targetCode'])) {
    // Get the parameters
    $amount = $_POST['amount'];
    $fromCurrency = $_POST['baseCode'];
    $toCurrency = $_POST['targetCode'];

    // Replace 'your_api_key' with your actual ExchangeRate-API key
    $apiKey = '3691d324884ed86cde1e1b66';

    // Construct the API URL with correct parameters and API key
    $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/pair/{$fromCurrency}/{$toCurrency}/{$amount}";

    // Attempt to fetch data from the API
    $response = file_get_contents($url);

    // Check if the request was successful
    if ($response !== false) {
        // Output the response
        echo $response;
    } else {
        // Handle the error
        $errorMessage = "Failed to fetch exchange rate data";
        echo json_encode(['result' => 'error', 'message' => $errorMessage]);
    }
} else {
    // Handle missing parameters
    $errorMessage = "Missing parameters: amount, baseCode, targetCode";
    echo json_encode(['result' => 'error', 'message' => $errorMessage]);
}