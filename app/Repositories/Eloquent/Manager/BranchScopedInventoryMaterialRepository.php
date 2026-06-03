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
     * Mo ta chuc nang:
     * Lay tong hop KPI va danh sach vat tu ton kho cua chi nhanh hien tai.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $filters: Bo loc ngay va bo loc danh sach tu Controller.
     *
     * Output:
     * - Mang gom kpi va materials da chuan hoa cho frontend.
     *
     * Ghi chu:
     * - Chi tong hop API doc du lieu, khong xu ly thao tac ghi.
     */
    public function getDashboard(int|string $branchId, array $filters = []): array
    {
        return [
            'kpi' => $this->getInventoryKpi($branchId, $filters),
            'materials' => $this->getMaterialList($branchId, $filters),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay cac KPI anh chup ton kho hien tai cua chi nhanh.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $filters: start_date va end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Danh sach KPI tong vat tu, het hang, sap het, von ton kho va margin tam tinh.
     *
     * Ghi chu:
     * - Schema chua co lich su ton kho nen KPI khong so sanh ky truoc.
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
                'Gia dinh tam thoi',
                $period,
                'percent'
            ),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay KPI tong quan ton kho vat tu cua chi nhanh Manager.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $filters: Bo loc ngay tu DateRangeFilterRequest, duoc normalize de giu contract API.
     *
     * Output:
     * - Mang gom total_materials, out_of_stock_count, low_stock_count,
     *   inventory_value, margin_percent_assumption, inventory_warning,
     *   warning_level, material_type_comparison va cards.
     *
     * Ghi chu:
     * - SQL Oracle nam trong Repository, bind p_branch_id va p_margin_assumption.
     * - KPI ton kho la anh chup hien tai cua chi nhanh, filter ngay chi dung cho period hien thi.
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
                'Vat tu dang hoat dong',
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
                'Can nhap bo sung ngay',
                $period
            ),
            $this->kpiCard(
                'low-stock-materials',
                $lowStockCount,
                'Bang hoac duoi nguong nhap lai',
                $period
            ),
            $this->kpiCard(
                'inventory-capital-value',
                (float) $snapshot['inventory_value'],
                'Gia tri ton theo don gia vat tu',
                $period,
                'currency'
            ),
            $this->kpiCard(
                'margin-assumption',
                (float) $snapshot['margin_percent_assumption'],
                'Gia dinh tam thoi',
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
     * Mo ta chuc nang:
     * So sanh so loai vat tu dang hoat dong cua chi nhanh voi ky truoc.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $filters: start_date, end_date, prev_start_date, prev_end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - total_this_period, total_previous_period, delta_materials,
     *   change_percent va trend.
     *
     * Ghi chu:
     * - SQL Oracle bind p_branch_id, p_end_date va p_prev_end_date.
     * - Vat tu cua chi nhanh duoc xac dinh qua branch_inventory.
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
     * Mo ta chuc nang:
     * Lay tong so loai vat tu dang duoc quan ly tai chi nhanh.
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
     * Mo ta chuc nang:
     * Lay so loai vat tu da het hang tai chi nhanh.
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
     * Mo ta chuc nang:
     * Lay danh sach vat tu het hang can nhap ngay theo chi nhanh.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $filters: Bo loc ngay tu DashboardEngine, giu contract API.
     *
     * Output:
     * - Danh sach vat tu het hang voi warning_text va status_level danger.
     *
     * Ghi chu:
     * - SQL Oracle dat trong Repository va bind p_branch_id.
     * - Bat buoc loc bi.branch_id = :p_branch_id va p.is_active = 1.
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
     * Mo ta chuc nang:
     * Lay danh sach vat tu con hang nhung bang hoac duoi nguong nhap lai.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $filters: Bo loc ngay tu DashboardEngine, giu contract API.
     *
     * Output:
     * - Danh sach vat tu sap het voi warning_text va status_level warning.
     *
     * Ghi chu:
     * - SQL Oracle dat trong Repository va bind p_branch_id.
     * - Bat buoc loc bi.branch_id = :p_branch_id va p.is_active = 1.
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
     * Mo ta chuc nang:
     * Lay so loai vat tu con hang nhung bang hoac duoi nguong nhap lai.
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
     * Mo ta chuc nang:
     * Lay gia tri von ton kho hien tai theo don gia vat tu.
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
     * Mo ta chuc nang:
     * Lay von ton kho hien tai theo nhom vat tu cua chi nhanh.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $filters: Bo loc ngay tu DashboardEngine, giu contract API.
     *
     * Output:
     * - Danh sach nhom vat tu voi material_count, total_quantity va inventory_value.
     *
     * Ghi chu:
     * - SQL Oracle dat trong Repository va bind p_branch_id.
     * - Gia tri ton kho tinh bang quantity_in_stock * item_price.
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
     * Mo ta chuc nang:
     * Lay danh sach vat tu ton kho cua chi nhanh hien tai.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $filters: start_date, end_date, search, group va status.
     *
     * Output:
     * - Danh sach vat tu voi key ro nghia cho bang quan tri.
     *
     * Ghi chu:
     * - Giu contract cu bang cach chuyen sang getMaterialsByBranch.
     */
    public function getMaterialList(int|string $branchId, array $filters = []): array
    {
        return $this->getMaterialsByBranch($branchId, $filters);
    }

    /**
     * Mo ta chuc nang:
     * Lay danh sach vat tu theo chi nhanh de render table Manager inventory.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $filters: start_date, end_date, search, group va status.
     *
     * Output:
     * - Danh sach vat tu voi product_id, material_code, stock_status,
     *   warning_text, stock_vs_threshold va status_level.
     *
     * Ghi chu:
     * - SQL Oracle dat trong Repository va bind p_branch_id.
     * - Vat tu cua chi nhanh bat buoc loc bang bi.branch_id = :p_branch_id.
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
     * Mo ta chuc nang:
     * Chuan hoa ky bao cao ve start_date va end_date dang YYYY-MM-DD.
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
     * Mo ta chuc nang:
     * Xac dinh trang thai hien thi cua vat tu tu ton hien tai va nguong nhap lai.
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
     * Mo ta chuc nang:
     * Truy van mot lan de lay anh chup KPI ton kho cua chi nhanh.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $filters: start_date va end_date can normalize truoc khi bind.
     *
     * Output:
     * - Mang so lieu KPI va ky bao cao da chuan hoa.
     *
     * Ghi chu:
     * - SQL Oracle dat trong Repository va bind branch/date/margin day du.
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
                    END), 0) > 0 THEN 'CANH BAO DO: CO VAT TU HET HANG - CAN NHAP NGAY'
                    WHEN NVL(SUM(CASE
                        WHEN p.product_id IS NOT NULL
                         AND NVL(bi.quantity_in_stock, 0) > 0
                         AND NVL(bi.quantity_in_stock, 0) <= NVL(bi.reorder_point, 0) THEN 1
                        ELSE 0
                    END), 0) > 0 THEN 'CANH BAO VANG: CO VAT TU SAP HET'
                    ELSE 'AN TOAN'
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
            'inventory_warning' => (string) ($row['inventory_warning'] ?? 'AN TOAN'),
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
     * Mo ta chuc nang:
     * Tao cau truc KPI thong nhat cho DashboardKpiAdapter.
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
     * Mo ta chuc nang:
     * Tinh phan tram thay doi cua so loai vat tu so voi ky truoc.
     *
     * Input:
     * - int $deltaMaterials: Chenh lech so loai vat tu.
     * - int $totalThisPeriod: Tong so loai vat tu ky hien tai.
     * - int $totalPreviousPeriod: Tong so loai vat tu ky truoc.
     *
     * Output:
     * - Phan tram thay doi da lam tron 2 chu so.
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
     * Mo ta chuc nang:
     * Suy ra xu huong tang/giam/khong doi tu chenh lech so loai vat tu.
     *
     * Input:
     * - int $delta: Chenh lech so luong.
     *
     * Output:
     * - up, down hoac neutral.
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
     * Mo ta chuc nang:
     * Xac dinh muc canh bao tong quan cua ton kho chi nhanh.
     *
     * Input:
     * - int $outOfStockCount: So vat tu het hang.
     * - int $lowStockCount: So vat tu sap het.
     *
     * Output:
     * - danger, warning hoac safe.
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
     * Mo ta chuc nang:
     * Normalize filter ngay va filter chuoi truoc khi bind Oracle.
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
     * Mo ta chuc nang:
     * Tao ky mac dinh la thang hien tai khi frontend chua chon ngay.
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
     * Tinh ky truoc co cung do dai voi ky hien tai.
     *
     * Input:
     * - string $startDate: Ngay bat dau ky hien tai dang YYYY-MM-DD.
     * - string $endDate: Ngay ket thuc ky hien tai dang YYYY-MM-DD.
     *
     * Output:
     * - prev_start_date va prev_end_date dang YYYY-MM-DD.
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
     * Mo ta chuc nang:
     * Chuyen Carbon/string/null ve chuoi ngay YYYY-MM-DD de bind Oracle.
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
     * Chuan hoa filter text rong thanh null truoc khi bind Oracle.
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
