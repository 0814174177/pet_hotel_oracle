<?php

namespace App\Repositories\Eloquent\Manager;

use App\Repositories\Contracts\Manager\BranchScopedRevenueReportRepositoryInterface;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class BranchScopedRevenueReportRepository implements BranchScopedRevenueReportRepositoryInterface
{
    /**
     * Temporary monthly revenue target until a branch target table is added.
     */
    private const TEMPORARY_TARGET_MONTH = 540000000;

    /**
     * Get the full branch revenue report dashboard structure.
     */
    public function getDashboard(int|string $branchId, array $filters = []): array
    {
        // TODO: Aggregate branch revenue report sections after business queries are implemented.
        return [
            'target_progress' => [],
            'revenue_comparison' => [],
            'service_mix' => [],
            'aov' => [],
            'employee_performance' => [],
            'customer_retention' => [],
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay tien do muc tieu doanh thu thang cua chi nhanh Manager.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $filters: start_date, end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang current_revenue, target_month, remaining_to_target,
     *   progress_percent, required_revenue_per_day, days_left,
     *   target_warning va warning_level.
     *
     * Ghi chu:
     * - SQL Oracle dat tai Repository, bind p_branch_id, p_start_date,
     *   p_end_date va p_target_month.
     * - target_month dang la bien tam thoi cho den khi co bang target rieng.
     */
    public function getTargetProgress(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :p_branch_id AS branch_id,
                    TO_DATE(:p_start_date, 'YYYY-MM-DD') AS start_date,
                    TO_DATE(:p_end_date, 'YYYY-MM-DD') AS end_date,
                    TO_NUMBER(:p_target_month) AS target_month
                FROM dual
            ),
            revenue AS (
                SELECT NVL(SUM(o.grand_total), 0) AS current_revenue
                FROM orders o
                JOIN params p
                    ON p.branch_id = o.branch_id
                WHERE o.status IN ('PAID', 'COMPLETED')
                  AND o.created_at >= p.start_date
                  AND o.created_at < p.end_date
            )
            SELECT
                r.current_revenue,
                p.target_month,
                p.target_month - r.current_revenue AS remaining_to_target,
                ROUND(r.current_revenue / NULLIF(p.target_month, 0) * 100, 2) AS progress_percent,
                ROUND(
                    (p.target_month - r.current_revenue)
                    / NULLIF(GREATEST(TRUNC(p.end_date) - TRUNC(SYSDATE), 1), 0),
                    2
                ) AS required_revenue_per_day,
                GREATEST(TRUNC(p.end_date) - TRUNC(SYSDATE), 0) AS days_left,
                CASE
                    WHEN r.current_revenue >= p.target_month THEN 'ĐẠT/VƯỢT TARGET'
                    WHEN r.current_revenue / NULLIF(p.target_month, 0) >= 0.75 THEN 'GẦN ĐẠT TARGET'
                    WHEN r.current_revenue / NULLIF(p.target_month, 0) >= 0.50 THEN 'CHẬM TIẾN ĐỘ'
                    ELSE 'CẢNH BÁO ĐỎ: DOANH THU THẤP'
                END AS target_warning
            FROM revenue r CROSS JOIN params p
            SQL;

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_branch_id' => $branchId,
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
            'p_target_month' => self::TEMPORARY_TARGET_MONTH,
        ]), CASE_LOWER);

        $targetWarning = (string) ($row['target_warning'] ?? 'CẢNH BÁO ĐỎ: DOANH THU THẤP');

        return [
            'current_revenue' => (float) ($row['current_revenue'] ?? 0),
            'target_month' => (float) ($row['target_month'] ?? self::TEMPORARY_TARGET_MONTH),
            'remaining_to_target' => (float) ($row['remaining_to_target'] ?? 0),
            'progress_percent' => $this->nullableFloat($row['progress_percent'] ?? null),
            'required_revenue_per_day' => (float) ($row['required_revenue_per_day'] ?? 0),
            'days_left' => (int) ($row['days_left'] ?? 0),
            'target_warning' => $targetWarning,
            'warning_level' => $this->targetWarningLevel($targetWarning),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay bieu do so sanh doanh thu theo ngay trong ky cua chi nhanh Manager.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $filters: start_date, end_date, prev_start_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Danh sach ngay gom revenue_date, current_revenue,
     *   previous_revenue va growth_percent.
     *
     * Ghi chu:
     * - SQL Oracle dat tai Repository, bind p_branch_id, p_start_date,
     *   p_end_date va p_prev_start_date.
     */
    public function getRevenueComparisonChart(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :p_branch_id AS branch_id,
                    TO_DATE(:p_start_date, 'YYYY-MM-DD') AS start_date,
                    TO_DATE(:p_end_date, 'YYYY-MM-DD') AS end_date,
                    TO_DATE(:p_prev_start_date, 'YYYY-MM-DD') AS prev_start_date
                FROM dual
            ),
            current_daily AS (
                SELECT
                    TRUNC(o.created_at) AS revenue_date,
                    NVL(SUM(o.grand_total), 0) AS current_revenue
                FROM orders o
                JOIN params p
                    ON p.branch_id = o.branch_id
                WHERE o.status IN ('PAID', 'COMPLETED')
                  AND o.created_at >= p.start_date
                  AND o.created_at < p.end_date
                GROUP BY TRUNC(o.created_at)
            ),
            previous_daily AS (
                SELECT
                    TRUNC(o.created_at) AS revenue_date,
                    NVL(SUM(o.grand_total), 0) AS previous_revenue
                FROM orders o
                JOIN params p
                    ON p.branch_id = o.branch_id
                WHERE o.status IN ('PAID', 'COMPLETED')
                  AND o.created_at >= p.prev_start_date
                  AND o.created_at < p.start_date
                GROUP BY TRUNC(o.created_at)
            )
            SELECT
                TO_CHAR(c.revenue_date, 'YYYY-MM-DD') AS revenue_date,
                NVL(c.current_revenue, 0) AS current_revenue,
                NVL(p.previous_revenue, 0) AS previous_revenue,
                ROUND(
                    (NVL(c.current_revenue, 0) - NVL(p.previous_revenue, 0))
                    / NULLIF(NVL(p.previous_revenue, 0), 0) * 100,
                    2
                ) AS growth_percent
            FROM current_daily c
            LEFT JOIN previous_daily p
                ON p.revenue_date = ADD_MONTHS(c.revenue_date, -1)
            ORDER BY c.revenue_date
            SQL;

        return array_map(function (object $row): array {
            $row = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'revenue_date' => (string) ($row['revenue_date'] ?? ''),
                'current_revenue' => (float) ($row['current_revenue'] ?? 0),
                'previous_revenue' => (float) ($row['previous_revenue'] ?? 0),
                'growth_percent' => $this->nullableFloat($row['growth_percent'] ?? null),
            ];
        }, DB::select($sql, [
            'p_branch_id' => $branchId,
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
            'p_prev_start_date' => $filters['prev_start_date'],
        ]));
    }

    /**
     * Get current-period and previous-period revenue comparison data.
     */
    public function getRevenueComparison(int|string $branchId, array $filters = []): array
    {
        return $this->getRevenueComparisonChart($branchId, $filters);
    }

    /**
     * Mo ta chuc nang:
     * Lay Service Mix va co cau doanh thu theo nhom dich vu cua chi nhanh Manager.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $filters: start_date va end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Danh sach nhom doanh thu gom revenue_group, revenue va percent_of_total.
     *
     * Ghi chu:
     * - SQL Oracle dat tai Repository, bind p_branch_id, p_start_date va p_end_date.
     * - order_details chua co product_id nen khong tu them logic Phu kien/Shop.
     * - Doanh thu phong duoc chia theo so pet trong phong de tong moi order_detail chi tinh mot lan.
     */
    public function getServiceMix(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :p_branch_id AS branch_id,
                    TO_DATE(:p_start_date, 'YYYY-MM-DD') AS start_date,
                    TO_DATE(:p_end_date, 'YYYY-MM-DD') AS end_date
                FROM dual
            ),
            room_pet_context AS (
                SELECT
                    brp.booking_room_id,
                    p.species,
                    COUNT(*) OVER (PARTITION BY brp.booking_room_id) AS pet_count
                FROM booking_room_pet brp
                JOIN pet p
                    ON p.pet_id = brp.pet_id
            ),
            classified_details AS (
                SELECT
                    CASE
                        WHEN od.booking_room_id IS NOT NULL AND rpc.species = 'DOG' THEN 'Hotel Chó'
                        WHEN od.booking_room_id IS NOT NULL AND rpc.species = 'CAT' THEN 'Hotel Mèo'
                        WHEN od.booking_service_pet_id IS NOT NULL AND (
                                LOWER(s.service_name) LIKE '%spa%'
                             OR LOWER(s.service_name) LIKE '%groom%'
                             OR LOWER(s.service_name) LIKE '%tắm%'
                             OR LOWER(s.service_name) LIKE '%cắt%'
                            ) THEN 'Spa / Grooming'
                        WHEN od.booking_service_pet_id IS NOT NULL AND (
                                LOWER(s.service_name) LIKE '%khám%'
                             OR LOWER(s.service_name) LIKE '%vaccine%'
                             OR LOWER(s.service_name) LIKE '%thú y%'
                            ) THEN 'Thú y / Khám'
                        ELSE 'Dịch vụ khác'
                    END AS revenue_group,
                    CASE
                        WHEN od.booking_room_id IS NOT NULL AND NVL(rpc.pet_count, 0) > 0
                            THEN NVL(od.line_total, 0) / NULLIF(rpc.pet_count, 0)
                        ELSE NVL(od.line_total, 0)
                    END AS line_total
                FROM params prm
                JOIN orders o
                    ON o.branch_id = prm.branch_id
                JOIN order_details od
                    ON od.order_id = o.order_id
                LEFT JOIN room_pet_context rpc
                    ON rpc.booking_room_id = od.booking_room_id
                LEFT JOIN booking_service_pet bsp
                    ON bsp.booking_service_pet_id = od.booking_service_pet_id
                LEFT JOIN services s
                    ON s.service_id = bsp.service_id
                WHERE o.status IN ('PAID', 'COMPLETED')
                  AND o.created_at >= prm.start_date
                  AND o.created_at < prm.end_date
            ),
            mix_data AS (
                SELECT
                    revenue_group,
                    NVL(SUM(line_total), 0) AS revenue
                FROM classified_details
                GROUP BY revenue_group
            )
            SELECT
                revenue_group,
                NVL(revenue, 0) AS revenue,
                ROUND(
                    NVL(revenue, 0) / NULLIF(SUM(NVL(revenue, 0)) OVER (), 0) * 100,
                    2
                ) AS percent_of_total
            FROM mix_data
            ORDER BY revenue DESC
            SQL;

        return array_map(function (object $row): array {
            $row = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'revenue_group' => (string) ($row['revenue_group'] ?? ''),
                'revenue' => (float) ($row['revenue'] ?? 0),
                'percent_of_total' => $this->nullableFloat($row['percent_of_total'] ?? null),
            ];
        }, DB::select($sql, [
            'p_branch_id' => $branchId,
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
        ]));
    }

    /**
     * Get service revenue mix and average order value data.
     */
    public function getServiceMixAndAov(int|string $branchId, array $filters = []): array
    {
        return [
            'service_mix' => $this->getServiceMix($branchId, $filters),
            'aov' => $this->getAovSummary($branchId, $filters),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay AOV, doanh thu va so don theo ky cua chi nhanh Manager.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $filters: start_date, end_date, prev_start_date, prev_end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang current_orders, current_revenue, current_aov,
     *   previous_orders, previous_revenue, previous_aov,
     *   aov_growth_percent, max_order_value va trend.
     *
     * Ghi chu:
     * - SQL Oracle dat tai Repository, bind p_branch_id, p_start_date,
     *   p_end_date, p_prev_start_date va p_prev_end_date.
     */
    public function getAovSummary(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :p_branch_id AS branch_id,
                    TO_DATE(:p_start_date, 'YYYY-MM-DD') AS start_date,
                    TO_DATE(:p_end_date, 'YYYY-MM-DD') AS end_date,
                    TO_DATE(:p_prev_start_date, 'YYYY-MM-DD') AS prev_start_date,
                    TO_DATE(:p_prev_end_date, 'YYYY-MM-DD') AS prev_end_date
                FROM dual
            ),
            current_data AS (
                SELECT
                    COUNT(*) AS current_orders,
                    NVL(SUM(o.grand_total), 0) AS current_revenue,
                    NVL(AVG(o.grand_total), 0) AS current_aov,
                    NVL(MAX(o.grand_total), 0) AS max_order_value
                FROM orders o
                JOIN params p
                    ON p.branch_id = o.branch_id
                WHERE o.status IN ('PAID', 'COMPLETED')
                  AND o.created_at >= p.start_date
                  AND o.created_at < p.end_date
            ),
            previous_data AS (
                SELECT
                    COUNT(*) AS previous_orders,
                    NVL(SUM(o.grand_total), 0) AS previous_revenue,
                    NVL(AVG(o.grand_total), 0) AS previous_aov
                FROM orders o
                JOIN params p
                    ON p.branch_id = o.branch_id
                WHERE o.status IN ('PAID', 'COMPLETED')
                  AND o.created_at >= p.prev_start_date
                  AND o.created_at < p.prev_end_date
            )
            SELECT
                c.current_orders,
                c.current_revenue,
                ROUND(c.current_aov, 2) AS current_aov,
                p.previous_orders,
                p.previous_revenue,
                ROUND(p.previous_aov, 2) AS previous_aov,
                ROUND(
                    (c.current_aov - p.previous_aov)
                    / NULLIF(p.previous_aov, 0) * 100,
                    2
                ) AS aov_growth_percent,
                c.max_order_value
            FROM current_data c
            CROSS JOIN previous_data p
            SQL;

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_branch_id' => $branchId,
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
            'p_prev_start_date' => $filters['prev_start_date'],
            'p_prev_end_date' => $filters['prev_end_date'],
        ]), CASE_LOWER);
        $growthPercent = $this->nullableFloat($row['aov_growth_percent'] ?? null);

        return [
            'current_orders' => (int) ($row['current_orders'] ?? 0),
            'current_revenue' => (float) ($row['current_revenue'] ?? 0),
            'current_aov' => (float) ($row['current_aov'] ?? 0),
            'previous_orders' => (int) ($row['previous_orders'] ?? 0),
            'previous_revenue' => (float) ($row['previous_revenue'] ?? 0),
            'previous_aov' => (float) ($row['previous_aov'] ?? 0),
            'aov_growth_percent' => $growthPercent,
            'max_order_value' => (float) ($row['max_order_value'] ?? 0),
            'trend' => $this->trendFromPercent($growthPercent),
        ];
    }

    /**
     * Get service revenue mix grouped by service category.
     */
    public function getServiceRevenueMix(int|string $branchId, array $filters = []): array
    {
        return $this->getServiceMix($branchId, $filters);
    }

    /**
     * Get average order value data for the selected branch and period.
     */
    public function getAverageOrderValue(int|string $branchId, array $filters = []): array
    {
        return $this->getAovSummary($branchId, $filters);
    }

    /**
     * Get employee performance by revenue and upsell activity.
     */
    public function getEmployeePerformance(int|string $branchId, array $filters = []): array
    {
        // TODO: Load revenue by employee, calculate upsell rate by employee, and prepare anomaly warning data filtered by branch and period.
        return [];
    }

    /**
     * Get customer retention, new customer, and loyal customer metrics.
     */
    public function getCustomerRetention(int|string $branchId, array $filters = []): array
    {
        // TODO: Calculate returning customer rate, new customers, and loyal customers using valid transactions only.
        return [];
    }

    /**
     * Resolve the selected report period into a date range.
     */
    public function resolvePeriodRange(array $filters = []): array
    {
        // TODO: Resolve day, month, or year into a start date, end date, and report grouping rule.
        return [];
    }

    /**
     * Resolve the previous comparable report period into a date range.
     */
    public function resolvePreviousPeriodRange(array $filters = []): array
    {
        // TODO: Resolve the comparable previous day, month, or year range based on the selected report period.
        return [];
    }

    /**
     * Mo ta chuc nang:
     * Normalize filter ngay tu Carbon/string/null ve chuoi YYYY-MM-DD truoc khi bind Oracle.
     *
     * Input:
     * - array $filters co the gom start_date va end_date.
     *
     * Output:
     * - Mang filters co start_date va end_date dang YYYY-MM-DD.
     *
     * Ghi chu:
     * - Khi frontend chua chon ngay, mac dinh la thang hien tai theo can tren doc quyen.
     */
    private function normalizeFilters(array $filters = []): array
    {
        $currentMonth = $this->currentMonthFilters();

        $startDate = $this->dateString($filters['start_date'] ?? null) ?? $currentMonth['start_date'];
        $endDate = $this->exclusiveEndDateString($filters['end_date'] ?? null) ?? $currentMonth['end_date'];
        $previousPeriod = $this->previousPeriodFilters($startDate, $endDate);

        return [
            ...$filters,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'prev_start_date' => $this->dateString($filters['prev_start_date'] ?? null) ?? $previousPeriod['prev_start_date'],
            'prev_end_date' => $this->exclusiveEndDateString($filters['prev_end_date'] ?? null) ?? $previousPeriod['prev_end_date'],
        ];
    }

    /**
     * Mo ta chuc nang:
     * Tao khoang ngay mac dinh cho thang hien tai voi end_date la can tren doc quyen.
     *
     * Output:
     * - start_date la ngay dau thang, end_date la ngay dau thang ke tiep.
     */
    private function currentMonthFilters(): array
    {
        $today = CarbonImmutable::today(config('app.timezone'));

        return [
            'start_date' => $today->startOfMonth()->toDateString(),
            'end_date' => $today->addMonthNoOverflow()->startOfMonth()->toDateString(),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Tinh ky truoc co cung so ngay voi khoang ngay hien tai dang dung can tren doc quyen.
     *
     * Input:
     * - string $startDate: Ngay bat dau ky hien tai dang YYYY-MM-DD.
     * - string $endDate: Can tren doc quyen cua ky hien tai dang YYYY-MM-DD.
     *
     * Output:
     * - prev_start_date va prev_end_date dang YYYY-MM-DD.
     */
    private function previousPeriodFilters(string $startDate, string $endDate): array
    {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $startDate, config('app.timezone'));
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $endDate, config('app.timezone'));
        $daysInPeriod = max(1, abs((int) $start->diffInDays($end)));
        $previousEnd = $start;

        return [
            'prev_start_date' => $previousEnd->subDays($daysInPeriod)->toDateString(),
            'prev_end_date' => $previousEnd->toDateString(),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Chuyen gia tri ngay Carbon/string ve chuoi YYYY-MM-DD.
     */
    private function dateString(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (! filled($value)) {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}/', $value) === 1) {
            return CarbonImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10))
                ->toDateString();
        }

        return CarbonImmutable::make($value)?->toDateString();
    }

    /**
     * Mo ta chuc nang:
     * Chuyen ngay ket thuc tu DateRangeFilterRequest ve can tren doc quyen YYYY-MM-DD.
     */
    private function exclusiveEndDateString(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)
                ->addDay()
                ->toDateString();
        }

        return $this->dateString($value);
    }

    /**
     * Mo ta chuc nang:
     * Ep kieu float nhung giu null cho gia tri khong tinh duoc.
     */
    private function nullableFloat(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    /**
     * Mo ta chuc nang:
     * Suy ra trend tu phan tram tang/giam.
     */
    private function trendFromPercent(?float $percent): string
    {
        if ($percent === null || $percent == 0.0) {
            return 'neutral';
        }

        return $percent > 0 ? 'up' : 'down';
    }

    /**
     * Mo ta chuc nang:
     * Anh xa noi dung canh bao target sang warning_level cho frontend.
     */
    private function targetWarningLevel(string $targetWarning): string
    {
        return match ($targetWarning) {
            'ĐẠT/VƯỢT TARGET', 'GẦN ĐẠT TARGET' => 'success',
            'CHẬM TIẾN ĐỘ' => 'warning',
            default => 'danger',
        };
    }
}
