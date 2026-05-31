<?php

namespace App\Http\Controllers\Api\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Branch\BranchServiceManagementRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchServiceManagementController extends Controller
{
    public function __construct(
        protected BranchServiceManagementRepositoryInterface $branchServiceManagementRepository
    ) {
    }

    public function index(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view service management data for this branch.
        $filters = $this->validateFilters($request);

        return response()->json([
            'success' => true,
            'data' => $this->branchServiceManagementRepository->getOverview($branchId, $filters),
        ]);
    }

    public function kpi(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view KPI cards for this branch.
        $filters = $this->validatePeriod($request);

        return response()->json([
            'success' => true,
            'data' => $this->branchServiceManagementRepository->getKpiCards($branchId, $filters),
        ]);
    }

    public function services(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view service list data for this branch.
        $filters = $this->validateFilters($request);

        return response()->json([
            'success' => true,
            'data' => $this->branchServiceManagementRepository->getServiceList($branchId, $filters),
        ]);
    }

    public function updateWebsiteVisibility(Request $request, int|string $branchId, int|string $serviceId): JsonResponse
    {
        // TODO: Authorize that the current user can update website visibility for this branch service.
        $request->validate([
            'is_visible' => ['required', 'boolean'],
        ]);

        return response()->json([
            'message' => 'Updated successfully',
            'data' => $this->branchServiceManagementRepository->updateWebsiteVisibility(
                $branchId,
                $serviceId,
                $request->boolean('is_visible')
            ),
        ]);
    }

    public function updateEmergencyLock(Request $request, int|string $branchId, int|string $serviceId): JsonResponse
    {
        // TODO: Authorize that the current user can lock or unlock this branch service.
        $validated = $request->validate([
            'is_locked' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['is_locked'] = $request->boolean('is_locked');

        return response()->json([
            'message' => 'Updated successfully',
            'data' => $this->branchServiceManagementRepository->updateEmergencyLock($branchId, $serviceId, $validated),
        ]);
    }

    public function updatePriceOverride(Request $request, int|string $branchId, int|string $serviceId): JsonResponse
    {
        // TODO: Authorize that the current user can update branch-specific service pricing.
        $validated = $request->validate([
            'override_price' => ['present', 'nullable', 'numeric', 'min:0'],
        ]);

        return response()->json([
            'message' => 'Updated successfully',
            'data' => $this->branchServiceManagementRepository->updatePriceOverride($branchId, $serviceId, $validated),
        ]);
    }

    private function validateFilters(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'period_type' => ['nullable', 'in:day,month,year'],
            'date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
            'service_group' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
        ]));
    }

    private function validatePeriod(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'period_type' => ['nullable', 'in:day,month,year'],
            'date' => ['nullable', 'date'],
        ]));
    }
}
