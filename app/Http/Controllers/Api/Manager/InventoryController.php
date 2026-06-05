<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Models\BranchInventory;
use App\Services\Manager\ManagerBranchScopeService;
use Illuminate\Http\JsonResponse;

/**
 * Controller quản lý các API liên quan đến hàng tồn kho (Inventory)
 * dành riêng cho cấp Quản lý (Manager) tại chi nhánh của họ.
 */
class InventoryController extends ApiController
{
    /**
     * Khởi tạo InventoryController.
     *
     * @param ManagerBranchScopeService $branchScope Service xử lý phân quyền và xác định phạm vi chi nhánh.
     */
    public function __construct(
        protected ManagerBranchScopeService $branchScope
    ) {
    }

    /**
     * Lấy danh sách vật tư/sản phẩm tồn kho tại chi nhánh hiện hành của Manager.
     * * Ghi chú: Dữ liệu trả về sẽ bao gồm luôn các thông tin liên kết (relations) 
     * về chi nhánh (branch) và sản phẩm (product).
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian và các tham số lọc khác.
     * @return JsonResponse Trả về đối tượng JSON chứa danh sách tồn kho của chi nhánh.
     */
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