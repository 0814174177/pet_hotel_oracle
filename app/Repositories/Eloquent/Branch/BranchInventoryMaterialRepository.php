<?php

namespace App\Repositories\Eloquent\Branch;

use App\Repositories\Contracts\Branch\BranchInventoryMaterialRepositoryInterface;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class BranchInventoryMaterialRepository implements BranchInventoryMaterialRepositoryInterface
{
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
            'kpi' => $this->getKpiCards($branchId, $filters),
            'materials' => $this->getMaterialList($branchId, $filters),
        ];
    }

    /**
     * Mo ta chuc nang:
     * Lay bon KPI anh chup ton kho hien tai cua chi nhanh.
     *
     * Input:
     * - int|string $branchId: Ma chi nhanh can loc.
     * - array $filters: start_date va end_date tu DateRangeFilterRequest.
     *
     * Output:
     * - Danh sach KPI tong vat tu, het hang, sap het va von ton kho.
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
     * - SQL Oracle bind p_branch_id, p_start_date, p_end_date va bo loc danh sach.
     * - Ngay bao cao duoc bind de giu cung contract DashboardEngine; so luong la anh chup hien tai.
     */
    public function getMaterialList(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :p_branch_id AS branch_id,
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date,
                    :p_search AS search_text,
                    :p_group AS group_name,
                    :p_status AS status_code
                FROM dual
            ),
            inventory_materials AS (
                SELECT
                    bi.branch_inventory_id AS material_id,
                    p.product_id,
                    'VT' || LPAD(TO_CHAR(p.product_id), 3, '0') AS material_code,
                    p.product_name AS material_name,
                    cp.product_category_name AS material_group,
                    NVL(bi.quantity_in_stock, 0) AS current_stock,
                    NVL(bi.reorder_point, 0) AS reorder_point,
                    NVL(p.unit, '-') AS unit,
                    NVL(p.item_price, 0) AS item_price,
                    NVL(bi.quantity_in_stock, 0) * NVL(p.item_price, 0) AS inventory_value,
                    bi.last_updated,
                    CASE
                        WHEN NVL(bi.quantity_in_stock, 0) <= 0 THEN 'out'
                        WHEN NVL(bi.quantity_in_stock, 0) <= NVL(bi.reorder_point, 0) THEN 'low'
                        ELSE 'normal'
                    END AS material_status
                FROM params prm
                JOIN branch_inventory bi
                    ON bi.branch_id = prm.branch_id
                JOIN product p
                    ON p.product_id = bi.product_id
                JOIN category_product cp
                    ON cp.product_category_id = p.product_category_id
            )
            SELECT
                m.material_id,
                m.product_id,
                m.material_code,
                m.material_name,
                m.material_group,
                m.material_status,
                CASE m.material_status
                    WHEN 'out' THEN 'HẾT HÀNG'
                    WHEN 'low' THEN 'SẮP HẾT'
                    ELSE 'HOẠT ĐỘNG'
                END AS status_label,
                m.current_stock,
                m.reorder_point,
                m.unit,
                m.item_price,
                m.inventory_value,
                TO_CHAR(m.last_updated, 'YYYY-MM-DD HH24:MI:SS') AS last_updated,
                TO_CHAR(prm.start_date, 'YYYY-MM-DD') AS report_start_date,
                TO_CHAR(prm.end_date, 'YYYY-MM-DD') AS report_end_date
            FROM inventory_materials m
            CROSS JOIN params prm
            WHERE (
                    prm.search_text IS NULL
                    OR LOWER(m.material_code || ' ' || m.material_name) LIKE '%' || LOWER(prm.search_text) || '%'
                )
              AND (prm.group_name IS NULL OR m.material_group = prm.group_name)
              AND (prm.status_code IS NULL OR m.material_status = prm.status_code)
            ORDER BY m.material_group, m.material_name
            SQL;

        return array_map(static function (object $row): array {
            $row = array_change_key_case((array) $row, CASE_LOWER);

            return [
                'material_id' => (int) ($row['material_id'] ?? 0),
                'product_id' => (int) ($row['product_id'] ?? 0),
                'material_code' => (string) ($row['material_code'] ?? ''),
                'material_name' => (string) ($row['material_name'] ?? ''),
                'material_group' => (string) ($row['material_group'] ?? ''),
                'status' => (string) ($row['material_status'] ?? 'normal'),
                'status_label' => (string) ($row['status_label'] ?? 'HOAT DONG'),
                'current_stock' => (int) ($row['current_stock'] ?? 0),
                'reorder_point' => (int) ($row['reorder_point'] ?? 0),
                'unit' => (string) ($row['unit'] ?? '-'),
                'item_price' => (float) ($row['item_price'] ?? 0),
                'inventory_value' => (float) ($row['inventory_value'] ?? 0),
                'last_updated' => (string) ($row['last_updated'] ?? ''),
            ];
        }, DB::select($sql, [
            'p_branch_id' => $branchId,
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
            'p_search' => $filters['search'],
            'p_group' => $filters['group'],
            'p_status' => $filters['status'],
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
     * Create a new inventory material for the selected branch.
     */
    public function createMaterial(int|string $branchId, array $data): array
    {
        // TODO: Create a branch inventory material, avoid sample data, and log the action when persistence is implemented.
        return [];
    }

    /**
     * Update an inventory material for the selected branch.
     */
    public function updateMaterial(int|string $branchId, int|string $materialId, array $data): array
    {
        // TODO: Update material information without deleting import/export history, and log the action when implemented.
        return [];
    }

    /**
     * Stop importing an inventory material for the selected branch.
     */
    public function stopImport(
        int|string $branchId,
        int|string $materialId,
        ?string $reason = null,
        int|string|null $userId = null
    ): array {
        // TODO: Mark the material as no longer imported, store the reason if supported, log the action, and never delete the material.
        return [];
    }

    /**
     * Resume importing an inventory material for the selected branch.
     */
    public function resumeImport(int|string $branchId, int|string $materialId, int|string|null $userId = null): array
    {
        // TODO: Allow the material to be imported again and log the action when persistence is implemented.
        return [];
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
     * Log an inventory material management action.
     */
    public function logMaterialAction(
        int|string $branchId,
        int|string $materialId,
        string $action,
        int|string|null $userId = null,
        array $metadata = []
    ): void {
        // TODO: Persist material management audit logs after a suitable log table is defined.
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
     * - SQL Oracle dat trong Repository va bind branch/date day du.
     */
    private function getKpiSnapshot(int|string $branchId, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $sql = <<<'SQL'
            WITH params AS (
                SELECT
                    :p_branch_id AS branch_id,
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS end_date
                FROM dual
            )
            SELECT
                COUNT(bi.branch_inventory_id) AS total_materials,
                NVL(SUM(CASE
                    WHEN bi.branch_inventory_id IS NOT NULL
                     AND NVL(bi.quantity_in_stock, 0) <= 0 THEN 1
                    ELSE 0
                END), 0) AS out_of_stock_materials,
                NVL(SUM(CASE
                    WHEN NVL(bi.quantity_in_stock, 0) > 0
                     AND NVL(bi.quantity_in_stock, 0) <= NVL(bi.reorder_point, 0) THEN 1
                    ELSE 0
                END), 0) AS low_stock_materials,
                NVL(SUM(
                    NVL(bi.quantity_in_stock, 0) * NVL(p.item_price, 0)
                ), 0) AS inventory_capital_value,
                TO_CHAR(prm.start_date, 'YYYY-MM-DD') AS report_start_date,
                TO_CHAR(prm.end_date, 'YYYY-MM-DD') AS report_end_date
            FROM params prm
            LEFT JOIN branch_inventory bi
                ON bi.branch_id = prm.branch_id
            LEFT JOIN product p
                ON p.product_id = bi.product_id
            GROUP BY prm.start_date, prm.end_date
            SQL;

        $row = array_change_key_case((array) DB::selectOne($sql, [
            'p_branch_id' => $branchId,
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
        ]), CASE_LOWER);

        return [
            'total_materials' => (int) ($row['total_materials'] ?? 0),
            'out_of_stock_materials' => (int) ($row['out_of_stock_materials'] ?? 0),
            'low_stock_materials' => (int) ($row['low_stock_materials'] ?? 0),
            'inventory_capital_value' => (float) ($row['inventory_capital_value'] ?? 0),
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
        string $valueType = 'number'
    ): array {
        return [
            'key' => $key,
            'value' => $value,
            'value_type' => $valueType,
            'trend' => $trend,
            'comparison' => null,
            'period' => $period,
        ];
    }

    /**
     * Mo ta chuc nang:
     * Normalize filter ngay va filter chuoi truoc khi bind Oracle.
     */
    private function normalizeFilters(array $filters = []): array
    {
        $currentMonth = $this->currentMonthFilters();

        return [
            ...$filters,
            'start_date' => $this->dateString($filters['start_date'] ?? null) ?? $currentMonth['start_date'],
            'end_date' => $this->dateString($filters['end_date'] ?? null) ?? $currentMonth['end_date'],
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
