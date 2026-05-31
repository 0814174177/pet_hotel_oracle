<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Ceo\CeoVendorRepositoryInterface;
use Illuminate\Http\JsonResponse;

class VendorController extends ApiController
{
    public function __construct(
        protected CeoVendorRepositoryInterface $vendors
    ) {
    }

    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->vendors->getVendors($this->filters($request)),
        ]);
    }

    private function filters(DateRangeFilterRequest $request): array
    {
        return $request->getFiltersArray();
    }
}
