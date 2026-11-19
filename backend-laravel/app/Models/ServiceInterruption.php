<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceInterruption extends Model
{
    protected $fillable = [
        'sector_id', 'report_id', 'created_by_user_id', 'cause', 'description',
        'affected_households', 'started_at', 'restored_at',
    ];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'restored_at' => 'datetime'];
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
