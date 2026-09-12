<?php

declare(strict_types=1);

namespace JamaatReport;

/**
 * Groups JamaatOnlineClient::getSabeelDueReport() rows (one row per Sabeel-type
 * line) by Sabeel No, and enriches each with the same HOF's Faiz (Niyaz), Hoob,
 * and Madresa (children's school fees) outstanding balances plus contact details.
 */
final class SabeelReportBuilder
{
    public function __construct(private JamaatOnlineClient $client)
    {
    }

    /**
     * "House Sabeel" (unlike every other Sabeel type) is billed monthly, so its
     * scraped `outstanding` only reflects what's due as of the last billing —
     * not what will have accrued by fiscal year-end. sabeelLines carries both
     * figures for every line (till-year-end just equals till-date for
     * non-monthly types, since nothing more is going to accrue on its own).
     */
    private const MONTHLY_SABEEL_TYPE = 'House Sabeel';

    /**
     * @param array<int, array{itsNo: string, fullName: string, mohallaName: string, sabeelType: string, grade: string, sabeelAmount: float, sabeelNo: string, mobile: string, paidTill: string, outstanding: float}> $dueRows
     * @param array<string, int> $itsNoToMemberId Full-roster ITS-No -> Member-ID map, see JamaatOnlineClient::getSabeelMemberRoster().
     * @param array<int, array{studentItsNo: string, studentName: string, standard: string, totalDue: float}> $madresaFeeRows See JamaatOnlineClient::getMadresaFeeDueReport().
     * @param array<string, array{studentName: string, standard: string, hofItsNo: string, hofName: string, contact: string}> $madresaStudentRoster See JamaatOnlineClient::getMadresaStudentRoster() — links a fee row's student ITS to their family's HOF ITS.
     * @param string $fiscalYearLabel e.g. '01 Apr 2026 To 31 Mar 2027' (see JamaatOnlineClient::getFiscalYears()) — used to project House Sabeel's outstanding through fiscal year-end.
     * @return array<string, array{
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
     * }>
     */
    public function buildFromDueRows(
        array $dueRows,
        array $itsNoToMemberId,
        array $madresaFeeRows = [],
        array $madresaStudentRoster = [],
        string $fiscalYearLabel = ''
    ): array {
        $monthsRemaining = $this->monthsRemainingInFiscalYear($fiscalYearLabel);

        $bySabeelNo = [];
        foreach ($dueRows as $row) {
            if ($row['sabeelNo'] === '') {
                continue;
            }

            $sabeelNo = $row['sabeelNo'];
            if (!isset($bySabeelNo[$sabeelNo])) {
                $bySabeelNo[$sabeelNo] = [
                    'sabeelNo' => $sabeelNo,
                    'itsNo' => $row['itsNo'],
                    'hof' => [],
                    'sabeelLines' => [],
                    'sabeelTotal' => 0.0,
                    'sabeelTotalTillYearEnd' => 0.0,
                ];
            }

            $outstandingTillYearEnd = strcasecmp(trim($row['sabeelType']), self::MONTHLY_SABEEL_TYPE) === 0
                ? $row['outstanding'] + $monthsRemaining * $row['sabeelAmount']
                : $row['outstanding'];

            $bySabeelNo[$sabeelNo]['sabeelLines'][] = [
                'sabeelType' => $row['sabeelType'],
                'grade' => $row['grade'],
                'outstanding' => $row['outstanding'],
                'outstandingTillYearEnd' => $outstandingTillYearEnd,
            ];
            $bySabeelNo[$sabeelNo]['sabeelTotal'] += $row['outstanding'];
            $bySabeelNo[$sabeelNo]['sabeelTotalTillYearEnd'] += $outstandingTillYearEnd;
        }

        $madresaByHofIts = [];
        foreach ($madresaFeeRows as $feeRow) {
            $hofItsNo = $madresaStudentRoster[$feeRow['studentItsNo']]['hofItsNo'] ?? null;
            if ($hofItsNo === null) {
                continue;
            }
            $madresaByHofIts[$hofItsNo][] = $feeRow;
        }

        foreach ($bySabeelNo as &$report) {
            $memberId = $itsNoToMemberId[$report['itsNo']] ?? null;
            $report['hof'] = $memberId !== null ? $this->client->getMumineenDetail($memberId) : [];

            [$report['faizPreviousDue'], $report['faizCurrentDue']] = $this->faizPreviousAndCurrentDue(
                $this->client->getFaizDueReport($report['itsNo'])
            );
            $report['faizDue'] = $report['faizPreviousDue'] + $report['faizCurrentDue'];

            $hoobRows = $this->client->getHoobDueReport($report['itsNo']);
            $report['hoobLines'] = array_map(
                static fn (array $r) => ['hoobName' => $r['hoobName'], 'takhmeenYear' => $r['takhmeenYear'], 'due' => $r['due']],
                $hoobRows
            );
            $report['hoobTotal'] = array_sum(array_column($hoobRows, 'due'));

            $madresaRows = $madresaByHofIts[$report['itsNo']] ?? [];
            $report['madresaLines'] = array_map(
                static fn (array $r) => ['studentName' => $r['studentName'], 'standard' => $r['standard'], 'due' => $r['totalDue']],
                $madresaRows
            );
            $report['madresaTotal'] = array_sum(array_column($madresaRows, 'totalDue'));

            $report['grandTotal'] = $report['sabeelTotal'] + $report['faizDue'] + $report['hoobTotal'] + $report['madresaTotal'];
        }
        unset($report);

        return $bySabeelNo;
    }

