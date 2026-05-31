<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Ceo\CeoDashboardRepositoryInterface;
use Illuminate\Http\JsonResponse;

class DashboardController extends ApiController
{
    public function __construct(
        protected CeoDashboardRepositoryInterface $dashboardRepository
    ) {
    }

    /**
     * Hàm trả response chuẩn cho toàn bộ dashboard.
     */
    private function respondData(mixed $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * 1.1. Tỷ lệ lấp đầy phòng hiện tại theo trạng thái ROOM.
     */
    public function currentHotelOccupancy(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getCurrentHotelOccupancy($request->getFiltersArray())
        );
    }

    /**
     * 1.2. Tỷ lệ lấp đầy phòng theo kỳ và so sánh kỳ trước.
     */
    public function occupancyRate(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getOccupancyRate($request->getFiltersArray())
        );
    }

    /**
     * 1.3. RevPAR - doanh thu trên mỗi phòng khả dụng.
     */
    public function revpar(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRevpar($request->getFiltersArray())
        );
    }

    /**
     * 1.4. Xu hướng lượng khách theo thời gian.
     */
    public function customerTrend(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getCustomerTrend($request->getFiltersArray())
        );
    }

    /**
     * 2.1. Tổng doanh thu toàn chuỗi và % tăng giảm so với kỳ trước.
     */
    public function chainRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getTotalRevenue($request->getFiltersArray())
        );
    }

    /**
     * 2.2. COGS ước tính / chi phí vật tư tiêu hao ước tính.
     */
    public function estimatedCogs(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getEstimatedCogs($request->getFiltersArray())
        );
    }

    /**
     * 2.3. Tỷ trọng doanh thu Hotel và Grooming/Spa.
     */
    public function revenueMix(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRevenueMix($request->getFiltersArray())
        );
    }

    /**
     * 2.4. Xu hướng doanh thu và COGS ước tính theo thời gian.
     */
    public function revenueAndCogsTrend(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRevenueAndCogsTrend($request->getFiltersArray())
        );
    }

    /**
     * 3.1. Doanh thu theo từng chi nhánh.
     */
    public function branchRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getBranchRevenue($request->getFiltersArray())
        );
    }

    /**
     * 3.2. Top 3 chi nhánh có doanh thu cao nhất.
     */
    public function branchRanking(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getBranchRanking($request->getFiltersArray())
        );
    }

    /**
     * 3.3. Top 5 dịch vụ được sử dụng nhiều nhất.
     */
    public function topUsedServices(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getTopUsedServices($request->getFiltersArray())
        );
    }

    /**
     * 4.5. Tổng hợp tất cả cảnh báo rủi ro.
     */
    public function riskAlerts(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRiskAlerts($request->getFiltersArray())
        );
    }

    /**
     * 5. Query tổng hợp nhanh cho backend debug.
     * Không nên dùng cho UI production.
     */
    public function debugSummary(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getDebugSummary($request->getFiltersArray())
        );
    }
}
