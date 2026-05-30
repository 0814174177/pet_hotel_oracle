<?php

namespace App\Repositories\Eloquent\Ceo;

use App\Repositories\Contracts\Ceo\CeoDashboardRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CeoDashboardRepository implements CeoDashboardRepositoryInterface
{
    /**
     * Get the full CEO dashboard structure.
     */
    public function getDashboard(array $filters = []): array
    {
        return [
            'filters' => $filters,
            'operation_overview' => $this->getOperationOverview($filters),
            'finance_overview' => $this->getFinanceOverview($filters),
            'strategy_risk' => $this->getStrategyRiskOverview($filters),
        ];
    }

    /**
     * Backward-compatible entry point for older DashboardController callers.
     */
    public function getDashboardData(array|string $filters = []): array
    {
        // TODO: Remove this compatibility wrapper after callers use getDashboard directly.
        return $this->getDashboard(is_array($filters) ? $filters : ['period' => $filters]);
    }

    /**
     * Get CEO operation overview metrics.
     */
    public function getOperationOverview(array $filters = []): array
    {
        return [
            'current_hotel_occupancy' => $this->getCurrentHotelOccupancy($filters),
            'occupancy_rate' => $this->getOccupancyRate($filters),
            'revpar' => $this->getRevpar($filters),
            'customer_trend' => $this->getCustomerTrend($filters),
        ];
    }

    /**
     * Get CEO finance overview metrics.
     */
    public function getFinanceOverview(array $filters = []): array
    {
        return [
            'total_revenue' => $this->getTotalRevenue($filters),
            'estimated_cogs' => $this->getEstimatedCogs($filters),
            'revenue_mix' => $this->getRevenueMix($filters),
            'revenue_and_cogs_trend' => $this->getRevenueAndCogsTrend($filters),
        ];
    }

    public function getTotalRevenue(array $filters): array
    {
        $sql = "
            WITH cur AS (
                SELECT SUM(o.grand_total) AS total_revenue
                FROM orders o
                WHERE o.status IN ('PAID', 'COMPLETED')
                  AND o.paid_at >= TO_DATE(:p_start_date, 'YYYY-MM-DD')
                  AND o.paid_at <  TO_DATE(:p_end_date, 'YYYY-MM-DD') + 1
            ),
            prev AS (
                SELECT SUM(o.grand_total) AS total_revenue
                FROM orders o
                WHERE o.status IN ('PAID', 'COMPLETED')
                  AND o.paid_at >= TO_DATE(:p_prev_start_date, 'YYYY-MM-DD')
                  AND o.paid_at <  TO_DATE(:p_prev_end_date, 'YYYY-MM-DD') + 1
            )
            SELECT
                NVL(cur.total_revenue, 0) AS current_total_revenue,
                NVL(prev.total_revenue, 0) AS previous_total_revenue,
                ROUND(
                    (NVL(cur.total_revenue, 0) - NVL(prev.total_revenue, 0))
                    / NULLIF(prev.total_revenue, 0) * 100,
                    2
                ) AS revenue_growth_percent
            FROM cur
            CROSS JOIN prev
        ";

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
            'p_prev_start_date' => $filters['prev_start_date'],
            'p_prev_end_date' => $filters['prev_end_date'],
        ]), CASE_LOWER);

        return [
            'current_total_revenue' => (float) ($row['current_total_revenue'] ?? 0),
            'previous_total_revenue' => (float) ($row['previous_total_revenue'] ?? 0),
            'revenue_growth_percent' => isset($row['revenue_growth_percent'])
                ? (float) $row['revenue_growth_percent']
                : null,
        ];
    }

    /**
     * Get CEO strategy and risk overview metrics.
     * 
     */
    public function getStrategyRiskOverview(array $filters = []): array
    {
        return [
            'branch_revenue' => $this->getBranchRevenue($filters),
            'branch_ranking' => $this->getBranchRanking($filters),
            'top_used_services' => $this->getTopUsedServices($filters),
            'risk_alerts' => $this->getRiskAlerts($filters),
        ];
    }

    /**
     * Get current hotel occupancy from the room status snapshot.
     */
    public function getCurrentHotelOccupancy(array $filters = []): array
    {
        $sql = "
            SELECT
                COUNT(r.room_id) AS total_room,
                SUM(CASE WHEN r.status = 'IN_USE' THEN 1 ELSE 0 END) AS occupied_room,
                SUM(CASE WHEN r.status = 'AVAILABLE' THEN 1 ELSE 0 END) AS available_room,
                SUM(CASE WHEN r.status = 'MAINTENANCE' THEN 1 ELSE 0 END) AS maintenance_room,
                SUM(CASE WHEN r.status IN ('AVAILABLE', 'IN_USE') THEN 1 ELSE 0 END) AS usable_room,
                ROUND(
                    SUM(CASE WHEN r.status = 'IN_USE' THEN 1 ELSE 0 END)
                    / NULLIF(SUM(CASE WHEN r.status IN ('AVAILABLE', 'IN_USE') THEN 1 ELSE 0 END), 0)
                    * 100,
                    2
                ) AS occupancy_rate
            FROM room r
            JOIN branch b
                ON b.branch_id = r.branch_id
            WHERE b.is_active = 1
        ";

        $row = array_change_key_case((array) DB::selectOne($sql), CASE_LOWER);

        return [
            'total_room' => (int) ($row['total_room'] ?? 0),
            'occupied_room' => (int) ($row['occupied_room'] ?? 0),
            'available_room' => (int) ($row['available_room'] ?? 0),
            'maintenance_room' => (int) ($row['maintenance_room'] ?? 0),
            'usable_room' => (int) ($row['usable_room'] ?? 0),
            'occupancy_rate' => $this->nullableFloat($row['occupancy_rate'] ?? null) ?? 0.0,
        ];
    }

    /**
     * Get hotel room occupancy rate.
     */
    public function getOccupancyRate(array $filters = []): array
    {
        $periodFilters = $this->getPreviousPeriodFilters($filters);

        $sql = "
            WITH total_room AS (
                SELECT COUNT(r.room_id) AS total_room_count
                FROM room r
                JOIN branch b
                    ON b.branch_id = r.branch_id
                WHERE b.is_active = 1
            ),
            cur AS (
                SELECT COUNT(DISTINCT br.room_id) AS used_room_count
                FROM booking bk
                JOIN booking_room br
                    ON br.booking_id = bk.booking_id
                JOIN room r
                    ON r.room_id = br.room_id
                JOIN branch b
                    ON b.branch_id = r.branch_id
                WHERE b.is_active = 1
                  AND bk.status IN ('CONFIRMED', 'COMPLETED')
                  AND bk.checkin_expected_at <  TO_DATE(:p_end_date, 'YYYY-MM-DD') + 1
                  AND bk.checkout_expected_at >= TO_DATE(:p_start_date, 'YYYY-MM-DD')
            ),
            prev AS (
                SELECT COUNT(DISTINCT br.room_id) AS used_room_count
                FROM booking bk
                JOIN booking_room br
                    ON br.booking_id = bk.booking_id
                JOIN room r
                    ON r.room_id = br.room_id
                JOIN branch b
                    ON b.branch_id = r.branch_id
                WHERE b.is_active = 1
                  AND bk.status IN ('CONFIRMED', 'COMPLETED')
                  AND bk.checkin_expected_at <  TO_DATE(:p_prev_end_date, 'YYYY-MM-DD') + 1
                  AND bk.checkout_expected_at >= TO_DATE(:p_prev_start_date, 'YYYY-MM-DD')
            )
            SELECT
                tr.total_room_count AS total_room,

                cur.used_room_count AS current_occupied_room,
                tr.total_room_count - cur.used_room_count AS current_available_room,
                ROUND(cur.used_room_count / NULLIF(tr.total_room_count, 0) * 100, 2) AS current_occupancy_rate,

                prev.used_room_count AS previous_occupied_room,
                tr.total_room_count - prev.used_room_count AS previous_available_room,
                ROUND(prev.used_room_count / NULLIF(tr.total_room_count, 0) * 100, 2) AS previous_occupancy_rate,

                ROUND(
                    (
                        (cur.used_room_count / NULLIF(tr.total_room_count, 0))
                        -
                        (prev.used_room_count / NULLIF(tr.total_room_count, 0))
                    ) * 100,
                    2
                ) AS occupancy_rate_diff_percent_point,

                ROUND(
                    (
                        (
                            cur.used_room_count / NULLIF(tr.total_room_count, 0) * 100
                        )
                        -
                        (
                            prev.used_room_count / NULLIF(tr.total_room_count, 0) * 100
                        )
                    )
                    / NULLIF(
                        prev.used_room_count / NULLIF(tr.total_room_count, 0) * 100,
                        0
                    ) * 100,
                    2
                ) AS occupancy_rate_change_percent
            FROM cur
            CROSS JOIN prev
            CROSS JOIN total_room tr
        ";

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_start_date' => $periodFilters['start_date'],
            'p_end_date' => $periodFilters['end_date'],
            'p_prev_start_date' => $periodFilters['prev_start_date'],
            'p_prev_end_date' => $periodFilters['prev_end_date'],
        ]), CASE_LOWER);

        $totalRoom = (int) ($row['total_room'] ?? 0);
        $currentOccupiedRoom = (int) ($row['current_occupied_room'] ?? 0);
        $previousOccupiedRoom = (int) ($row['previous_occupied_room'] ?? 0);
        $currentOccupancyRate = $this->nullableFloat($row['current_occupancy_rate'] ?? null) ?? 0.0;
        $previousOccupancyRate = $this->nullableFloat($row['previous_occupancy_rate'] ?? null) ?? 0.0;
        $diffPercentPoint = $this->nullableFloat($row['occupancy_rate_diff_percent_point'] ?? null) ?? 0.0;
        $changePercent = $this->nullableFloat($row['occupancy_rate_change_percent'] ?? null);

        return [
            'current' => [
                'total_room' => $totalRoom,
                'occupied_room' => $currentOccupiedRoom,
                'available_room' => max(0, $totalRoom - $currentOccupiedRoom),
                'occupancy_rate' => $currentOccupancyRate,
            ],
            'previous' => [
                'total_room' => $totalRoom,
                'occupied_room' => $previousOccupiedRoom,
                'available_room' => max(0, $totalRoom - $previousOccupiedRoom),
                'occupancy_rate' => $previousOccupancyRate,
            ],
            'comparison' => [
                'diff_percent_point' => $diffPercentPoint,
                'change_percent' => $changePercent,
                'trend' => $this->getTrend($changePercent),
            ],
        ];
    }

    /**
     * Get RevPAR.
     */
    public function getRevpar(array $filters = []): array
    {
        $periodFilters = $this->getPreviousPeriodFilters($filters);

        $sql = "
            WITH total_room AS (
                SELECT COUNT(r.room_id) AS total_room_count
                FROM room r
                JOIN branch b
                    ON b.branch_id = r.branch_id
                WHERE b.is_active = 1
            ),
            cur AS (
                SELECT NVL(SUM(od.line_total), 0) AS total_room_revenue
                FROM order_details od
                JOIN orders o
                    ON o.order_id = od.order_id
                JOIN booking_room br
                    ON br.booking_room_id = od.booking_room_id
                JOIN room r
                    ON r.room_id = br.room_id
                JOIN branch b
                    ON b.branch_id = r.branch_id
                WHERE od.booking_room_id IS NOT NULL
                  AND od.booking_service_pet_id IS NULL
                  AND b.is_active = 1
                  AND o.status IN ('PAID', 'COMPLETED')
                  AND o.paid_at >= TO_DATE(:p_start_date, 'YYYY-MM-DD')
                  AND o.paid_at <  TO_DATE(:p_end_date, 'YYYY-MM-DD') + 1
            ),
            prev AS (
                SELECT NVL(SUM(od.line_total), 0) AS total_room_revenue
                FROM order_details od
                JOIN orders o
                    ON o.order_id = od.order_id
                JOIN booking_room br
                    ON br.booking_room_id = od.booking_room_id
                JOIN room r
                    ON r.room_id = br.room_id
                JOIN branch b
                    ON b.branch_id = r.branch_id
                WHERE od.booking_room_id IS NOT NULL
                  AND od.booking_service_pet_id IS NULL
                  AND b.is_active = 1
                  AND o.status IN ('PAID', 'COMPLETED')
                  AND o.paid_at >= TO_DATE(:p_prev_start_date, 'YYYY-MM-DD')
                  AND o.paid_at <  TO_DATE(:p_prev_end_date, 'YYYY-MM-DD') + 1
            )
            SELECT
                tr.total_room_count AS total_room,

                cur.total_room_revenue AS current_room_revenue,
                ROUND(cur.total_room_revenue / NULLIF(tr.total_room_count, 0), 2) AS current_revpar,

                prev.total_room_revenue AS previous_room_revenue,
                ROUND(prev.total_room_revenue / NULLIF(tr.total_room_count, 0), 2) AS previous_revpar,

                ROUND(
                    (
                        cur.total_room_revenue / NULLIF(tr.total_room_count, 0)
                    )
                    -
                    (
                        prev.total_room_revenue / NULLIF(tr.total_room_count, 0)
                    ),
                    2
                ) AS revpar_diff_amount,

                ROUND(
                    (
                        (
                            cur.total_room_revenue / NULLIF(tr.total_room_count, 0)
                        )
                        -
                        (
                            prev.total_room_revenue / NULLIF(tr.total_room_count, 0)
                        )
                    )
                    / NULLIF(
                        prev.total_room_revenue / NULLIF(tr.total_room_count, 0),
                        0
                    ) * 100,
                    2
                ) AS revpar_change_percent
            FROM cur
            CROSS JOIN prev
            CROSS JOIN total_room tr
        ";

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_start_date' => $periodFilters['start_date'],
            'p_end_date' => $periodFilters['end_date'],
            'p_prev_start_date' => $periodFilters['prev_start_date'],
            'p_prev_end_date' => $periodFilters['prev_end_date'],
        ]), CASE_LOWER);

        $totalRoom = (int) ($row['total_room'] ?? 0);
        $currentRoomRevenue = $this->nullableFloat($row['current_room_revenue'] ?? null) ?? 0.0;
        $previousRoomRevenue = $this->nullableFloat($row['previous_room_revenue'] ?? null) ?? 0.0;
        $currentRevpar = $this->nullableFloat($row['current_revpar'] ?? null) ?? 0.0;
        $previousRevpar = $this->nullableFloat($row['previous_revpar'] ?? null) ?? 0.0;
        $diffAmount = $this->nullableFloat($row['revpar_diff_amount'] ?? null) ?? 0.0;
        $changePercent = $this->nullableFloat($row['revpar_change_percent'] ?? null);

        return [
            'current' => [
                'total_room_revenue' => $currentRoomRevenue,
                'total_room' => $totalRoom,
                'revpar' => $currentRevpar,
            ],
            'previous' => [
                'total_room_revenue' => $previousRoomRevenue,
                'total_room' => $totalRoom,
                'revpar' => $previousRevpar,
            ],
            'comparison' => [
                'diff_amount' => $diffAmount,
                'change_percent' => $changePercent,
                'trend' => $this->getTrend($changePercent),
            ],
        ];
    }

    /**
     * Get booking cancellation rate.
     */
    public function getCancellationRate(array $filters = []): array
    {
        // TODO: Calculate cancelled bookings over total bookings, compare with previous period when needed, and prepare warning flags.
        return [];
    }

    /**
     * Get customer count trend.
     */
    public function getCustomerTrend(array $filters = []): array
    {
        $sql = "
            WITH report_params AS (
                SELECT
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date
                FROM dual
            ),
            date_range AS (
                SELECT rp.start_date + LEVEL - 1 AS report_date
                FROM report_params rp
                CONNECT BY LEVEL <= rp.end_date - rp.start_date + 1
            ),
            booking_daily AS (
                SELECT
                    TRUNC(bk.checkin_expected_at) AS report_date,
                    COUNT(DISTINCT bk.customer_id) AS customer_count,
                    COUNT(DISTINCT bk.booking_id) AS booking_count
                FROM booking bk
                JOIN booking_room br
                    ON br.booking_id = bk.booking_id
                JOIN room r
                    ON r.room_id = br.room_id
                JOIN branch b
                    ON b.branch_id = r.branch_id
                CROSS JOIN report_params rp
                WHERE b.is_active = 1
                  AND bk.checkin_expected_at >= rp.start_date
                  AND bk.checkin_expected_at <  rp.end_date + 1
                  AND bk.status IN ('CONFIRMED', 'COMPLETED')
                GROUP BY TRUNC(bk.checkin_expected_at)
            )
            SELECT
                TO_CHAR(dr.report_date, 'YYYY-MM-DD') AS report_date,
                NVL(bd.customer_count, 0) AS customer_count,
                NVL(bd.booking_count, 0) AS booking_count
            FROM date_range dr
            LEFT JOIN booking_daily bd
                ON bd.report_date = dr.report_date
            ORDER BY dr.report_date
        ";

        $rows = DB::select($sql, [
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
        ]);

        return array_map(static function (object $row): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'report_date' => (string) ($data['report_date'] ?? ''),
                'customer_count' => (int) ($data['customer_count'] ?? 0),
                'booking_count' => (int) ($data['booking_count'] ?? 0),
            ];
        }, $rows);
    }

    /**
     * Get total valid chain revenue.
     */
    public function getTotalChainRevenue(array $filters = []): array
    {
        return $this->getTotalRevenue($filters);
    }

    /**
     * Get estimated material COGS.
     */
    public function getEstimatedCogs(array $filters = []): array
    {
        return $this->getTotalInventoryImportCost($filters);
    }

    /**
     * Get total inventory import cost.
     */
    public function getTotalInventoryImportCost(array $filters = []): array
    {
        $periodFilters = $this->getPreviousPeriodFilters($filters);

        $sql = "
            WITH material_cost_per_service AS (
                SELECT
                    spd.service_id,
                    SUM(NVL(spd.amount, 0) * NVL(p.item_price, 0)) AS material_cost_per_time
                FROM service_product_detail spd
                JOIN product p
                    ON p.product_id = spd.product_id
                GROUP BY spd.service_id
            ),
            cur AS (
                SELECT NVL(SUM(NVL(mc.material_cost_per_time, 0)), 0) AS estimated_cogs
                FROM booking_service_pet bsp
                JOIN booking bk
                    ON bk.booking_id = bsp.booking_id
                JOIN branch b
                    ON b.branch_id = bk.branch_id
                LEFT JOIN material_cost_per_service mc
                    ON mc.service_id = bsp.service_id
                WHERE b.is_active = 1
                  AND bsp.status = 'DONE'
                  AND bsp.scheduled_at >= TO_DATE(:p_start_date, 'YYYY-MM-DD')
                  AND bsp.scheduled_at <  TO_DATE(:p_end_date, 'YYYY-MM-DD') + 1
            ),
            prev AS (
                SELECT NVL(SUM(NVL(mc.material_cost_per_time, 0)), 0) AS estimated_cogs
                FROM booking_service_pet bsp
                JOIN booking bk
                    ON bk.booking_id = bsp.booking_id
                JOIN branch b
                    ON b.branch_id = bk.branch_id
                LEFT JOIN material_cost_per_service mc
                    ON mc.service_id = bsp.service_id
                WHERE b.is_active = 1
                  AND bsp.status = 'DONE'
                  AND bsp.scheduled_at >= TO_DATE(:p_prev_start_date, 'YYYY-MM-DD')
                  AND bsp.scheduled_at <  TO_DATE(:p_prev_end_date, 'YYYY-MM-DD') + 1
            )
            SELECT
                cur.estimated_cogs AS current_estimated_cogs,
                prev.estimated_cogs AS previous_estimated_cogs,
                ROUND(
                    (cur.estimated_cogs - prev.estimated_cogs)
                    / NULLIF(prev.estimated_cogs, 0) * 100,
                    2
                ) AS cogs_growth_percent
            FROM cur
            CROSS JOIN prev
        ";

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_start_date' => $periodFilters['start_date'],
            'p_end_date' => $periodFilters['end_date'],
            'p_prev_start_date' => $periodFilters['prev_start_date'],
            'p_prev_end_date' => $periodFilters['prev_end_date'],
        ]), CASE_LOWER);

        $currentEstimatedCogs = $this->nullableFloat($row['current_estimated_cogs'] ?? null) ?? 0.0;
        $previousEstimatedCogs = $this->nullableFloat($row['previous_estimated_cogs'] ?? null) ?? 0.0;
        $diffAmount = round($currentEstimatedCogs - $previousEstimatedCogs, 2);
        $growthPercent = $this->nullableFloat($row['cogs_growth_percent'] ?? null);

        return [
            'current' => [
                'estimated_cogs' => $currentEstimatedCogs,
            ],
            'previous' => [
                'estimated_cogs' => $previousEstimatedCogs,
            ],
            'comparison' => [
                'diff_amount' => $diffAmount,
                'growth_percent' => $growthPercent,
                'trend' => $this->getTrend($growthPercent),
            ],
        ];
    }

    /**
     * Get Grooming and Hotel revenue mix.
     */
    public function getRevenueMix(array $filters = []): array
    {
        $sql = "
            SELECT
                NVL(SUM(CASE
                    WHEN od.booking_room_id IS NOT NULL
                     AND od.booking_service_pet_id IS NULL
                    THEN od.line_total
                    ELSE 0
                END), 0) AS hotel_revenue,
                NVL(SUM(CASE
                    WHEN od.booking_service_pet_id IS NOT NULL
                    THEN od.line_total
                    ELSE 0
                END), 0) AS service_revenue
            FROM order_details od
            JOIN orders o
                ON o.order_id = od.order_id
            JOIN branch b
                ON b.branch_id = o.branch_id
            WHERE b.is_active = 1
              AND o.status IN ('PAID', 'COMPLETED')
              AND o.paid_at >= TO_DATE(:p_start_date, 'YYYY-MM-DD')
              AND o.paid_at <  TO_DATE(:p_end_date, 'YYYY-MM-DD') + 1
        ";

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
        ]), CASE_LOWER);

        $hotelRevenue = $this->nullableFloat($row['hotel_revenue'] ?? null) ?? 0.0;
        $serviceRevenue = $this->nullableFloat($row['service_revenue'] ?? null) ?? 0.0;
        $totalRevenue = $hotelRevenue + $serviceRevenue;

        return [
            [
                'revenue_group' => 'Hotel',
                'revenue_amount' => $hotelRevenue,
                'revenue_percent' => $totalRevenue > 0 ? round($hotelRevenue / $totalRevenue * 100, 2) : 0.0,
            ],
            [
                'revenue_group' => 'Grooming & Spa',
                'revenue_amount' => $serviceRevenue,
                'revenue_percent' => $totalRevenue > 0 ? round($serviceRevenue / $totalRevenue * 100, 2) : 0.0,
            ],
        ];
    }

    /**
     * Get revenue trend.
     */
    public function getRevenueTrend(array $filters = []): array
    {
        return $this->getRevenueAndCogsTrend($filters);
    }

    /**
     * Get revenue and estimated material COGS trend.
     */
    public function getRevenueAndCogsTrend(array $filters = []): array
    {
        $sql = "
            WITH report_params AS (
                SELECT
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date
                FROM dual
            ),
            date_range AS (
                SELECT rp.start_date + LEVEL - 1 AS report_date
                FROM report_params rp
                CONNECT BY LEVEL <= rp.end_date - rp.start_date + 1
            ),
            material_cost_per_service AS (
                SELECT
                    spd.service_id,
                    SUM(NVL(spd.amount, 0) * NVL(p.item_price, 0)) AS material_cost_per_time
                FROM service_product_detail spd
                JOIN product p
                    ON p.product_id = spd.product_id
                GROUP BY spd.service_id
            ),
            revenue_daily AS (
                SELECT
                    TRUNC(o.paid_at) AS report_date,
                    SUM(NVL(o.grand_total, 0)) AS revenue
                FROM orders o
                JOIN branch b
                    ON b.branch_id = o.branch_id
                CROSS JOIN report_params rp
                WHERE b.is_active = 1
                  AND o.status IN ('PAID', 'COMPLETED')
                  AND o.paid_at >= rp.start_date
                  AND o.paid_at <  rp.end_date + 1
                GROUP BY TRUNC(o.paid_at)
            ),
            cogs_daily AS (
                SELECT
                    TRUNC(bsp.scheduled_at) AS report_date,
                    SUM(NVL(mc.material_cost_per_time, 0)) AS estimated_cogs
                FROM booking_service_pet bsp
                JOIN booking bk
                    ON bk.booking_id = bsp.booking_id
                JOIN branch b
                    ON b.branch_id = bk.branch_id
                LEFT JOIN material_cost_per_service mc
                    ON mc.service_id = bsp.service_id
                CROSS JOIN report_params rp
                WHERE b.is_active = 1
                  AND bsp.status = 'DONE'
                  AND bsp.scheduled_at >= rp.start_date
                  AND bsp.scheduled_at <  rp.end_date + 1
                GROUP BY TRUNC(bsp.scheduled_at)
            )
            SELECT
                TO_CHAR(dr.report_date, 'YYYY-MM-DD') AS report_date,
                NVL(rd.revenue, 0) AS revenue,
                NVL(cd.estimated_cogs, 0) AS estimated_cogs,
                NVL(rd.revenue, 0) - NVL(cd.estimated_cogs, 0) AS gross_profit
            FROM date_range dr
            LEFT JOIN revenue_daily rd
                ON rd.report_date = dr.report_date
            LEFT JOIN cogs_daily cd
                ON cd.report_date = dr.report_date
            ORDER BY dr.report_date
        ";

        $rows = DB::select($sql, [
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
        ]);

        return array_map(function (object $row): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'report_date' => (string) ($data['report_date'] ?? ''),
                'revenue' => $this->nullableFloat($data['revenue'] ?? null) ?? 0.0,
                'estimated_cogs' => $this->nullableFloat($data['estimated_cogs'] ?? null) ?? 0.0,
                'gross_profit' => $this->nullableFloat($data['gross_profit'] ?? null) ?? 0.0,
            ];
        }, $rows);
    }

    /**
     * Get revenue grouped by branch.
     */
    public function getBranchRevenue(array $filters = []): array
    {
        $sql = "
            SELECT
                b.branch_id,
                b.branch_name,
                NVL(SUM(o.grand_total), 0) AS total_revenue
            FROM branch b
            LEFT JOIN orders o
                ON o.branch_id = b.branch_id
               AND o.status IN ('PAID', 'COMPLETED')
               AND o.paid_at >= TO_DATE(:p_start_date, 'YYYY-MM-DD')
               AND o.paid_at <  TO_DATE(:p_end_date, 'YYYY-MM-DD') + 1
            WHERE b.is_active = 1
            GROUP BY b.branch_id, b.branch_name
            ORDER BY total_revenue DESC, b.branch_id
        ";

        $rows = DB::select($sql, [
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
        ]);

        return array_map(function (object $row): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);
            $totalRevenue = $this->nullableFloat($data['total_revenue'] ?? null) ?? 0.0;

            return [
                'branch_id' => (int) ($data['branch_id'] ?? 0),
                'branch_name' => (string) ($data['branch_name'] ?? ''),
                'total_revenue' => $totalRevenue,
                'revenue_million_vnd' => round($totalRevenue / 1000000, 2),
            ];
        }, $rows);
    }

    /**
     * Get branch ranking by revenue.
     */
    public function getBranchRanking(array $filters = []): array
    {
        $branches = $this->getBranchRevenue($filters);

        usort($branches, static function (array $a, array $b): int {
            return ($b['total_revenue'] ?? 0) <=> ($a['total_revenue'] ?? 0);
        });

        $topBranches = array_slice($branches, 0, 3);

        return array_map(function (array $branch, int $index): array {
            $totalRevenue = $this->nullableFloat($branch['total_revenue'] ?? null) ?? 0.0;

            return [
                'rank_no' => $index + 1,
                'branch_id' => $branch['branch_id'] ?? null,
                'branch_name' => (string) ($branch['branch_name'] ?? ''),
                'total_revenue' => $totalRevenue,
                'revenue_million_vnd' => round($totalRevenue / 1000000, 2),
            ];
        }, $topBranches, array_keys($topBranches));
    }

    /**
     * Get top used services.
     */
    public function getTopUsedServices(array $filters = []): array
    {
        $sql = "
            WITH report_params AS (
                SELECT
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date
                FROM dual
            ),
            usage_by_service AS (
                SELECT
                    s.service_id,
                    s.service_name,
                    COUNT(bsp.booking_service_pet_id) AS usage_count
                FROM booking_service_pet bsp
                JOIN services s
                    ON s.service_id = bsp.service_id
                JOIN booking bk
                    ON bk.booking_id = bsp.booking_id
                JOIN branch b
                    ON b.branch_id = bk.branch_id
                CROSS JOIN report_params rp
                WHERE b.is_active = 1
                  AND s.is_active = 1
                  AND bsp.status = 'DONE'
                  AND bsp.scheduled_at >= rp.start_date
                  AND bsp.scheduled_at <  rp.end_date + 1
                GROUP BY s.service_id, s.service_name
            ),
            revenue_by_service AS (
                SELECT
                    s.service_id,
                    SUM(NVL(od.line_total, 0)) AS service_revenue
                FROM order_details od
                JOIN orders o
                    ON o.order_id = od.order_id
                JOIN booking_service_pet bsp
                    ON bsp.booking_service_pet_id = od.booking_service_pet_id
                JOIN services s
                    ON s.service_id = bsp.service_id
                JOIN branch b
                    ON b.branch_id = o.branch_id
                CROSS JOIN report_params rp
                WHERE b.is_active = 1
                  AND s.is_active = 1
                  AND od.booking_service_pet_id IS NOT NULL
                  AND o.status IN ('PAID', 'COMPLETED')
                  AND o.paid_at >= rp.start_date
                  AND o.paid_at <  rp.end_date + 1
                GROUP BY s.service_id
            ),
            ranked_services AS (
                SELECT
                    ROW_NUMBER() OVER (
                        ORDER BY u.usage_count DESC, NVL(r.service_revenue, 0) DESC, u.service_id
                    ) AS rank_no,
                    u.service_id,
                    u.service_name,
                    u.usage_count,
                    NVL(r.service_revenue, 0) AS service_revenue
                FROM usage_by_service u
                LEFT JOIN revenue_by_service r
                    ON r.service_id = u.service_id
            )
            SELECT
                rank_no,
                service_id,
                service_name,
                usage_count,
                service_revenue
            FROM ranked_services
            WHERE rank_no <= 5
            ORDER BY rank_no
        ";

        $rows = DB::select($sql, [
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
        ]);

        return array_map(static function (object $row): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'rank_no' => (int) ($data['rank_no'] ?? 0),
                'service_id' => (int) ($data['service_id'] ?? 0),
                'service_name' => (string) ($data['service_name'] ?? ''),
                'usage_count' => (int) ($data['usage_count'] ?? 0),
                'service_revenue' => (float) ($data['service_revenue'] ?? 0),
            ];
        }, $rows);
    }

    /**
     * Get unusual data or risk alerts from the last 30 days.
     */
    public function getRiskAlertsLast30Days(array $filters = []): array
    {
        $endDate = CarbonImmutable::today(config('app.timezone'));
        $startDate = $endDate->subDays(29);
        $previousEndDate = $startDate->subDay();
        $previousStartDate = $previousEndDate->subDays(29);

        return $this->getRiskAlerts(array_merge($filters, [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'prev_start_date' => $previousStartDate->toDateString(),
            'prev_end_date' => $previousEndDate->toDateString(),
        ]));
    }

    /**
     * Get CEO risk alerts for the selected period.
     */
    public function getRiskAlerts(array $filters = []): array
    {
        $periodFilters = $this->riskAlertPeriodFilters($filters);
        $cancelRateThreshold = (float) ($filters['cancel_rate_threshold'] ?? 15);
        $highCancelAmount = (float) ($filters['high_cancel_amount'] ?? 1000000);
        $actionCountThreshold = (int) ($filters['action_count_threshold'] ?? 5);
        $revenueDropThreshold = (float) ($filters['revenue_drop_threshold'] ?? -20);

        return $this->sortRiskAlerts(array_merge(
            $this->getHighCancelRateAlerts($periodFilters, $cancelRateThreshold),
            $this->getHighValueCancelledBookingAlerts($periodFilters, $highCancelAmount),
            $this->getSensitiveDataActionAlerts($periodFilters, $actionCountThreshold),
            $this->getBranchRevenueDropAlerts($periodFilters, $revenueDropThreshold),
        ));
    }

    /**
     * Get a compact aggregate payload for backend verification.
     */
    public function getDebugSummary(array $filters = []): array
    {
        return $this->getDashboard($filters);
    }

    /**
     * Resolve selected reporting period into a date range.
     */
    public function resolvePeriodRange(
        ?string $period = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $date = null
    ): array {
        if ($startDate !== null && $endDate !== null) {
            return [
                'start_date' => CarbonImmutable::parse($startDate)->toDateString(),
                'end_date' => CarbonImmutable::parse($endDate)->toDateString(),
            ];
        }

        if ($date !== null) {
            $resolvedDate = CarbonImmutable::parse($date)->toDateString();

            return [
                'start_date' => $resolvedDate,
                'end_date' => $resolvedDate,
            ];
        }

        if ($period !== null) {
            $period = trim($period);

            if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $period) === 1) {
                $resolvedDate = CarbonImmutable::parse($period)->toDateString();

                return [
                    'start_date' => $resolvedDate,
                    'end_date' => $resolvedDate,
                ];
            }

            if (preg_match('/^\d{4}-\d{1,2}$/', $period) === 1) {
                $resolvedDate = CarbonImmutable::parse($period . '-01');

                return [
                    'start_date' => $resolvedDate->startOfMonth()->toDateString(),
                    'end_date' => $resolvedDate->endOfMonth()->toDateString(),
                ];
            }

            if (preg_match('/^\d{4}$/', $period) === 1) {
                $resolvedDate = CarbonImmutable::parse($period . '-01-01');

                return [
                    'start_date' => $resolvedDate->startOfYear()->toDateString(),
                    'end_date' => $resolvedDate->endOfYear()->toDateString(),
                ];
            }
        }

        $today = CarbonImmutable::today();

        return [
            'start_date' => $today->startOfMonth()->toDateString(),
            'end_date' => $today->endOfMonth()->toDateString(),
        ];
    }

    /**
     * Resolve previous comparable reporting period.
     */
    public function resolvePreviousPeriodRange(
        ?string $period = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $date = null
    ): array {
        $range = $this->resolvePeriodRange($period, $startDate, $endDate, $date);
        $previousFilters = $this->getPreviousPeriodFilters($range);

        return [
            'start_date' => $previousFilters['prev_start_date'],
            'end_date' => $previousFilters['prev_end_date'],
        ];
    }

    /**
     * Format percentage values for API output.
     */
    public function formatPercentage(int|float|null $value): int|float|null
    {
        return $value === null ? null : round((float) $value, 2);
    }

    /**
     * Format currency values for API output.
     */
    public function formatCurrency(int|float|null $value): int|float|null
    {
        return $value === null ? null : round((float) $value, 2);
    }

    private function getHighCancelRateAlerts(array $filters, float $cancelRateThreshold): array
    {
        $sql = "
            WITH report_params AS (
                SELECT
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date,
                    :p_cancel_rate_threshold AS cancel_rate_threshold
                FROM dual
            )
            SELECT
                b.branch_id,
                b.branch_name,
                COUNT(bk.booking_id) AS total_booking,
                SUM(CASE WHEN bk.status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled_booking,
                ROUND(
                    SUM(CASE WHEN bk.status = 'CANCELLED' THEN 1 ELSE 0 END)
                    / NULLIF(COUNT(bk.booking_id), 0) * 100,
                    2
                ) AS cancel_rate_percent,
                TO_CHAR(MAX(bk.created_at), 'YYYY-MM-DD HH24:MI:SS') AS created_at
            FROM booking bk
            JOIN branch b
                ON b.branch_id = bk.branch_id
            CROSS JOIN report_params rp
            WHERE b.is_active = 1
              AND bk.created_at >= rp.start_date
              AND bk.created_at <  rp.end_date + 1
            GROUP BY b.branch_id, b.branch_name, rp.cancel_rate_threshold
            HAVING ROUND(
                SUM(CASE WHEN bk.status = 'CANCELLED' THEN 1 ELSE 0 END)
                / NULLIF(COUNT(bk.booking_id), 0) * 100,
                2
            ) >= rp.cancel_rate_threshold
        ";

        $rows = DB::select($sql, [
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
            'p_cancel_rate_threshold' => $cancelRateThreshold,
        ]);

        return array_map(function (object $row) use ($cancelRateThreshold): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);
            $branchName = (string) ($data['branch_name'] ?? '');
            $cancelRate = $this->nullableFloat($data['cancel_rate_percent'] ?? null) ?? 0.0;

            return [
                'alert_type' => 'CANCEL_RATE_HIGH',
                'alert_level' => $cancelRate >= 20 ? 'HIGH' : 'MEDIUM',
                'title' => 'Tỷ lệ hủy booking cao',
                'warning_text' => sprintf(
                    'Tỷ lệ hủy booking tại %s đang ở mức %s%%, cần kiểm tra chất lượng CSKH và vận hành.',
                    $branchName,
                    $this->formatRiskNumber($cancelRate)
                ),
                'branch_id' => (int) ($data['branch_id'] ?? 0),
                'branch_name' => $branchName,
                'main_value' => $cancelRate,
                'compare_value' => $cancelRateThreshold,
                'created_at' => (string) ($data['created_at'] ?? ''),
                'total_booking' => (int) ($data['total_booking'] ?? 0),
                'cancelled_booking' => (int) ($data['cancelled_booking'] ?? 0),
            ];
        }, $rows);
    }

    private function getHighValueCancelledBookingAlerts(array $filters, float $highCancelAmount): array
    {
        $sql = "
            WITH report_params AS (
                SELECT
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date,
                    :p_high_cancel_amount AS high_cancel_amount
                FROM dual
            )
            SELECT
                bk.booking_id,
                bk.customer_id,
                c.full_name AS customer_name,
                c.phone AS customer_phone,
                b.branch_id,
                b.branch_name,
                NVL(bk.total_amount, 0) AS total_amount,
                TO_CHAR(bk.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at
            FROM booking bk
            JOIN branch b
                ON b.branch_id = bk.branch_id
            LEFT JOIN customer c
                ON c.customer_id = bk.customer_id
            CROSS JOIN report_params rp
            WHERE b.is_active = 1
              AND bk.status = 'CANCELLED'
              AND NVL(bk.total_amount, 0) >= rp.high_cancel_amount
              AND bk.created_at >= rp.start_date
              AND bk.created_at <  rp.end_date + 1
            ORDER BY total_amount DESC, bk.created_at DESC, bk.booking_id
        ";

        $rows = DB::select($sql, [
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
            'p_high_cancel_amount' => $highCancelAmount,
        ]);

        return array_map(function (object $row) use ($highCancelAmount): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);
            $branchName = (string) ($data['branch_name'] ?? '');
            $totalAmount = $this->nullableFloat($data['total_amount'] ?? null) ?? 0.0;
            $bookingId = (int) ($data['booking_id'] ?? 0);

            return [
                'alert_type' => 'HIGH_VALUE_CANCELLED_BOOKING',
                'alert_level' => $totalAmount >= 3000000 ? 'HIGH' : 'MEDIUM',
                'title' => 'Booking hủy giá trị cao',
                'warning_text' => sprintf(
                    'Booking #%d tại %s bị hủy với giá trị %s VND.',
                    $bookingId,
                    $branchName,
                    number_format($totalAmount, 0, '.', ',')
                ),
                'branch_id' => (int) ($data['branch_id'] ?? 0),
                'branch_name' => $branchName,
                'main_value' => $totalAmount,
                'compare_value' => $highCancelAmount,
                'created_at' => (string) ($data['created_at'] ?? ''),
                'booking_id' => $bookingId,
                'customer_id' => (int) ($data['customer_id'] ?? 0),
                'customer_name' => (string) ($data['customer_name'] ?? ''),
                'customer_phone' => (string) ($data['customer_phone'] ?? ''),
            ];
        }, $rows);
    }

    private function getSensitiveDataActionAlerts(array $filters, int $actionCountThreshold): array
    {
        $sql = "
            WITH report_params AS (
                SELECT
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date,
                    :p_action_count_threshold AS action_count_threshold
                FROM dual
            )
            SELECT
                al.changed_by_user_id,
                u.name AS changed_by_name,
                u.email AS changed_by_email,
                LOWER(al.table_name) AS table_name,
                al.action_type,
                COUNT(*) AS action_count,
                TO_CHAR(MIN(al.changed_at), 'YYYY-MM-DD HH24:MI:SS') AS first_action_at,
                TO_CHAR(MAX(al.changed_at), 'YYYY-MM-DD HH24:MI:SS') AS latest_action_at
            FROM audit_log al
            LEFT JOIN users u
                ON u.id = al.changed_by_user_id
            CROSS JOIN report_params rp
            WHERE al.action_type IN ('UPDATE', 'DELETE')
              AND LOWER(al.table_name) IN ('orders', 'payments', 'booking', 'branch_inventory')
              AND al.changed_at >= rp.start_date
              AND al.changed_at <  rp.end_date + 1
            GROUP BY
                al.changed_by_user_id,
                u.name,
                u.email,
                LOWER(al.table_name),
                al.action_type,
                rp.action_count_threshold
            HAVING COUNT(*) >= rp.action_count_threshold
        ";

        $rows = DB::select($sql, [
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
            'p_action_count_threshold' => $actionCountThreshold,
        ]);

        return array_map(function (object $row) use ($actionCountThreshold): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);
            $actionCount = (int) ($data['action_count'] ?? 0);
            $changedByUserId = isset($data['changed_by_user_id'])
                ? (int) $data['changed_by_user_id']
                : null;
            $changedByName = (string) ($data['changed_by_name'] ?? '');
            $changedByEmail = (string) ($data['changed_by_email'] ?? '');
            $actor = $changedByName !== ''
                ? $changedByName
                : ($changedByEmail !== '' ? $changedByEmail : (string) ($changedByUserId ?? 'unknown'));
            $actionType = (string) ($data['action_type'] ?? '');
            $tableName = (string) ($data['table_name'] ?? '');

            return [
                'alert_type' => 'SENSITIVE_DATA_ACTION',
                'alert_level' => $actionCount >= 10 ? 'HIGH' : 'MEDIUM',
                'title' => 'Thao tác bất thường trên dữ liệu nhạy cảm',
                'warning_text' => sprintf(
                    'User %s có %d thao tác %s trên bảng %s trong kỳ báo cáo.',
                    $actor,
                    $actionCount,
                    $actionType,
                    $tableName
                ),
                'branch_id' => null,
                'branch_name' => null,
                'main_value' => $actionCount,
                'compare_value' => $actionCountThreshold,
                'created_at' => (string) ($data['latest_action_at'] ?? ''),
                'table_name' => $tableName,
                'action_type' => $actionType,
                'changed_by_user_id' => $changedByUserId,
                'changed_by_name' => $changedByName,
                'changed_by_email' => $changedByEmail,
                'first_action_at' => (string) ($data['first_action_at'] ?? ''),
                'latest_action_at' => (string) ($data['latest_action_at'] ?? ''),
            ];
        }, $rows);
    }

    private function getBranchRevenueDropAlerts(array $filters, float $revenueDropThreshold): array
    {
        $sql = "
            WITH report_params AS (
                SELECT
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date,
                    TRUNC(TO_DATE(:p_prev_start_date, 'YYYY-MM-DD')) AS prev_start_date,
                    TRUNC(TO_DATE(:p_prev_end_date, 'YYYY-MM-DD')) AS prev_end_date,
                    :p_revenue_drop_threshold AS revenue_drop_threshold
                FROM dual
            ),
            branch_revenue AS (
                SELECT
                    b.branch_id,
                    b.branch_name,
                    rp.end_date,
                    rp.revenue_drop_threshold,
                    NVL(SUM(CASE
                        WHEN o.paid_at >= rp.start_date
                         AND o.paid_at <  rp.end_date + 1
                        THEN NVL(o.grand_total, 0)
                        ELSE 0
                    END), 0) AS current_revenue,
                    NVL(SUM(CASE
                        WHEN o.paid_at >= rp.prev_start_date
                         AND o.paid_at <  rp.prev_end_date + 1
                        THEN NVL(o.grand_total, 0)
                        ELSE 0
                    END), 0) AS previous_revenue
                FROM branch b
                CROSS JOIN report_params rp
                LEFT JOIN orders o
                    ON o.branch_id = b.branch_id
                   AND o.status IN ('PAID', 'COMPLETED')
                WHERE b.is_active = 1
                GROUP BY
                    b.branch_id,
                    b.branch_name,
                    rp.end_date,
                    rp.revenue_drop_threshold
            ),
            compared AS (
                SELECT
                    branch_id,
                    branch_name,
                    end_date,
                    revenue_drop_threshold,
                    current_revenue,
                    previous_revenue,
                    ROUND(
                        (current_revenue - previous_revenue)
                        / NULLIF(previous_revenue, 0) * 100,
                        2
                    ) AS growth_percent
                FROM branch_revenue
            )
            SELECT
                branch_id,
                branch_name,
                current_revenue,
                previous_revenue,
                growth_percent,
                TO_CHAR(end_date, 'YYYY-MM-DD HH24:MI:SS') AS created_at
            FROM compared
            WHERE previous_revenue > 0
              AND growth_percent <= revenue_drop_threshold
            ORDER BY growth_percent, branch_id
        ";

        $rows = DB::select($sql, [
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
            'p_prev_start_date' => $filters['prev_start_date'],
            'p_prev_end_date' => $filters['prev_end_date'],
            'p_revenue_drop_threshold' => $revenueDropThreshold,
        ]);

        return array_map(function (object $row) use ($revenueDropThreshold): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);
            $branchName = (string) ($data['branch_name'] ?? '');
            $growthPercent = $this->nullableFloat($data['growth_percent'] ?? null) ?? 0.0;

            return [
                'alert_type' => 'BRANCH_REVENUE_DROP',
                'alert_level' => $growthPercent <= -30 ? 'HIGH' : 'MEDIUM',
                'title' => 'Doanh thu chi nhánh giảm mạnh',
                'warning_text' => sprintf(
                    'Doanh thu chi nhánh %s giảm %s%% so với kỳ trước.',
                    $branchName,
                    $this->formatRiskNumber(abs($growthPercent))
                ),
                'branch_id' => (int) ($data['branch_id'] ?? 0),
                'branch_name' => $branchName,
                'main_value' => $growthPercent,
                'compare_value' => $revenueDropThreshold,
                'created_at' => (string) ($data['created_at'] ?? ''),
                'current_revenue' => $this->nullableFloat($data['current_revenue'] ?? null) ?? 0.0,
                'previous_revenue' => $this->nullableFloat($data['previous_revenue'] ?? null) ?? 0.0,
            ];
        }, $rows);
    }

    private function riskAlertPeriodFilters(array $filters): array
    {
        if (! isset($filters['start_date'], $filters['end_date'])) {
            $filters = array_merge($filters, $this->resolvePeriodRange());
        }

        if (! isset($filters['prev_start_date'], $filters['prev_end_date'])) {
            $filters = array_merge($filters, $this->getPreviousPeriodFilters($filters));
        }

        return $filters;
    }

    private function sortRiskAlerts(array $alerts): array
    {
        $levelPriority = [
            'HIGH' => 0,
            'MEDIUM' => 1,
            'LOW' => 2,
        ];

        usort($alerts, static function (array $left, array $right) use ($levelPriority): int {
            $levelComparison = ($levelPriority[$left['alert_level']] ?? 99)
                <=> ($levelPriority[$right['alert_level']] ?? 99);

            if ($levelComparison !== 0) {
                return $levelComparison;
            }

            return strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? ''));
        });

        return $alerts;
    }

    private function formatRiskNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function getPreviousPeriodFilters(array $filters): array
    {
        $startDate = CarbonImmutable::parse($filters['start_date']);
        $endDate = CarbonImmutable::parse($filters['end_date']);
        $daysInPeriod = abs((int) $startDate->diffInDays($endDate)) + 1;
        $previousEndDate = $startDate->subDay();
        $previousStartDate = $previousEndDate->subDays($daysInPeriod - 1);

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'prev_start_date' => $previousStartDate->toDateString(),
            'prev_end_date' => $previousEndDate->toDateString(),
        ];
    }

    private function getTrend(?float $changePercent): string
    {
        if ($changePercent === null) {
            return 'no_previous_data';
        }

        return match (true) {
            $changePercent > 0 => 'increase',
            $changePercent < 0 => 'decrease',
            default => 'neutral',
        };
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
