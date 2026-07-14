<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    protected $fillable = ['type', 'disk', 'path', 'size', 'checksum', 'status', 'error_message', 'requested_by', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return ['size' => 'integer', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
