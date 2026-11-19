<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shift extends Model
{
    protected $fillable = ['sector_id', 'responsible_user_id', 'starts_at', 'ends_at', 'status', 'notes'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'shift_resources')->withTimestamps();
    }

    public function result(): HasOne
    {
        return $this->hasOne(ShiftResult::class);
    }
}
