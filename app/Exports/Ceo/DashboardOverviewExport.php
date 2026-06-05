<?php

namespace App\Exports\Ceo;

use App\Exports\Sheets\ArraySheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DashboardOverviewExport implements WithMultipleSheets
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
            new ArraySheetExport('Khach hang', ['Ngay', 'So khach', 'So booking'], $this->customerTrendRows(), [
                'B' => '#,##0',
                'C' => '#,##0',
            ]),
            new ArraySheetExport('Revenue mix', ['Nhom doanh thu', 'Doanh thu', 'Ty trong (%)'], $this->revenueMixRows(), [
                'B' => '#,##0',
                'C' => '0.00',
            ]),
            new ArraySheetExport('Revenue COGS', ['Ngay', 'Doanh thu', 'COGS uoc tinh', 'Loi nhuan gop'], $this->revenueCogsTrendRows(), [
                'B' => '#,##0',
                'C' => '#,##0',
                'D' => '#,##0',
            ]),
            new ArraySheetExport('Doanh thu CN', ['Ma CN', 'Chi nhanh', 'Doanh thu', 'Trieu VND'], $this->branchRevenueRows(), [
                'C' => '#,##0',
                'D' => '#,##0.00',
            ]),
            new ArraySheetExport('Xep hang', ['Hang', 'Loai', 'Ma', 'Ten', 'Gia tri'], $this->rankingRows(), [
                'E' => '#,##0.00',
            ]),
            new ArraySheetExport('Canh bao', ['Loai', 'Muc', 'Tieu de', 'Noi dung', 'Chi nhanh', 'Gia tri', 'Nguong', 'Thoi diem'], $this->riskAlertRows(), [
                'F' => '#,##0.00',
                'G' => '#,##0.00',
            ]),
        ];
    }

    private function summaryRows(): array
    {
        $operation = $this->data['operation_overview'] ?? [];
        $finance = $this->data['finance_overview'] ?? [];
        $filters = $this->data['filters'] ?? $this->filters;

        $occupancySnapshot = $operation['current_hotel_occupancy'] ?? [];
        $occupancy = $operation['occupancy_rate'] ?? [];
        $revpar = $operation['revpar'] ?? [];
        $revenue = $finance['total_revenue'] ?? [];
        $cogs = $finance['estimated_cogs'] ?? [];

        return [
            ['Khoang ngay', $this->dateRangeLabel($filters), null, null, ''],
            ['Phong dang su dung', $this->value($occupancySnapshot, 'occupied_room'), null, null, 'Snapshot trang thai phong'],
            ['Phong kha dung', $this->value($occupancySnapshot, 'usable_room'), null, null, 'Snapshot trang thai phong'],
            ['Ty le lap day snapshot (%)', $this->value($occupancySnapshot, 'occupancy_rate'), null, null, ''],
            ['Ty le lap day theo ky (%)', $this->nestedValue($occupancy, ['current', 'occupancy_rate']), $this->nestedValue($occupancy, ['previous', 'occupancy_rate']), $this->nestedValue($occupancy, ['comparison', 'change_percent']), (string) $this->nestedValue($occupancy, ['comparison', 'trend'], '')],
            ['RevPAR', $this->nestedValue($revpar, ['current', 'revpar']), $this->nestedValue($revpar, ['previous', 'revpar']), $this->nestedValue($revpar, ['comparison', 'change_percent']), (string) $this->nestedValue($revpar, ['comparison', 'trend'], '')],
            ['Tong doanh thu', $this->value($revenue, 'current_total_revenue'), $this->value($revenue, 'previous_total_revenue'), $this->value($revenue, 'revenue_growth_percent'), 'VND'],
            ['COGS uoc tinh', $this->nestedValue($cogs, ['current', 'estimated_cogs']), $this->nestedValue($cogs, ['previous', 'estimated_cogs']), $this->nestedValue($cogs, ['comparison', 'growth_percent']), (string) $this->nestedValue($cogs, ['comparison', 'trend'], '')],
        ];
    }

    private function customerTrendRows(): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'report_date'),
            $this->value($row, 'customer_count'),
            $this->value($row, 'booking_count'),
        ], $this->data['operation_overview']['customer_trend'] ?? []);
    }

    private function revenueMixRows(): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'revenue_group'),
            $this->value($row, 'revenue_amount'),
            $this->value($row, 'revenue_percent'),
        ], $this->data['finance_overview']['revenue_mix'] ?? []);
    }

    private function revenueCogsTrendRows(): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'report_date'),
            $this->value($row, 'revenue'),
            $this->value($row, 'estimated_cogs'),
            $this->value($row, 'gross_profit'),
        ], $this->data['finance_overview']['revenue_and_cogs_trend'] ?? []);
    }

    private function branchRevenueRows(): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'branch_id'),
            $this->value($row, 'branch_name'),
            $this->value($row, 'total_revenue'),
            $this->value($row, 'revenue_million_vnd'),
        ], $this->data['strategy_risk']['branch_revenue'] ?? []);
    }

    private function rankingRows(): array
    {
        $rows = [];

        foreach ($this->data['strategy_risk']['branch_ranking'] ?? [] as $row) {
            $rows[] = [
                $this->value($row, 'rank_no'),
                'Chi nhanh',
                $this->value($row, 'branch_id'),
                $this->value($row, 'branch_name'),
                $this->value($row, 'total_revenue'),
            ];
        }

        foreach ($this->data['strategy_risk']['top_used_services'] ?? [] as $row) {
            $rows[] = [
                $this->value($row, 'rank_no'),
                'Dich vu',
                $this->value($row, 'service_id'),
                $this->value($row, 'service_name'),
                $this->value($row, 'usage_count'),
            ];
        }

        return $rows;
    }

    private function riskAlertRows(): array
    {
        return array_map(fn (array $row): array => [
            $this->value($row, 'alert_type'),
            $this->value($row, 'alert_level'),
            $this->value($row, 'title'),
            $this->value($row, 'warning_text'),
            $this->value($row, 'branch_name'),
            $this->value($row, 'main_value'),
            $this->value($row, 'compare_value'),
            $this->value($row, 'created_at'),
        ], $this->data['strategy_risk']['risk_alerts'] ?? []);
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

    private function nestedValue(array $row, array $keys, mixed $default = null): mixed
    {
        $value = $row;

        foreach ($keys as $key) {
            if (! is_array($value) || ! array_key_exists($key, $value)) {
                return $default;
            }

            $value = $value[$key];
        }

        return $value;
    }

    private function value(array $row, string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $row) ? $row[$key] : $default;
    }
}
