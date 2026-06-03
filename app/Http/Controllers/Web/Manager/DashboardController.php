<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Web\WebController;
use App\Services\Manager\ManagerBranchScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends WebController
{
    public function __construct(
        protected ManagerBranchScopeService $branchScope
    ) {
    }

    public function index(int|string $branchId): View
    {
        return view('pages.manager.branch-dashboard', [
            'managerBranchId' => $this->branchScope->ensureCanAccessBranch((int) $branchId),
        ]);
    }

    public function filter(Request $request): RedirectResponse
    {
        return back()->with('status', 'Xu ly loc dashboard manager.');
    }
}
