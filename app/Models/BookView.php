<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookView extends Model
{
    protected $fillable = ['book_id', 'user_id', 'visitor_hash', 'session_hash', 'device_type', 'browser', 'referrer', 'duration_seconds', 'last_page', 'viewed_at'];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime'];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
