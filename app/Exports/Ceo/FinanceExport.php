<?php

namespace App\Exports\Ceo;

use App\Exports\Sheets\ArraySheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FinanceExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $data,
        private readonly array $filters = []
    ) {
    }

    public function sheets(): array
    {
        return [
            new ArraySheetExport('Tong quan', ['Chi so', 'Hien tai', 'Ky truoc', 'Tang truong (%)', 'Ghi chu'], $this->summaryRows(), [
                'B' => '#,##0.00',
                'C' => '#,##0.00',
                'D' => '0.00',
            ]),
            new ArraySheetExport('Xu huong ngay', ['Ngay', 'Nhan', 'Doanh thu', 'Chi phi vat tu', 'Chi phi luong', 'Tong chi phi', 'Loi nhuan'], $this->trendRows('trend'), [
                'C' => '#,##0',
                'D' => '#,##0',
                'E' => '#,##0',
                'F' => '#,##0',
                'G' => '#,##0',
            ]),
            new ArraySheetExport('Xu huong thang', ['Thang', 'Nhan', 'Doanh thu', 'Chi phi vat tu', 'Chi phi luong', 'Tong chi phi', 'Loi nhuan'], $this->trendRows('monthly_trend'), [
                'C' => '#,##0',
                'D' => '#,##0',
                'E' => '#,##0',
                'F' => '#,##0',
                'G' => '#,##0',
            ]),
            new ArraySheetExport('Co cau chi phi', ['Nhom chi phi', 'So tien', 'Ty trong (%)'], $this->costRows(), [
                'B' => '#,##0',
                'C' => '0.00',
            ]),
            new ArraySheetExport('Loi nhuan CN', ['Hang', 'Ma CN', 'Chi nhanh', 'Doanh thu', 'Chi phi luong', 'Chi phi vat tu', 'Tong chi phi', 'Loi nhuan', 'Bien LN (%)'], $this->branchProfitRows(), [
                'D' => '#,##0',
                'E' => '#,##0',
                'F' => '#,##0',
                'G' => '#,##0',
                'H' => '#,##0',
                'I' => '0.00',
            ]),
            new ArraySheetExport('Loi nhuan dich vu', ['Hang', 'Dich vu', 'Luot dung', 'Doanh thu', 'Chi phi vat tu', 'Chi phi luong', 'Tong chi phi', 'Loi nhuan', 'Bien LN (%)'], $this->serviceProfitRows('service_profit'), [
                'C' => '#,##0',
                'D' => '#,##0',
                'E' => '#,##0',
                'F' => '#,##0',
                'G' => '#,##0',
                'H' => '#,##0',
                'I' => '0.00',
            ]),
            new ArraySheetExport('Bien LN thap', ['STT', 'Dich vu', 'Luot dung', 'Doanh thu', 'Chi phi vat tu', 'Chi phi luong', 'Tong chi phi', 'Loi nhuan', 'Bien LN (%)'], $this->serviceProfitRows('lowest_margin_services'), [
                'C' => '#,##0',
                'D' => '#,##0',
                'E' => '#,##0',
                'F' => '#,##0',
                'G' => '#,##0',
                'H' => '#,##0',
                'I' => '0.00',
            ]),
            new ArraySheetExport('Canh bao', ['Nhom canh bao', 'Loai', 'Muc', 'Tieu de', 'Noi dung', 'Doi tuong', 'Gia tri', 'Nguong', 'Thoi diem'], $this->alertRows(), [
                'G' => '#,##0.00',
                'H' => '#,##0.00',
            ]),
        ];
    }

    private function summaryRows(): array
    {
        $overview = $this->data['overview'] ?? [];
        $kpi = $overview['kpi_cards'] ?? [];
        $range = $overview['date_range'] ?? [];

        return [
            ['Khoang ngay', $this->dateRangeLabel($range), null, null, ''],
            ['Doanh thu toan chuoi', $this->value($kpi, 'total_revenue'), $this->value($kpi, 'previous_total_revenue'), $this->value($kpi, 'revenue_growth_percent'), 'VND'],
            ['Tong chi phi uoc tinh', $this->value($kpi, 'estimated_total_cost'), $this->value($kpi, 'previous_estimated_total_cost'), $this->value($kpi, 'cost_growth_percent'), (string) $this->value($kpi, 'cost_trend', '')],
            ['Loi nhuan uoc tinh', $this->value($kpi, 'estimated_profit'), $this->value($kpi, 'previous_estimated_profit'), $this->value($kpi, 'profit_growth_percent'), (string) $this->value($kpi, 'profit_trend', '')],
            ['Bien loi nhuan uoc tinh', $this->value($kpi, 'estimated_margin_percent'), $this->value($kpi, 'previous_estimated_margin_percent'), $this->value($kpi, 'margin_growth_percent'), (string) $this->value($kpi, 'margin_trend', '')],
        ];
    }

    private function trendRows(string $key): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'report_date', $this->value($row, 'report_month')),
            $this->value($row, 'label'),
            $this->value($row, 'revenue'),
            $this->value($row, 'estimated_material_cost'),
            $this->value($row, 'estimated_salary_cost'),
            $this->value($row, 'estimated_total_cost'),
            $this->value($row, 'estimated_profit'),
        ], $this->data[$key]['rows'] ?? []);
    }

    private function costRows(): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'cost_group'),
            $this->value($row, 'cost_amount'),
            $this->value($row, 'cost_percent'),
        ], $this->data['cost_structure'] ?? []);
    }

    private function branchProfitRows(): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'rank_no'),
            $this->value($row, 'branch_code'),
            $this->value($row, 'branch_name'),
            $this->value($row, 'branch_revenue'),
            $this->value($row, 'branch_salary_cost'),
            $this->value($row, 'branch_material_cost'),
            $this->value($row, 'estimated_branch_cost'),
            $this->value($row, 'estimated_branch_profit'),
            $this->value($row, 'estimated_branch_margin_percent'),
        ], $this->data['branch_profit'] ?? []);
    }

    private function serviceProfitRows(string $key): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'profit_rank', $this->value($row, 'rank_no')),
            $this->value($row, 'service_name'),
            $this->value($row, 'usage_count'),
            $this->value($row, 'total_service_revenue'),
            $this->value($row, 'total_material_cost'),
            $this->value($row, 'total_labor_cost'),
            $this->value($row, 'estimated_service_cost'),
            $this->value($row, 'estimated_service_profit'),
            $this->value($row, 'estimated_service_margin_percent'),
        ], $this->data[$key] ?? []);
    }

    private function alertRows(): array
    {
        $groups = [
            'Chi nhanh loi nhuan am' => $this->data['negative_branch_profit_alerts'] ?? [],
            'Dich vu margin thap' => $this->data['low_service_margin_alerts'] ?? [],
            'Chi phi tang manh' => $this->data['cost_growth_alerts'] ?? [],
        ];

        $rows = [];

        foreach ($groups as $group => $alerts) {
            foreach ($alerts as $alert) {
                $rows[] = [
                    $group,
                    $this->value($alert, 'alert_type'),
                    $this->value($alert, 'alert_level'),
                    $this->value($alert, 'title'),
                    $this->value($alert, 'warning_text'),
                    $this->value($alert, 'branch_name', $this->value($alert, 'service_name', 'Toan chuoi')),
                    $this->value($alert, 'main_value'),
                    $this->value($alert, 'compare_value'),
                    $this->value($alert, 'created_at'),
                ];
            }
        }

        return $rows;
    }

    private function dateRangeLabel(array $range): string
    {
        $start = $range['start_date'] ?? null;
        $end = $range['end_date'] ?? null;

        if ($start && $end) {
            return "{$start} - {$end}";
        }

        return 'Mac dinh theo he thong';
    }

    private function value(array $row, string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $row) ? $row[$key] : $default;
    }
}
