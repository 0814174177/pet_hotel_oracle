<?php

namespace App\Repositories\Eloquent\Manager;

use App\Repositories\Contracts\Manager\BranchScopedInventoryMaterialRepositoryInterface;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class BranchScopedInventoryMaterialRepository implements BranchScopedInventoryMaterialRepositoryInterface
{
    /**
     * Temporary margin assumption until product cost price is available.
     */
    private const TEMPORARY_MARGIN_ASSUMPTION = 0.65;

    /**
     * Mô tả chức năng:
     * Lấy tổng hợp KPI và danh sách vật tư tồn kho của chi nhánh hiện tại.
     *
     * Input:
     * - int|string $branchId: Mã chi nhánh cần lọc.
     * - array $filters: Bộ lọc ngày và bộ lọc danh sách từ Controller.
     *
     * Output:
     * - Mảng gồm kpi và materials đã chuẩn hóa cho frontend.
     *
     * Ghi chú:
     * - Chỉ tổng hợp API đọc dữ liệu, không xử lý thao tác ghi.
     */
    public function getDashboard(int|string $branchId, array $filters = []): array
    {
        return [
            'kpi' => $this->getInventoryKpi($branchId, $filters),
            'materials' => $this->getMaterialList($branchId, $filters),
        ];
    }

    /**
     * Mô tả chức năng:
     * Lấy các KPI ảnh chụp tồn kho hiện tại của chi nhánh.
     *
     * Input:
     * - int|string $branchId: Mã chi nhánh cần lọc.
     * - array $filters: start_date và end_date từ DateRangeFilterRequest.
     *
     * Output:
     * - Danh sách KPI tổng vật tư, hết hàng, sắp hết, vốn tồn kho và margin tạm tính.
     *
     * Ghi chú:
     * - Schema chưa có lịch sử tồn kho nên KPI không so sánh kỳ trước.
     */
    public function getKpiCards(int|string $branchId, array $filters = []): array
    {
        $snapshot = $this->getKpiSnapshot($branchId, $filters);
        $period = $snapshot['period'];

        return [
            $this->kpiCard(
                'total-materials',
                (int) $snapshot['total_materials'],
                'Vật tư tại chi nhánh hiện tại',
                $period
            ),
            $this->kpiCard(
                'out-of-stock-materials',
                (int) $snapshot['out_of_stock_materials'],
                'Cần nhập bổ sung ngay',
                $period
            ),
            $this->kpiCard(
                'low-stock-materials',
                (int) $snapshot['low_stock_materials'],
                'Bằng hoặc dưới ngưỡng nhập lại',
                $period
            ),
            $this->kpiCard(
                'inventory-capital-value',
                (float) $snapshot['inventory_capital_value'],
                'Giá trị tồn theo đơn giá vật tư',
                $period,
                'currency'
            ),
            $this->kpiCard(
                'margin-assumption',
                (float) $snapshot['margin_percent_assumption'],
                'Giả định tạm thời',
                $period,
                'percent'
            ),
        ];
    }

    /**
     * Mô tả chức năng:
     * Lấy KPI tổng quan tồn kho vật tư của chi nhánh Manager.
     *
     * Input:
     * - int|string $branchId: Mã chi nhánh cần lọc.
     * - array $filters: Bộ lọc ngày từ DateRangeFilterRequest, được normalize để giữ contract API.
     *
     * Output:
     * - Mảng gồm total_materials, out_of_stock_count, low_stock_count,
     * inventory_value, margin_percent_assumption, inventory_warning,
     * warning_level, material_type_comparison và cards.
     *
     * Ghi chú:
     * - SQL Oracle nằm trong Repository, bind p_branch_id và p_margin_assumption.
     * - KPI tồn kho là ảnh chụp hiện tại của chi nhánh, filter ngày chỉ dùng cho period hiển thị.
     */
    public function getInventoryKpi(int|string $branchId, array $filters = []): array
    {
        $snapshot = $this->getKpiSnapshot($branchId, $filters);
        $materialTypeComparison = $this->getMaterialTypeComparison($branchId, $filters);
        $period = $snapshot['period'];
        $outOfStockCount = (int) $snapshot['out_of_stock_count'];
        $lowStockCount = (int) $snapshot['low_stock_count'];
        $warningLevel = $this->inventoryWarningLevel($outOfStockCount, $lowStockCount);

        $cards = [
            $this->kpiCard(
                'total-materials',
                (int) $snapshot['total_materials'],
                'Vật tư đang hoạt động',
                $period,
                'number',
                [
                    'change_percent' => $materialTypeComparison['change_percent'],
                    'diff_amount' => $materialTypeComparison['delta_materials'],
                    'trend' => $materialTypeComparison['trend'],
                ]
            ),
            $this->kpiCard(
                'out-of-stock-materials',
                $outOfStockCount,
                'Cần nhập bổ sung ngay',
                $period
            ),
            $this->kpiCard(
                'low-stock-materials',
                $lowStockCount,
                'Bằng hoặc dưới ngưỡng nhập lại',
                $period
            ),
            $this->kpiCard(
                'inventory-capital-value',
                (float) $snapshot['inventory_value'],
                'Giá trị tồn theo đơn giá vật tư',
                $period,
                'currency'
            ),
            $this->kpiCard(
                'margin-assumption',
                (float) $snapshot['margin_percent_assumption'],
                'Giả định tạm thời',
                $period,
                'percent'
            ),
        ];

        return [
            'total_materials' => (int) $snapshot['total_materials'],
            'out_of_stock_count' => $outOfStockCount,
            'low_stock_count' => $lowStockCount,
            'inventory_value' => (float) $snapshot['inventory_value'],
            'margin_percent_assumption' => (float) $snapshot['margin_percent_assumption'],
            'inventory_warning' => (string) $snapshot['inventory_warning'],
            'warning_level' => $warningLevel,
            'material_type_comparison' => $materialTypeComparison,
            'period' => $period,
            'cards' => $cards,
        ];
    }

    /**
     * Mô tả chức năng:
     * So sánh số loại vật tư đang hoạt động của chi nhánh với kỳ trước.
     *
     * Input:
     * - int|string $branchId: Mã chi nhánh cần lọc.
     * - array $filters: start_date, end_date, prev_start_date, prev_end_date từ DateRangeFilterRequest.
     *
     * Output:
     * - total_this_period, total_previous_period, delta_materials,
     * change_percent và trend.
     *
     * Ghi chú:
     * - SQL Oracle bind p_branch_id, p_end_date và p_prev_end_date.
     * - Vật tư của chi nhánh được xác định qua branch_inventory.
     */
    public function getMaterialTypeComparison(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :p_branch_id AS branch_id,
                    TO_DATE(:p_end_date, 'YYYY-MM-DD') AS end_date,
                    TO_DATE(:p_prev_end_date, 'YYYY-MM-DD') AS prev_end_date
                FROM dual
            )
            SELECT
                (
                    SELECT COUNT(DISTINCT p.product_id)
                    FROM product p
                    JOIN branch_inventory bi
                        ON bi.product_id = p.product_id
                    JOIN params prm
                        ON 1 = 1
                    WHERE NVL(p.is_active, 0) = 1
                      AND bi.branch_id = prm.branch_id
                      AND p.created_at < prm.end_date
                ) AS total_this_period,
                (
                    SELECT COUNT(DISTINCT p.product_id)
                    FROM product p
                    JOIN branch_inventory bi
                        ON bi.product_id = p.product_id
                    JOIN params prm
                        ON 1 = 1
                    WHERE NVL(p.is_active, 0) = 1
                      AND bi.branch_id = prm.branch_id
                      AND p.created_at < prm.prev_end_date
                ) AS total_previous_period
            FROM dual
            SQL;

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_branch_id' => $branchId,
            'p_end_date' => $filters['end_date'],
            'p_prev_end_date' => $filters['prev_end_date'],
        ]), CASE_LOWER);

        $totalThisPeriod = (int) ($row['total_this_period'] ?? 0);
        $totalPreviousPeriod = (int) ($row['total_previous_period'] ?? 0);
        $deltaMaterials = $totalThisPeriod - $totalPreviousPeriod;
        $changePercent = $this->comparisonChangePercent($deltaMaterials, $totalThisPeriod, $totalPreviousPeriod);

        return [
            'total_this_period' => $totalThisPeriod,
            'total_previous_period' => $totalPreviousPeriod,
            'delta_materials' => $deltaMaterials,
            'change_percent' => $changePercent,
            'trend' => $this->trend($deltaMaterials),
        ];
    }

    /**
     * Mô tả chức năng:
     * Lấy tổng số loại vật tư đang được quản lý tại chi nhánh.
     */
    public function getTotalMaterialCount(int|string $branchId, array $filters = []): array
    {
        $snapshot = $this->getKpiSnapshot($branchId, $filters);

        return [
            'value' => (int) $snapshot['total_materials'],
            'period' => $snapshot['period'],
        ];
    }

    /**
     * Mô tả chức năng:
     * Lấy số loại vật tư đã hết hàng tại chi nhánh.
     */
    public function getOutOfStockCount(int|string $branchId, array $filters = []): array
    {
        $snapshot = $this->getKpiSnapshot($branchId, $filters);

        return [
            'value' => (int) $snapshot['out_of_stock_materials'],
            'period' => $snapshot['period'],
        ];
    }

    /**
     * Mô tả chức năng:
     * Lấy danh sách vật tư hết hàng cần nhập ngay theo chi nhánh.
     *
     * Input:
     * - int|string $branchId: Mã chi nhánh cần lọc.
     * - array $filters: Bộ lọc ngày từ DashboardEngine, giữ contract API.
     *
     * Output:
     * - Danh sách vật tư hết hàng với warning_text và status_level danger.
     *
     * Ghi chú:
     * - SQL Oracle đặt trong Repository và bind p_branch_id.
     * - Bắt buộc lọc bi.branch_id = :p_branch_id và p.is_active = 1.
     */
    public function getOutOfStockMaterials(int|string $branchId, array $filters = []): array
    {
        $sql = <<<'SQL'
            SELECT
                p.product_id,
                p.product_name,
                cp.product_category_name,
                bi.quantity_in_stock,
                bi.reorder_point,
                p.unit,
                TO_CHAR(bi.last_updated, 'YYYY-MM-DD HH24:MI:SS') AS last_updated,
                'CẦN NHẬP NGAY' AS warning_text
            FROM branch_inventory bi
            JOIN product p
                ON p.product_id = bi.product_id
            JOIN category_product cp
                ON cp.product_category_id = p.product_category_id
            WHERE bi.branch_id = :p_branch_id
              AND bi.quantity_in_stock = 0
              AND p.is_active = 1
            ORDER BY p.product_name
            SQL;

        return array_map(static function (object $row): array {
            $row = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'product_id' => (int) ($row['product_id'] ?? 0),
                'product_name' => (string) ($row['product_name'] ?? ''),
                'product_category_name' => (string) ($row['product_category_name'] ?? ''),
                'quantity_in_stock' => (int) ($row['quantity_in_stock'] ?? 0),
                'reorder_point' => (int) ($row['reorder_point'] ?? 0),
                'unit' => (string) ($row['unit'] ?? '-'),
                'last_updated' => (string) ($row['last_updated'] ?? ''),
                'warning_text' => (string) ($row['warning_text'] ?? 'CẦN NHẬP NGAY'),
                'status_level' => 'danger',
            ];
        }, DB::select($sql, [
            'p_branch_id' => $branchId,
        ]));
    }

    /**
     * Mô tả chức năng:
     * Lấy danh sách vật tư còn hàng nhưng bằng hoặc dưới ngưỡng nhập lại.
     *
     * Input:
     * - int|string $branchId: Mã chi nhánh cần lọc.
     * - array $filters: Bộ lọc ngày từ DashboardEngine, giữ contract API.
     *
     * Output:
     * - Danh sách vật tư sắp hết với warning_text và status_level warning.
     *
     * Ghi chú:
     * - SQL Oracle đặt trong Repository và bind p_branch_id.
     * - Bắt buộc lọc bi.branch_id = :p_branch_id và p.is_active = 1.
     */
    public function getLowStockMaterials(int|string $branchId, array $filters = []): array
    {
        $sql = <<<'SQL'
            SELECT
                p.product_id,
                p.product_name,
                cp.product_category_name,
                NVL(bi.quantity_in_stock, 0) AS quantity_in_stock,
                NVL(bi.reorder_point, 0) AS reorder_point,
                NVL(p.unit, '-') AS unit,
                TO_CHAR(bi.last_updated, 'YYYY-MM-DD HH24:MI:SS') AS last_updated,
                'SẮP HẾT' AS warning_text
            FROM branch_inventory bi
            JOIN product p
                ON p.product_id = bi.product_id
            JOIN category_product cp
                ON cp.product_category_id = p.product_category_id
            WHERE bi.branch_id = :p_branch_id
              AND NVL(bi.quantity_in_stock, 0) > 0
              AND NVL(bi.quantity_in_stock, 0) <= NVL(bi.reorder_point, 0)
              AND p.is_active = 1
            ORDER BY NVL(bi.quantity_in_stock, 0) ASC, p.product_name
            SQL;

        return array_map(static function (object $row): array {
            $row = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'product_id' => (int) ($row['product_id'] ?? 0),
                'product_name' => (string) ($row['product_name'] ?? ''),
                'product_category_name' => (string) ($row['product_category_name'] ?? ''),
                'quantity_in_stock' => (float) ($row['quantity_in_stock'] ?? 0),
                'reorder_point' => (float) ($row['reorder_point'] ?? 0),
                'unit' => (string) ($row['unit'] ?? '-'),
                'last_updated' => (string) ($row['last_updated'] ?? ''),
                'warning_text' => (string) ($row['warning_text'] ?? 'SẮP HẾT'),
                'status_level' => 'warning',
            ];
        }, DB::select($sql, [
            'p_branch_id' => $branchId,
        ]));
    }

    /**
     * Mô tả chức năng:
     * Lấy số loại vật tư còn hàng nhưng bằng hoặc dưới ngưỡng nhập lại.
     */
    public function getLowStockCount(int|string $branchId, array $filters = []): array
    {
        $snapshot = $this->getKpiSnapshot($branchId, $filters);

        return [
            'value' => (int) $snapshot['low_stock_materials'],
            'period' => $snapshot['period'],
        ];
    }

    /**
     * Mô tả chức năng:
     * Lấy giá trị vốn tồn kho hiện tại theo đơn giá vật tư.
     */
    public function getInventoryCapitalValue(int|string $branchId, array $filters = []): array
    {
        $snapshot = $this->getKpiSnapshot($branchId, $filters);

        return [
            'value' => (float) $snapshot['inventory_capital_value'],
            'period' => $snapshot['period'],
        ];
    }

    /**
     * Mô tả chức năng:
     * Lấy vốn tồn kho hiện tại theo nhóm vật tư của chi nhánh.
     *
     * Input:
     * - int|string $branchId: Mã chi nhánh cần lọc.
     * - array $filters: Bộ lọc ngày từ DashboardEngine, giữ contract API.
     *
     * Output:
     * - Danh sách nhóm vật tư với material_count, total_quantity và inventory_value.
     *
     * Ghi chú:
     * - SQL Oracle đặt trong Repository và bind p_branch_id.
     * - Giá trị tồn kho tính bằng quantity_in_stock * item_price.
     */
    public function getInventoryValueByCategory(int|string $branchId, array $filters = []): array
    {
        $sql = <<<'SQL'
            SELECT
                cp.product_category_name,
                COUNT(p.product_id) AS material_count,
                NVL(SUM(NVL(bi.quantity_in_stock, 0)), 0) AS total_quantity,
                NVL(SUM(NVL(bi.quantity_in_stock, 0) * NVL(p.item_price, 0)), 0) AS inventory_value
            FROM branch_inventory bi
            JOIN product p
                ON p.product_id = bi.product_id
            JOIN category_product cp
                ON cp.product_category_id = p.product_category_id
            WHERE bi.branch_id = :p_branch_id
              AND p.is_active = 1
            GROUP BY cp.product_category_name
            ORDER BY inventory_value DESC
            SQL;

        return array_map(static function (object $row): array {
            $row = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'product_category_name' => (string) ($row['product_category_name'] ?? ''),
                'material_count' => (int) ($row['material_count'] ?? 0),
                'total_quantity' => (float) ($row['total_quantity'] ?? 0),
                'inventory_value' => (float) ($row['inventory_value'] ?? 0),
            ];
        }, DB::select($sql, [
            'p_branch_id' => $branchId,
        ]));
    }

    /**
     * Mô tả chức năng:
     * Lấy danh sách vật tư tồn kho của chi nhánh hiện tại.
     *
     * Input:
     * - int|string $branchId: Mã chi nhánh cần lọc.
     * - array $filters: start_date, end_date, search, group và status.
     *
     * Output:
     * - Danh sách vật tư với key rõ nghĩa cho bảng quản trị.
     *
     * Ghi chú:
     * - Giữ contract cũ bằng cách chuyển sang getMaterialsByBranch.
     */
    public function getMaterialList(int|string $branchId, array $filters = []): array
    {
        return $this->getMaterialsByBranch($branchId, $filters);
    }

    /**
     * Mô tả chức năng:
     * Lấy danh sách vật tư theo chi nhánh để render table Manager inventory.
     *
     * Input:
     * - int|string $branchId: Mã chi nhánh cần lọc.
     * - array $filters: start_date, end_date, search, group và status.
     *
     * Output:
     * - Danh sách vật tư với product_id, material_code, stock_status,
     * warning_text, stock_vs_threshold và status_level.
     *
     * Ghi chú:
     * - SQL Oracle đặt trong Repository và bind p_branch_id.
     * - Vật tư của chi nhánh bắt buộc lọc bằng bi.branch_id = :p_branch_id.
     */
    public function getMaterialsByBranch(int|string $branchId, array $filters = []): array
    {
        $sql = <<<'SQL'
            SELECT
                bi.branch_inventory_id AS material_id,
                p.product_id,
                'VT' || LPAD(TO_CHAR(p.product_id), 3, '0') AS material_code,
                p.product_name,
                cp.product_category_name,
                NVL(p.unit, '-') AS unit,
                NVL(p.item_price, 0) AS item_price,
                NVL(bi.quantity_in_stock, 0) AS quantity_in_stock,
                NVL(bi.reorder_point, 0) AS reorder_point,
                TO_CHAR(bi.last_updated, 'YYYY-MM-DD HH24:MI:SS') AS last_updated,
                TO_CHAR(bi.updated_at, 'YYYY-MM-DD HH24:MI:SS') AS updated_at,
                CASE
                    WHEN NVL(bi.quantity_in_stock, 0) = 0 THEN 'HẾT HÀNG'
                    WHEN NVL(bi.quantity_in_stock, 0) > 0
                     AND NVL(bi.quantity_in_stock, 0) <= NVL(bi.reorder_point, 0) THEN 'SẮP HẾT'
                    ELSE 'HOẠT ĐỘNG'
                END AS stock_status,
                CASE
                    WHEN NVL(bi.quantity_in_stock, 0) = 0 THEN 'CẦN NHẬP NGAY'
                    WHEN NVL(bi.quantity_in_stock, 0) > 0
                     AND NVL(bi.quantity_in_stock, 0) <= NVL(bi.reorder_point, 0) THEN 'TRONG NGƯỠNG CẢNH BÁO'
                    ELSE 'AN TOÀN'
                END AS warning_text,
                NVL(bi.quantity_in_stock, 0) || '/' || NVL(bi.reorder_point, 0) AS stock_vs_threshold,
                CASE
                    WHEN NVL(bi.quantity_in_stock, 0) = 0 THEN 'danger'
                    WHEN NVL(bi.quantity_in_stock, 0) > 0
                     AND NVL(bi.quantity_in_stock, 0) <= NVL(bi.reorder_point, 0) THEN 'warning'
                    ELSE 'safe'
                END AS status_level
            FROM branch_inventory bi
            JOIN product p
                ON p.product_id = bi.product_id
            JOIN category_product cp
                ON cp.product_category_id = p.product_category_id
            WHERE bi.branch_id = :p_branch_id
              AND p.is_active = 1
            ORDER BY
                CASE
                    WHEN NVL(bi.quantity_in_stock, 0) = 0 THEN 1
                    WHEN NVL(bi.quantity_in_stock, 0) > 0
                     AND NVL(bi.quantity_in_stock, 0) <= NVL(bi.reorder_point, 0) THEN 2
                    ELSE 3
                END,
                p.product_name
            SQL;

        return array_map(static function (object $row): array {
            $row = array_change_key_case((array) $row, CASE_LOWER);
            $quantityInStock = (int) ($row['quantity_in_stock'] ?? 0);
            $reorderPoint = (int) ($row['reorder_point'] ?? 0);
            $statusLevel = (string) ($row['status_level'] ?? 'safe');

            return [
                'material_id' => (int) ($row['material_id'] ?? 0),
                'product_id' => (int) ($row['product_id'] ?? 0),
                'material_code' => (string) ($row['material_code'] ?? ''),
                'product_name' => (string) ($row['product_name'] ?? ''),
                'product_category_name' => (string) ($row['product_category_name'] ?? ''),
                'unit' => (string) ($row['unit'] ?? '-'),
                'item_price' => (float) ($row['item_price'] ?? 0),
                'quantity_in_stock' => $quantityInStock,
                'reorder_point' => $reorderPoint,
                'last_updated' => (string) ($row['last_updated'] ?? ''),
                'updated_at' => (string) ($row['updated_at'] ?? ''),
                'stock_status' => (string) ($row['stock_status'] ?? 'HOẠT ĐỘNG'),
                'warning_text' => (string) ($row['warning_text'] ?? 'AN TOÀN'),
                'stock_vs_threshold' => (string) ($row['stock_vs_threshold'] ?? "{$quantityInStock}/{$reorderPoint}"),
                'status_level' => $statusLevel,
                'material_name' => (string) ($row['product_name'] ?? ''),
                'material_group' => (string) ($row['product_category_name'] ?? ''),
                'status' => $statusLevel,
                'status_label' => (string) ($row['stock_status'] ?? 'HOẠT ĐỘNG'),
                'current_stock' => $quantityInStock,
            ];
        }, DB::select($sql, [
            'p_branch_id' => $branchId,
        ]));
    }

    /**
     * Find inventory material detail for the selected branch.
     */
    public function findMaterialDetail(int|string $branchId, int|string $materialId): ?array
    {
        // TODO: Load material detail, current stock, supplier, and import/export history when available.
        return null;
    }

    /**
     * Mô tả chức năng:
     * Chuẩn hóa kỳ báo cáo về start_date và end_date dạng YYYY-MM-DD.
     */
    public function resolvePeriodRange(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        return [
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
        ];
    }

    /**
     * Mô tả chức năng:
     * Xác định trạng thái hiển thị của vật tư từ tồn hiện tại và ngưỡng nhập lại.
     */
    public function resolveMaterialStatus(
        float|int|null $currentStock,
        float|int|null $threshold,
        ?string $importStatus = null
    ): string {
        if ((float) ($currentStock ?? 0) <= 0) {
            return 'out';
        }

        if ((float) $currentStock <= (float) ($threshold ?? 0)) {
            return 'low';
        }

        return 'normal';
    }

    /**
     * Mô tả chức năng:
     * Truy vấn một lần để lấy ảnh chụp KPI tồn kho của chi nhánh.
     *
     * Input:
     * - int|string $branchId: Mã chi nhánh cần lọc.
     * - array $filters: start_date và end_date cần normalize trước khi bind.
     *
     * Output:
     * - Mảng số liệu KPI và kỳ báo cáo đã chuẩn hóa.
     *
     * Ghi chú:
     * - SQL Oracle đặt trong Repository và bind branch/date/margin đầy đủ.
     */
    private function getKpiSnapshot(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :p_branch_id AS branch_id,
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date,
                    :p_margin_assumption AS margin_assumption
                FROM dual
            )
            SELECT
                COUNT(DISTINCT p.product_id) AS total_materials,
                NVL(SUM(CASE
                    WHEN p.product_id IS NOT NULL
                     AND NVL(bi.quantity_in_stock, 0) = 0 THEN 1
                    ELSE 0
                END), 0) AS out_of_stock_count,
                NVL(SUM(CASE
                    WHEN p.product_id IS NOT NULL
                     AND NVL(bi.quantity_in_stock, 0) > 0
                     AND NVL(bi.quantity_in_stock, 0) <= NVL(bi.reorder_point, 0) THEN 1
                    ELSE 0
                END), 0) AS low_stock_count,
                NVL(SUM(
                    NVL(bi.quantity_in_stock, 0) * NVL(p.item_price, 0)
                ), 0) AS inventory_value,
                ROUND(MAX(prm.margin_assumption) * 100, 2) AS margin_percent_assumption,
                CASE
                    WHEN NVL(SUM(CASE
                        WHEN p.product_id IS NOT NULL
                         AND NVL(bi.quantity_in_stock, 0) = 0 THEN 1
                        ELSE 0
                    END), 0) > 0 THEN 'CẢNH BÁO ĐỎ: CÓ VẬT TƯ HẾT HÀNG - CẦN NHẬP NGAY'
                    WHEN NVL(SUM(CASE
                        WHEN p.product_id IS NOT NULL
                         AND NVL(bi.quantity_in_stock, 0) > 0
                         AND NVL(bi.quantity_in_stock, 0) <= NVL(bi.reorder_point, 0) THEN 1
                        ELSE 0
                    END), 0) > 0 THEN 'CẢNH BÁO VÀNG: CÓ VẬT TƯ SẮP HẾT'
                    ELSE 'AN TOÀN'
                END AS inventory_warning,
                TO_CHAR(prm.start_date, 'YYYY-MM-DD') AS report_start_date,
                TO_CHAR(prm.end_date, 'YYYY-MM-DD') AS report_end_date
            FROM params prm
            LEFT JOIN branch_inventory bi
                ON bi.branch_id = prm.branch_id
            LEFT JOIN product p
                ON p.product_id = bi.product_id
               AND NVL(p.is_active, 0) = 1
            GROUP BY prm.start_date, prm.end_date
            SQL;

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_branch_id' => $branchId,
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
            'p_margin_assumption' => self::TEMPORARY_MARGIN_ASSUMPTION,
        ]), CASE_LOWER);

        $outOfStockCount = (int) ($row['out_of_stock_count'] ?? 0);
        $lowStockCount = (int) ($row['low_stock_count'] ?? 0);
        $inventoryValue = (float) ($row['inventory_value'] ?? 0);

        return [
            'total_materials' => (int) ($row['total_materials'] ?? 0),
            'out_of_stock_count' => $outOfStockCount,
            'low_stock_count' => $lowStockCount,
            'inventory_value' => $inventoryValue,
            'margin_percent_assumption' => (float) ($row['margin_percent_assumption'] ?? self::TEMPORARY_MARGIN_ASSUMPTION * 100),
            'inventory_warning' => (string) ($row['inventory_warning'] ?? 'AN TOÀN'),
            'out_of_stock_materials' => $outOfStockCount,
            'low_stock_materials' => $lowStockCount,
            'inventory_capital_value' => $inventoryValue,
            'period' => [
                'start_date' => (string) ($row['report_start_date'] ?? $filters['start_date']),
                'end_date' => (string) ($row['report_end_date'] ?? $filters['end_date']),
            ],
        ];
    }

    /**
     * Mô tả chức năng:
     * Tạo cấu trúc KPI thống nhất cho DashboardKpiAdapter.
     */
    private function kpiCard(
        string $key,
        float|int $value,
        string $trend,
        array $period,
        string $valueType = 'number',
        ?array $comparison = null
    ): array {
        return [
            'key' => $key,
            'value' => $value,
            'value_type' => $valueType,
            'trend' => $trend,
            'comparison' => $comparison,
            'period' => $period,
        ];
    }

    /**
     * Mô tả chức năng:
     * Tính phần trăm thay đổi của số loại vật tư so với kỳ trước.
     *
     * Input:
     * - int $deltaMaterials: Chênh lệch số loại vật tư.
     * - int $totalThisPeriod: Tổng số loại vật tư kỳ hiện tại.
     * - int $totalPreviousPeriod: Tổng số loại vật tư kỳ trước.
     *
     * Output:
     * - Phần trăm thay đổi đã làm tròn 2 chữ số.
     */
    private function comparisonChangePercent(
        int $deltaMaterials,
        int $totalThisPeriod,
        int $totalPreviousPeriod
    ): float {
        if ($totalPreviousPeriod > 0) {
            return round($deltaMaterials / $totalPreviousPeriod * 100, 2);
        }

        return $totalThisPeriod > 0 ? 100.0 : 0.0;
    }

    /**
     * Mô tả chức năng:
     * Suy ra xu hướng tăng/giảm/không đổi từ chênh lệch số loại vật tư.
     *
     * Input:
     * - int $delta: Chênh lệch số lượng.
     *
     * Output:
     * - up, down hoặc neutral.
     */
    private function trend(int $delta): string
    {
        if ($delta > 0) {
            return 'up';
        }

        if ($delta < 0) {
            return 'down';
        }

        return 'neutral';
    }

    /**
     * Mô tả chức năng:
     * Xác định mức cảnh báo tổng quan của tồn kho chi nhánh.
     *
     * Input:
     * - int $outOfStockCount: Số vật tư hết hàng.
     * - int $lowStockCount: Số vật tư sắp hết.
     *
     * Output:
     * - danger, warning hoặc safe.
     */
    private function inventoryWarningLevel(int $outOfStockCount, int $lowStockCount): string
    {
        if ($outOfStockCount > 0) {
            return 'danger';
        }

        if ($lowStockCount > 0) {
            return 'warning';
        }

        return 'safe';
    }

    /**
     * Mô tả chức năng:
     * Normalize filter ngày và filter chuỗi trước khi bind Oracle.
     */
    private function normalizeFilters(array $filters = []): array
    {
        $currentMonth = $this->currentMonthFilters();
        $startDate = $this->dateString($filters['start_date'] ?? null) ?? $currentMonth['start_date'];
        $endDate = $this->dateString($filters['end_date'] ?? null) ?? $currentMonth['end_date'];
        $previousPeriod = $this->previousPeriodFilters($startDate, $endDate);

        return [
            ...$filters,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'prev_start_date' => $this->dateString($filters['prev_start_date'] ?? null) ?? $previousPeriod['prev_start_date'],
            'prev_end_date' => $this->dateString($filters['prev_end_date'] ?? null) ?? $previousPeriod['prev_end_date'],
            'search' => $this->nullableString($filters['search'] ?? null),
            'group' => $this->nullableString($filters['group'] ?? null),
            'status' => $this->nullableString($filters['status'] ?? null),
        ];
    }

    /**
     * Mô tả chức năng:
     * Tạo kỳ mặc định là tháng hiện tại khi frontend chưa chọn ngày.
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
     * Mô tả chức năng:
     * Tính kỳ trước có cùng độ dài với kỳ hiện tại.
     *
     * Input:
     * - string $startDate: Ngày bắt đầu kỳ hiện tại dạng YYYY-MM-DD.
     * - string $endDate: Ngày kết thúc kỳ hiện tại dạng YYYY-MM-DD.
     *
     * Output:
     * - prev_start_date và prev_end_date dạng YYYY-MM-DD.
     */
    private function previousPeriodFilters(string $startDate, string $endDate): array
    {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $startDate, config('app.timezone'));
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $endDate, config('app.timezone'));
        $daysInPeriod = abs((int) $start->diffInDays($end)) + 1;
        $previousEnd = $start->subDay();

        return [
            'prev_start_date' => $previousEnd->subDays($daysInPeriod - 1)->toDateString(),
            'prev_end_date' => $previousEnd->toDateString(),
        ];
    }

    /**
     * Mô tả chức năng:
     * Chuyển Carbon/string/null về chuỗi ngày YYYY-MM-DD để bind Oracle.
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
     * Chuẩn hóa filter text rỗng thành null trước khi bind Oracle.
     */
    private function nullableString(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}