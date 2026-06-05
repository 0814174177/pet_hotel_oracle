<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Manager\BranchScopedServiceManagementRepositoryInterface;
use App\Services\Manager\ManagerBranchScopeService;
use Illuminate\Http\JsonResponse;

/**
 * Controller quản lý các API liên quan đến quản trị dịch vụ và theo dõi hiệu suất
 * dành riêng cho cấp Quản lý (Manager) theo phạm vi chi nhánh được phân quyền.
 */
class ServiceManagementController extends ApiController
{
    /**
     * Khởi tạo ServiceManagementController.
     *
     * @param BranchScopedServiceManagementRepositoryInterface $branchServiceManagementRepository Repository xử lý dữ liệu dịch vụ.
     * @param ManagerBranchScopeService $branchScope Service kiểm tra quyền và phạm vi chi nhánh của Manager.
     */
    public function __construct(
        protected BranchScopedServiceManagementRepositoryInterface $branchServiceManagementRepository,
        protected ManagerBranchScopeService $branchScope
    ) {
    }

    /**
     * Lấy dữ liệu tổng quan về tình hình quản lý dịch vụ tại chi nhánh.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian và các điều kiện lọc mở rộng.
     * @param int|string $branchId ID của chi nhánh (từ route).
     * @return JsonResponse Trả về JSON chứa dữ liệu tổng quan.
     */
    public function index(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);
        $filters = $this->validateFilters($request);

        return $this->respondData(
            $this->branchServiceManagementRepository->getOverview($branchId, $filters)
        );
    }

    /**
     * Truy xuất các thẻ chỉ số KPI tổng quan về dịch vụ của chi nhánh.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian chu kỳ.
     * @param int|string $branchId ID của chi nhánh (từ route).
     * @return JsonResponse Trả về JSON chứa các thông số KPI.
     */
    public function kpi(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);
        $filters = $this->validatePeriod($request);

        return $this->respondData(
            $this->branchServiceManagementRepository->getKpiCards($branchId, $filters)
        );
    }

    /**
     * Lấy KPI tiến độ đạt mục tiêu doanh thu dịch vụ của chi nhánh trong kỳ lọc.
     * Bao gồm: Doanh thu hiện tại, chỉ tiêu, phần trăm hoàn thành, mức tăng/giảm và cảnh báo.
     * Ghi chú: Controller không chứa SQL và không trực tiếp tính KPI, chỉ truyền filters.
     *
     * @param DateRangeFilterRequest $request Tự xử lý start_date, end_date và kỳ trước.
     * @param int|string $branchId ID của chi nhánh (từ route).
     * @return JsonResponse Trả về JSON tiến độ doanh thu dịch vụ.
     */
    public function revenueProgress(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);
        $filters = array_merge($request->getFiltersArray(), $request->validate([
            'target_service_revenue' => ['nullable', 'numeric', 'min:0'],
        ]));

        return $this->respondData(
            $this->branchServiceManagementRepository->getRevenueProgress(
                $branchId,
                $filters
            )
        );
    }

    /**
     * Lấy danh sách cảnh báo các dịch vụ có doanh thu giảm sút mạnh tại chi nhánh.
     *
     * @param DateRangeFilterRequest $request Tự xử lý start_date, end_date và kỳ trước.
     * @param int|string $branchId ID của chi nhánh (từ route).
     * @return JsonResponse JSON chứa danh sách cảnh báo, tóm tắt và mức độ cảnh báo (warning_level).
     */
    public function revenueDropAlerts(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchServiceManagementRepository->getRevenueDropAlerts(
                $branchId,
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Lấy KPI tỷ lệ bán chéo (upsell) dịch vụ kèm theo các booking phòng tại chi nhánh.
     * Bao gồm: Số lượng booking phòng, số booking có sử dụng dịch vụ, tỷ lệ upsell và đánh giá hiệu suất.
     *
     * @param DateRangeFilterRequest $request Tự xử lý start_date, end_date và kỳ trước.
     * @param int|string $branchId ID của chi nhánh (từ route).
     * @return JsonResponse Trả về JSON chứa KPI tỷ lệ upsell.
     */
    public function upsellRate(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchServiceManagementRepository->getUpsellRate(
                $branchId,
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Lấy danh sách chi tiết các dịch vụ để phục vụ công tác quản trị tại chi nhánh.
     *
     * @param DateRangeFilterRequest $request Chứa các bộ lọc thời gian và dữ liệu khác.
     * @param int|string $branchId ID của chi nhánh (từ route).
     * @param string|null $search Từ khóa tìm kiếm dịch vụ (truyền qua URL path).
     * @param string|null $serviceGroup Bộ lọc nhóm dịch vụ (truyền qua URL path).
     * @param string|null $status Bộ lọc trạng thái dịch vụ (truyền qua URL path).
     * @return JsonResponse Trả về JSON chứa danh sách các dịch vụ đã được chuẩn hóa.
     */
    public function services(
        DateRangeFilterRequest $request,
        int|string $branchId,
        ?string $search = null,
        ?string $serviceGroup = null,
        ?string $status = null
    ): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);
        $filters = $this->validateFilters($request);
        
        $filters['search'] = $this->pathFilterValue($search) ?? ($filters['search'] ?? null);
        $filters['service_group'] = $this->pathFilterValue($serviceGroup) ?? ($filters['service_group'] ?? null);
        $filters['status'] = $this->pathFilterValue($status) ?? ($filters['status'] ?? null);

        return $this->respondData(
            $this->branchServiceManagementRepository->getServiceList($branchId, $filters)
        );
    }

    /**
     * Hợp nhất và xác thực các điều kiện lọc chung (tìm kiếm, nhóm, trạng thái...).
     *
     * @param DateRangeFilterRequest $request Request chứa dữ liệu đầu vào.
     * @return array Mảng chứa các quy tắc lọc đã được xác thực an toàn.
     */
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

    /**
     * Chuyển đổi và chuẩn hóa giá trị filter từ đường dẫn URL (Path) để truyền vào Repository.
     * Ký tự "_" hoặc chuỗi rỗng sẽ được xử lý thành null để hỗ trợ DashboardEngine giữ nguyên query GET.
     *
     * @param string|null $value Giá trị trên đường dẫn URL.
     * @return string|null Chuỗi filter đã được làm sạch (trim) hoặc null.
     */
    private function pathFilterValue(?string $value): ?string
    {
        if ($value === null || $value === '_') {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Xác thực các tham số bộ lọc liên quan đến chu kỳ thời gian (kỳ lọc, ngày cụ thể).
     *
     * @param DateRangeFilterRequest $request Request chứa dữ liệu chu kỳ.
     * @return array Mảng chứa thông tin chu kỳ đã được xác thực.
     */
    private function validatePeriod(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'period_type' => ['nullable', 'in:day,month,year'],
            'date' => ['nullable', 'date'],
        ]));
    }
}