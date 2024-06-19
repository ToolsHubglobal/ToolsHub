<?php
// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

function validateCreditCard($number) {
    // Remove any non-digits
    $number = preg_replace('/\D/', '', $number);

    // Check if the number is empty
    if (empty($number)) {
        return false;
    }

    // Luhn algorithm to validate the credit card number
    $sum = 0;
    $alt = false;

    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $n = $number[$i];

        if ($alt) {
            $n *= 2;
            if ($n > 9) {
                $n -= 9;
            }
        }

        $sum += $n;
        $alt = !$alt;
    }

    return ($sum % 10 == 0);
}

header('Content-Type: application/json');

$response = [
    'success' => 0,
    'message' => 'Invalid request'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cc'])) {
    $cards = explode("\n", trim($_POST['cc']));
    $results = [
        'live' => [],
        'die' => [],
        'unknown' => []
    ];

    foreach ($cards as $card) {
        $card = trim($card);

        // Validate the card format (should be in the form of number|MM|YYYY|CVV)
        $cardParts = explode('|', $card);

        if (count($cardParts) === 4) {
            $cardNumber = $cardParts[0];

            if (validateCreditCard($cardNumber)) {
                $results['live'][] = $card;
            } else {
                $results['die'][] = $card;
            }
        } else {
            $results['unknown'][] = $card;
        }
    }

    $response['success'] = 1;
    $response['live'] = count($results['live']);
    $response['die'] = count($results['die']);
    $response['unknown'] = count($results['unknown']);
    $response['message'] = 'Validation complete';
    $response['results'] = $results;
} else {
    $response['message'] = 'Invalid POST data or request method';
}

echo json_encode($response);
?>
