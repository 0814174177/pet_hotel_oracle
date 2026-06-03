<?php

namespace App\Repositories\Eloquent\Manager;

use App\Repositories\Contracts\Manager\ManagerDashboardRepositoryInterface;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

class ManagerDashboardRepository implements ManagerDashboardRepositoryInterface
{
    /**
     * Mo ta chuc nang:
     * Lay nhom KPI tong quan lich chi nhanh cho trang Manager overview.
     *
     * Input:
     * - int|string $branchId: Chi nhanh hien tai cua Manager hoac branchId tu route.
     * - array $filters gom start_date, end_date, prev_start_date, prev_end_date.
     *
     * Output:
     * - Mang data gom checkin, checkout, spa_grooming va walkin_rooms.
     *
     * Ghi chu:
     * - SQL Oracle nam trong Repository, Controller khong tinh toan KPI.
     */
    public function getOverview(int|string $branchId, array $filters = []): array
    {
        return $this->getScheduleKpis($branchId, $filters);
    }

    /**
     * Mo ta chuc nang:
     * Lay KPI lich check-in, check-out, Spa/Grooming va phong walk-in theo chi nhanh.
     *
     * Input:
     * - int|string $branchId: Chi nhanh can loc.
     * - array $filters gom start_date, end_date va ky truoc tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang JSON ro nghia cho frontend render KPI card.
     *
     * Ghi chu:
     * - Bind Oracle dung p_branch_id, p_start_date, p_end_date, p_prev_start_date,
     *   p_prev_end_date; khong hard-code branch/ngay.
     */
    public function getScheduleKpis(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($branchId, $filters);

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
            checkin_cur AS (
                SELECT COUNT(DISTINCT brp.booking_room_pet_id) AS pet_count
                FROM params prm
                JOIN booking bk
                    ON bk.branch_id = prm.branch_id
                JOIN booking_room br
                    ON br.booking_id = bk.booking_id
                JOIN booking_room_pet brp
                    ON brp.booking_room_id = br.booking_room_id
                WHERE NVL(bk.status, 'PENDING') <> 'CANCELLED'
                  AND bk.checkin_expected_at >= prm.start_date
                  AND bk.checkin_expected_at <  prm.end_date + 1
            ),
            checkin_prev AS (
                SELECT COUNT(DISTINCT brp.booking_room_pet_id) AS pet_count
                FROM params prm
                JOIN booking bk
                    ON bk.branch_id = prm.branch_id
                JOIN booking_room br
                    ON br.booking_id = bk.booking_id
                JOIN booking_room_pet brp
                    ON brp.booking_room_id = br.booking_room_id
                WHERE NVL(bk.status, 'PENDING') <> 'CANCELLED'
                  AND bk.checkin_expected_at >= prm.prev_start_date
                  AND bk.checkin_expected_at <  prm.prev_end_date + 1
            ),
            checkout_cur AS (
                SELECT COUNT(DISTINCT brp.booking_room_pet_id) AS pet_count
                FROM params prm
                JOIN booking bk
                    ON bk.branch_id = prm.branch_id
                JOIN booking_room br
                    ON br.booking_id = bk.booking_id
                JOIN booking_room_pet brp
                    ON brp.booking_room_id = br.booking_room_id
                WHERE NVL(bk.status, 'PENDING') <> 'CANCELLED'
                  AND bk.checkout_expected_at >= prm.start_date
                  AND bk.checkout_expected_at <  prm.end_date + 1
            ),
            checkout_prev AS (
                SELECT COUNT(DISTINCT brp.booking_room_pet_id) AS pet_count
                FROM params prm
                JOIN booking bk
                    ON bk.branch_id = prm.branch_id
                JOIN booking_room br
                    ON br.booking_id = bk.booking_id
                JOIN booking_room_pet brp
                    ON brp.booking_room_id = br.booking_room_id
                WHERE NVL(bk.status, 'PENDING') <> 'CANCELLED'
                  AND bk.checkout_expected_at >= prm.prev_start_date
                  AND bk.checkout_expected_at <  prm.prev_end_date + 1
            ),
            spa_cur AS (
                SELECT COUNT(DISTINCT bsp.booking_service_pet_id) AS service_count
                FROM params prm
                JOIN booking bk
                    ON bk.branch_id = prm.branch_id
                JOIN booking_service_pet bsp
                    ON bsp.booking_id = bk.booking_id
                WHERE NVL(bk.status, 'PENDING') <> 'CANCELLED'
                  AND NVL(bsp.status, 'PENDING') <> 'CANCELLED'
                  AND bsp.scheduled_at >= prm.start_date
                  AND bsp.scheduled_at <  prm.end_date + 1
            ),
            spa_prev AS (
                SELECT COUNT(DISTINCT bsp.booking_service_pet_id) AS service_count
                FROM params prm
                JOIN booking bk
                    ON bk.branch_id = prm.branch_id
                JOIN booking_service_pet bsp
                    ON bsp.booking_id = bk.booking_id
                WHERE NVL(bk.status, 'PENDING') <> 'CANCELLED'
                  AND NVL(bsp.status, 'PENDING') <> 'CANCELLED'
                  AND bsp.scheduled_at >= prm.prev_start_date
                  AND bsp.scheduled_at <  prm.prev_end_date + 1
            ),
            room_status AS (
                SELECT COUNT(r.room_id) AS available_rooms
                FROM params prm
                LEFT JOIN room r
                    ON r.branch_id = prm.branch_id
                   AND r.status = 'AVAILABLE'
            ),
            metrics AS (
                SELECT
                    NVL(checkin_cur.pet_count, 0) AS checkin_current,
                    NVL(checkin_prev.pet_count, 0) AS checkin_previous,
                    NVL(checkout_cur.pet_count, 0) AS checkout_current,
                    NVL(checkout_prev.pet_count, 0) AS checkout_previous,
                    NVL(spa_cur.service_count, 0) AS spa_grooming_current,
                    NVL(spa_prev.service_count, 0) AS spa_grooming_previous,
                    NVL(room_status.available_rooms, 0) AS available_rooms
                FROM checkin_cur
                CROSS JOIN checkin_prev
                CROSS JOIN checkout_cur
                CROSS JOIN checkout_prev
                CROSS JOIN spa_cur
                CROSS JOIN spa_prev
                CROSS JOIN room_status
            )
            SELECT
                checkin_current,
                checkin_previous,
                CASE
                    WHEN checkin_previous = 0 THEN NULL
                    ELSE ROUND(
                        (checkin_current - checkin_previous)
                        / NULLIF(checkin_previous, 0) * 100,
                        2
                    )
                END AS checkin_growth_percent,

                checkout_current,
                checkout_previous,
                CASE
                    WHEN checkout_previous = 0 THEN NULL
                    ELSE ROUND(
                        (checkout_current - checkout_previous)
                        / NULLIF(checkout_previous, 0) * 100,
                        2
                    )
                END AS checkout_growth_percent,

                spa_grooming_current,
                spa_grooming_previous,
                CASE
                    WHEN spa_grooming_previous = 0 THEN NULL
                    ELSE ROUND(
                        (spa_grooming_current - spa_grooming_previous)
                        / NULLIF(spa_grooming_previous, 0) * 100,
                        2
                    )
                END AS spa_grooming_growth_percent,

                available_rooms,
                CASE
                    WHEN available_rooms = 0 THEN 'HẾT PHÒNG WALK-IN'
                    WHEN available_rooms <= 5 THEN 'CẢNH BÁO: SẮP HẾT PHÒNG WALK-IN'
                    ELSE 'CÒN PHÒNG'
                END AS walkin_warning
            FROM metrics
            SQL;

        $row = array_change_key_case((array) DB::selectOne($sql, $this->oracleBindings($branchId, $filters)), CASE_LOWER);

        $availableRooms = (int) ($row['available_rooms'] ?? 0);

        return [
            'checkin' => $this->comparisonPayload($row, 'checkin'),
            'checkout' => $this->comparisonPayload($row, 'checkout'),
            'spa_grooming' => $this->comparisonPayload($row, 'spa_grooming'),
            'walkin_rooms' => [
                'available_rooms' => $availableRooms,
                'warning' => (string) ($row['walkin_warning'] ?? $this->walkinWarning($availableRooms)),
            ],
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay canh bao ton kho vat tu theo chi nhanh cho bang cong viec khan cap.
     *
     * Input:
     * - int|string $branchId: Chi nhanh can loc.
     * - array $filters: Giu chu ky API dashboard, khong dung de loc ngay cho ton kho snapshot.
     *
     * Output:
     * - Mang current gom out_of_stock_count, low_stock_count, inventory_warning va severity.
     *
     * Ghi chu:
     * - SQL Oracle dung :p_branch_id, khong hard-code branch_id.
     */
    public function getInventoryWarning(int|string $branchId, array $filters = []): array
    {
        $sql = <<<'SQL'
            WITH params AS (
                SELECT :p_branch_id AS branch_id
                FROM dual
            ),
            inventory_counts AS (
                SELECT
                    COUNT(CASE
                        WHEN bi.quantity_in_stock = 0 THEN 1
                    END) AS out_of_stock_count,

                    COUNT(CASE
                        WHEN bi.quantity_in_stock > 0
                         AND bi.quantity_in_stock <= NVL(bi.reorder_point, 0)
                        THEN 1
                    END) AS low_stock_count
                FROM branch_inventory bi
                JOIN params p
                    ON p.branch_id = bi.branch_id
            )
            SELECT
                NVL(out_of_stock_count, 0) AS out_of_stock_count,
                NVL(low_stock_count, 0) AS low_stock_count,
                CASE
                    WHEN NVL(out_of_stock_count, 0) > 0 THEN 'CẢNH BÁO ĐỎ: CÓ VẬT TƯ HẾT HÀNG'
                    WHEN NVL(low_stock_count, 0) > 0 THEN 'CẢNH BÁO VÀNG: CÓ VẬT TƯ SẮP HẾT'
                    ELSE 'AN TOÀN: VẬT TƯ TIÊU HAO ĐANG Ở MỨC AN TOÀN'
                END AS inventory_warning,
                CASE
                    WHEN NVL(out_of_stock_count, 0) > 0 THEN 'red'
                    WHEN NVL(low_stock_count, 0) > 0 THEN 'yellow'
                    ELSE 'green'
                END AS severity
            FROM inventory_counts
            SQL;

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_branch_id' => $branchId,
        ]), CASE_LOWER);

