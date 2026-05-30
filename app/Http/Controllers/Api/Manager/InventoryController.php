<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Models\BranchInventory;
use Illuminate\Http\JsonResponse;

class InventoryController extends ApiController
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => BranchInventory::with(['branch', 'product'])
                ->orderBy('branch_id')
                ->get(),
        ]);
    }
}
