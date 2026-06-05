<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Manager\BranchScopedInventoryMaterialRepositoryInterface;
use App\Services\Manager\ManagerBranchScopeService;
use Illuminate\Http\JsonResponse;

/**
 * Controller quản lý các API liên quan đến vật tư và hàng tồn kho
 * dành riêng cho cấp Quản lý (Manager) theo phạm vi chi nhánh được phân quyền.
 */
class InventoryManagementController extends ApiController
{
    /**
     * Khởi tạo InventoryManagementController.
     *
     * @param BranchScopedInventoryMaterialRepositoryInterface $branchInventoryMaterialRepository Repository xử lý dữ liệu vật tư tồn kho.
     * @param ManagerBranchScopeService $branchScope Service xử lý phân quyền và xác định phạm vi chi nhánh.
     */
    public function __construct(
        protected BranchScopedInventoryMaterialRepositoryInterface $branchInventoryMaterialRepository,
        protected ManagerBranchScopeService $branchScope
    ) {
    }

    /**
     * Lấy dữ liệu tổng hợp cho Dashboard vật tư tồn kho của chi nhánh hiện tại.
     * Controller chỉ truyền bộ lọc xuống Repository, không xử lý logic SQL.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian và danh sách.
     * @param int|string $branchId Mã chi nhánh truyền từ route.
     * @return JsonResponse Trả về đối tượng JSON chứa { kpi, materials }.
     */
    public function index(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchInventoryMaterialRepository->getDashboard($branchId, $this->filters($request))
        );
    }

    /**
     * Lấy các chỉ số KPI về tồn kho của chi nhánh hiện tại.
     * Bao gồm: Tổng vật tư, số lượng hết hàng/sắp hết, giá trị tồn kho, cảnh báo, v.v.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian từ DashboardEngine.
     * @param int|string $branchId Mã chi nhánh truyền từ route.
     * @return JsonResponse Trả về đối tượng JSON chứa các chỉ số KPI tồn kho.
     */
    public function kpi(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchInventoryMaterialRepository->getInventoryKpi($branchId, $this->filters($request))
        );
    }

    /**
     * Lấy danh sách vật tư đang tồn kho của chi nhánh hiện tại.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian và bộ lọc danh sách.
     * @param int|string $branchId Mã chi nhánh truyền từ route.
     * @return JsonResponse Trả về đối tượng JSON chứa danh sách vật tư.
     */
    public function materials(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchInventoryMaterialRepository->getMaterialsByBranch($branchId, $this->filters($request))
        );
    }

    /**
     * Lấy danh sách các vật tư đã hết hàng và cần nhập kho khẩn cấp theo chi nhánh.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian từ DashboardEngine.
     * @param int|string $branchId Mã chi nhánh truyền từ route.
     * @return JsonResponse Trả về đối tượng JSON chứa danh sách vật tư hết hàng.
     */
    public function outOfStock(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchInventoryMaterialRepository->getOutOfStockMaterials($branchId, $this->filters($request))
        );
    }

    /**
     * Lấy danh sách các vật tư sắp hết hàng (dưới ngưỡng an toàn) theo chi nhánh.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian từ DashboardEngine.
     * @param int|string $branchId Mã chi nhánh truyền từ route.
     * @return JsonResponse Trả về đối tượng JSON chứa danh sách vật tư sắp hết.
     */
    public function lowStock(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchInventoryMaterialRepository->getLowStockMaterials($branchId, $this->filters($request))
        );
    }

    /**
     * Lấy thống kê giá trị vốn tồn kho phân bổ theo từng nhóm vật tư của chi nhánh.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian từ DashboardEngine.
     * @param int|string $branchId Mã chi nhánh truyền từ route.
     * @return JsonResponse Trả về đối tượng JSON chứa dữ liệu giá trị tồn kho theo nhóm.
     */
    public function inventoryValueByCategory(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchInventoryMaterialRepository->getInventoryValueByCategory($branchId, $this->filters($request))
        );
    }

    /**
     * Lấy thông tin chi tiết của một vật tư cụ thể tại chi nhánh.
     *
     * @param int|string $branchId Mã chi nhánh.
     * @param int|string $materialId Mã vật tư cần xem chi tiết.
     * @return JsonResponse Trả về đối tượng JSON chứa chi tiết vật tư.
     */
    public function show(int|string $branchId, int|string $materialId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchInventoryMaterialRepository->findMaterialDetail($branchId, $materialId)
        );
    }

    /**
     * Hợp nhất và xác thực các điều kiện lọc (tìm kiếm, nhóm, trạng thái...)
     * để truyền trực tiếp xuống Repository xử lý.
     *
     * @param DateRangeFilterRequest $request Request chứa dữ liệu đầu vào.
     * @return array Mảng chứa các quy tắc lọc đã được xác thực an toàn.
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