<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Ceo\ServiceRevenueRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Controller quản lý các API phân tích hiệu suất và doanh thu của các dịch vụ
 * cung cấp số liệu cho Bảng điều khiển (Dashboard) của CEO.
 */
class ServiceAnalyticsController extends ApiController
{
    /**
     * Khởi tạo ServiceAnalyticsController.
     *
     * @param ServiceRevenueRepositoryInterface $serviceRevenues Giao diện xử lý truy xuất doanh thu dịch vụ.
     */
    public function __construct(
        protected ServiceRevenueRepositoryInterface $serviceRevenues
    ) {
    }

    /**
     * Truy xuất các chỉ số KPI tổng quan về dịch vụ.
     * Bao gồm: Dịch vụ doanh thu cao nhất, thấp nhất và tổng doanh thu dịch vụ.
     *
     * @param DateRangeFilterRequest $request Chứa tham số lọc khoảng thời gian.
     * @return JsonResponse Trả về đối tượng JSON chứa các chỉ số KPI.
     */
    public function kpi(DateRangeFilterRequest $request): JsonResponse
    {
        $filters = $request->getFiltersArray();

        $services = collect($this->serviceRevenues->getServiceRevenueList($filters));

        return $this->respondData(
            [
                'top_revenue_service' => $this->formatKpiService($this->serviceRevenues->getHighestRevenueService($filters)),
                'lowest_revenue_service' => $this->formatKpiService($this->serviceRevenues->getLowestRevenueService($filters)),
                'total_service_revenue' => $this->cleanNumber((float) $services->sum('revenue')),
            ],
            'Service KPI data loaded successfully'
        );
    }

    /**
     * Lấy danh sách phân tích chi tiết cho từng dịch vụ.
     * Tích hợp các bộ lọc phụ (trạng thái, sắp xếp) và xử lý phân trang thủ công.
     *
     * @param DateRangeFilterRequest $request Chứa bộ lọc thời gian và phân trang.
     * @return JsonResponse Trả về đối tượng JSON danh sách dịch vụ cùng siêu dữ liệu phân trang (meta).
     */
    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        $filters = array_merge($request->getFiltersArray(), $request->validate([
            'service_status' => ['nullable', 'string', 'max:50'],
            'sort_by' => ['nullable', 'string'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]));

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));
        
        $data = collect($this->serviceRevenues->getServiceRevenueList($filters))
            ->map(fn (array $service): array => [
                'service_code' => $this->serviceCode($service['service_id']),
                'service_name' => $service['service_name'],
                'served_count_last_30_days' => $service['served_count_30_days'],
                'coverage_rate' => $service['coverage_rate'],
            ])
            ->values();

        return $this->respondData(
            $data->forPage($page, $perPage)->values(),
            'Service analytics list loaded successfully',
            200,
            [
                'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $data->count(),
                'last_page' => max(1, (int) ceil($data->count() / $perPage)),
                ],
            ]
        );
    }

    /**
     * Chuẩn hóa cấu trúc dữ liệu của một dịch vụ cho việc hiển thị KPI.
     *
     * @param array|null $service Mảng dữ liệu thô của dịch vụ.
     * @return array|null Mảng dữ liệu đã được tinh gọn và mã hóa định dạng hoặc null nếu không có dữ liệu.
     */
    private function formatKpiService(?array $service): ?array
    {
        if (! $service) {
            return null;
        }

        return [
            'service_code' => $this->serviceCode($service['service_id']),
            'service_name' => $service['service_name'],
            'revenue' => $service['revenue'],
            'revenue_share_percent' => $service['revenue_share'],
        ];
    }

    /**
     * Tạo mã định danh dịch vụ (Service Code) từ ID gốc.
     * Tự động thêm các số 0 ở trước nếu ID là dạng số (Ví dụ: 1 -> SV001).
     *
     * @param mixed $serviceId ID của dịch vụ (có thể là số hoặc chuỗi).
     * @return string Mã định danh dịch vụ đã chuẩn hóa.
     */
    private function serviceCode(mixed $serviceId): string
    {
        return is_numeric($serviceId)
            ? sprintf('SV%03d', (int) $serviceId)
            : (string) $serviceId;
    }

    /**
     * Làm sạch số liệu đầu ra.
     * Loại bỏ các phần thập phân không cần thiết nếu số đã làm tròn tương đương với số nguyên.
     *
     * @param float $value Giá trị số cần làm sạch.
     * @return int|float Trả về số nguyên nếu phần thập phân là 0, ngược lại giữ nguyên dạng số thực.
     */
    private function cleanNumber(float $value): int|float
    {
        $rounded = round($value, 2);

        return abs($rounded - round($rounded)) < 0.00001
            ? (int) round($rounded)
            : $rounded;
    }
}