        return [
            'current' => [
                'out_of_stock_count' => (int) ($row['out_of_stock_count'] ?? 0),
                'low_stock_count' => (int) ($row['low_stock_count'] ?? 0),
                'inventory_warning' => (string) ($row['inventory_warning'] ?? 'AN TOÀN: VẬT TƯ TIÊU HAO ĐANG Ở MỨC AN TOÀN'),
                'severity' => (string) ($row['severity'] ?? 'green'),
            ],
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay canh bao y te tam thoi theo keyword trong ghi chu cua pet va lich dich vu.
     *
     * Input:
     * - int|string $branchId: Chi nhanh can loc.
     * - array $filters gom start_date va end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang current gom health_warning_count, health_warning_text va severity.
     *
     * Ghi chu:
     * - Tạm thời phát hiện cảnh báo y tế bằng keyword trong notes/special_notes.
     *   Khi có bảng pet_health_record thì cần thay bằng dữ liệu y tế chuẩn.
     */
    public function getHealthWarning(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($branchId, $filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :branch_id AS branch_id,
                    TRUNC(TO_DATE(:start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:end_date, 'YYYY-MM-DD')) AS end_date
                FROM dual
            ),
            health_counts AS (
                SELECT COUNT(DISTINCT p.pet_id) AS health_warning_count
                FROM booking b
                JOIN booking_service_pet bsp
                    ON bsp.booking_id = b.booking_id
                JOIN pet p
                    ON p.pet_id = bsp.pet_id
                JOIN params prm
                    ON prm.branch_id = b.branch_id
                WHERE bsp.scheduled_at >= prm.start_date
                  AND bsp.scheduled_at <  prm.end_date + 1
                  AND (
                        LOWER(NVL(p.special_notes, ' ')) LIKE '%bệnh%'
                     OR LOWER(NVL(p.special_notes, ' ')) LIKE '%dị ứng%'
                     OR LOWER(NVL(p.special_notes, ' ')) LIKE '%thuốc%'
                     OR LOWER(NVL(p.special_notes, ' ')) LIKE '%theo dõi%'
                     OR LOWER(NVL(p.special_notes, ' ')) LIKE '%bỏ ăn%'
                     OR LOWER(NVL(p.special_notes, ' ')) LIKE '%y tế%'
                     OR LOWER(NVL(bsp.notes, ' ')) LIKE '%bệnh%'
                     OR LOWER(NVL(bsp.notes, ' ')) LIKE '%dị ứng%'
                     OR LOWER(NVL(bsp.notes, ' ')) LIKE '%thuốc%'
                     OR LOWER(NVL(bsp.notes, ' ')) LIKE '%theo dõi%'
                     OR LOWER(NVL(bsp.notes, ' ')) LIKE '%bỏ ăn%'
                     OR LOWER(NVL(bsp.notes, ' ')) LIKE '%y tế%'
                  )
            )
            SELECT
                NVL(health_warning_count, 0) AS health_warning_count,
                CASE
                    WHEN NVL(health_warning_count, 0) = 0 THEN
                        '0 Cảnh báo Y tế - Tất cả các bé đều đang khỏe mạnh và ăn uống tốt.'
                    WHEN NVL(health_warning_count, 0) <= 5 THEN
                        'CẢNH BÁO Y TẾ NHẸ: CÓ PET CẦN THEO DÕI'
                    ELSE
                        'CẢNH BÁO Y TẾ CAO: NHIỀU PET CẦN THEO DÕI'
                END AS health_warning_text,
                CASE
                    WHEN NVL(health_warning_count, 0) = 0 THEN 'green'
                    WHEN NVL(health_warning_count, 0) <= 5 THEN 'yellow'
                    ELSE 'red'
                END AS severity
            FROM health_counts
            SQL;

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'branch_id' => $branchId,
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
        ]), CASE_LOWER);

        return [
            'current' => [
                'health_warning_count' => (int) ($row['health_warning_count'] ?? 0),
                'health_warning_text' => (string) ($row['health_warning_text'] ?? '0 Cảnh báo Y tế - Tất cả các bé đều đang khỏe mạnh và ăn uống tốt.'),
                'severity' => (string) ($row['severity'] ?? 'green'),
            ],
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay canh bao rui ro tai chinh theo don huy/hoan va booking bi huy cua chi nhanh.
     *
     * Input:
     * - int|string $branchId: Chi nhanh can loc.
     * - array $filters gom start_date va end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang current gom cancelled_or_refunded_orders, lost_revenue_amount,
     *   financial_risk_warning va severity.
     *
     * Ghi chu:
     * - orders.status CANCELLED/REFUNDED la nguon chinh.
     * - booking.status CANCELLED duoc tinh them khi booking chua co order CANCELLED/REFUNDED,
     *   de tranh dem trung cung mot giao dich.
     */
    public function getFinancialRiskWarning(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($branchId, $filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :branch_id AS branch_id,
                    TRUNC(TO_DATE(:start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:end_date, 'YYYY-MM-DD')) AS end_date
                FROM dual
            ),
            risk_events AS (
                SELECT
                    'ORDER_' || o.order_id AS event_key,
                    NVL(o.grand_total, 0) AS lost_amount
                FROM orders o
                JOIN params p
                    ON p.branch_id = o.branch_id
                WHERE o.created_at >= p.start_date
                  AND o.created_at <  p.end_date + 1
                  AND o.status IN ('CANCELLED', 'REFUNDED')

                UNION ALL

                SELECT
                    'BOOKING_' || b.booking_id AS event_key,
                    NVL(b.total_amount, 0) AS lost_amount
                FROM booking b
                JOIN params p
                    ON p.branch_id = b.branch_id
                WHERE b.created_at >= p.start_date
                  AND b.created_at <  p.end_date + 1
                  AND b.status = 'CANCELLED'
                  AND NOT EXISTS (
                      SELECT 1
                      FROM orders o
                      WHERE o.booking_id = b.booking_id
                        AND o.status IN ('CANCELLED', 'REFUNDED')
                  )
            ),
            risk_totals AS (
                SELECT
                    COUNT(event_key) AS cancelled_or_refunded_orders,
                    NVL(SUM(lost_amount), 0) AS lost_revenue_amount
                FROM risk_events
            )
            SELECT
                NVL(cancelled_or_refunded_orders, 0) AS cancelled_or_refunded_orders,
                NVL(lost_revenue_amount, 0) AS lost_revenue_amount,
                CASE
                    WHEN NVL(cancelled_or_refunded_orders, 0) = 0 THEN 'BÌNH THƯỜNG'
                    WHEN NVL(cancelled_or_refunded_orders, 0) > 5
                      OR NVL(lost_revenue_amount, 0) > 5000000
                    THEN 'CẢNH BÁO ĐỎ: RỦI RO TÀI CHÍNH CAO'
                    ELSE 'CẢNH BÁO NHẸ: CÓ ĐƠN HỦY/HOÀN'
                END AS financial_risk_warning,
                CASE
                    WHEN NVL(cancelled_or_refunded_orders, 0) = 0 THEN 'green'
                    WHEN NVL(cancelled_or_refunded_orders, 0) > 5
                      OR NVL(lost_revenue_amount, 0) > 5000000
                    THEN 'red'
                    ELSE 'yellow'
                END AS severity
            FROM risk_totals
            SQL;

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'branch_id' => $branchId,
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
        ]), CASE_LOWER);

        return [
            'current' => [
                'cancelled_or_refunded_orders' => (int) ($row['cancelled_or_refunded_orders'] ?? 0),
                'lost_revenue_amount' => $this->nullableFloat($row['lost_revenue_amount'] ?? null) ?? 0.0,
                'financial_risk_warning' => (string) ($row['financial_risk_warning'] ?? 'BÌNH THƯỜNG'),
                'severity' => (string) ($row['severity'] ?? 'green'),
            ],
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach booking bi huy gan thoi diem check-in cua chi nhanh trong ky.
     *
     * Input:
     * - int|string $branchId: Chi nhanh can loc.
     * - array $filters gom start_date va end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang current gom total_late_cancelled_bookings va items chi tiet booking huy.
     *
     * Ghi chu:
     * - Schema hiện chưa có cancelled_at, tạm dùng booking.updated_at làm thời điểm hủy.
     *   Khi bổ sung cancelled_at thì cần thay lại để dữ liệu chính xác hơn.
     * - Loc ky bao cao theo booking.updated_at vi day la thoi diem huy tam tinh.
     * - orders duoc aggregate theo booking_id truoc khi join de tranh lap booking tren danh sach.
     */
    public function getLateCancelledBookings(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($branchId, $filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :branch_id AS branch_id,
                    TRUNC(TO_DATE(:start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:end_date, 'YYYY-MM-DD')) AS end_date
                FROM dual
            ),
            order_totals AS (
                SELECT
                    booking_id,
                    SUM(NVL(grand_total, 0)) AS grand_total
                FROM orders
                WHERE booking_id IS NOT NULL
                GROUP BY booking_id
            ),
            cancelled_bookings AS (
                SELECT
                    b.booking_id,
                    c.full_name AS customer_name,
                    b.checkin_expected_at,
                    b.updated_at AS cancel_time_assumption,
                    ROUND(
                        (
                            CAST(b.checkin_expected_at AS DATE)
                            - CAST(b.updated_at AS DATE)
                        ) * 24,
                        2
                    ) AS hours_before_checkin,
                    NVL(ot.grand_total, 0) AS grand_total
                FROM booking b
                JOIN customer c
                    ON c.customer_id = b.customer_id
                LEFT JOIN order_totals ot
                    ON ot.booking_id = b.booking_id
                JOIN params p
                    ON p.branch_id = b.branch_id
                WHERE b.status = 'CANCELLED'
                  AND b.updated_at >= p.start_date
                  AND b.updated_at <  p.end_date + 1
            )
            SELECT
                booking_id,
                customer_name,
                TO_CHAR(checkin_expected_at, 'YYYY-MM-DD HH24:MI:SS') AS checkin_expected_at,
                TO_CHAR(cancel_time_assumption, 'YYYY-MM-DD HH24:MI:SS') AS cancel_time_assumption,
                hours_before_checkin,
                grand_total,
                CASE
                    WHEN hours_before_checkin <= 0 THEN 'CẢNH BÁO ĐỎ: HỦY SAU GIỜ CHECK-IN'
                    WHEN hours_before_checkin <= 24 THEN 'HỦY SÁT GIỜ'
                    WHEN hours_before_checkin <= 48 THEN 'CẢNH BÁO NHẸ'
                    ELSE 'HỦY BÌNH THƯỜNG'
                END AS cancel_warning,
                CASE
                    WHEN hours_before_checkin <= 0 THEN 'red'
                    WHEN hours_before_checkin <= 24 THEN 'yellow'
                    WHEN hours_before_checkin <= 48 THEN 'orange'
                    ELSE 'green'
                END AS severity
            FROM cancelled_bookings
            ORDER BY checkin_expected_at, booking_id
            SQL;

        $rows = DB::select($sql, [
            'branch_id' => $branchId,
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
        ]);

        $items = array_map(function (object $row): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'booking_id' => (int) ($data['booking_id'] ?? 0),
                'customer_name' => (string) ($data['customer_name'] ?? ''),
                'checkin_expected_at' => (string) ($data['checkin_expected_at'] ?? ''),
                'cancel_time_assumption' => (string) ($data['cancel_time_assumption'] ?? ''),
                'hours_before_checkin' => $this->nullableFloat($data['hours_before_checkin'] ?? null) ?? 0.0,
                'grand_total' => $this->nullableFloat($data['grand_total'] ?? null) ?? 0.0,
                'cancel_warning' => (string) ($data['cancel_warning'] ?? ''),
                'severity' => (string) ($data['severity'] ?? 'green'),
            ];
        }, $rows);

        return [
            'current' => [
                'total_late_cancelled_bookings' => count($items),
                'items' => $items,
            ],
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay top 5 dich vu co doanh thu cao nhat cua chi nhanh trong ky.
     *
     * Input:
     * - int|string $branchId: Chi nhanh can loc.
     * - array $filters gom start_date va end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang current.items gom rank_no, service_id, service_name,
     *   service_revenue va service_cases.
     *
     * Ghi chu:
     * - Doanh thu dich vu tinh bang SUM(order_details.line_total).
     * - Chi tinh orders.status PAID/COMPLETED va loc ky theo orders.created_at.
     * - Dung ROW_NUMBER() de sap xep truoc khi lay top 5 theo Oracle.
     */
    public function getTopRevenueServices(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($branchId, $filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :branch_id AS branch_id,
                    TRUNC(TO_DATE(:start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:end_date, 'YYYY-MM-DD')) AS end_date
                FROM dual
            ),
            service_totals AS (
                SELECT
                    s.service_id,
                    s.service_name,
                    SUM(NVL(od.line_total, 0)) AS service_revenue,
                    COUNT(DISTINCT bsp.booking_service_pet_id) AS service_cases
                FROM params p
                JOIN orders o
                    ON o.branch_id = p.branch_id
                JOIN order_details od
                    ON od.order_id = o.order_id
                JOIN booking_service_pet bsp
                    ON bsp.booking_service_pet_id = od.booking_service_pet_id
                JOIN services s
                    ON s.service_id = bsp.service_id
                WHERE o.status IN ('PAID', 'COMPLETED')
                  AND o.created_at >= p.start_date
                  AND o.created_at <  p.end_date + 1
                GROUP BY
                    s.service_id,
                    s.service_name
            ),
            ranked_services AS (
                SELECT
                    ROW_NUMBER() OVER (
                        ORDER BY service_revenue DESC, service_id
                    ) AS rank_no,
                    service_id,
                    service_name,
                    service_revenue,
                    service_cases
                FROM service_totals
            )
            SELECT
                rank_no,
                service_id,
                service_name,
                service_revenue,
                service_cases
            FROM ranked_services
            WHERE rank_no <= 5
            ORDER BY rank_no
            SQL;

        $rows = DB::select($sql, [
            'branch_id' => $branchId,
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
        ]);

        $items = array_map(function (object $row): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'rank_no' => (int) ($data['rank_no'] ?? 0),
                'service_id' => (int) ($data['service_id'] ?? 0),
                'service_name' => (string) ($data['service_name'] ?? ''),
                'service_revenue' => $this->nullableFloat($data['service_revenue'] ?? null) ?? 0.0,
                'service_cases' => (int) ($data['service_cases'] ?? 0),
            ];
        }, $rows);

        return [
            'current' => [
                'items' => $items,
            ],
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay co cau doanh thu theo nhom Pet Hotel, Grooming & Spa va Khac.
     *
     * Input:
     * - int|string $branchId: Chi nhanh can loc.
     * - array $filters gom start_date va end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang current.items gom revenue_group, revenue va percent_of_total.
     *
     * Ghi chu:
     * - Pet Hotel: order_details.booking_room_id IS NOT NULL.
     * - Grooming & Spa: order_details.booking_service_pet_id IS NOT NULL.
     * - Neu mot dong order_details co ca booking_room_id va booking_service_pet_id,
     *   CASE se xep vao Pet Hotel theo quy tac uu tien cua SQL dau vao.
     */
    public function getRevenueStructure(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($branchId, $filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :branch_id AS branch_id,
                    TRUNC(TO_DATE(:start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:end_date, 'YYYY-MM-DD')) AS end_date
                FROM dual
            ),
            revenue_groups AS (
                SELECT 'Pet Hotel' AS revenue_group, 1 AS group_order FROM dual
                UNION ALL
                SELECT 'Grooming & Spa' AS revenue_group, 2 AS group_order FROM dual
                UNION ALL
                SELECT 'Khác' AS revenue_group, 3 AS group_order FROM dual
            ),
            revenue_by_group AS (
                SELECT
                    CASE
                        WHEN od.booking_room_id IS NOT NULL THEN 'Pet Hotel'
                        WHEN od.booking_service_pet_id IS NOT NULL THEN 'Grooming & Spa'
                        ELSE 'Khác'
                    END AS revenue_group,
                    SUM(NVL(od.line_total, 0)) AS revenue
                FROM params p
                JOIN orders o
                    ON o.branch_id = p.branch_id
                JOIN order_details od
                    ON od.order_id = o.order_id
                WHERE o.status IN ('PAID', 'COMPLETED')
                  AND o.created_at >= p.start_date
                  AND o.created_at <  p.end_date + 1
                GROUP BY CASE
                    WHEN od.booking_room_id IS NOT NULL THEN 'Pet Hotel'
                    WHEN od.booking_service_pet_id IS NOT NULL THEN 'Grooming & Spa'
                    ELSE 'Khác'
                END
            ),
            revenue_totals AS (
                SELECT
                    rg.revenue_group,
                    rg.group_order,
                    NVL(rbg.revenue, 0) AS revenue,
                    SUM(NVL(rbg.revenue, 0)) OVER () AS total_revenue
                FROM revenue_groups rg
                LEFT JOIN revenue_by_group rbg
                    ON rbg.revenue_group = rg.revenue_group
            )
            SELECT
                revenue_group,
                revenue,
                CASE
                    WHEN NVL(total_revenue, 0) = 0 THEN 0
                    ELSE ROUND(revenue / NULLIF(total_revenue, 0) * 100, 2)
                END AS percent_of_total
            FROM revenue_totals
            ORDER BY revenue DESC, group_order
            SQL;

        $rows = DB::select($sql, [
            'branch_id' => $branchId,
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
        ]);

        $items = array_map(function (object $row): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'revenue_group' => (string) ($data['revenue_group'] ?? ''),
                'revenue' => $this->nullableFloat($data['revenue'] ?? null) ?? 0.0,
                'percent_of_total' => $this->nullableFloat($data['percent_of_total'] ?? null) ?? 0.0,
            ];
        }, $rows);

        return [
            'current' => [
                'items' => $items,
            ],
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach hoa don chua thanh toan du va con cong no cua chi nhanh.
     *
     * Input:
     * - int|string $branchId: Chi nhanh can loc.
     * - array $filters gom start_date va end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Mang current gom total_unpaid_invoices, total_remaining_debt va items.
     *
     * Ghi chu:
     * - Schema hien tai co unique payments.order_id nen moi order toi da mot payment,
     *   vi vay co the LEFT JOIN payments truc tiep.
     * - Neu sau nay cho phep nhieu payment/order, can aggregate payments truoc khi join.
     */
    public function getUnpaidInvoices(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($branchId, $filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :branch_id AS branch_id,
                    TRUNC(TO_DATE(:start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:end_date, 'YYYY-MM-DD')) AS end_date
                FROM dual
            ),
            unpaid_orders AS (
                SELECT
                    c.customer_id,
                    c.full_name AS customer_name,
                    c.phone,
                    o.order_id,
                    NVL(o.grand_total, 0) AS grand_total,
                    NVL(pmt.amount, 0) AS paid_amount,
                    NVL(o.grand_total, 0) - NVL(pmt.amount, 0) AS remaining_debt,
                    o.created_at AS debt_created_at
                FROM params prm
                JOIN orders o
                    ON o.branch_id = prm.branch_id
                JOIN customer c
                    ON c.customer_id = o.customer_id
                LEFT JOIN payments pmt
                    ON pmt.order_id = o.order_id
                WHERE o.created_at >= prm.start_date
                  AND o.created_at <  prm.end_date + 1
                  AND (
                        o.status IN ('PENDING', 'PROCESSING', 'PARTIAL')
                     OR pmt.status IN ('PENDING', 'FAILED')
                     OR NVL(o.grand_total, 0) > NVL(pmt.amount, 0)
                  )
            )
            SELECT
                customer_id,
                customer_name,
                phone,
                order_id,
                grand_total,
                paid_amount,
                remaining_debt,
                TO_CHAR(debt_created_at, 'YYYY-MM-DD HH24:MI:SS') AS debt_created_at,
                CASE
                    WHEN remaining_debt > 1000000 THEN 'CÔNG NỢ CAO'
                    WHEN remaining_debt > 0 THEN 'CÓ CÔNG NỢ'
                    ELSE 'ĐÃ THANH TOÁN'
                END AS debt_warning,
                CASE
                    WHEN remaining_debt > 1000000 THEN 'red'
                    WHEN remaining_debt > 0 THEN 'yellow'
                    ELSE 'green'
                END AS severity
            FROM unpaid_orders
            ORDER BY remaining_debt DESC, debt_created_at DESC, order_id
            SQL;

        $rows = DB::select($sql, [
            'branch_id' => $branchId,
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
        ]);

        $items = array_map(function (object $row): array {
            $data = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'customer_id' => (int) ($data['customer_id'] ?? 0),
                'customer_name' => (string) ($data['customer_name'] ?? ''),
                'phone' => (string) ($data['phone'] ?? ''),
                'order_id' => (int) ($data['order_id'] ?? 0),
                'grand_total' => $this->nullableFloat($data['grand_total'] ?? null) ?? 0.0,
                'paid_amount' => $this->nullableFloat($data['paid_amount'] ?? null) ?? 0.0,
                'remaining_debt' => $this->nullableFloat($data['remaining_debt'] ?? null) ?? 0.0,
                'debt_created_at' => (string) ($data['debt_created_at'] ?? ''),
                'debt_warning' => (string) ($data['debt_warning'] ?? 'ĐÃ THANH TOÁN'),
                'severity' => (string) ($data['severity'] ?? 'green'),
            ];
        }, $rows);

        $totalRemainingDebt = array_reduce(
            $items,
            fn (float $sum, array $item): float => $sum + (float) $item['remaining_debt'],
            0.0
        );

        return [
            'current' => [
                'total_unpaid_invoices' => count($items),
                'total_remaining_debt' => $totalRemainingDebt,
                'items' => $items,
            ],
        ];
    }

    /**
     * Mo ta chuc nang:
     * Dem so pet co booking phong check-in trong ky hien tai.
     *
     * Input:
     * - int|string|null $branchId: Chi nhanh can loc.
     * - array $period gom start_date va end_date.
     *
     * Output:
     * - So pet co lich check-in.
     */
    public function countCheckIns(int|string|null $branchId, array $period): int
    {
        return $this->countRoomBookingPetsByDate($branchId, $period, 'checkin_expected_at');
    }

    /**
     * Mo ta chuc nang:
     * Dem so pet co booking phong check-out trong ky hien tai.
     *
     * Input:
     * - int|string|null $branchId: Chi nhanh can loc.
     * - array $period gom start_date va end_date.
     *
     * Output:
     * - So pet co lich check-out.
     */
    public function countCheckOuts(int|string|null $branchId, array $period): int
    {
        return $this->countRoomBookingPetsByDate($branchId, $period, 'checkout_expected_at');
    }

    /**
     * Mo ta chuc nang:
     * Dem so booking_service_pet co lich Spa/Grooming trong ky hien tai.
     *
     * Input:
     * - int|string|null $branchId: Chi nhanh can loc.
     * - array $period gom start_date va end_date.
     *
     * Output:
     * - So booking_service_pet theo scheduled_at.
     */
    public function countGroomingAppointments(int|string|null $branchId, array $period): int
    {
        $filters = $this->normalizeFilters($branchId, $period);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :p_branch_id AS branch_id,
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date
                FROM dual
            )
            SELECT COUNT(DISTINCT bsp.booking_service_pet_id) AS service_count
            FROM params prm
            JOIN booking bk
                ON bk.branch_id = prm.branch_id
            JOIN booking_service_pet bsp
                ON bsp.booking_id = bk.booking_id
            WHERE NVL(bk.status, 'PENDING') <> 'CANCELLED'
              AND NVL(bsp.status, 'PENDING') <> 'CANCELLED'
              AND bsp.scheduled_at >= prm.start_date
              AND bsp.scheduled_at <  prm.end_date + 1
            SQL;

        $row = DB::selectOne($sql, $this->currentOracleBindings($branchId, $filters));

        return (int) ($this->rowValue($row, 'service_count') ?? 0);
    }

    /**
     * Mo ta chuc nang:
     * Lay so phong AVAILABLE va canh bao phong walk-in cua chi nhanh.
     *
     * Input:
     * - int|string|null $branchId: Chi nhanh can loc.
     * - array $period: Giu chu ky method hien co, khong dung de dem phong snapshot.
     *
     * Output:
     * - Mang gom available_rooms va warning.
     */
    public function getRoomStatus(int|string|null $branchId, array $period): array
    {
        $sql = <<<'SQL'
            SELECT COUNT(r.room_id) AS available_rooms
            FROM room r
            WHERE r.branch_id = :p_branch_id
              AND r.status = 'AVAILABLE'
            SQL;

        $row = DB::selectOne($sql, ['p_branch_id' => $branchId ?? 0]);
        $availableRooms = (int) ($this->rowValue($row, 'available_rooms') ?? 0);

        return [
            'available_rooms' => $availableRooms,
            'warning' => $this->walkinWarning($availableRooms),
        ];
    }

    /**
     * Return urgent operational task data for the selected branch and reporting period.
     */
    public function getUrgentTaskData(int|string|null $branchId, array $period): array
    {
        // TODO: Return urgent operational task data for the selected branch and reporting period.
        return [];
    }

    /**
     * Return financial risk radar data for the selected branch and reporting period.
     */
    public function getFinancialRiskData(int|string|null $branchId, array $period): array
    {
        // TODO: Return financial risk radar data for the selected branch and reporting period.
        return [];
    }

    /**
     * Return cancelled booking summary data for the selected branch and reporting period.
     */
    public function getCancelledBookingSummary(int|string|null $branchId, array $period): array
    {
        // TODO: Return cancelled booking summary data for the selected branch and reporting period.
        return [];
    }

    /**
     * Return cancelled booking detail data for the selected branch and reporting period.
     */
    public function getCancelledBookingDetails(int|string|null $branchId, array $period): array
    {
        // TODO: Return cancelled booking detail data for the selected branch and reporting period.
        return [];
    }

    /**
     * Return top revenue service data for the selected branch and reporting period.
     */
    public function getTopRevenueServiceData(int|string|null $branchId, array $period): array
    {
        return $this->getTopRevenueServices($branchId ?? 0, $period);
    }

    /**
     * Return revenue structure data for the selected branch and reporting period.
     */
    public function getRevenueStructureData(int|string|null $branchId, array $period): array
    {
        return $this->getRevenueStructure($branchId ?? 0, $period);
    }

    /**
     * Return debt ledger data for the selected branch and reporting period.
     */
    public function getDebtData(int|string|null $branchId, array $period): array
    {
        return $this->getUnpaidInvoices($branchId ?? 0, $period);
    }

    /**
     * Mo ta chuc nang:
     * Dem so pet trong booking phong theo cot ngay check-in hoac check-out.
     *
     * Input:
     * - branchId, period va dateColumn da duoc whitelist.
     *
     * Output:
     * - So pet booking phong trong ky hien tai.
     */
    private function countRoomBookingPetsByDate(int|string|null $branchId, array $period, string $dateColumn): int
    {
        $filters = $this->normalizeFilters($branchId, $period);
        $dateColumn = match ($dateColumn) {
            'checkout_expected_at' => 'checkout_expected_at',
            default => 'checkin_expected_at',
        };

        $sql = <<<SQL
            WITH params AS (
                SELECT
                    :p_branch_id AS branch_id,
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date
                FROM dual
            )
            SELECT COUNT(DISTINCT brp.booking_room_pet_id) AS pet_count
            FROM params prm
            JOIN booking bk
                ON bk.branch_id = prm.branch_id
            JOIN booking_room br
                ON br.booking_id = bk.booking_id
            JOIN booking_room_pet brp
                ON brp.booking_room_id = br.booking_room_id
            WHERE NVL(bk.status, 'PENDING') <> 'CANCELLED'
              AND bk.{$dateColumn} >= prm.start_date
              AND bk.{$dateColumn} <  prm.end_date + 1
            SQL;

        $row = DB::selectOne($sql, $this->currentOracleBindings($branchId, $filters));

        return (int) ($this->rowValue($row, 'pet_count') ?? 0);
    }

    /**
     * Mo ta chuc nang:
     * Tao binding Oracle day du branch va ngay hien tai/ky truoc.
     *
     * Input:
     * - branchId va filters da normalize.
     *
     * Output:
     * - Mang bind cho SQL KPI so sanh ky.
     */
    private function oracleBindings(int|string|null $branchId, array $filters): array
    {
        return [
            'p_branch_id' => $branchId ?? 0,
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
            'p_prev_start_date' => $filters['prev_start_date'],
            'p_prev_end_date' => $filters['prev_end_date'],
        ];
    }

    /**
     * Mo ta chuc nang:
     * Tao binding Oracle cho cac truy van chi can ky hien tai.
     *
     * Input:
     * - branchId va filters da normalize.
     *
     * Output:
     * - Mang bind gom branch, start_date va end_date.
     */
    private function currentOracleBindings(int|string|null $branchId, array $filters): array
    {
        return [
            'p_branch_id' => $branchId ?? 0,
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
        ];
    }

    /**
     * Mo ta chuc nang:
     * Chuyen raw row SQL thanh payload current/previous/growth_percent.
     *
     * Input:
     * - row SQL da lower-case key va prefix KPI.
     *
     * Output:
     * - Payload KPI ro nghia cho frontend.
     */
    private function comparisonPayload(array $row, string $prefix): array
    {
        $current = (int) ($row["{$prefix}_current"] ?? 0);
        $previous = (int) ($row["{$prefix}_previous"] ?? 0);
        $growthPercent = $this->nullableFloat($row["{$prefix}_growth_percent"] ?? null);

        return [
            'current' => $current,
            'previous' => $previous,
            'growth_percent' => $growthPercent,
            'trend' => $this->trend($growthPercent),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Normalize filter Carbon/string/null ve chuoi YYYY-MM-DD truoc khi bind Oracle.
     *
     * Input:
     * - branchId va filters tu DateRangeFilterRequest.
     *
     * Output:
     * - Filters co start_date, end_date, prev_start_date, prev_end_date.
     */
    private function normalizeFilters(int|string|null $branchId, array $filters = []): array
    {
        $startDate = $this->dateString($filters['start_date'] ?? null);
        $endDate = $this->dateString($filters['end_date'] ?? null);
        $prevStartDate = $this->dateString($filters['prev_start_date'] ?? null);
        $prevEndDate = $this->dateString($filters['prev_end_date'] ?? null);

        if ($startDate === null || $endDate === null) {
            $bounds = $this->dashboardDateBounds($branchId);
            $startDate ??= $bounds['start_date'];
            $endDate ??= $bounds['end_date'];
        }

        $filters['start_date'] = $startDate;
        $filters['end_date'] = $endDate;

        if ($prevStartDate === null || $prevEndDate === null) {
            $previousFilters = $this->previousPeriodFilters($filters);
            $prevStartDate ??= $previousFilters['prev_start_date'];
            $prevEndDate ??= $previousFilters['prev_end_date'];
        }

        $filters['prev_start_date'] = $prevStartDate;
        $filters['prev_end_date'] = $prevEndDate;

        return $filters;
    }

    /**
     * Mo ta chuc nang:
     * Tinh ky truoc khi filters chua co prev_start_date hoac prev_end_date.
     *
     * Input:
     * - Filters da co start_date va end_date.
     *
     * Output:
     * - prev_start_date va prev_end_date dang YYYY-MM-DD.
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
     * Lay khoang ngay fallback theo du lieu lich cua chi nhanh.
     *
     * Input:
     * - branchId can loc.
     *
     * Output:
     * - start_date va end_date fallback dang YYYY-MM-DD.
     */
    private function dashboardDateBounds(int|string|null $branchId): array
    {
        try {
            $sql = <<<'SQL'
                WITH params AS (
                    SELECT :p_branch_id AS branch_id
                    FROM dual
                )
                SELECT
                    TO_CHAR(MIN(report_date), 'YYYY-MM-DD') AS start_date,
                    TO_CHAR(MAX(report_date), 'YYYY-MM-DD') AS end_date
                FROM (
                    SELECT bk.checkin_expected_at AS report_date
                    FROM booking bk
                    JOIN params prm
                        ON prm.branch_id = bk.branch_id
                    WHERE bk.checkin_expected_at IS NOT NULL

                    UNION ALL

                    SELECT bk.checkout_expected_at AS report_date
                    FROM booking bk
                    JOIN params prm
                        ON prm.branch_id = bk.branch_id
                    WHERE bk.checkout_expected_at IS NOT NULL

                    UNION ALL

                    SELECT bsp.scheduled_at AS report_date
                    FROM booking_service_pet bsp
                    JOIN booking bk
                        ON bk.booking_id = bsp.booking_id
                    JOIN params prm
                        ON prm.branch_id = bk.branch_id
                    WHERE bsp.scheduled_at IS NOT NULL
                )
                SQL;

            $row = DB::selectOne($sql, ['p_branch_id' => $branchId ?? 0]);
        } catch (Throwable) {
            $row = null;
        }

        $today = CarbonImmutable::today(config('app.timezone'));

        return [
            'start_date' => (string) ($this->rowValue($row, 'start_date') ?? $today->startOfMonth()->toDateString()),
            'end_date' => (string) ($this->rowValue($row, 'end_date') ?? $today->endOfMonth()->toDateString()),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Chuyen gia tri ngay Carbon/string ve chuoi YYYY-MM-DD.
     *
     * Input:
     * - value co the la DateTimeInterface, string hoac null.
     *
     * Output:
     * - Chuoi ngay YYYY-MM-DD hoac null.
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
     * - value ngay bat ky hop le.
     *
     * Output:
     * - CarbonImmutable theo timezone app.
     */
    private function dateFromString(mixed $value): CarbonImmutable
    {
        $date = $this->dateString($value) ?? CarbonImmutable::today(config('app.timezone'))->toDateString();

        return CarbonImmutable::createFromFormat('!Y-m-d', $date, config('app.timezone'));
    }

    /**
     * Mo ta chuc nang:
     * Doc gia tri tu row SQL bat ke key tra ve dang lower-case hay upper-case.
     *
     * Input:
     * - row SQL va key can doc.
     *
     * Output:
     * - Gia tri trong row hoac null.
     */
    private function rowValue(?object $row, string $key): mixed
    {
        if ($row === null) {
            return null;
        }

        return $row->{$key} ?? $row->{strtoupper($key)} ?? null;
    }

    /**
     * Mo ta chuc nang:
     * Ep kieu float nhung giu null cho growth_percent khong co ky truoc.
     *
     * Input:
     * - value tu SQL.
     *
     * Output:
     * - float hoac null.
     */
    private function nullableFloat(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    /**
     * Mo ta chuc nang:
     * Suy ra trend tu growth_percent.
     *
     * Input:
     * - growthPercent co the null.
     *
     * Output:
     * - up, down, neutral hoac no_previous_data.
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
     * Tao noi dung canh bao phong walk-in theo so phong trong.
     *
     * Input:
     * - So phong AVAILABLE cua chi nhanh.
     *
     * Output:
     * - Chuoi canh bao phong walk-in.
     */
    private function walkinWarning(int $availableRooms): string
    {
        if ($availableRooms === 0) {
            return 'HẾT PHÒNG WALK-IN';
        }

        if ($availableRooms <= 5) {
            return 'CẢNH BÁO: SẮP HẾT PHÒNG WALK-IN';
        }

        return 'CÒN PHÒNG';
    }
}
