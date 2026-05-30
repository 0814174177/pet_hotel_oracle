<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\ReportFilterRequest;
use App\Repositories\Contracts\Ceo\BranchNetworkRepositoryInterface;
use Illuminate\Http\JsonResponse;

class BranchController extends ApiController
{
    public function __construct(
        protected BranchNetworkRepositoryInterface $branches
    ) {
    }

    public function activeCount(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->branches->getActiveBranchCount($this->filters($request)),
        ]);
    }

    public function highestRevenue(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->branches->getHighestRevenueBranch($this->filters($request)),
        ]);
    }

    public function lowestOccupancy(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->branches->getLowestOccupancyBranch($this->filters($request)),
        ]);
    }

    public function index(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->branches->getBranchNetworkList($this->filters($request)),
        ]);
    }

    private function filters(ReportFilterRequest $request): array
    {
        return array_merge($request->reportFilters(), $request->validate([
            'region' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'min_revenue' => ['nullable', 'numeric', 'min:0'],
            'max_revenue' => ['nullable', 'numeric', 'min:0'],
            'min_occupancy_rate' => ['nullable', 'numeric', 'min:0'],
            'max_occupancy_rate' => ['nullable', 'numeric', 'min:0'],
            'sort_by' => ['nullable', 'string', 'in:branch_name,region,revenue,occupancy_rate,status'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ]));
    }
}
