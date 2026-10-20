<?php

declare(strict_types=1);

namespace App\Actions\Center;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Exports every business table (JSON per table) plus the private files
 * into one zip on the private disk: exports/export-{timestamp}.zip
 */
final class ExportCenterDataAction
{
    /**
     * Framework tables that carry no center data.
     *
     * @var list<string>
     */
    private const SKIPPED_TABLES = [
        'migrations', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions',
        'password_reset_tokens', 'personal_access_tokens', 'idempotency_keys',
    ];

    public function execute(): string
    {
        $disk = Storage::disk((string) config('center.storage_disk'));
        $relative = 'exports/export-'.now()->format('Ymd-His').'.zip';
        $disk->makeDirectory('exports');

        $zip = new ZipArchive;

        if ($zip->open($disk->path($relative), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the export archive.');
        }

        foreach (Schema::getTableListing() as $table) {
            $name = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;

            if (in_array($name, self::SKIPPED_TABLES, true)) {
                continue;
            }

            $rows = [];

            DB::table($name)->orderBy(Schema::hasColumn($name, 'id') ? 'id' : DB::raw('1'))->chunk(500, function ($chunk) use (&$rows): void {
                foreach ($chunk as $row) {
                    $rows[] = (array) $row;
                }
            });

            $zip->addFromString("data/{$name}.json", (string) json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        foreach ($disk->allFiles() as $file) {
            if (! str_starts_with($file, 'exports/')) {
                $zip->addFile($disk->path($file), 'files/'.$file);
            }
        }

        $zip->close();

        activity('export')->withProperties(['path' => $relative])->log('center.exported');

        return $relative;
    }
}
