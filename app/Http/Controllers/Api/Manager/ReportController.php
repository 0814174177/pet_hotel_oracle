<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class ReportController extends ApiController
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'paid_revenue' => Payment::where('status', 'SUCCESS')->sum('amount'),
                'orders' => Order::with(['branch', 'payment'])
                    ->orderByDesc('created_at')
                    ->limit(50)
                    ->get(),
            ],
        ]);
    }
}
