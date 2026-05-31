<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Ceo\BranchNetworkRepositoryInterface;
use Illuminate\Http\JsonResponse;

class BranchController extends ApiController
{
    public function __construct(
        protected BranchNetworkRepositoryInterface $branches
    ) {
    }

    public function activeCount(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->branches->getActiveBranchCount($this->filters($request)),
        ]);
    }

    public function highestRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->branches->getHighestRevenueBranch($this->filters($request)),
        ]);
    }

    public function lowestOccupancy(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->branches->getLowestOccupancyBranch($this->filters($request)),
        ]);
    }

    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->branches->getBranchNetworkList($this->filters($request)),
        ]);
    }

    private function filters(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
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
