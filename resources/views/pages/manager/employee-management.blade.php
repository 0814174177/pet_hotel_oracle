@extends('layouts.manager')

@section('title', 'Quản lý nhân viên')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/manager/employee-management.css') }}?v={{ time() }}">
@endpush

@section('content')
<div
    id="managerEmployeePage"
    class="manager-employee-page"
    data-create-has-errors="{{ $errors->any() && old('_employee_form_mode', 'create') === 'create' ? '1' : '0' }}"
    data-edit-has-errors="{{ $errors->any() && old('_employee_form_mode') === 'edit' ? '1' : '0' }}"
    data-old-employee-id="{{ old('_employee_id') }}"
    data-update-url-template="{{ route('manager.employees.update', ['employee' => '__EMPLOYEE_ID__']) }}"
    data-resign-url-template="{{ route('manager.employees.resign', ['employee' => '__EMPLOYEE_ID__']) }}"
>
    <script type="application/json" id="managerEmployeeData">@json($employees ?? [])</script>

    <header class="manager-employee-header">
        <div>
            <p class="manager-employee-eyebrow">Nhân viên</p>
            <h1>Quản lý nhân viên</h1>
            <p>Quản lý nhân sự thuộc chi nhánh của bạn.</p>
        </div>
    </header>

    @if (session('status'))
        <div class="manager-employee-flash manager-employee-flash--success">{{ session('status') }}</div>
    @endif

    <section class="manager-employee-stats-grid" aria-label="Tổng quan nhân viên">
        <article class="manager-employee-stat-card">
            <span class="manager-employee-stat-icon manager-employee-stat-icon--total">NV</span>
            <div>
                <div class="manager-employee-stat-label">Tổng nhân viên</div>
                <div class="manager-employee-stat-value" data-stat-total>0</div>
                <div class="manager-employee-stat-sub">Đang làm</div>
            </div>
        </article>

        <article class="manager-employee-stat-card">
            <span class="manager-employee-stat-icon manager-employee-stat-icon--position">VT</span>
            <div>
                <div class="manager-employee-stat-label">Số vị trí</div>
                <div class="manager-employee-stat-value" data-stat-positions>0</div>
                <div class="manager-employee-stat-sub">Lễ tân / Groomer</div>
            </div>
        </article>

        <article class="manager-employee-stat-card">
            <span class="manager-employee-stat-icon manager-employee-stat-icon--salary">₫</span>
            <div>
                <div class="manager-employee-stat-label">Lương trung bình</div>
                <div class="manager-employee-stat-value" data-stat-avg-salary>0</div>
                <div class="manager-employee-stat-sub">VND / tháng</div>
            </div>
        </article>
    </section>

    <section class="manager-employee-table-card">
        <div class="manager-employee-table-header">
            <div>
                <h2>Danh sách nhân viên</h2>
                <span data-table-count>0 người</span>
            </div>

            <div class="manager-employee-toolbar" aria-label="Bộ lọc nhân viên">
                <label class="manager-employee-search-box">
                    <span class="manager-employee-search-icon">⌕</span>
                    <input type="text" placeholder="Tìm tên, email, số điện thoại..." data-search-input>
                </label>

                <select class="manager-employee-filter-select" data-filter-position aria-label="Lọc vị trí">
                    <option value="">Tất cả vị trí</option>
                    <option value="RECEPTIONIST">Lễ tân</option>
                    <option value="GROOMER">Groomer</option>
                </select>

                <button type="button" class="manager-employee-btn-primary" data-open-create-modal>+ Thêm nhân viên</button>
            </div>
        </div>

        <div class="manager-employee-table-wrapper">
            <table class="manager-employee-table">
                <thead>
                    <tr>
                        <th>Nhân viên</th>
                        <th>Liên hệ</th>
                        <th>Vị trí</th>
                        <th>Lương</th>
                        <th>Ngày vào làm</th>
                        <th>Kinh nghiệm</th>
                        <th>Trạng thái</th>
                        <th class="manager-employee-actions-col">Thao tác</th>
                    </tr>
                </thead>
                <tbody data-employee-table-body></tbody>
            </table>
        </div>

        <div class="manager-employee-empty-state manager-employee-hidden" data-empty-state>
            <div class="manager-employee-empty-icon">∅</div>
            <p>Không tìm thấy nhân viên nào phù hợp.</p>
        </div>
    </section>

    <div class="manager-employee-overlay" data-create-overlay aria-hidden="true">
        <form class="manager-employee-modal" method="POST" action="{{ route('manager.employees.store') }}" data-create-form>
            @csrf
            <input type="hidden" name="_employee_form_mode" value="create">

            <div class="manager-employee-modal-head">
                <div>
                    <div class="manager-employee-modal-title">Thêm nhân viên</div>
                    <div class="manager-employee-modal-sub">Tạo tài khoản đăng nhập và hồ sơ cho nhân viên thuộc chi nhánh của bạn</div>
                </div>
                <button type="button" class="manager-employee-modal-close" data-close-create-modal aria-label="Đóng">×</button>
            </div>

            @if ($errors->any() && old('_employee_form_mode', 'create') === 'create')
                <div class="manager-employee-form-errors" data-create-errors>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @else
                <div class="manager-employee-form-errors manager-employee-hidden" data-create-errors></div>
            @endif

            <section class="manager-employee-form-section">
                <div class="manager-employee-form-section-title">Thông tin cá nhân</div>
                <div class="manager-employee-form-grid">
                    <label class="manager-employee-form-group manager-employee-form-group--full">
                        <span>Họ và tên <strong>*</strong></span>
                        <input type="text" name="full_name" maxlength="200" value="{{ old('full_name') }}" placeholder="VD: Trần Minh Khoa">
                    </label>

                    <label class="manager-employee-form-group">
                        <span>Email đăng nhập <strong>*</strong></span>
                        <input type="email" name="email" maxlength="255" value="{{ old('email') }}" placeholder="staff.branch@pethotel.test">
                    </label>

                    <label class="manager-employee-form-group">
                        <span>Số điện thoại</span>
                        <input type="tel" name="phone" maxlength="20" value="{{ old('phone') }}" placeholder="0901 234 567">
                    </label>

                    <label class="manager-employee-form-group">
                        <span>Mật khẩu <strong>*</strong></span>
                        <input type="password" name="password" autocomplete="new-password" placeholder="Tối thiểu 6 ký tự">
                    </label>

                    <label class="manager-employee-form-group">
                        <span>Xác nhận mật khẩu <strong>*</strong></span>
                        <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Nhập lại mật khẩu">
                    </label>
                </div>
            </section>

            <section class="manager-employee-form-section">
                <div class="manager-employee-form-section-title">Thông tin công việc</div>
                <div class="manager-employee-form-grid">
                    <label class="manager-employee-form-group">
                        <span>Vị trí <strong>*</strong></span>
                        <select name="position">
                            <option value="">Chọn vị trí</option>
                            <option value="RECEPTIONIST" @selected(old('position') === 'RECEPTIONIST')>Lễ tân</option>
                            <option value="GROOMER" @selected(old('position') === 'GROOMER')>Groomer</option>
                        </select>
                    </label>

                    <label class="manager-employee-form-group">
                        <span>Lương</span>
                        <input type="number" name="salary" min="0" step="0.01" value="{{ old('salary') }}" placeholder="VD: 12000000">
                    </label>

                    <label class="manager-employee-form-group">
                        <span>Ngày vào làm</span>
                        <input type="date" name="hire_date" value="{{ old('hire_date') }}">
                    </label>

                    <label class="manager-employee-form-group">
                        <span>Ngày sinh</span>
                        <input type="date" name="birthday" value="{{ old('birthday') }}">
                    </label>

                    <label class="manager-employee-form-group manager-employee-form-group--full">
                        <span>Kinh nghiệm</span>
                        <input type="text" name="experience" maxlength="50" value="{{ old('experience') }}" placeholder="VD: 2 năm chăm sóc khách hàng">
                    </label>

                    <label class="manager-employee-form-group manager-employee-form-group--full">
                        <span>Ghi chú</span>
                        <textarea name="notes" placeholder="Ghi chú thêm về nhân viên...">{{ old('notes') }}</textarea>
                    </label>
                </div>
            </section>

            <div class="manager-employee-modal-footer">
                <button type="button" class="manager-employee-btn-secondary" data-close-create-modal>Hủy bỏ</button>
                <button type="submit" class="manager-employee-btn-primary">Tạo nhân viên</button>
            </div>
        </form>
    </div>

    <div class="manager-employee-overlay" data-edit-overlay aria-hidden="true">
        <form class="manager-employee-modal" method="POST" action="{{ old('_employee_id') ? route('manager.employees.update', ['employee' => old('_employee_id')]) : '#' }}" data-edit-form>
            @csrf
            @method('PATCH')
            <input type="hidden" name="_employee_form_mode" value="edit">
            <input type="hidden" name="_employee_id" value="{{ old('_employee_id') }}" data-edit-employee-id>

            <div class="manager-employee-modal-head">
                <div>
                    <div class="manager-employee-modal-title">Sửa thông tin nhân viên</div>
                    <div class="manager-employee-modal-sub">Không đổi email, mật khẩu, chi nhánh hoặc trạng thái</div>
                </div>
                <button type="button" class="manager-employee-modal-close" data-close-edit-modal aria-label="Đóng">×</button>
            </div>

            @if ($errors->any() && old('_employee_form_mode') === 'edit')
                <div class="manager-employee-form-errors" data-edit-errors>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @else
                <div class="manager-employee-form-errors manager-employee-hidden" data-edit-errors></div>
            @endif

            <section class="manager-employee-form-section">
                <div class="manager-employee-form-section-title">Thông tin có thể chỉnh sửa</div>
                <div class="manager-employee-form-grid">
                    <label class="manager-employee-form-group manager-employee-form-group--full">
                        <span>Họ và tên <strong>*</strong></span>
                        <input type="text" name="full_name" maxlength="200" value="{{ old('_employee_form_mode') === 'edit' ? old('full_name') : '' }}" data-edit-field="name" placeholder="VD: Trần Minh Khoa">
                    </label>

                    <label class="manager-employee-form-group">
                        <span>Số điện thoại</span>
                        <input type="tel" name="phone" maxlength="20" value="{{ old('_employee_form_mode') === 'edit' ? old('phone') : '' }}" data-edit-field="phone" placeholder="0901 234 567">
                    </label>

                    <label class="manager-employee-form-group">
                        <span>Vị trí <strong>*</strong></span>
                        <select name="position" data-edit-field="position">
                            <option value="RECEPTIONIST" @selected(old('_employee_form_mode') === 'edit' && old('position') === 'RECEPTIONIST')>Lễ tân</option>
                            <option value="GROOMER" @selected(old('_employee_form_mode') === 'edit' && old('position') === 'GROOMER')>Groomer</option>
                        </select>
                    </label>

                    <label class="manager-employee-form-group">
                        <span>Lương</span>
                        <input type="number" name="salary" min="0" step="0.01" value="{{ old('_employee_form_mode') === 'edit' ? old('salary') : '' }}" data-edit-field="salary" placeholder="VD: 12000000">
                    </label>

                    <label class="manager-employee-form-group">
                        <span>Ngày vào làm</span>
                        <input type="date" name="hire_date" value="{{ old('_employee_form_mode') === 'edit' ? old('hire_date') : '' }}" data-edit-field="hireDate">
                    </label>

                    <label class="manager-employee-form-group">
                        <span>Ngày sinh</span>
                        <input type="date" name="birthday" value="{{ old('_employee_form_mode') === 'edit' ? old('birthday') : '' }}" data-edit-field="birthday">
                    </label>

                    <label class="manager-employee-form-group manager-employee-form-group--full">
                        <span>Kinh nghiệm</span>
                        <input type="text" name="experience" maxlength="50" value="{{ old('_employee_form_mode') === 'edit' ? old('experience') : '' }}" data-edit-field="experience" placeholder="VD: 2 năm chăm sóc khách hàng">
                    </label>

                    <label class="manager-employee-form-group manager-employee-form-group--full">
                        <span>Ghi chú</span>
                        <textarea name="notes" data-edit-field="notes" placeholder="Ghi chú thêm về nhân viên...">{{ old('_employee_form_mode') === 'edit' ? old('notes') : '' }}</textarea>
                    </label>
                </div>
            </section>

            <div class="manager-employee-modal-footer">
                <button type="button" class="manager-employee-btn-secondary" data-close-edit-modal>Hủy bỏ</button>
                <button type="submit" class="manager-employee-btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>

    <div class="manager-employee-overlay" data-resign-overlay aria-hidden="true">
        <form class="manager-employee-modal manager-employee-confirm-modal" method="POST" action="#" data-resign-form>
            @csrf

            <div class="manager-employee-modal-head">
                <div>
                    <div class="manager-employee-modal-title">Cho nhân viên nghỉ việc</div>
                    <div class="manager-employee-modal-sub">Hành động này không xóa dữ liệu nhân viên</div>
                </div>
                <button type="button" class="manager-employee-modal-close" data-close-resign-modal aria-label="Đóng">×</button>
            </div>

            <div class="manager-employee-confirm-body">
                <p>Bạn có chắc muốn cho nhân viên này nghỉ việc không? Tài khoản đăng nhập của người này sẽ bị vô hiệu hóa.</p>
                <strong data-resign-employee-name></strong>
            </div>

            <div class="manager-employee-modal-footer">
                <button type="button" class="manager-employee-btn-secondary" data-close-resign-modal>Hủy bỏ</button>
                <button type="submit" class="manager-employee-btn-danger">Xác nhận nghỉ việc</button>
            </div>
        </form>
    </div>

    <div class="manager-employee-toast manager-employee-hidden" data-toast role="status" aria-live="polite"></div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/client/js/manager/employee-management.js') }}?v={{ time() }}"></script>
@endpush
