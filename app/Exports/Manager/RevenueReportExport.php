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
            new ArraySheetExport('Tong quan', ['Chi so', 'Gia tri', 'Ky truoc Nguong', 'Ghi chu'], $this->summaryRows(), [
                'B' => '#,##0.00',
                'C' => '#,##0.00',
            ]),
            new ArraySheetExport('So sanh doanh thu', ['Ngay', 'Doanh thu hien tai', 'Doanh thu ky truoc', 'Tang truong (%)'], $this->comparisonRows(), [
                'B' => '#,##0',
                'C' => '#,##0',
                'D' => '0.00',
            ]),
            new ArraySheetExport('Co cau dich vu', ['Nhom doanh thu', 'Doanh thu', 'Ty trong (%)'], $this->serviceMixRows(), [
                'B' => '#,##0',
                'C' => '0.00',
            ]),
            new ArraySheetExport('Hieu suat NV', ['Ma NV', 'Nhan vien', 'Doanh thu', 'So don', 'Ty le upsell (%)', 'Canh bao'], $this->employeeRows(), [
                'C' => '#,##0',
                'D' => '#,##0',
                'E' => '0.00',
            ]),
            new ArraySheetExport('Khach hang', ['Chi so', 'Gia tri', 'Ky truoc', 'Tang truong (%)', 'Xu huong'], $this->customerRows(), [
                'B' => '#,##0.00',
                'C' => '#,##0.00',
                'D' => '0.00',
            ]),
        ];
    }

    private function summaryRows(): array
    {
        $target = $this->data['target_progress'] ?? [];
        $aov = $this->data['aov'] ?? [];

        return [
            ['Doanh thu hien tai', $this->value($target, 'current_revenue'), null, 'VND'],
            ['Target thang', $this->value($target, 'target_month'), null, 'VND'],
            ['Con lai de dat target', $this->value($target, 'remaining_to_target'), null, 'VND'],
            ['Tien do target (%)', $this->value($target, 'progress_percent'), null, (string) $this->value($target, 'target_warning', '')],
            ['Doanh thu can moi ngay', $this->value($target, 'required_revenue_per_day'), null, 'VND'],
            ['So ngay con lai', $this->value($target, 'days_left'), null, 'Ngay'],
            ['AOV hien tai', $this->value($aov, 'current_aov'), $this->value($aov, 'previous_aov'), (string) $this->value($aov, 'trend', '')],
            ['So don hien tai', $this->value($aov, 'current_orders'), $this->value($aov, 'previous_orders'), 'Don'],
            ['Doanh thu AOV', $this->value($aov, 'current_revenue'), $this->value($aov, 'previous_revenue'), 'VND'],
            ['Tang truong AOV (%)', $this->value($aov, 'aov_growth_percent'), null, ''],
            ['Gia tri don cao nhat', $this->value($aov, 'max_order_value'), null, 'VND'],
        ];
    }

    private function comparisonRows(): array
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
            $this->value($row, 'employee_id'),
            $this->value($row, 'employee_name'),
            $this->value($row, 'revenue'),
            $this->value($row, 'order_count'),
            $this->value($row, 'upsell_rate'),
            $this->value($row, 'warning_text'),
        ], $this->data['employee_performance'] ?? []);
    }

    private function customerRows(): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'metric'),
            $this->value($row, 'value'),
            $this->value($row, 'previous_value'),
            $this->value($row, 'growth_percent'),
            $this->value($row, 'trend'),
        ], $this->data['customer_retention'] ?? []);
    }

    private function value(array $row, string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $row) ? $row[$key] : $default;
    }
}
