<?php

namespace Tests\Unit\Domain;

use App\Domain\Catalog\BookPublicationNotifier;
use App\Domain\Documents\PdfIngestionService;
use App\Domain\Documents\PdfProbe;
use App\Domain\Documents\PdfValidationService;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\User;
use App\Services\RemotePdfDownloader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PdfIngestionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_private_storage_write_does_not_create_a_published_book(): void
    {
        $validator = Mockery::mock(PdfValidationService::class);
        $validator->shouldReceive('probeUpload')->once()->andReturn(new PdfProbe(1));

        $upload = Mockery::mock(UploadedFile::class);
        $upload->shouldReceive('storeAs')->once()->andReturn(false);

        $service = new PdfIngestionService(
            $validator,
            Mockery::mock(RemotePdfDownloader::class),
            Mockery::mock(BookPublicationNotifier::class),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PDF gagal disimpan');

        try {
            $service->createPublished(
                ['title' => 'Tidak Boleh Terbit', 'visibility' => 'public'],
                $upload,
                User::factory()->create(['role' => UserRole::Superadmin]),
            );
        } finally {
            $this->assertDatabaseMissing('books', ['title' => 'Tidak Boleh Terbit']);
        }
    }

    public function test_failed_replacement_write_preserves_existing_book_files(): void
    {
        Storage::fake('private');
        Storage::fake('public');
        Storage::disk('private')->put('books/original.pdf', 'original');
        Storage::disk('private')->put('books/optimized.pdf', 'optimized');
        Storage::disk('public')->put('covers/original.webp', 'cover');

        $book = Book::factory()->create([
            'original_file' => 'books/original.pdf',
            'optimized_file' => 'books/optimized.pdf',
            'cover_image' => 'covers/original.webp',
        ]);
        $validator = Mockery::mock(PdfValidationService::class);
        $validator->shouldReceive('probeUpload')->once()->andReturn(new PdfProbe(1));
        $upload = Mockery::mock(UploadedFile::class);
        $upload->shouldReceive('storeAs')->once()->andReturn(false);
        $service = new PdfIngestionService(
            $validator,
            Mockery::mock(RemotePdfDownloader::class),
            Mockery::mock(BookPublicationNotifier::class),
        );

        try {
            $service->replacePdf($book, $upload, User::factory()->create(['role' => UserRole::Superadmin]));
            $this->fail('Kegagalan penyimpanan seharusnya melempar exception.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('PDF gagal disimpan', $exception->getMessage());
        }

        $this->assertSame('books/original.pdf', $book->fresh()->original_file);
        Storage::disk('private')->assertExists('books/original.pdf');
        Storage::disk('private')->assertExists('books/optimized.pdf');
        Storage::disk('public')->assertExists('covers/original.webp');
    }
}
