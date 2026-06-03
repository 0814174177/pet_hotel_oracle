<?php

namespace App\Services\Manager;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ManagerBranchScopeService
{
    public function currentBranchId(): int
    {
        return $this->resolveForManager(Auth::user());
    }

    public function ensureCanAccessBranch(int $routeBranchId): int
    {
        $user = Auth::user();

        abort_if(! $user, 403, 'Authentication is required.');

        if (! $user->isManager()) {
            return $routeBranchId;
        }

        $branchId = $this->currentBranchId();

        abort_if($routeBranchId !== $branchId, 403, 'Manager cannot access this branch.');

        return $branchId;
    }

    public function resolveForManager(?User $user): int
    {
        abort_if(! $user, 403, 'Manager authentication is required.');
        abort_if(! $user->isManager(), 403, 'Manager role is required.');
        abort_if(! $user->is_active, 403, 'Manager account is inactive.');

        $employee = $user->employee;

        abort_if(! $employee, 403, 'Manager employee profile is required.');
        abort_if(! $employee->isWorking(), 403, 'Manager employee profile is inactive.');
        abort_if(! $employee->isManagerPosition(), 403, 'Manager employee position is required.');

        $branchId = $user->managerBranchId();

        abort_if($branchId === null, 403, 'Manager branch is required.');

        return $branchId;
    }
}
