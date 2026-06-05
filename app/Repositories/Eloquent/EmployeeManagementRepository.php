<?php

namespace App\Repositories\Eloquent;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\Contracts\EmployeeManagementRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeManagementRepository implements EmployeeManagementRepositoryInterface
{
    private const SYSTEM_BLOCKED_ROLES = ['ADMIN', 'CEO'];

    public function ceoEmployees(): Collection
    {
        return Employee::with(['user', 'branch'])
            ->working()
            ->whereHas('user', function (Builder $query): void {
                $query->where('is_active', 1)
                    ->whereNotIn('role', self::SYSTEM_BLOCKED_ROLES);
            })
            ->orderBy('full_name')
            ->get()
            ->map(fn (Employee $employee): array => $this->mapEmployeeForUi($employee))
            ->values();
    }

    public function branchOptions(): Collection
    {
        return Branch::query()
            ->select(['branch_id', 'branch_name'])
            ->orderBy('branch_name')
            ->get()
            ->map(fn (Branch $branch): array => [
                'id' => (int) $branch->branch_id,
                'name' => $branch->branch_name,
            ])
            ->values();
    }

    public function createCeoManager(array $validated): Employee
    {
        return DB::transaction(function () use ($validated): Employee {
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'MANAGER',
                'is_active' => 1,
            ]);

            return Employee::create([
                'user_id' => $user->id,
                'branch_id' => (int) $validated['branch_id'],
                'full_name' => $validated['full_name'],
                'position' => 'MANAGER',
                'salary' => $validated['salary'] ?? 0,
                'phone' => $validated['phone'] ?? null,
                'hire_date' => $validated['hire_date'] ?? null,
                'birthday' => $validated['birthday'] ?? null,
                'experience' => $validated['experience'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => Employee::STATUS_WORKING,
            ]);
        })->load(['user', 'branch']);
    }

    public function updateCeoManager(Employee $employee, array $validated): Employee
    {
        DB::transaction(function () use ($employee, $validated): void {
            $employee->update([
                'branch_id' => (int) $validated['branch_id'],
                'full_name' => $validated['full_name'],
                'salary' => $validated['salary'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'hire_date' => $validated['hire_date'] ?? null,
                'birthday' => $validated['birthday'] ?? null,
                'experience' => $validated['experience'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($employee->user) {
                $employee->user->update([
                    'name' => $validated['full_name'],
                ]);
            }
        });

        return $employee->load(['user', 'branch']);
    }

    public function managerBranchEmployees(int $branchId, array $allowedRoles, array $blockedRoles): Collection
    {
        return $this->baseBranchStaffQuery($branchId, $allowedRoles, $blockedRoles)
            ->orderBy('full_name')
            ->get()
            ->map(fn (Employee $employee): array => $this->mapEmployeeForUi($employee))
            ->values();
    }

    public function createBranchStaff(array $validated, int $branchId): Employee
    {
        return DB::transaction(function () use ($validated, $branchId): Employee {
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['position'],
                'is_active' => 1,
            ]);

            return Employee::create([
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'full_name' => $validated['full_name'],
                'position' => $validated['position'],
                'salary' => $validated['salary'] ?? 0,
                'phone' => $validated['phone'] ?? null,
                'hire_date' => $validated['hire_date'] ?? null,
                'birthday' => $validated['birthday'] ?? null,
                'experience' => $validated['experience'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => Employee::STATUS_WORKING,
            ]);
        })->load(['user', 'branch']);
    }

    public function updateBranchStaff(Employee $employee, array $validated): Employee
    {
        DB::transaction(function () use ($employee, $validated): void {
            $employee->update([
                'full_name' => $validated['full_name'],
                'position' => $validated['position'],
                'salary' => $validated['salary'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'hire_date' => $validated['hire_date'] ?? null,
                'birthday' => $validated['birthday'] ?? null,
                'experience' => $validated['experience'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($employee->user) {
                $employee->user->update([
                    'name' => $validated['full_name'],
                    'role' => $validated['position'],
                ]);
            }
        });

        return $employee->load(['user', 'branch']);
    }

    public function resign(Employee $employee): void
    {
        DB::transaction(function () use ($employee): void {
            $employee->update([
                'status' => Employee::STATUS_RESIGNED,
            ]);

            if ($employee->user) {
                $employee->user->update([
                    'is_active' => 0,
                ]);
            }
        });
    }

    public function isManageableSystemEmployee(
        Employee $employee,
        ?int $currentUserId = null,
        bool $preventSelf = false
    ): bool {
        $employee->loadMissing('user');
        $role = strtoupper((string) $employee->user?->role);

        $isAllowed = (int) $employee->status === Employee::STATUS_WORKING
            && $employee->user
            && (int) $employee->user->is_active === 1
            && ! in_array($role, self::SYSTEM_BLOCKED_ROLES, true);

        if ($preventSelf && $currentUserId !== null && (int) $employee->user_id === $currentUserId) {
            return false;
        }

        return $isAllowed;
    }

    public function isManageableBranchStaff(
        Employee $employee,
        int $branchId,
        array $allowedRoles,
        array $blockedRoles,
        ?int $currentUserId = null,
        bool $preventSelf = false
    ): bool {
        $employee->loadMissing('user');
        $role = strtoupper((string) $employee->user?->role);

        $isAllowed = (int) $employee->branch_id === $branchId
            && (int) $employee->status === Employee::STATUS_WORKING
            && $employee->user
            && (int) $employee->user->is_active === 1
            && ! in_array($role, $blockedRoles, true)
            && in_array($role, $allowedRoles, true);

        if ($preventSelf && $currentUserId !== null && (int) $employee->user_id === $currentUserId) {
            return false;
        }

        return $isAllowed;
    }

    public function mapEmployeeForUi(Employee $employee): array
    {
        return [
            'id' => (int) $employee->employee_id,
            'code' => 'EMP-'.str_pad((string) $employee->employee_id, 3, '0', STR_PAD_LEFT),
            'name' => $employee->full_name,
            'phone' => $employee->phone,
            'email' => $employee->user?->email,
            'avatarUrl' => $this->avatarUrl($employee->avatar),
            'avatarText' => $this->initials((string) $employee->full_name),
            'branch' => [
                'id' => $employee->branch_id !== null ? (int) $employee->branch_id : null,
                'name' => $employee->branch?->branch_name,
            ],
            'position' => strtoupper((string) $employee->position),
            'positionLabel' => $this->positionLabel($employee->position),
            'salary' => $employee->salary !== null ? (float) $employee->salary : 0,
            'hireDate' => $employee->hire_date?->format('Y-m-d'),
            'birthday' => $employee->birthday?->format('Y-m-d'),
            'experience' => $employee->experience,
            'notes' => $employee->notes,
            'status' => (int) $employee->status,
            'statusLabel' => (int) $employee->status === Employee::STATUS_WORKING ? 'Đang làm' : 'Nghỉ việc',
        ];
    }

    private function baseBranchStaffQuery(int $branchId, array $allowedRoles, array $blockedRoles): Builder
    {
        return Employee::query()
            ->with(['user', 'branch'])
            ->working()
            ->where('branch_id', $branchId)
            ->whereHas('user', function (Builder $query) use ($allowedRoles, $blockedRoles): void {
                $query->where('is_active', 1)
                    ->whereNotIn('role', $blockedRoles)
                    ->whereIn('role', $allowedRoles);
            });
    }

    private function positionLabel(?string $position): string
    {
        return match (strtoupper((string) $position)) {
            'MANAGER' => 'Quản lý',
            'RECEPTIONIST' => 'Lễ tân',
            'GROOMER' => 'Groomer',
            'VET' => 'Bác sĩ thú y',
            'CLEANER' => 'Tạp vụ',
            default => 'Khác',
        };
    }

    private function avatarUrl(?string $avatar): string
    {
        $avatar = trim((string) $avatar);

        if ($avatar === '') {
            return '';
        }

        if (
            Str::startsWith($avatar, ['http://', 'https://', '/', 'data:'])
            || Str::startsWith($avatar, ['assets/', 'images/'])
        ) {
            return Str::startsWith($avatar, ['assets/', 'images/']) ? asset($avatar) : $avatar;
        }

        return asset('storage/'.$avatar);
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $parts = array_values(array_filter($parts));

        if (count($parts) >= 2) {
            return Str::upper(Str::substr($parts[count($parts) - 2], 0, 1).Str::substr($parts[count($parts) - 1], 0, 1));
        }

        return Str::upper(Str::substr($name, 0, 2));
    }
}
