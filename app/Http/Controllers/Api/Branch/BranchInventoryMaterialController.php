<?php

namespace App\Http\Controllers\Api\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\InventoryMaterialIndexRequest;
use App\Http\Requests\Branch\StopImportInventoryMaterialRequest;
use App\Http\Requests\Branch\StoreInventoryMaterialRequest;
use App\Http\Requests\Branch\UpdateInventoryMaterialRequest;
use App\Repositories\Contracts\Branch\BranchInventoryMaterialRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchInventoryMaterialController extends Controller
{
    public function __construct(
        protected BranchInventoryMaterialRepositoryInterface $branchInventoryMaterialRepository
    ) {
    }

    public function index(InventoryMaterialIndexRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view inventory materials for this branch.
        return response()->json([
            'data' => $this->branchInventoryMaterialRepository->getDashboard($branchId, $request->validated()),
        ]);
    }

    public function kpi(InventoryMaterialIndexRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view inventory material KPI cards for this branch.
        [$period, $startDate, $endDate] = $this->reportFilters($request);

        return response()->json([
            'data' => $this->branchInventoryMaterialRepository->getKpiCards($branchId, $period, $startDate, $endDate),
        ]);
    }

    public function materials(InventoryMaterialIndexRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view inventory material list data for this branch.
        return response()->json([
            'data' => $this->branchInventoryMaterialRepository->getMaterialList($branchId, $request->validated()),
        ]);
    }

    public function show(int|string $branchId, int|string $materialId): JsonResponse
    {
        // TODO: Authorize that the current user can view this material detail for the branch.
        return response()->json([
            'data' => $this->branchInventoryMaterialRepository->findMaterialDetail($branchId, $materialId),
        ]);
    }

    public function store(StoreInventoryMaterialRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can add inventory materials for this branch.
        return response()->json([
            'message' => 'Action completed',
            'data' => $this->branchInventoryMaterialRepository->createMaterial($branchId, $request->validated()),
        ], 201);
    }

    public function update(
        UpdateInventoryMaterialRequest $request,
        int|string $branchId,
        int|string $materialId
    ): JsonResponse {
        // TODO: Authorize that the current user can update inventory materials for this branch.
        return response()->json([
            'message' => 'Action completed',
            'data' => $this->branchInventoryMaterialRepository->updateMaterial(
                $branchId,
                $materialId,
                $request->validated()
            ),
        ]);
    }

    public function stopImport(
        StopImportInventoryMaterialRequest $request,
        int|string $branchId,
        int|string $materialId
    ): JsonResponse {
        // TODO: Authorize that the current user can stop importing this material for the branch.
        $validated = $request->validated();

        return response()->json([
            'message' => 'Action completed',
            'data' => $this->branchInventoryMaterialRepository->stopImport(
                $branchId,
                $materialId,
                $validated['reason'] ?? null,
                $request->user()?->getAuthIdentifier()
            ),
        ]);
    }

    public function resumeImport(Request $request, int|string $branchId, int|string $materialId): JsonResponse
    {
        // TODO: Authorize that the current user can resume importing this material for the branch.
        return response()->json([
            'message' => 'Action completed',
            'data' => $this->branchInventoryMaterialRepository->resumeImport(
                $branchId,
                $materialId,
                $request->user()?->getAuthIdentifier()
            ),
        ]);
    }

    private function reportFilters(InventoryMaterialIndexRequest $request): array
    {
        $validated = $request->validated();

        return [
            (string) ($validated['period'] ?? 'month'),
            $validated['start_date'] ?? $validated['date'] ?? null,
            $validated['end_date'] ?? null,
        ];
    }
}
