<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Models\BranchInventory;
use Illuminate\Http\JsonResponse;

class InventoryController extends ApiController
{
    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        $request->getFiltersArray();

        return response()->json([
            'success' => true,
            'data' => BranchInventory::with(['branch', 'product'])
                ->orderBy('branch_id')
                ->get(),
        ]);
    }
}
