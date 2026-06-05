<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Web\WebController;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeManagementRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EmployeeController extends WebController
{
    private const ALLOWED_STAFF_ROLES = ['RECEPTIONIST', 'GROOMER'];

    private const BLOCKED_ROLES = ['CEO', 'ADMIN', 'MANAGER'];

    public function __construct(private EmployeeManagementRepositoryInterface $employees)
    {
    }

    public function index(): View
    {
        $branchId = $this->currentManagerBranchId();

        $employees = $this->employees->managerBranchEmployees(
            $branchId,
            self::ALLOWED_STAFF_ROLES,
            self::BLOCKED_ROLES
        );

        return view('pages.shared.employee-management', [
            'employees' => $employees,
            'branches' => collect(),
            'employeePage' => $this->pageConfig(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $branchId = $this->currentManagerBranchId();

        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'full_name' => trim((string) $request->input('full_name')),
            'phone' => $this->normalizePhone($request->input('phone')),
            'position' => strtoupper((string) $request->input('position')),
        ]);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', 'min:6'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(0[0-9]{9}|\+84[0-9]{9})$/', Rule::unique('employee', 'phone')],
            'position' => ['required', Rule::in(self::ALLOWED_STAFF_ROLES)],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:200000000'],
            'hire_date' => ['nullable', 'date', 'before_or_equal:today'],
            'birthday' => ['nullable', 'date', 'before_or_equal:'.now()->subYears(16)->toDateString()],
            'experience' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], $this->validationMessages());
        $this->ensureEmployeeDatesAreLogical($validated);

        $employee = $this->employees->createBranchStaff($validated, $branchId);

        $message = 'Thêm nhân viên thành công.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'employee' => $this->employees->mapEmployeeForUi($employee),
            ], 201);
        }

        return redirect()->route('manager.employees')->with('status', $message);
    }

    public function update(Request $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $this->authorizeManagedEmployee($employee);

        $request->merge([
            'full_name' => trim((string) $request->input('full_name')),
            'phone' => $this->normalizePhone($request->input('phone')),
            'position' => strtoupper((string) $request->input('position')),
        ]);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:200'],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^(0[0-9]{9}|\+84[0-9]{9})$/',
                Rule::unique('employee', 'phone')->ignore($employee->employee_id, 'employee_id'),
            ],
            'position' => ['required', Rule::in(self::ALLOWED_STAFF_ROLES)],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:200000000'],
            'hire_date' => ['nullable', 'date', 'before_or_equal:today'],
            'birthday' => ['nullable', 'date', 'before_or_equal:'.now()->subYears(16)->toDateString()],
            'experience' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            '_employee_form_mode' => ['nullable', 'string'],
            '_employee_id' => ['nullable', 'integer'],
        ], $this->validationMessages());
        $this->ensureEmployeeDatesAreLogical($validated);

        $employee = $this->employees->updateBranchStaff($employee, $validated);
        $message = 'Cập nhật thông tin nhân viên thành công.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'employee' => $this->employees->mapEmployeeForUi($employee),
            ]);
        }

        return redirect()->route('manager.employees')->with('status', $message);
    }

    public function resign(Request $request, Employee $employee): RedirectResponse|JsonResponse
    {
        $this->authorizeManagedEmployee($employee, true);

        $this->employees->resign($employee);

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

    private function authorizeManagedEmployee(Employee $employee, bool $preventSelf = false): void
    {
        $branchId = $this->currentManagerBranchId();
        $isAllowed = $this->employees->isManageableBranchStaff(
            $employee,
            $branchId,
            self::ALLOWED_STAFF_ROLES,
            self::BLOCKED_ROLES,
            auth()->id(),
            $preventSelf
        );

        if (! $isAllowed) {
            throw new HttpException(403, 'Bạn không có quyền thao tác nhân viên này.');
        }
    }

    private function normalizePhone(mixed $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        $phone = preg_replace('/[\s.\-()]/', '', $phone) ?: $phone;

        return str_starts_with($phone, '84') ? '+'.$phone : $phone;
    }

    private function ensureEmployeeDatesAreLogical(array $validated): void
    {
        if (empty($validated['birthday']) || empty($validated['hire_date'])) {
            return;
        }

        $minimumHireDate = Carbon::parse($validated['birthday'])->addYears(16);
        $hireDate = Carbon::parse($validated['hire_date']);

        if ($hireDate->lt($minimumHireDate)) {
            throw ValidationException::withMessages([
                'hire_date' => 'Ngày vào làm phải sau thời điểm nhân viên đủ 16 tuổi.',
            ]);
        }
    }

    private function pageConfig(): array
    {
        return [
            'layout' => 'layouts.manager',
            'context' => 'manager',
            'title' => 'Quản lý nhân viên',
            'storeRoute' => route('manager.employees.store'),
            'updateUrlTemplate' => route('manager.employees.update', ['employee' => '__EMPLOYEE_ID__']),
            'resignUrlTemplate' => route('manager.employees.resign', ['employee' => '__EMPLOYEE_ID__']),
            'description' => 'Quản lý nhân sự đang làm việc tại chi nhánh của bạn.',
            'createButtonLabel' => 'Thêm nhân viên',
            'createTitle' => 'Thêm nhân viên',
            'createSubtitle' => 'Tạo tài khoản đăng nhập và hồ sơ cho nhân viên thuộc chi nhánh của bạn.',
            'createSubmitLabel' => 'Tạo nhân viên',
            'editTitle' => 'Sửa thông tin nhân viên',
            'editSubtitle' => 'Không đổi email, mật khẩu, chi nhánh hoặc trạng thái.',
            'resignTitle' => 'Cho nhân viên nghỉ việc',
            'resignText' => 'Bạn có chắc muốn cho nhân viên này nghỉ việc không? Tài khoản đăng nhập của người này sẽ bị vô hiệu hóa và nhân viên sẽ ẩn khỏi danh sách.',
            'resignSubmitLabel' => 'Xác nhận nghỉ việc',
            'createSuccessMessage' => 'Thêm nhân viên thành công.',
            'editSuccessMessage' => 'Cập nhật thông tin nhân viên thành công.',
            'resignSuccessMessage' => 'Đã cho nhân viên nghỉ việc.',
            'showBranchFilter' => false,
            'showBranchColumn' => true,
            'canChooseBranchOnCreate' => false,
            'canEditBranch' => false,
            'canChoosePositionOnCreate' => true,
            'canEditPosition' => true,
            'positions' => [
                ['value' => 'RECEPTIONIST', 'label' => 'Lễ tân'],
                ['value' => 'GROOMER', 'label' => 'Groomer'],
            ],
            'stats' => [
                ['title' => 'Tổng nhân viên', 'detail' => 'Đang làm', 'attribute' => 'data-stat-total'],
                ['title' => 'Số vị trí', 'detail' => 'Lễ tân / Groomer', 'attribute' => 'data-stat-positions'],
                ['title' => 'Lương trung bình', 'detail' => 'VND / tháng', 'attribute' => 'data-stat-avg-salary'],
            ],
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
            'phone.regex' => 'Số điện thoại phải có 10 chữ số và bắt đầu bằng 0 hoặc +84.',
            'phone.unique' => 'Số điện thoại này đã thuộc về nhân viên khác.',
            'position.required' => 'Vui lòng chọn vị trí.',
            'position.in' => 'Manager chỉ được tạo nhân viên Lễ tân hoặc Groomer.',
            'salary.min' => 'Lương không được âm.',
            'salary.max' => 'Lương vượt quá giới hạn hợp lệ.',
            'hire_date.date' => 'Ngày vào làm không hợp lệ.',
            'hire_date.before_or_equal' => 'Ngày vào làm không được lớn hơn ngày hiện tại.',
            'birthday.date' => 'Ngày sinh không hợp lệ.',
            'birthday.before_or_equal' => 'Nhân viên phải đủ từ 16 tuổi trở lên.',
            'experience.max' => 'Kinh nghiệm không được vượt quá 50 ký tự.',
            'notes.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
        ];
    }

}
