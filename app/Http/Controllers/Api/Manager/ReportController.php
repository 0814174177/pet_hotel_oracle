<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class ReportController extends ApiController
{
    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        $filters = $request->getFiltersArray();
        $payments = Payment::where('status', 'SUCCESS');
        $orders = Order::with(['branch', 'payment'])
            ->orderByDesc('created_at')
            ->limit(50);

        if (! empty($filters['start_date'])) {
            $payments->where('paid_at', '>=', $filters['start_date']);
            $orders->where('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $payments->where('paid_at', '<=', $filters['end_date']);
            $orders->where('created_at', '<=', $filters['end_date']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'paid_revenue' => $payments->sum('amount'),
                'orders' => $orders->get(),
            ],
        ]);
    }
}
