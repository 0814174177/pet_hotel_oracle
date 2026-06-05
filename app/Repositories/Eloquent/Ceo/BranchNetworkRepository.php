<?php

namespace App\Repositories\Eloquent\Ceo;

use App\Models\Branch;
use App\Models\Employee;
use App\Repositories\Contracts\Ceo\BranchNetworkRepositoryInterface;
use DateTimeInterface;
use Illuminate\Database\Query\Builder as QueryBuilder;
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
        $start = filled($filters['start_date'] ?? null)
            ? \Illuminate\Support\Carbon::parse($filters['start_date'])->startOfDay()
            : now(config('app.timezone'))->startOfMonth();
        $end = filled($filters['end_date'] ?? null)
            ? \Illuminate\Support\Carbon::parse($filters['end_date'])->startOfDay()->addDay()
            : now(config('app.timezone'))->addMonthNoOverflow()->startOfMonth();

        $sql = <<<'SQL'
            WITH tham_so AS (
                SELECT
                    TO_DATE(:ngay_bat_dau, 'YYYY-MM-DD') AS ngay_bat_dau,
                    TO_DATE(:ngay_ket_thuc, 'YYYY-MM-DD') AS ngay_ket_thuc
                FROM dual
            ),
            doanh_thu_chi_nhanh AS (
                SELECT
                    b.branch_id,
                    SUM(NVL(o.grand_total, 0)) AS doanh_thu
                FROM branch b
                CROSS JOIN tham_so ts
                LEFT JOIN orders o
                    ON o.branch_id = b.branch_id
                   AND o.status IN ('PAID', 'COMPLETED')
                   AND o.paid_at >= ts.ngay_bat_dau
                   AND o.paid_at <  ts.ngay_ket_thuc
                GROUP BY b.branch_id
            ),
            doanh_thu_trung_binh AS (
                SELECT
                    AVG(NVL(dt.doanh_thu, 0)) AS doanh_thu_tb_chuoi
                FROM branch b
                LEFT JOIN doanh_thu_chi_nhanh dt
                    ON dt.branch_id = b.branch_id
                WHERE b.is_active = 1
            ),
            phong_theo_chi_nhanh AS (
                SELECT
                    branch_id,
                    COUNT(*) AS tong_phong,
                    SUM(CASE WHEN status = 'MAINTENANCE' THEN 1 ELSE 0 END) AS phong_bao_tri,
                    SUM(CASE WHEN status <> 'MAINTENANCE' THEN 1 ELSE 0 END) AS phong_co_the_ban
                FROM room
                GROUP BY branch_id
            ),
            phong_duoc_dat AS (
                SELECT
                    r.branch_id,
                    COUNT(DISTINCT r.room_id) AS so_phong_duoc_dat,
                    SUM(
                        GREATEST(
                            0,
                            LEAST(CAST(bk.checkout_expected_at AS DATE), ts.ngay_ket_thuc)
                            -
                            GREATEST(CAST(bk.checkin_expected_at AS DATE), ts.ngay_bat_dau)
                        )
                    ) AS booked_room_days
                FROM room r
                JOIN booking_room br
                    ON br.room_id = r.room_id
                JOIN booking bk
                    ON bk.booking_id = br.booking_id
                CROSS JOIN tham_so ts
                WHERE bk.status IN ('CONFIRMED', 'CHECKED_IN', 'CHECKED_OUT', 'COMPLETED')
                  AND CAST(bk.checkin_expected_at AS DATE) <  ts.ngay_ket_thuc
                  AND CAST(bk.checkout_expected_at AS DATE) > ts.ngay_bat_dau
                  AND r.status <> 'MAINTENANCE'
                GROUP BY r.branch_id
            ),
            quan_ly AS (
                SELECT
                    e.branch_id,
                    MAX(e.full_name) KEEP (
                        DENSE_RANK FIRST ORDER BY e.employee_id
                    ) AS ten_quan_ly,
                    MAX(e.phone) KEEP (
                        DENSE_RANK FIRST ORDER BY e.employee_id
                    ) AS sdt_quan_ly
                FROM employee e
                JOIN users u
                    ON u.id = e.user_id
                WHERE e.status = 1
                  AND u.is_active = 1
                  AND u.role = 'MANAGER'
                  AND e.position = 'MANAGER'
                GROUP BY e.branch_id
            ),
            so_booking AS (
                SELECT
                    bk.branch_id,
                    COUNT(*) AS bookings_count
                FROM booking bk
                CROSS JOIN tham_so ts
                WHERE CAST(bk.checkin_expected_at AS DATE) <  ts.ngay_ket_thuc
                  AND CAST(bk.checkout_expected_at AS DATE) > ts.ngay_bat_dau
                GROUP BY bk.branch_id
            ),
            so_nhan_vien AS (
                SELECT
                    branch_id,
                    COUNT(*) AS employees_count
                FROM employee
                WHERE status = 1
                GROUP BY branch_id
            ),
            du_lieu AS (
                SELECT
                    b.branch_id,
                    b.branch_name,
                    b.address,
                    b.is_active,
                    NVL(dt.doanh_thu, 0) AS doanh_thu,
                    NVL(ptcn.tong_phong, 0) AS tong_phong,
                    NVL(ptcn.phong_bao_tri, 0) AS phong_bao_tri,
                    NVL(ptcn.phong_co_the_ban, 0) AS phong_co_the_ban,
                    NVL(pdd.so_phong_duoc_dat, 0) AS so_phong_duoc_dat,
                    NVL(pdd.booked_room_days, 0) AS booked_room_days,
                    NVL(
                        ROUND(
                            NVL(pdd.booked_room_days, 0)
                            / NULLIF(
                                NVL(ptcn.phong_co_the_ban, 0)
                                * (ts.ngay_ket_thuc - ts.ngay_bat_dau),
                                0
                            ) * 100,
                            1
                        ),
                        0
                    ) AS ty_le_lap_day,
                    NVL(
                        ROUND(
                            NVL(ptcn.phong_bao_tri, 0)
                            / NULLIF(NVL(ptcn.tong_phong, 0), 0) * 100,
                            1
                        ),
                        0
                    ) AS ty_le_bao_tri,
                    ql.ten_quan_ly,
                    ql.sdt_quan_ly,
                    NVL((SELECT doanh_thu_tb_chuoi FROM doanh_thu_trung_binh), 0) AS doanh_thu_tb_chuoi,
                    NVL(sb.bookings_count, 0) AS bookings_count,
                    NVL(snv.employees_count, 0) AS employees_count
                FROM branch b
                CROSS JOIN tham_so ts
                LEFT JOIN doanh_thu_chi_nhanh dt
                    ON dt.branch_id = b.branch_id
                LEFT JOIN phong_theo_chi_nhanh ptcn
                    ON ptcn.branch_id = b.branch_id
                LEFT JOIN phong_duoc_dat pdd
                    ON pdd.branch_id = b.branch_id
                LEFT JOIN quan_ly ql
                    ON ql.branch_id = b.branch_id
                LEFT JOIN so_booking sb
                    ON sb.branch_id = b.branch_id
                LEFT JOIN so_nhan_vien snv
                    ON snv.branch_id = b.branch_id
            )
            SELECT
                branch_id,
                branch_name,
                'CN-' || branch_id AS branch_code,
                address,
                is_active,
                doanh_thu AS revenue,
                tong_phong AS total_rooms,
                phong_bao_tri AS maintenance_rooms,
                phong_co_the_ban AS sellable_rooms,
                so_phong_duoc_dat AS used_rooms,
                booked_room_days,
                ty_le_lap_day AS occupancy_rate,
                ty_le_bao_tri AS maintenance_rate,
                ten_quan_ly AS manager_name,
                sdt_quan_ly AS manager_phone,
                bookings_count,
                employees_count,
                doanh_thu_tb_chuoi AS average_chain_revenue,
                CASE
                    WHEN is_active = 0 THEN 'Ngưng hoạt động'
                    WHEN ty_le_bao_tri > 40 THEN 'Bảo trì nặng'
                    WHEN ty_le_bao_tri > 25 THEN 'Bảo trì'
                    ELSE 'Hoạt động'
                END AS status_label,
                CASE
                    WHEN is_active = 0 THEN 'Cảnh báo đỏ - chi nhánh đang ngưng hoạt động'
                    WHEN ty_le_bao_tri > 40 THEN 'Cảnh báo đỏ - tỷ lệ phòng bảo trì trên 40%'
                    WHEN ty_le_bao_tri > 25 THEN 'Cảnh báo vàng - tỷ lệ phòng bảo trì trên 25%'
                    WHEN ty_le_lap_day < 30 THEN 'Cảnh báo đỏ - lấp đầy dưới 30%, cần thanh tra vận hành'
                    WHEN ty_le_lap_day < 50 THEN 'Cảnh báo vàng - lấp đầy dưới 50%, cần kiểm tra vận hành/marketing'
                    WHEN doanh_thu_tb_chuoi > 0
                     AND doanh_thu < doanh_thu_tb_chuoi * 0.5
                        THEN 'Cảnh báo đỏ - doanh thu thấp hơn 50% trung bình chuỗi'
                    WHEN doanh_thu_tb_chuoi > 0
                     AND doanh_thu < doanh_thu_tb_chuoi * 0.7
                        THEN 'Cảnh báo vàng - doanh thu thấp hơn 70% trung bình chuỗi'
                    ELSE 'Ổn định'
                END AS warning
            FROM du_lieu
            ORDER BY doanh_thu DESC
            SQL;

        $branches = collect(DB::select($sql, [
            'ngay_bat_dau' => $start->toDateString(),
            'ngay_ket_thuc' => $end->toDateString(),
        ]))->map(function (object $row): array {
            $branch = new Branch();
            $branch->forceFill([
                'branch_name' => (string) ($this->rowValue($row, 'branch_name') ?? ''),
                'address' => (string) ($this->rowValue($row, 'address') ?? ''),
            ]);

            $isActive = (int) ($this->rowValue($row, 'is_active') ?? 0) === self::ACTIVE_BRANCH_VALUE;

            return [
                'branch_id' => (int) ($this->rowValue($row, 'branch_id') ?? 0),
                'branch_code' => (string) ($this->rowValue($row, 'branch_code') ?? ''),
                'branch_name' => (string) ($this->rowValue($row, 'branch_name') ?? ''),
                'region' => $this->regionFrom($branch),
                'revenue' => $this->cleanNumber((float) ($this->rowValue($row, 'revenue') ?? 0)),
                'occupancy_rate' => $this->cleanNumber((float) ($this->rowValue($row, 'occupancy_rate') ?? 0)),
                'maintenance_rate' => $this->cleanNumber((float) ($this->rowValue($row, 'maintenance_rate') ?? 0)),
                'used_rooms' => (int) ($this->rowValue($row, 'used_rooms') ?? 0),
                'total_rooms' => (int) ($this->rowValue($row, 'total_rooms') ?? 0),
                'rooms_count' => (int) ($this->rowValue($row, 'total_rooms') ?? 0),
                'maintenance_rooms' => (int) ($this->rowValue($row, 'maintenance_rooms') ?? 0),
                'sellable_rooms' => (int) ($this->rowValue($row, 'sellable_rooms') ?? 0),
                'booked_room_days' => $this->cleanNumber((float) ($this->rowValue($row, 'booked_room_days') ?? 0)),
                'manager_name' => $this->rowValue($row, 'manager_name'),
                'manager_phone' => $this->rowValue($row, 'manager_phone'),
                'status' => $isActive ? 'active' : 'inactive',
                'status_label' => (string) ($this->rowValue($row, 'status_label') ?? ''),
                'warning' => (string) ($this->rowValue($row, 'warning') ?? ''),
                'average_chain_revenue' => $this->cleanNumber((float) ($this->rowValue($row, 'average_chain_revenue') ?? 0)),
                'bookings_count' => (int) ($this->rowValue($row, 'bookings_count') ?? 0),
                'employees_count' => (int) ($this->rowValue($row, 'employees_count') ?? 0),
                'address' => (string) ($this->rowValue($row, 'address') ?? ''),
            ];
        });

        $sortFilters = filled($filters['sort_by'] ?? null)
            ? $filters
            : [
                ...$filters,
                'sort_by' => 'revenue',
                'sort_direction' => 'desc',
            ];

        return $this->sortBranches(
            $this->applyFilters($branches, $filters),
            $sortFilters
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

                return $items->filter(function (array $branch) use ($status): bool {
                    $statusLabel = $this->searchable((string) ($branch['status_label'] ?? ''));
                    $isMaintenance = Str::contains($statusLabel, 'bao tri');
                    $isInactive = $branch['status'] === 'inactive'
                        || Str::contains($statusLabel, ['ngung', 'tam ngung']);

                    return match ($status) {
                        'active' => $branch['status'] === 'active' && ! $isMaintenance,
                        'inactive' => $isInactive,
                        'maintenance' => $isMaintenance,
                        default => $branch['status'] === $status || Str::contains($statusLabel, $status),
                    };
                });
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
            '0', 'false', 'inactive', 'disabled', 'ngung hoat dong', 'tam ngung' => 'inactive',
            'maintenance', 'bao tri', 'bao tri nang' => 'maintenance',
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

    private function applyDateFilters(QueryBuilder $query, string $column, array $filters): void
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
