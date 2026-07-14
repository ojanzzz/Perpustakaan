<?php

namespace App\Models;

use Database\Factories\PublisherFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Publisher extends Model
{
    /** @use HasFactory<PublisherFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description'];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
