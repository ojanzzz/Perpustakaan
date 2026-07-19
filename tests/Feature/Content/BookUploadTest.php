<?php

namespace Tests\Feature\Content;

use App\Enums\UserRole;
use App\Jobs\ProcessPdf;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use App\Models\User;
use App\Notifications\BookPublishedNotification;
use App\Services\RemotePdfDownloader;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\Support\PdfFixture;
use Tests\TestCase;

class BookUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        Storage::fake('private');
        Queue::fake();
    }

    public function test_superadmin_upload_publishes_book_immediately_with_a_private_pdf(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        $category = Category::factory()->create();
        $collection = Collection::factory()->create();
        $pdf = UploadedFile::fake()->createWithContent('Dokumen Asli.pdf', PdfFixture::onePage());

        $this->actingAs($superadmin)->post('/admin/books', [
            'title' => 'Panduan Pemilih',
            'description' => 'Dokumen demo tanpa konten berhak cipta.',
            'publication_year' => 2026,
            'visibility' => 'public',
            'category_ids' => [$category->id],
            'collection_ids' => [$collection->id],
            'pdf' => $pdf,
        ])->assertRedirect('/admin/books');

        $this->assertDatabaseHas('books', [
            'title' => 'Panduan Pemilih',
            'status' => 'published',
            'processing_status' => 'pending',
            'created_by' => $superadmin->id,
        ]);
        $model = Book::query()->where('title', 'Panduan Pemilih')->firstOrFail();

        $this->assertNotNull($model->published_at);
        $this->assertTrue($model->published_at->isPast() || $model->published_at->isCurrentSecond());
        $this->assertNotSame('Dokumen Asli.pdf', $model->original_file);
        $this->assertStringStartsWith('books/', $model->original_file);
        Storage::disk('private')->assertExists($model->original_file);
        $this->assertDatabaseHas('book_versions', ['book_id' => $model->id, 'version_number' => 1]);
        $this->assertDatabaseHas('book_category', ['book_id' => $model->id, 'category_id' => $category->id]);
        $this->assertDatabaseHas('book_collection', ['book_id' => $model->id, 'collection_id' => $collection->id]);
        $this->assertDatabaseHas('audit_logs', ['target_id' => $model->id, 'action' => 'books.publish']);
        Queue::assertPushed(ProcessPdf::class, fn (ProcessPdf $job) => $job->bookId === $model->id);
    }

    public function test_corrupt_pdf_is_rejected_without_persisting_a_book(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        $file = UploadedFile::fake()->createWithContent('broken.pdf', 'not a pdf');

        $this->actingAs($superadmin)->post('/admin/books', [
            'title' => 'Rusak',
            'visibility' => 'public',
            'pdf' => $file,
        ])->assertSessionHasErrors('pdf');

        $this->assertDatabaseMissing('books', ['title' => 'Rusak']);
        Queue::assertNothingPushed();
    }

    public function test_remote_pdf_upload_also_publishes_book_immediately(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        $url = 'https://example.test/document.pdf';
        $tempPdf = tempnam(sys_get_temp_dir(), 'remote-pdf-');
        file_put_contents($tempPdf, PdfFixture::onePage());

        $this->mock(RemotePdfDownloader::class, function (MockInterface $mock) use ($tempPdf, $url): void {
            $mock->shouldReceive('download')->once()->with($url)->andReturn($tempPdf);
        });

        $this->actingAs($superadmin)->post('/admin/books', [
            'title' => 'Dokumen dari URL',
            'visibility' => 'public',
            'pdf_url' => $url,
        ])->assertRedirect('/admin/books');

        $book = Book::query()->where('title', 'Dokumen dari URL')->firstOrFail();
        $this->assertSame('published', $book->status->value);
        $this->assertNotNull($book->published_at);
        Storage::disk('private')->assertExists($book->original_file);
        Queue::assertPushed(ProcessPdf::class, fn (ProcessPdf $job) => $job->bookId === $book->id);
    }

    public function test_direct_publication_notifies_subscribers_after_categories_are_attached(): void
    {
        Notification::fake();
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        $member = User::factory()->create(['role' => UserRole::Member]);
        $category = Category::factory()->create();
        $member->subscribedCategories()->attach($category);

        $this->actingAs($superadmin)->post('/admin/books', [
            'title' => 'Buku Langsung Terbit',
            'visibility' => 'public',
            'category_ids' => [$category->id],
            'pdf' => UploadedFile::fake()->createWithContent('valid.pdf', PdfFixture::onePage()),
        ])->assertRedirect('/admin/books');

        $book = Book::query()->where('title', 'Buku Langsung Terbit')->firstOrFail();
        Notification::assertSentTo(
            $member,
            BookPublishedNotification::class,
            fn (BookPublishedNotification $notification) => $notification->bookId === $book->id,
        );
    }

    public function test_member_cannot_upload_a_book(): void
    {
        $member = User::factory()->create(['role' => UserRole::Member]);

        $this->actingAs($member)->post('/admin/books', [
            'title' => 'Dilarang',
            'visibility' => 'public',
            'pdf' => UploadedFile::fake()->createWithContent('valid.pdf', PdfFixture::onePage()),
        ])->assertForbidden();
    }

    public function test_remote_pdf_url_must_use_http_or_https(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);

        $this->actingAs($superadmin)->post('/admin/books', [
            'title' => 'URL tidak aman',
            'visibility' => 'public',
            'pdf_url' => 'ftp://example.test/document.pdf',
        ])->assertSessionHasErrors('pdf_url');

        $this->assertDatabaseMissing('books', ['title' => 'URL tidak aman']);
    }
}
