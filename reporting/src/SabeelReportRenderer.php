<?php

declare(strict_types=1);

namespace JamaatReport;

/**
 * Renders a Sabeel report (see SabeelReportBuilder) as a print-ready HTML
 * document that Dompdf can turn into a PDF.
 */
final class SabeelReportRenderer
{
    public function __construct(
        private string $orgName,
        private string $currencySymbol
    ) {
    }

    /**
     * @param array{
     *     sabeelNo: string,
     *     itsNo: string,
     *     hof: array<string, string>,
     *     sabeelLines: array<int, array{sabeelType: string, grade: string, outstanding: float, outstandingTillYearEnd: float}>,
     *     sabeelTotal: float,
     *     sabeelTotalTillYearEnd: float,
     *     faizPreviousDue: float,
     *     faizCurrentDue: float,
     *     faizDue: float,
     *     hoobLines: array<int, array{hoobName: string, takhmeenYear: string, due: float}>,
     *     hoobTotal: float,
     *     madresaLines: array<int, array{studentName: string, standard: string, due: float}>,
     *     madresaTotal: float,
     *     grandTotal: float
     * } $report
     */
    public function render(array $report, string $fiscalYearLabel, string $generatedOn): string
    {
        $hof = $report['hof'];

        $sabeelRows = '';
        foreach ($report['sabeelLines'] as $line) {
            $sabeelRows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td class="right">%s</td><td class="right">%s</td></tr>',
                $this->esc($line['sabeelType']),
                $this->esc($line['grade']),
                $this->esc($this->formatOutstanding($line['outstanding'])),
                $this->esc($this->formatOutstanding($line['outstandingTillYearEnd']))
            );
        }

        $hoobSection = $this->renderLineItemsSection(
            'Hoob',
            'Hoob',
            'Year',
            'Hoob Total',
            array_map(static fn (array $l) => [$l['hoobName'], self::formatHoobYear($l['takhmeenYear']), $l['due']], $report['hoobLines']),
            $report['hoobTotal']
        );

        $madresaSection = $this->renderLineItemsSection(
            'Madresa',
            'Student',
            'Standard',
            'Madresa Total',
            array_map(static fn (array $l) => [$l['studentName'], $l['standard'], $l['due']], $report['madresaLines']),
            $report['madresaTotal']
        );

