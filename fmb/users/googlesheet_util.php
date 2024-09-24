<?php
include_once __DIR__ . '/../vendor/autoload.php';

function create_receipt_in_sheet($receipt_number, $thali, $amount, $payment_type, $transactionId)
{
    // Get the API client and construct the service object.
    $client = new Google\Client();
    $client->useApplicationDefaultCredentials();
    $client->addScope(Google\Service\Sheets::SPREADSHEETS);
    $client->setAuthConfig('google_credentails.json');
    $service = new Google\Service\Sheets($client);

    // test spreadsheet id, change to production when complete
    $spreadsheetId = '1M6XKavMwGhFQvckdayOpjizVT8LkxyqFF5zErDUo4lI';
    $range = 'Inward Transactions FMB';
    $date = date("d-M-Y");

    $values = [
        [$receipt_number, $thali, "Faizul Mawaidil Burhaniyah", $date, $amount, $payment_type, $transactionId, "", "", $date, "kalimiwebsite@faiz-kalimi.iam.gserviceaccount.com"]
    ];

    $body = new Google\Service\Sheets\ValueRange([
        'values' => $values
    ]);
    $params = [
        'valueInputOption' => "USER_ENTERED"
    ];
    $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
}
