<?php

namespace App\Repositories\Eloquent\Ceo;

use App\Models\Service;
use App\Repositories\Contracts\Ceo\CeoFinanceRepositoryInterface;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CeoFinanceRepository implements CeoFinanceRepositoryInterface
{
    private const PAYMENT_SUCCESS_STATUSES = [
        'SUCCESS',
    ];

    private const ORDER_REVENUE_STATUSES = [
        'COMPLETED',
        'PAID',
    ];

    private const ORDER_EXCLUDED_STATUSES = [
        'CANCELLED',
        'REFUNDED',
    ];

    private const RECEIPT_VALID_STATUSES = [
        'APPROVED',
    ];

    private const SERVICE_VALID_STATUSES = [
        'DONE',
    ];

    public function getFinanceData(array $filters = []): array
    {
        $range = $this->resolvePeriodRange(
            $filters['period_type'] ?? $filters['period'] ?? 'month',
            $filters['date'] ?? null,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        $totalRevenueCard = $this->getTotalChainRevenueCard($filters);
        $estimatedTotalCostCard = $this->getEstimatedTotalCostCard($filters);
        $estimatedProfitCard = $this->getEstimatedProfitCard($filters);
        $estimatedMarginCard = $this->getEstimatedMarginCard($filters);
        $financeTrendChart = $this->getFinanceTrendChart($filters);
        $totalRevenue = (float) $totalRevenueCard['current_revenue'];
        $estimatedTotalCost = (float) $estimatedTotalCostCard['current_value'];
        $estimatedProfit = (float) $estimatedProfitCard['current_value'];

        return [
            'period' => $range['period'],
            'date_range' => [
                'start_date' => $range['start']->toDateString(),
                'end_date' => $range['end']->toDateString(),
            ],
            'kpi_cards' => [
                'total_revenue' => $this->cleanNumber($totalRevenue),
                'previous_total_revenue' => $this->cleanNumber((float) $totalRevenueCard['previous_revenue']),
                'revenue_growth_percent' => $totalRevenueCard['revenue_growth_percent'],
                'total_inventory_cost' => $this->cleanNumber($estimatedTotalCost),
                'estimated_total_cost' => $this->cleanNumber($estimatedTotalCost),
                'previous_estimated_total_cost' => $this->cleanNumber((float) $estimatedTotalCostCard['previous_value']),
                'cost_growth_percent' => $estimatedTotalCostCard['growth_percent'],
                'cost_trend' => $estimatedTotalCostCard['trend'],
                'cost_breakdown' => $estimatedTotalCostCard['components'],
                'net_profit' => $this->cleanNumber($estimatedProfit),
                'estimated_profit' => $this->cleanNumber($estimatedProfit),
                'previous_estimated_profit' => $this->cleanNumber((float) $estimatedProfitCard['previous_value']),
                'profit_growth_percent' => $estimatedProfitCard['growth_percent'],
                'profit_trend' => $estimatedProfitCard['trend'],
                'profit_breakdown' => $estimatedProfitCard['components'],
                'estimated_margin_percent' => $estimatedMarginCard['current_value'],
                'previous_estimated_margin_percent' => $estimatedMarginCard['previous_value'],
                'margin_growth_percent' => $estimatedMarginCard['growth_percent'],
                'margin_trend' => $estimatedMarginCard['trend'],
                'margin_breakdown' => $estimatedMarginCard['components'],
            ],
            'revenue_cost_chart' => $financeTrendChart,
            'finance_trend_chart' => $financeTrendChart,
            'revenue_mix_chart' => $this->revenueMixChart($range),
            'gross_margin_analysis' => $this->grossMarginAnalysis(),
            'loss_risk_alerts' => $this->lossRiskAlerts(),
        ];
    }

    public function getTotalChainRevenueCard(array $filters = []): array
    {
        $range = $this->resolvePeriodRange(
            $filters['period_type'] ?? $filters['period'] ?? 'month',
            $filters['date'] ?? null,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );
        $previousRange = $this->previousRange($range, $filters);

        $sql = "
            WITH cur AS (
                SELECT
                    NVL(SUM(o.grand_total), 0) AS current_revenue
                FROM orders o
                WHERE o.status IN ('PAID', 'COMPLETED')
                  AND o.paid_at >= TO_DATE(:p_start_date, 'YYYY-MM-DD')
                  AND o.paid_at <  TO_DATE(:p_end_date, 'YYYY-MM-DD') + 1
            ),
            prev AS (
                SELECT
                    NVL(SUM(o.grand_total), 0) AS previous_revenue
                FROM orders o
                WHERE o.status IN ('PAID', 'COMPLETED')
                  AND o.paid_at >= TO_DATE(:p_prev_start_date, 'YYYY-MM-DD')
                  AND o.paid_at <  TO_DATE(:p_prev_end_date, 'YYYY-MM-DD') + 1
            )
            SELECT
                cur.current_revenue,
                prev.previous_revenue,
                ROUND(
                    (cur.current_revenue - prev.previous_revenue)
                    / NULLIF(prev.previous_revenue, 0) * 100,
                    2
                ) AS revenue_growth_percent
            FROM cur
            CROSS JOIN prev
        ";

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_start_date' => $range['start']->toDateString(),
            'p_end_date' => $range['end']->toDateString(),
            'p_prev_start_date' => $previousRange['start']->toDateString(),
            'p_prev_end_date' => $previousRange['end']->toDateString(),
        ]), CASE_LOWER);

        $currentRevenue = (float) ($row['current_revenue'] ?? 0);
        $previousRevenue = (float) ($row['previous_revenue'] ?? 0);

        return [
            'current_revenue' => $this->cleanNumber($currentRevenue),
            'previous_revenue' => $this->cleanNumber($previousRevenue),
            'revenue_growth_percent' => $this->growthPercent($currentRevenue, $previousRevenue),
        ];
    }

    public function getEstimatedTotalCostCard(array $filters = []): array
    {
        $metrics = $this->estimatedProfitMetrics($filters);
        $currentTotalCost = $metrics['current_estimated_total_cost'];
        $previousTotalCost = $metrics['previous_estimated_total_cost'];

        return [
            'title' => 'Tổng chi phí ước tính',
            'current_value' => $this->cleanNumber($currentTotalCost),
            'previous_value' => $this->cleanNumber($previousTotalCost),
            'growth_percent' => $this->growthPercent($currentTotalCost, $previousTotalCost),
            'trend' => $this->trend($currentTotalCost, $previousTotalCost),
            'unit' => 'VND',
            'components' => [
                'current_salary_cost' => $this->cleanNumber($metrics['current_salary_cost']),
                'previous_salary_cost' => $this->cleanNumber($metrics['previous_salary_cost']),
                'current_material_cost' => $this->cleanNumber($metrics['current_material_cost']),
                'previous_material_cost' => $this->cleanNumber($metrics['previous_material_cost']),
            ],
        ];
    }

    public function getEstimatedProfitCard(array $filters = []): array
    {
        $metrics = $this->estimatedProfitMetrics($filters);
        $currentProfit = $metrics['current_estimated_profit'];
        $previousProfit = $metrics['previous_estimated_profit'];

        return [
            'title' => 'Lợi nhuận ước tính toàn chuỗi',
            'current_value' => $this->cleanNumber($currentProfit),
            'previous_value' => $this->cleanNumber($previousProfit),
            'growth_percent' => $this->growthPercent($currentProfit, $previousProfit),
            'trend' => $this->trend($currentProfit, $previousProfit),
            'unit' => 'VND',
            'components' => [
                'current' => [
                    'total_revenue' => $this->cleanNumber($metrics['current_total_revenue']),
                    'salary_cost' => $this->cleanNumber($metrics['current_salary_cost']),
                    'material_cost' => $this->cleanNumber($metrics['current_material_cost']),
                    'estimated_total_cost' => $this->cleanNumber($metrics['current_estimated_total_cost']),
                ],
                'previous' => [
                    'total_revenue' => $this->cleanNumber($metrics['previous_total_revenue']),
                    'salary_cost' => $this->cleanNumber($metrics['previous_salary_cost']),
                    'material_cost' => $this->cleanNumber($metrics['previous_material_cost']),
                    'estimated_total_cost' => $this->cleanNumber($metrics['previous_estimated_total_cost']),
                ],
            ],
        ];
    }

    public function getEstimatedMarginCard(array $filters = []): array
    {
        $metrics = $this->estimatedProfitMetrics($filters);
        $currentMargin = $this->marginPercent(
            $metrics['current_estimated_profit'],
            $metrics['current_total_revenue']
        );
        $previousMargin = $this->marginPercent(
            $metrics['previous_estimated_profit'],
            $metrics['previous_total_revenue']
        );

        return [
            'title' => 'Biên lợi nhuận ước tính',
            'current_value' => $currentMargin,
            'previous_value' => $previousMargin,
            'growth_percent' => $this->nullableGrowthPercent($currentMargin, $previousMargin),
            'trend' => $this->nullableTrend($currentMargin, $previousMargin),
            'unit' => '%',
            'components' => [
                'current' => [
                    'total_revenue' => $this->cleanNumber($metrics['current_total_revenue']),
                    'estimated_total_cost' => $this->cleanNumber($metrics['current_estimated_total_cost']),
                    'estimated_profit' => $this->cleanNumber($metrics['current_estimated_profit']),
                ],
                'previous' => [
                    'total_revenue' => $this->cleanNumber($metrics['previous_total_revenue']),
                    'estimated_total_cost' => $this->cleanNumber($metrics['previous_estimated_total_cost']),
                    'estimated_profit' => $this->cleanNumber($metrics['previous_estimated_profit']),
                ],
            ],
        ];
    }

    public function getFinanceTrendChart(array $filters = []): array
    {
        $range = $this->resolvePeriodRange(
            $filters['period_type'] ?? $filters['period'] ?? 'month',
            $filters['date'] ?? null,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        $sql = "
            WITH params AS (
                SELECT
                    TO_DATE(:p_start_date, 'YYYY-MM-DD') AS start_date,
                    TO_DATE(:p_end_date, 'YYYY-MM-DD') AS end_date
                FROM dual
            ),
            report_days AS (
                SELECT
                    pa.start_date + LEVEL - 1 AS report_date
                FROM params pa
                CONNECT BY LEVEL <= pa.end_date - pa.start_date + 1
            ),
            revenue_by_day AS (
                SELECT
                    TRUNC(o.paid_at) AS report_date,
                    NVL(SUM(o.grand_total), 0) AS revenue
                FROM params pa
                JOIN orders o
                    ON o.status IN ('PAID', 'COMPLETED')
                   AND o.paid_at >= pa.start_date
                   AND o.paid_at <  pa.end_date + 1
                GROUP BY TRUNC(o.paid_at)
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
            material_by_day AS (
                SELECT
                    TRUNC(bsp.scheduled_at) AS report_date,
                    NVL(SUM(NVL(mc.material_cost_per_time, 0)), 0) AS estimated_material_cost
                FROM params pa
                JOIN booking_service_pet bsp
                    ON bsp.status = 'DONE'
                   AND bsp.scheduled_at >= pa.start_date
                   AND bsp.scheduled_at <  pa.end_date + 1
                LEFT JOIN material_cost_per_service mc
                    ON mc.service_id = bsp.service_id
                GROUP BY TRUNC(bsp.scheduled_at)
            ),
            salary_base AS (
                SELECT
                    NVL(SUM(e.salary), 0) AS monthly_salary_cost
                FROM employee e
            ),
            salary_by_day AS (
                SELECT
                    rd.report_date,
                    ROUND(
                        sb.monthly_salary_cost
                        / (
                            LAST_DAY(rd.report_date)
                            - TRUNC(rd.report_date, 'MM')
                            + 1
                        ),
                        0
                    ) AS estimated_salary_cost
                FROM report_days rd
                CROSS JOIN salary_base sb
            )
            SELECT
                rd.report_date,
                NVL(r.revenue, 0) AS revenue,
                NVL(m.estimated_material_cost, 0) AS estimated_material_cost,
                NVL(s.estimated_salary_cost, 0) AS estimated_salary_cost,
                NVL(m.estimated_material_cost, 0)
                    + NVL(s.estimated_salary_cost, 0) AS estimated_total_cost,
                NVL(r.revenue, 0)
                    - (
                        NVL(m.estimated_material_cost, 0)
                        + NVL(s.estimated_salary_cost, 0)
                    ) AS estimated_profit
            FROM report_days rd
            LEFT JOIN revenue_by_day r
                ON r.report_date = rd.report_date
            LEFT JOIN material_by_day m
                ON m.report_date = rd.report_date
            LEFT JOIN salary_by_day s
                ON s.report_date = rd.report_date
            ORDER BY rd.report_date
        ";

        $rows = collect(DB::select($sql, [
            'p_start_date' => $range['start']->toDateString(),
            'p_end_date' => $range['end']->toDateString(),
        ]))->map(function (object $row): array {
            $reportDate = $this->carbonDate($this->rowValue($row, 'report_date')) ?? now();

            return [
                'report_date' => $reportDate->toDateString(),
                'label' => $reportDate->format('d/m'),
                'revenue' => $this->cleanNumber((float) ($this->rowValue($row, 'revenue') ?? 0)),
                'estimated_material_cost' => $this->cleanNumber((float) ($this->rowValue($row, 'estimated_material_cost') ?? 0)),
                'estimated_salary_cost' => $this->cleanNumber((float) ($this->rowValue($row, 'estimated_salary_cost') ?? 0)),
                'estimated_total_cost' => $this->cleanNumber((float) ($this->rowValue($row, 'estimated_total_cost') ?? 0)),
                'estimated_profit' => $this->cleanNumber((float) ($this->rowValue($row, 'estimated_profit') ?? 0)),
            ];
        })->values();

        $labels = $rows->pluck('label')->all();

        return [
            'title' => 'Xu hướng doanh thu - chi phí - lợi nhuận theo ngày',
            'labels' => $labels,
            'revenue' => $rows->pluck('revenue')->all(),
            'estimated_material_cost' => $rows->pluck('estimated_material_cost')->all(),
            'estimated_salary_cost' => $rows->pluck('estimated_salary_cost')->all(),
            'estimated_total_cost' => $rows->pluck('estimated_total_cost')->all(),
            'estimated_profit' => $rows->pluck('estimated_profit')->all(),
            'inventory_cost' => $rows->pluck('estimated_total_cost')->all(),
            'datasets' => [
                [
                    'key' => 'revenue',
                    'label' => 'Doanh thu',
                    'data' => $rows->pluck('revenue')->all(),
                ],
                [
                    'key' => 'estimated_material_cost',
                    'label' => 'Chi phí vật tư',
                    'data' => $rows->pluck('estimated_material_cost')->all(),
                ],
                [
                    'key' => 'estimated_salary_cost',
                    'label' => 'Chi phí lương',
                    'data' => $rows->pluck('estimated_salary_cost')->all(),
                ],
                [
                    'key' => 'estimated_total_cost',
                    'label' => 'Tổng chi phí ước tính',
                    'data' => $rows->pluck('estimated_total_cost')->all(),
                ],
                [
                    'key' => 'estimated_profit',
                    'label' => 'Lợi nhuận ước tính',
                    'data' => $rows->pluck('estimated_profit')->all(),
                ],
            ],
            'rows' => $rows->all(),
        ];
    }

    public function getFinanceMonthlyTrendChart(array $filters = []): array
    {
        $range = $this->resolvePeriodRange(
            $filters['period_type'] ?? $filters['period'] ?? 'month',
            $filters['date'] ?? null,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        $sql = "
            WITH params AS (
                SELECT
                    TO_DATE(:p_start_date, 'YYYY-MM-DD') AS start_date,
                    TO_DATE(:p_end_date, 'YYYY-MM-DD') AS end_date
                FROM dual
            ),
            report_months AS (
                SELECT
                    ADD_MONTHS(TRUNC(pa.start_date, 'MM'), LEVEL - 1) AS report_month,
                    ADD_MONTHS(TRUNC(pa.start_date, 'MM'), LEVEL - 1) AS month_start,
                    LAST_DAY(ADD_MONTHS(TRUNC(pa.start_date, 'MM'), LEVEL - 1)) AS month_end,
                    pa.start_date,
                    pa.end_date
                FROM params pa
                CONNECT BY LEVEL <= MONTHS_BETWEEN(
                    TRUNC(pa.end_date, 'MM'),
                    TRUNC(pa.start_date, 'MM')
                ) + 1
            ),
            revenue_by_month AS (
                SELECT
                    TRUNC(o.paid_at, 'MM') AS report_month,
                    NVL(SUM(o.grand_total), 0) AS revenue
                FROM params pa
                JOIN orders o
                    ON o.status IN ('PAID', 'COMPLETED')
                   AND o.paid_at >= pa.start_date
                   AND o.paid_at <  pa.end_date + 1
                GROUP BY TRUNC(o.paid_at, 'MM')
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
            material_by_month AS (
                SELECT
                    TRUNC(bsp.scheduled_at, 'MM') AS report_month,
                    NVL(SUM(NVL(mc.material_cost_per_time, 0)), 0) AS estimated_material_cost
                FROM params pa
                JOIN booking_service_pet bsp
                    ON bsp.status = 'DONE'
                   AND bsp.scheduled_at >= pa.start_date
                   AND bsp.scheduled_at <  pa.end_date + 1
                LEFT JOIN material_cost_per_service mc
                    ON mc.service_id = bsp.service_id
                GROUP BY TRUNC(bsp.scheduled_at, 'MM')
            ),
            salary_base AS (
                SELECT
                    NVL(SUM(e.salary), 0) AS monthly_salary_cost
                FROM employee e
            ),
            salary_by_month AS (
                SELECT
                    rm.report_month,
                    ROUND(
                        sb.monthly_salary_cost
                        * (
                            LEAST(rm.month_end, rm.end_date)
                            - GREATEST(rm.month_start, rm.start_date)
                            + 1
                        )
                        / (rm.month_end - rm.month_start + 1),
                        0
                    ) AS estimated_salary_cost
                FROM report_months rm
                CROSS JOIN salary_base sb
            )
            SELECT
                rm.report_month,
                NVL(r.revenue, 0) AS revenue,
                NVL(m.estimated_material_cost, 0) AS estimated_material_cost,
                NVL(s.estimated_salary_cost, 0) AS estimated_salary_cost,
                NVL(m.estimated_material_cost, 0)
                    + NVL(s.estimated_salary_cost, 0) AS estimated_total_cost,
                NVL(r.revenue, 0)
                    - (
                        NVL(m.estimated_material_cost, 0)
                        + NVL(s.estimated_salary_cost, 0)
                    ) AS estimated_profit
            FROM report_months rm
            LEFT JOIN revenue_by_month r
                ON r.report_month = rm.report_month
            LEFT JOIN material_by_month m
                ON m.report_month = rm.report_month
            LEFT JOIN salary_by_month s
                ON s.report_month = rm.report_month
            ORDER BY rm.report_month
        ";

        $rows = collect(DB::select($sql, [
            'p_start_date' => $range['start']->toDateString(),
            'p_end_date' => $range['end']->toDateString(),
        ]))->map(function (object $row): array {
            $reportMonth = $this->carbonDate($this->rowValue($row, 'report_month')) ?? now();

            return [
                'report_month' => $reportMonth->format('Y-m'),
                'label' => $reportMonth->format('m/Y'),
                'revenue' => $this->cleanNumber((float) ($this->rowValue($row, 'revenue') ?? 0)),
                'estimated_material_cost' => $this->cleanNumber((float) ($this->rowValue($row, 'estimated_material_cost') ?? 0)),
                'estimated_salary_cost' => $this->cleanNumber((float) ($this->rowValue($row, 'estimated_salary_cost') ?? 0)),
                'estimated_total_cost' => $this->cleanNumber((float) ($this->rowValue($row, 'estimated_total_cost') ?? 0)),
                'estimated_profit' => $this->cleanNumber((float) ($this->rowValue($row, 'estimated_profit') ?? 0)),
            ];
        })->values();

        return [
            'title' => 'Xu hướng doanh thu - chi phí - lợi nhuận theo tháng',
            'labels' => $rows->pluck('label')->all(),
            'revenue' => $rows->pluck('revenue')->all(),
            'estimated_material_cost' => $rows->pluck('estimated_material_cost')->all(),
            'estimated_salary_cost' => $rows->pluck('estimated_salary_cost')->all(),
            'estimated_total_cost' => $rows->pluck('estimated_total_cost')->all(),
            'estimated_profit' => $rows->pluck('estimated_profit')->all(),
            'datasets' => [
                [
                    'key' => 'revenue',
                    'label' => 'Doanh thu',
                    'data' => $rows->pluck('revenue')->all(),
                ],
                [
                    'key' => 'estimated_material_cost',
                    'label' => 'Chi phí vật tư',
                    'data' => $rows->pluck('estimated_material_cost')->all(),
                ],
                [
                    'key' => 'estimated_salary_cost',
                    'label' => 'Chi phí lương',
                    'data' => $rows->pluck('estimated_salary_cost')->all(),
                ],
                [
                    'key' => 'estimated_total_cost',
                    'label' => 'Tổng chi phí ước tính',
                    'data' => $rows->pluck('estimated_total_cost')->all(),
                ],
                [
                    'key' => 'estimated_profit',
                    'label' => 'Lợi nhuận ước tính',
                    'data' => $rows->pluck('estimated_profit')->all(),
                ],
            ],
            'rows' => $rows->all(),
        ];
    }

    private function estimatedProfitMetrics(array $filters = []): array
    {
        $range = $this->resolvePeriodRange(
            $filters['period_type'] ?? $filters['period'] ?? 'month',
            $filters['date'] ?? null,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );
        $previousRange = $this->previousRange($range, $filters);

        $sql = "
            WITH params AS (
                SELECT
                    TO_DATE(:p_start_date, 'YYYY-MM-DD') AS start_date,
                    TO_DATE(:p_end_date, 'YYYY-MM-DD') AS end_date,
                    TO_DATE(:p_prev_start_date, 'YYYY-MM-DD') AS prev_start_date,
                    TO_DATE(:p_prev_end_date, 'YYYY-MM-DD') AS prev_end_date
                FROM dual
            ),
            current_revenue AS (
                SELECT
                    NVL(SUM(o.grand_total), 0) AS current_total_revenue
                FROM params pa
                JOIN orders o
                    ON o.status IN ('PAID', 'COMPLETED')
                   AND o.paid_at >= pa.start_date
                   AND o.paid_at <  pa.end_date + 1
            ),
            previous_revenue AS (
                SELECT
                    NVL(SUM(o.grand_total), 0) AS previous_total_revenue
                FROM params pa
                JOIN orders o
                    ON o.status IN ('PAID', 'COMPLETED')
                   AND o.paid_at >= pa.prev_start_date
                   AND o.paid_at <  pa.prev_end_date + 1
            ),
            salary_base AS (
                SELECT
                    NVL(SUM(e.salary), 0) AS monthly_salary_cost
                FROM employee e
            ),
            current_report_months AS (
                SELECT
                    ADD_MONTHS(TRUNC(pa.start_date, 'MM'), LEVEL - 1) AS month_start,
                    LAST_DAY(ADD_MONTHS(TRUNC(pa.start_date, 'MM'), LEVEL - 1)) AS month_end,
                    pa.start_date,
                    pa.end_date
                FROM params pa
                CONNECT BY LEVEL <= MONTHS_BETWEEN(
                    TRUNC(pa.end_date, 'MM'),
                    TRUNC(pa.start_date, 'MM')
                ) + 1
            ),
            previous_report_months AS (
                SELECT
                    ADD_MONTHS(TRUNC(pa.prev_start_date, 'MM'), LEVEL - 1) AS month_start,
                    LAST_DAY(ADD_MONTHS(TRUNC(pa.prev_start_date, 'MM'), LEVEL - 1)) AS month_end,
                    pa.prev_start_date,
                    pa.prev_end_date
                FROM params pa
                CONNECT BY LEVEL <= MONTHS_BETWEEN(
                    TRUNC(pa.prev_end_date, 'MM'),
                    TRUNC(pa.prev_start_date, 'MM')
                ) + 1
            ),
            current_salary AS (
                SELECT
                    NVL(
                        ROUND(
                            SUM(
                                sb.monthly_salary_cost
                                * (
                                    LEAST(rm.month_end, rm.end_date)
                                    - GREATEST(rm.month_start, rm.start_date)
                                    + 1
                                )
                                / (rm.month_end - rm.month_start + 1)
                            ),
                            0
                        ),
                        0
                    ) AS current_salary_cost
                FROM current_report_months rm
                CROSS JOIN salary_base sb
            ),
            previous_salary AS (
                SELECT
                    NVL(
                        ROUND(
                            SUM(
                                sb.monthly_salary_cost
                                * (
                                    LEAST(rm.month_end, rm.prev_end_date)
                                    - GREATEST(rm.month_start, rm.prev_start_date)
                                    + 1
                                )
                                / (rm.month_end - rm.month_start + 1)
                            ),
                            0
                        ),
                        0
                    ) AS previous_salary_cost
                FROM previous_report_months rm
                CROSS JOIN salary_base sb
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
            current_material AS (
                SELECT
                    NVL(SUM(NVL(mc.material_cost_per_time, 0)), 0) AS current_material_cost
                FROM params pa
                JOIN booking_service_pet bsp
                    ON bsp.status = 'DONE'
                   AND bsp.scheduled_at >= pa.start_date
                   AND bsp.scheduled_at <  pa.end_date + 1
                LEFT JOIN material_cost_per_service mc
                    ON mc.service_id = bsp.service_id
            ),
            previous_material AS (
                SELECT
                    NVL(SUM(NVL(mc.material_cost_per_time, 0)), 0) AS previous_material_cost
                FROM params pa
                JOIN booking_service_pet bsp
                    ON bsp.status = 'DONE'
                   AND bsp.scheduled_at >= pa.prev_start_date
                   AND bsp.scheduled_at <  pa.prev_end_date + 1
                LEFT JOIN material_cost_per_service mc
                    ON mc.service_id = bsp.service_id
            ),
            totals AS (
                SELECT
                    current_revenue.current_total_revenue,
                    previous_revenue.previous_total_revenue,
                    current_salary.current_salary_cost,
                    previous_salary.previous_salary_cost,
                    current_material.current_material_cost,
                    previous_material.previous_material_cost,
                    current_salary.current_salary_cost + current_material.current_material_cost
                        AS current_estimated_total_cost,
                    previous_salary.previous_salary_cost + previous_material.previous_material_cost
                        AS previous_estimated_total_cost,
                    current_revenue.current_total_revenue
                        - (current_salary.current_salary_cost + current_material.current_material_cost)
                        AS current_estimated_profit,
                    previous_revenue.previous_total_revenue
                        - (previous_salary.previous_salary_cost + previous_material.previous_material_cost)
                        AS previous_estimated_profit
                FROM current_revenue
                CROSS JOIN previous_revenue
                CROSS JOIN current_salary
                CROSS JOIN previous_salary
                CROSS JOIN current_material
                CROSS JOIN previous_material
            )
            SELECT
                current_total_revenue,
                previous_total_revenue,
                current_salary_cost,
                previous_salary_cost,
                current_material_cost,
                previous_material_cost,
                current_estimated_total_cost,
                previous_estimated_total_cost,
                current_estimated_profit,
                previous_estimated_profit
            FROM totals
        ";

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_start_date' => $range['start']->toDateString(),
            'p_end_date' => $range['end']->toDateString(),
            'p_prev_start_date' => $previousRange['start']->toDateString(),
            'p_prev_end_date' => $previousRange['end']->toDateString(),
        ]), CASE_LOWER);

        return [
            'current_total_revenue' => (float) ($row['current_total_revenue'] ?? 0),
            'previous_total_revenue' => (float) ($row['previous_total_revenue'] ?? 0),
            'current_salary_cost' => (float) ($row['current_salary_cost'] ?? 0),
            'previous_salary_cost' => (float) ($row['previous_salary_cost'] ?? 0),
            'current_material_cost' => (float) ($row['current_material_cost'] ?? 0),
            'previous_material_cost' => (float) ($row['previous_material_cost'] ?? 0),
            'current_estimated_total_cost' => (float) ($row['current_estimated_total_cost'] ?? 0),
            'previous_estimated_total_cost' => (float) ($row['previous_estimated_total_cost'] ?? 0),
            'current_estimated_profit' => (float) ($row['current_estimated_profit'] ?? 0),
            'previous_estimated_profit' => (float) ($row['previous_estimated_profit'] ?? 0),
        ];
    }

    private function resolvePeriodRange(
        string $period,
        mixed $date = null,
        mixed $startDate = null,
        mixed $endDate = null
    ): array
    {
        $period = in_array($period, ['day', 'month', 'year'], true) ? $period : 'month';

        if ($startDate || $endDate) {
            $start = $this->carbonDate($startDate)?->startOfDay() ?? now()->startOfMonth();
            $end = $this->carbonDate($endDate)?->endOfDay() ?? now()->endOfDay();

            return [
                'period' => $period,
                'group_by' => $this->groupByFor($period),
                'start' => $start,
                'end' => $end,
            ];
        }

        if ($date) {
            $selectedDate = $this->carbonDate($date) ?? now();

            return match ($period) {
                'day' => [
                    'period' => 'day',
                    'group_by' => 'hour',
                    'start' => $selectedDate->copy()->startOfDay(),
                    'end' => $selectedDate->copy()->endOfDay(),
                ],
                'year' => [
                    'period' => 'year',
                    'group_by' => 'month',
                    'start' => $selectedDate->copy()->startOfYear(),
                    'end' => $selectedDate->copy()->endOfYear(),
                ],
                default => [
                    'period' => 'month',
                    'group_by' => 'day',
                    'start' => $selectedDate->copy()->startOfMonth(),
                    'end' => $selectedDate->copy()->endOfMonth(),
                ],
            };
        }

        $bounds = $this->financeDateBounds();

        return [
            'period' => 'all',
            'group_by' => 'month',
            'start' => $bounds['start'],
            'end' => $bounds['end'],
        ];
    }

    private function groupByFor(string $period): string
    {
        return match ($period) {
            'day' => 'hour',
            'year' => 'month',
            default => 'day',
        };
    }

    private function previousRange(array $range, array $filters = []): array
    {
        $previousStart = $this->carbonDate($filters['prev_start_date'] ?? null);
        $previousEnd = $this->carbonDate($filters['prev_end_date'] ?? null);

        if ($previousStart && $previousEnd) {
            return [
                'start' => $previousStart->startOfDay(),
                'end' => $previousEnd->endOfDay(),
            ];
        }

        $start = $range['start']->copy()->startOfDay();
        $end = $range['end']->copy()->startOfDay();
        $daysInPeriod = (int) $start->diffInDays($end) + 1;
        $previousEnd = $start->copy()->subDay();

        return [
            'start' => $previousEnd->copy()->subDays($daysInPeriod - 1)->startOfDay(),
            'end' => $previousEnd->endOfDay(),
        ];
    }

    private function revenueRows(array $range): Collection
    {
        if ($this->hasSuccessfulPayments()) {
            return DB::table('payments')
                ->join('orders', 'payments.order_id', '=', 'orders.order_id')
                ->whereIn('payments.status', self::PAYMENT_SUCCESS_STATUSES)
                ->whereNotNull('payments.paid_at')
                ->whereNotIn('orders.status', self::ORDER_EXCLUDED_STATUSES)
                ->whereBetween('payments.paid_at', [$range['start'], $range['end']])
                ->select([
                    'payments.amount',
                    'payments.paid_at as occurred_at',
                ])
                ->get()
                ->map(fn (object $row): array => [
                    'amount' => (float) $this->rowValue($row, 'amount'),
                    'occurred_at' => $this->rowValue($row, 'occurred_at'),
                ]);
        }

        return DB::table('orders')
            ->whereIn('status', self::ORDER_REVENUE_STATUSES)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$range['start'], $range['end']])
            ->select([
                'grand_total as amount',
                'paid_at as occurred_at',
            ])
            ->get()
            ->map(fn (object $row): array => [
                'amount' => (float) $this->rowValue($row, 'amount'),
                'occurred_at' => $this->rowValue($row, 'occurred_at'),
            ]);
    }

    private function inventoryCostRows(array $range): Collection
    {
        if (! Schema::hasTable('goods_receipt') || ! Schema::hasTable('goods_receipt_detail')) {
            return collect();
        }

        return DB::table('goods_receipt_detail')
            ->join('goods_receipt', 'goods_receipt_detail.goods_receipt_id', '=', 'goods_receipt.goods_receipt_id')
            ->whereIn('goods_receipt.status', self::RECEIPT_VALID_STATUSES)
            ->whereBetween('goods_receipt.receipt_date', [$range['start'], $range['end']])
            ->select([
                'goods_receipt_detail.line_total as amount',
                'goods_receipt.receipt_date as occurred_at',
            ])
            ->get()
            ->map(fn (object $row): array => [
                'amount' => (float) $this->rowValue($row, 'amount'),
                'occurred_at' => $this->rowValue($row, 'occurred_at'),
            ]);
    }

    private function revenueCostChart(array $range, Collection $revenueRows, Collection $inventoryCostRows): array
    {
        $buckets = $this->emptyBuckets($range);
        $revenue = $buckets;
        $inventoryCost = $buckets;

        foreach ($revenueRows as $row) {
            $key = $this->bucketKey($row['occurred_at'], $range['group_by']);

            if (array_key_exists($key, $revenue)) {
                $revenue[$key] += (float) $row['amount'];
            }
        }

        foreach ($inventoryCostRows as $row) {
            $key = $this->bucketKey($row['occurred_at'], $range['group_by']);

            if (array_key_exists($key, $inventoryCost)) {
                $inventoryCost[$key] += (float) $row['amount'];
            }
        }

        return [
            'labels' => $this->bucketLabels($range),
            'revenue' => array_map(fn (float $value): int|float => $this->cleanNumber($value), array_values($revenue)),
            'inventory_cost' => array_map(fn (float $value): int|float => $this->cleanNumber($value), array_values($inventoryCost)),
        ];
    }

    private function revenueMixChart(array $range): array
    {
        $buckets = $this->emptyBuckets($range);
        $groomingRevenue = $buckets;
        $hotelRevenue = $buckets;

        foreach ($this->orderDetailRevenueRows($range) as $row) {
            $key = $this->bucketKey($row['occurred_at'], $range['group_by']);

            if (! array_key_exists($key, $groomingRevenue)) {
                continue;
            }

            if ($row['type'] === 'hotel') {
                $hotelRevenue[$key] += (float) $row['amount'];
            } else {
                $groomingRevenue[$key] += (float) $row['amount'];
            }
        }

        return [
            'labels' => $this->bucketLabels($range),
            'grooming_revenue' => array_map(fn (float $value): int|float => $this->cleanNumber($value), array_values($groomingRevenue)),
            'hotel_revenue' => array_map(fn (float $value): int|float => $this->cleanNumber($value), array_values($hotelRevenue)),
        ];
    }

    private function orderDetailRevenueRows(array $range): Collection
    {
        $query = DB::table('order_details')
            ->join('orders', 'order_details.order_id', '=', 'orders.order_id')
            ->whereBetween($this->hasSuccessfulPayments() ? 'payments.paid_at' : 'orders.paid_at', [$range['start'], $range['end']])
            ->whereNotIn('orders.status', self::ORDER_EXCLUDED_STATUSES);

        if ($this->hasSuccessfulPayments()) {
            $query->join('payments', 'orders.order_id', '=', 'payments.order_id')
                ->whereIn('payments.status', self::PAYMENT_SUCCESS_STATUSES)
                ->whereNotNull('payments.paid_at')
                ->select([
                    'order_details.line_total',
                    'orders.subtotal',
                    'payments.amount as paid_amount',
                    'order_details.booking_room_id',
                    'order_details.booking_service_pet_id',
                    'payments.paid_at as occurred_at',
                ]);
        } else {
            $query->whereIn('orders.status', self::ORDER_REVENUE_STATUSES)
                ->whereNotNull('orders.paid_at')
                ->select([
                    'order_details.line_total',
                    'orders.subtotal',
                    'orders.grand_total as paid_amount',
                    'order_details.booking_room_id',
                    'order_details.booking_service_pet_id',
                    'orders.paid_at as occurred_at',
                ]);
        }

        return $query->get()
            ->map(function (object $row): array {
                $lineTotal = (float) $this->rowValue($row, 'line_total');
                $subtotal = (float) $this->rowValue($row, 'subtotal');
                $paidAmount = (float) $this->rowValue($row, 'paid_amount');

                return [
                    // Allocate actual collected order revenue back to each detail line so discounts/partial payments do not inflate the mix.
                    'amount' => $subtotal > 0 ? ($lineTotal / $subtotal) * $paidAmount : $lineTotal,
                    'occurred_at' => $this->rowValue($row, 'occurred_at'),
                    'type' => filled($this->rowValue($row, 'booking_room_id')) ? 'hotel' : 'grooming',
                ];
            });
    }

    private function grossMarginAnalysis(): array
    {
        return Service::query()
            ->where('is_active', 1)
            ->with('serviceProductDetails.product')
            ->orderBy('service_name')
            ->get()
            ->map(function (Service $service): array {
                $sellingPrice = (float) $service->base_price;
                // Cost is estimated from material norms because this schema has no direct service cost table.
                $costPrice = (float) $service->serviceProductDetails->sum(function ($detail): float {
                    return (float) $detail->amount * (float) ($detail->product?->item_price ?? 0);
                });
                $grossProfit = $sellingPrice - $costPrice;

                return [
                    'service_name' => (string) $service->service_name,
                    'selling_price' => $this->cleanNumber($sellingPrice),
                    'cost_price' => $this->cleanNumber($costPrice),
                    'gross_profit' => $this->cleanNumber($grossProfit),
                    // margin_percent = gross profit / selling price * 100.
                    'margin_percent' => $sellingPrice > 0 ? $this->cleanNumber(($grossProfit / $sellingPrice) * 100) : 0,
                ];
            })
            ->values()
            ->all();
    }

    private function lossRiskAlerts(): array
    {
        // Current migrated schema has material norms and stock balances, but no stock export/consumption ledger.
        // Without actual consumption rows, expected vs actual loss cannot be calculated reliably.
        return [];
    }

    private function emptyBuckets(array $range): array
    {
        $buckets = [];
        $cursor = $range['start']->copy();

        while ($cursor->lte($range['end'])) {
            $buckets[$this->bucketKey($cursor, $range['group_by'])] = 0.0;
            $cursor = match ($range['group_by']) {
                'hour' => $cursor->addHour(),
                'month' => $cursor->addMonthNoOverflow(),
                default => $cursor->addDay(),
            };
        }

        return $buckets;
    }

    private function bucketLabels(array $range): array
    {
        return collect(array_keys($this->emptyBuckets($range)))
            ->map(function (string $key) use ($range): string {
                return match ($range['group_by']) {
                    'hour' => Carbon::createFromFormat('!Y-m-d H', $key)->format('H:00'),
                    'month' => Carbon::createFromFormat('!Y-m', $key)->format('m/Y'),
                    default => Carbon::createFromFormat('!Y-m-d', $key)->format('d/m'),
                };
            })
            ->all();
    }

    private function bucketKey(mixed $value, string $groupBy): string
    {
        $date = $value instanceof Carbon ? $value : (Carbon::make($value) ?? now());

        return match ($groupBy) {
            'hour' => $date->format('Y-m-d H'),
            'month' => $date->format('Y-m'),
            default => $date->format('Y-m-d'),
        };
    }

    private function hasSuccessfulPayments(): bool
    {
        return Schema::hasTable('payments')
            && DB::table('payments')->whereIn('status', self::PAYMENT_SUCCESS_STATUSES)->exists();
    }

    private function cleanNumber(float $value): int|float
    {
        $rounded = round($value, 2);

        return abs($rounded - round($rounded)) < 0.00001
            ? (int) round($rounded)
            : $rounded;
    }

    private function growthPercent(float $current, float $previous): int|float
    {
        if (abs($previous) < 0.00001) {
            return $current > 0 ? 100 : 0;
        }

        return $this->cleanNumber((($current - $previous) / $previous) * 100);
    }

    private function nullableGrowthPercent(?float $current, ?float $previous): int|float|null
    {
        if ($current === null || $previous === null) {
            return null;
        }

        return $this->growthPercent($current, $previous);
    }

    private function marginPercent(float $profit, float $revenue): int|float|null
    {
        if (abs($revenue) < 0.00001) {
            return null;
        }

        return $this->cleanNumber(($profit / $revenue) * 100);
    }

    private function trend(float $current, float $previous): string
    {
        if ($current > $previous) {
            return 'up';
        }

        if ($current < $previous) {
            return 'down';
        }

        return 'neutral';
    }

    private function nullableTrend(?float $current, ?float $previous): string
    {
        if ($current === null || $previous === null) {
            return 'neutral';
        }

        return $this->trend($current, $previous);
    }

    private function rowValue(?object $row, string $key): mixed
    {
        if ($row === null) {
            return null;
        }

        return $row->{$key} ?? $row->{strtoupper($key)} ?? null;
    }

    private function carbonDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        return filled($value) ? Carbon::make($value) : null;
    }

    private function financeDateBounds(): array
    {
        $start = null;
        $end = null;

        try {
            $row = DB::table('orders')
                ->whereNotNull('paid_at')
                ->selectRaw('MIN(paid_at) as start_date, MAX(paid_at) as end_date')
                ->first();

            $start = $this->carbonDate($this->rowValue($row, 'start_date') ?? null);
            $end = $this->carbonDate($this->rowValue($row, 'end_date') ?? null);
        } catch (Throwable) {
            $start = null;
            $end = null;
        }

        return [
            'start' => ($start ?? now()->startOfMonth())->startOfDay(),
            'end' => ($end ?? now()->endOfMonth())->endOfDay(),
        ];
    }
}
