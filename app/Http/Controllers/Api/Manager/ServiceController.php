<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

/**
 * Controller quản lý các API liên quan đến dịch vụ (Service)
 * dành riêng cho cấp Quản lý (Manager).
 */
class ServiceController extends ApiController
{
    /**
     * Lấy danh sách các dịch vụ đang hoạt động trong hệ thống.
     * Dữ liệu trả về sẽ tự động liên kết (eager load) với thông tin danh mục (category) của dịch vụ đó
     * và được sắp xếp theo thứ tự bảng chữ cái của tên dịch vụ.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian và các tham số truy vấn (nếu có).
     * @return JsonResponse Trả về đối tượng JSON chứa danh sách dịch vụ đang hoạt động.
     */
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