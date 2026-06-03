<?php

namespace App\Repositories\Eloquent\Manager;

use App\Repositories\Contracts\Manager\BranchScopedServiceManagementRepositoryInterface;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class BranchScopedServiceManagementRepository implements BranchScopedServiceManagementRepositoryInterface
{
    /**
     * Temporary monthly service revenue target until a branch target table is added.
     */
    private const TEMPORARY_TARGET_SERVICE_REVENUE = 120000000;

    /**
     * Get the full branch service management overview structure.
     */
    public function getOverview(int|string $branchId, array $filters = []): array
    {
        // TODO: Aggregate KPI cards and service list for the selected branch after real business logic is implemented.
        return [
            'kpi' => [],
            'services' => [],
        ];
    }

    /**
     * Get KPI cards for branch service management.
     */
    public function getKpiCards(int|string $branchId, array $period = []): array
    {
        // TODO: Load revenue progress, local top service, and upsell rate for the selected branch and period.
        return [];
    }

    /**
     * Mo ta chuc nang:
     * Lay KPI tien do doanh thu dich vu thang cua chi nhanh hien tai.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $period: start_date, end_date, prev_start_date, prev_end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang service_revenue gom current, previous, target, progress_percent,
     *   growth_vs_previous_percent, warning, warning_level va trend.
     *
     * Ghi chu:
     * - SQL Oracle dat tai Repository va bind p_branch_id, p_start_date, p_end_date,
     *   p_prev_start_date, p_prev_end_date, p_target_service_revenue.
     * - target_service_revenue dang la chi tieu tam thoi cho den khi co bang target theo chi nhanh.
     */
    public function getRevenueProgress(int|string $branchId, array $period = []): array
    {
        $period = $this->normalizeFilters($period);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :p_branch_id AS branch_id,
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date,
                    TRUNC(TO_DATE(:p_prev_start_date, 'YYYY-MM-DD')) AS prev_start_date,
                    TRUNC(TO_DATE(:p_prev_end_date, 'YYYY-MM-DD')) AS prev_end_date,
                    :p_target_service_revenue AS target_service_revenue
                FROM dual
            ),
            current_revenue AS (
                SELECT NVL(SUM(od.line_total), 0) AS service_revenue
                FROM params p
                JOIN orders o
                    ON o.branch_id = p.branch_id
                JOIN order_details od
                    ON od.order_id = o.order_id
                JOIN booking_service_pet bsp
                    ON bsp.booking_service_pet_id = od.booking_service_pet_id
                WHERE od.booking_service_pet_id IS NOT NULL
                  AND o.status IN ('PAID', 'COMPLETED')
                  AND o.created_at >= p.start_date
                  AND o.created_at <  p.end_date + 1
            ),
            previous_revenue AS (
                SELECT NVL(SUM(od.line_total), 0) AS prev_service_revenue
                FROM params p
                JOIN orders o
                    ON o.branch_id = p.branch_id
                JOIN order_details od
                    ON od.order_id = o.order_id
                JOIN booking_service_pet bsp
                    ON bsp.booking_service_pet_id = od.booking_service_pet_id
                WHERE od.booking_service_pet_id IS NOT NULL
                  AND o.status IN ('PAID', 'COMPLETED')
                  AND o.created_at >= p.prev_start_date
                  AND o.created_at <  p.prev_end_date + 1
            )
            SELECT
                c.service_revenue,
                pr.prev_service_revenue,
                p.target_service_revenue,
                ROUND(c.service_revenue / NULLIF(p.target_service_revenue, 0) * 100, 2) AS progress_percent,
                ROUND((c.service_revenue - pr.prev_service_revenue) / NULLIF(pr.prev_service_revenue, 0) * 100, 2) AS growth_vs_previous_percent,
                CASE
                    WHEN c.service_revenue / NULLIF(p.target_service_revenue, 0) >= 1 THEN 'VƯỢT CHỈ TIÊU'
                    WHEN c.service_revenue / NULLIF(p.target_service_revenue, 0) >= 0.75 THEN 'ĐANG ỔN'
                    WHEN c.service_revenue / NULLIF(p.target_service_revenue, 0) >= 0.50 THEN 'CHẬM TIẾN ĐỘ'
                    ELSE 'CẢNH BÁO ĐỎ: DOANH THU DỊCH VỤ THẤP'
                END AS progress_warning
            FROM current_revenue c
            CROSS JOIN previous_revenue pr
            CROSS JOIN params p
            SQL;

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_branch_id' => $branchId,
            'p_start_date' => $period['start_date'],
            'p_end_date' => $period['end_date'],
            'p_prev_start_date' => $period['prev_start_date'],
            'p_prev_end_date' => $period['prev_end_date'],
            'p_target_service_revenue' => self::TEMPORARY_TARGET_SERVICE_REVENUE,
        ]), CASE_LOWER);

        $current = (float) ($row['service_revenue'] ?? 0);
        $previous = (float) ($row['prev_service_revenue'] ?? 0);
        $target = (float) ($row['target_service_revenue'] ?? self::TEMPORARY_TARGET_SERVICE_REVENUE);
        $progressPercent = $this->nullableFloat($row['progress_percent'] ?? null);
        $growthPercent = $this->nullableFloat($row['growth_vs_previous_percent'] ?? null);
        $warning = (string) ($row['progress_warning'] ?? 'CẢNH BÁO ĐỎ: DOANH THU DỊCH VỤ THẤP');
        $warningLevel = $this->warningLevel($progressPercent);
        $trend = $this->trend($growthPercent);

        return [
            'service_revenue' => [
                'current' => $current,
                'previous' => $previous,
                'target' => $target,
                'progress_percent' => $progressPercent,
                'growth_vs_previous_percent' => $growthPercent,
                'warning' => $warning,
                'warning_level' => $warningLevel,
                'alert_type' => $warningLevel === 'danger' ? 'danger' : 'warning',
                'trend' => $trend,
                'comparison' => [
                    'change_percent' => $growthPercent,
                    'trend' => $trend,
                ],
                'period' => [
                    'start_date' => $period['start_date'],
                    'end_date' => $period['end_date'],
                    'prev_start_date' => $period['prev_start_date'],
                    'prev_end_date' => $period['prev_end_date'],
                ],
            ],
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach dich vu tai chi nhanh co doanh thu giam tu 20% so voi ky truoc.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $period: start_date, end_date, prev_start_date, prev_end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang gom revenue_drop_alerts, summary va warning_level.
     *
     * Ghi chu:
     * - SQL Oracle dat tai Repository va bind day du branch/date.
     * - Dich vu khong co doanh thu ky truoc khong du co so de tinh phan tram giam.
     */
    public function getRevenueDropAlerts(int|string $branchId, array $period = []): array
    {
        $period = $this->normalizeFilters($period);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :p_branch_id AS branch_id,
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date,
                    TRUNC(TO_DATE(:p_prev_start_date, 'YYYY-MM-DD')) AS prev_start_date,
                    TRUNC(TO_DATE(:p_prev_end_date, 'YYYY-MM-DD')) AS prev_end_date
                FROM dual
            ),
            service_revenue AS (
                SELECT
                    s.service_id,
                    s.service_name,
                    s.species,
                    NVL(SUM(
                        CASE
                            WHEN o.created_at >= p.start_date
                             AND o.created_at <  p.end_date + 1
                            THEN NVL(od.line_total, 0)
                            ELSE 0
                        END
                    ), 0) AS current_revenue,
                    NVL(SUM(
                        CASE
                            WHEN o.created_at >= p.prev_start_date
                             AND o.created_at <  p.prev_end_date + 1
                            THEN NVL(od.line_total, 0)
                            ELSE 0
                        END
                    ), 0) AS previous_revenue
                FROM params p
                JOIN orders o
                    ON o.branch_id = p.branch_id
                JOIN order_details od
                    ON od.order_id = o.order_id
                JOIN booking_service_pet bsp
                    ON bsp.booking_service_pet_id = od.booking_service_pet_id
                JOIN services s
                    ON s.service_id = bsp.service_id
                WHERE od.booking_service_pet_id IS NOT NULL
                  AND o.status IN ('PAID', 'COMPLETED')
                  AND (
                        (
                            o.created_at >= p.start_date
                            AND o.created_at <  p.end_date + 1
                        )
                        OR (
                            o.created_at >= p.prev_start_date
                            AND o.created_at <  p.prev_end_date + 1
                        )
                  )
                GROUP BY
                    s.service_id,
                    s.service_name,
                    s.species
            ),
            revenue_drop AS (
                SELECT
                    service_id,
                    service_name,
                    species,
                    current_revenue,
                    previous_revenue,
                    previous_revenue - current_revenue AS revenue_drop_amount,
                    ROUND(
                        (previous_revenue - current_revenue)
                        / NULLIF(previous_revenue, 0) * 100,
                        2
                    ) AS revenue_drop_percent
                FROM service_revenue
                WHERE previous_revenue > 0
                  AND (
                        (previous_revenue - current_revenue)
                        / NULLIF(previous_revenue, 0)
                  ) >= 0.20
            )
            SELECT
                service_id,
                service_name,
                species,
                current_revenue,
                previous_revenue,
                revenue_drop_amount,
                revenue_drop_percent,
                CASE
                    WHEN revenue_drop_percent >= 50 THEN 'danger'
                    ELSE 'warning'
                END AS warning_level
            FROM revenue_drop
            ORDER BY
                revenue_drop_percent DESC,
                service_id
            SQL;

        $alerts = array_map(function (object $row): array {
            $row = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'service_id' => (int) ($row['service_id'] ?? 0),
                'service_name' => (string) ($row['service_name'] ?? ''),
                'species' => (string) ($row['species'] ?? ''),
                'current_revenue' => (float) ($row['current_revenue'] ?? 0),
                'previous_revenue' => (float) ($row['previous_revenue'] ?? 0),
                'revenue_drop_amount' => (float) ($row['revenue_drop_amount'] ?? 0),
                'revenue_drop_percent' => (float) ($row['revenue_drop_percent'] ?? 0),
                'warning_level' => (string) ($row['warning_level'] ?? 'warning'),
            ];
        }, DB::select($sql, [
            'p_branch_id' => $branchId,
            'p_start_date' => $period['start_date'],
            'p_end_date' => $period['end_date'],
            'p_prev_start_date' => $period['prev_start_date'],
            'p_prev_end_date' => $period['prev_end_date'],
        ]));

        $warningLevel = empty($alerts)
            ? 'normal'
            : (in_array('danger', array_column($alerts, 'warning_level'), true) ? 'danger' : 'warning');

        return [
            'revenue_drop_alerts' => $alerts,
            'summary' => [
                'alert_count' => count($alerts),
                'threshold_percent' => 20,
                'message' => empty($alerts)
                    ? 'Không có dịch vụ giảm mạnh trong kỳ này'
                    : count($alerts) . ' dịch vụ giảm doanh thu từ 20% trở lên',
            ],
            'warning_level' => $warningLevel,
        ];
    }

    /**
     * Get the local top service for the selected branch and period.
     */
    public function getLocalTopService(int|string $branchId, array $period = []): ?array
    {
        // TODO: Find the highest-revenue valid service at this branch and exclude cancelled or unpaid transactions.
        return null;
    }

    /**
     * Mo ta chuc nang:
     * Lay KPI ty le upsell cua booking co phong va mua them dich vu tai chi nhanh.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $period: start_date, end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang upsell_rate gom total_room_bookings, upsell_bookings,
     *   upsell_rate_percent, warning va warning_level.
     *
     * Ghi chu:
     * - SQL Oracle dat tai Repository va bind p_branch_id, p_start_date, p_end_date.
     * - Khi khong co booking phong, ty le upsell la null de tranh chia cho 0.
     */
    public function getUpsellRate(int|string $branchId, array $period = []): array
    {
        $period = $this->normalizeFilters($period);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :p_branch_id AS branch_id,
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date
                FROM dual
            ),
            room_bookings AS (
                SELECT DISTINCT
                    b.booking_id
                FROM params p
                JOIN booking b
                    ON b.branch_id = p.branch_id
                JOIN booking_room br
                    ON br.booking_id = b.booking_id
                WHERE b.created_at >= p.start_date
                  AND b.created_at <  p.end_date + 1
                  AND b.status IN ('CONFIRMED', 'CHECKED_IN', 'CHECKED_OUT', 'COMPLETED')
            ),
            room_plus_service AS (
                SELECT DISTINCT
                    rb.booking_id
                FROM room_bookings rb
                JOIN booking_service_pet bsp
                    ON bsp.booking_id = rb.booking_id
                WHERE bsp.status IN ('ASSIGNED', 'SCHEDULED', 'IN_PROGRESS', 'DONE')
            ),
            upsell_summary AS (
                SELECT
                    (SELECT COUNT(*) FROM room_bookings) AS total_room_bookings,
                    (SELECT COUNT(*) FROM room_plus_service) AS upsell_bookings
                FROM dual
            )
            SELECT
                total_room_bookings,
                upsell_bookings,
                ROUND(
                    upsell_bookings / NULLIF(total_room_bookings, 0) * 100,
                    2
                ) AS upsell_rate_percent,
                CASE
                    WHEN total_room_bookings = 0 THEN 'CHƯA CÓ DỮ LIỆU BOOKING PHÒNG'
                    WHEN upsell_bookings / NULLIF(total_room_bookings, 0) >= 0.25 THEN 'UPSELL TỐT'
                    WHEN upsell_bookings / NULLIF(total_room_bookings, 0) >= 0.15 THEN 'UPSELL TRUNG BÌNH'
                    ELSE 'CẢNH BÁO: CẦN CẢI THIỆN UPSELL'
                END AS upsell_warning
            FROM upsell_summary
            SQL;

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_branch_id' => $branchId,
            'p_start_date' => $period['start_date'],
            'p_end_date' => $period['end_date'],
        ]), CASE_LOWER);

        $totalRoomBookings = (int) ($row['total_room_bookings'] ?? 0);
        $upsellRatePercent = $this->nullableFloat($row['upsell_rate_percent'] ?? null);

        return [
            'upsell_rate' => [
                'total_room_bookings' => $totalRoomBookings,
                'upsell_bookings' => (int) ($row['upsell_bookings'] ?? 0),
                'upsell_rate_percent' => $upsellRatePercent,
                'warning' => (string) ($row['upsell_warning'] ?? 'CHƯA CÓ DỮ LIỆU BOOKING PHÒNG'),
                'warning_level' => $this->upsellWarningLevel($upsellRatePercent, $totalRoomBookings),
            ],
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach dich vu quan tri cho chi nhanh Manager.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can hien thi danh sach.
     * - array $filters: search, service_group va status neu co.
     *
     * Output:
     * - Mang dich vu gom ma, ten, danh muc, loai pet, thoi luong, gia,
     *   hien thi website, khoa khan cap, trang thai va chi so doanh thu hai ky.
     *
     * Ghi chu:
     * - SQL Oracle dat tai Repository va bind p_branch_id, ngay cung bo loc tuy chon.
     * - Schema chua co bang cau hinh dich vu theo chi nhanh, nen hien thi website,
     *   khoa khan cap va gia override dang tam suy ra tu services.is_active/base_price.
     */
    public function getServiceList(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :p_branch_id AS branch_id,
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date,
                    TRUNC(TO_DATE(:p_prev_start_date, 'YYYY-MM-DD')) AS prev_start_date,
                    TRUNC(TO_DATE(:p_prev_end_date, 'YYYY-MM-DD')) AS prev_end_date,
                    TRIM(:p_search) AS search_term,
                    TRIM(:p_service_group) AS service_group,
                    LOWER(TRIM(:p_status)) AS status_filter
                FROM dual
            ),
            branch_scope AS (
                SELECT
                    br.branch_id,
                    p.start_date,
                    p.end_date,
                    p.prev_start_date,
                    p.prev_end_date,
                    p.search_term,
                    p.service_group,
                    p.status_filter
                FROM params p
                JOIN branch br
                    ON br.branch_id = p.branch_id
            ),
            service_revenue AS (
                SELECT
                    s.service_id,
                    NVL(SUM(
                        CASE
                            WHEN o.branch_id = bs.branch_id
                             AND o.status IN ('PAID', 'COMPLETED')
                             AND o.created_at >= bs.start_date
                             AND o.created_at <  bs.end_date + 1
                            THEN NVL(od.line_total, 0)
                            ELSE 0
                        END
                    ), 0) AS current_revenue,
                    NVL(SUM(
                        CASE
                            WHEN o.branch_id = bs.branch_id
                             AND o.status IN ('PAID', 'COMPLETED')
                             AND o.created_at >= bs.prev_start_date
                             AND o.created_at <  bs.prev_end_date + 1
                            THEN NVL(od.line_total, 0)
                            ELSE 0
                        END
                    ), 0) AS previous_revenue
                FROM branch_scope bs
                CROSS JOIN services s
                LEFT JOIN booking_service_pet bsp
                    ON bsp.service_id = s.service_id
                LEFT JOIN order_details od
                    ON od.booking_service_pet_id = bsp.booking_service_pet_id
                LEFT JOIN orders o
                    ON o.order_id = od.order_id
                GROUP BY
                    s.service_id
            )
            SELECT
                bs.branch_id,
                s.service_id,
                'SV' || LPAD(TO_CHAR(s.service_id), 3, '0') AS service_code,
                s.service_name,
                cs.service_category_name,
                s.species,
                s.duration_minutes,
                s.base_price AS original_price,
                s.base_price AS override_price_assumption,
                s.is_active,
                CASE WHEN s.is_active = 1 THEN 'BẬT' ELSE 'TẮT' END AS website_visibility_assumption,
                CASE WHEN s.is_active = 1 THEN 'KHÔNG KHÓA' ELSE 'ĐANG KHÓA' END AS emergency_lock_assumption,
                CASE WHEN s.is_active = 1 THEN 'HOẠT ĐỘNG' ELSE 'NGỪNG CUNG CẤP' END AS status_text,
                sr.current_revenue,
                sr.previous_revenue,
                ROUND(
                    (sr.current_revenue - sr.previous_revenue)
                    / NULLIF(sr.previous_revenue, 0) * 100,
                    2
                ) AS growth_percent,
                CASE
                    WHEN sr.previous_revenue > 0
                     AND (sr.current_revenue - sr.previous_revenue)
                         / NULLIF(sr.previous_revenue, 0) <= -0.20
                    THEN 'CẢNH BÁO: DOANH THU DỊCH VỤ GIẢM TỪ 20%'
                    WHEN sr.previous_revenue > 0
                     AND (sr.current_revenue - sr.previous_revenue)
                         / NULLIF(sr.previous_revenue, 0) >= 0.20
                    THEN 'TĂNG TRƯỞNG TỐT'
                    ELSE 'ỔN ĐỊNH'
                END AS revenue_warning_text,
                TO_CHAR(s.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,
                TO_CHAR(s.updated_at, 'YYYY-MM-DD HH24:MI:SS') AS updated_at
            FROM branch_scope bs
            CROSS JOIN services s
            JOIN category_services cs
                ON cs.service_category_id = s.service_category_id
            JOIN service_revenue sr
                ON sr.service_id = s.service_id
            WHERE (
                    bs.search_term IS NULL
                    OR UPPER(s.service_name) LIKE '%' || UPPER(bs.search_term) || '%'
                    OR UPPER('SV' || LPAD(TO_CHAR(s.service_id), 3, '0')) LIKE '%' || UPPER(bs.search_term) || '%'
            )
              AND (
                    bs.service_group IS NULL
                    OR UPPER(cs.service_category_name) = UPPER(bs.service_group)
              )
              AND (
                    bs.status_filter IS NULL
                    OR (bs.status_filter = 'active' AND s.is_active = 1)
                    OR (bs.status_filter IN ('inactive', 'paused') AND s.is_active = 0)
              )
            ORDER BY
                cs.service_category_name,
                s.service_name
            SQL;

        return array_map(function (object $row): array {
            $row = array_change_key_case((array) $row, CASE_LOWER);
            $isActive = (int) ($row['is_active'] ?? 0) === 1;

            return [
                'branch_id' => (int) ($row['branch_id'] ?? 0),
                'service_id' => (int) ($row['service_id'] ?? 0),
                'service_code' => (string) ($row['service_code'] ?? ''),
                'service_name' => (string) ($row['service_name'] ?? ''),
                'service_category_name' => (string) ($row['service_category_name'] ?? ''),
                'species' => (string) ($row['species'] ?? ''),
                'duration_minutes' => isset($row['duration_minutes']) ? (int) $row['duration_minutes'] : null,
                'original_price' => (float) ($row['original_price'] ?? 0),
                'override_price_assumption' => (float) ($row['override_price_assumption'] ?? 0),
                'is_active' => $isActive ? 1 : 0,
                'website_visible' => $isActive,
                'website_visibility_assumption' => (string) ($row['website_visibility_assumption'] ?? ''),
                'is_emergency_locked' => ! $isActive,
                'emergency_lock_assumption' => (string) ($row['emergency_lock_assumption'] ?? ''),
                'status_text' => (string) ($row['status_text'] ?? ''),
                'current_revenue' => (float) ($row['current_revenue'] ?? 0),
                'previous_revenue' => (float) ($row['previous_revenue'] ?? 0),
                'growth_percent' => $this->nullableFloat($row['growth_percent'] ?? null),
                'revenue_warning_text' => (string) ($row['revenue_warning_text'] ?? 'ỔN ĐỊNH'),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'updated_at' => (string) ($row['updated_at'] ?? ''),
            ];
        }, DB::select($sql, [
            'p_branch_id' => $branchId,
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
            'p_prev_start_date' => $filters['prev_start_date'],
            'p_prev_end_date' => $filters['prev_end_date'],
            'p_search' => filled($filters['search'] ?? null) ? trim((string) $filters['search']) : null,
            'p_service_group' => filled($filters['service_group'] ?? null) ? trim((string) $filters['service_group']) : null,
            'p_status' => filled($filters['status'] ?? null) ? trim((string) $filters['status']) : null,
        ]));
    }

    /**
     * Resolve the reporting period into a date range.
     */
    public function resolvePeriodRange(?string $periodType, ?string $date = null): array
    {
        // TODO: Resolve day, month, or year period filters into a start date, end date, and grouping rule.
        return [];
    }

    /**
     * Mo ta chuc nang:
     * Normalize filter ngay tu Carbon/string/null ve chuoi YYYY-MM-DD truoc khi bind Oracle.
     *
     * Input:
     * - array $filters co the gom start_date, end_date, prev_start_date, prev_end_date.
     *
     * Output:
     * - Mang filters co du 4 moc ngay dang YYYY-MM-DD.
     *
     * Ghi chu:
     * - Khi frontend chua chon ngay, mac dinh la thang hien tai.
     */
    private function normalizeFilters(array $filters = []): array
    {
        $startDate = $this->dateString($filters['start_date'] ?? null);
        $endDate = $this->dateString($filters['end_date'] ?? null);
        $prevStartDate = $this->dateString($filters['prev_start_date'] ?? null);
        $prevEndDate = $this->dateString($filters['prev_end_date'] ?? null);

        if ($startDate === null || $endDate === null) {
            $currentMonth = $this->currentMonthFilters();
            $startDate ??= $currentMonth['start_date'];
            $endDate ??= $currentMonth['end_date'];
        }

        $filters['start_date'] = $startDate;
        $filters['end_date'] = $endDate;

        if ($prevStartDate === null || $prevEndDate === null) {
            $previousPeriod = $this->previousPeriodFilters($filters);
            $prevStartDate ??= $previousPeriod['prev_start_date'];
            $prevEndDate ??= $previousPeriod['prev_end_date'];
        }

        $filters['prev_start_date'] = $prevStartDate;
        $filters['prev_end_date'] = $prevEndDate;

        return $filters;
    }

    /**
     * Mo ta chuc nang:
     * Tao khoang ngay mac dinh theo thang hien tai.
     *
     * Input:
     * - Khong co.
     *
     * Output:
     * - start_date va end_date dang YYYY-MM-DD.
     *
     * Ghi chu:
     * - Dung khi DashboardEngine goi lan dau voi filter rong.
     */
    private function currentMonthFilters(): array
    {
        $today = CarbonImmutable::today(config('app.timezone'));

        return [
            'start_date' => $today->startOfMonth()->toDateString(),
            'end_date' => $today->endOfMonth()->toDateString(),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Tinh ky truoc tu start_date va end_date da normalize.
     *
     * Input:
     * - array $filters co start_date va end_date.
     *
     * Output:
     * - prev_start_date va prev_end_date dang YYYY-MM-DD.
     *
     * Ghi chu:
     * - Ky truoc co cung do dai voi ky hien tai.
     */
    private function previousPeriodFilters(array $filters): array
    {
        $startDate = $this->dateFromString($filters['start_date']);
        $endDate = $this->dateFromString($filters['end_date']);
        $daysInPeriod = abs((int) $startDate->diffInDays($endDate)) + 1;
        $previousEndDate = $startDate->subDay();
        $previousStartDate = $previousEndDate->subDays($daysInPeriod - 1);

        return [
            'prev_start_date' => $previousStartDate->toDateString(),
            'prev_end_date' => $previousEndDate->toDateString(),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Chuyen gia tri ngay Carbon/string ve chuoi YYYY-MM-DD.
     *
     * Input:
     * - mixed $value: DateTimeInterface, string hoac null.
     *
     * Output:
     * - Chuoi ngay YYYY-MM-DD hoac null.
     *
     * Ghi chu:
     * - Tranh bind Carbon truc tiep vao Oracle.
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
     * Tao CarbonImmutable tu gia tri ngay da normalize.
     *
     * Input:
     * - mixed $value: Ngay bat ky hop le.
     *
     * Output:
     * - CarbonImmutable theo timezone app.
     *
     * Ghi chu:
     * - Dung noi bo de tinh ky truoc.
     */
    private function dateFromString(mixed $value): CarbonImmutable
    {
        $date = $this->dateString($value) ?? CarbonImmutable::today(config('app.timezone'))->toDateString();

        return CarbonImmutable::createFromFormat('!Y-m-d', $date, config('app.timezone'));
    }

    /**
     * Mo ta chuc nang:
     * Ep kieu float nhung giu null cho gia tri khong tinh duoc.
     *
     * Input:
     * - mixed $value: Gia tri tu SQL.
     *
     * Output:
     * - float hoac null.
     *
     * Ghi chu:
     * - Dung cho progress/growth co the null khi chia cho 0.
     */
    private function nullableFloat(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    /**
     * Mo ta chuc nang:
     * Suy ra trend tu phan tram tang/giam ky truoc.
     *
     * Input:
     * - ?float $growthPercent: Phan tram tang/giam so voi ky truoc.
     *
     * Output:
     * - up, down, neutral hoac no_previous_data.
     *
     * Ghi chu:
     * - null nghia la ky truoc bang 0 hoac khong co du lieu.
     */
    private function trend(?float $growthPercent): string
    {
        if ($growthPercent === null) {
            return 'no_previous_data';
        }

        return match (true) {
            $growthPercent > 0 => 'up',
            $growthPercent < 0 => 'down',
            default => 'neutral',
        };
    }

    /**
     * Mo ta chuc nang:
     * Suy ra muc canh bao tu phan tram hoan thanh chi tieu.
     *
     * Input:
     * - ?float $progressPercent: Phan tram hoan thanh chi tieu.
     *
     * Output:
     * - success, normal, warning hoac danger.
     *
     * Ghi chu:
     * - Nguong canh bao theo de bai: >=100, >=75, >=50 va duoi 50.
     */
    private function warningLevel(?float $progressPercent): string
    {
        if ($progressPercent === null || $progressPercent < 50) {
            return 'danger';
        }

        if ($progressPercent < 75) {
            return 'warning';
        }

        if ($progressPercent < 100) {
            return 'normal';
        }

        return 'success';
    }

    /**
     * Mo ta chuc nang:
     * Suy ra muc danh gia tu ty le upsell cua booking phong.
     *
     * Input:
     * - ?float $upsellRatePercent: Phan tram booking phong co mua them dich vu.
     * - int $totalRoomBookings: Tong booking phong hop le trong ky.
     *
     * Output:
     * - success, normal, warning hoac neutral.
     *
     * Ghi chu:
     * - Nguong danh gia theo de bai: >=25, >=15 va duoi 15.
     */
    private function upsellWarningLevel(?float $upsellRatePercent, int $totalRoomBookings): string
    {
        if ($totalRoomBookings === 0 || $upsellRatePercent === null) {
            return 'neutral';
        }

        if ($upsellRatePercent >= 25) {
            return 'success';
        }

        if ($upsellRatePercent >= 15) {
            return 'normal';
        }

        return 'warning';
    }
}
