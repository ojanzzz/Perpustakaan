<?php

namespace App\Jobs;

use App\Domain\Backup\BackupService;
use App\Models\Backup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunBackup implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(public readonly int $backupId) {}

    public function handle(BackupService $service): void
    {
        $service->run(Backup::query()->findOrFail($this->backupId));
    }
}