        $orgName = $this->esc($this->orgName);
        $fiscalYearLabel = $this->esc($fiscalYearLabel);
        $sabeelNo = $this->esc($report['sabeelNo']);
        $itsNo = $this->esc($report['itsNo']);
        $hofName = $this->esc($hof['fullname'] ?? '');
        $sabeelTotal = $this->esc($this->formatOutstanding($report['sabeelTotal']));
        $sabeelTotalTillYearEnd = $this->esc($this->formatOutstanding($report['sabeelTotalTillYearEnd']));
        $faizPreviousDue = $this->esc($this->formatOutstanding($report['faizPreviousDue']));
        $faizCurrentDue = $this->esc($this->formatOutstanding($report['faizCurrentDue']));
        $faizTotalDue = $this->esc($this->formatOutstanding($report['faizDue']));
        $grandTotal = $this->esc($this->formatOutstanding($report['grandTotal']));
        $generatedOn = $this->esc($generatedOn);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
    h1 { font-size: 18px; margin-bottom: 0; }
    h2 { font-size: 14px; margin: 20px 0 0; }
    .subtitle { color: #666; margin-top: 2px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
    th { background: #f2f2f2; }
    td.right, th.right { text-align: right; }
    .meta { margin-top: 12px; }
    .meta td { border: none; padding: 2px 8px 2px 0; }
    tfoot td { font-weight: bold; border-top: 2px solid #333; }
    .grand-total { margin-top: 20px; font-size: 14px; }
    .grand-total table { margin-top: 6px; }
    .footer { margin-top: 24px; color: #888; font-size: 10px; }
</style>
</head>
<body>
    <h1>{$orgName}</h1>
    <div class="subtitle">Statement of Dues &mdash; {$fiscalYearLabel}</div>

    <table class="meta">
        <tr><td><strong>Sabeel No.</strong></td><td>{$sabeelNo}</td></tr>
        <tr><td><strong>Head of Family</strong></td><td>{$hofName}</td></tr>
        <tr><td><strong>ITS No.</strong></td><td>{$itsNo}</td></tr>
    </table>

    <h2>Sabeel</h2>
    <table>
        <thead>
            <tr><th>Sabeel Type</th><th>Grade</th><th class="right">Outstanding (Till Date)</th><th class="right">Outstanding (Till Year-End)</th></tr>
        </thead>
        <tbody>
            {$sabeelRows}
        </tbody>
        <tfoot>
            <tr><td colspan="2">Sabeel Total</td><td class="right">{$sabeelTotal}</td><td class="right">{$sabeelTotalTillYearEnd}</td></tr>
        </tfoot>
    </table>

    <h2>Faiz</h2>
    <table>
        <thead>
            <tr><th class="right">Previous Due</th><th class="right">Current Due</th><th class="right">Total</th></tr>
        </thead>
        <tbody>
            <tr><td class="right">{$faizPreviousDue}</td><td class="right">{$faizCurrentDue}</td><td class="right">{$faizTotalDue}</td></tr>
        </tbody>
    </table>

    {$hoobSection}

    {$madresaSection}

    <div class="grand-total">
        <table>
            <tr><td><strong>Grand Total</strong></td><td class="right"><strong>{$grandTotal}</strong></td></tr>
        </table>
    </div>

    <div class="footer">Generated on {$generatedOn}. This is a system-generated statement; please contact the jamaat office for any queries.</div>
</body>
</html>
HTML;
    }

    /**
     * Renders a Hoob/Madresa-shaped section (a label + secondary column + due,
     * with a totals row) — empty string if there are no lines, so the section
     * (including its heading) simply doesn't appear for a HOF with nothing
     * pending in that category.
     *
     * @param array<int, array{0: string, 1: string, 2: float}> $lines Each: [label, secondary column, due].
     */
    private function renderLineItemsSection(
        string $heading,
        string $labelColumn,
        string $secondaryColumn,
        string $totalLabel,
        array $lines,
        float $total
    ): string {
        if ($lines === []) {
            return '';
        }

        $rows = '';
        foreach ($lines as [$label, $secondary, $due]) {
            $rows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td class="right">%s</td></tr>',
                $this->esc($label),
                $this->esc($secondary),
                $this->esc($this->formatOutstanding($due))
            );
        }

        $heading = $this->esc($heading);
        $labelColumn = $this->esc($labelColumn);
        $secondaryColumn = $this->esc($secondaryColumn);
        $totalLabel = $this->esc($totalLabel);
        $totalFormatted = $this->esc($this->formatOutstanding($total));

        return <<<HTML
    <h2>{$heading}</h2>
    <table>
        <thead>
            <tr><th>{$labelColumn}</th><th>{$secondaryColumn}</th><th class="right">Due</th></tr>
        </thead>
        <tbody>
            {$rows}
        </tbody>
        <tfoot>
            <tr><td colspan="2">{$totalLabel}</td><td class="right">{$totalFormatted}</td></tr>
        </tfoot>
    </table>
HTML;
    }

    /**
     * Hoob's takhmeenYear is scraped as a single year (e.g. '1447'), unlike
     * Faiz's own '1447-1448' range — displayed here as a range too for
     * consistency, since a Hoob pledge spans into the following year.
     */
    private static function formatHoobYear(string $takhmeenYear): string
    {
        if (!ctype_digit($takhmeenYear)) {
            return $takhmeenYear;
        }

        return $takhmeenYear . ' - ' . ((int) $takhmeenYear + 1);
    }

    /**
     * Negative outstanding means the member has paid ahead (an advance/credit);
     * positive means it's owed. Showing the bare signed number would be easy to
     * misread as the opposite, so the sign is spelled out instead.
     */
    private function formatOutstanding(float $amount): string
    {
        if ($amount < 0) {
            return $this->money(abs($amount)) . ' (Advance)';
        }
        if ($amount > 0) {
            return $this->money($amount) . ' (Due)';
        }

        return $this->money(0) . ' (Paid Up)';
    }

    private function money(float $amount): string
    {
        return $this->currencySymbol . number_format($amount, 2);
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
