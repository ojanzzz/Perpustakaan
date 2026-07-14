<?php

namespace App\Domain\Documents;

use App\Enums\BookStatus;
use App\Enums\ProcessingStatus;
use App\Jobs\ProcessPdf;
use App\Models\Book;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfIngestionService
{
    public function __construct(private readonly PdfValidationService $validator) {}

    /** @param array<string, mixed> $data */
    public function createDraft(array $data, UploadedFile $pdf, User $actor): Book
    {
        $probe = $this->validator->probeUpload($pdf);
        $directory = 'books/'.Str::uuid();
        $storedName = Str::uuid().'.pdf';
        $path = $pdf->storeAs($directory, $storedName, 'private');

        try {
            $book = DB::transaction(function () use ($data, $pdf, $actor, $probe, $path): Book {
                $book = Book::query()->create([
                    ...collect($data)->except(['category_ids', 'collection_ids', 'author_ids', 'tag_ids'])->all(),
                    'slug' => $this->uniqueSlug((string) $data['title']),
                    'status' => BookStatus::Draft,
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

        ProcessPdf::dispatch($book->id)->afterCommit();

        return $book;
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
