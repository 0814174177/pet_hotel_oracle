<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\ReportFilterRequest;
use App\Repositories\Contracts\Ceo\ServiceRevenueRepositoryInterface;
use Illuminate\Http\JsonResponse;

class ServiceController extends ApiController
{
    public function __construct(
        protected ServiceRevenueRepositoryInterface $serviceRevenues
    ) {
    }

    public function summary(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->serviceRevenues->getServiceSummary($this->filters($request)),
        ]);
    }

    public function catalog(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->serviceRevenues->getServiceCatalog($this->filters($request)),
        ]);
    }

    public function index(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->serviceRevenues->getServiceRevenueList($this->filters($request)),
        ]);
    }

    public function highestRevenue(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->serviceRevenues->getHighestRevenueService($this->filters($request)),
        ]);
    }

    public function noActivity(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->serviceRevenues->getNoActivityServices($this->filters($request)),
        ]);
    }

    public function mostProfitable(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->serviceRevenues->getMostProfitableService($this->filters($request)),
        ]);
    }

    private function filters(ReportFilterRequest $request): array
    {
        return array_merge($request->reportFilters(), $request->validate([
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