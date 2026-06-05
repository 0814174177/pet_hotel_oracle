<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Manager\ManagerBranchScopeService;
use Illuminate\Http\JsonResponse;

/**
 * Controller quản lý các API liên quan đến báo cáo doanh thu và đơn hàng
 * dành riêng cho cấp Quản lý (Manager) tại chi nhánh mà họ phụ trách.
 */
class ReportController extends ApiController
{
    /**
     * Khởi tạo ReportController.
     *
     * @param ManagerBranchScopeService $branchScope Service xử lý phân quyền và xác định phạm vi chi nhánh.
     */
    public function __construct(
        protected ManagerBranchScopeService $branchScope
    ) {
    }

    /**
     * Lấy báo cáo tổng quan về doanh thu và danh sách đơn hàng của chi nhánh.
     * Tính toán tổng doanh thu từ các giao dịch đã thanh toán thành công (SUCCESS) 
     * và truy xuất tối đa 50 đơn hàng mới nhất dựa trên bộ lọc thời gian.
     *
     * @param DateRangeFilterRequest $request Chứa tham số lọc khoảng thời gian (start_date, end_date).
     * @return JsonResponse Trả về đối tượng JSON chứa tổng doanh thu (paid_revenue) và danh sách đơn hàng (orders).
     */
    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        $filters = $request->getFiltersArray();
        $branchId = $this->branchScope->currentBranchId();
        
        $payments = Payment::where('status', 'SUCCESS')
            ->whereHas('order', fn ($query) => $query->where('branch_id', $branchId));
            
        $orders = Order::with(['branch', 'payment'])
            ->where('branch_id', $branchId)
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

        return $this->respondData(
            [
                'paid_revenue' => $payments->sum('amount'),
                'orders' => $orders->get(),
            ]
        );
    }
}