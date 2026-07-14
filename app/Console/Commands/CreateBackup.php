<?php

namespace App\Console\Commands;

use App\Domain\Backup\BackupService;
use App\Jobs\RunBackup;
use App\Models\Backup;
use Illuminate\Console\Command;

class CreateBackup extends Command
{
    protected $signature = 'library:backup {--queue : Jalankan melalui queue}';

    protected $description = 'Membuat backup basis data E-Perpustakaan';

    public function handle(BackupService $service): int
    {
        $backup = Backup::query()->create(['type' => 'database', 'disk' => 'backups', 'status' => 'pending']);
        if ($this->option('queue')) {
            RunBackup::dispatch($backup->id);
            $this->info("Backup #{$backup->id} masuk antrean.");
        } else {
            $service->run($backup);
            $this->info("Backup selesai: {$backup->fresh()->path}");
        }

        return self::SUCCESS;
    }
}
