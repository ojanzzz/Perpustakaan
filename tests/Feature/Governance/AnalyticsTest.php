<?php

namespace Tests\Feature\Governance;

use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Support\PdfFixture;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_activity_is_deduplicated_without_storing_raw_visitor_identity(): void
    {
        $book = $this->publishedBook(['page_count' => 10]);
        $payload = ['session_key' => 'reader-session-1234567890', 'page' => 5, 'duration_delta' => 30];

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone) Safari/605.1')
            ->postJson("/api/books/{$book->id}/view", $payload)->assertCreated();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->postJson("/api/books/{$book->id}/view", [...$payload, 'page' => 6, 'duration_delta' => 20])->assertOk();

        $this->assertDatabaseCount('book_views', 1);
        $view = $book->views()->firstOrFail();
        $this->assertSame(50, $view->duration_seconds);
        $this->assertSame(6, $view->last_page);
        $this->assertNotSame('203.0.113.42', $view->visitor_hash);
        $this->assertSame(64, strlen($view->visitor_hash));
    }

    public function test_authorized_download_is_recorded(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('books/demo.pdf', PdfFixture::onePage());
        $book = $this->publishedBook(['original_file' => 'books/demo.pdf', 'download_enabled' => true]);
        $url = URL::temporarySignedRoute('reader.download', now()->addMinute(), $book);

        $this->get($url)->assertOk();

        $this->assertDatabaseHas('book_downloads', ['book_id' => $book->id]);
    }

    public function test_superadmin_can_view_statistics_and_export_valid_formats_while_member_cannot(): void
    {
        $this->seed(PermissionSeeder::class);
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        $member = User::factory()->create(['role' => UserRole::Member]);
        $book = $this->publishedBook(['title' => 'Statistik Buku']);
        $book->views()->create(['visitor_hash' => hash('sha256', 'visitor'), 'session_hash' => hash('sha256', 'session'), 'viewed_at' => now(), 'duration_seconds' => 90]);

        $this->actingAs($member)->get('/admin/statistics')->assertForbidden();
        $this->actingAs($superadmin)->get('/admin/statistics')->assertOk()->assertSee('Statistik Buku');
        $this->actingAs($superadmin)->get('/admin/statistics/export?format=csv')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $xlsx = $this->actingAs($superadmin)->get('/admin/statistics/export?format=xlsx')->assertOk();
        $this->assertStringStartsWith('PK', $xlsx->streamedContent());
        $pdf = $this->actingAs($superadmin)->get('/admin/statistics/export?format=pdf')->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }

    private function publishedBook(array $attributes = []): Book
    {
        return Book::factory()->create([
            'status' => BookStatus::Published,
            'visibility' => BookVisibility::Public,
            'published_at' => now()->subMinute(),
            ...$attributes,
        ]);
    }
}
