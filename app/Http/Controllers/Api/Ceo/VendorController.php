<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Ceo\CeoVendorRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Controller quản lý các API liên quan đến đối tác, nhà cung cấp, hiệu suất giao hàng 
 * và công nợ phục vụ cho Bảng điều khiển (Dashboard) của CEO.
 */
class VendorController extends ApiController
{
    /**
     * Các ngưỡng xếp hạng hiệu suất của nhà cung cấp. 
     * Có thể tinh chỉnh tại đây mà không cần can thiệp vào SQL trong Repository.
     */
    private const PLATINUM_PERFORMANCE_THRESHOLD = 95.0;

    private const GOLD_PERFORMANCE_THRESHOLD = 90.0;

    private const SILVER_PERFORMANCE_THRESHOLD = 75.0;

    private const OUT_OF_STOCK_PENALTY = 18.0;

    private const LOW_STOCK_PENALTY = 8.0;

    /**
     * Các ngưỡng cảnh báo tốc độ tăng trưởng công nợ phải trả.
     */
    private const PAYABLE_LIGHT_GROWTH_THRESHOLD = 10.0;

    private const PAYABLE_WARNING_GROWTH_THRESHOLD = 20.0;

    private const PAYABLE_CRITICAL_GROWTH_THRESHOLD = 35.0;

    /**
     * Các ngưỡng cảnh báo mức độ biến động giá nhập hàng.
     */
    private const PRICE_VARIANCE_WATCH_THRESHOLD = 10.0;

    private const PRICE_VARIANCE_WARNING_THRESHOLD = 20.0;

    private const PRICE_VARIANCE_CRITICAL_THRESHOLD = 30.0;

    /**
     * Các ngưỡng đánh giá tỷ lệ giao hàng đúng hạn (OTD - On-Time Delivery) và độ trễ.
     */
    private const OTD_EXCELLENT_THRESHOLD = 95.0;

    private const OTD_GOOD_THRESHOLD = 90.0;

    private const OTD_WARNING_THRESHOLD = 80.0;

    private const LOW_QUALITY_THRESHOLD = 3.5;

    private const MEDIUM_QUALITY_THRESHOLD = 4.0;

    private const SEVERE_DELAY_HOURS = 24.0;

    private const WARNING_DELAY_HOURS = 12.0;

    private const WATCH_DELAY_HOURS = 3.0;

    /**
     * Khởi tạo VendorController.
     *
     * @param CeoVendorRepositoryInterface $vendors Giao diện xử lý dữ liệu nhà cung cấp.
     */
    public function __construct(
        protected CeoVendorRepositoryInterface $vendors
    ) {
    }

    /**
     * Lấy danh sách và phân hạng đối tác/nhà cung cấp dựa trên sức khỏe tồn kho hiện tại.
     * Áp dụng các ngưỡng phân hạng và điểm phạt tồn kho cấu hình nội bộ.
     *
     * @param DateRangeFilterRequest $request Chứa tham số lọc thời gian.
     * @return JsonResponse Trả về danh sách nhà cung cấp đã được xếp hạng.
     */
    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->vendors->getVendors(
                $request->getFiltersArray(),
                self::PLATINUM_PERFORMANCE_THRESHOLD,
                self::GOLD_PERFORMANCE_THRESHOLD,
                self::SILVER_PERFORMANCE_THRESHOLD,
                self::OUT_OF_STOCK_PENALTY,
                self::LOW_STOCK_PENALTY
            ),
            errorMessage: 'Unable to load CEO vendor data'
        );
    }

    /**
     * Lấy dữ liệu phân tích hiệu suất giao hàng đúng hạn (OTD) và các cảnh báo liên quan.
     * Áp dụng các ngưỡng OTD, chất lượng và thời gian trễ.
     *
     * @param DateRangeFilterRequest $request Chứa tham số lọc thời gian.
     * @return JsonResponse Trả về dữ liệu chi tiết hiệu suất giao hàng.
     */
    public function onTimeDeliveryPerformance(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->vendors->getOnTimeDeliveryPerformance(
                $request->getFiltersArray(),
                self::OTD_EXCELLENT_THRESHOLD,
                self::OTD_GOOD_THRESHOLD,
                self::OTD_WARNING_THRESHOLD,
                self::LOW_QUALITY_THRESHOLD,
                self::MEDIUM_QUALITY_THRESHOLD,
                self::SEVERE_DELAY_HOURS,
                self::WARNING_DELAY_HOURS,
                self::WATCH_DELAY_HOURS
            ),
            errorMessage: 'Unable to load CEO vendor data'
        );
    }

    /**
     * Lấy chỉ số tổng hợp giao hàng đúng hạn (OTD) của toàn hệ thống.
     * Phục vụ hiển thị tỷ lệ dung hạn trên biểu đồ tổng quan (Dashboard).
     *
     * @param DateRangeFilterRequest $request Chứa tham số lọc thời gian.
     * @return JsonResponse Trả về chỉ số tổng hợp OTD.
     */
    public function onTimeDeliverySummary(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->vendors->getOnTimeDeliverySummary(
                $request->getFiltersArray(),
                self::OTD_GOOD_THRESHOLD,
                self::OTD_WARNING_THRESHOLD
            ),
            errorMessage: 'Unable to load CEO vendor data'
        );
    }

    /**
     * Lấy dữ liệu biểu đồ dòng tiền và công nợ phải trả cho nhà cung cấp phân bổ theo tháng.
     * Áp dụng các ngưỡng cảnh báo tăng trưởng công nợ.
     *
     * @param DateRangeFilterRequest $request Chứa tham số lọc thời gian.
     * @return JsonResponse Trả về dữ liệu biểu đồ dòng tiền công nợ.
     */
    public function payableCashflow(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->vendors->getPayableCashflow(
                $request->getFiltersArray(),
                self::PAYABLE_LIGHT_GROWTH_THRESHOLD,
                self::PAYABLE_WARNING_GROWTH_THRESHOLD,
                self::PAYABLE_CRITICAL_GROWTH_THRESHOLD
            ),
            errorMessage: 'Unable to load CEO vendor data'
        );
    }

    /**
     * Lấy danh sách các cảnh báo về biến động giá nhập hàng từ nhà cung cấp.
     * Hỗ trợ rà soát phòng chống việc nhập sai giá hoặc gian lận giá cả.
     *
     * @param DateRangeFilterRequest $request Chứa tham số lọc thời gian.
     * @return JsonResponse Trả về danh sách cảnh báo biến động giá.
     */
    public function priceVarianceAlerts(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            fn () => $this->vendors->getPriceVarianceAlerts(
                $request->getFiltersArray(),
                self::PRICE_VARIANCE_WATCH_THRESHOLD,
                self::PRICE_VARIANCE_WARNING_THRESHOLD,
                self::PRICE_VARIANCE_CRITICAL_THRESHOLD
            ),
            errorMessage: 'Unable to load CEO vendor data'
        );
    }
}