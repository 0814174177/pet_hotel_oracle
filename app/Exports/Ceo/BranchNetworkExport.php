<?php

namespace App\Exports\Ceo;

use App\Exports\Sheets\ArraySheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BranchNetworkExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $data,
        private readonly array $filters = []
    ) {
    }

    public function sheets(): array
    {
        return [
            new ArraySheetExport('Tong quan', ['Chi so', 'Ten', 'Gia tri', 'Ghi chu'], $this->summaryRows(), [
                'C' => '#,##0.00',
            ]),
            new ArraySheetExport('Danh sach CN', ['ID', 'Chi nhanh', 'Khu vuc', 'Doanh thu', 'Ty le lap day (%)', 'Phong dang dung', 'Tong phong', 'Quan ly', 'Trang thai', 'So booking'], $this->branchRows(), [
                'D' => '#,##0',
                'E' => '0.00',
                'F' => '#,##0',
                'G' => '#,##0',
                'J' => '#,##0',
            ]),
        ];
    }

    private function summaryRows(): array
    {
        $highest = $this->data['highest_revenue_branch'] ?? [];
        $lowest = $this->data['lowest_occupancy_branch'] ?? [];
        $highest = is_array($highest) ? $highest : [];
        $lowest = is_array($lowest) ? $lowest : [];

        return [
            ['Tong chi nhanh dang hoat dong', null, $this->value($this->data['active_count'] ?? [], 'value', 0), ''],
            ['Chi nhanh doanh thu cao nhat', $this->value($highest, 'branch_name'), $this->value($highest, 'revenue'), $this->value($highest, 'region')],
            ['Chi nhanh lap day thap nhat', $this->value($lowest, 'branch_name'), $this->value($lowest, 'occupancy_rate'), $this->value($lowest, 'region')],
        ];
    }

    private function branchRows(): array
    {
        return array_map(fn (array $branch): array => [
            $this->value($branch, 'branch_id'),
            $this->value($branch, 'branch_name'),
            $this->value($branch, 'region'),
            $this->value($branch, 'revenue'),
            $this->value($branch, 'occupancy_rate'),
            $this->value($branch, 'used_rooms'),
            $this->value($branch, 'total_rooms'),
            $this->value($branch, 'manager_name'),
            $this->statusLabel((string) $this->value($branch, 'status', '')),
            $this->value($branch, 'bookings_count'),
        ], $this->data['branches'] ?? []);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Hoat dong',
            'inactive' => 'Ngung hoat dong',
            default => $status,
        };
    }

    private function value(array $row, string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $row) ? $row[$key] : $default;
    }
}
