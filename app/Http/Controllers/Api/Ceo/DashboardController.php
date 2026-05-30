<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Ceo\DashboardRequest;
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
    public function currentHotelOccupancy(DashboardRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getCurrentHotelOccupancy($request->reportFilters())
        );
    }

    /**
     * 1.2. Tỷ lệ lấp đầy phòng theo kỳ và so sánh kỳ trước.
     */
    public function occupancyRate(DashboardRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getOccupancyRate($request->reportFilters())
        );
    }

    /**
     * 1.3. RevPAR - doanh thu trên mỗi phòng khả dụng.
     */
    public function revpar(DashboardRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRevpar($request->reportFilters())
        );
    }

    /**
     * 1.4. Xu hướng lượng khách theo thời gian.
     */
    public function customerTrend(DashboardRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getCustomerTrend($request->reportFilters())
        );
    }

    /**
     * 2.1. Tổng doanh thu toàn chuỗi và % tăng giảm so với kỳ trước.
     */
    public function chainRevenue(DashboardRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getTotalRevenue($request->reportFilters())
        );
    }

    /**
     * 2.2. COGS ước tính / chi phí vật tư tiêu hao ước tính.
     */
    public function estimatedCogs(DashboardRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getEstimatedCogs($request->reportFilters())
        );
    }

    /**
     * 2.3. Tỷ trọng doanh thu Hotel và Grooming/Spa.
     */
    public function revenueMix(DashboardRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRevenueMix($request->reportFilters())
        );
    }

    /**
     * 2.4. Xu hướng doanh thu và COGS ước tính theo thời gian.
     */
    public function revenueAndCogsTrend(DashboardRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRevenueAndCogsTrend($request->reportFilters())
        );
    }

    /**
     * 3.1. Doanh thu theo từng chi nhánh.
     */
    public function branchRevenue(DashboardRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getBranchRevenue($request->reportFilters())
        );
    }

    /**
     * 3.2. Top 3 chi nhánh có doanh thu cao nhất.
     */
    public function branchRanking(DashboardRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getBranchRanking($request->reportFilters())
        );
    }

    /**
     * 3.3. Top 5 dịch vụ được sử dụng nhiều nhất.
     */
    public function topUsedServices(DashboardRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getTopUsedServices($request->reportFilters())
        );
    }

    /**
     * 4.5. Tổng hợp tất cả cảnh báo rủi ro.
     */
    public function riskAlerts(DashboardRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRiskAlerts($request->reportFilters())
        );
    }

    /**
     * 5. Query tổng hợp nhanh cho backend debug.
     * Không nên dùng cho UI production.
     */
    public function debugSummary(DashboardRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getDebugSummary($request->reportFilters())
        );
    }
}
