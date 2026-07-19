<?php

namespace App\Models;

use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Enums\ProcessingStatus;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'subtitle', 'description', 'editor', 'publisher_id', 'language_id',
        'publication_year', 'publication_date', 'isbn', 'document_number', 'publication_type',
        'page_count', 'file_size', 'original_file', 'optimized_file', 'cover_image', 'file_hash',
        'processing_status', 'processing_error', 'download_enabled', 'print_enabled', 'status',
        'visibility', 'password_hash', 'published_at', 'expires_at', 'sort_order', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookStatus::class,
            'visibility' => BookVisibility::class,
            'processing_status' => ProcessingStatus::class,
            'publication_date' => 'date',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'download_enabled' => 'boolean',
            'print_enabled' => 'boolean',
        ];
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        if (str_starts_with($this->cover_image, 'images/')) {
            return asset($this->cover_image);
        }

        return Storage::disk('public')->url($this->cover_image);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(BookVersion::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(BookReview::class)->latest('id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('sort_order');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)->withPivot('sort_order');
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_author');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(BookView::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(BookDownload::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function readingHistories(): HasMany
    {
        return $this->hasMany(ReadingHistory::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function personalCollections(): BelongsToMany
    {
        return $this->belongsToMany(PersonalCollection::class, 'personal_collection_books')
            ->withPivot('sort_order')->withTimestamps();
    }
}
