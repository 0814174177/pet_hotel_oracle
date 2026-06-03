<?php

namespace App\Http\Controllers\Web\Ceo;

use App\Http\Controllers\Web\WebController;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends WebController
{
    public function index(): View
    {
        $employees = Employee::with(['user', 'branch'])
            ->orderByDesc('status')
            ->orderBy('full_name')
            ->get()
            ->map(fn (Employee $employee): array => $this->mapEmployeeForUi($employee))
            ->values();

        $branches = Branch::query()
            ->select(['branch_id', 'branch_name'])
            ->orderBy('branch_name')
            ->get()
            ->map(fn (Branch $branch): array => [
                'id' => (int) $branch->branch_id,
                'name' => $branch->branch_name,
            ])
            ->values();

        return view('pages.ceo.employee-management', [
            'employees' => $employees,
            'branches' => $branches,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'full_name' => trim((string) $request->input('full_name')),
        ]);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', 'min:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'branch_id' => ['required', Rule::exists('branch', 'branch_id')],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'hire_date' => ['nullable', 'date'],
            'birthday' => ['nullable', 'date'],
            'experience' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ], $this->validationMessages());

        $employee = DB::transaction(function () use ($validated): Employee {
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

        $message = 'Thêm quản lý thành công.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'employee' => $this->mapEmployeeForUi($employee),
            ], 201);
        }

        return redirect()
            ->route('ceo.employees')
            ->with('status', $message);
    }

    public function update(Request $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $request->merge([
            'full_name' => trim((string) $request->input('full_name')),
        ]);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:20'],
            'branch_id' => ['required', Rule::exists('branch', 'branch_id')],
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

        $employee->load(['user', 'branch']);
        $message = 'Cập nhật thông tin quản lý thành công.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'employee' => $this->mapEmployeeForUi($employee),
            ]);
        }

        return redirect()
            ->route('ceo.employees')
            ->with('status', $message);
    }

    public function resign(Request $request, Employee $employee): RedirectResponse|JsonResponse
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

        $message = 'Đã cho quản lý nghỉ việc và vô hiệu hóa tài khoản đăng nhập.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'employee_id' => (int) $employee->employee_id,
            ]);
        }

        return redirect()
            ->route('ceo.employees')
            ->with('status', $message);
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
            'position' => $employee->position,
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
            'branch_id.required' => 'Vui lòng chọn chi nhánh.',
            'branch_id.exists' => 'Chi nhánh không hợp lệ.',
            'salary.min' => 'Lương không được âm.',
            'hire_date.date' => 'Ngày vào làm không hợp lệ.',
            'birthday.date' => 'Ngày sinh không hợp lệ.',
            'experience.max' => 'Kinh nghiệm không được vượt quá 50 ký tự.',
        ];
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
}
