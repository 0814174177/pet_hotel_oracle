<?php

namespace App\Http\Controllers\Web\Ceo;

use App\Exports\Ceo\BranchNetworkExport;
use App\Exports\Ceo\DashboardOverviewExport;
use App\Exports\Ceo\FinanceExport;
use App\Http\Controllers\Web\WebController;
use App\Http\Requests\Shared\DateRangeFilterRequest;
use App\Repositories\Contracts\Ceo\BranchNetworkRepositoryInterface;
use App\Repositories\Contracts\Ceo\CeoDashboardRepositoryInterface;
use App\Repositories\Contracts\Ceo\CeoFinanceRepositoryInterface;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends WebController
{
    public function __construct(
        private readonly CeoFinanceRepositoryInterface $financeRepository,
        private readonly BranchNetworkRepositoryInterface $branchRepository,
        private readonly CeoDashboardRepositoryInterface $dashboardRepository
    ) {
    }

    public function dashboard(DateRangeFilterRequest $request): BinaryFileResponse
    {
        $filters = $request->getFiltersArray();

        return Excel::download(
            new DashboardOverviewExport($this->dashboardRepository->getDashboard($filters), $filters),
            $this->filename('ceo-dashboard')
        );
    }

    public function finance(DateRangeFilterRequest $request): BinaryFileResponse
    {
        $filters = $this->financeFilters($request);

        $data = [
            'overview' => $this->financeRepository->getFinanceData($filters),
            'trend' => $this->financeRepository->getFinanceTrendChart($filters),
            'monthly_trend' => $this->financeRepository->getFinanceMonthlyTrendChart($filters),
            'cost_structure' => $this->financeRepository->getCostStructureChart($filters),
            'branch_profit' => $this->financeRepository->getBranchEstimatedProfitTable($filters),
            'service_profit' => $this->financeRepository->getServiceEstimatedProfitTable($filters),
            'lowest_margin_services' => $this->financeRepository->getLowestMarginServicesTable($filters),
            'negative_branch_profit_alerts' => $this->financeRepository->getNegativeBranchProfitAlerts($filters),
            'low_service_margin_alerts' => $this->financeRepository->getLowServiceMarginAlerts($filters),
            'cost_growth_alerts' => $this->financeRepository->getCostGrowthAlerts($filters),
        ];

        return Excel::download(
            new FinanceExport($data, $filters),
            $this->filename('ceo-finance')
        );
    }

    public function branches(DateRangeFilterRequest $request): BinaryFileResponse
    {
        $filters = $this->branchFilters($request);

        $data = [
            'active_count' => $this->branchRepository->getActiveBranchCount($filters),
            'highest_revenue_branch' => $this->branchRepository->getHighestRevenueBranch($filters),
            'lowest_occupancy_branch' => $this->branchRepository->getLowestOccupancyBranch($filters),
            'branches' => $this->branchRepository->getBranchNetworkList($filters),
        ];

        return Excel::download(
            new BranchNetworkExport($data, $filters),
            $this->filename('ceo-branches')
        );
    }

    private function financeFilters(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'period' => ['nullable', 'string', 'in:day,month,year'],
            'period_type' => ['nullable', 'string', 'in:day,month,year'],
            'date' => ['nullable', 'date'],
        ]));
    }

    private function branchFilters(DateRangeFilterRequest $request): array
    {
        return array_merge($request->getFiltersArray(), $request->validate([
            'region' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'min_revenue' => ['nullable', 'numeric', 'min:0'],
            'max_revenue' => ['nullable', 'numeric', 'min:0'],
            'min_occupancy_rate' => ['nullable', 'numeric', 'min:0'],
            'max_occupancy_rate' => ['nullable', 'numeric', 'min:0'],
            'sort_by' => ['nullable', 'string', 'in:branch_name,region,revenue,occupancy_rate,status'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ]));
    }

    private function filename(string $prefix): string
    {
        return $prefix.'-'.now()->format('Ymd-His').'.xlsx';
    }
}
