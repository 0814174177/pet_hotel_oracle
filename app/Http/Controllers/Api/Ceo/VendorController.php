<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\ReportFilterRequest;
use App\Repositories\Contracts\Ceo\CeoVendorRepositoryInterface;
use Illuminate\Http\JsonResponse;

class VendorController extends ApiController
{
    public function __construct(
        protected CeoVendorRepositoryInterface $vendors
    ) {
    }

    public function index(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->vendors->getVendors($this->filters($request)),
        ]);
    }

    private function filters(ReportFilterRequest $request): array
    {
        return $request->reportFilters();
    }
}
