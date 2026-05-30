<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class ServiceController extends ApiController
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Service::with('category')
                ->where('is_active', 1)
                ->orderBy('service_name')
                ->get(),
        ]);
    }
}
