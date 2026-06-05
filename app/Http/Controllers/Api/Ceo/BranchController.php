<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Ceo\BranchNetworkRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Quản lý các API liên quan đến thống kê và mạng lưới chi nhánh dành cho cấp bậc CEO.
 */
class BranchController extends ApiController
{
    /**
     * Khởi tạo BranchController.
     * * @param BranchNetworkRepositoryInterface $branches Giao diện xử lý dữ liệu mạng lưới chi nhánh.
     */
    public function __construct(
        protected BranchNetworkRepositoryInterface $branches
    ) {
    }

    /**
     * Lấy số lượng các chi nhánh đang hoạt động dựa trên bộ lọc thời gian.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc về khoảng thời gian.
     * @return JsonResponse Trả về dữ liệu JSON chứa số lượng chi nhánh hoạt động.
     */
    public function activeCount(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->branches->getActiveBranchCount($this->filters($request))
        );
    }

    /**
     * Lấy thông tin chi nhánh có doanh thu cao nhất.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc dữ liệu.
     * @return JsonResponse Trả về dữ liệu JSON chi tiết chi nhánh doanh thu cao nhất.
     */
    public function highestRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->branches->getHighestRevenueBranch($this->filters($request))
        );
    }

    /**
     * Lấy thông tin chi nhánh có tỷ lệ lấp đầy thấp nhất.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc dữ liệu.
     * @return JsonResponse Trả về dữ liệu JSON chi tiết chi nhánh có tỷ lệ lấp đầy thấp nhất.
     */
    public function lowestOccupancy(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->branches->getLowestOccupancyBranch($this->filters($request))
        );
    }

    /**
     * Lấy danh sách tổng hợp của toàn bộ mạng lưới chi nhánh.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc dữ liệu.
     * @return JsonResponse Trả về dữ liệu JSON danh sách các chi nhánh.
     */
    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->branches->getBranchNetworkList($this->filters($request))
        );
    }

    /**
     * Xử lý, xác thực và hợp nhất các điều kiện lọc dữ liệu bổ sung.
     *
     * @param DateRangeFilterRequest $request Request chứa dữ liệu đầu vào từ người dùng.
     * @return array Mảng chứa các quy tắc lọc đã được xác thực và hợp nhất an toàn.
     */
    private function filters(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'region' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'min_revenue' => ['nullable', 'numeric', 'min:0'],
            'max_revenue' => ['nullable', 'numeric', 'min:0'],
            'min_occupancy_rate' => ['nullable', 'numeric', 'min:0'],
            'max_occupancy_rate' => ['nullable', 'numeric', 'min:0'],
            'sort_by' => ['nullable', 'string', 'in:branch_name,region,revenue,occupancy_rate,status'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ]));
    }
}