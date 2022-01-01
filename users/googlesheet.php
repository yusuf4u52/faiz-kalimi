<?php
include_once __DIR__ . '/../vendor/autoload.php';

function create_receipt_in_sheet($thali, $amount, $transactionId, $orderId)
{
    // Get the API client and construct the service object.
    $client = new Google\Client();
    $client->useApplicationDefaultCredentials();
    $client->addScope(Google\Service\Sheets::SPREADSHEETS);
    $client->setAuthConfig('credentials.json');
    $service = new Google\Service\Sheets($client);

    // test spreadsheet id, change to production when complete
    $spreadsheetId = '1b3iSKwPSJfhhIIENbH85W3WjDt18Ys22SPt5uXkxqkM';
    $range = 'Inward Transactions FMB';

    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
    $data = $response->getValues();

    $lastrow = end($data);
    $lastReceiptNumber = $lastrow[0];
    $receipt_number = explode("-", $lastReceiptNumber);
    $number = str_pad($receipt_number[1] + 1, 5, "0", STR_PAD_LEFT);

    $next_receipt_number =  "FMB-" . $number;
    $date = date("d-M-Y");

    $values = [
        [$next_receipt_number, $thali, "Faizul Mawaidil Burhaniyah", $date, $amount, "Online", $transactionId, "", "", $date, "kalimiwebsite@faiz-kalimi.iam.gserviceaccount.com", "", "", "", $orderId]
    ];

    $body = new Google\Service\Sheets\ValueRange([
        'values' => $values
    ]);
    $params = [
        'valueInputOption' => "RAW"
    ];
    $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
}
