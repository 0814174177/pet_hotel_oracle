<?php

namespace App\Repositories\Eloquent\Ceo;

use App\Models\Service;
use App\Repositories\Contracts\Ceo\CeoFinanceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CeoFinanceRepository implements CeoFinanceRepositoryInterface
{
    private const PAYMENT_SUCCESS_STATUSES = [
        'SUCCESS',
    ];

    private const ORDER_REVENUE_STATUSES = [
        'COMPLETED',
        'PAID',
    ];

    private const ORDER_EXCLUDED_STATUSES = [
        'CANCELLED',
        'REFUNDED',
    ];

    private const RECEIPT_VALID_STATUSES = [
        'APPROVED',
    ];

    private const SERVICE_VALID_STATUSES = [
        'DONE',
    ];

    public function getFinanceData(array $filters = []): array
    {
        $range = $this->resolvePeriodRange(
            $filters['period_type'] ?? $filters['period'] ?? 'month',
            $filters['date'] ?? null,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        $revenueRows = $this->revenueRows($range);
        $inventoryCostRows = $this->inventoryCostRows($range);
        $totalRevenue = (float) $revenueRows->sum('amount');
        $totalInventoryCost = (float) $inventoryCostRows->sum('amount');

        return [
            'period' => $range['period'],
            'date_range' => [
                'start_date' => $range['start']->toDateString(),
                'end_date' => $range['end']->toDateString(),
            ],
            'kpi_cards' => [
                'total_revenue' => $this->cleanNumber($totalRevenue),
                'total_inventory_cost' => $this->cleanNumber($totalInventoryCost),
                // Temporary profit: this subtracts inventory cost only, not salary, rent, utilities, marketing, or depreciation.
                'net_profit' => $this->cleanNumber($totalRevenue - $totalInventoryCost),
            ],
            'revenue_cost_chart' => $this->revenueCostChart($range, $revenueRows, $inventoryCostRows),
            'revenue_mix_chart' => $this->revenueMixChart($range),
            'gross_margin_analysis' => $this->grossMarginAnalysis(),
            'loss_risk_alerts' => $this->lossRiskAlerts(),
        ];
    }

    private function resolvePeriodRange(
        string $period,
        ?string $date = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array
    {
        $period = in_array($period, ['day', 'month', 'year'], true) ? $period : 'month';

        if ($startDate || $endDate) {
            $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
            $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

            return [
                'period' => $period,
                'group_by' => $this->groupByFor($period),
                'start' => $start,
                'end' => $end,
            ];
        }

        if ($date) {
            $selectedDate = Carbon::parse($date);

            return match ($period) {
                'day' => [
                    'period' => 'day',
                    'group_by' => 'hour',
                    'start' => $selectedDate->copy()->startOfDay(),
                    'end' => $selectedDate->copy()->endOfDay(),
                ],
                'year' => [
                    'period' => 'year',
                    'group_by' => 'month',
                    'start' => $selectedDate->copy()->startOfYear(),
                    'end' => $selectedDate->copy()->endOfYear(),
                ],
                default => [
                    'period' => 'month',
                    'group_by' => 'day',
                    'start' => $selectedDate->copy()->startOfMonth(),
                    'end' => $selectedDate->copy()->endOfMonth(),
                ],
            };
        }

        return match ($period) {
            'day' => [
                'period' => 'day',
                'group_by' => 'hour',
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'year' => [
                'period' => 'year',
                'group_by' => 'month',
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear(),
            ],
            default => [
                'period' => 'month',
                'group_by' => 'day',
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
        };
    }

    private function groupByFor(string $period): string
    {
        return match ($period) {
            'day' => 'hour',
            'year' => 'month',
            default => 'day',
        };
    }

    private function revenueRows(array $range): Collection
    {
        if ($this->hasSuccessfulPayments()) {
            return DB::table('payments')
                ->join('orders', 'payments.order_id', '=', 'orders.order_id')
                ->whereIn('payments.status', self::PAYMENT_SUCCESS_STATUSES)
                ->whereNotNull('payments.paid_at')
                ->whereNotIn('orders.status', self::ORDER_EXCLUDED_STATUSES)
                ->whereBetween('payments.paid_at', [$range['start'], $range['end']])
                ->select([
                    'payments.amount',
                    'payments.paid_at as occurred_at',
                ])
                ->get()
                ->map(fn (object $row): array => [
                    'amount' => (float) $this->rowValue($row, 'amount'),
                    'occurred_at' => $this->rowValue($row, 'occurred_at'),
                ]);
        }

        return DB::table('orders')
            ->whereIn('status', self::ORDER_REVENUE_STATUSES)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$range['start'], $range['end']])
            ->select([
                'grand_total as amount',
                'paid_at as occurred_at',
            ])
            ->get()
            ->map(fn (object $row): array => [
                'amount' => (float) $this->rowValue($row, 'amount'),
                'occurred_at' => $this->rowValue($row, 'occurred_at'),
            ]);
    }

    private function inventoryCostRows(array $range): Collection
    {
        if (! Schema::hasTable('goods_receipt') || ! Schema::hasTable('goods_receipt_detail')) {
            return collect();
        }

        return DB::table('goods_receipt_detail')
            ->join('goods_receipt', 'goods_receipt_detail.goods_receipt_id', '=', 'goods_receipt.goods_receipt_id')
            ->whereIn('goods_receipt.status', self::RECEIPT_VALID_STATUSES)
            ->whereBetween('goods_receipt.receipt_date', [$range['start'], $range['end']])
            ->select([
                'goods_receipt_detail.line_total as amount',
                'goods_receipt.receipt_date as occurred_at',
            ])
            ->get()
            ->map(fn (object $row): array => [
                'amount' => (float) $this->rowValue($row, 'amount'),
                'occurred_at' => $this->rowValue($row, 'occurred_at'),
            ]);
    }

    private function revenueCostChart(array $range, Collection $revenueRows, Collection $inventoryCostRows): array
    {
        $buckets = $this->emptyBuckets($range);
        $revenue = $buckets;
        $inventoryCost = $buckets;

        foreach ($revenueRows as $row) {
            $key = $this->bucketKey($row['occurred_at'], $range['group_by']);

            if (array_key_exists($key, $revenue)) {
                $revenue[$key] += (float) $row['amount'];
            }
        }

        foreach ($inventoryCostRows as $row) {
            $key = $this->bucketKey($row['occurred_at'], $range['group_by']);

            if (array_key_exists($key, $inventoryCost)) {
                $inventoryCost[$key] += (float) $row['amount'];
            }
        }

        return [
            'labels' => $this->bucketLabels($range),
            'revenue' => array_map(fn (float $value): int|float => $this->cleanNumber($value), array_values($revenue)),
            'inventory_cost' => array_map(fn (float $value): int|float => $this->cleanNumber($value), array_values($inventoryCost)),
        ];
    }

    private function revenueMixChart(array $range): array
    {
        $buckets = $this->emptyBuckets($range);
        $groomingRevenue = $buckets;
        $hotelRevenue = $buckets;

        foreach ($this->orderDetailRevenueRows($range) as $row) {
            $key = $this->bucketKey($row['occurred_at'], $range['group_by']);

            if (! array_key_exists($key, $groomingRevenue)) {
                continue;
            }

            if ($row['type'] === 'hotel') {
                $hotelRevenue[$key] += (float) $row['amount'];
            } else {
                $groomingRevenue[$key] += (float) $row['amount'];
            }
        }

        return [
            'labels' => $this->bucketLabels($range),
            'grooming_revenue' => array_map(fn (float $value): int|float => $this->cleanNumber($value), array_values($groomingRevenue)),
            'hotel_revenue' => array_map(fn (float $value): int|float => $this->cleanNumber($value), array_values($hotelRevenue)),
        ];
    }

    private function orderDetailRevenueRows(array $range): Collection
    {
        $query = DB::table('order_details')
            ->join('orders', 'order_details.order_id', '=', 'orders.order_id')
            ->whereBetween($this->hasSuccessfulPayments() ? 'payments.paid_at' : 'orders.paid_at', [$range['start'], $range['end']])
            ->whereNotIn('orders.status', self::ORDER_EXCLUDED_STATUSES);

        if ($this->hasSuccessfulPayments()) {
            $query->join('payments', 'orders.order_id', '=', 'payments.order_id')
                ->whereIn('payments.status', self::PAYMENT_SUCCESS_STATUSES)
                ->whereNotNull('payments.paid_at')
                ->select([
                    'order_details.line_total',
                    'orders.subtotal',
                    'payments.amount as paid_amount',
                    'order_details.booking_room_id',
                    'order_details.booking_service_pet_id',
                    'payments.paid_at as occurred_at',
                ]);
        } else {
            $query->whereIn('orders.status', self::ORDER_REVENUE_STATUSES)
                ->whereNotNull('orders.paid_at')
                ->select([
                    'order_details.line_total',
                    'orders.subtotal',
                    'orders.grand_total as paid_amount',
                    'order_details.booking_room_id',
                    'order_details.booking_service_pet_id',
                    'orders.paid_at as occurred_at',
                ]);
        }

        return $query->get()
            ->map(function (object $row): array {
                $lineTotal = (float) $this->rowValue($row, 'line_total');
                $subtotal = (float) $this->rowValue($row, 'subtotal');
                $paidAmount = (float) $this->rowValue($row, 'paid_amount');

                return [
                    // Allocate actual collected order revenue back to each detail line so discounts/partial payments do not inflate the mix.
                    'amount' => $subtotal > 0 ? ($lineTotal / $subtotal) * $paidAmount : $lineTotal,
                    'occurred_at' => $this->rowValue($row, 'occurred_at'),
                    'type' => filled($this->rowValue($row, 'booking_room_id')) ? 'hotel' : 'grooming',
                ];
            });
    }

    private function grossMarginAnalysis(): array
    {
        return Service::query()
            ->where('is_active', 1)
            ->with('serviceProductDetails.product')
            ->orderBy('service_name')
            ->get()
            ->map(function (Service $service): array {
                $sellingPrice = (float) $service->base_price;
                // Cost is estimated from material norms because this schema has no direct service cost table.
                $costPrice = (float) $service->serviceProductDetails->sum(function ($detail): float {
                    return (float) $detail->amount * (float) ($detail->product?->item_price ?? 0);
                });
                $grossProfit = $sellingPrice - $costPrice;

                return [
                    'service_name' => (string) $service->service_name,
                    'selling_price' => $this->cleanNumber($sellingPrice),
                    'cost_price' => $this->cleanNumber($costPrice),
                    'gross_profit' => $this->cleanNumber($grossProfit),
                    // margin_percent = gross profit / selling price * 100.
                    'margin_percent' => $sellingPrice > 0 ? $this->cleanNumber(($grossProfit / $sellingPrice) * 100) : 0,
                ];
            })
            ->values()
            ->all();
    }

    private function lossRiskAlerts(): array
    {
        // Current migrated schema has material norms and stock balances, but no stock export/consumption ledger.
        // Without actual consumption rows, expected vs actual loss cannot be calculated reliably.
        return [];
    }

    private function emptyBuckets(array $range): array
    {
        $buckets = [];
        $cursor = $range['start']->copy();

        while ($cursor->lte($range['end'])) {
            $buckets[$this->bucketKey($cursor, $range['group_by'])] = 0.0;
            $cursor = match ($range['group_by']) {
                'hour' => $cursor->addHour(),
                'month' => $cursor->addMonthNoOverflow(),
                default => $cursor->addDay(),
            };
        }

        return $buckets;
    }

    private function bucketLabels(array $range): array
    {
        return collect(array_keys($this->emptyBuckets($range)))
            ->map(function (string $key) use ($range): string {
                return match ($range['group_by']) {
                    'hour' => Carbon::createFromFormat('!Y-m-d H', $key)->format('H:00'),
                    'month' => Carbon::createFromFormat('!Y-m', $key)->format('m/Y'),
                    default => Carbon::createFromFormat('!Y-m-d', $key)->format('d/m'),
                };
            })
            ->all();
    }

    private function bucketKey(mixed $value, string $groupBy): string
    {
        $date = $value instanceof Carbon ? $value : Carbon::parse($value);

        return match ($groupBy) {
            'hour' => $date->format('Y-m-d H'),
            'month' => $date->format('Y-m'),
            default => $date->format('Y-m-d'),
        };
    }

    private function hasSuccessfulPayments(): bool
    {
        return Schema::hasTable('payments')
            && DB::table('payments')->whereIn('status', self::PAYMENT_SUCCESS_STATUSES)->exists();
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
}
