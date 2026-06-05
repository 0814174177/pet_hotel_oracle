<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Manager\ManagerDashboardRepositoryInterface;
use App\Services\Manager\ManagerBranchScopeService;
use Illuminate\Http\JsonResponse;

/**
 * Controller quản lý các API dành cho Bảng điều khiển (Dashboard) của cấp Quản lý (Manager).
 * Cung cấp luồng truy xuất dữ liệu tự động theo chi nhánh quản lý hiện tại hoặc theo ID chi nhánh cụ thể.
 */
class DashboardController extends ApiController
{
    /**
     * Khởi tạo DashboardController.
     *
     * @param ManagerDashboardRepositoryInterface $dashboardRepository Giao diện xử lý truy xuất dữ liệu Dashboard.
     * @param ManagerBranchScopeService $branchScope Service kiểm tra quyền và phạm vi chi nhánh của Manager.
     */
    public function __construct(
        protected ManagerDashboardRepositoryInterface $dashboardRepository,
        protected ManagerBranchScopeService $branchScope
    ) {
    }

    /**
     * Endpoint mặc định cho Dashboard. Tự động chuyển hướng xử lý sang overview.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc khoảng thời gian.
     * @return JsonResponse
     */
    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->overview($request);
    }

    /**
     * Lấy dữ liệu KPI tổng quan cho Manager dựa trên chi nhánh của người dùng hiện tại.
     * Ghi chú: Controller không chứa SQL và không trực tiếp tính toán KPI.
     *
     * @param DateRangeFilterRequest $request Chứa tham số thời gian bắt đầu, kết thúc và kỳ trước.
     * @return JsonResponse Trả về đối tượng JSON chứa dữ liệu KPI chi nhánh.
     */
    public function overview(DateRangeFilterRequest $request): JsonResponse
    {
        $data = $this->dashboardRepository->getOverview(
            $this->currentBranchId($request),
            $request->getFiltersArray()
        );

        return $this->respondData($data);
    }

    /**
     * Lấy dữ liệu KPI tổng quan cho Manager dựa trên ID chi nhánh cụ thể từ route.
     *
     * @param DateRangeFilterRequest $request Chứa tham số thời gian bắt đầu, kết thúc và kỳ trước.
     * @param int|string $branchId ID của chi nhánh (VD: từ route /branches/{branchId}/overview).
     * @return JsonResponse Trả về đối tượng JSON chứa dữ liệu KPI chi nhánh.
     */
    public function branchOverview(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->dashboardRepository->getOverview(
                $branchId,
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Lấy cảnh báo hàng tồn kho (số vật tư đã hết/sắp hết) dựa trên chi nhánh của người dùng hiện tại.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian chung của Dashboard.
     * @return JsonResponse Trả về đối tượng JSON chứa thông tin cảnh báo tồn kho.
     */
    public function inventoryWarning(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getInventoryWarning(
                $this->currentBranchId($request),
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Lấy cảnh báo hàng tồn kho (số vật tư đã hết/sắp hết) dựa trên ID chi nhánh cụ thể.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian chung của Dashboard.
     * @param int|string $branchId ID của chi nhánh.
     * @return JsonResponse Trả về đối tượng JSON chứa thông tin cảnh báo tồn kho.
     */
    public function branchInventoryWarning(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->dashboardRepository->getInventoryWarning(
                $branchId,
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Lấy cảnh báo y tế tạm thời (số lượng thú cưng cần theo dõi) dựa trên chi nhánh của người dùng hiện tại.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian chung của Dashboard.
     * @return JsonResponse Trả về đối tượng JSON chứa thông tin cảnh báo y tế.
     */
    public function healthWarning(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getHealthWarning(
                $this->currentBranchId($request),
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Lấy cảnh báo y tế tạm thời (số lượng thú cưng cần theo dõi) dựa trên ID chi nhánh cụ thể.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian chung của Dashboard.
     * @param int|string $branchId ID của chi nhánh.
     * @return JsonResponse Trả về đối tượng JSON chứa thông tin cảnh báo y tế.
     */
    public function branchHealthWarning(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->dashboardRepository->getHealthWarning(
                $branchId,
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Lấy cảnh báo rủi ro tài chính (số đơn hủy/hoàn và thất thoát) dựa trên chi nhánh của người dùng hiện tại.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian chung của Dashboard.
     * @return JsonResponse Trả về đối tượng JSON chứa thông tin rủi ro tài chính.
     */
    public function financialRiskWarning(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getFinancialRiskWarning(
                $this->currentBranchId($request),
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Lấy cảnh báo rủi ro tài chính (số đơn hủy/hoàn và thất thoát) dựa trên ID chi nhánh cụ thể.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian chung của Dashboard.
     * @param int|string $branchId ID của chi nhánh.
     * @return JsonResponse Trả về đối tượng JSON chứa thông tin rủi ro tài chính.
     */
    public function branchFinancialRiskWarning(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->dashboardRepository->getFinancialRiskWarning(
                $branchId,
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Lấy danh sách booking bị hủy sát giờ dựa trên chi nhánh của người dùng hiện tại.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian chung của Dashboard.
     * @return JsonResponse Trả về đối tượng JSON chứa số lượng và chi tiết các booking bị hủy.
     */
    public function lateCancelledBookings(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getLateCancelledBookings(
                $this->currentBranchId($request),
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Lấy danh sách booking bị hủy sát giờ dựa trên ID chi nhánh cụ thể.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian chung của Dashboard.
     * @param int|string $branchId ID của chi nhánh.
     * @return JsonResponse Trả về đối tượng JSON chứa số lượng và chi tiết các booking bị hủy.
     */
    public function branchLateCancelledBookings(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->dashboardRepository->getLateCancelledBookings(
                $branchId,
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Lấy danh sách Top 5 dịch vụ có doanh thu cao nhất dựa trên chi nhánh của người dùng hiện tại.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian chung của Dashboard.
     * @return JsonResponse Trả về đối tượng JSON chứa danh sách các dịch vụ đạt doanh thu cao.
     */
    public function topRevenueServices(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getTopRevenueServices(
                $this->currentBranchId($request),
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Lấy danh sách Top 5 dịch vụ có doanh thu cao nhất dựa trên ID chi nhánh cụ thể.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian chung của Dashboard.
     * @param int|string $branchId ID của chi nhánh.
     * @return JsonResponse Trả về đối tượng JSON chứa danh sách các dịch vụ đạt doanh thu cao.
     */
    public function branchTopRevenueServices(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->dashboardRepository->getTopRevenueServices(
                $branchId,
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Phân tích cơ cấu doanh thu (Pet Hotel, Grooming & Spa, Khác) dựa trên chi nhánh của người dùng hiện tại.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian chung của Dashboard.
     * @return JsonResponse Trả về đối tượng JSON chứa dữ liệu cơ cấu doanh thu.
     */
    public function revenueStructure(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRevenueStructure(
                $this->currentBranchId($request),
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Phân tích cơ cấu doanh thu (Pet Hotel, Grooming & Spa, Khác) dựa trên ID chi nhánh cụ thể.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian chung của Dashboard.
     * @param int|string $branchId ID của chi nhánh.
     * @return JsonResponse Trả về đối tượng JSON chứa dữ liệu cơ cấu doanh thu.
     */
    public function branchRevenueStructure(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->dashboardRepository->getRevenueStructure(
                $branchId,
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Lấy danh sách hóa đơn chưa thanh toán đủ (công nợ) dựa trên chi nhánh của người dùng hiện tại.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian chung của Dashboard.
     * @return JsonResponse Trả về đối tượng JSON chứa tổng nợ và danh sách các hóa đơn nợ.
     */
    public function unpaidInvoices(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getUnpaidInvoices(
                $this->currentBranchId($request),
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Lấy danh sách hóa đơn chưa thanh toán đủ (công nợ) dựa trên ID chi nhánh cụ thể.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian chung của Dashboard.
     * @param int|string $branchId ID của chi nhánh.
     * @return JsonResponse Trả về đối tượng JSON chứa tổng nợ và danh sách các hóa đơn nợ.
     */
    public function branchUnpaidInvoices(DateRangeFilterRequest $request, int|string $branchId): JsonResponse
    {
        $branchId = $this->branchScope->ensureCanAccessBranch((int) $branchId);

        return $this->respondData(
            $this->dashboardRepository->getUnpaidInvoices(
                $branchId,
                $request->getFiltersArray()
            )
        );
    }

    /**
     * Trích xuất ID chi nhánh hiện hành của Manager.
     * Trả về lỗi 403 (Abort) nếu Manager không có quyền hạn hợp lệ đối với chi nhánh.
     *
     * @param DateRangeFilterRequest $request Dữ liệu Request.
     * @return int ID của chi nhánh quản lý hiện tại.
     */
    private function currentBranchId(DateRangeFilterRequest $request): int
    {
        return $this->branchScope->currentBranchId();
    }
}