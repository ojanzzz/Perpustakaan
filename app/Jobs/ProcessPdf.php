<?php

namespace App\Jobs;

use App\Domain\Documents\PdfValidationService;
use App\Enums\ProcessingStatus;
use App\Models\Book;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ProcessPdf implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public readonly int $bookId) {}

    public function handle(PdfValidationService $validator): void
    {
        $book = Book::query()->findOrFail($this->bookId);
        $book->update(['processing_status' => ProcessingStatus::Processing, 'processing_error' => null]);

        try {
            $path = Storage::disk('private')->path($book->original_file);
            $probe = $validator->probePath($path);
            $fileHash = hash_file('sha256', $path);
            $cover = $this->createCover($path, $book->id, substr($fileHash, 0, 16));
            if ($cover === null) {
                throw new \RuntimeException('Cover halaman pertama PDF gagal dibuat.');
            }
            $oldCover = $book->cover_image;
            $updates = [
                'page_count' => $probe->pageCount,
                'file_size' => filesize($path),
                'file_hash' => $fileHash,
                'cover_image' => $cover,
                'processing_status' => ProcessingStatus::Completed,
                'processing_error' => null,
            ];
            $book->update($updates);
            $book->versions()->latest('version_number')->first()?->update([
                'page_count' => $updates['page_count'],
                'file_size' => $updates['file_size'],
                'file_hash' => $updates['file_hash'],
            ]);
            if ($oldCover && $oldCover !== $cover) {
                try {
                    Storage::disk('public')->delete($oldCover);
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
        } catch (\Throwable $exception) {
            $book->update([
                'processing_status' => ProcessingStatus::Failed,
                'processing_error' => Str($exception->getMessage())->limit(1000),
            ]);
        }
    }

    private function createCover(string $pdfPath, int $bookId, string $version): ?string
    {
        $ghostscript = (string) config('pdf.ghostscript_binary');
        if ($ghostscript !== '' && $this->commandExists($ghostscript)) {
            $cover = $this->createCoverWithGhostscript($pdfPath, $bookId, $version, $ghostscript);
            if ($cover !== null) {
                return $cover;
            }
        }

        $binary = (string) config('pdf.pdftoppm_binary');
        if ($binary !== '' && $this->commandExists($binary)) {
            $cover = $this->createCoverWithPdfToPpm($pdfPath, $bookId, $version, $binary);
            if ($cover !== null) {
                return $cover;
            }
        }

        return $this->createCoverWithPython($pdfPath, $bookId, $version);
    }

    private function createCoverWithGhostscript(string $pdfPath, int $bookId, string $version, string $binary): ?string
    {
        $jpg = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kpu-cover-'.bin2hex(random_bytes(8)).'.jpg';
        try {
            $process = new Process([
                $binary,
                '-dSAFER',
                '-dBATCH',
                '-dNOPAUSE',
                '-dUseCropBox',
                '-dAutoRotatePages=/PageByPage',
                '-sDEVICE=jpeg',
                '-dFirstPage=1',
                '-dLastPage=1',
                '-r144',
                '-dJPEGQ=90',
                '-sOutputFile='.$jpg,
                $pdfPath,
            ]);
            $process->setTimeout(60);
            $process->run();
            if (! $process->isSuccessful() || ! is_file($jpg)) {
                return null;
            }

            return $this->convertJpgToWebP($jpg, $bookId, $version);
        } finally {
            if (is_file($jpg)) {
                @unlink($jpg);
            }
        }
    }

    private function createCoverWithPdfToPpm(string $pdfPath, int $bookId, string $version, string $binary): ?string
    {
        $prefix = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kpu-cover-'.bin2hex(random_bytes(8));
        $jpg = $prefix.'.jpg';
        try {
            $process = new Process([$binary, '-f', '1', '-singlefile', '-jpeg', '-scale-to-x', '900', '-scale-to-y', '-1', $pdfPath, $prefix]);
            $process->setTimeout(60);
            $process->run();
            if (! $process->isSuccessful() || ! is_file($jpg)) {
                return null;
            }

            return $this->convertJpgToWebP($jpg, $bookId, $version);
        } finally {
            if (is_file($jpg)) {
                @unlink($jpg);
            }
        }
    }

    private function createCoverWithPython(string $pdfPath, int $bookId, string $version): ?string
    {
        $python = (string) config('pdf.python_binary');
        $script = base_path('scripts/pdf_cover.py');
        if ($python === '' || ! $this->commandExists($python) || ! is_file($script)) {
            return null;
        }

        $prefix = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kpu-cover-'.bin2hex(random_bytes(8));
        $jpg = $prefix.'.jpg';
        try {
            $process = new Process([$python, $script, $pdfPath, $jpg, '2']);
            $process->setTimeout(60);
            $process->run();
            if (! $process->isSuccessful() || ! is_file($jpg)) {
                return null;
            }

            return $this->convertJpgToWebP($jpg, $bookId, $version);
        } finally {
            if (is_file($jpg)) {
                @unlink($jpg);
            }
        }
    }

    private function convertJpgToWebP(string $jpgPath, int $bookId, string $version): ?string
    {
        $image = imagecreatefromjpeg($jpgPath);
        if ($image === false) {
            return null;
        }
        ob_start();
        $encoded = imagewebp($image, null, 82);
        $webp = ob_get_clean();
        imagedestroy($image);
        if (! $encoded || ! is_string($webp) || $webp === '') {
            return null;
        }
        $path = "covers/{$bookId}/cover-{$version}.webp";
        if (! Storage::disk('public')->put($path, $webp)) {
            return null;
        }

        return $path;
    }

    private function commandExists(string $binary): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return (bool) preg_match('/^[A-Z]:\\\\/', $binary) || (bool) shell_exec("where $binary 2>NUL");
        }

        return (bool) shell_exec("command -v $binary 2>/dev/null");
    }
}
