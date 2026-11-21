<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\EvidenceStorageException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportEvidenceRequest;
use App\Http\Requests\StoreReportFollowupRequest;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Models\ReportEvidence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReportController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:pending,in_progress,resolved,closed'],
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
        ]);

        $reports = Report::query()
            ->with(['sector', 'creator', 'followups.user', 'evidences'])
            ->when($request->user()->hasRole('neighbor'), fn ($query) => $query->where('created_by_user_id', $request->user()->id))
            ->when($request->user()->hasRole('coordinator'), fn ($query) => $query->whereIn('sector_id', $request->user()->managedSectors()->select('sectors.id')))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['sector_id'] ?? null, fn ($query, int $sectorId) => $query->where('sector_id', $sectorId))
            ->latest()
            ->get();

        return ReportResource::collection($reports);
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = Report::query()->create([
            ...$request->validated(),
            'created_by_user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return (new ReportResource($report->load(['sector', 'creator'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Report $report): ReportResource
    {
        Gate::authorize('view', $report);

        return new ReportResource($report->load(['sector', 'creator', 'followups.user', 'evidences']));
    }

    public function update(UpdateReportRequest $request, Report $report): ReportResource
    {
        $status = $request->validated('status');
        $report->update([
            'status' => $status,
            'resolved_at' => in_array($status, ['resolved', 'closed'], true) ? now() : null,
        ]);

        return new ReportResource($report->fresh()->load(['sector', 'creator', 'followups.user', 'evidences']));
    }

    public function storeFollowup(StoreReportFollowupRequest $request, Report $report): ReportResource
    {
        DB::transaction(function () use ($request, $report): void {
            $followup = $report->followups()->create([
                ...$request->validated(),
                'user_id' => $request->user()->id,
            ]);

            if ($followup->new_status !== null) {
                $report->update([
                    'status' => $followup->new_status,
                    'resolved_at' => in_array($followup->new_status, ['resolved', 'closed'], true) ? now() : null,
                ]);
            }
        });

        return new ReportResource($report->fresh()->load(['sector', 'creator', 'followups.user', 'evidences']));
    }

    public function storeEvidence(StoreReportEvidenceRequest $request, Report $report): JsonResponse
    {
        $storedKeys = [];

        try {
            $evidences = DB::transaction(function () use ($request, $report, &$storedKeys): array {
                return collect($request->file('files'))->map(function ($file) use ($request, $report, &$storedKeys): ReportEvidence {
                    $filename = Str::uuid().'-'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                    $storageKey = "reports/{$report->id}/{$filename}.{$file->extension()}";

                    $wasStored = Storage::disk('s3')->put($storageKey, $file->getContent(), ['ContentType' => $file->getMimeType()]);

                    if (! $wasStored) {
                        throw new EvidenceStorageException('El almacenamiento de evidencias no confirmó la carga del archivo.');
                    }

                    $storedKeys[] = $storageKey;

                    return $report->evidences()->create([
                        'uploaded_by_user_id' => $request->user()->id,
                        'original_name' => $file->getClientOriginalName(),
                        'storage_key' => $storageKey,
                        'mime_type' => $file->getMimeType(),
                        'size_bytes' => $file->getSize(),
                    ]);
                })->all();
            });
        } catch (FilesystemException|EvidenceStorageException $exception) {
            $this->deleteStoredKeys($storedKeys);
            report($exception);

            throw new HttpException(503, 'No fue posible guardar la evidencia. Intenta nuevamente más tarde.', $exception);
        } catch (\Throwable $exception) {
            $this->deleteStoredKeys($storedKeys);

            throw $exception;
        }

        return response()->json(['data' => $evidences], 201);
    }

    public function downloadEvidence(Request $request, Report $report, ReportEvidence $evidence): JsonResponse
    {
        Gate::authorize('view', $report);
        abort_unless($evidence->report_id === $report->id, 404);

        $expiresAt = now()->addMinutes(5);

        return response()->json([
            'url' => Storage::disk('s3')->temporaryUrl($evidence->storage_key, $expiresAt),
            'expires_at' => $expiresAt->toISOString(),
        ]);
    }

    /** @param array<int, string> $storedKeys */
    private function deleteStoredKeys(array $storedKeys): void
    {
        if ($storedKeys === []) {
            return;
        }

        try {
            Storage::disk('s3')->delete($storedKeys);
        } catch (FilesystemException $exception) {
            report($exception);
        }
    }
}
