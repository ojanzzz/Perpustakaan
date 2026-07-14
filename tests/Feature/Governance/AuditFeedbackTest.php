<?php

namespace Tests\Feature\Governance;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_submit_valid_feedback_but_not_unsafe_payload(): void
    {
        $this->post('/feedback', [
            'type' => 'suggestion', 'name' => 'Warga', 'email' => 'warga@example.test',
            'subject' => 'Usulan koleksi', 'message' => 'Mohon tambahkan dokumen pendidikan pemilih.',
        ])->assertRedirect();
        $this->assertDatabaseHas('feedback', ['type' => 'suggestion', 'subject' => 'Usulan koleksi', 'status' => 'new']);

        $this->post('/feedback', ['type' => 'invalid', 'subject' => '<script>x</script>', 'message' => 'x'])
            ->assertSessionHasErrors(['type', 'subject', 'message']);
    }

    public function test_login_and_book_change_are_written_to_audit_log(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Superadmin, 'password' => 'Password!123']);

        $this->post('/login', ['email' => $admin->email, 'password' => 'Password!123'])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'auth.login']);

        $book = Book::factory()->create(['title' => 'Judul Awal']);
        $book->update(['title' => 'Judul Baru']);
        $log = AuditLog::query()->where('action', 'books.update')->latest('id')->firstOrFail();
        $this->assertSame('Judul Awal', $log->before_values['title']);
        $this->assertSame('Judul Baru', $log->after_values['title']);
    }

    public function test_audit_log_is_immutable_in_model_and_database(): void
    {
        $log = AuditLog::query()->create(['action' => 'test.event']);

        try {
            $log->update(['action' => 'tampered']);
            $this->fail('Audit log update should fail.');
        } catch (\LogicException) {
            $this->assertSame('test.event', $log->fresh()->action);
        }

        $this->expectException(QueryException::class);
        DB::table('audit_logs')->where('id', $log->id)->delete();
    }

    public function test_superadmin_can_view_and_export_audit_while_member_cannot(): void
    {
        $this->seed(PermissionSeeder::class);
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        $member = User::factory()->create(['role' => UserRole::Member]);
        AuditLog::query()->create(['user_id' => $superadmin->id, 'action' => 'report.view']);

        $this->actingAs($superadmin)->get('/admin/audit-logs')->assertOk()->assertSee('report.view');
        $this->actingAs($superadmin)->get('/admin/audit-logs/export')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($member)->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_superadmin_can_resolve_feedback(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->create(['role' => UserRole::Superadmin]);
        $id = DB::table('feedback')->insertGetId(['type' => 'report', 'subject' => 'File rusak', 'message' => 'PDF tidak terbuka', 'status' => 'new', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($admin)->put("/admin/feedback/{$id}", ['status' => 'resolved', 'resolution_notes' => 'PDF sudah diganti.'])
            ->assertRedirect('/admin/feedback');
        $this->assertDatabaseHas('feedback', ['id' => $id, 'status' => 'resolved', 'resolved_by' => $admin->id]);
    }
}
