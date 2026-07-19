<?php

namespace App\Domain\Documents;

use App\Domain\Catalog\BookPublicationNotifier;
use App\Enums\BookStatus;
use App\Enums\ProcessingStatus;
use App\Jobs\ProcessPdf;
use App\Models\Book;
use App\Models\User;
use App\Services\RemotePdfDownloader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfIngestionService
{
    public function __construct(
        private readonly PdfValidationService $validator,
        private readonly RemotePdfDownloader $downloader,
        private readonly BookPublicationNotifier $publicationNotifier,
    ) {}

    /** @param array<string, mixed> $data */
    public function createPublished(array $data, UploadedFile $pdf, User $actor): Book
    {
        $probe = $this->validator->probeUpload($pdf);
        $directory = 'books/'.Str::uuid();
        $storedName = Str::uuid().'.pdf';
        $path = $pdf->storeAs($directory, $storedName, 'private');

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('PDF gagal disimpan ke penyimpanan privat.');
        }

        try {
            $book = DB::transaction(function () use ($data, $pdf, $actor, $probe, $path): Book {
                $book = Book::query()->create([
                    ...collect($data)->except(['category_ids', 'collection_ids', 'author_ids', 'tag_ids'])->all(),
                    'slug' => $this->uniqueSlug((string) $data['title']),
                    'status' => BookStatus::Published,
                    'published_at' => now(),
                    'processing_status' => ProcessingStatus::Pending,
                    'original_file' => $path,
                    'file_size' => $pdf->getSize(),
                    'page_count' => $probe->pageCount,
                    'file_hash' => hash_file('sha256', $pdf->getRealPath()),
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);

                $book->versions()->create([
                    'version_number' => 1,
                    'original_name' => $this->safeOriginalName($pdf->getClientOriginalName()),
                    'original_file' => $path,
                    'file_hash' => $book->file_hash,
                    'file_size' => $book->file_size,
                    'page_count' => $book->page_count,
                    'created_by' => $actor->id,
                ]);

                $book->categories()->sync($data['category_ids'] ?? []);
                $book->collections()->sync($data['collection_ids'] ?? []);
                $book->authors()->sync($data['author_ids'] ?? []);
                $book->tags()->sync($data['tag_ids'] ?? []);

                return $book;
            });
        } catch (\Throwable $exception) {
            Storage::disk('private')->delete($path);
            throw $exception;
        }

        $this->publicationNotifier->notify($book);
        ProcessPdf::dispatch($book->id)->afterCommit();

        return $book;
    }

    /** @param array<string, mixed> $data */
    public function createPublishedFromUrl(array $data, string $url, User $actor): Book
    {
        $tempPath = $this->downloader->download($url);
        $originalName = basename(parse_url($url, PHP_URL_PATH) ?: 'document.pdf');

        try {
            $probe = $this->validator->probePath($tempPath);
            $directory = 'books/'.Str::uuid();
            $storedName = Str::uuid().'.pdf';
            $path = Storage::disk('private')->putFileAs($directory, $tempPath, $storedName);

            if (! is_string($path) || $path === '') {
                throw new \RuntimeException('PDF gagal disimpan ke penyimpanan privat.');
            }

            try {
                $book = DB::transaction(function () use ($data, $tempPath, $actor, $probe, $path, $originalName): Book {
                    $book = Book::query()->create([
                        ...collect($data)->except(['category_ids', 'collection_ids', 'author_ids', 'tag_ids'])->all(),
                        'slug' => $this->uniqueSlug((string) $data['title']),
                        'status' => BookStatus::Published,
                        'published_at' => now(),
                        'processing_status' => ProcessingStatus::Pending,
                        'original_file' => $path,
                        'file_size' => filesize($tempPath),
                        'page_count' => $probe->pageCount,
                        'file_hash' => hash_file('sha256', $tempPath),
                        'created_by' => $actor->id,
                        'updated_by' => $actor->id,
                    ]);

                    $book->versions()->create([
                        'version_number' => 1,
                        'original_name' => $this->safeOriginalName($originalName),
                        'original_file' => $path,
                        'file_hash' => $book->file_hash,
                        'file_size' => $book->file_size,
                        'page_count' => $book->page_count,
                        'created_by' => $actor->id,
                    ]);

                    $book->categories()->sync($data['category_ids'] ?? []);
                    $book->collections()->sync($data['collection_ids'] ?? []);
                    $book->authors()->sync($data['author_ids'] ?? []);
                    $book->tags()->sync($data['tag_ids'] ?? []);

                    return $book;
                });
            } catch (\Throwable $exception) {
                Storage::disk('private')->delete($path);
                throw $exception;
            }

            $this->publicationNotifier->notify($book);
            ProcessPdf::dispatch($book->id)->afterCommit();

            return $book;
        } finally {
            @unlink($tempPath);
        }
    }

    public function replacePdf(Book $book, UploadedFile $pdf, User $actor): void
    {
        $probe = $this->validator->probeUpload($pdf);
        $directory = 'books/'.Str::uuid();
        $storedName = Str::uuid().'.pdf';
        $newPath = $pdf->storeAs($directory, $storedName, 'private');

        if (! is_string($newPath) || $newPath === '') {
            throw new \RuntimeException('PDF gagal disimpan ke penyimpanan privat.');
        }

        $oldOriginal = $book->original_file;
        $oldOptimized = $book->optimized_file;
        $oldCover = $book->cover_image;

        try {
            DB::transaction(function () use ($book, $pdf, $actor, $probe, $newPath): void {
                $book->update([
                    'original_file' => $newPath,
                    'file_size' => $pdf->getSize(),
                    'page_count' => $probe->pageCount,
                    'file_hash' => hash_file('sha256', $pdf->getRealPath()),
                    'processing_status' => ProcessingStatus::Pending,
                    'processing_error' => null,
                    'updated_by' => $actor->id,
                ]);

                $latestVersion = $book->versions()->latest('version_number')->first();
                $versionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;

                $book->versions()->create([
                    'version_number' => $versionNumber,
                    'original_name' => $this->safeOriginalName($pdf->getClientOriginalName()),
                    'original_file' => $newPath,
                    'file_hash' => $book->file_hash,
                    'file_size' => $book->file_size,
                    'page_count' => $book->page_count,
                    'created_by' => $actor->id,
                ]);

            });
        } catch (\Throwable $exception) {
            Storage::disk('private')->delete($newPath);
            throw $exception;
        }

        $this->deleteReplacedFiles($oldOriginal, $oldOptimized, $oldCover);

        ProcessPdf::dispatch($book->id)->afterCommit();
    }

    private function deleteReplacedFiles(?string $original, ?string $optimized, ?string $cover): void
    {
        try {
            if ($original) {
                Storage::disk('private')->delete($original);
            }
            if ($optimized) {
                Storage::disk('private')->delete($optimized);
            }
            if ($cover) {
                Storage::disk('public')->delete($cover);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'buku';
        $slug = $base;
        $counter = 2;
        while (Book::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function safeOriginalName(string $name): string
    {
        $name = preg_replace('/[^\pL\pN._ -]+/u', '', basename($name)) ?: 'document.pdf';

        return Str::limit($name, 200, '');
    }
}
