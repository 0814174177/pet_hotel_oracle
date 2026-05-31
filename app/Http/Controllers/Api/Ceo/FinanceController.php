<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Ceo\CeoFinanceRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Throwable;

class FinanceController extends ApiController
{
    public function __construct(
        protected CeoFinanceRepositoryInterface $financeRepository
    ) {
    }

    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        $filters = $request->getFiltersArray();

        try {
            return response()->json([
                'success' => true,
                'message' => 'CEO finance data loaded successfully',
                'data' => $this->financeRepository->getFinanceData($filters),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load CEO finance data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function totalChainRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        $filters = $request->getFiltersArray();

        try {
            return response()->json([
                'success' => true,
                'message' => 'CEO total chain revenue loaded successfully',
                'data' => $this->financeRepository->getTotalChainRevenueCard($filters),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load CEO total chain revenue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function estimatedTotalCost(DateRangeFilterRequest $request): JsonResponse
    {
        $filters = $request->getFiltersArray();

        try {
            return response()->json([
                'success' => true,
                'message' => 'CEO estimated total cost loaded successfully',
                'data' => $this->financeRepository->getEstimatedTotalCostCard($filters),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load CEO estimated total cost',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function estimatedProfit(DateRangeFilterRequest $request): JsonResponse
    {
        $filters = $request->getFiltersArray();

        try {
            return response()->json([
                'success' => true,
                'message' => 'CEO estimated profit loaded successfully',
                'data' => $this->financeRepository->getEstimatedProfitCard($filters),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load CEO estimated profit',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function estimatedMargin(DateRangeFilterRequest $request): JsonResponse
    {
        $filters = $request->getFiltersArray();

        try {
            return response()->json([
                'success' => true,
                'message' => 'CEO estimated margin loaded successfully',
                'data' => $this->financeRepository->getEstimatedMarginCard($filters),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load CEO estimated margin',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function trend(DateRangeFilterRequest $request): JsonResponse
    {
        $filters = $request->getFiltersArray();

        try {
            return response()->json([
                'success' => true,
                'message' => 'CEO finance trend loaded successfully',
                'data' => $this->financeRepository->getFinanceTrendChart($filters),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load CEO finance trend',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function monthlyTrend(DateRangeFilterRequest $request): JsonResponse
    {
        $filters = $request->getFiltersArray();

        try {
            return response()->json([
                'success' => true,
                'message' => 'CEO finance monthly trend loaded successfully',
                'data' => $this->financeRepository->getFinanceMonthlyTrendChart($filters),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load CEO finance monthly trend',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
