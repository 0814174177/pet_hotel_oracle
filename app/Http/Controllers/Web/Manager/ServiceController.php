<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Web\WebController;
use App\Services\Manager\ManagerBranchScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceController extends WebController
{
    public function __construct(
        protected ManagerBranchScopeService $branchScope
    ) {
    }

    public function index(int|string $branchId): View
    {
        return view('pages.manager.branch-service-management', [
            'managerBranchId' => $this->branchScope->ensureCanAccessBranch((int) $branchId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return back()->with('status', 'Xu ly luu dich vu chi nhanh.');
    }
}
