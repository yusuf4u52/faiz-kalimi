<?php

declare(strict_types=1);

namespace JamaatReport;

/**
 * Local on-disk snapshot of the jamaat-wide (not Sabeel-specific) JamaatOnline
 * lookups index.php needs for every report: the Sabeel due report and roster
 * (both already returned whole-jamaat in one call, see JamaatOnlineClient),
 * plus the Madresa academic year/roster/fee report. refresh_cache.php (run
 * every few minutes via cron) is the only writer; index.php only reads, and
 * falls back to a live scrape when the snapshot is missing or stale.
 */
final class JamaatDataCache
{
    public function __construct(private string $path)
    {
    }

    /**
     * @return array{fetchedAt: int, fiscalYearId: int, fiscalYearLabel: string, dueRows: array, roster: array<int, array{itsNo: string, fullName: string}>, academicYearId: int, academicYearLabel: string, madresaStudentRoster: array, madresaFeeRows: array}|null
     *   Null if no snapshot has been written yet, or the file is missing/unreadable/corrupt.
     */
    public function read(): ?array
    {
        if (!is_file($this->path)) {
            return null;
        }

        $json = file_get_contents($this->path);
        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true);
        if (!is_array($data)
            || !isset($data['fetchedAt'], $data['fiscalYearId'], $data['dueRows'], $data['roster'], $data['academicYearId'], $data['madresaStudentRoster'], $data['madresaFeeRows'])
        ) {
            return null;
        }

        // JSON object keys are always strings; JamaatOnlineClient::getSabeelMemberRoster()
        // and every caller of it expect roster to stay keyed by int Member ID.
        $roster = [];
        foreach ($data['roster'] as $memberId => $member) {
            $roster[(int) $memberId] = $member;
        }
        $data['roster'] = $roster;

        return $data;
    }

    public function ageSeconds(array $snapshot): int
    {
        return max(0, time() - (int) $snapshot['fetchedAt']);
    }

    /**
     * Writes via a temp-file-then-rename so a reader never sees a partially
     * written file, and a failed refresh run leaves the previous good snapshot
     * in place instead of truncating it.
     *
     * @param array{fiscalYearId: int, fiscalYearLabel: string, dueRows: array, roster: array, academicYearId: int, academicYearLabel: string, madresaStudentRoster: array, madresaFeeRows: array} $snapshot
     */
    public function write(array $snapshot): void
    {
        $snapshot['fetchedAt'] = time();

        $dir = dirname($this->path);
        if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
            throw new \RuntimeException("Could not create cache directory: $dir");
        }

        $tmpPath = $this->path . '.' . getmypid() . '.tmp';
        if (file_put_contents($tmpPath, json_encode($snapshot, JSON_THROW_ON_ERROR)) === false) {
            throw new \RuntimeException("Could not write cache file: $tmpPath");
        }

        if (!rename($tmpPath, $this->path)) {
            @unlink($tmpPath);
            throw new \RuntimeException("Could not activate new cache file: {$this->path}");
        }
    }
}
