<?php
// include your composer dependencies
require_once '../vendor/autoload.php';

$client = new Google\Client();
$client->setApplicationName("Client_Library_Examples");
$client->setDeveloperKey("AIzaSyDiQaDEwgVTUU2o59uLY2W4P-qk4AhFLA8");

$service = new Google\Service\Sheets($client);

// Prints the names and majors of students in a sample spreadsheet:
// https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit
$spreadsheetId = '1ZwHIA-fRmUmpE8OPG5_rOwEB4sPYqCb8NWQCJ5kU1Bc';
$range = 'Inward Transactions FMB';
$response = $service->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();

if (empty($values)) {
    print "No data found.\n";
} else {
    print "Name, Major:\n";
    foreach ($values as $row) {
        // Print columns A and E, which correspond to indices 0 and 4.
        printf("%s, %s\n", $row[0], $row[4]);
    }
}