<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftResult extends Model
{
    protected $fillable = ['shift_id', 'delivered_volume', 'volume_unit', 'notes', 'recorded_by_user_id'];

    protected function casts(): array
    {
        return ['delivered_volume' => 'decimal:2'];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
