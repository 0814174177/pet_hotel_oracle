<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Manager\BranchScopedInventoryMaterialRepositoryInterface;
use Illuminate\Http\JsonResponse;

class InventoryManagementController extends Controller
{
    public function __construct(
        protected BranchScopedInventoryMaterialRepositoryInterface $branchInventoryMaterialRepository
    ) {
    }

    /**
     * Mo ta chuc nang:
     * Lay tong hop dashboard vat tu ton kho cua chi nhanh hien tai.
     *
     * Input:
     * - DateRangeFilterRequest $request: Bo loc ngay va bo loc danh sach.
     * - int|string $branchId: Ma chi nhanh tu route.
     *
     * Output:
     * - JSON { success: true, data: { kpi, materials } }.
     *
     * Ghi chu:
     * - Controller chi truyen filter xuong Repository, khong xu ly SQL.
     */
    public function index(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view inventory materials for this branch.
        return response()->json([
            'success' => true,
            'data' => $this->branchInventoryMaterialRepository->getDashboard($branchId, $this->filters($request)),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay cac KPI ton kho cua chi nhanh hien tai.
     *
     * Input:
     * - DateRangeFilterRequest $request: Bo loc ngay tu DashboardEngine.
     * - int|string $branchId: Ma chi nhanh tu route.
     *
     * Output:
     * - JSON { success: true, data: { total_materials, out_of_stock_count,
     *   low_stock_count, inventory_value, margin_percent_assumption,
     *   inventory_warning, warning_level, material_type_comparison, cards } }.
     *
     * Ghi chu:
     * - Controller chi truyen branchId va filters xuong Repository, khong xu ly SQL.
     */
    public function kpi(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view inventory material KPI cards for this branch.
        return response()->json([
            'success' => true,
            'data' => $this->branchInventoryMaterialRepository->getInventoryKpi($branchId, $this->filters($request)),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach vat tu ton kho cua chi nhanh hien tai.
     *
     * Input:
     * - DateRangeFilterRequest $request: Bo loc ngay va bo loc danh sach.
     * - int|string $branchId: Ma chi nhanh tu route.
     *
     * Output:
     * - JSON { success: true, data: [...] }.
     */
    public function materials(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view inventory material list data for this branch.
        return response()->json([
            'success' => true,
            'data' => $this->branchInventoryMaterialRepository->getMaterialsByBranch($branchId, $this->filters($request)),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach vat tu het hang can nhap ngay theo chi nhanh.
     *
     * Input:
     * - DateRangeFilterRequest $request: Bo loc ngay tu DashboardEngine.
     * - int|string $branchId: Ma chi nhanh tu route.
     *
     * Output:
     * - JSON { success: true, data: [...] }.
     */
    public function outOfStock(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view out-of-stock materials for this branch.
        return response()->json([
            'success' => true,
            'data' => $this->branchInventoryMaterialRepository->getOutOfStockMaterials($branchId, $this->filters($request)),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach vat tu sap het theo chi nhanh.
     *
     * Input:
     * - DateRangeFilterRequest $request: Bo loc ngay tu DashboardEngine.
     * - int|string $branchId: Ma chi nhanh tu route.
     *
     * Output:
     * - JSON { success: true, data: [...] }.
     */
    public function lowStock(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view low-stock materials for this branch.
        return response()->json([
            'success' => true,
            'data' => $this->branchInventoryMaterialRepository->getLowStockMaterials($branchId, $this->filters($request)),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Lay von ton kho theo nhom vat tu cua chi nhanh.
     *
     * Input:
     * - DateRangeFilterRequest $request: Bo loc ngay tu DashboardEngine.
     * - int|string $branchId: Ma chi nhanh tu route.
     *
     * Output:
     * - JSON { success: true, data: [...] }.
     */
    public function inventoryValueByCategory(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        // TODO: Authorize that the current user can view inventory value by category for this branch.
        return response()->json([
            'success' => true,
            'data' => $this->branchInventoryMaterialRepository->getInventoryValueByCategory($branchId, $this->filters($request)),
        ]);
    }

    public function show(int|string $branchId, int|string $materialId): JsonResponse
    {
        // TODO: Authorize that the current user can view this material detail for the branch.
        return response()->json([
            'data' => $this->branchInventoryMaterialRepository->findMaterialDetail($branchId, $materialId),
        ]);
    }

    /**
     * Mo ta chuc nang:
     * Gop bo loc ngay tu DateRangeFilterRequest voi bo loc danh sach vat tu.
     *
     * Input:
     * - DateRangeFilterRequest $request: Request API inventory.
     *
     * Output:
     * - Mang filter truyen truc tiep xuong Repository.
     */
    private function filters(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'period' => ['nullable', 'string', 'in:day,month,year'],
            'date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
            'group' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
        ]));
    }
}
