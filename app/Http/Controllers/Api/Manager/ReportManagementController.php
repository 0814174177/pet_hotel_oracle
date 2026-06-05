<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Manager\BranchScopedRevenueReportRepositoryInterface;
use App\Services\Manager\ManagerBranchScopeService;
use Illuminate\Http\JsonResponse;

/**
 * Controller quản lý các API liên quan đến báo cáo phân tích doanh thu
 * dành riêng cho cấp Quản lý (Manager) dựa trên phạm vi chi nhánh được phân quyền.
 */
class ReportManagementController extends ApiController
{
    /**
     * Khởi tạo ReportManagementController.
     *
     * @param BranchScopedRevenueReportRepositoryInterface $branchRevenueReportRepository Repository xử lý dữ liệu báo cáo doanh thu.
     * @param ManagerBranchScopeService $branchScope Service xử lý phân quyền và kiểm tra phạm vi chi nhánh.
     */
    public function __construct(
        protected BranchScopedRevenueReportRepositoryInterface $branchRevenueReportRepository,
        protected ManagerBranchScopeService $branchScope
    ) {
    }

    /**
     * Endpoint truy xuất toàn bộ dữ liệu báo cáo doanh thu cho Dashboard.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @param int|string $branchId ID của chi nhánh (truyền từ route).
     * @return JsonResponse Trả về đối tượng JSON chứa tổng hợp báo cáo.
     */
    public function index(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchRevenueReportRepository->getDashboard($branchId, $this->filters($request))
        );
    }

    /**
     * Lấy tiến độ hoàn thành mục tiêu doanh thu (Target Progress) trong tháng của chi nhánh.
     *
     * Ghi chú: Controller không chứa SQL, chỉ truyền `$branchId` và `filters` xuống Repository.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian (start_date, end_date và kỳ trước).
     * @param int|string $branchId ID của chi nhánh.
     * @return JsonResponse Trả về đối tượng JSON chứa doanh thu thực tế, mục tiêu, tiến độ (%) và cảnh báo.
     */
    public function targetProgress(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchRevenueReportRepository->getTargetProgress($branchId, $this->filters($request))
        );
    }

    /**
     * Lấy dữ liệu biểu đồ so sánh doanh thu theo từng ngày giữa kỳ này và kỳ trước của chi nhánh.
     *
     * Ghi chú: Controller không chứa SQL, chỉ gọi Repository.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian (start_date, end_date, prev_start_date).
     * @param int|string $branchId ID của chi nhánh.
     * @return JsonResponse Trả về đối tượng JSON chứa dãy doanh thu theo ngày của kỳ hiện tại và kỳ trước.
     */
    public function revenueComparison(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchRevenueReportRepository->getRevenueComparisonChart($branchId, $this->filters($request))
        );
    }

    /**
     * Phân tích cơ cấu doanh thu theo từng nhóm dịch vụ (Service Mix) tại chi nhánh.
     *
     * Ghi chú: Controller không chứa SQL, chỉ gọi Repository.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @param int|string $branchId ID của chi nhánh.
     * @return JsonResponse Trả về đối tượng JSON chứa tên nhóm dịch vụ, doanh thu và tỷ trọng (%).
     */
    public function serviceMix(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchRevenueReportRepository->getServiceMix($branchId, $this->filters($request))
        );
    }

    /**
     * Thống kê Giá trị trung bình đơn hàng (AOV), tổng doanh thu và tổng số đơn hàng của chi nhánh.
     *
     * Ghi chú: Controller không chứa SQL, chỉ gọi Repository.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian hiện tại và kỳ trước.
     * @param int|string $branchId ID của chi nhánh.
     * @return JsonResponse Trả về đối tượng JSON chứa AOV, số lượng đơn và mức độ tăng trưởng (growth).
     */
    public function aovSummary(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchRevenueReportRepository->getAovSummary($branchId, $this->filters($request))
        );
    }

    /**
     * Đánh giá và xếp hạng hiệu suất làm việc của nhân viên (Employee Performance) tại chi nhánh.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @param int|string $branchId ID của chi nhánh.
     * @return JsonResponse Trả về đối tượng JSON chứa danh sách nhân viên cùng doanh thu và số đơn họ xử lý.
     */
    public function employeePerformance(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchRevenueReportRepository->getEmployeePerformance($branchId, $this->filters($request))
        );
    }

    /**
     * Phân tích tỷ lệ giữ chân khách hàng (Customer Retention) tại chi nhánh.
     * Đánh giá tỷ lệ doanh thu/số đơn đến từ khách hàng mới so với khách hàng quay lại.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @param int|string $branchId ID của chi nhánh.
     * @return JsonResponse Trả về đối tượng JSON chứa phân tích dữ liệu khách hàng mới/quay lại.
     */
    public function customerRetention(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->branchRevenueReportRepository->getCustomerRetention($branchId, $this->filters($request))
        );
    }

    /**
     * Hợp nhất bộ lọc thời gian mặc định với các tham số điều kiện hiển thị mở rộng.
     *
     * @param DateRangeFilterRequest $request Request chứa dữ liệu đầu vào.
     * @return array Trả về mảng chứa các tham số lọc đã được xác thực an toàn.
     */
    private function filters(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'period' => ['nullable', 'string', 'in:day,month,year'],
            'date' => ['nullable', 'date'],
        ]));
    }
}