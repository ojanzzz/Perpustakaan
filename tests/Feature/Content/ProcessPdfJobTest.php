<?php

namespace Tests\Feature\Content;

use App\Domain\Documents\PdfValidationService;
use App\Enums\ProcessingStatus;
use App\Jobs\ProcessPdf;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Support\PdfFixture;
use Tests\TestCase;

class ProcessPdfJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_extracts_metadata_without_requiring_thumbnail_binary(): void
    {
        Storage::fake('private');
        Storage::fake('public');
        config(['pdf.pdfinfo_binary' => '', 'pdf.pdftoppm_binary' => '']);
        $book = Book::factory()->create([
            'original_file' => 'books/test/document.pdf',
            'processing_status' => ProcessingStatus::Pending,
        ]);
        Storage::disk('private')->put($book->original_file, PdfFixture::onePage());

        (new ProcessPdf($book->id))->handle(app(PdfValidationService::class));

        $book->refresh();
        $this->assertSame(ProcessingStatus::Completed, $book->processing_status);
        $this->assertSame(1, $book->page_count);
        $this->assertSame(64, strlen($book->file_hash));
        $this->assertNull($book->processing_error);
    }

    public function test_job_records_failure_when_private_original_is_missing(): void
    {
        Storage::fake('private');
        config(['pdf.pdfinfo_binary' => '', 'pdf.pdftoppm_binary' => '']);
        $book = Book::factory()->create([
            'original_file' => 'books/missing.pdf',
            'processing_status' => ProcessingStatus::Pending,
        ]);

        try {
            (new ProcessPdf($book->id))->handle(app(PdfValidationService::class));
            $this->fail('Expected missing private PDF to fail.');
        } catch (RuntimeException) {
            $book->refresh();
            $this->assertSame(ProcessingStatus::Failed, $book->processing_status);
            $this->assertNotNull($book->processing_error);
        }
    }
}
