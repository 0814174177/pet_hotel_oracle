<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Ceo\ServiceRevenueRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Controller quản lý các API liên quan đến danh mục và doanh thu dịch vụ
 * cung cấp số liệu phân tích chuyên sâu cho CEO.
 */
class ServiceController extends ApiController
{
    /**
     * Khởi tạo ServiceController.
     *
     * @param ServiceRevenueRepositoryInterface $serviceRevenues Giao diện xử lý dữ liệu doanh thu dịch vụ.
     */
    public function __construct(
        protected ServiceRevenueRepositoryInterface $serviceRevenues
    ) {
    }

    /**
     * Lấy dữ liệu KPI tổng quan của các dịch vụ theo khoảng thời gian lọc.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số bắt đầu, kết thúc và kỳ trước.
     * @return JsonResponse Trả về đối tượng JSON chứa dữ liệu tổng quan.
     */
    public function summary(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->serviceRevenues->getServiceSummary($this->filters($request))
        );
    }

    /**
     * Lấy danh sách danh mục (catalog) dịch vụ để hiển thị trên trang quản trị của CEO.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian và các điều kiện lọc bổ sung.
     * @return JsonResponse Trả về đối tượng JSON chứa danh mục dịch vụ.
     */
    public function catalog(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->serviceRevenues->getServiceCatalog($this->filters($request))
        );
    }

    /**
     * Lấy bảng phân tích chi tiết doanh thu của từng dịch vụ dựa trên bộ lọc.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian và tham số sắp xếp/lọc doanh thu.
     * @return JsonResponse Trả về đối tượng JSON chứa danh sách doanh thu dịch vụ.
     */
    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->serviceRevenues->getServiceRevenueList($this->filters($request))
        );
    }

    /**
     * Lấy thông tin dịch vụ mang lại doanh thu cao nhất toàn chuỗi trong kỳ lọc.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc thời gian.
     * @return JsonResponse Trả về đối tượng JSON chứa thông tin dịch vụ doanh thu cao nhất.
     */
    public function highestRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->serviceRevenues->getHighestRevenueService($this->filters($request))
        );
    }

    /**
     * Lấy thông tin dịch vụ có doanh thu thấp nhất toàn chuỗi trong kỳ lọc.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc thời gian.
     * @return JsonResponse Trả về đối tượng JSON chứa thông tin dịch vụ doanh thu thấp nhất.
     */
    public function lowestRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->serviceRevenues->getLowestRevenueService($this->filters($request))
        );
    }

    /**
     * Lấy danh sách các dịch vụ không phát sinh doanh thu (không hoạt động) trong kỳ lọc.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc thời gian.
     * @return JsonResponse Trả về đối tượng JSON chứa danh sách dịch vụ không có doanh thu.
     */
    public function noActivity(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->serviceRevenues->getNoActivityServices($this->filters($request))
        );
    }

    /**
     * Lấy thông tin dịch vụ mang lại tỷ suất lợi nhuận cao nhất toàn chuỗi trong kỳ lọc.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc thời gian.
     * @return JsonResponse Trả về đối tượng JSON chứa thông tin dịch vụ sinh lời cao nhất.
     */
    public function mostProfitable(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->serviceRevenues->getMostProfitableService($this->filters($request))
        );
    }

    /**
     * Xử lý, xác thực và hợp nhất các điều kiện lọc thời gian với các điều kiện lọc dành riêng cho dịch vụ.
     *
     * @param DateRangeFilterRequest $request Request chứa dữ liệu đầu vào.
     * @return array Mảng chứa các quy tắc lọc đã được xác thực truyền xuống Repository.
     */
    private function filters(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'status' => ['nullable', 'string', 'max:50'],
            'min_revenue' => ['nullable', 'numeric', 'min:0'],
            'max_revenue' => ['nullable', 'numeric', 'min:0'],
            'min_coverage_rate' => ['nullable', 'numeric', 'min:0'],
            'max_coverage_rate' => ['nullable', 'numeric', 'min:0'],
            'min_served_count' => ['nullable', 'integer', 'min:0'],
            'max_served_count' => ['nullable', 'integer', 'min:0'],
            'sort_by' => ['nullable', 'string', 'in:service_id,service_name,revenue,revenue_share,served_count_30_days,coverage_rate,status'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ]));
    }
}