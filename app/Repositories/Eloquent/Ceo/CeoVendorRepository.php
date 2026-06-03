<?php

namespace App\Repositories\Eloquent\Ceo;

use App\Repositories\Contracts\Ceo\CeoVendorRepositoryInterface;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class CeoVendorRepository implements CeoVendorRepositoryInterface
{
    /**
     * Mo ta chuc nang:
     * Lay danh sach va phan hang doi tac/nha cung cap gia lap theo nhom vat tu.
     *
     * Input:
     * - array $filters: filter ngay tu DateRangeFilterRequest, duoc normalize de tranh truyen Carbon vao SQL.
     * - float $platinumThreshold, $goldThreshold, $silverThreshold: nguong phan hang hieu suat.
     * - float $outOfStockPenalty, $lowStockPenalty: diem phat khi vat tu het/sap het.
     *
     * Output:
     * - Mang row table gom ten doi tac, so mat hang, tong chi tieu, hang, diem va canh bao.
     */
    public function getVendors(
        array $filters = [],
        float $platinumThreshold = 95.0,
        float $goldThreshold = 90.0,
        float $silverThreshold = 75.0,
        float $outOfStockPenalty = 18.0,
        float $lowStockPenalty = 8.0
    ): array
    {
        $filters = $this->normalizeFilters($filters);

        $sql = "
            WITH report_params AS (
                SELECT
                    :p_platinum_threshold AS platinum_threshold,
                    :p_gold_threshold AS gold_threshold,
                    :p_silver_threshold AS silver_threshold,
                    :p_out_of_stock_penalty AS out_of_stock_penalty,
                    :p_low_stock_penalty AS low_stock_penalty
                FROM dual
            ),
            partner_stats AS (
                SELECT
                    cp.product_category_id,
                    cp.product_category_name,
                    COUNT(DISTINCT p.product_id) AS item_count,
                    NVL(SUM(NVL(bi.quantity_in_stock, 0) * NVL(p.item_price, 0)), 0) AS total_spend_amount,
                    SUM(CASE WHEN NVL(bi.quantity_in_stock, 0) = 0 THEN 1 ELSE 0 END) AS out_of_stock_item_count,
                    SUM(CASE
                            WHEN NVL(bi.quantity_in_stock, 0) > 0
                             AND NVL(bi.quantity_in_stock, 0) <= NVL(bi.reorder_point, 0)
                            THEN 1 ELSE 0
                        END) AS low_stock_item_count
                FROM category_product cp
                JOIN product p
                    ON p.product_category_id = cp.product_category_id
                LEFT JOIN branch_inventory bi
                    ON bi.product_id = p.product_id
                WHERE cp.is_active = 1
                  AND p.is_active = 1
                GROUP BY
                    cp.product_category_id,
                    cp.product_category_name
            ),
            ranked_partners AS (
                SELECT
                    ps.product_category_id,
                    ps.product_category_name,
                    ps.item_count,
                    ps.total_spend_amount,
                    ps.out_of_stock_item_count,
                    ps.low_stock_item_count,
                    rp.platinum_threshold,
                    rp.gold_threshold,
                    rp.silver_threshold,
                    GREATEST(
                        0,
                        CASE
                            WHEN ps.item_count = 0 THEN 0
                            ELSE ROUND(
                                100
                                - (ps.out_of_stock_item_count * rp.out_of_stock_penalty)
                                - (ps.low_stock_item_count * rp.low_stock_penalty),
                                1
                            )
                        END
                    ) AS performance_rate
                FROM partner_stats ps
                CROSS JOIN report_params rp
            )
            SELECT
                product_category_id AS partner_group_id,
                product_category_name AS partner_name,
                item_count,
                total_spend_amount,
                out_of_stock_item_count,
                low_stock_item_count,
                performance_rate,
                ROUND(performance_rate / 20, 1) AS performance_score,
                CASE
                    WHEN performance_rate >= platinum_threshold THEN 'PLATINUM'
                    WHEN performance_rate >= gold_threshold THEN 'GOLD'
                    WHEN performance_rate >= silver_threshold THEN 'SILVER'
                    ELSE 'RISK'
                END AS tier,
                CASE
                    WHEN performance_rate >= platinum_threshold THEN 'Khong canh bao - doi tac rat on dinh'
                    WHEN performance_rate >= gold_threshold THEN 'Theo doi nhe - hieu suat tot'
                    WHEN performance_rate >= silver_threshold THEN 'Canh bao vang - can theo doi ton kho/giao hang'
                    ELSE 'Canh bao do - hieu suat thap, can kiem tra nha cung cap'
                END AS warning_text
            FROM ranked_partners
            ORDER BY performance_rate DESC, total_spend_amount DESC, partner_group_id
        ";

        $rows = DB::select($sql, [
            'p_platinum_threshold' => $platinumThreshold,
            'p_gold_threshold' => $goldThreshold,
            'p_silver_threshold' => $silverThreshold,
            'p_out_of_stock_penalty' => $outOfStockPenalty,
            'p_low_stock_penalty' => $lowStockPenalty,
        ]);

        return array_map(function (object $row): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'partner_group_id' => (int) ($data['partner_group_id'] ?? 0),
                'partner_name' => (string) ($data['partner_name'] ?? ''),
                'item_count' => (int) ($data['item_count'] ?? 0),
                'total_spend_amount' => $this->cleanNumber((float) ($data['total_spend_amount'] ?? 0)),
                'out_of_stock_item_count' => (int) ($data['out_of_stock_item_count'] ?? 0),
                'low_stock_item_count' => (int) ($data['low_stock_item_count'] ?? 0),
                'performance_score' => $this->cleanNumber((float) ($data['performance_score'] ?? 0)),
                'performance_rate' => $this->cleanNumber((float) ($data['performance_rate'] ?? 0)),
                'tier' => (string) ($data['tier'] ?? 'RISK'),
                'warning_text' => (string) ($data['warning_text'] ?? ''),
            ];
        }, $rows);
    }

    /**
     * Mo ta chuc nang:
     * Lay hieu suat giao hang dung han OTD bang CTE mo phong khi schema chua co bang giao nhan.
     *
     * Input:
     * - array $filters: filter ngay tu DateRangeFilterRequest, duoc normalize de tranh truyen Carbon vao SQL.
     * - Cac nguong OTD, chat luong va thoi gian tre truyen tu VendorController.
     *
     * Output:
     * - summary: so lieu tong hop cho gauge/chart OTD.
     * - vendors: danh sach nha cung cap va canh bao OTD chi tiet.
     */
    public function getOnTimeDeliveryPerformance(
        array $filters = [],
        float $excellentOtdThreshold = 95.0,
        float $goodOtdThreshold = 90.0,
        float $warningOtdThreshold = 80.0,
        float $lowQualityThreshold = 3.5,
        float $mediumQualityThreshold = 4.0,
        float $severeDelayHours = 24.0,
        float $warningDelayHours = 12.0,
        float $watchDelayHours = 3.0
    ): array
    {
        $filters = $this->normalizeFilters($filters);

        $sql = "
            WITH report_params AS (
                SELECT
                    :p_excellent_otd_threshold AS excellent_otd_threshold,
                    :p_good_otd_threshold AS good_otd_threshold,
                    :p_warning_otd_threshold AS warning_otd_threshold,
                    :p_low_quality_threshold AS low_quality_threshold,
                    :p_medium_quality_threshold AS medium_quality_threshold,
                    :p_severe_delay_hours AS severe_delay_hours,
                    :p_warning_delay_hours AS warning_delay_hours,
                    :p_watch_delay_hours AS watch_delay_hours
                FROM dual
            ),
            mock_delivery_orders AS (
                SELECT 'Pet Food Solution Co.' AS supplier_name, 50 AS total_delivery_order_count, 49 AS on_time_delivery_order_count, 4.8 AS quality_score, 2 AS average_delay_hours FROM dual
                UNION ALL
                SELECT 'Duoc pham Thu y A Au', 40, 37, 4.5, 5 FROM dual
                UNION ALL
                SELECT 'Xuong Nem Happy Pet', 25, 21, 3.9, 12 FROM dual
                UNION ALL
                SELECT 'Dai ly Cat Sai Gon', 20, 14, 3.2, 26 FROM dual
            ),
            otd_calculated AS (
                SELECT
                    supplier_name,
                    total_delivery_order_count,
                    on_time_delivery_order_count,
                    ROUND(
                        on_time_delivery_order_count
                        / NULLIF(total_delivery_order_count, 0) * 100,
                        1
                    ) AS on_time_delivery_rate,
                    quality_score,
                    average_delay_hours
                FROM mock_delivery_orders
            ),
            classified AS (
                SELECT
                    oc.supplier_name,
                    oc.total_delivery_order_count,
                    oc.on_time_delivery_order_count,
                    oc.on_time_delivery_rate,
                    oc.quality_score,
                    oc.average_delay_hours,
                    CASE
                        WHEN oc.on_time_delivery_rate >= rp.excellent_otd_threshold THEN 'Tot - khong canh bao'
                        WHEN oc.on_time_delivery_rate >= rp.good_otd_threshold THEN 'Theo doi nhe'
                        WHEN oc.on_time_delivery_rate >= rp.warning_otd_threshold THEN 'Canh bao vang - ty le dung han duoi 90%'
                        ELSE 'Canh bao do - duoi 80%, can dam phan lai'
                    END AS otd_warning_text,
                    CASE
                        WHEN oc.quality_score < rp.low_quality_threshold THEN 'Canh bao do - chat luong hang kem'
                        WHEN oc.quality_score < rp.medium_quality_threshold THEN 'Canh bao vang - chat luong trung binh'
                        ELSE 'Chat luong dat'
                    END AS quality_warning_text,
                    CASE
                        WHEN oc.average_delay_hours > rp.severe_delay_hours THEN 'Canh bao do - tre nghiem trong'
                        WHEN oc.average_delay_hours > rp.warning_delay_hours THEN 'Canh bao vang - tre dang ke'
                        WHEN oc.average_delay_hours > rp.watch_delay_hours THEN 'Theo doi tre nhe'
                        ELSE 'Giao dung tien do'
                    END AS delay_warning_text
                FROM otd_calculated oc
                CROSS JOIN report_params rp
            )
            SELECT
                supplier_name,
                total_delivery_order_count,
                on_time_delivery_order_count,
                on_time_delivery_rate,
                quality_score,
                average_delay_hours,
                otd_warning_text,
                quality_warning_text,
                delay_warning_text
            FROM classified
            ORDER BY on_time_delivery_rate ASC, supplier_name
        ";

        $rows = DB::select($sql, [
            'p_excellent_otd_threshold' => $excellentOtdThreshold,
            'p_good_otd_threshold' => $goodOtdThreshold,
            'p_warning_otd_threshold' => $warningOtdThreshold,
            'p_low_quality_threshold' => $lowQualityThreshold,
            'p_medium_quality_threshold' => $mediumQualityThreshold,
            'p_severe_delay_hours' => $severeDelayHours,
            'p_warning_delay_hours' => $warningDelayHours,
            'p_watch_delay_hours' => $watchDelayHours,
        ]);

        $vendors = array_map(function (object $row): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'supplier_name' => (string) ($data['supplier_name'] ?? ''),
                'total_delivery_order_count' => (int) ($data['total_delivery_order_count'] ?? 0),
                'on_time_delivery_order_count' => (int) ($data['on_time_delivery_order_count'] ?? 0),
                'on_time_delivery_rate' => $this->cleanNumber((float) ($data['on_time_delivery_rate'] ?? 0)),
                'quality_score' => $this->cleanNumber((float) ($data['quality_score'] ?? 0)),
                'average_delay_hours' => $this->cleanNumber((float) ($data['average_delay_hours'] ?? 0)),
                'otd_warning_text' => (string) ($data['otd_warning_text'] ?? ''),
                'quality_warning_text' => (string) ($data['quality_warning_text'] ?? ''),
                'delay_warning_text' => (string) ($data['delay_warning_text'] ?? ''),
            ];
        }, $rows);

        return [
            'summary' => $this->summarizeOnTimeDelivery(
                $vendors,
                $excellentOtdThreshold,
                $goodOtdThreshold,
                $warningOtdThreshold
            ),
            'vendors' => $vendors,
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay tong hop OTD toan chuoi bang CTE mo phong khi schema chua co bang giao nhan.
     *
     * Input:
     * - array $filters: filter ngay tu DateRangeFilterRequest, duoc normalize de tranh truyen Carbon vao SQL.
     * - float $goodOtdThreshold, $warningOtdThreshold: nguong canh bao OTD toan chuoi.
     *
     * Output:
     * - Mang summary gom ty le dung han toan chuoi, chat luong, gio tre va canh bao tong hop.
     */
    public function getOnTimeDeliverySummary(
        array $filters = [],
        float $goodOtdThreshold = 90.0,
        float $warningOtdThreshold = 80.0
    ): array {
        $filters = $this->normalizeFilters($filters);

        $sql = "
            WITH report_params AS (
                SELECT
                    :p_good_otd_threshold AS good_otd_threshold,
                    :p_warning_otd_threshold AS warning_otd_threshold
                FROM dual
            ),
            mock_delivery_orders AS (
                SELECT 'Pet Food Solution Co.' AS supplier_name, 50 AS total_delivery_order_count, 49 AS on_time_delivery_order_count, 4.8 AS quality_score, 2 AS average_delay_hours FROM dual
                UNION ALL
                SELECT 'Duoc pham Thu y A Au', 40, 37, 4.5, 5 FROM dual
                UNION ALL
                SELECT 'Xuong Nem Happy Pet', 25, 21, 3.9, 12 FROM dual
                UNION ALL
                SELECT 'Dai ly Cat Sai Gon', 20, 14, 3.2, 26 FROM dual
            ),
            otd_calculated AS (
                SELECT
                    supplier_name,
                    total_delivery_order_count,
                    on_time_delivery_order_count,
                    ROUND(
                        on_time_delivery_order_count
                        / NULLIF(total_delivery_order_count, 0) * 100,
                        1
                    ) AS on_time_delivery_rate,
                    quality_score,
                    average_delay_hours
                FROM mock_delivery_orders
            )
            SELECT
                ROUND(
                    SUM(on_time_delivery_order_count)
                    / NULLIF(SUM(total_delivery_order_count), 0) * 100,
                    1
                ) AS chain_on_time_delivery_rate,
                ROUND(AVG(quality_score), 1) AS average_quality_score,
                ROUND(AVG(average_delay_hours), 1) AS average_delay_hours,
                SUM(CASE WHEN on_time_delivery_rate < rp.warning_otd_threshold THEN 1 ELSE 0 END) AS low_otd_vendor_count,
                SUM(total_delivery_order_count) AS total_delivery_order_count,
                SUM(on_time_delivery_order_count) AS on_time_delivery_order_count,
                CASE
                    WHEN SUM(CASE WHEN on_time_delivery_rate < rp.warning_otd_threshold THEN 1 ELSE 0 END) > 0
                    THEN 'danger'
                    WHEN ROUND(
                        SUM(on_time_delivery_order_count)
                        / NULLIF(SUM(total_delivery_order_count), 0) * 100,
                        1
                    ) < rp.good_otd_threshold
                    THEN 'warning'
                    ELSE 'good'
                END AS status,
                CASE
                    WHEN SUM(CASE WHEN on_time_delivery_rate < rp.warning_otd_threshold THEN 1 ELSE 0 END) > 0
                    THEN 'Canh bao do - co doi tac giao dung han duoi 80%, gay rui ro van hanh'
                    WHEN ROUND(
                        SUM(on_time_delivery_order_count)
                        / NULLIF(SUM(total_delivery_order_count), 0) * 100,
                        1
                    ) < rp.good_otd_threshold
                    THEN 'Canh bao vang - OTD toan chuoi duoi 90%'
                    ELSE 'On dinh - hieu suat giao hang tot'
                END AS warning_text
            FROM otd_calculated
            CROSS JOIN report_params rp
            GROUP BY
                rp.good_otd_threshold,
                rp.warning_otd_threshold
        ";

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_good_otd_threshold' => $goodOtdThreshold,
            'p_warning_otd_threshold' => $warningOtdThreshold,
        ]), CASE_LOWER);

        $lowOtdVendorCount = (int) ($row['low_otd_vendor_count'] ?? 0);
        $status = (string) ($row['status'] ?? 'good');

        return [
            'on_time_delivery_rate' => $this->cleanNumber((float) ($row['chain_on_time_delivery_rate'] ?? 0)),
            'quality_score' => $this->cleanNumber((float) ($row['average_quality_score'] ?? 0)),
            'average_delay_hours' => $this->cleanNumber((float) ($row['average_delay_hours'] ?? 0)),
            'low_otd_vendor_count' => $lowOtdVendorCount,
            'total_delivery_order_count' => (int) ($row['total_delivery_order_count'] ?? 0),
            'on_time_delivery_order_count' => (int) ($row['on_time_delivery_order_count'] ?? 0),
            'status' => $status,
            'show_alert' => $status !== 'good',
            'warning_text' => (string) ($row['warning_text'] ?? ''),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay bieu do dong tien va cong no phai tra nha cung cap theo thang bang CTE mo phong.
     *
     * Input:
     * - array $filters: filter ngay tu DateRangeFilterRequest, duoc normalize truoc khi bind SQL.
     * - float $lightGrowthThreshold, $warningGrowthThreshold, $criticalGrowthThreshold: nguong canh bao tang truong cong no.
     *
     * Output:
     * - summary: cong no thang moi nhat, tang truong va canh bao dong tien.
     * - chart: cac dong thang de frontend render bar chart.
     */
    public function getPayableCashflow(
        array $filters = [],
        float $lightGrowthThreshold = 10.0,
        float $warningGrowthThreshold = 20.0,
        float $criticalGrowthThreshold = 35.0
    ): array {
        $filters = $this->normalizeFilters($filters);

        $sql = "
            WITH report_params AS (
                SELECT
                    TO_DATE(:p_start_date, 'YYYY-MM-DD') AS start_date,
                    TO_DATE(:p_end_date, 'YYYY-MM-DD') AS end_date,
                    :p_light_growth_threshold AS light_growth_threshold,
                    :p_warning_growth_threshold AS warning_growth_threshold,
                    :p_critical_growth_threshold AS critical_growth_threshold
                FROM dual
            ),
            mock_payables AS (
                SELECT '2026-01' AS month_key, 320000000 AS total_payable_amount FROM dual
                UNION ALL SELECT '2026-02', 350000000 FROM dual
                UNION ALL SELECT '2026-03', 280000000 FROM dual
                UNION ALL SELECT '2026-04', 305000000 FROM dual
                UNION ALL SELECT '2026-05', 410000000 FROM dual
                UNION ALL SELECT '2026-06', 450500000 FROM dual
            ),
            payable_growth AS (
                SELECT
                    mp.month_key,
                    TO_DATE(mp.month_key || '-01', 'YYYY-MM-DD') AS month_date,
                    mp.total_payable_amount,
                    LAG(mp.total_payable_amount) OVER (ORDER BY mp.month_key) AS previous_payable_amount
                FROM mock_payables mp
            ),
            filtered_growth AS (
                SELECT
                    pg.month_key,
                    pg.month_date,
                    pg.total_payable_amount,
                    pg.previous_payable_amount,
                    ROUND(
                        (pg.total_payable_amount - pg.previous_payable_amount)
                        / NULLIF(pg.previous_payable_amount, 0) * 100,
                        1
                    ) AS growth_percent,
                    rp.light_growth_threshold,
                    rp.warning_growth_threshold,
                    rp.critical_growth_threshold
                FROM payable_growth pg
                CROSS JOIN report_params rp
                WHERE (rp.start_date IS NULL OR pg.month_date >= TRUNC(rp.start_date, 'MM'))
                  AND (rp.end_date IS NULL OR pg.month_date < ADD_MONTHS(TRUNC(rp.end_date, 'MM'), 1))
            )
            SELECT
                month_key,
                total_payable_amount,
                previous_payable_amount,
                growth_percent,
                CASE
                    WHEN previous_payable_amount IS NULL THEN 'no_previous_data'
                    WHEN growth_percent > critical_growth_threshold THEN 'danger'
                    WHEN growth_percent > warning_growth_threshold THEN 'warning'
                    WHEN growth_percent >= light_growth_threshold THEN 'watch'
                    ELSE 'normal'
                END AS status,
                CASE
                    WHEN previous_payable_amount IS NULL THEN 'Khong du du lieu so sanh'
                    WHEN growth_percent > critical_growth_threshold THEN 'Canh bao do - cong no tang dot bien, can chuan bi dong tien'
                    WHEN growth_percent > warning_growth_threshold THEN 'Canh bao vang - cong no tang manh'
                    WHEN growth_percent >= light_growth_threshold THEN 'Canh bao nhe - cong no tang dang chu y'
                    ELSE 'Binh thuong'
                END AS warning_text
            FROM filtered_growth
            ORDER BY month_key
        ";

        $rows = DB::select($sql, [
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
            'p_light_growth_threshold' => $lightGrowthThreshold,
            'p_warning_growth_threshold' => $warningGrowthThreshold,
            'p_critical_growth_threshold' => $criticalGrowthThreshold,
        ]);

        $chart = array_map(function (object $row): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);
            $monthKey = (string) ($data['month_key'] ?? '');

            return [
                'month' => $monthKey,
                'month_label' => $this->monthLabel($monthKey),
                'total_payable_amount' => $this->cleanNumber((float) ($data['total_payable_amount'] ?? 0)),
                'previous_payable_amount' => isset($data['previous_payable_amount'])
                    ? $this->cleanNumber((float) $data['previous_payable_amount'])
                    : null,
                'growth_percent' => isset($data['growth_percent'])
                    ? $this->cleanNumber((float) $data['growth_percent'])
                    : null,
                'status' => (string) ($data['status'] ?? 'normal'),
                'warning_text' => (string) ($data['warning_text'] ?? ''),
            ];
        }, $rows);

        $summary = $chart !== [] ? $chart[array_key_last($chart)] : [
            'month' => null,
            'month_label' => '',
            'total_payable_amount' => 0,
            'previous_payable_amount' => null,
            'growth_percent' => null,
            'status' => 'no_data',
            'warning_text' => 'Khong co du lieu cong no trong ky loc',
        ];

        return [
            'summary' => $summary,
            'chart' => $chart,
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay canh bao bien dong gia nhap bang CTE mo phong khi schema chua co lich su gia nhap.
     *
     * Input:
     * - array $filters: filter ngay tu DateRangeFilterRequest, duoc normalize de tranh truyen Carbon vao SQL.
     * - float $watchThreshold, $warningThreshold, $criticalThreshold: nguong canh bao bien dong gia.
     *
     * Output:
     * - Mang row table gom vat tu, nha cung cap, gia trung binh 3 thang, gia nhap moi, bien dong va thao tac de xuat.
     */
    public function getPriceVarianceAlerts(
        array $filters = [],
        float $watchThreshold = 10.0,
        float $warningThreshold = 20.0,
        float $criticalThreshold = 30.0
    ): array {
        $filters = $this->normalizeFilters($filters);

        $sql = "
            WITH report_params AS (
                SELECT
                    :p_watch_threshold AS watch_threshold,
                    :p_warning_threshold AS warning_threshold,
                    :p_critical_threshold AS critical_threshold
                FROM dual
            ),
            mock_price_variance AS (
                SELECT 'Cat dau nanh huu co CleanCat 10L' AS item_name, 'Dai ly Cat Sai Gon' AS supplier_name, 110000 AS average_price_3_months, 137500 AS latest_import_price FROM dual
                UNION ALL
                SELECT 'Sua tam SOS cho cho long trang 5L', 'Pet Food Solution Co.', 250000, 285000 FROM dual
                UNION ALL
                SELECT 'Khan tam sieu tham hut thu cung', 'Xuong det may An Phu', 35000, 43050 FROM dual
            ),
            calculated_variance AS (
                SELECT
                    item_name,
                    supplier_name,
                    average_price_3_months,
                    latest_import_price,
                    ROUND(
                        (latest_import_price - average_price_3_months)
                        / NULLIF(average_price_3_months, 0) * 100,
                        1
                    ) AS variance_percent
                FROM mock_price_variance
            )
            SELECT
                cv.item_name,
                cv.supplier_name,
                cv.average_price_3_months,
                cv.latest_import_price,
                cv.variance_percent,
                CASE
                    WHEN cv.variance_percent > rp.critical_threshold THEN 'critical'
                    WHEN cv.variance_percent > rp.warning_threshold THEN 'warning'
                    WHEN cv.variance_percent > rp.watch_threshold THEN 'watch'
                    ELSE 'normal'
                END AS status,
                CASE
                    WHEN cv.variance_percent > rp.critical_threshold THEN 'Canh bao do - bien dong rat bat thuong'
                    WHEN cv.variance_percent > rp.warning_threshold THEN 'Canh bao vang - vuot nguong 20%, can dieu tra'
                    WHEN cv.variance_percent > rp.watch_threshold THEN 'Theo doi - gia tang dang chu y'
                    ELSE 'Binh thuong'
                END AS warning_text,
                CASE
                    WHEN cv.variance_percent > rp.warning_threshold THEN 'Dieu tra'
                    ELSE 'Theo doi'
                END AS suggested_action
            FROM calculated_variance cv
            CROSS JOIN report_params rp
            ORDER BY cv.variance_percent DESC, cv.item_name
        ";

        $rows = DB::select($sql, [
            'p_watch_threshold' => $watchThreshold,
            'p_warning_threshold' => $warningThreshold,
            'p_critical_threshold' => $criticalThreshold,
        ]);

        return array_map(function (object $row): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'item_name' => (string) ($data['item_name'] ?? ''),
                'supplier_name' => (string) ($data['supplier_name'] ?? ''),
                'average_price_3_months' => $this->cleanNumber((float) ($data['average_price_3_months'] ?? 0)),
                'latest_import_price' => $this->cleanNumber((float) ($data['latest_import_price'] ?? 0)),
                'variance_percent' => $this->cleanNumber((float) ($data['variance_percent'] ?? 0)),
                'status' => (string) ($data['status'] ?? 'normal'),
                'warning_text' => (string) ($data['warning_text'] ?? ''),
                'suggested_action' => (string) ($data['suggested_action'] ?? 'Theo doi'),
            ];
        }, $rows);
    }

    public function resolvePeriodRange(
        ?string $periodType,
        ?string $date = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        // TODO: Resolve day/month/year or explicit date range before applying period-aware vendor spending metrics.
        return [];
    }

    /**
     * Mo ta chuc nang:
     * Tong hop cac dong OTD nha cung cap thanh so lieu summary cho gauge/chart.
     *
     * Input:
     * - array $vendors: cac dong OTD da map tu SQL.
     * - float $excellentOtdThreshold, $goodOtdThreshold, $warningOtdThreshold: nguong canh bao.
     *
     * Output:
     * - Mang summary gom ty le OTD chuoi, chat luong, gio tre va so doi tac canh bao.
     */
    private function summarizeOnTimeDelivery(
        array $vendors,
        float $excellentOtdThreshold,
        float $goodOtdThreshold,
        float $warningOtdThreshold
    ): array {
        $vendorCount = count($vendors);
        $totalOrders = array_sum(array_column($vendors, 'total_delivery_order_count'));
        $onTimeOrders = array_sum(array_column($vendors, 'on_time_delivery_order_count'));
        $onTimeRate = $totalOrders > 0
            ? $this->cleanNumber(round($onTimeOrders / $totalOrders * 100, 1))
            : 0;
        $qualityScore = $vendorCount > 0
            ? $this->cleanNumber(round(array_sum(array_column($vendors, 'quality_score')) / $vendorCount, 1))
            : 0;
        $averageDelayHours = $vendorCount > 0
            ? $this->cleanNumber(round(array_sum(array_column($vendors, 'average_delay_hours')) / $vendorCount, 1))
            : 0;
        $lowOtdVendorCount = count(array_filter(
            $vendors,
            static fn (array $vendor): bool => (float) ($vendor['on_time_delivery_rate'] ?? 0) < $warningOtdThreshold
        ));
        $watchOtdVendorCount = count(array_filter(
            $vendors,
            static fn (array $vendor): bool => (float) ($vendor['on_time_delivery_rate'] ?? 0) < $goodOtdThreshold
        ));

        return [
            'total_delivery_order_count' => (int) $totalOrders,
            'on_time_delivery_order_count' => (int) $onTimeOrders,
            'on_time_delivery_rate' => $onTimeRate,
            'quality_score' => $qualityScore,
            'average_delay_hours' => $averageDelayHours,
            'low_otd_vendor_count' => $lowOtdVendorCount,
            'watch_otd_vendor_count' => $watchOtdVendorCount,
            'status' => $this->onTimeDeliverySummaryStatus(
                (float) $onTimeRate,
                $lowOtdVendorCount,
                $excellentOtdThreshold,
                $warningOtdThreshold
            ),
            'show_alert' => $lowOtdVendorCount > 0 || (float) $onTimeRate < $goodOtdThreshold,
            'warning_text' => $this->onTimeDeliverySummaryWarning(
                (float) $onTimeRate,
                $lowOtdVendorCount,
                $excellentOtdThreshold,
                $goodOtdThreshold,
                $warningOtdThreshold
            ),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Xac dinh trang thai mau cho gauge OTD dua tren summary da tinh.
     *
     * Input:
     * - float $onTimeRate: ty le giao dung han toan chuoi.
     * - int $lowOtdVendorCount: so nha cung cap duoi nguong do.
     * - float $excellentOtdThreshold, $warningOtdThreshold: nguong trang thai.
     *
     * Output:
     * - Chuoi status gom danger, warning hoac good cho frontend render class.
     */
    private function onTimeDeliverySummaryStatus(
        float $onTimeRate,
        int $lowOtdVendorCount,
        float $excellentOtdThreshold,
        float $warningOtdThreshold
    ): string {
        if ($lowOtdVendorCount > 0 || $onTimeRate < $warningOtdThreshold) {
            return 'danger';
        }

        if ($onTimeRate < $excellentOtdThreshold) {
            return 'warning';
        }

        return 'good';
    }

    /**
     * Mo ta chuc nang:
     * Tao noi dung canh bao tong hop theo nguong OTD.
     *
     * Input:
     * - float $onTimeRate: ty le giao dung han toan chuoi.
     * - int $lowOtdVendorCount: so nha cung cap duoi nguong do.
     * - Cac nguong OTD cau hinh.
     *
     * Output:
     * - Chuoi canh bao de frontend hien thi trong card OTD.
     */
    private function onTimeDeliverySummaryWarning(
        float $onTimeRate,
        int $lowOtdVendorCount,
        float $excellentOtdThreshold,
        float $goodOtdThreshold,
        float $warningOtdThreshold
    ): string {
        if ($lowOtdVendorCount > 0) {
            return 'Co nha cung cap duoi nguong 80%, can dam phan lai de tranh gian doan vat tu.';
        }

        if ($onTimeRate >= $excellentOtdThreshold) {
            return 'OTD rat tot, chua can canh bao.';
        }

        if ($onTimeRate >= $goodOtdThreshold) {
            return 'OTD tot, tiep tuc theo doi nhe.';
        }

        if ($onTimeRate >= $warningOtdThreshold) {
            return 'Canh bao vang: OTD toan chuoi duoi 90%, can theo doi lich giao hang.';
        }

        return 'Canh bao do: OTD toan chuoi duoi 80%, can dam phan lai voi nha cung cap.';
    }

    /**
     * Mo ta chuc nang:
     * Chuyen cac moc ngay trong filter ve chuoi Y-m-d neu co.
     *
     * Input:
     * - array $filters: filter tu DateRangeFilterRequest co the chua Carbon object.
     *
     * Output:
     * - Mang filter da normalize ngay, khong truyen Carbon truc tiep vao SQL.
     */
    private function normalizeFilters(array $filters = []): array
    {
        $filters['start_date'] = $this->dateString($filters['start_date'] ?? null);
        $filters['end_date'] = $this->dateString($filters['end_date'] ?? null);
        $filters['prev_start_date'] = $this->dateString($filters['prev_start_date'] ?? null);
        $filters['prev_end_date'] = $this->dateString($filters['prev_end_date'] ?? null);

        return $filters;
    }

    /**
     * Mo ta chuc nang:
     * Chuyen mot gia tri ngay bat ky ve chuoi Y-m-d.
     *
     * Input:
     * - mixed $value: DateTimeInterface, chuoi ngay hoac null.
     *
     * Output:
     * - Chuoi ngay Y-m-d hoac null neu khong co gia tri hop le.
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
     * Doi chuoi thang YYYY-MM thanh nhan ngan cho bieu do.
     *
     * Input:
     * - string $monthKey: gia tri thang dang YYYY-MM.
     *
     * Output:
     * - Nhan thang dang T1, T2... hoac chuoi goc neu khong hop le.
     */
    private function monthLabel(string $monthKey): string
    {
        if (preg_match('/^\d{4}-(\d{2})$/', $monthKey, $matches) !== 1) {
            return $monthKey;
        }

        return 'T'.(int) $matches[1];
    }

    /**
     * Mo ta chuc nang:
     * Lam gon so thap phan tra ve cho JSON table.
     *
     * Input:
     * - float $value: gia tri can lam gon.
     *
     * Output:
     * - int neu khong co phan thap phan, nguoc lai tra float 2 chu so.
     */
    private function cleanNumber(float $value): int|float
    {
        $rounded = round($value, 2);

        return abs($rounded - round($rounded)) < 0.00001
            ? (int) round($rounded)
            : $rounded;
    }
}
