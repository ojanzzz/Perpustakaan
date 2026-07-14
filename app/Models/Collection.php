<?php

namespace App\Models;

use Database\Factories\CollectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    /** @use HasFactory<CollectionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'cover_image', 'visibility', 'password_hash', 'sort_order', 'status'];

    protected $hidden = ['password_hash'];

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class)->withPivot('sort_order');
    }
}
