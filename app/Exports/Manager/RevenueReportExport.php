<?php

namespace App\Exports\Manager;

use App\Exports\Sheets\ArraySheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RevenueReportExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $data,
        private readonly array $filters = []
    ) {
    }

    public function sheets(): array
    {
        return [
            new ArraySheetExport('Tong quan', ['Chi so', 'Hien tai', 'Ky truoc', 'Tang truong', 'Ghi chu'], $this->summaryRows(), [
                'B' => '#,##0.00',
                'C' => '#,##0.00',
                'D' => '0.00',
            ]),
            new ArraySheetExport('So sanh doanh thu', ['Ngay', 'Doanh thu ky nay', 'Doanh thu ky truoc', 'Tang truong (%)'], $this->revenueComparisonRows(), [
                'B' => '#,##0',
                'C' => '#,##0',
                'D' => '0.00',
            ]),
            new ArraySheetExport('Co cau dich vu', ['Nhom doanh thu', 'Doanh thu', 'Ty trong (%)'], $this->serviceMixRows(), [
                'B' => '#,##0',
                'C' => '0.00',
            ]),
            new ArraySheetExport('Nhan vien', ['Nhan vien', 'Vai tro', 'Doanh thu', 'Upsell', 'Ghi chu'], $this->employeeRows(), [
                'C' => '#,##0',
                'D' => '#,##0',
            ]),
            new ArraySheetExport('Khach hang', ['Chi so', 'Gia tri', 'Ghi chu'], $this->customerRows(), [
                'B' => '#,##0.00',
            ]),
        ];
    }

    private function summaryRows(): array
    {
        $target = $this->data['target_progress'] ?? [];
        $aov = $this->data['aov'] ?? [];

        return [
            ['Khoang ngay', $this->dateRangeLabel($this->filters), null, null, ''],
            ['Doanh thu hien tai', $this->value($target, 'current_revenue'), null, null, ''],
            ['Muc tieu thang', $this->value($target, 'target_month'), null, $this->value($target, 'progress_percent'), (string) $this->value($target, 'target_warning', '')],
            ['Con thieu target', $this->value($target, 'remaining_to_target'), null, null, ''],
            ['Can moi ngay', $this->value($target, 'required_revenue_per_day'), null, null, 'So ngay con lai: '.$this->value($target, 'days_left', 0)],
            ['So don ky nay', $this->value($aov, 'current_orders'), $this->value($aov, 'previous_orders'), null, ''],
            ['Doanh thu ky nay', $this->value($aov, 'current_revenue'), $this->value($aov, 'previous_revenue'), null, ''],
            ['AOV', $this->value($aov, 'current_aov'), $this->value($aov, 'previous_aov'), $this->value($aov, 'aov_growth_percent'), (string) $this->value($aov, 'trend', '')],
            ['Gia tri don cao nhat', $this->value($aov, 'max_order_value'), null, null, ''],
        ];
    }

    private function revenueComparisonRows(): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'revenue_date'),
            $this->value($row, 'current_revenue'),
            $this->value($row, 'previous_revenue'),
            $this->value($row, 'growth_percent'),
        ], $this->data['revenue_comparison'] ?? []);
    }

    private function serviceMixRows(): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'revenue_group'),
            $this->value($row, 'revenue'),
            $this->value($row, 'percent_of_total'),
        ], $this->data['service_mix'] ?? []);
    }

    private function employeeRows(): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'employee_name', $this->value($row, 'name')),
            $this->value($row, 'role_name', $this->value($row, 'role')),
            $this->value($row, 'revenue'),
            $this->value($row, 'upsell_value', $this->value($row, 'upsell_count')),
            $this->value($row, 'warning_text', ''),
        ], $this->data['employee_performance'] ?? []);
    }

    private function customerRows(): array
    {
        $retention = $this->data['customer_retention'] ?? [];

        if ($retention === []) {
            return [];
        }

        return [
            ['Ty le quay lai', $this->value($retention, 'retention_rate'), (string) $this->value($retention, 'trend', '')],
            ['Khach moi', $this->value($retention, 'new_customers'), ''],
            ['Khach trung thanh', $this->value($retention, 'loyal_customers'), ''],
        ];
    }

    private function dateRangeLabel(array $filters): string
    {
        $start = $filters['start_date'] ?? null;
        $end = $filters['end_date'] ?? null;

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
