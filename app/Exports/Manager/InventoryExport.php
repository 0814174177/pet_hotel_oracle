<?php

namespace App\Exports\Manager;

use App\Exports\Sheets\ArraySheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InventoryExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $data,
        private readonly array $filters = []
    ) {
    }

    public function sheets(): array
    {
        return [
            new ArraySheetExport('Tong quan', ['Chi so', 'Gia tri', 'Ghi chu'], $this->summaryRows(), [
                'B' => '#,##0.00',
            ]),
            new ArraySheetExport('Danh sach vat tu', ['Ma vat tu', 'Ten vat tu', 'Nhom', 'Don vi', 'Don gia', 'Ton kho', 'Nguong nhap', 'Trang thai', 'Canh bao', 'Cap nhat luc'], $this->materialRows(), [
                'E' => '#,##0',
                'F' => '#,##0',
                'G' => '#,##0',
            ]),
            new ArraySheetExport('Het hang', ['ID SP', 'Ten vat tu', 'Nhom', 'Ton kho', 'Nguong nhap', 'Don vi', 'Canh bao', 'Cap nhat luc'], $this->warningRows('out_of_stock'), [
                'D' => '#,##0',
                'E' => '#,##0',
            ]),
            new ArraySheetExport('Sap het hang', ['ID SP', 'Ten vat tu', 'Nhom', 'Ton kho', 'Nguong nhap', 'Don vi', 'Canh bao', 'Cap nhat luc'], $this->warningRows('low_stock'), [
                'D' => '#,##0',
                'E' => '#,##0',
            ]),
            new ArraySheetExport('Gia tri theo nhom', ['Nhom vat tu', 'So loai vat tu', 'Tong so luong', 'Gia tri ton kho'], $this->categoryRows(), [
                'B' => '#,##0',
                'C' => '#,##0',
                'D' => '#,##0',
            ]),
        ];
    }

    private function summaryRows(): array
    {
        $kpi = $this->data['kpi'] ?? [];
        $comparison = $this->value($kpi, 'material_type_comparison', []);
        $comparison = is_array($comparison) ? $comparison : [];

        return [
            ['Tong vat tu', $this->value($kpi, 'total_materials'), 'Dang hoat dong tai chi nhanh'],
            ['Het hang', $this->value($kpi, 'out_of_stock_count'), 'Can nhap bo sung ngay'],
            ['Sap het hang', $this->value($kpi, 'low_stock_count'), 'Bang hoac duoi nguong nhap lai'],
            ['Gia tri ton kho', $this->value($kpi, 'inventory_value'), 'VND'],
            ['Gia dinh bien loi nhuan (%)', $this->value($kpi, 'margin_percent_assumption'), ''],
            ['Canh bao ton kho', null, $this->value($kpi, 'inventory_warning')],
            ['Bien dong so loai vat tu', $this->value($comparison, 'delta_materials'), (string) $this->value($comparison, 'trend', '')],
            ['Ty le bien dong (%)', $this->value($comparison, 'change_percent'), ''],
        ];
    }

    private function materialRows(): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'material_code'),
            $this->value($row, 'product_name', $this->value($row, 'material_name')),
            $this->value($row, 'product_category_name', $this->value($row, 'material_group')),
            $this->value($row, 'unit'),
            $this->value($row, 'item_price'),
            $this->value($row, 'quantity_in_stock', $this->value($row, 'current_stock')),
            $this->value($row, 'reorder_point'),
            $this->value($row, 'stock_status', $this->value($row, 'status_label')),
            $this->value($row, 'warning_text'),
            $this->value($row, 'last_updated', $this->value($row, 'updated_at')),
        ], $this->data['materials'] ?? []);
    }

    private function warningRows(string $key): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'product_id'),
            $this->value($row, 'product_name'),
            $this->value($row, 'product_category_name'),
            $this->value($row, 'quantity_in_stock'),
            $this->value($row, 'reorder_point'),
            $this->value($row, 'unit'),
            $this->value($row, 'warning_text'),
            $this->value($row, 'last_updated'),
        ], $this->data[$key] ?? []);
    }

    private function categoryRows(): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'product_category_name'),
            $this->value($row, 'material_count'),
            $this->value($row, 'total_quantity'),
            $this->value($row, 'inventory_value'),
        ], $this->data['inventory_value_by_category'] ?? []);
    }

    private function value(array $row, string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $row) ? $row[$key] : $default;
    }
}
