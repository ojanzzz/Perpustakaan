<?php

namespace App\Domain\Backup;

use App\Domain\Audit\AuditRecorder;
use App\Models\Backup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class RestoreService
{
    public function __construct(private readonly BackupService $backups, private readonly AuditRecorder $audit) {}

    public function restore(Backup $backup): void
    {
        abort_unless($backup->status === 'completed' && $backup->path, 422, 'Backup belum siap dipulihkan.');
        $path = Storage::disk($backup->disk)->path($backup->path);
        if (! is_file($path) || ! hash_equals((string) $backup->checksum, hash_file('sha256', $path))) {
            throw new RuntimeException('Checksum backup tidak cocok.');
        }
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Arsip backup tidak dapat dibuka.');
        }
        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        $sql = $zip->getFromName('database.sql');
        $zip->close();
        if (($manifest['format_version'] ?? null) !== 2 || ($manifest['database_driver'] ?? null) !== DB::connection()->getDriverName() || ! is_string($sql)) {
            throw new RuntimeException('Format atau driver backup tidak kompatibel.');
        }

        $sourceData = $backup->only(['id', 'type', 'disk', 'path', 'size', 'checksum', 'status', 'requested_by', 'started_at', 'completed_at', 'created_at', 'updated_at']);
        $preRestore = Backup::query()->create(['type' => 'database', 'disk' => 'backups', 'status' => 'pending', 'requested_by' => $backup->requested_by]);
        $this->backups->run($preRestore);
        $preRestoreData = $preRestore->fresh()->only(['type', 'disk', 'path', 'size', 'checksum', 'status', 'requested_by', 'started_at', 'completed_at', 'created_at', 'updated_at']);
        $this->audit->record('restore.started', $backup, null, ['checksum' => $backup->checksum]);
        DB::unprepared($sql);
        if ($sourceData['requested_by'] && ! DB::table('users')->where('id', $sourceData['requested_by'])->exists()) {
            $sourceData['requested_by'] = null;
        }
        if ($preRestoreData['requested_by'] && ! DB::table('users')->where('id', $preRestoreData['requested_by'])->exists()) {
            $preRestoreData['requested_by'] = null;
        }
        DB::table('backups')->updateOrInsert(['id' => $sourceData['id']], $sourceData);
        DB::table('backups')->updateOrInsert(['checksum' => $preRestoreData['checksum']], $preRestoreData);
        $restoredBackup = Backup::query()->where('checksum', $backup->checksum)->first();
        $this->audit->record('restore.completed', $restoredBackup, null, ['source_checksum' => $backup->checksum]);
    }
}
