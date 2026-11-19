<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceConflictLog extends Model
{
    protected $fillable = [
        'resource_id', 'attempted_shift_id', 'conflicting_shift_id', 'attempted_starts_at', 'attempted_ends_at', 'attempted_by_user_id',
    ];

    protected function casts(): array
    {
        return ['attempted_starts_at' => 'datetime', 'attempted_ends_at' => 'datetime'];
    }
}
