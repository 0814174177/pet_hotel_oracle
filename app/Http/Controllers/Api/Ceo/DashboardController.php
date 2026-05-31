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

    private function respondData(mixed $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function currentHotelOccupancy(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getCurrentHotelOccupancy($request->getFiltersArray())
        );
    }

    public function occupancyRate(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getOccupancyRate($request->getFiltersArray())
        );
    }

    public function revpar(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRevpar($request->getFiltersArray())
        );
    }

    public function customerTrend(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getCustomerTrend($request->getFiltersArray())
        );
    }

    public function chainRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getTotalRevenue($request->getFiltersArray())
        );
    }

    public function estimatedCogs(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getEstimatedCogs($request->getFiltersArray())
        );
    }

    /**
     * Get the estimated cost structure chart for the selected date range.
     *
     * Input:
     * - DateRangeFilterRequest provides start_date and end_date.
     *
     * Output:
     * - JSON response through respondData().
     */
    public function costStructure(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getCostStructure($request->getFiltersArray())
        );
    }

    public function revenueMix(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRevenueMix($request->getFiltersArray())
        );
    }

    public function revenueAndCogsTrend(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRevenueAndCogsTrend($request->getFiltersArray())
        );
    }

    public function branchRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getBranchRevenue($request->getFiltersArray())
        );
    }

    public function branchRanking(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getBranchRanking($request->getFiltersArray())
        );
    }

    public function topUsedServices(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getTopUsedServices($request->getFiltersArray())
        );
    }

    public function riskAlerts(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRiskAlerts($request->getFiltersArray())
        );
    }
}
