<?php

namespace App\Repositories\Eloquent\Ceo;

use App\Models\Branch;
use App\Models\Employee;
use App\Repositories\Contracts\Ceo\BranchNetworkRepositoryInterface;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class BranchNetworkRepository implements BranchNetworkRepositoryInterface
{
    private const ACTIVE_BRANCH_VALUE = 1;

    private const ROOM_USED_STATUSES = [
        'IN_USE',
        'OCCUPIED',
        'BOOKED',
        'USING',
        'Đang sử dụng',
        'Đã đặt',
    ];

    private const PAID_PAYMENT_STATUSES = [
        'SUCCESS',
    ];

    private const PAID_ORDER_STATUSES = [
        'COMPLETED',
        'PAID',
    ];

    private const SORTABLE_COLUMNS = [
        'branch_name',
        'region',
        'revenue',
        'occupancy_rate',
        'status',
    ];

    public function getActiveBranchCount(array $filters = []): array
    {
        return [
            'value' => (int) Branch::query()
                ->where('is_active', self::ACTIVE_BRANCH_VALUE)
                ->count(),
        ];
    }

    public function resolvePeriodRange(
        ?string $periodType,
        ?string $date = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        // TODO: Resolve day/month/year or explicit date range before applying period-aware branch metrics.
        return [];
    }

    public function getHighestRevenueBranch(array $filters = []): ?array
    {
        $branch = collect($this->getBranchNetworkList($filters))
            ->sortByDesc('revenue')
            ->values()
            ->first();

        if (! $branch) {
            return null;
        }

        return [
            'branch_id' => $branch['branch_id'],
            'branch_name' => $branch['branch_name'],
            'region' => $branch['region'],
            'revenue' => $branch['revenue'],
        ];
    }

    public function getLowestOccupancyBranch(array $filters = []): ?array
    {
        $branch = collect($this->getBranchNetworkList([
            ...$filters,
            'status' => 'active',
        ]))
            ->sortBy('occupancy_rate')
            ->values()
            ->first();

        if (! $branch) {
            return null;
        }

        return [
            'branch_id' => $branch['branch_id'],
            'branch_name' => $branch['branch_name'],
            'region' => $branch['region'],
            'occupancy_rate' => $branch['occupancy_rate'],
            'used_rooms' => $branch['used_rooms'],
            'total_rooms' => $branch['total_rooms'],
        ];
    }

    public function getBranchNetworkList(array $filters = []): array
    {
        $revenues = $this->revenueByBranch($filters);

        $branches = Branch::query()
            ->with([
                'employees' => fn ($query) => $query
                    ->working()
                    ->where('position', 'MANAGER')
                    ->with('user')
                    ->orderBy('employee_id'),
            ])
            ->withCount([
                'rooms',
                'bookings',
                'employees' => fn ($query) => $query->working(),
                'rooms as used_rooms' => fn ($query) => $query->whereIn('status', self::ROOM_USED_STATUSES),
            ])
            ->orderBy('branch_name')
            ->get()
            ->map(fn (Branch $branch): array => $this->formatBranch($branch, $revenues));

        return $this->sortBranches(
            $this->applyFilters($branches, $filters),
            $filters
        )
            ->values()
            ->all();
    }

    private function revenueByBranch(array $filters = []): Collection
    {
        try {
            // Payments are canonical revenue; paid orders are only a fallback for demo DBs without successful payments.
            if (Schema::hasTable('payments') && DB::table('payments')->whereIn('status', self::PAID_PAYMENT_STATUSES)->exists()) {
                return $this->paymentRevenueByBranch($filters);
            }
        } catch (Throwable) {
            return $this->orderRevenueByBranch($filters);
        }

        return $this->orderRevenueByBranch($filters);
    }

    private function paymentRevenueByBranch(array $filters = []): Collection
    {
        $query = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.order_id')
            ->whereIn('payments.status', self::PAID_PAYMENT_STATUSES);

        $this->applyDateFilters($query, 'payments.paid_at', $filters);

        return $query
            ->select([
                'orders.branch_id as branch_id',
                DB::raw('SUM(payments.amount) AS revenue'),
            ])
            ->groupBy('orders.branch_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (string) $this->rowValue($row, 'branch_id') => (float) $this->rowValue($row, 'revenue'),
            ]);
    }

    private function orderRevenueByBranch(array $filters = []): Collection
    {
        try {
            if (! Schema::hasTable('orders')) {
                return collect();
            }
        } catch (Throwable) {
            return collect();
        }

        $query = DB::table('orders')
            ->whereIn('status', self::PAID_ORDER_STATUSES)
            ->whereNotNull('paid_at');

        $this->applyDateFilters($query, 'paid_at', $filters);

        return $query
            ->select([
                'branch_id',
                DB::raw('SUM(grand_total) AS revenue'),
            ])
            ->groupBy('branch_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (string) $this->rowValue($row, 'branch_id') => (float) $this->rowValue($row, 'revenue'),
            ]);
    }

    private function formatBranch(Branch $branch, Collection $revenues): array
    {
        $totalRooms = (int) ($branch->rooms_count ?? 0);
        $usedRooms = (int) ($branch->used_rooms ?? 0);
        $revenue = (float) ($revenues->get((string) $branch->branch_id, 0) ?? 0);

        return [
            'branch_id' => (int) $branch->branch_id,
            'branch_name' => (string) $branch->branch_name,
            'region' => $this->regionFrom($branch),
            'revenue' => $this->cleanNumber($revenue),
            'occupancy_rate' => $this->occupancyRate($usedRooms, $totalRooms),
            'used_rooms' => $usedRooms,
            'total_rooms' => $totalRooms,
            'manager_name' => $this->managerName($branch->employees),
            'status' => ((int) $branch->is_active === self::ACTIVE_BRANCH_VALUE) ? 'active' : 'inactive',
            'rooms_count' => $totalRooms,
            'bookings_count' => (int) ($branch->bookings_count ?? 0),
            'employees_count' => (int) ($branch->employees_count ?? 0),
            'address' => (string) $branch->address,
        ];
    }

    private function applyFilters(Collection $branches, array $filters): Collection
    {
        return $branches
            ->when(filled($filters['region'] ?? null), function (Collection $items) use ($filters): Collection {
                $region = $this->searchable((string) $filters['region']);

                return $items->filter(
                    fn (array $branch): bool => Str::contains($this->searchable($branch['region']), $region)
                );
            })
            ->when(filled($filters['status'] ?? null), function (Collection $items) use ($filters): Collection {
                $status = $this->normalizeStatus((string) $filters['status']);

                return $items->filter(fn (array $branch): bool => $branch['status'] === $status);
            })
            ->when(is_numeric($filters['min_revenue'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $branch): bool => (float) $branch['revenue'] >= (float) $filters['min_revenue']);
            })
            ->when(is_numeric($filters['max_revenue'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $branch): bool => (float) $branch['revenue'] <= (float) $filters['max_revenue']);
            })
            ->when(is_numeric($filters['min_occupancy_rate'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $branch): bool => (float) $branch['occupancy_rate'] >= (float) $filters['min_occupancy_rate']);
            })
            ->when(is_numeric($filters['max_occupancy_rate'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $branch): bool => (float) $branch['occupancy_rate'] <= (float) $filters['max_occupancy_rate']);
            });
    }

    private function sortBranches(Collection $branches, array $filters): Collection
    {
        $sortBy = (string) ($filters['sort_by'] ?? 'branch_name');

        if (! in_array($sortBy, self::SORTABLE_COLUMNS, true)) {
            $sortBy = 'branch_name';
        }

        $direction = Str::lower((string) ($filters['sort_direction'] ?? 'asc')) === 'desc'
            ? 'desc'
            : 'asc';

        return $direction === 'desc'
            ? $branches->sortByDesc($sortBy)
            : $branches->sortBy($sortBy);
    }

    private function managerName(Collection $employees): ?string
    {
        /** @var Employee|null $manager */
        $manager = $employees->first(
            fn (Employee $employee): bool => strtoupper((string) $employee->user?->role) === 'MANAGER'
        ) ?: $employees->first();

        return $manager?->full_name;
    }

    private function occupancyRate(int $usedRooms, int $totalRooms): int|float
    {
        if ($totalRooms <= 0) {
            return 0;
        }

        return $this->cleanNumber(($usedRooms / $totalRooms) * 100);
    }

    private function regionFrom(Branch $branch): string
    {
        $value = trim((string) $branch->branch_name.' '.(string) $branch->address);

        if (preg_match('/Quận\s+(\d{1,2})/iu', $value, $matches)) {
            return 'Quận '.$matches[1];
        }

        $searchable = $this->searchable($value);

        if (preg_match('/\bq(?:uan)?\.?\s*(\d{1,2})\b/', $searchable, $matches)) {
            return 'Quận '.$matches[1];
        }

        foreach ($this->regionAliases() as $region => $aliases) {
            if (Str::contains($searchable, $aliases)) {
                return $region;
            }
        }

        return 'Khu vực khác';
    }

    private function regionAliases(): array
    {
        return [
            'Thủ Đức' => ['thu duc', 'tp thu duc', 'thanh pho thu duc'],
            'Gò Vấp' => ['go vap'],
            'Bình Thạnh' => ['binh thanh'],
            'Phú Nhuận' => ['phu nhuan'],
            'Tân Bình' => ['tan binh'],
            'Tân Phú' => ['tan phu'],
            'Bình Tân' => ['binh tan'],
            'Bình Chánh' => ['binh chanh'],
            'Nhà Bè' => ['nha be'],
            'Củ Chi' => ['cu chi'],
            'Hóc Môn' => ['hoc mon'],
            'Cần Giờ' => ['can gio'],
        ];
    }

    private function normalizeStatus(string $status): string
    {
        return match ($this->searchable($status)) {
            '1', 'true', 'active', 'enabled', 'dang hoat dong', 'hoat dong' => 'active',
            '0', 'false', 'inactive', 'disabled', 'ngung hoat dong', 'tam ngung', 'bao tri' => 'inactive',
            default => $this->searchable($status),
        };
    }

    private function searchable(?string $value): string
    {
        return Str::lower(Str::ascii($value ?? ''));
    }

    private function cleanNumber(float $value): int|float
    {
        $rounded = round($value, 2);

        return abs($rounded - round($rounded)) < 0.00001
            ? (int) round($rounded)
            : $rounded;
    }

    private function rowValue(object $row, string $key): mixed
    {
        return $row->{$key} ?? $row->{strtoupper($key)} ?? null;
    }

    private function applyDateFilters($query, string $column, array $filters): void
    {
        if (! empty($filters['start_date'])) {
            $query->where($column, '>=', $this->dateValue($filters['start_date']));
        }

        if (! empty($filters['end_date'])) {
            $query->where($column, '<=', $this->dateValue($filters['end_date']));
        }
    }

    private function dateValue(mixed $value): mixed
    {
        return $value instanceof DateTimeInterface ? $value : (string) $value;
    }
}
