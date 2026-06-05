<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Ceo\CeoFinanceRepositoryInterface;
use Illuminate\Http\JsonResponse;

class FinanceController extends ApiController
{
    /**
     * Alert thresholds can be tuned here without changing repository SQL.
     */
    private const NEGATIVE_BRANCH_PROFIT_THRESHOLD = 0.0;

    private const LOW_SERVICE_MARGIN_THRESHOLD = 20.0;

    private const HIGH_SERVICE_MARGIN_THRESHOLD = 10.0;

    private const COST_GROWTH_THRESHOLD = 20.0;

    private const HIGH_COST_GROWTH_THRESHOLD = 40.0;

    public function __construct(
        protected CeoFinanceRepositoryInterface $financeRepository
    ) {
    }

    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getFinanceData($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    public function totalChainRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getTotalChainRevenueCard($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    public function estimatedTotalCost(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getEstimatedTotalCostCard($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    public function estimatedProfit(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getEstimatedProfitCard($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    public function estimatedMargin(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getEstimatedMarginCard($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    public function trend(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getFinanceTrendChart($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    public function monthlyTrend(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getFinanceMonthlyTrendChart($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    public function costStructure(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getCostStructureChart($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    public function branchEstimatedProfit(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getBranchEstimatedProfitTable($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    public function serviceEstimatedProfit(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getServiceEstimatedProfitTable($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

    public function lowestMarginServices(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->financeRepository->getLowestMarginServicesTable($request->getFiltersArray()),
            errorMessage: 'Unable to load CEO finance data'
        );
    }

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
