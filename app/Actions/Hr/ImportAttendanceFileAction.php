<?php

declare(strict_types=1);

namespace App\Actions\Hr;

/**
 * Reads a ZKTeco ATTLOG export (.dat, tab separated) or a CSV file:
 * the first column is the device PIN, the second the date and time
 * ("2026-10-22 09:03:11"), or the second and third are date and time.
 */
final class ImportAttendanceFileAction
{
    public function __construct(private readonly IngestAttendancePunchesAction $ingest) {}

    /**
     * @return array{created: int, duplicates: int, skipped: int}
     */
    public function execute(int $branchId, string $contents): array
    {
        $rows = [];
        $skipped = 0;

        foreach (preg_split('/\r\n|\n|\r/', $contents) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $cells = array_map('trim', preg_split('/[\t,;]/', $line) ?: []);
            $time = $cells[1] ?? '';

            if (isset($cells[2]) && preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $cells[2]) === 1) {
                $time .= ' '.$cells[2];
            }

            if (($cells[0] ?? '') === '' || preg_match('/^\d{4}-\d{2}-\d{2}/', $time) !== 1) {
                $skipped++;

                continue;
            }

            $rows[] = [$cells[0], $time];
        }

        $result = $this->ingest->execute($branchId, $rows, 'import');
        $result['skipped'] += $skipped;

        return $result;
    }
}
