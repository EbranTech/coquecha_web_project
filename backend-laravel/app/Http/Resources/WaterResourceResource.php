<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaterResourceResource extends JsonResource
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
            'name' => $this->name,
            'resource_type' => $this->resource_type,
            'description' => $this->description,
            'capacity' => $this->capacity,
            'capacity_unit' => $this->capacity_unit,
            'status' => $this->status,
            'location_description' => $this->location_description,
        ];
    }
}
