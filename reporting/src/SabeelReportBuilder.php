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
     * @param array<int, array{itsNo: string, fullName: string, mohallaName: string, sabeelType: string, grade: string, sabeelAmount: string, sabeelNo: string, mobile: string, paidTill: string, outstanding: float}> $dueRows
     * @param array<string, int> $itsNoToMemberId Full-roster ITS-No -> Member-ID map, see JamaatOnlineClient::getSabeelMemberRoster().
     * @param array<int, array{studentItsNo: string, studentName: string, standard: string, totalDue: float}> $madresaFeeRows See JamaatOnlineClient::getMadresaFeeDueReport().
     * @param array<string, array{studentName: string, standard: string, hofItsNo: string, hofName: string, contact: string}> $madresaStudentRoster See JamaatOnlineClient::getMadresaStudentRoster() — links a fee row's student ITS to their family's HOF ITS.
     * @return array<string, array{
     *     sabeelNo: string,
     *     itsNo: string,
     *     hof: array<string, string>,
     *     sabeelLines: array<int, array{sabeelType: string, grade: string, outstanding: float}>,
     *     sabeelTotal: float,
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
        array $madresaStudentRoster = []
    ): array {
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
                ];
            }

            $bySabeelNo[$sabeelNo]['sabeelLines'][] = [
                'sabeelType' => $row['sabeelType'],
                'grade' => $row['grade'],
                'outstanding' => $row['outstanding'],
            ];
            $bySabeelNo[$sabeelNo]['sabeelTotal'] += $row['outstanding'];
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

            $report['faizDue'] = $this->latestFaizDue($this->client->getFaizDueReport($report['itsNo']));

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
     * carries into the next), so the current amount owed is the most recent
     * year's Due, not a sum across years — see JamaatOnlineClient::getFaizDueReport().
     *
     * @param array<int, array{takhmeenYear: string, due: float}> $faizRows
     */
    private function latestFaizDue(array $faizRows): float
    {
        if ($faizRows === []) {
            return 0.0;
        }

        usort($faizRows, static fn (array $a, array $b) => self::startYear($a['takhmeenYear']) <=> self::startYear($b['takhmeenYear']));

        return end($faizRows)['due'];
    }

    private static function startYear(string $takhmeenYear): int
    {
        return (int) explode('-', $takhmeenYear)[0];
    }
}
