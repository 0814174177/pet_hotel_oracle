<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Ceo\CeoFinanceRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Controller quản lý các API liên quan đến số liệu tài chính và cảnh báo rủi ro
 * dành riêng cho cấp bậc Giám đốc điều hành (CEO).
 */
class FinanceController extends ApiController
{
    /**
     * Các ngưỡng cảnh báo có thể được điều chỉnh tại đây mà không cần thay đổi câu truy vấn SQL trong Repository.
     * * Ngưỡng cảnh báo chi nhánh có mức lợi nhuận âm.
     */
    private const NEGATIVE_BRANCH_PROFIT_THRESHOLD = 0.0;

    /**
     * Ngưỡng cảnh báo biên lợi nhuận dịch vụ chạm mức thấp.
     */
    private const LOW_SERVICE_MARGIN_THRESHOLD = 20.0;

    /**
     * Ngưỡng cảnh báo biên lợi nhuận dịch vụ chạm mức rủi ro cao.
     */
    private const HIGH_SERVICE_MARGIN_THRESHOLD = 10.0;

    /**
     * Ngưỡng cảnh báo tốc độ tăng trưởng chi phí vượt mức bình thường.
     */
    private const COST_GROWTH_THRESHOLD = 20.0;

    /**
     * Ngưỡng cảnh báo tốc độ tăng trưởng chi phí ở mức độ nguy hiểm.
     */
    private const HIGH_COST_GROWTH_THRESHOLD = 40.0;

    /**
     * Khởi tạo FinanceController.
     *
     * @param CeoFinanceRepositoryInterface $financeRepository Interface xử lý truy xuất dữ liệu tài chính.
     */
    public function __construct(
        protected CeoFinanceRepositoryInterface $financeRepository
    ) {
    }

    /**
     * Lấy dữ liệu tổng quan về tình hình tài chính.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @return JsonResponse
     */
    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getFinanceData($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    /**
     * Lấy số liệu hiển thị thẻ (Card) Tổng doanh thu toàn chuỗi.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @return JsonResponse
     */
    public function totalChainRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getTotalChainRevenueCard($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    /**
     * Lấy số liệu hiển thị thẻ (Card) Ước tính tổng chi phí.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @return JsonResponse
     */
    public function estimatedTotalCost(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getEstimatedTotalCostCard($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    /**
     * Lấy số liệu hiển thị thẻ (Card) Ước tính tổng lợi nhuận.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @return JsonResponse
     */
    public function estimatedProfit(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getEstimatedProfitCard($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    /**
     * Lấy số liệu hiển thị thẻ (Card) Ước tính biên lợi nhuận.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @return JsonResponse
     */
    public function estimatedMargin(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getEstimatedMarginCard($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    /**
     * Lấy dữ liệu phục vụ biểu đồ xu hướng tài chính chung.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @return JsonResponse
     */
    public function trend(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getFinanceTrendChart($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    /**
     * Lấy dữ liệu phục vụ biểu đồ xu hướng tài chính chi tiết theo từng tháng.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @return JsonResponse
     */
    public function monthlyTrend(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getFinanceMonthlyTrendChart($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    /**
     * Lấy dữ liệu phục vụ biểu đồ cơ cấu phân bổ chi phí.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @return JsonResponse
     */
    public function costStructure(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getCostStructureChart($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    /**
     * Lấy bảng thống kê ước tính lợi nhuận chi tiết theo từng chi nhánh.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @return JsonResponse
     */
    public function branchEstimatedProfit(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getBranchEstimatedProfitTable($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    /**
     * Lấy bảng thống kê ước tính lợi nhuận chi tiết theo từng loại dịch vụ.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @return JsonResponse
     */
    public function serviceEstimatedProfit(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getServiceEstimatedProfitTable($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    /**
     * Lấy danh sách các dịch vụ có mức biên lợi nhuận thấp nhất trong hệ thống.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @return JsonResponse
     */
    public function lowestMarginServices(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getLowestMarginServicesTable($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    /**
     * Truy xuất các cảnh báo rủi ro đối với những chi nhánh có lợi nhuận chạm mức âm.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @return JsonResponse
     */
    public function negativeBranchProfitAlerts(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getNegativeBranchProfitAlerts(
                $request->getFiltersArray(),
                self::NEGATIVE_BRANCH_PROFIT_THRESHOLD
            ),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    /**
     * Truy xuất các cảnh báo rủi ro đối với những dịch vụ có biên lợi nhuận thấp.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @return JsonResponse
     */
    public function lowServiceMarginAlerts(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getLowServiceMarginAlerts(
                $request->getFiltersArray(),
                self::LOW_SERVICE_MARGIN_THRESHOLD,
                self::HIGH_SERVICE_MARGIN_THRESHOLD
            ),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    /**
     * Truy xuất các cảnh báo rủi ro khi tốc độ tăng trưởng chi phí vượt mức kiểm soát.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian.
     * @return JsonResponse
     */
    public function costGrowthAlerts(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getCostGrowthAlerts(
                $request->getFiltersArray(),
                self::COST_GROWTH_THRESHOLD,
                self::HIGH_COST_GROWTH_THRESHOLD
            ),
            errorMessage: 'Unable to load CEO finance data'
        );
    }
}