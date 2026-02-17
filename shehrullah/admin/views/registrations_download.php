<?php

DEFINE('NO_TEMPLATE', true);

if( !is_super_admin() ) {
    do_redirect_with_message('/home', 'Unauthorized.');
}

// Load composer autoload for PhpSpreadsheet (vendor is in fmb/vendor)
require_once __DIR__ . '/../../fmb/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$hirji_year = get_current_hijri_year();
$registration_data = get_registration_data($hirji_year);
$registration_summary = get_registration_summary($hirji_year);

// Calculate pending amount for each record
foreach ($registration_data as &$record) {
    $record->pending = $record->takhmeen - $record->paid_amount;
}
unset($record); // Break the reference

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Registrations');

// Define column headers
$headers = [
    'Sr#',
    'HOF ID',
    'Name',
    'WhatsApp',
    'Male (>11)',
    'Female (>11)',
    'Kids (5~11)',
    'Infant (<5)',
    'Chairs',
    'Pirsa',
    'Zabihat',
    'Takhmeen',
    'Paid',
    'Pending'
];

// Set headers
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

// Style header row
$headerRange = 'A1:' . $col . '1';
$sheet->getStyle($headerRange)->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4']
    ],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
]);

// Add data rows
$row = 2;
$sr = 1;
foreach ($registration_data as $record) {
    $sheet->setCellValue('A' . $row, $sr++);
    $sheet->setCellValue('B' . $row, $record->hof_id ?? '');
    $sheet->setCellValue('C' . $row, $record->full_name ?? '');
    $sheet->setCellValue('D' . $row, $record->whatsapp ?? '');
    $sheet->setCellValue('E' . $row, $record->male ?? 0);
    $sheet->setCellValue('F' . $row, $record->female ?? 0);
    $sheet->setCellValue('G' . $row, $record->kids ?? 0);
    $sheet->setCellValue('H' . $row, $record->infant ?? 0);
    $sheet->setCellValue('I' . $row, $record->chairs ?? 0);
    $sheet->setCellValue('J' . $row, $record->pirsa_count ?? 0);
    $sheet->setCellValue('K' . $row, $record->zabihat_count ?? 0);
    $sheet->setCellValue('L' . $row, $record->takhmeen ?? 0);
    $sheet->setCellValue('M' . $row, $record->paid_amount ?? 0);
    $sheet->setCellValue('N' . $row, $record->pending ?? 0);
    $row++;
}

// Add summary row if available
if (!is_null($registration_summary)) {
    $sheet->setCellValue('A' . $row, 'TOTAL ==>');
    $sheet->setCellValue('B' . $row, '');
    $sheet->setCellValue('C' . $row, '');
    $sheet->setCellValue('D' . $row, '');
    $sheet->setCellValue('E' . $row, $registration_summary->males ?? 0);
    $sheet->setCellValue('F' . $row, $registration_summary->females ?? 0);
    $sheet->setCellValue('G' . $row, $registration_summary->kids ?? 0);
    $sheet->setCellValue('H' . $row, $registration_summary->infants ?? 0);
    $sheet->setCellValue('I' . $row, $registration_summary->chairs ?? 0);
    $sheet->setCellValue('J' . $row, $registration_summary->pirsas ?? 0);
    $sheet->setCellValue('K' . $row, $registration_summary->zabihat ?? 0);
    $sheet->setCellValue('L' . $row, $registration_summary->takhmeen ?? 0);
    $sheet->setCellValue('M' . $row, $registration_summary->paid ?? 0);
    $sheet->setCellValue('N' . $row, ($registration_summary->takhmeen ?? 0) - ($registration_summary->paid ?? 0));
    
    // Style summary row
    $summaryRange = 'A' . $row . ':N' . $row;
    $sheet->getStyle($summaryRange)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'D9E1F2']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);
}

// Apply borders to all data cells
$dataRange = 'A1:N' . $row;
$sheet->getStyle($dataRange)->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CCCCCC']
        ]
    ]
]);

// Auto-size columns
foreach (range('A', 'N') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set alignment for numeric columns
$sheet->getStyle('E2:N' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// Output headers
$filename = 'registrations_' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Write file to output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
