<?php

namespace App\Models;

use Database\Factories\ReportEvidenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportEvidence extends Model
{
    /** @use HasFactory<ReportEvidenceFactory> */
    use HasFactory;

    protected $table = 'report_evidences';

    protected $fillable = ['report_id', 'report_followup_id', 'uploaded_by_user_id', 'original_name', 'storage_key', 'mime_type', 'size_bytes'];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function followup(): BelongsTo
    {
        return $this->belongsTo(ReportFollowup::class, 'report_followup_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
