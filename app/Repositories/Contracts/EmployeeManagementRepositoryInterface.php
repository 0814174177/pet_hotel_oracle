<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use Illuminate\Support\Collection;

interface EmployeeManagementRepositoryInterface
{
    public function ceoEmployees(): Collection;

    public function branchOptions(): Collection;

    public function createCeoManager(array $validated): Employee;

    public function updateCeoManager(Employee $employee, array $validated): Employee;

    public function managerBranchEmployees(int $branchId, array $allowedRoles, array $blockedRoles): Collection;

    public function createBranchStaff(array $validated, int $branchId): Employee;

    public function updateBranchStaff(Employee $employee, array $validated): Employee;

    public function resign(Employee $employee): void;

    public function isManageableSystemEmployee(
        Employee $employee,
        ?int $currentUserId = null,
        bool $preventSelf = false
    ): bool;

    public function isManageableBranchStaff(
        Employee $employee,
        int $branchId,
        array $allowedRoles,
        array $blockedRoles,
        ?int $currentUserId = null,
        bool $preventSelf = false
    ): bool;

    public function mapEmployeeForUi(Employee $employee): array;
}
