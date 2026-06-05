<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Models\BranchInventory;
use App\Services\Manager\ManagerBranchScopeService;
use Illuminate\Http\JsonResponse;

class InventoryController extends ApiController
{
    public function __construct(
        protected ManagerBranchScopeService $branchScope
    ) {
    }

    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        $request->getFiltersArray();
        $branchId = $this->branchScope->currentBranchId();

        return $this->respondData(
            BranchInventory::with(['branch', 'product'])
                ->where('branch_id', $branchId)
                ->orderBy('branch_id')
                ->get()
        );
    }
}
