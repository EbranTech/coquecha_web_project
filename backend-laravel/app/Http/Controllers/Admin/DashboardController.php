<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\Sector;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.admin.dashboard', [
            'title' => 'Resumen administrativo',
            'activeSectors' => Sector::query()->where('status', 'active')->count(),
            'availableResources' => Resource::query()->where('status', 'available')->count(),
            'resourcesInMaintenance' => Resource::query()->where('status', 'maintenance')->count(),
        ]);
    }
}
