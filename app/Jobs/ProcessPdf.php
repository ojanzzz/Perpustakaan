<?php

namespace App\Jobs;

use App\Domain\Documents\PdfValidationService;
use App\Enums\ProcessingStatus;
use App\Models\Book;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
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
            $cover = $this->createCover($path, $book->id);
            $updates = [
                'page_count' => $probe->pageCount,
                'file_size' => filesize($path),
                'file_hash' => hash_file('sha256', $path),
                'processing_status' => ProcessingStatus::Completed,
                'processing_error' => null,
            ];
            if ($cover !== null) {
                $updates['cover_image'] = $cover;
            }
            $book->update($updates);
            $book->versions()->latest('version_number')->first()?->update([
                'page_count' => $updates['page_count'],
                'file_size' => $updates['file_size'],
                'file_hash' => $updates['file_hash'],
            ]);
        } catch (\Throwable $exception) {
            $book->update([
                'processing_status' => ProcessingStatus::Failed,
                'processing_error' => Str($exception->getMessage())->limit(1000),
            ]);
            throw $exception;
        }
    }

    private function createCover(string $pdfPath, int $bookId): ?string
    {
        $binary = (string) config('pdf.pdftoppm_binary');
        if ($binary === '') {
            return null;
        }

        $prefix = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kpu-cover-'.bin2hex(random_bytes(8));
        $jpg = $prefix.'.jpg';
        try {
            $process = new Process([$binary, '-f', '1', '-singlefile', '-jpeg', '-scale-to-x', '900', '-scale-to-y', '-1', $pdfPath, $prefix]);
            $process->setTimeout(60);
            $process->run();
            if (! $process->isSuccessful() || ! is_file($jpg)) {
                return null;
            }
            $image = imagecreatefromjpeg($jpg);
            if ($image === false) {
                throw new RuntimeException('Thumbnail PDF tidak dapat dibaca oleh GD.');
            }
            ob_start();
            imagewebp($image, null, 82);
            $webp = ob_get_clean();
            imagedestroy($image);
            if (! is_string($webp)) {
                return null;
            }
            $path = "covers/{$bookId}/cover.webp";
            Storage::disk('public')->put($path, $webp);

            return $path;
        } finally {
            if (is_file($jpg)) {
                @unlink($jpg);
            }
        }
    }
}
