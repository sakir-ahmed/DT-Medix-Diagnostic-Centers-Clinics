<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Center\ExportCenterDataAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('center:export')]
#[Description('Export all data and files of this center into a zip on the private disk')]
class ExportCenterCommand extends Command
{
    public function handle(ExportCenterDataAction $export): int
    {
        $path = $export->execute();
        $this->info('Export written to '.$path);

        return self::SUCCESS;
    }
}
