<?php

namespace App\Http\Controllers\Api\Ceo;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\ReportFilterRequest;
use App\Repositories\Contracts\Ceo\ServiceRevenueRepositoryInterface;
use Illuminate\Http\JsonResponse;

class ServiceAnalyticsController extends ApiController
{
    public function __construct(
        protected ServiceRevenueRepositoryInterface $serviceRevenues
    ) {
    }

    public function kpi(ReportFilterRequest $request): JsonResponse
    {
        $filters = $request->reportFilters();

        $services = collect($this->serviceRevenues->getServiceRevenueList($filters));

        return response()->json([
            'success' => true,
            'message' => 'Service KPI data loaded successfully',
            'data' => [
                'top_revenue_service' => $this->formatKpiService($this->serviceRevenues->getHighestRevenueService($filters)),
                'lowest_revenue_service' => $this->formatKpiService($this->serviceRevenues->getLowestRevenueService($filters)),
                'total_service_revenue' => $this->cleanNumber((float) $services->sum('revenue')),
            ],
        ]);
    }

    public function index(ReportFilterRequest $request): JsonResponse
    {
        $filters = array_merge($request->reportFilters(), $request->validate([
            'service_status' => ['nullable', 'string', 'max:50'],
            'sort_by' => ['nullable', 'string'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]));

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));
        $data = collect($this->serviceRevenues->getServiceRevenueList($filters))
            ->map(fn (array $service): array => [
                'service_code' => $this->serviceCode($service['service_id']),
                'service_name' => $service['service_name'],
                'served_count_last_30_days' => $service['served_count_30_days'],
                'coverage_rate' => $service['coverage_rate'],
            ])
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Service analytics list loaded successfully',
            'data' => $data->forPage($page, $perPage)->values(),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $data->count(),
                'last_page' => max(1, (int) ceil($data->count() / $perPage)),
            ],
        ]);
    }

    private function formatKpiService(?array $service): ?array
    {
        if (! $service) {
            return null;
        }

        return [
            'service_code' => $this->serviceCode($service['service_id']),
            'service_name' => $service['service_name'],
            'revenue' => $service['revenue'],
            'revenue_share_percent' => $service['revenue_share'],
        ];
    }

    private function serviceCode(mixed $serviceId): string
    {
        return is_numeric($serviceId)
            ? sprintf('SV%03d', (int) $serviceId)
            : (string) $serviceId;
    }

    private function cleanNumber(float $value): int|float
    {
        $rounded = round($value, 2);

        return abs($rounded - round($rounded)) < 0.00001
            ? (int) round($rounded)
            : $rounded;
    }
}
