<?php

namespace App\Repositories\Eloquent\Ceo;

use App\Models\Branch;
use App\Models\Service;
use App\Repositories\Contracts\Ceo\ServiceRevenueRepositoryInterface;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ServiceRevenueRepository implements ServiceRevenueRepositoryInterface
{
    private const ACTIVE_VALUE = 1;

    private const PAID_PAYMENT_STATUSES = [
        'SUCCESS',
    ];

    private const PAID_ORDER_STATUSES = [
        'COMPLETED',
        'PAID',
    ];

    private const SERVED_SERVICE_STATUSES = [
        'DONE',
    ];

    private const SORTABLE_COLUMNS = [
        'service_id',
        'service_name',
        'revenue',
        'revenue_share',
        'served_count_30_days',
        'coverage_rate',
        'status',
    ];

    /**
     * Mo ta chuc nang:
     * Lay KPI dich vu toan chuoi theo doanh thu da thanh toan trong ky loc.
     *
     * Input:
     * - array $filters gom start_date, end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang KPI gom top_revenue_service, top_revenue_amount, top_revenue_percent,
     *   active_service_count va no_revenue_service_count.
     *
     * Ghi chu:
     * - Dich vu khong phat sinh duoc tinh theo viec khong co doanh thu da thanh toan
     *   trong paid_service_lines, khong tinh theo booking_service_pet rieng le.
     */
    public function getServiceSummary(array $filters = []): array
    {
        [$startDate, $endDate] = $this->dateRange($filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS p_start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS p_end_date
                FROM dual
            ),
            paid_service_lines AS (
                SELECT
                    o.order_id,
                    od.order_detail_id,
                    bsp.booking_service_pet_id,
                    bsp.service_id,
                    s.service_name,
                    od.line_total,
                    NVL(CAST(o.paid_at AS DATE), CAST(o.created_at AS DATE)) AS report_date
                FROM orders o
                JOIN order_details od
                    ON od.order_id = o.order_id
                JOIN booking_service_pet bsp
                    ON bsp.booking_service_pet_id = od.booking_service_pet_id
                JOIN services s
                    ON s.service_id = bsp.service_id
                CROSS JOIN params prm
                WHERE od.booking_service_pet_id IS NOT NULL
                  AND o.status IN ('PAID', 'COMPLETED', 'DONE')
                  AND NVL(CAST(o.paid_at AS DATE), CAST(o.created_at AS DATE)) >= prm.p_start_date
                  AND NVL(CAST(o.paid_at AS DATE), CAST(o.created_at AS DATE)) <  prm.p_end_date + 1
            ),
            service_revenue AS (
                SELECT
                    service_id,
                    service_name,
                    SUM(NVL(line_total, 0)) AS service_revenue
                FROM paid_service_lines
                GROUP BY
                    service_id,
                    service_name
            ),
            ranked_service AS (
                SELECT
                    ROW_NUMBER() OVER (
                        ORDER BY service_revenue DESC, service_id
                    ) AS rank_no,
                    service_id,
                    service_name,
                    service_revenue,
                    SUM(service_revenue) OVER () AS total_service_revenue
                FROM service_revenue
            )
            SELECT
                (
                    SELECT service_name
                    FROM ranked_service
                    WHERE rank_no = 1
                ) AS top_revenue_service,

                NVL((
                    SELECT service_revenue
                    FROM ranked_service
                    WHERE rank_no = 1
                ), 0) AS top_revenue_amount,

                NVL((
                    SELECT ROUND(service_revenue / NULLIF(total_service_revenue, 0) * 100, 2)
                    FROM ranked_service
                    WHERE rank_no = 1
                ), 0) AS top_revenue_percent,

                (
                    SELECT COUNT(*)
                    FROM services s
                    WHERE s.is_active = 1
                ) AS active_service_count,

                (
                    SELECT COUNT(*)
                    FROM services s
                    WHERE NOT EXISTS (
                        SELECT 1
                        FROM service_revenue sr
                        WHERE sr.service_id = s.service_id
                    )
                ) AS no_revenue_service_count
            FROM dual
            SQL;

        $row = DB::selectOne($sql, [
            'p_start_date' => $startDate,
            'p_end_date' => $endDate,
        ]);

        return [
            'top_revenue_service' => $this->rowValue($row, 'top_revenue_service'),
            'top_revenue_amount' => $this->cleanNumber((float) ($this->rowValue($row, 'top_revenue_amount') ?? 0)),
            'top_revenue_percent' => $this->cleanNumber((float) ($this->rowValue($row, 'top_revenue_percent') ?? 0)),
            'active_service_count' => (int) ($this->rowValue($row, 'active_service_count') ?? 0),
            'no_revenue_service_count' => (int) ($this->rowValue($row, 'no_revenue_service_count') ?? 0),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach dich vu quan tri toan chuoi theo ky loc hien tai.
     *
     * Input:
     * - array $filters gom start_date, end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang dong table gom thong tin dich vu, danh muc, trang thai, gia ban,
     *   von vat tu tam tinh, margin, luot phuc vu va do phu chi nhanh.
     *
     * Ghi chu:
     * - Chi phi vat tu duoc tach thanh CTE rieng de tranh nhan dong voi lich su phuc vu.
     */
    public function getServiceCatalog(array $filters = []): array
    {
        [$startDate, $endDate] = $this->dateRange($filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS p_start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS p_end_date
                FROM dual
            ),
            material_cost_per_service AS (
                SELECT
                    spd.service_id,
                    SUM(NVL(spd.amount, 0) * NVL(p.item_price, 0)) AS material_cost_per_time
                FROM service_product_detail spd
                JOIN product p
                    ON p.product_id = spd.product_id
                GROUP BY
                    spd.service_id
            ),
            service_activity_in_period AS (
                SELECT
                    bsp.service_id,
                    COUNT(DISTINCT bsp.booking_service_pet_id) AS service_count_in_period,
                    COUNT(DISTINCT b.branch_id) AS covered_branch_count
                FROM booking_service_pet bsp
                LEFT JOIN booking b
                    ON b.booking_id = bsp.booking_id
                CROSS JOIN params prm
                WHERE CAST(bsp.scheduled_at AS DATE) >= prm.p_start_date
                  AND CAST(bsp.scheduled_at AS DATE) <  prm.p_end_date + 1
                GROUP BY
                    bsp.service_id
            ),
            active_branch_count AS (
                SELECT
                    COUNT(*) AS total_active_branch
                FROM branch br
                WHERE br.is_active = 1
            )
            SELECT
                s.service_id,
                s.service_name,
                cs.service_category_name,

                CASE
                    WHEN s.is_active = 1 THEN 'Hoạt động'
                    ELSE 'Đã ẩn/Ngưng'
                END AS service_status,

                s.base_price,

                NVL(mc.material_cost_per_time, 0) AS estimated_material_cost,

                ROUND(
                    CASE
                        WHEN s.base_price > 0 THEN
                            (
                                s.base_price - NVL(mc.material_cost_per_time, 0)
                            ) / s.base_price * 100
                        ELSE 0
                    END,
                    2
                ) AS margin_percent,

                NVL(sa.service_count_in_period, 0) AS service_count_in_period,

                NVL(sa.covered_branch_count, 0) AS covered_branch_count,

                ab.total_active_branch,

                ROUND(
                    NVL(sa.covered_branch_count, 0)
                    / NULLIF(ab.total_active_branch, 0) * 100,
                    2
                ) AS coverage_percent
            FROM services s
            LEFT JOIN category_services cs
                ON cs.service_category_id = s.service_category_id
            LEFT JOIN material_cost_per_service mc
                ON mc.service_id = s.service_id
            LEFT JOIN service_activity_in_period sa
                ON sa.service_id = s.service_id
            CROSS JOIN active_branch_count ab
            ORDER BY
                s.service_id
            SQL;

        return array_map(function (object $row): array {
            return [
                'service_id' => (int) ($this->rowValue($row, 'service_id') ?? 0),
                'service_name' => (string) ($this->rowValue($row, 'service_name') ?? ''),
                'service_category_name' => (string) ($this->rowValue($row, 'service_category_name') ?? ''),
                'service_status' => (string) ($this->rowValue($row, 'service_status') ?? ''),
                'base_price' => $this->cleanNumber((float) ($this->rowValue($row, 'base_price') ?? 0)),
                'estimated_material_cost' => $this->cleanNumber((float) ($this->rowValue($row, 'estimated_material_cost') ?? 0)),
                'margin_percent' => $this->cleanNumber((float) ($this->rowValue($row, 'margin_percent') ?? 0)),
                'service_count_in_period' => (int) ($this->rowValue($row, 'service_count_in_period') ?? 0),
                'covered_branch_count' => (int) ($this->rowValue($row, 'covered_branch_count') ?? 0),
                'total_active_branch' => (int) ($this->rowValue($row, 'total_active_branch') ?? 0),
                'coverage_percent' => $this->cleanNumber((float) ($this->rowValue($row, 'coverage_percent') ?? 0)),
            ];
        }, DB::select($sql, [
            'p_start_date' => $startDate,
            'p_end_date' => $endDate,
        ]));
    }

    /**
     * Mo ta chuc nang:
     * Lay dich vu ganh doanh thu toan chuoi trong ky loc.
     *
     * Input:
     * - array $filters gom start_date, end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mot dong table gom rank_no, service_id, service_name, service_revenue,
     *   total_service_revenue, revenue_percent va display_text hoac null.
     *
     * Ghi chu:
     * - Dung paid_service_lines lam nguon doanh thu chuan de khong tinh doanh thu
     *   phong/luu tru khong gan voi booking_service_pet.
     */
    public function getHighestRevenueService(array $filters = []): ?array
    {
        [$startDate, $endDate] = $this->dateRange($filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS p_start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS p_end_date
                FROM dual
            ),
            paid_service_lines AS (
                SELECT
                    o.order_id,
                    od.order_detail_id,
                    bsp.booking_service_pet_id,
                    bsp.service_id,
                    s.service_name,
                    od.line_total,
                    NVL(CAST(o.paid_at AS DATE), CAST(o.created_at AS DATE)) AS report_date
                FROM orders o
                JOIN order_details od
                    ON od.order_id = o.order_id
                JOIN booking_service_pet bsp
                    ON bsp.booking_service_pet_id = od.booking_service_pet_id
                JOIN services s
                    ON s.service_id = bsp.service_id
                CROSS JOIN params prm
                WHERE od.booking_service_pet_id IS NOT NULL
                  AND o.status IN ('PAID', 'COMPLETED', 'DONE')
                  AND NVL(CAST(o.paid_at AS DATE), CAST(o.created_at AS DATE)) >= prm.p_start_date
                  AND NVL(CAST(o.paid_at AS DATE), CAST(o.created_at AS DATE)) <  prm.p_end_date + 1
            ),
            service_revenue AS (
                SELECT
                    service_id,
                    service_name,
                    SUM(NVL(line_total, 0)) AS service_revenue
                FROM paid_service_lines
                GROUP BY
                    service_id,
                    service_name
            ),
            ranked_service AS (
                SELECT
                    ROW_NUMBER() OVER (
                        ORDER BY service_revenue DESC, service_id
                    ) AS rank_no,
                    service_id,
                    service_name,
                    service_revenue,
                    SUM(service_revenue) OVER () AS total_service_revenue
                FROM service_revenue
            )
            SELECT
                rank_no,
                service_id,
                service_name,
                service_revenue,
                total_service_revenue,

                ROUND(
                    service_revenue / NULLIF(total_service_revenue, 0) * 100,
                    2
                ) AS revenue_percent,

                service_name
                    || ' - chiếm '
                    || ROUND(service_revenue / NULLIF(total_service_revenue, 0) * 100, 2)
                    || '% tổng doanh thu dịch vụ' AS display_text
            FROM ranked_service
            WHERE rank_no = 1
            SQL;

        $row = DB::selectOne($sql, [
            'p_start_date' => $startDate,
            'p_end_date' => $endDate,
        ]);

        if ($row === null) {
            return null;
        }

        return [
            'rank_no' => (int) ($this->rowValue($row, 'rank_no') ?? 0),
            'service_id' => (int) ($this->rowValue($row, 'service_id') ?? 0),
            'service_name' => (string) ($this->rowValue($row, 'service_name') ?? ''),
            'service_revenue' => $this->cleanNumber((float) ($this->rowValue($row, 'service_revenue') ?? 0)),
            'total_service_revenue' => $this->cleanNumber((float) ($this->rowValue($row, 'total_service_revenue') ?? 0)),
            'revenue_percent' => $this->cleanNumber((float) ($this->rowValue($row, 'revenue_percent') ?? 0)),
            'display_text' => (string) ($this->rowValue($row, 'display_text') ?? ''),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay dich vu co doanh thu thap nhat nhung van co doanh thu trong ky loc.
     *
     * Input:
     * - array $filters gom start_date, end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang KPI rut gon gom service_id, service_name, revenue, revenue_share hoac null.
     */
    public function getLowestRevenueService(array $filters = []): ?array
    {
        $service = collect($this->getServiceRevenueList($filters))
            ->filter(fn (array $service): bool => (float) $service['service_revenue'] > 0)
            ->sortBy('service_revenue')
            ->values()
            ->first();

        return $service ? $this->kpiPayload($service) : null;
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach dich vu khong co doanh thu da thanh toan trong ky loc.
     *
     * Input:
     * - array $filters gom start_date, end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang dong table gom service_id, service_name, service_category_name,
     *   base_price, is_active, booking_count_in_period va no_revenue_reason.
     *
     * Ghi chu:
     * - Dich vu co booking nhung chua co doanh thu da thanh toan van duoc xem
     *   la khong phat sinh doanh thu theo goc nhin CEO.
     */
    public function getNoActivityServices(array $filters = []): array
    {
        [$startDate, $endDate] = $this->dateRange($filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS p_start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS p_end_date
                FROM dual
            ),
            paid_service_lines AS (
                SELECT
                    o.order_id,
                    od.order_detail_id,
                    bsp.booking_service_pet_id,
                    bsp.service_id,
                    s.service_name,
                    od.line_total,
                    NVL(CAST(o.paid_at AS DATE), CAST(o.created_at AS DATE)) AS report_date
                FROM orders o
                JOIN order_details od
                    ON od.order_id = o.order_id
                JOIN booking_service_pet bsp
                    ON bsp.booking_service_pet_id = od.booking_service_pet_id
                JOIN services s
                    ON s.service_id = bsp.service_id
                CROSS JOIN params prm
                WHERE od.booking_service_pet_id IS NOT NULL
                  AND o.status IN ('PAID', 'COMPLETED', 'DONE')
                  AND NVL(CAST(o.paid_at AS DATE), CAST(o.created_at AS DATE)) >= prm.p_start_date
                  AND NVL(CAST(o.paid_at AS DATE), CAST(o.created_at AS DATE)) <  prm.p_end_date + 1
            ),
            service_revenue AS (
                SELECT
                    service_id,
                    SUM(NVL(line_total, 0)) AS service_revenue
                FROM paid_service_lines
                GROUP BY
                    service_id
            ),
            booking_activity AS (
                SELECT
                    bsp.service_id,
                    COUNT(DISTINCT bsp.booking_service_pet_id) AS booking_count_in_period
                FROM booking_service_pet bsp
                CROSS JOIN params prm
                WHERE CAST(bsp.scheduled_at AS DATE) >= prm.p_start_date
                  AND CAST(bsp.scheduled_at AS DATE) <  prm.p_end_date + 1
                GROUP BY
                    bsp.service_id
            )
            SELECT
                s.service_id,
                s.service_name,
                cs.service_category_name,
                s.base_price,
                s.is_active,

                NVL(ba.booking_count_in_period, 0) AS booking_count_in_period,

                CASE
                    WHEN NVL(ba.booking_count_in_period, 0) = 0 THEN
                        'Không có booking và không có doanh thu trong kỳ'
                    ELSE
                        'Có booking nhưng chưa có doanh thu đã thanh toán trong kỳ'
                END AS no_revenue_reason
            FROM services s
            LEFT JOIN category_services cs
                ON cs.service_category_id = s.service_category_id
            LEFT JOIN booking_activity ba
                ON ba.service_id = s.service_id
            WHERE NOT EXISTS (
                SELECT 1
                FROM service_revenue sr
                WHERE sr.service_id = s.service_id
            )
            ORDER BY
                s.service_name
            SQL;

        return array_map(function (object $row): array {
            return [
                'service_id' => (int) ($this->rowValue($row, 'service_id') ?? 0),
                'service_name' => (string) ($this->rowValue($row, 'service_name') ?? ''),
                'service_category_name' => (string) ($this->rowValue($row, 'service_category_name') ?? ''),
                'base_price' => $this->cleanNumber((float) ($this->rowValue($row, 'base_price') ?? 0)),
                'is_active' => (int) ($this->rowValue($row, 'is_active') ?? 0),
                'booking_count_in_period' => (int) ($this->rowValue($row, 'booking_count_in_period') ?? 0),
                'no_revenue_reason' => (string) ($this->rowValue($row, 'no_revenue_reason') ?? ''),
            ];
        }, DB::select($sql, [
            'p_start_date' => $startDate,
            'p_end_date' => $endDate,
        ]));
    }

    /**
     * Mo ta chuc nang:
     * Lay dich vu sieu loi nhuan toan chuoi theo doanh thu da thanh toan trong ky loc.
     *
     * Input:
     * - array $filters gom start_date, end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mot dong table gom service_id, service_name, total_service_revenue,
     *   total_material_cost, total_labor_cost, estimated_profit,
     *   estimated_margin_percent, missing_employee_count, cost_warning va display_text.
     *
     * Ghi chu:
     * - Neu thieu employee_id, labor_cost bang 0 va cost_warning se canh bao
     *   so luot dich vu thieu nhan vien phu trach.
     */
    public function getMostProfitableService(array $filters = []): ?array
    {
        [$startDate, $endDate] = $this->dateRange($filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS p_start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS p_end_date
                FROM dual
            ),
            paid_service_lines AS (
                SELECT
                    o.order_id,
                    od.order_detail_id,
                    bsp.booking_service_pet_id,
                    bsp.service_id,
                    s.service_name,
                    bsp.employee_id,
                    bsp.status AS booking_service_status,
                    od.line_total,
                    NVL(CAST(o.paid_at AS DATE), CAST(o.created_at AS DATE)) AS report_date
                FROM orders o
                JOIN order_details od
                    ON od.order_id = o.order_id
                JOIN booking_service_pet bsp
                    ON bsp.booking_service_pet_id = od.booking_service_pet_id
                JOIN services s
                    ON s.service_id = bsp.service_id
                CROSS JOIN params prm
                WHERE od.booking_service_pet_id IS NOT NULL
                  AND o.status IN ('PAID', 'COMPLETED', 'DONE')
                  AND NVL(CAST(o.paid_at AS DATE), CAST(o.created_at AS DATE)) >= prm.p_start_date
                  AND NVL(CAST(o.paid_at AS DATE), CAST(o.created_at AS DATE)) <  prm.p_end_date + 1
            ),
            paid_done_service_instances AS (
                SELECT
                    booking_service_pet_id,
                    service_id,
                    service_name,
                    employee_id,
                    SUM(NVL(line_total, 0)) AS instance_revenue
                FROM paid_service_lines
                WHERE booking_service_status = 'DONE'
                GROUP BY
                    booking_service_pet_id,
                    service_id,
                    service_name,
                    employee_id
            ),
            material_cost_per_service AS (
                SELECT
                    spd.service_id,
                    SUM(NVL(spd.amount, 0) * NVL(p.item_price, 0)) AS material_cost_per_time
                FROM service_product_detail spd
                JOIN product p
                    ON p.product_id = spd.product_id
                GROUP BY
                    spd.service_id
            ),
            service_instance_cost AS (
                SELECT
                    pdsi.booking_service_pet_id,
                    pdsi.service_id,
                    pdsi.service_name,
                    pdsi.instance_revenue,

                    NVL(mc.material_cost_per_time, 0) AS material_cost,

                    CASE
                        WHEN pdsi.employee_id IS NULL THEN 0
                        ELSE
                            (NVL(e.salary, 0) / 26 / 8)
                            * (NVL(s.duration_minutes, 0) / 60)
                    END AS labor_cost,

                    CASE
                        WHEN pdsi.employee_id IS NULL THEN 1
                        ELSE 0
                    END AS missing_employee_flag
                FROM paid_done_service_instances pdsi
                JOIN services s
                    ON s.service_id = pdsi.service_id
                LEFT JOIN employee e
                    ON e.employee_id = pdsi.employee_id
                LEFT JOIN material_cost_per_service mc
                    ON mc.service_id = pdsi.service_id
            ),
            profit_by_service AS (
                SELECT
                    service_id,
                    service_name,

                    SUM(instance_revenue) AS total_service_revenue,

                    SUM(material_cost) AS total_material_cost,

                    SUM(labor_cost) AS total_labor_cost,

                    SUM(instance_revenue)
                    - SUM(material_cost)
                    - SUM(labor_cost) AS estimated_profit,

                    ROUND(
                        (
                            SUM(instance_revenue)
                            - SUM(material_cost)
                            - SUM(labor_cost)
                        ) / NULLIF(SUM(instance_revenue), 0) * 100,
                        2
                    ) AS estimated_margin_percent,

                    SUM(missing_employee_flag) AS missing_employee_count,

                    CASE
                        WHEN SUM(missing_employee_flag) > 0 THEN
                            'Thiếu nhân viên phụ trách ở '
                            || SUM(missing_employee_flag)
                            || ' lượt dịch vụ, chi phí nhân công có thể bị thấp hơn thực tế'
                        ELSE
                            NULL
                    END AS cost_warning
                FROM service_instance_cost
                GROUP BY
                    service_id,
                    service_name
            ),
            ranked_profit AS (
                SELECT
                    ROW_NUMBER() OVER (
                        ORDER BY estimated_margin_percent DESC, estimated_profit DESC, service_id
                    ) AS rank_no,

                    service_id,
                    service_name,
                    total_service_revenue,
                    total_material_cost,
                    total_labor_cost,
                    estimated_profit,
                    estimated_margin_percent,
                    missing_employee_count,
                    cost_warning
                FROM profit_by_service
                WHERE total_service_revenue > 0
            )
            SELECT
                service_id,
                service_name,
                total_service_revenue,
                total_material_cost,
                total_labor_cost,
                estimated_profit,
                estimated_margin_percent,
                missing_employee_count,
                cost_warning,

                service_name
                    || ' - margin tạm tính '
                    || estimated_margin_percent
                    || '%' AS display_text
            FROM ranked_profit
            WHERE rank_no = 1
            SQL;

        $row = DB::selectOne($sql, [
            'p_start_date' => $startDate,
            'p_end_date' => $endDate,
        ]);

        if ($row === null) {
            return null;
        }

        return [
            'service_id' => (int) ($this->rowValue($row, 'service_id') ?? 0),
            'service_name' => (string) ($this->rowValue($row, 'service_name') ?? ''),
            'total_service_revenue' => $this->cleanNumber((float) ($this->rowValue($row, 'total_service_revenue') ?? 0)),
            'total_material_cost' => $this->cleanNumber((float) ($this->rowValue($row, 'total_material_cost') ?? 0)),
            'total_labor_cost' => $this->cleanNumber((float) ($this->rowValue($row, 'total_labor_cost') ?? 0)),
            'estimated_profit' => $this->cleanNumber((float) ($this->rowValue($row, 'estimated_profit') ?? 0)),
            'estimated_margin_percent' => $this->cleanNumber((float) ($this->rowValue($row, 'estimated_margin_percent') ?? 0)),
            'missing_employee_count' => (int) ($this->rowValue($row, 'missing_employee_count') ?? 0),
            'cost_warning' => $this->rowValue($row, 'cost_warning'),
            'display_text' => (string) ($this->rowValue($row, 'display_text') ?? ''),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay bang doanh thu tung dich vu toan chuoi theo ky loc hien tai.
     *
     * Input:
     * - array $filters gom start_date, end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang xep hang doanh thu gom rank_no, service_id, service_name,
     *   service_revenue, total_service_revenue, revenue_percent, order_count
     *   va service_usage_count.
     *
     * Ghi chu:
     * - Dung paid_service_lines lam nguon doanh thu chuan de dong nhat voi KPI.
     */
    public function getServiceRevenueList(array $filters = []): array
    {
        [$startDate, $endDate] = $this->dateRange($filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS p_start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS p_end_date
                FROM dual
            ),
            paid_service_lines AS (
                SELECT
                    o.order_id,
                    od.order_detail_id,
                    bsp.booking_service_pet_id,
                    bsp.service_id,
                    s.service_name,
                    od.line_total,
                    NVL(CAST(o.paid_at AS DATE), CAST(o.created_at AS DATE)) AS report_date
                FROM orders o
                JOIN order_details od
                    ON od.order_id = o.order_id
                JOIN booking_service_pet bsp
                    ON bsp.booking_service_pet_id = od.booking_service_pet_id
                JOIN services s
                    ON s.service_id = bsp.service_id
                CROSS JOIN params prm
                WHERE od.booking_service_pet_id IS NOT NULL
                  AND o.status IN ('PAID', 'COMPLETED', 'DONE')
                  AND NVL(CAST(o.paid_at AS DATE), CAST(o.created_at AS DATE)) >= prm.p_start_date
                  AND NVL(CAST(o.paid_at AS DATE), CAST(o.created_at AS DATE)) <  prm.p_end_date + 1
            ),
            service_revenue AS (
                SELECT
                    service_id,
                    service_name,
                    SUM(NVL(line_total, 0)) AS service_revenue,
                    COUNT(DISTINCT order_id) AS order_count,
                    COUNT(DISTINCT booking_service_pet_id) AS service_usage_count
                FROM paid_service_lines
                GROUP BY
                    service_id,
                    service_name
            ),
            ranked_service AS (
                SELECT
                    ROW_NUMBER() OVER (
                        ORDER BY service_revenue DESC, service_id
                    ) AS rank_no,
                    service_id,
                    service_name,
                    service_revenue,
                    SUM(service_revenue) OVER () AS total_service_revenue,
                    order_count,
                    service_usage_count
                FROM service_revenue
            )
            SELECT
                rank_no,
                service_id,
                service_name,
                service_revenue,
                total_service_revenue,

                ROUND(
                    service_revenue / NULLIF(total_service_revenue, 0) * 100,
                    2
                ) AS revenue_percent,

                order_count,
                service_usage_count
            FROM ranked_service
            ORDER BY
                rank_no
            SQL;

        return array_map(function (object $row): array {
            return [
                'rank_no' => (int) ($this->rowValue($row, 'rank_no') ?? 0),
                'service_id' => (int) ($this->rowValue($row, 'service_id') ?? 0),
                'service_name' => (string) ($this->rowValue($row, 'service_name') ?? ''),
                'service_revenue' => $this->cleanNumber((float) ($this->rowValue($row, 'service_revenue') ?? 0)),
                'total_service_revenue' => $this->cleanNumber((float) ($this->rowValue($row, 'total_service_revenue') ?? 0)),
                'revenue_percent' => $this->cleanNumber((float) ($this->rowValue($row, 'revenue_percent') ?? 0)),
                'order_count' => (int) ($this->rowValue($row, 'order_count') ?? 0),
                'service_usage_count' => (int) ($this->rowValue($row, 'service_usage_count') ?? 0),
            ];
        }, DB::select($sql, [
            'p_start_date' => $startDate,
            'p_end_date' => $endDate,
        ]));
    }

    public function resolvePeriodRange(
        ?string $periodType,
        ?string $date = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        // TODO: Resolve period_type/date/start_date/end_date before applying period-aware service revenue metrics.
        return [];
    }

    /**
     * Mo ta chuc nang:
     * Chuyen mot dong doanh thu dich vu sang payload KPI rut gon.
     *
     * Input:
     * - array $service gom service_id, service_name, service_revenue, revenue_percent.
     *
     * Output:
     * - Mang KPI gom service_id, service_name, revenue, revenue_share.
     */
    private function kpiPayload(array $service): array
    {
        return [
            'service_id' => $service['service_id'],
            'service_name' => $service['service_name'],
            'revenue' => $service['service_revenue'],
            'revenue_share' => $service['revenue_percent'],
        ];
    }

    private function revenueByService(bool $usePayments, array $filters = []): Collection
    {
        return $this->serviceTransactionQuery($usePayments, $filters)
            ->select([
                'booking_service_pet.service_id',
                DB::raw('SUM(order_details.line_total) AS revenue'),
            ])
            ->groupBy('booking_service_pet.service_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (string) $this->rowValue($row, 'service_id') => (float) $this->rowValue($row, 'revenue'),
            ]);
    }

    private function servedCountByServiceLast30Days(bool $usePayments, array $filters = []): Collection
    {
        $query = $this->serviceTransactionQuery($usePayments, $filters);

        if (empty($filters['start_date']) && empty($filters['end_date'])) {
            $query->where('booking_service_pet.scheduled_at', '>=', now()->subDays(30));
        }

        return $query
            ->select([
                'booking_service_pet.service_id',
                DB::raw('SUM(order_details.quantity) AS served_count'),
            ])
            ->groupBy('booking_service_pet.service_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (string) $this->rowValue($row, 'service_id') => (int) $this->rowValue($row, 'served_count'),
            ]);
    }

    private function coverageBranchCountByService(bool $usePayments, array $filters = []): Collection
    {
        return $this->serviceTransactionQuery($usePayments, $filters)
            ->join('branch', 'orders.branch_id', '=', 'branch.branch_id')
            ->where('branch.is_active', self::ACTIVE_VALUE)
            ->select([
                'booking_service_pet.service_id',
                DB::raw('COUNT(DISTINCT orders.branch_id) AS branch_count'),
            ])
            ->groupBy('booking_service_pet.service_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (string) $this->rowValue($row, 'service_id') => (int) $this->rowValue($row, 'branch_count'),
            ]);
    }

    private function serviceTransactionQuery(bool $usePayments, array $filters = []): Builder
    {
        $query = DB::table('order_details')
            ->join('orders', 'order_details.order_id', '=', 'orders.order_id')
            ->join(
                'booking_service_pet',
                'order_details.booking_service_pet_id',
                '=',
                'booking_service_pet.booking_service_pet_id'
            )
            ->join('booking', 'booking_service_pet.booking_id', '=', 'booking.booking_id')
            ->whereNotNull('order_details.booking_service_pet_id')
            ->whereIn('orders.status', self::PAID_ORDER_STATUSES)
            ->whereNotNull('orders.paid_at')
            ->where('booking.status', '<>', 'CANCELLED')
            ->whereIn('booking_service_pet.status', self::SERVED_SERVICE_STATUSES);

        if ($usePayments) {
            // Payments confirm the order is paid; service revenue comes from order_details because payments are order-level.
            $query->join('payments', 'orders.order_id', '=', 'payments.order_id')
                ->whereIn('payments.status', self::PAID_PAYMENT_STATUSES)
                ->whereNotNull('payments.paid_at');
        }

        $this->applyDateFilters($query, $usePayments ? 'payments.paid_at' : 'orders.paid_at', $filters);

        return $query;
    }

    private function hasSuccessfulPayments(): bool
    {
        try {
            return Schema::hasTable('payments')
                && DB::table('payments')->whereIn('status', self::PAID_PAYMENT_STATUSES)->exists();
        } catch (Throwable) {
            return false;
        }
    }

    private function activeBranchCount(): int
    {
        return (int) Branch::query()
            ->where('is_active', self::ACTIVE_VALUE)
            ->count();
    }

    private function applyFilters(Collection $services, array $filters): Collection
    {
        return $services
            ->when(filled($filters['status'] ?? null), function (Collection $items) use ($filters): Collection {
                $status = $this->normalizeStatus((string) $filters['status']);

                return $items->filter(fn (array $service): bool => $service['status'] === $status);
            })
            ->when(is_numeric($filters['min_revenue'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $service): bool => (float) $service['revenue'] >= (float) $filters['min_revenue']);
            })
            ->when(is_numeric($filters['max_revenue'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $service): bool => (float) $service['revenue'] <= (float) $filters['max_revenue']);
            })
            ->when(is_numeric($filters['min_coverage_rate'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $service): bool => (float) $service['coverage_rate'] >= (float) $filters['min_coverage_rate']);
            })
            ->when(is_numeric($filters['max_coverage_rate'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $service): bool => (float) $service['coverage_rate'] <= (float) $filters['max_coverage_rate']);
            })
            ->when(is_numeric($filters['min_served_count'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $service): bool => (int) $service['served_count_30_days'] >= (int) $filters['min_served_count']);
            })
            ->when(is_numeric($filters['max_served_count'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $service): bool => (int) $service['served_count_30_days'] <= (int) $filters['max_served_count']);
            });
    }

    private function sortServices(Collection $services, array $filters): Collection
    {
        $sortBy = (string) ($filters['sort_by'] ?? 'revenue');

        if (! in_array($sortBy, self::SORTABLE_COLUMNS, true)) {
            $sortBy = 'revenue';
        }

        $direction = Str::lower((string) ($filters['sort_direction'] ?? 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        return $direction === 'asc'
            ? $services->sortBy($sortBy)
            : $services->sortByDesc($sortBy);
    }

    private function revenueShare(float $serviceRevenue, float $totalRevenue): int|float
    {
        if ($totalRevenue <= 0) {
            return 0;
        }

        // revenue_share = service revenue / total valid service revenue * 100.
        return $this->cleanNumber(($serviceRevenue / $totalRevenue) * 100);
    }

    private function coverageRate(int $branchCount, int $activeBranchCount): int|float
    {
        if ($activeBranchCount <= 0) {
            return 0;
        }

        // coverage_rate = active branches with this service / total active branches * 100.
        return $this->cleanNumber(($branchCount / $activeBranchCount) * 100);
    }

    private function normalizeStatus(string $status): string
    {
        return match ($this->searchable($status)) {
            '1', 'true', 'active', 'enabled', 'dang hoat dong', 'hoat dong' => 'active',
            '0', 'false', 'inactive', 'disabled', 'ngung hoat dong', 'tam ngung' => 'inactive',
            default => $this->searchable($status),
        };
    }

    private function searchable(?string $value): string
    {
        return Str::lower(Str::ascii($value ?? ''));
    }

    private function cleanNumber(float $value): int|float
    {
        $rounded = round($value, 2);

        return abs($rounded - round($rounded)) < 0.00001
            ? (int) round($rounded)
            : $rounded;
    }

    private function mapTopService(?object $row): ?array
    {
        $serviceId = $this->rowValue($row, 'top_service_id');

        if ($serviceId === null) {
            return null;
        }

        return [
            'service_id' => (int) $serviceId,
            'service_name' => (string) ($this->rowValue($row, 'top_service_name') ?? ''),
            'revenue' => (float) ($this->rowValue($row, 'top_service_revenue') ?? 0),
            'total_service_revenue' => (float) ($this->rowValue($row, 'total_service_revenue') ?? 0),
            'revenue_share' => (float) ($this->rowValue($row, 'top_service_revenue_share') ?? 0),
        ];
    }

    private function calculateChange(float|int $current, float|int $comparison): array
    {
        $difference = $current - $comparison;

        return [
            'value' => $this->cleanNumber((float) $difference),
            'percent' => $comparison == 0
                ? null
                : round(($difference / $comparison) * 100, 2),
            'trend' => match (true) {
                $difference > 0 => 'up',
                $difference < 0 => 'down',
                default => 'equal',
            },
        ];
    }

    private function dateRange(array $filters): array
    {
        return [
            $this->dateValue($filters['start_date'] ?? null, '1900-01-01'),
            $this->dateValue($filters['end_date'] ?? null, CarbonImmutable::today(config('app.timezone'))->toDateString()),
        ];
    }

    private function rowValue(?object $row, string $key): mixed
    {
        if ($row === null) {
            return null;
        }

        return $row->{$key} ?? $row->{strtoupper($key)} ?? null;
    }

    private function applyDateFilters(Builder $query, string $column, array $filters): void
    {
        if (! empty($filters['start_date'])) {
            $query->where($column, '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where($column, '<=', $filters['end_date']);
        }
    }

    private function dateValue(mixed $value, string $fallback): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (filled($value)) {
            return substr((string) $value, 0, 10);
        }

        return $fallback;
    }
}