    /**
     * Faiz's due report is a running year-by-year ledger (each year's balance
     * carries into the next), so the total amount owed is the most recent
     * year's Due, not a sum across years — see JamaatOnlineClient::getFaizDueReport().
     * Split here into what was already outstanding going into the latest year
     * ("previous due", i.e. the year before's Due) and what that latest year
     * itself added on top ("current due"), so previous + current == total.
     *
     * @param array<int, array{takhmeenYear: string, due: float}> $faizRows
     * @return array{0: float, 1: float} [previousDue, currentDue]
     */
    private function faizPreviousAndCurrentDue(array $faizRows): array
    {
        if ($faizRows === []) {
            return [0.0, 0.0];
        }

        usort($faizRows, static fn (array $a, array $b) => self::startYear($a['takhmeenYear']) <=> self::startYear($b['takhmeenYear']));

        $latestDue = end($faizRows)['due'];
        $previousDue = count($faizRows) >= 2 ? $faizRows[count($faizRows) - 2]['due'] : 0.0;

        return [$previousDue, $latestDue - $previousDue];
    }

    private static function startYear(string $takhmeenYear): int
    {
        return (int) explode('-', $takhmeenYear)[0];
    }

    /**
     * Counts calendar months from the month after $today through the fiscal
     * year's end month, inclusive — e.g. today in September with the fiscal
     * year ending 31 Mar means Oct, Nov, Dec, Jan, Feb, Mar: 6 months still to
     * accrue on a monthly due like House Sabeel.
     *
     * @param string $fiscalYearLabel e.g. '01 Apr 2026 To 31 Mar 2027', see JamaatOnlineClient::getFiscalYears().
     */
    private function monthsRemainingInFiscalYear(string $fiscalYearLabel, ?\DateTimeImmutable $today = null): int
    {
        if (!preg_match('/to\s+(.+)$/i', trim($fiscalYearLabel), $m)) {
            return 0;
        }

        $fiscalYearEnd = \DateTimeImmutable::createFromFormat('d M Y', trim($m[1]));
        if ($fiscalYearEnd === false) {
            return 0;
        }

        $today ??= new \DateTimeImmutable();

        $startMonth = (int) $today->format('n') + 1;
        $startYear = (int) $today->format('Y');
        if ($startMonth > 12) {
            $startMonth = 1;
            $startYear++;
        }

        $endMonth = (int) $fiscalYearEnd->format('n');
        $endYear = (int) $fiscalYearEnd->format('Y');

        return max(0, ($endYear - $startYear) * 12 + ($endMonth - $startMonth) + 1);
    }
}
