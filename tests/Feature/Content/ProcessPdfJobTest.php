<?php

namespace Tests\Feature\Content;

use App\Domain\Documents\PdfValidationService;
use App\Enums\ProcessingStatus;
use App\Jobs\ProcessPdf;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

        (new ProcessPdf($book->id))->handle(app(PdfValidationService::class));

        $book->refresh();
        $this->assertSame(ProcessingStatus::Failed, $book->processing_status);
        $this->assertNotNull($book->processing_error);
    }

    public function test_job_uses_ghostscript_to_render_only_the_first_pdf_page_as_cover(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $tempDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kpu-fake-gs-'.bin2hex(random_bytes(6));
        mkdir($tempDirectory);
        $sourceJpg = $tempDirectory.DIRECTORY_SEPARATOR.'first-page.jpg';
        $image = imagecreatetruecolor(20, 30);
        imagefill($image, 0, 0, imagecolorallocate($image, 196, 30, 58));
        imagejpeg($image, $sourceJpg, 90);
        imagedestroy($image);

        $binary = $this->createFakeGhostscript($tempDirectory, $sourceJpg);
        $originalPath = getenv('PATH');
        putenv('PATH=');

        try {
            config([
                'pdf.pdfinfo_binary' => '',
                'pdf.pdftoppm_binary' => '',
                'pdf.ghostscript_binary' => $binary,
            ]);
            $book = Book::factory()->create([
                'original_file' => 'books/ghostscript/document.pdf',
                'processing_status' => ProcessingStatus::Pending,
            ]);
            Storage::disk('private')->put($book->original_file, PdfFixture::onePage());

            (new ProcessPdf($book->id))->handle(app(PdfValidationService::class));

            $book->refresh();
            $this->assertSame(ProcessingStatus::Completed, $book->processing_status);
            $this->assertSame("covers/{$book->id}/cover-".substr($book->file_hash, 0, 16).'.webp', $book->cover_image);
            Storage::disk('public')->assertExists($book->cover_image);
        } finally {
            putenv($originalPath === false ? 'PATH' : 'PATH='.$originalPath);
            foreach (glob($tempDirectory.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($tempDirectory);
        }
    }

    private function createFakeGhostscript(string $directory, string $sourceJpg): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $binary = $directory.DIRECTORY_SEPARATOR.'fake-gs.cmd';
            $script = <<<'BAT'
@echo off
setlocal EnableDelayedExpansion
set first=0
set last=0
set output=
for %%A in (%*) do (
  set arg=%%~A
  if "!arg!"=="-dFirstPage=1" set first=1
  if "!arg!"=="-dLastPage=1" set last=1
  if "!arg:~0,13!"=="-sOutputFile=" set output=!arg:~13!
)
if not "!first!!last!"=="11" exit /b 12
copy /Y "__SOURCE__" "!output!" >nul
BAT;
            $script = str_replace('__SOURCE__', $sourceJpg, $script);
        } else {
            $binary = $directory.DIRECTORY_SEPARATOR.'fake-gs';
            $script = <<<'SH'
#!/bin/sh
first=0
last=0
output=''
for arg in "$@"; do
  [ "$arg" = '-dFirstPage=1' ] && first=1
  [ "$arg" = '-dLastPage=1' ] && last=1
  case "$arg" in -sOutputFile=*) output="${arg#-sOutputFile=}";; esac
done
[ "$first$last" = '11' ] || exit 12
/bin/cp __SOURCE__ "$output"
SH;
            $script = str_replace('__SOURCE__', escapeshellarg($sourceJpg), $script);
        }

        file_put_contents($binary, $script);
        @chmod($binary, 0700);

        return $binary;
    }
}
