<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Ceo\ServiceRevenueRepositoryInterface;
use Illuminate\Http\JsonResponse;

class ServiceController extends ApiController
{
    public function __construct(
        protected ServiceRevenueRepositoryInterface $serviceRevenues
    ) {
    }

    public function summary(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->serviceRevenues->getServiceSummary($this->filters($request)),
        ]);
    }

    public function catalog(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->serviceRevenues->getServiceCatalog($this->filters($request)),
        ]);
    }

    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->serviceRevenues->getServiceRevenueList($this->filters($request)),
        ]);
    }

    public function highestRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->serviceRevenues->getHighestRevenueService($this->filters($request)),
        ]);
    }

    public function lowestRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->serviceRevenues->getLowestRevenueService($this->filters($request)),
        ]);
    }

    public function noActivity(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->serviceRevenues->getNoActivityServices($this->filters($request)),
        ]);
    }

    public function mostProfitable(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->serviceRevenues->getMostProfitableService($this->filters($request)),
        ]);
    }

    private function filters(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'status' => ['nullable', 'string', 'max:50'],
            'min_revenue' => ['nullable', 'numeric', 'min:0'],
            'max_revenue' => ['nullable', 'numeric', 'min:0'],
            'min_coverage_rate' => ['nullable', 'numeric', 'min:0'],
            'max_coverage_rate' => ['nullable', 'numeric', 'min:0'],
            'min_served_count' => ['nullable', 'integer', 'min:0'],
            'max_served_count' => ['nullable', 'integer', 'min:0'],
            'sort_by' => ['nullable', 'string', 'in:service_id,service_name,revenue,revenue_share,served_count_30_days,coverage_rate,status'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ]));
    }
}
