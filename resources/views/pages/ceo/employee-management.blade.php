@extends('layouts.ceo')

@section('title', 'Quản lý nhân viên')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/ceo/employee-management.css') }}">
@endpush

@section('content')
<div
    id="ceoEmployeePage"
    class="ceo-employee-page"
    data-create-has-errors="{{ $errors->any() && old('_employee_form_mode', 'create') === 'create' ? '1' : '0' }}"
    data-edit-has-errors="{{ $errors->any() && old('_employee_form_mode') === 'edit' ? '1' : '0' }}"
    data-old-employee-id="{{ old('_employee_id') }}"
    data-update-url-template="{{ route('ceo.employees.update', ['employee' => '__EMPLOYEE_ID__']) }}"
    data-resign-url-template="{{ route('ceo.employees.resign', ['employee' => '__EMPLOYEE_ID__']) }}"
>
    <script type="application/json" id="ceoEmployeeData">@json($employees ?? [])</script>

    <header class="ceo-employee-header">
        <div>
            <h1>Quản lý nhân viên</h1>
            <p>Theo dõi nhân sự, vị trí, chi nhánh phụ trách và trạng thái làm việc trên toàn hệ thống.</p>
        </div>
    </header>

    @if (session('status'))
        <div class="ceo-employee-flash ceo-employee-flash--success">{{ session('status') }}</div>
    @endif

    <section class="ceo-employee-stats-grid" aria-label="Tổng quan nhân viên">
        <article class="ceo-employee-stat-card">
            <div class="ceo-employee-stat-top">
                <span class="ceo-employee-stat-icon ceo-employee-stat-icon--purple">NV</span>
            </div>
            <div class="ceo-employee-stat-value" data-stat-total>0</div>
            <div class="ceo-employee-stat-label">Tổng nhân viên</div>
        </article>

        <article class="ceo-employee-stat-card">
            <div class="ceo-employee-stat-top">
                <span class="ceo-employee-stat-icon ceo-employee-stat-icon--blue">CN</span>
            </div>
            <div class="ceo-employee-stat-value" data-stat-branches>0</div>
            <div class="ceo-employee-stat-label">Chi nhánh có nhân viên</div>
        </article>

        <article class="ceo-employee-stat-card">
            <div class="ceo-employee-stat-top">
                <span class="ceo-employee-stat-icon ceo-employee-stat-icon--amber">₫</span>
            </div>
            <div class="ceo-employee-stat-value" data-stat-avg-salary>0</div>
            <div class="ceo-employee-stat-label">Lương trung bình</div>
        </article>
    </section>

    <section class="ceo-employee-table-card">
        <div class="ceo-employee-table-header">
            <div>
                <span class="ceo-employee-table-title">Danh sách nhân viên</span>
                <span class="ceo-employee-table-count" data-table-count>(0 người)</span>
            </div>

            <div class="ceo-employee-toolbar" aria-label="Bộ lọc nhân viên">
                <label class="ceo-employee-search-box">
                    <span class="ceo-employee-search-icon">⌕</span>
                    <input type="text" placeholder="Tìm kiếm..." data-search-input>
                </label>

                <select class="ceo-employee-filter-select" data-filter-branch aria-label="Lọc chi nhánh">
                    <option value="">Tất cả chi nhánh</option>
                    @foreach (($branches ?? []) as $branch)
                        <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                    @endforeach
                </select>

                <button type="button" class="ceo-employee-btn-primary" data-open-create-modal>+ Thêm quản lý</button>
            </div>
        </div>

        <div class="ceo-employee-table-wrapper">
            <table class="ceo-employee-table">
                <thead>
                    <tr>
                        <th>Nhân viên</th>
                        <th>Liên hệ</th>
                        <th>Vị trí</th>
                        <th>Chi nhánh</th>
                        <th>Lương</th>
                        <th>Ngày vào làm</th>
                        <th>Kinh nghiệm</th>
                        <th>Trạng thái</th>
                        <th class="ceo-employee-actions-col">Thao tác</th>
                    </tr>
                </thead>
                <tbody data-employee-table-body></tbody>
            </table>
        </div>

        <div class="ceo-employee-empty-state ceo-employee-hidden" data-empty-state>
            <div class="ceo-employee-empty-icon">∅</div>
            <p>Không tìm thấy nhân viên nào phù hợp</p>
        </div>
    </section>

    <div class="ceo-employee-overlay" data-create-overlay aria-hidden="true">
        <form class="ceo-employee-modal" method="POST" action="{{ route('ceo.employees.store') }}" data-create-form>
            @csrf
            <input type="hidden" name="_employee_form_mode" value="create">
            <div class="ceo-employee-modal-head">
                <div>
                    <div class="ceo-employee-modal-title">Thêm quản lý chi nhánh</div>
                    <div class="ceo-employee-modal-sub">Tạo tài khoản đăng nhập và hồ sơ nhân viên mới</div>
                </div>
                <button type="button" class="ceo-employee-modal-close" data-close-create-modal aria-label="Đóng">×</button>
            </div>

            @if ($errors->any() && old('_employee_form_mode', 'create') === 'create')
                <div class="ceo-employee-form-errors" data-create-errors>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @else
                <div class="ceo-employee-form-errors ceo-employee-hidden" data-create-errors></div>
            @endif

            <section class="ceo-employee-form-section">
                <div class="ceo-employee-form-section-title">Thông tin cá nhân</div>
                <div class="ceo-employee-form-grid">
                    <label class="ceo-employee-form-group ceo-employee-form-group--full">
                        <span>Họ và tên <strong>*</strong></span>
                        <input type="text" name="full_name" maxlength="200" value="{{ old('full_name') }}" placeholder="VD: Trần Minh Khoa">
                    </label>

                    <label class="ceo-employee-form-group">
                        <span>Email đăng nhập <strong>*</strong></span>
                        <input type="email" name="email" maxlength="255" value="{{ old('email') }}" placeholder="manager.branch@pethotel.test">
                    </label>

                    <label class="ceo-employee-form-group">
                        <span>Số điện thoại</span>
                        <input type="tel" name="phone" maxlength="20" value="{{ old('phone') }}" placeholder="0901 234 567">
                    </label>

                    <label class="ceo-employee-form-group">
                        <span>Mật khẩu <strong>*</strong></span>
                        <input type="password" name="password" autocomplete="new-password" placeholder="Tối thiểu 6 ký tự">
                    </label>

                    <label class="ceo-employee-form-group">
                        <span>Xác nhận mật khẩu <strong>*</strong></span>
                        <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Nhập lại mật khẩu">
                    </label>
                </div>
            </section>

            <section class="ceo-employee-form-section">
                <div class="ceo-employee-form-section-title">Thông tin công việc</div>
                <div class="ceo-employee-form-grid">
                    <label class="ceo-employee-form-group">
                        <span>Chi nhánh phụ trách <strong>*</strong></span>
                        <select name="branch_id">
                            <option value="">Chọn chi nhánh</option>
                            @foreach (($branches ?? []) as $branch)
                                <option value="{{ $branch['id'] }}" @selected((string) old('branch_id') === (string) $branch['id'])>{{ $branch['name'] }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="ceo-employee-form-group">
                        <span>Lương</span>
                        <input type="number" name="salary" min="0" step="0.01" value="{{ old('salary') }}" placeholder="VD: 18000000">
                    </label>

                    <label class="ceo-employee-form-group">
                        <span>Ngày vào làm</span>
                        <input type="date" name="hire_date" value="{{ old('hire_date') }}">
                    </label>

                    <label class="ceo-employee-form-group">
                        <span>Ngày sinh</span>
                        <input type="date" name="birthday" value="{{ old('birthday') }}">
                    </label>

                    <label class="ceo-employee-form-group ceo-employee-form-group--full">
                        <span>Kinh nghiệm</span>
                        <input type="text" name="experience" maxlength="50" value="{{ old('experience') }}" placeholder="VD: 3 năm quản lý chi nhánh">
                    </label>

                    <label class="ceo-employee-form-group ceo-employee-form-group--full">
                        <span>Ghi chú</span>
                        <textarea name="notes" placeholder="Ghi chú thêm về quản lý...">{{ old('notes') }}</textarea>
                    </label>
                </div>
            </section>

            <div class="ceo-employee-modal-footer">
                <button type="button" class="ceo-employee-btn-secondary" data-close-create-modal>Hủy bỏ</button>
                <button type="submit" class="ceo-employee-btn-primary">Tạo quản lý</button>
            </div>
        </form>
    </div>

    <div class="ceo-employee-overlay" data-edit-overlay aria-hidden="true">
        <form class="ceo-employee-modal" method="POST" action="{{ old('_employee_id') ? route('ceo.employees.update', ['employee' => old('_employee_id')]) : '#' }}" data-edit-form>
            @csrf
            @method('PATCH')
            <input type="hidden" name="_employee_form_mode" value="edit">
            <input type="hidden" name="_employee_id" value="{{ old('_employee_id') }}" data-edit-employee-id>

            <div class="ceo-employee-modal-head">
                <div>
                    <div class="ceo-employee-modal-title">Sửa thông tin quản lý</div>
                    <div class="ceo-employee-modal-sub">Chỉ cập nhật hồ sơ nhân viên, không đổi email, mật khẩu hoặc vai trò</div>
                </div>
                <button type="button" class="ceo-employee-modal-close" data-close-edit-modal aria-label="Đóng">×</button>
            </div>

            @if ($errors->any() && old('_employee_form_mode') === 'edit')
                <div class="ceo-employee-form-errors" data-edit-errors>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @else
                <div class="ceo-employee-form-errors ceo-employee-hidden" data-edit-errors></div>
            @endif

            <section class="ceo-employee-form-section">
                <div class="ceo-employee-form-section-title">Thông tin có thể chỉnh sửa</div>
                <div class="ceo-employee-form-grid">
                    <label class="ceo-employee-form-group ceo-employee-form-group--full">
                        <span>Họ và tên <strong>*</strong></span>
                        <input type="text" name="full_name" maxlength="200" value="{{ old('_employee_form_mode') === 'edit' ? old('full_name') : '' }}" data-edit-field="name" placeholder="VD: Trần Minh Khoa">
                    </label>

                    <label class="ceo-employee-form-group">
                        <span>Số điện thoại</span>
                        <input type="tel" name="phone" maxlength="20" value="{{ old('_employee_form_mode') === 'edit' ? old('phone') : '' }}" data-edit-field="phone" placeholder="0901 234 567">
                    </label>

                    <label class="ceo-employee-form-group">
                        <span>Chi nhánh phụ trách <strong>*</strong></span>
                        <select name="branch_id" data-edit-field="branchId">
                            <option value="">Chọn chi nhánh</option>
                            @foreach (($branches ?? []) as $branch)
                                <option value="{{ $branch['id'] }}" @selected(old('_employee_form_mode') === 'edit' && (string) old('branch_id') === (string) $branch['id'])>{{ $branch['name'] }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="ceo-employee-form-group">
                        <span>Lương</span>
                        <input type="number" name="salary" min="0" step="0.01" value="{{ old('_employee_form_mode') === 'edit' ? old('salary') : '' }}" data-edit-field="salary" placeholder="VD: 18000000">
                    </label>

                    <label class="ceo-employee-form-group">
                        <span>Ngày vào làm</span>
                        <input type="date" name="hire_date" value="{{ old('_employee_form_mode') === 'edit' ? old('hire_date') : '' }}" data-edit-field="hireDate">
                    </label>

                    <label class="ceo-employee-form-group">
                        <span>Ngày sinh</span>
                        <input type="date" name="birthday" value="{{ old('_employee_form_mode') === 'edit' ? old('birthday') : '' }}" data-edit-field="birthday">
                    </label>

                    <label class="ceo-employee-form-group ceo-employee-form-group--full">
                        <span>Kinh nghiệm</span>
                        <input type="text" name="experience" maxlength="50" value="{{ old('_employee_form_mode') === 'edit' ? old('experience') : '' }}" data-edit-field="experience" placeholder="VD: 3 năm quản lý chi nhánh">
                    </label>

                    <label class="ceo-employee-form-group ceo-employee-form-group--full">
                        <span>Ghi chú</span>
                        <textarea name="notes" data-edit-field="notes" placeholder="Ghi chú thêm về quản lý...">{{ old('_employee_form_mode') === 'edit' ? old('notes') : '' }}</textarea>
                    </label>
                </div>
            </section>

            <div class="ceo-employee-modal-footer">
                <button type="button" class="ceo-employee-btn-secondary" data-close-edit-modal>Hủy bỏ</button>
                <button type="submit" class="ceo-employee-btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>

    <div class="ceo-employee-overlay" data-resign-overlay aria-hidden="true">
        <form class="ceo-employee-modal ceo-employee-confirm-modal" method="POST" action="#" data-resign-form>
            @csrf
            <div class="ceo-employee-modal-head">
                <div>
                    <div class="ceo-employee-modal-title">Cho quản lý nghỉ việc</div>
                    <div class="ceo-employee-modal-sub">Hành động này không xóa dữ liệu nhân viên</div>
                </div>
                <button type="button" class="ceo-employee-modal-close" data-close-resign-modal aria-label="Đóng">×</button>
            </div>

            <div class="ceo-employee-confirm-body">
                <p>Bạn có chắc muốn cho quản lý này nghỉ việc không? Tài khoản đăng nhập của người này sẽ bị vô hiệu hóa.</p>
                <strong data-resign-employee-name></strong>
            </div>

            <div class="ceo-employee-modal-footer">
                <button type="button" class="ceo-employee-btn-secondary" data-close-resign-modal>Hủy bỏ</button>
                <button type="submit" class="ceo-employee-btn-danger">Xác nhận nghỉ việc</button>
            </div>
        </form>
    </div>

    <div class="ceo-employee-toast ceo-employee-hidden" data-demo-toast role="status" aria-live="polite"></div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/client/js/ceo/employee-management.js') }}"></script>
@endpush
