<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sector' => ['id' => $this->sector->id, 'name' => $this->sector->name],
            'category' => $this->category,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'creator' => ['id' => $this->creator->id, 'name' => $this->creator->display_name],
            'followups' => $this->whenLoaded('followups', fn (): array => $this->followups->map(fn ($followup): array => [
                'id' => $followup->id,
                'message' => $followup->message,
                'new_status' => $followup->new_status,
                'created_at' => $followup->created_at?->toISOString(),
                'user' => ['id' => $followup->user->id, 'name' => $followup->user->display_name],
            ])->all()),
            'evidences' => ReportEvidenceResource::collection($this->whenLoaded('evidences')),
        ];
    }
}
