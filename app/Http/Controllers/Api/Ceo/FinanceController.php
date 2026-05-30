<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\ReportFilterRequest;
use App\Repositories\Contracts\Ceo\CeoFinanceRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Throwable;

class FinanceController extends ApiController
{
    public function __construct(
        protected CeoFinanceRepositoryInterface $financeRepository
    ) {
    }

    public function index(ReportFilterRequest $request): JsonResponse
    {
        $filters = $request->reportFilters();

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
}
