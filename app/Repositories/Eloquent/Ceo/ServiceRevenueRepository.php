<?php

namespace App\Repositories\Eloquent\Ceo;

use App\Models\Branch;
use App\Models\Service;
use App\Repositories\Contracts\Ceo\ServiceRevenueRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ServiceRevenueRepository implements ServiceRevenueRepositoryInterface
{
    private const ACTIVE_VALUE = 1;

    private const PAID_PAYMENT_STATUSES = [
        'SUCCESS',
    ];

    private const PAID_ORDER_STATUSES = [
        'COMPLETED',
        'PAID',
    ];

    private const SERVED_SERVICE_STATUSES = [
        'DONE',
    ];

    private const SORTABLE_COLUMNS = [
        'service_id',
        'service_name',
        'revenue',
        'revenue_share',
        'served_count_30_days',
        'coverage_rate',
        'status',
    ];

    public function getServiceSummary(array $filters): array
    {
        $sql = <<<'SQL'
            WITH report_params AS (
                SELECT
                    TRUNC(TO_DATE(:p_start_date, 'YYYY-MM-DD')) AS current_start_date,
                    TRUNC(TO_DATE(:p_end_date, 'YYYY-MM-DD')) AS current_end_date
                FROM dual
            ),
            periods AS (
                SELECT
                    'current' AS period_type,
                    current_start_date AS start_date,
                    current_end_date AS end_date
                FROM report_params

                UNION ALL

                SELECT
                    'comparison' AS period_type,
                    ADD_MONTHS(current_start_date, -1) AS start_date,
                    ADD_MONTHS(current_end_date, -1) AS end_date
                FROM report_params
            ),
            service_revenue AS (
                SELECT
                    p.period_type,
                    s.service_id,
                    s.service_name,
                    SUM(NVL(od.line_total, 0)) AS service_revenue
                FROM periods p
                JOIN orders o
                    ON o.paid_at >= p.start_date
                   AND o.paid_at <  p.end_date + 1
                JOIN order_details od
                    ON od.order_id = o.order_id
                JOIN booking_service_pet bsp
                    ON bsp.booking_service_pet_id = od.booking_service_pet_id
                JOIN booking bk
                    ON bk.booking_id = bsp.booking_id
                JOIN services s
                    ON s.service_id = bsp.service_id
                WHERE o.status IN ('PAID', 'COMPLETED')
                  AND od.booking_service_pet_id IS NOT NULL
                  AND bsp.status = 'DONE'
                  AND bk.status <> 'CANCELLED'
                GROUP BY
                    p.period_type,
                    s.service_id,
                    s.service_name
            ),
            top_service_ranked AS (
                SELECT
                    sr.period_type,
                    sr.service_id,
                    sr.service_name,
                    sr.service_revenue,
                    SUM(sr.service_revenue) OVER (
                        PARTITION BY sr.period_type
                    ) AS total_service_revenue,
                    ROW_NUMBER() OVER (
                        PARTITION BY sr.period_type
                        ORDER BY sr.service_revenue DESC, sr.service_id
                    ) AS rn
                FROM service_revenue sr
            ),
            top_service AS (
                SELECT
                    period_type,
                    service_id,
                    service_name,
                    service_revenue,
                    total_service_revenue,
                    ROUND(
                        service_revenue / NULLIF(total_service_revenue, 0) * 100,
                        2
                    ) AS revenue_share
                FROM top_service_ranked
                WHERE rn = 1
            ),
            no_activity AS (
                SELECT
                    p.period_type,
                    COUNT(*) AS no_activity_service_count
                FROM periods p
                CROSS JOIN services s
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM booking_service_pet bsp
                    WHERE bsp.service_id = s.service_id
                      AND bsp.scheduled_at >= p.start_date
                      AND bsp.scheduled_at <  p.end_date + 1
                )
                GROUP BY p.period_type
            ),
            active_services AS (
                SELECT COUNT(*) AS active_service_count
                FROM services
                WHERE is_active = 1
            )
            SELECT
                p.period_type,
                TO_CHAR(p.start_date, 'YYYY-MM-DD') AS period_start_date,
                TO_CHAR(p.end_date, 'YYYY-MM-DD') AS period_end_date,
                ts.service_id AS top_service_id,
                ts.service_name AS top_service_name,
                NVL(ts.service_revenue, 0) AS top_service_revenue,
                NVL(ts.total_service_revenue, 0) AS total_service_revenue,
                NVL(ts.revenue_share, 0) AS top_service_revenue_share,
                NVL(na.no_activity_service_count, 0) AS no_activity_service_count,
                ac.active_service_count
            FROM periods p
            LEFT JOIN top_service ts
                ON ts.period_type = p.period_type
            LEFT JOIN no_activity na
                ON na.period_type = p.period_type
            CROSS JOIN active_services ac
            ORDER BY
                CASE p.period_type
                    WHEN 'current' THEN 1
                    ELSE 2
                END
            SQL;

        $rows = collect(DB::select($sql, [
            'p_start_date' => $filters['start_date'],
            'p_end_date' => $filters['end_date'],
        ]));

        $currentRow = $rows->first(
            fn (object $row): bool => $this->rowValue($row, 'period_type') === 'current'
        );
        $comparisonRow = $rows->first(
            fn (object $row): bool => $this->rowValue($row, 'period_type') === 'comparison'
        );
        $currentTopService = $this->mapTopService($currentRow);
        $comparisonTopService = $this->mapTopService($comparisonRow);
        $currentNoActivityCount = (int) ($this->rowValue($currentRow, 'no_activity_service_count') ?? 0);
        $comparisonNoActivityCount = (int) ($this->rowValue($comparisonRow, 'no_activity_service_count') ?? 0);

        return [
            'period' => [
                'current' => [
                    'start_date' => (string) ($this->rowValue($currentRow, 'period_start_date') ?? $filters['start_date']),
                    'end_date' => (string) ($this->rowValue($currentRow, 'period_end_date') ?? $filters['end_date']),
                ],
                'comparison' => [
                    'type' => 'previous_month',
                    'start_date' => (string) ($this->rowValue($comparisonRow, 'period_start_date') ?? ''),
                    'end_date' => (string) ($this->rowValue($comparisonRow, 'period_end_date') ?? ''),
                ],
            ],
            'top_revenue_service' => [
                'current' => $currentTopService,
                'comparison' => $comparisonTopService,
                'change' => $this->calculateChange(
                    $currentTopService['revenue'] ?? 0,
                    $comparisonTopService['revenue'] ?? 0
                ),
            ],
            'active_service_count' => [
                'current' => (int) ($this->rowValue($currentRow, 'active_service_count') ?? 0),
                'comparison' => null,
                'change' => null,
                'note' => 'Chỉ tính được trạng thái hiện tại vì bảng services không có lịch sử trạng thái theo kỳ.',
            ],
            'no_activity_service_count' => [
                'current' => $currentNoActivityCount,
                'comparison' => $comparisonNoActivityCount,
                'change' => $this->calculateChange($currentNoActivityCount, $comparisonNoActivityCount),
            ],
        ];
    }

    public function getServiceCatalog(array $filters = []): array
    {
        return Service::with('category')
            ->orderBy('service_name')
            ->get()
            ->all();
    }

    public function getHighestRevenueService(array $filters = []): ?array
    {
        $service = collect($this->getServiceRevenueList($filters))
            ->where('status', 'active')
            ->filter(fn (array $service): bool => (float) $service['revenue'] > 0)
            ->sortByDesc('revenue')
            ->values()
            ->first();

        return $service ? $this->kpiPayload($service) : null;
    }

    public function getLowestRevenueService(array $filters = []): ?array
    {
        $service = collect($this->getServiceRevenueList($filters))
            ->where('status', 'active')
            ->filter(fn (array $service): bool => (float) $service['revenue'] > 0)
            ->sortBy('revenue')
            ->values()
            ->first();

        return $service ? $this->kpiPayload($service) : null;
    }

    public function getNoActivityServices(array $filters): array
    {
        [$startDate, $endDate] = $this->dateRange($filters);

        $sql = <<<'SQL'
            SELECT
                s.service_id,
                s.service_name
            FROM services s
            WHERE NOT EXISTS (
                SELECT 1
                FROM booking_service_pet bsp
                WHERE bsp.service_id = s.service_id
                  AND bsp.scheduled_at >= TO_DATE(:p_start_date, 'YYYY-MM-DD')
                  AND bsp.scheduled_at <  TO_DATE(:p_end_date, 'YYYY-MM-DD') + 1
            )
            ORDER BY s.service_name, s.service_id
            SQL;

        return array_map(function (object $row): array {
            return [
                'service_id' => (int) ($this->rowValue($row, 'service_id') ?? 0),
                'service_name' => (string) ($this->rowValue($row, 'service_name') ?? ''),
            ];
        }, DB::select($sql, [
            'p_start_date' => $startDate,
            'p_end_date' => $endDate,
        ]));
    }

    public function getMostProfitableService(array $filters): ?array
    {
        $service = Service::query()
            ->where('is_active', self::ACTIVE_VALUE)
            ->with('serviceProductDetails.product')
            ->get()
            ->map(function (Service $service): array {
                $estimatedMaterialCost = (float) $service->serviceProductDetails->sum(
                    fn ($detail): float => (float) $detail->amount * (float) ($detail->product?->item_price ?? 0)
                );
                $estimatedProfit = (float) $service->base_price - $estimatedMaterialCost;

                return [
                    'service_id' => (int) $service->service_id,
                    'service_name' => (string) $service->service_name,
                    'selling_price' => $this->cleanNumber((float) $service->base_price),
                    'estimated_material_cost' => $this->cleanNumber($estimatedMaterialCost),
                    'estimated_profit' => $this->cleanNumber($estimatedProfit),
                ];
            })
            ->sortByDesc('estimated_profit')
            ->values()
            ->first();

        return $service ?: null;
    }

    public function getServiceRevenueList(array $filters = []): array
    {
        $this->resolvePeriodRange(
            $filters['period_type'] ?? $filters['period'] ?? null,
            $filters['date'] ?? null,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        $usePayments = $this->hasSuccessfulPayments();
        $revenueByService = $this->revenueByService($usePayments);
        $servedCount30Days = $this->servedCountByServiceLast30Days($usePayments);
        $activeBranchCount = $this->activeBranchCount();
        $coverageBranchCount = $this->coverageBranchCountByService($usePayments);
        $totalRevenue = (float) $revenueByService->sum();

        $services = Service::query()
            ->orderBy('service_name')
            ->get()
            ->map(function (Service $service) use (
                $revenueByService,
                $servedCount30Days,
                $activeBranchCount,
                $coverageBranchCount,
                $totalRevenue
            ): array {
                $serviceId = (string) $service->service_id;
                $revenue = (float) ($revenueByService->get($serviceId, 0) ?? 0);
                $branchCount = (int) ($coverageBranchCount->get($serviceId, 0) ?? 0);

                return [
                    'service_id' => (int) $service->service_id,
                    'service_name' => (string) $service->service_name,
                    'served_count_30_days' => (int) ($servedCount30Days->get($serviceId, 0) ?? 0),
                    'coverage_rate' => $this->coverageRate($branchCount, $activeBranchCount),
                    'revenue' => $this->cleanNumber($revenue),
                    'revenue_share' => $this->revenueShare($revenue, $totalRevenue),
                    'status' => ((int) $service->is_active === self::ACTIVE_VALUE) ? 'active' : 'inactive',
                ];
            });

        return $this->sortServices(
            $this->applyFilters($services, $filters),
            $filters
        )
            ->values()
            ->all();
    }

    public function resolvePeriodRange(
        ?string $periodType,
        ?string $date = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        // TODO: Resolve period_type/date/start_date/end_date before applying period-aware service revenue metrics.
        return [];
    }

    private function kpiPayload(array $service): array
    {
        return [
            'service_id' => $service['service_id'],
            'service_name' => $service['service_name'],
            'revenue' => $service['revenue'],
            'revenue_share' => $service['revenue_share'],
        ];
    }

    private function revenueByService(bool $usePayments): Collection
    {
        return $this->serviceTransactionQuery($usePayments)
            ->select([
                'booking_service_pet.service_id',
                DB::raw('SUM(order_details.line_total) AS revenue'),
            ])
            ->groupBy('booking_service_pet.service_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (string) $this->rowValue($row, 'service_id') => (float) $this->rowValue($row, 'revenue'),
            ]);
    }

    private function servedCountByServiceLast30Days(bool $usePayments): Collection
    {
        return $this->serviceTransactionQuery($usePayments)
            ->where('booking_service_pet.scheduled_at', '>=', now()->subDays(30))
            ->select([
                'booking_service_pet.service_id',
                DB::raw('SUM(order_details.quantity) AS served_count'),
            ])
            ->groupBy('booking_service_pet.service_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (string) $this->rowValue($row, 'service_id') => (int) $this->rowValue($row, 'served_count'),
            ]);
    }

    private function coverageBranchCountByService(bool $usePayments): Collection
    {
        return $this->serviceTransactionQuery($usePayments)
            ->join('branch', 'orders.branch_id', '=', 'branch.branch_id')
            ->where('branch.is_active', self::ACTIVE_VALUE)
            ->select([
                'booking_service_pet.service_id',
                DB::raw('COUNT(DISTINCT orders.branch_id) AS branch_count'),
            ])
            ->groupBy('booking_service_pet.service_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (string) $this->rowValue($row, 'service_id') => (int) $this->rowValue($row, 'branch_count'),
            ]);
    }

    private function serviceTransactionQuery(bool $usePayments): Builder
    {
        $query = DB::table('order_details')
            ->join('orders', 'order_details.order_id', '=', 'orders.order_id')
            ->join(
                'booking_service_pet',
                'order_details.booking_service_pet_id',
                '=',
                'booking_service_pet.booking_service_pet_id'
            )
            ->join('booking', 'booking_service_pet.booking_id', '=', 'booking.booking_id')
            ->whereNotNull('order_details.booking_service_pet_id')
            ->whereIn('orders.status', self::PAID_ORDER_STATUSES)
            ->whereNotNull('orders.paid_at')
            ->where('booking.status', '<>', 'CANCELLED')
            ->whereIn('booking_service_pet.status', self::SERVED_SERVICE_STATUSES);

        if ($usePayments) {
            // Payments confirm the order is paid; service revenue comes from order_details because payments are order-level.
            $query->join('payments', 'orders.order_id', '=', 'payments.order_id')
                ->whereIn('payments.status', self::PAID_PAYMENT_STATUSES)
                ->whereNotNull('payments.paid_at');
        }

        return $query;
    }

    private function hasSuccessfulPayments(): bool
    {
        try {
            return Schema::hasTable('payments')
                && DB::table('payments')->whereIn('status', self::PAID_PAYMENT_STATUSES)->exists();
        } catch (Throwable) {
            return false;
        }
    }

    private function activeBranchCount(): int
    {
        return (int) Branch::query()
            ->where('is_active', self::ACTIVE_VALUE)
            ->count();
    }

    private function applyFilters(Collection $services, array $filters): Collection
    {
        return $services
            ->when(filled($filters['status'] ?? null), function (Collection $items) use ($filters): Collection {
                $status = $this->normalizeStatus((string) $filters['status']);

                return $items->filter(fn (array $service): bool => $service['status'] === $status);
            })
            ->when(is_numeric($filters['min_revenue'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $service): bool => (float) $service['revenue'] >= (float) $filters['min_revenue']);
            })
            ->when(is_numeric($filters['max_revenue'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $service): bool => (float) $service['revenue'] <= (float) $filters['max_revenue']);
            })
            ->when(is_numeric($filters['min_coverage_rate'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $service): bool => (float) $service['coverage_rate'] >= (float) $filters['min_coverage_rate']);
            })
            ->when(is_numeric($filters['max_coverage_rate'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $service): bool => (float) $service['coverage_rate'] <= (float) $filters['max_coverage_rate']);
            })
            ->when(is_numeric($filters['min_served_count'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $service): bool => (int) $service['served_count_30_days'] >= (int) $filters['min_served_count']);
            })
            ->when(is_numeric($filters['max_served_count'] ?? null), function (Collection $items) use ($filters): Collection {
                return $items->filter(fn (array $service): bool => (int) $service['served_count_30_days'] <= (int) $filters['max_served_count']);
            });
    }

    private function sortServices(Collection $services, array $filters): Collection
    {
        $sortBy = (string) ($filters['sort_by'] ?? 'revenue');

        if (! in_array($sortBy, self::SORTABLE_COLUMNS, true)) {
            $sortBy = 'revenue';
        }

        $direction = Str::lower((string) ($filters['sort_direction'] ?? 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        return $direction === 'asc'
            ? $services->sortBy($sortBy)
            : $services->sortByDesc($sortBy);
    }

    private function revenueShare(float $serviceRevenue, float $totalRevenue): int|float
    {
        if ($totalRevenue <= 0) {
            return 0;
        }

        // revenue_share = service revenue / total valid service revenue * 100.
        return $this->cleanNumber(($serviceRevenue / $totalRevenue) * 100);
    }

    private function coverageRate(int $branchCount, int $activeBranchCount): int|float
    {
        if ($activeBranchCount <= 0) {
            return 0;
        }

        // coverage_rate = active branches with this service / total active branches * 100.
        return $this->cleanNumber(($branchCount / $activeBranchCount) * 100);
    }

    private function normalizeStatus(string $status): string
    {
        return match ($this->searchable($status)) {
            '1', 'true', 'active', 'enabled', 'dang hoat dong', 'hoat dong' => 'active',
            '0', 'false', 'inactive', 'disabled', 'ngung hoat dong', 'tam ngung' => 'inactive',
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

    private function mapTopService(?object $row): ?array
    {
        $serviceId = $this->rowValue($row, 'top_service_id');

        if ($serviceId === null) {
            return null;
        }

        return [
            'service_id' => (int) $serviceId,
            'service_name' => (string) ($this->rowValue($row, 'top_service_name') ?? ''),
            'revenue' => (float) ($this->rowValue($row, 'top_service_revenue') ?? 0),
            'total_service_revenue' => (float) ($this->rowValue($row, 'total_service_revenue') ?? 0),
            'revenue_share' => (float) ($this->rowValue($row, 'top_service_revenue_share') ?? 0),
        ];
    }

    private function calculateChange(float|int $current, float|int $comparison): array
    {
        $difference = $current - $comparison;

        return [
            'value' => $this->cleanNumber((float) $difference),
            'percent' => $comparison == 0
                ? null
                : round(($difference / $comparison) * 100, 2),
            'trend' => match (true) {
                $difference > 0 => 'up',
                $difference < 0 => 'down',
                default => 'equal',
            },
        ];
    }

    private function dateRange(array $filters): array
    {
        $today = CarbonImmutable::today(config('app.timezone'));

        return [
            (string) ($filters['start_date'] ?? $today->startOfMonth()->toDateString()),
            (string) ($filters['end_date'] ?? $today->endOfMonth()->toDateString()),
        ];
    }

    private function rowValue(?object $row, string $key): mixed
    {
        if ($row === null) {
            return null;
        }

        return $row->{$key} ?? $row->{strtoupper($key)} ?? null;
    }
}
