<?php

namespace App\Http\Controllers\Web\Manager;

use App\Exports\Manager\InventoryExport;
use App\Exports\Manager\RevenueReportExport;
use App\Http\Controllers\Web\WebController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Manager\BranchScopedInventoryMaterialRepositoryInterface;
use App\Repositories\Contracts\Manager\BranchScopedRevenueReportRepositoryInterface;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends WebController
{
    public function __construct(
        private readonly BranchScopedRevenueReportRepositoryInterface $revenueReportRepository,
        private readonly BranchScopedInventoryMaterialRepositoryInterface $inventoryRepository
    ) {
    }

    public function revenueReport(DateRangeFilterRequest $request): BinaryFileResponse
    {
        $branchId = $this->currentManagerBranchId();
        $filters = $this->reportFilters($request);

        $data = [
            'target_progress' => $this->revenueReportRepository->getTargetProgress($branchId, $filters),
            'revenue_comparison' => $this->revenueReportRepository->getRevenueComparisonChart($branchId, $filters),
            'service_mix' => $this->revenueReportRepository->getServiceMix($branchId, $filters),
            'aov' => $this->revenueReportRepository->getAovSummary($branchId, $filters),
            'employee_performance' => $this->revenueReportRepository->getEmployeePerformance($branchId, $filters),
            'customer_retention' => $this->revenueReportRepository->getCustomerRetention($branchId, $filters),
        ];

        return Excel::download(
            new RevenueReportExport($data, $filters),
            $this->filename('manager-revenue-report')
        );
    }

    public function inventory(DateRangeFilterRequest $request): BinaryFileResponse
    {
        $branchId = $this->currentManagerBranchId();
        $filters = $this->inventoryFilters($request);

        $data = [
            'kpi' => $this->inventoryRepository->getInventoryKpi($branchId, $filters),
            'materials' => $this->inventoryRepository->getMaterialsByBranch($branchId, $filters),
            'out_of_stock' => $this->inventoryRepository->getOutOfStockMaterials($branchId, $filters),
            'low_stock' => $this->inventoryRepository->getLowStockMaterials($branchId, $filters),
            'inventory_value_by_category' => $this->inventoryRepository->getInventoryValueByCategory($branchId, $filters),
        ];

        return Excel::download(
            new InventoryExport($data, $filters),
            $this->filename('manager-inventory')
        );
    }

    private function reportFilters(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'period' => ['nullable', 'string', 'in:day,month,year'],
            'date' => ['nullable', 'date'],
        ]));
    }

    private function inventoryFilters(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'period' => ['nullable', 'string', 'in:day,month,year'],
            'date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
            'group' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
        ]));
    }

    private function currentManagerBranchId(): int
    {
        $branchId = auth()->user()?->employee?->branch_id;

        abort_if($branchId === null, 403, 'Manager account is not assigned to a branch.');

        return (int) $branchId;
    }

    private function filename(string $prefix): string
    {
        return $prefix.'-'.now()->format('Ymd-His').'.xlsx';
    }
}
