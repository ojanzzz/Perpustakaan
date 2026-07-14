<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookVersion extends Model
{
    protected $fillable = [
        'book_id', 'version_number', 'original_name', 'original_file', 'optimized_file',
        'file_hash', 'file_size', 'page_count', 'change_notes', 'created_by',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
