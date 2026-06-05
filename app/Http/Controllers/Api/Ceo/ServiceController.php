<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Ceo\ServiceRevenueRepositoryInterface;
use Illuminate\Http\JsonResponse;

class ServiceController extends ApiController
{
    public function __construct(
        protected ServiceRevenueRepositoryInterface $serviceRevenues
    ) {
    }

    /**
     * Mo ta chuc nang:
     * Lay KPI tong quan dich vu theo khoang thoi gian filter.
     *
     * Input:
     * - DateRangeFilterRequest tu xu ly start_date, end_date va ky truoc.
     *
     * Output:
     * - JSON response chuan thong qua respondData().
     */
    public function summary(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->serviceRevenues->getServiceSummary($this->filters($request))
        );
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach catalog dich vu de hien thi tren trang quan tri dich vu CEO.
     *
     * Input:
     * - DateRangeFilterRequest va cac filter bo sung neu co.
     *
     * Output:
     * - JSON response chuan thong qua respondData().
     */
    public function catalog(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->serviceRevenues->getServiceCatalog($this->filters($request))
        );
    }

    /**
     * Mo ta chuc nang:
     * Lay bang phan tich doanh thu tung dich vu theo filter hien tai.
     *
     * Input:
     * - DateRangeFilterRequest va cac filter sap xep/loc doanh thu neu co.
     *
     * Output:
     * - JSON response chuan thong qua respondData().
     */
    public function index(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->serviceRevenues->getServiceRevenueList($this->filters($request))
        );
    }

    /**
     * Mo ta chuc nang:
     * Lay dich vu ganh doanh thu toan chuoi trong khoang thoi gian filter.
     *
     * Input:
     * - DateRangeFilterRequest tu xu ly start_date, end_date va ky truoc.
     *
     * Output:
     * - JSON response chuan thong qua respondData().
     */
    public function highestRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->serviceRevenues->getHighestRevenueService($this->filters($request))
        );
    }

    /**
     * Mo ta chuc nang:
     * Lay dich vu co doanh thu thap nhat trong khoang thoi gian filter.
     *
     * Input:
     * - DateRangeFilterRequest tu xu ly start_date, end_date va ky truoc.
     *
     * Output:
     * - JSON response chuan thong qua respondData().
     */
    public function lowestRevenue(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->serviceRevenues->getLowestRevenueService($this->filters($request))
        );
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach dich vu khong phat sinh doanh thu da thanh toan trong ky loc.
     *
     * Input:
     * - DateRangeFilterRequest tu xu ly start_date, end_date va ky truoc.
     *
     * Output:
     * - JSON response chuan thong qua respondData().
     */
    public function noActivity(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->serviceRevenues->getNoActivityServices($this->filters($request))
        );
    }

    /**
     * Mo ta chuc nang:
     * Lay dich vu sieu loi nhuan toan chuoi theo khoang thoi gian filter.
     *
     * Input:
     * - DateRangeFilterRequest tu xu ly start_date, end_date va ky truoc.
     *
     * Output:
     * - JSON response chuan thong qua respondData().
     */
    public function mostProfitable(DateRangeFilterRequest $request): JsonResponse
    {
        return $this->respondData(
            $this->serviceRevenues->getMostProfitableService($this->filters($request))
        );
    }

    /**
     * Mo ta chuc nang:
     * Gom filter ngay tu DateRangeFilterRequest voi cac filter rieng cua trang dich vu.
     *
     * Input:
     * - DateRangeFilterRequest gom start_date, end_date va query filter bo sung.
     *
     * Output:
     * - Mang filter truyen xuong ServiceRevenueRepository.
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
