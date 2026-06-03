<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Web\WebController;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EmployeeController extends WebController
{
    private const ALLOWED_STAFF_ROLES = ['RECEPTIONIST', 'GROOMER'];

    private const BLOCKED_ROLES = ['CEO', 'ADMIN', 'MANAGER'];

    public function index(): View
    {
        $branchId = $this->currentManagerBranchId();

        $employees = $this->baseBranchStaffQuery($branchId)
            ->orderBy('full_name')
            ->get()
            ->map(fn (Employee $employee): array => $this->mapEmployeeForUi($employee))
            ->values();

        return view('pages.manager.employee-management', [
            'employees' => $employees,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $branchId = $this->currentManagerBranchId();

        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'full_name' => trim((string) $request->input('full_name')),
            'position' => strtoupper((string) $request->input('position')),
        ]);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', 'min:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['required', Rule::in(self::ALLOWED_STAFF_ROLES)],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'hire_date' => ['nullable', 'date'],
            'birthday' => ['nullable', 'date'],
            'experience' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ], $this->validationMessages());

        $employee = DB::transaction(function () use ($validated, $branchId): Employee {
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

        $message = 'Thêm nhân viên thành công.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'employee' => $this->mapEmployeeForUi($employee),
            ], 201);
        }

        return redirect()->route('manager.employees')->with('status', $message);
    }

    public function update(Request $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $this->authorizeManagedEmployee($employee);

        $request->merge([
            'full_name' => trim((string) $request->input('full_name')),
            'position' => strtoupper((string) $request->input('position')),
        ]);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['required', Rule::in(self::ALLOWED_STAFF_ROLES)],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'hire_date' => ['nullable', 'date'],
            'birthday' => ['nullable', 'date'],
            'experience' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            '_employee_form_mode' => ['nullable', 'string'],
            '_employee_id' => ['nullable', 'integer'],
        ], $this->validationMessages());

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

        $employee->load(['user', 'branch']);
        $message = 'Cập nhật thông tin nhân viên thành công.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'employee' => $this->mapEmployeeForUi($employee),
            ]);
        }

        return redirect()->route('manager.employees')->with('status', $message);
    }

    public function resign(Request $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $this->authorizeManagedEmployee($employee, true);

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

        $message = 'Đã cho nhân viên nghỉ việc và vô hiệu hóa tài khoản đăng nhập.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'employee_id' => (int) $employee->employee_id,
            ]);
        }

        return redirect()->route('manager.employees')->with('status', $message);
    }

    private function currentManagerBranchId(): int
    {
        $branchId = auth()->user()?->employee?->branch_id;

        if ($branchId === null) {
            abort(403, 'Manager account is not assigned to a branch.');
        }

        return (int) $branchId;
    }

    private function baseBranchStaffQuery(int $branchId)
    {
        return Employee::query()
            ->with(['user', 'branch'])
            ->working()
            ->where('branch_id', $branchId)
            ->whereHas('user', function ($query): void {
                $query->where('is_active', 1)
                    ->whereNotIn('role', self::BLOCKED_ROLES)
                    ->whereIn('role', self::ALLOWED_STAFF_ROLES);
            });
    }

    private function authorizeManagedEmployee(Employee $employee, bool $preventSelf = false): void
    {
        $branchId = $this->currentManagerBranchId();
        $employee->loadMissing('user');
        $role = strtoupper((string) $employee->user?->role);

        $isAllowed = (int) $employee->branch_id === $branchId
            && (int) $employee->status === Employee::STATUS_WORKING
            && $employee->user
            && (int) $employee->user->is_active === 1
            && ! in_array($role, self::BLOCKED_ROLES, true)
            && in_array($role, self::ALLOWED_STAFF_ROLES, true);

        if ($preventSelf && (int) $employee->user_id === (int) auth()->id()) {
            $isAllowed = false;
        }

        if (! $isAllowed) {
            throw new HttpException(403, 'Bạn không có quyền thao tác nhân viên này.');
        }
    }

    private function mapEmployeeForUi(Employee $employee): array
    {
        return [
            'id' => (int) $employee->employee_id,
            'code' => 'EMP-'.str_pad((string) $employee->employee_id, 3, '0', STR_PAD_LEFT),
            'name' => $employee->full_name,
            'phone' => $employee->phone,
            'email' => $employee->user?->email,
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

    private function validationMessages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'full_name.max' => 'Họ và tên không được vượt quá 200 ký tự.',
            'email.required' => 'Vui lòng nhập email đăng nhập.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã tồn tại.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'position.required' => 'Vui lòng chọn vị trí.',
            'position.in' => 'Manager chỉ được tạo nhân viên Lễ tân hoặc Groomer.',
            'salary.min' => 'Lương không được âm.',
            'hire_date.date' => 'Ngày vào làm không hợp lệ.',
            'birthday.date' => 'Ngày sinh không hợp lệ.',
            'experience.max' => 'Kinh nghiệm không được vượt quá 50 ký tự.',
        ];
    }

    private function positionLabel(?string $position): string
    {
        return match (strtoupper((string) $position)) {
            'RECEPTIONIST' => 'Lễ tân',
            'GROOMER' => 'Groomer',
            default => 'Khác',
        };
    }
}
