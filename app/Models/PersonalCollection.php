<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalCollection extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'name', 'slug', 'description'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'personal_collection_books')
            ->withPivot('sort_order')->withTimestamps()->orderByPivot('sort_order');
    }
}
