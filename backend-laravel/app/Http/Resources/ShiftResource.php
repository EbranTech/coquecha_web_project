<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
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
            'responsible' => $this->responsible ? ['id' => $this->responsible->id, 'name' => $this->responsible->display_name] : null,
            'resources' => $this->resources->map(fn ($resource): array => ['id' => $resource->id, 'name' => $resource->name, 'resource_type' => $resource->resource_type])->all(),
            'starts_at' => $this->starts_at->toISOString(),
            'ends_at' => $this->ends_at->toISOString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'result' => $this->whenLoaded('result', fn (): ?array => $this->result ? [
                'delivered_volume' => $this->result->delivered_volume,
                'volume_unit' => $this->result->volume_unit,
                'notes' => $this->result->notes,
            ] : null),
        ];
    }
}
