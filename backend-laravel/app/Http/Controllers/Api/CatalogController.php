<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SectorResource;
use App\Http\Resources\WaterResourceResource;
use App\Models\Resource;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController extends Controller
{
    public function sectors(): AnonymousResourceCollection
    {
        return SectorResource::collection(
            Sector::query()->where('status', 'active')->orderBy('name')->get(),
        );
    }

    public function resources(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:available,maintenance,inactive'],
            'type' => ['nullable', 'string', 'max:80'],
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
        ]);

        return WaterResourceResource::collection(
            Resource::query()
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('resource_type', $type))
                ->when($filters['sector_id'] ?? null, fn ($query, int $sectorId) => $query->whereHas('sectors', fn ($sectorQuery) => $sectorQuery->whereKey($sectorId)))
                ->orderBy('name')
                ->orderBy('id')
                ->get(),
        );
    }
}
