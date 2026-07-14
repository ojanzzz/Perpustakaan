<?php

namespace App\Jobs;

use App\Domain\Backup\RestoreService;
use App\Models\Backup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunRestore implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(public readonly int $backupId) {}

    public function handle(RestoreService $service): void
    {
        $service->restore(Backup::query()->findOrFail($this->backupId));
    }
}
