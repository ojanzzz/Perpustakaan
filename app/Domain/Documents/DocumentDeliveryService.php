<?php

namespace App\Domain\Documents;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDeliveryService
{
    public function exists(Book $book): bool
    {
        return filled($book->original_file) && Storage::disk('private')->exists($book->original_file);
    }

    public function stream(Book $book, Request $request, bool $download = false): StreamedResponse
    {
        abort_unless($this->exists($book), 404);

        $disk = Storage::disk('private');
        $size = $disk->size($book->original_file);
        [$start, $end, $status] = $this->range($request->header('Range'), $size);
        $length = $end - $start + 1;
        $filename = ($book->slug ?: 'dokumen').'.pdf';

        $headers = [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => 'application/pdf',
            'Content-Length' => (string) $length,
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ];
        if ($status === 206) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        return response()->stream(function () use ($disk, $book, $start, $length): void {
            $stream = $disk->readStream($book->original_file);
            abort_if($stream === false, 404);
            try {
                fseek($stream, $start);
                $remaining = $length;
                while ($remaining > 0 && ! feof($stream)) {
                    $chunk = fread($stream, min(8192, $remaining));
                    if ($chunk === false || $chunk === '') {
                        break;
                    }
                    echo $chunk;
                    $remaining -= strlen($chunk);
                }
            } finally {
                fclose($stream);
            }
        }, $status, $headers);
    }

    /** @return array{int, int, int} */
    private function range(?string $header, int $size): array
    {
        if (! $header) {
            return [0, max(0, $size - 1), 200];
        }

        abort_unless(preg_match('/^bytes=(\d*)-(\d*)$/', $header, $matches) === 1, 416);
        $start = $matches[1] === '' ? null : (int) $matches[1];
        $end = $matches[2] === '' ? null : (int) $matches[2];

        if ($start === null) {
            $suffix = min($end ?? 0, $size);
            $start = $size - $suffix;
            $end = $size - 1;
        } else {
            $end = min($end ?? ($size - 1), $size - 1);
        }

        abort_if($size < 1 || $start < 0 || $start >= $size || $start > $end, 416);

        return [$start, $end, 206];
    }
}
