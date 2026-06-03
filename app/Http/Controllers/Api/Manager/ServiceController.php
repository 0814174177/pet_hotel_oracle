<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class ServiceController extends ApiController
{
    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        $request->getFiltersArray();

        return $this->respondData(
            Service::with('category')
                ->where('is_active', 1)
                ->orderBy('service_name')
                ->get()
        );
    }
}
