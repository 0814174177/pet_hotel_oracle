<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Ceo\CeoDashboardRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Quản lý các API cung cấp số liệu tổng quan và phân tích chuyên sâu cho Bảng điều khiển (Dashboard) của CEO.
 */
class DashboardController extends ApiController
{
    /**
     * Khởi tạo DashboardController.
     *
     * @param CeoDashboardRepositoryInterface $dashboardRepository Giao diện xử lý dữ liệu thống kê Dashboard.
     */
    public function __construct(
        protected CeoDashboardRepositoryInterface $dashboardRepository
    ) {
    }

    /**
     * Lấy tình trạng lưu trú và công suất sử dụng phòng hiện tại của hệ thống.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc dữ liệu.
     * @return JsonResponse Trả về dữ liệu JSON chi tiết công suất phòng hiện tại.
     */
    public function currentHotelOccupancy(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getCurrentHotelOccupancy($request->getFiltersArray())
        );
    }

    /**
     * Lấy thống kê tỷ lệ lấp đầy (Occupancy Rate) theo các điều kiện lọc.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc dữ liệu (thời gian, chi nhánh...).
     * @return JsonResponse Trả về dữ liệu JSON biểu diễn tỷ lệ lấp đầy.
     */
    public function occupancyRate(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getOccupancyRate($request->getFiltersArray())
        );
    }

    /**
     * Lấy chỉ số RevPAR (Revenue Per Available Room - Doanh thu trên mỗi phòng có sẵn).
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc dữ liệu.
     * @return JsonResponse Trả về dữ liệu JSON biểu diễn chỉ số RevPAR.
     */
    public function revpar(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRevpar($request->getFiltersArray())
        );
    }
    
    /**
     * Lấy dữ liệu phân tích xu hướng và hành vi của khách hàng.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc dữ liệu.
     * @return JsonResponse Trả về dữ liệu JSON về xu hướng khách hàng.
     */
    public function customerTrend(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getCustomerTrend($request->getFiltersArray())
        );
    }

    /**
     * Lấy tổng doanh thu của toàn bộ chuỗi hệ thống.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc dữ liệu.
     * @return JsonResponse Trả về dữ liệu JSON tổng doanh thu toàn chuỗi.
     */
    public function chainRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getTotalRevenue($request->getFiltersArray())
        );
    }

    /**
     * Lấy số liệu ước tính Giá vốn hàng bán (COGS - Cost of Goods Sold) và chi phí vận hành.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc dữ liệu.
     * @return JsonResponse Trả về dữ liệu JSON ước tính chi phí giá vốn.
     */
    public function estimatedCogs(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getEstimatedCogs($request->getFiltersArray())
        );
    }

    /**
     * Lấy dữ liệu cơ cấu doanh thu (Revenue Mix) để xem tỷ trọng từ các nguồn khác nhau.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc dữ liệu.
     * @return JsonResponse Trả về dữ liệu JSON cơ cấu doanh thu.
     */
    public function revenueMix(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRevenueMix($request->getFiltersArray())
        );
    }

    /**
     * Lấy dữ liệu phân tích xu hướng so sánh giữa Doanh thu và Giá vốn (COGS).
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc dữ liệu.
     * @return JsonResponse Trả về dữ liệu JSON xu hướng tương quan Doanh thu - Chi phí.
     */
    public function revenueAndCogsTrend(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRevenueAndCogsTrend($request->getFiltersArray())
        );
    }

    /**
     * Lấy báo cáo doanh thu chi tiết phân bổ theo từng chi nhánh.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc dữ liệu.
     * @return JsonResponse Trả về dữ liệu JSON doanh thu theo chi nhánh.
     */
    public function branchRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getBranchRevenue($request->getFiltersArray())
        );
    }

    /**
     * Lấy bảng xếp hạng hiệu quả hoạt động của các chi nhánh.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc dữ liệu.
     * @return JsonResponse Trả về dữ liệu JSON xếp hạng chi nhánh.
     */
    public function branchRanking(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getBranchRanking($request->getFiltersArray())
        );
    }

    /**
     * Lấy danh sách thống kê các dịch vụ được sử dụng nhiều nhất (Top Used Services).
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc dữ liệu.
     * @return JsonResponse Trả về dữ liệu JSON danh sách dịch vụ phổ biến nhất.
     */
    public function topUsedServices(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getTopUsedServices($request->getFiltersArray())
        );
    }

    /**
     * Lấy danh sách các cảnh báo rủi ro về mặt vận hành, tài chính hoặc hiệu suất.
     *
     * @param DateRangeFilterRequest $request Chứa các tham số lọc dữ liệu.
     * @return JsonResponse Trả về dữ liệu JSON các cảnh báo rủi ro cần chú ý.
     */
    public function riskAlerts(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->dashboardRepository->getRiskAlerts($request->getFiltersArray())
        );
    }
}