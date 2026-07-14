<?php

namespace Tests\Feature\Reader;

use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Support\PdfFixture;
use Tests\TestCase;

class DocumentDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_reader_bootstrap_uses_expiring_document_url_and_permission_flags(): void
    {
        $book = $this->readableBook(['download_enabled' => false, 'print_enabled' => true, 'page_count' => 10]);

        $response = $this->get(route('reader.show', ['book' => $book, 'page' => 4]));

        $response->assertOk()
            ->assertSee('data-reader-root', false)
            ->assertSee('data-initial-page="4"', false)
            ->assertSee('data-can-download="false"', false)
            ->assertSee('data-can-print="true"', false)
            ->assertSee('signature=', false);
    }

    public function test_explicit_page_query_takes_priority_over_member_saved_progress(): void
    {
        $member = User::factory()->create();
        $book = $this->readableBook(['page_count' => 10]);
        $member->readingHistories()->create(['book_id' => $book->id, 'last_page' => 8]);

        $this->actingAs($member)->get(route('reader.show', ['book' => $book, 'page' => 4]))
            ->assertOk()
            ->assertSee('data-initial-page="4"', false)
            ->assertSee('data-explicit-page="true"', false);
    }

    public function test_document_delivery_requires_valid_signature_and_supports_byte_ranges(): void
    {
        $book = $this->readableBook();

        $this->get(route('reader.document', $book))->assertForbidden();

        $url = URL::temporarySignedRoute('reader.document', now()->addMinutes(5), $book);
        $response = $this->withHeader('Range', 'bytes=0-7')->get($url);

        $response->assertStatus(206)
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('accept-ranges', 'bytes')
            ->assertHeader('content-range');
        $this->assertSame(substr(PdfFixture::onePage(), 0, 8), $response->streamedContent());
    }

    public function test_reader_does_not_expose_missing_or_inaccessible_documents(): void
    {
        $missing = $this->readableBook(['original_file' => 'books/missing.pdf']);
        Storage::disk('private')->delete('books/missing.pdf');
        $private = $this->readableBook(['visibility' => BookVisibility::Private]);

        $this->get(route('reader.show', $missing))->assertNotFound();
        $this->get(route('reader.show', $private))->assertNotFound();
    }

    public function test_download_requires_book_permission_and_a_signed_url(): void
    {
        $denied = $this->readableBook(['download_enabled' => false]);
        $allowed = $this->readableBook(['download_enabled' => true]);

        $deniedUrl = URL::temporarySignedRoute('reader.download', now()->addMinutes(5), $denied);
        $allowedUrl = URL::temporarySignedRoute('reader.download', now()->addMinutes(5), $allowed);

        $this->get($deniedUrl)->assertForbidden();
        $this->get(route('reader.download', $allowed))->assertForbidden();
        $this->get($allowedUrl)->assertOk()->assertDownload();
    }

    private function readableBook(array $attributes = []): Book
    {
        $path = $attributes['original_file'] ?? 'books/demo.pdf';
        Storage::disk('private')->put($path, PdfFixture::onePage());

        return Book::factory()->create([
            'status' => BookStatus::Published,
            'visibility' => BookVisibility::Public,
            'published_at' => now()->subMinute(),
            'original_file' => $path,
            'file_size' => strlen(PdfFixture::onePage()),
            'page_count' => 1,
            ...$attributes,
        ]);
    }
}
