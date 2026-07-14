<?php

namespace Tests\Feature\Content;

use App\Enums\UserRole;
use App\Jobs\ProcessPdf;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
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

    public function test_superadmin_can_create_a_draft_with_a_private_pdf(): void
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

        $book = $this->assertDatabaseHas('books', [
            'title' => 'Panduan Pemilih',
            'status' => 'draft',
            'processing_status' => 'pending',
            'created_by' => $superadmin->id,
        ]);
        $model = Book::query()->where('title', 'Panduan Pemilih')->firstOrFail();

        $this->assertNotSame('Dokumen Asli.pdf', $model->original_file);
        $this->assertStringStartsWith('books/', $model->original_file);
        Storage::disk('private')->assertExists($model->original_file);
        $this->assertDatabaseHas('book_versions', ['book_id' => $model->id, 'version_number' => 1]);
        $this->assertDatabaseHas('book_category', ['book_id' => $model->id, 'category_id' => $category->id]);
        $this->assertDatabaseHas('book_collection', ['book_id' => $model->id, 'collection_id' => $collection->id]);
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

    public function test_member_cannot_upload_a_book(): void
    {
        $member = User::factory()->create(['role' => UserRole::Member]);

        $this->actingAs($member)->post('/admin/books', [
            'title' => 'Dilarang',
            'visibility' => 'public',
            'pdf' => UploadedFile::fake()->createWithContent('valid.pdf', PdfFixture::onePage()),
        ])->assertForbidden();
    }
}
