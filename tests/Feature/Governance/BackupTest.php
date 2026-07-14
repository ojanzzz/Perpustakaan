<?php

namespace Tests\Feature\Governance;

use App\Domain\Backup\BackupService;
use App\Domain\Backup\RestoreService;
use App\Enums\AdminLevel;
use App\Enums\UserRole;
use App\Jobs\RunBackup;
use App\Models\Backup;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_authorized_admin_can_trigger_backup(): void
    {
        $this->seed(PermissionSeeder::class);
        Queue::fake();
        $superadmin = User::factory()->create(['role' => UserRole::Admin, 'admin_level' => AdminLevel::Superadmin]);
        $auditor = User::factory()->create(['role' => UserRole::Admin, 'admin_level' => AdminLevel::Auditor]);

        $this->actingAs($auditor)->post('/admin/backups')->assertForbidden();
        $this->actingAs($superadmin)->post('/admin/backups', ['type' => 'database'])->assertRedirect('/admin/backups');

        $backup = Backup::query()->latest('id')->firstOrFail();
        $this->assertSame('pending', $backup->status);
        Queue::assertPushed(RunBackup::class, fn (RunBackup $job) => $job->backupId === $backup->id);
    }

    public function test_backup_service_creates_valid_archive_and_checksum(): void
    {
        Storage::fake('backups');
        $backup = Backup::query()->create(['type' => 'database', 'disk' => 'backups', 'status' => 'pending']);

        app(BackupService::class)->run($backup);
        $backup->refresh();

        $this->assertSame('completed', $backup->status);
        Storage::disk('backups')->assertExists($backup->path);
        $absolutePath = Storage::disk('backups')->path($backup->path);
        $this->assertSame(hash_file('sha256', $absolutePath), $backup->checksum);
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($absolutePath) === true);
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $this->assertNotFalse($zip->locateName('database.sql'));
        $zip->close();
    }

    public function test_failed_backup_records_safe_error_state(): void
    {
        config(['filesystems.disks.backups.root' => null]);
        $backup = Backup::query()->create(['type' => 'database', 'disk' => 'backups', 'status' => 'pending']);

        try {
            app(BackupService::class)->run($backup);
        } catch (\Throwable) {
        }

        $backup->refresh();
        $this->assertSame('failed', $backup->status);
        $this->assertNotNull($backup->error_message);
    }

    public function test_restore_validates_archive_and_recovers_database_state(): void
    {
        Storage::fake('backups');
        Setting::putValue('restore_probe', 'before');
        $backup = Backup::query()->create(['type' => 'database', 'disk' => 'backups', 'status' => 'pending']);
        app(BackupService::class)->run($backup);
        Setting::putValue('restore_probe', 'after');

        app(RestoreService::class)->restore($backup->fresh());

        $this->assertSame('before', Setting::valueOf('restore_probe'));
        $this->assertDatabaseHas('backups', ['checksum' => $backup->fresh()->checksum, 'status' => 'completed']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'restore.completed']);
    }
}
