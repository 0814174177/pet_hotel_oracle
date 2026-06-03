<?php

namespace App\Repositories\Eloquent\Ceo;

use App\Repositories\Contracts\Ceo\CeoVendorRepositoryInterface;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class CeoVendorRepository implements CeoVendorRepositoryInterface
{
    /**
     * Mô tả chức năng:
     * Lấy danh sách và phân hạng đối tác/nhà cung cấp giả lập theo nhóm vật tư.
     *
     * Input:
     * - array $filters: filter ngày từ DateRangeFilterRequest, được normalize để tránh truyền Carbon vào SQL.
     * - float $platinumThreshold, $goldThreshold, $silverThreshold: ngưỡng phân hạng hiệu suất.
     * - float $outOfStockPenalty, $lowStockPenalty: điểm phạt khi vật tư hết/sắp hết.
     *
     * Output:
     * - Mảng row table gồm tên đối tác, số mặt hàng, tổng chi tiêu, hạng, điểm và cảnh báo.
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
                    WHEN performance_rate >= platinum_threshold THEN 'Không cảnh báo - đối tác rất ổn định'
                    WHEN performance_rate >= gold_threshold THEN 'Theo dõi nhẹ - hiệu suất tốt'
                    WHEN performance_rate >= silver_threshold THEN 'Cảnh báo vàng - cần theo dõi tồn kho/giao hàng'
                    ELSE 'Cảnh báo đỏ - hiệu suất thấp, cần kiểm tra nhà cung cấp'
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
     * Mô tả chức năng:
     * Lấy hiệu suất giao hàng đúng hạn OTD bằng CTE mô phỏng khi schema chưa có bảng giao nhận.
     *
     * Input:
     * - array $filters: filter ngày từ DateRangeFilterRequest, được normalize để tránh truyền Carbon vào SQL.
     * - Các ngưỡng OTD, chất lượng và thời gian trễ truyền từ VendorController.
     *
     * Output:
     * - summary: số liệu tổng hợp cho gauge/chart OTD.
     * - vendors: danh sách nhà cung cấp và cảnh báo OTD chi tiết.
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
            delivery_orders AS (
                SELECT
                    CAST(NULL AS VARCHAR2(255)) AS supplier_name,
                    0 AS total_delivery_order_count,
                    0 AS on_time_delivery_order_count,
                    0 AS quality_score,
                    0 AS average_delay_hours
                FROM dual
                WHERE 1 = 0
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
                FROM delivery_orders
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
                        WHEN oc.on_time_delivery_rate >= rp.excellent_otd_threshold THEN 'Tốt - không cảnh báo'
                        WHEN oc.on_time_delivery_rate >= rp.good_otd_threshold THEN 'Theo dõi nhẹ'
                        WHEN oc.on_time_delivery_rate >= rp.warning_otd_threshold THEN 'Cảnh báo vàng - tỷ lệ đúng hạn dưới 90%'
                        ELSE 'Cảnh báo đỏ - dưới 80%, cần đàm phán lại'
                    END AS otd_warning_text,
                    CASE
                        WHEN oc.quality_score < rp.low_quality_threshold THEN 'Cảnh báo đỏ - chất lượng hàng kém'
                        WHEN oc.quality_score < rp.medium_quality_threshold THEN 'Cảnh báo vàng - chất lượng trung bình'
                        ELSE 'Chất lượng đạt'
                    END AS quality_warning_text,
                    CASE
                        WHEN oc.average_delay_hours > rp.severe_delay_hours THEN 'Cảnh báo đỏ - trễ nghiêm trọng'
                        WHEN oc.average_delay_hours > rp.warning_delay_hours THEN 'Cảnh báo vàng - trễ đáng kể'
                        WHEN oc.average_delay_hours > rp.watch_delay_hours THEN 'Theo dõi trễ nhẹ'
                        ELSE 'Giao đúng tiến độ'
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
     * Mô tả chức năng:
     * Lấy tổng hợp OTD toàn chuỗi bằng CTE mô phỏng khi schema chưa có bảng giao nhận.
     *
     * Input:
     * - array $filters: filter ngày từ DateRangeFilterRequest, được normalize để tránh truyền Carbon vào SQL.
     * - float $goodOtdThreshold, $warningOtdThreshold: ngưỡng cảnh báo OTD toàn chuỗi.
     *
     * Output:
     * - Mảng summary gồm tỷ lệ đúng hạn toàn chuỗi, chất lượng, giờ trễ và cảnh báo tổng hợp.
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
            delivery_orders AS (
                SELECT
                    CAST(NULL AS VARCHAR2(255)) AS supplier_name,
                    0 AS total_delivery_order_count,
                    0 AS on_time_delivery_order_count,
                    0 AS quality_score,
                    0 AS average_delay_hours
                FROM dual
                WHERE 1 = 0
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
                FROM delivery_orders
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
                    THEN 'Cảnh báo đỏ - có đối tác giao đúng hạn dưới 80%, gây rủi ro vận hành'
                    WHEN ROUND(
                        SUM(on_time_delivery_order_count)
                        / NULLIF(SUM(total_delivery_order_count), 0) * 100,
                        1
                    ) < rp.good_otd_threshold
                    THEN 'Cảnh báo vàng - OTD toàn chuỗi dưới 90%'
                    ELSE 'Ổn định - hiệu suất giao hàng tốt'
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

        $totalDeliveryOrderCount = (int) ($row['total_delivery_order_count'] ?? 0);

        if ($totalDeliveryOrderCount <= 0) {
            return [
                'on_time_delivery_rate' => 0,
                'quality_score' => 0,
                'average_delay_hours' => 0,
                'low_otd_vendor_count' => 0,
                'total_delivery_order_count' => 0,
                'on_time_delivery_order_count' => 0,
                'status' => 'no_data',
                'show_alert' => false,
                'warning_text' => 'Chưa có dữ liệu giao hàng đúng hạn.',
            ];
        }

        $lowOtdVendorCount = (int) ($row['low_otd_vendor_count'] ?? 0);
        $status = (string) ($row['status'] ?? 'good');

        return [
            'on_time_delivery_rate' => $this->cleanNumber((float) ($row['chain_on_time_delivery_rate'] ?? 0)),
            'quality_score' => $this->cleanNumber((float) ($row['average_quality_score'] ?? 0)),
            'average_delay_hours' => $this->cleanNumber((float) ($row['average_delay_hours'] ?? 0)),
            'low_otd_vendor_count' => $lowOtdVendorCount,
            'total_delivery_order_count' => $totalDeliveryOrderCount,
            'on_time_delivery_order_count' => (int) ($row['on_time_delivery_order_count'] ?? 0),
            'status' => $status,
            'show_alert' => $status !== 'good',
            'warning_text' => (string) ($row['warning_text'] ?? ''),
        ];
    }

    /**
     * Mô tả chức năng:
     * Lấy biểu đồ dòng tiền và công nợ phải trả nhà cung cấp theo tháng bằng CTE mô phỏng.
     *
     * Input:
     * - array $filters: filter ngày từ DateRangeFilterRequest, được normalize trước khi bind SQL.
     * - float $lightGrowthThreshold, $warningGrowthThreshold, $criticalGrowthThreshold: ngưỡng cảnh báo tăng trưởng công nợ.
     *
     * Output:
     * - summary: công nợ tháng mới nhất, tăng trưởng và cảnh báo dòng tiền.
     * - chart: các dòng tháng để frontend render bar chart.
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
            payables AS (
                SELECT
                    CAST(NULL AS VARCHAR2(7)) AS month_key,
                    0 AS total_payable_amount
                FROM dual
                WHERE 1 = 0
            ),
            payable_growth AS (
                SELECT
                    mp.month_key,
                    TO_DATE(mp.month_key || '-01', 'YYYY-MM-DD') AS month_date,
                    mp.total_payable_amount,
                    LAG(mp.total_payable_amount) OVER (ORDER BY mp.month_key) AS previous_payable_amount
                FROM payables mp
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
                    WHEN previous_payable_amount IS NULL THEN 'Không đủ dữ liệu so sánh'
                    WHEN growth_percent > critical_growth_threshold THEN 'Cảnh báo đỏ - công nợ tăng đột biến, cần chuẩn bị dòng tiền'
                    WHEN growth_percent > warning_growth_threshold THEN 'Cảnh báo vàng - công nợ tăng mạnh'
                    WHEN growth_percent >= light_growth_threshold THEN 'Cảnh báo nhẹ - công nợ tăng đáng chú ý'
                    ELSE 'Bình thường'
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
            'warning_text' => 'Không có dữ liệu công nợ trong kỳ lọc',
        ];

        return [
            'summary' => $summary,
            'chart' => $chart,
        ];
    }

    /**
     * Mô tả chức năng:
     * Lấy cảnh báo biến động giá nhập bằng CTE mô phỏng khi schema chưa có lịch sử giá nhập.
     *
     * Input:
     * - array $filters: filter ngày từ DateRangeFilterRequest, được normalize để tránh truyền Carbon vào SQL.
     * - float $watchThreshold, $warningThreshold, $criticalThreshold: ngưỡng cảnh báo biến động giá.
     *
     * Output:
     * - Mảng row table gồm vật tư, nhà cung cấp, giá trung bình 3 tháng, giá nhập mới, biến động và thao tác đề xuất.
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
            price_variance AS (
                SELECT
                    CAST(NULL AS VARCHAR2(255)) AS item_name,
                    CAST(NULL AS VARCHAR2(255)) AS supplier_name,
                    0 AS average_price_3_months,
                    0 AS latest_import_price
                FROM dual
                WHERE 1 = 0
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
                FROM price_variance
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
                    WHEN cv.variance_percent > rp.critical_threshold THEN 'Cảnh báo đỏ - biến động rất bất thường'
                    WHEN cv.variance_percent > rp.warning_threshold THEN 'Cảnh báo vàng - vượt ngưỡng 20%, cần điều tra'
                    WHEN cv.variance_percent > rp.watch_threshold THEN 'Theo dõi - giá tăng đáng chú ý'
                    ELSE 'Bình thường'
                END AS warning_text,
                CASE
                    WHEN cv.variance_percent > rp.warning_threshold THEN 'Điều tra'
                    ELSE 'Theo dõi'
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
                'suggested_action' => (string) ($data['suggested_action'] ?? 'Theo dõi'),
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
     * Mô tả chức năng:
     * Tổng hợp các dòng OTD nhà cung cấp thành số liệu summary cho gauge/chart.
     *
     * Input:
     * - array $vendors: các dòng OTD đã map từ SQL.
     * - float $excellentOtdThreshold, $goodOtdThreshold, $warningOtdThreshold: ngưỡng cảnh báo.
     *
     * Output:
     * - Mảng summary gồm tỷ lệ OTD chuỗi, chất lượng, giờ trễ và số đối tác cảnh báo.
     */
    private function summarizeOnTimeDelivery(
        array $vendors,
        float $excellentOtdThreshold,
        float $goodOtdThreshold,
        float $warningOtdThreshold
    ): array {
        $vendorCount = count($vendors);

        if ($vendorCount === 0) {
            return [
                'total_delivery_order_count' => 0,
                'on_time_delivery_order_count' => 0,
                'on_time_delivery_rate' => 0,
                'quality_score' => 0,
                'average_delay_hours' => 0,
                'low_otd_vendor_count' => 0,
                'watch_otd_vendor_count' => 0,
                'status' => 'no_data',
                'show_alert' => false,
                'warning_text' => 'Chưa có dữ liệu giao hàng đúng hạn.',
            ];
        }

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
     * Mô tả chức năng:
     * Xác định trạng thái màu cho gauge OTD dựa trên summary đã tính.
     *
     * Input:
     * - float $onTimeRate: tỷ lệ giao đúng hạn toàn chuỗi.
     * - int $lowOtdVendorCount: số nhà cung cấp dưới ngưỡng đỏ.
     * - float $excellentOtdThreshold, $warningOtdThreshold: ngưỡng trạng thái.
     *
     * Output:
     * - Chuỗi status gồm danger, warning hoặc good cho frontend render class.
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
     * Mô tả chức năng:
     * Tạo nội dung cảnh báo tổng hợp theo ngưỡng OTD.
     *
     * Input:
     * - float $onTimeRate: tỷ lệ giao đúng hạn toàn chuỗi.
     * - int $lowOtdVendorCount: số nhà cung cấp dưới ngưỡng đỏ.
     * - Các ngưỡng OTD cấu hình.
     *
     * Output:
     * - Chuỗi cảnh báo để frontend hiển thị trong card OTD.
     */
    private function onTimeDeliverySummaryWarning(
        float $onTimeRate,
        int $lowOtdVendorCount,
        float $excellentOtdThreshold,
        float $goodOtdThreshold,
        float $warningOtdThreshold
    ): string {
        if ($lowOtdVendorCount > 0) {
            return 'Có nhà cung cấp dưới ngưỡng 80%, cần đàm phán lại để tránh gián đoạn vật tư.';
        }

        if ($onTimeRate >= $excellentOtdThreshold) {
            return 'OTD rất tốt, chưa cần cảnh báo.';
        }

        if ($onTimeRate >= $goodOtdThreshold) {
            return 'OTD tốt, tiếp tục theo dõi nhẹ.';
        }

        if ($onTimeRate >= $warningOtdThreshold) {
            return 'Cảnh báo vàng: OTD toàn chuỗi dưới 90%, cần theo dõi lịch giao hàng.';
        }

        return 'Cảnh báo đỏ: OTD toàn chuỗi dưới 80%, cần đàm phán lại với nhà cung cấp.';
    }

    /**
     * Mô tả chức năng:
     * Chuyển các mốc ngày trong filter về chuỗi Y-m-d nếu có.
     *
     * Input:
     * - array $filters: filter từ DateRangeFilterRequest có thể chứa Carbon object.
     *
     * Output:
     * - Mảng filter đã normalize ngày, không truyền Carbon trực tiếp vào SQL.
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
     * Mô tả chức năng:
     * Chuyển một giá trị ngày bất kỳ về chuỗi Y-m-d.
     *
     * Input:
     * - mixed $value: DateTimeInterface, chuỗi ngày hoặc null.
     *
     * Output:
     * - Chuỗi ngày Y-m-d hoặc null nếu không có giá trị hợp lệ.
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
     * Mô tả chức năng:
     * Đổi chuỗi tháng YYYY-MM thành nhãn ngắn cho biểu đồ.
     *
     * Input:
     * - string $monthKey: giá trị tháng dạng YYYY-MM.
     *
     * Output:
     * - Nhãn tháng dạng T1, T2... hoặc chuỗi gốc nếu không hợp lệ.
     */
    private function monthLabel(string $monthKey): string
    {
        if (preg_match('/^\d{4}-(\d{2})$/', $monthKey, $matches) !== 1) {
            return $monthKey;
        }

        return 'T'.(int) $matches[1];
    }

    /**
     * Mô tả chức năng:
     * Làm gọn số thập phân trả về cho JSON table.
     *
     * Input:
     * - float $value: giá trị cần làm gọn.
     *
     * Output:
     * - int nếu không có phần thập phân, ngược lại trả float 2 chữ số.
     */
    private function cleanNumber(float $value): int|float
    {
        $rounded = round($value, 2);

        return abs($rounded - round($rounded)) < 0.00001
            ? (int) round($rounded)
            : $rounded;
    }
}
