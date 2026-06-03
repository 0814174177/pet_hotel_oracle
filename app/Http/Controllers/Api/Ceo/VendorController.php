<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Ceo\CeoVendorRepositoryInterface;
use Illuminate\Http\JsonResponse;

class VendorController extends ApiController
{
    /**
     * Vendor ranking thresholds can be tuned here without changing repository SQL.
     */
    private const PLATINUM_PERFORMANCE_THRESHOLD = 95.0;

    private const GOLD_PERFORMANCE_THRESHOLD = 90.0;

    private const SILVER_PERFORMANCE_THRESHOLD = 75.0;

    private const OUT_OF_STOCK_PENALTY = 18.0;

    private const LOW_STOCK_PENALTY = 8.0;

    /**
     * Payable growth thresholds can be tuned here without changing repository SQL.
     */
    private const PAYABLE_LIGHT_GROWTH_THRESHOLD = 10.0;

    private const PAYABLE_WARNING_GROWTH_THRESHOLD = 20.0;

    private const PAYABLE_CRITICAL_GROWTH_THRESHOLD = 35.0;

    /**
     * Price variance thresholds can be tuned here without changing repository SQL. 
     */
    private const PRICE_VARIANCE_WATCH_THRESHOLD = 10.0;

    private const PRICE_VARIANCE_WARNING_THRESHOLD = 20.0;

    private const PRICE_VARIANCE_CRITICAL_THRESHOLD = 30.0;

    /**
     * OTD thresholds can be tuned here without changing repository SQL.
     */
    private const OTD_EXCELLENT_THRESHOLD = 95.0;

    private const OTD_GOOD_THRESHOLD = 90.0;

    private const OTD_WARNING_THRESHOLD = 80.0;

    private const LOW_QUALITY_THRESHOLD = 3.5;

    private const MEDIUM_QUALITY_THRESHOLD = 4.0;

    private const SEVERE_DELAY_HOURS = 24.0;

    private const WARNING_DELAY_HOURS = 12.0;

    private const WATCH_DELAY_HOURS = 3.0;

    public function __construct(
        protected CeoVendorRepositoryInterface $vendors
    ) {
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach va phan hang doi tac/nha cung cap theo suc khoe ton kho hien tai.
     *
     * Input:
     * - DateRangeFilterRequest tu xu ly start_date, end_date va ky truoc.
     * - Cac nguong phan hang, diem phat ton kho cau hinh tai controller.
     *
     * Output:
     * - JSON response chuan thong qua respondData().
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
     * Mo ta chuc nang:
     * Lay hieu suat giao hang dung han OTD va cac canh bao lien quan cho dashboard CEO.
     *
     * Input:
     * - DateRangeFilterRequest tu xu ly start_date, end_date va ky truoc.
     * - Cac nguong OTD, chat luong va thoi gian tre cau hinh tai controller.
     *
     * Output:
     * - JSON response chuan thong qua respondData().
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
     * Mo ta chuc nang:
     * Lay tong hop OTD toan chuoi de hien thi dong ho ty le dung han tren dashboard CEO.
     *
     * Input:
     * - DateRangeFilterRequest tu xu ly start_date, end_date va ky truoc.
     * - Cac nguong OTD cau hinh tai controller.
     *
     * Output:
     * - JSON response chuan thong qua respondData().
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
     * Mo ta chuc nang:
     * Lay bieu do dong tien va cong no phai tra nha cung cap theo thang.
     *
     * Input:
     * - DateRangeFilterRequest tu xu ly start_date, end_date va ky truoc.
     * - Cac nguong tang truong cong no cau hinh tai controller.
     *
     * Output:
     * - JSON response chuan thong qua respondData().
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
     * Mo ta chuc nang:
     * Lay danh sach canh bao bien dong gia nhap de phong chong nhap sai gia/gian lan.
     *
     * Input:
     * - DateRangeFilterRequest tu xu ly start_date, end_date va ky truoc.
     * - Cac nguong bien dong gia cau hinh tai controller.
     *
     * Output:
     * - JSON response chuan thong qua respondData().
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
