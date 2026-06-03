# CODEX_MANAGER_DASHBOARD_CONTEXT.md

Cập nhật: 2026-06-02

## 1. Mục đích file

File này mô tả quy tắc triển khai chức năng Dashboard cho khu vực **Manager** trong repo hiện tại.

Codex phải đọc file này trước khi triển khai bất kỳ chức năng Dashboard Manager nào.

Mục tiêu là bảo đảm khi thêm KPI card, chart, table, list hoặc alert cho Manager thì Codex làm đúng kiến trúc hiện tại:

```text
Blade/View đã có sẵn
-> JS riêng của trang chỉ cấu hình DashboardEngine
-> DashboardEngine tự gọi API bằng GET
-> Route API Manager
-> Controller
-> DateRangeFilterRequest
-> Repository xử lý SQL Oracle
-> Repository trả data
-> Controller trả JSON { success: true, data: ... }
-> JS render dữ liệu động lên UI có sẵn
```

Không tạo kiến trúc mới, không viết fetch riêng, không phá giao diện tĩnh đã chuyển từ React sang Blade/PHP.

---

## 2. Khác biệt quan trọng giữa CEO và Manager

CEO thường xem dữ liệu toàn hệ thống.

Manager thường xem dữ liệu theo **chi nhánh hiện tại**.

Vì vậy khi triển khai Dashboard Manager, Codex phải luôn kiểm tra:

1. Chức năng này thuộc trang Manager nào?
2. Chức năng này có cần giới hạn theo `branchId` không?
3. Route hiện tại có dạng `/branches/{branchId}/...` không?
4. Blade đã có biến `$managerBranchId` chưa?
5. SQL đã lọc theo chi nhánh chưa?
6. Repository đã bind `p_branch_id` chưa?

Không được bê nguyên tư duy CEO sang Manager rồi viết SQL toàn chuỗi nếu chức năng đó là dữ liệu chi nhánh.

---

## 3. Input chuẩn khi yêu cầu Codex triển khai một chức năng

Khi tôi giao task, tôi sẽ cung cấp theo mẫu:

```text
Nhiệm vụ:
Triển khai chức năng Dashboard Manager: [TÊN CHỨC NĂNG]

Loại trang Manager:
[Manager overview / Manager revenue report / Manager service management / Manager inventory management]

Loại dữ liệu:
[KPI card / Chart / Table / List / Alert]

SQL đầu vào:
```sql
DÁN SQL Ở ĐÂY
```

Yêu cầu:
- Tuân theo docs/CODEX_MANAGER_DASHBOARD_CONTEXT.md.
- Dựa vào docs/MANAGER_DASHBOARD_FILE_MAP.md để xác định đúng file cần sửa.
- Không sửa lan man.
- Không tạo kiến trúc mới.
- Không viết fetch riêng.
- JS chỉ dùng DashboardEngine.run([...]).
```

Nếu thông tin tôi đưa chưa đủ, Codex không được đoán bừa. Codex phải đọc repo để xác định đúng file, route, controller, repository, Blade và JS đang dùng.

---

## 4. Các trang Manager cần phân biệt

### 4.1. Manager overview

Dùng cho Dashboard chi nhánh tổng quan.

Ví dụ file thường liên quan:

```text
resources/views/pages/manager/branch-dashboard.blade.php
public/assets/client/js/manager/branch-dashboard.js
routes/api/dashboard/manager/dashboard.php
app/Http/Controllers/Api/Manager/DashboardController.php
app/Repositories/Eloquent/Manager/ManagerDashboardRepository.php
app/Repositories/Contracts/Manager/ManagerDashboardRepositoryInterface.php
```

Dùng khi chức năng thuộc dashboard tổng quan của Manager.

---

### 4.2. Manager revenue report

Dùng cho báo cáo doanh thu chi nhánh.

Ví dụ file thường liên quan:

```text
resources/views/pages/manager/branch-revenue-reports.blade.php
public/assets/client/js/manager/branch-revenue-reports.js
routes/api/dashboard/manager/report.php
app/Http/Controllers/Api/Branch/BranchRevenueReportController.php
app/Repositories/Eloquent/Branch/BranchRevenueReportRepository.php
app/Repositories/Contracts/Branch/BranchRevenueReportRepositoryInterface.php
```

Dùng khi chức năng liên quan doanh thu, mục tiêu doanh thu, so sánh doanh thu, cơ cấu dịch vụ, hiệu suất nhân viên, giữ chân khách hàng.

---

### 4.3. Manager service management

Dùng cho quản lý dịch vụ chi nhánh.

Ví dụ file thường liên quan:

```text
resources/views/pages/manager/branch-service-management.blade.php
public/assets/client/js/manager/branch-service-management.js
routes/api/dashboard/manager/service.php
app/Http/Controllers/Api/Branch/BranchServiceManagementController.php
app/Repositories/Eloquent/Branch/BranchServiceManagementRepository.php
app/Repositories/Contracts/Branch/BranchServiceManagementRepositoryInterface.php
```

Dùng khi chức năng liên quan danh sách dịch vụ chi nhánh, KPI dịch vụ, bật/tắt dịch vụ, khóa dịch vụ, giá override.

Lưu ý: route service có thể có cả API đọc dữ liệu và API ghi dữ liệu. Luồng DashboardEngine chỉ dùng API đọc dữ liệu bằng GET. Không đổi các route PATCH/POST/PUT nếu không được yêu cầu.

---

### 4.4. Manager inventory management

Dùng cho quản trị vật tư của chi nhánh.

Ví dụ file thường liên quan:

```text
resources/views/pages/manager/inventory-management.blade.php
public/assets/client/js/manager/inventory-management.js
routes/api/dashboard/manager/inventory.php
app/Http/Controllers/Api/Branch/BranchInventoryMaterialController.php
app/Repositories/Eloquent/Branch/BranchInventoryMaterialRepository.php
app/Repositories/Contracts/Branch/BranchInventoryMaterialRepositoryInterface.php
```

Dùng khi chức năng liên quan vật tư, tồn kho, cảnh báo tồn kho, danh sách nguyên liệu, nhập/xuất/tạm dừng/khôi phục vật tư.

Lưu ý: route inventory có thể có cả API đọc dữ liệu và API ghi dữ liệu. Luồng DashboardEngine chỉ dùng API đọc dữ liệu bằng GET.

---

## 5. Quy tắc bắt buộc về branchId

Manager phải được giới hạn theo chi nhánh.

### 5.1. Blade

Nếu route API cần `{branchId}`, Blade phải truyền đúng branch hiện tại.

Ví dụ:

```blade
@php
    $managerBranchId = auth()->user()?->employee?->branch_id ?? 1;
@endphp

<div
    id="managerRevenueReportPage"
    data-target-progress-url="{{ route('api.dashboard.manager.branches.revenue-report.target-progress', ['branchId' => $managerBranchId]) }}"
>
</div>
```

Quy tắc:

- Không hard-code URL API trong JS.
- Không hard-code branch ID trong JS.
- URL API đặt ở root container của page.
- Nếu root container đã có data-url thì chỉ bổ sung data-url mới, không dựng lại giao diện.

---

### 5.2. Route

Nếu chức năng là dữ liệu theo chi nhánh, route nên giữ dạng:

```php
Route::get('/branches/{branchId}/...', [Controller::class, 'method'])
    ->name('branches....');
```

Quy tắc:

- Route đọc dữ liệu dùng `Route::get`.
- Không dùng POST cho API đọc dữ liệu bằng DashboardEngine.
- Không tạo route trùng.
- Không đổi route cũ nếu frontend đang dùng.
- Không phá nhóm route Manager hiện có.

---

### 5.3. Controller

Controller phải nhận `$branchId` nếu route có `{branchId}`.

Ví dụ:

```php
/**
 * Mô tả chức năng:
 * Lấy dữ liệu doanh thu theo chi nhánh hiện tại của Manager.
 *
 * Input:
 * - int|string $branchId: Mã chi nhánh lấy từ route.
 * - DateRangeFilterRequest: tự xử lý start_date, end_date và kỳ trước.
 *
 * Output:
 * - JSON { success: true, data: ... }.
 *
 * Ghi chú:
 * - Controller không xử lý SQL.
 * - Controller chỉ truyền branchId và filters xuống Repository.
 */
public function revenueComparison(int|string $branchId, DateRangeFilterRequest $request): JsonResponse
{
    $data = $this->revenueReportRepository->getRevenueComparison(
        $branchId,
        $request->getFiltersArray()
    );

    return response()->json([
        'success' => true,
        'data' => $data,
    ]);
}
```

Quy tắc:

- Controller không viết SQL.
- Controller không tính toán KPI/chart/table.
- Controller không tự tính kỳ trước.
- Controller không format JSON phức tạp nếu Repository đã trả data đúng.
- Nếu file controller hiện tại đã có helper `respondData()` thì có thể dùng.
- Nếu file controller chưa có `respondData()` thì không tạo kiến trúc mới; giữ style response hiện tại của file.
- Output cuối cùng vẫn phải thống nhất `{ success: true, data: ... }`.

---

### 5.4. Repository

Repository phải nhận `branchId` hoặc filter có branch_id.

Ví dụ binding:

```php
$filters = $this->normalizeFilters($filters);

$bindings = [
    'p_branch_id' => $branchId,
    'p_start_date' => $filters['start_date'],
    'p_end_date' => $filters['end_date'],
    'p_prev_start_date' => $filters['prev_start_date'],
    'p_prev_end_date' => $filters['prev_end_date'],
];
```

SQL phải lọc chi nhánh:

```sql
WHERE o.branch_id = :p_branch_id
  AND o.paid_at >= TO_DATE(:p_start_date, 'YYYY-MM-DD')
  AND o.paid_at <  TO_DATE(:p_end_date, 'YYYY-MM-DD') + 1
```

Quy tắc:

- Không truyền Carbon object trực tiếp vào SQL.
- Normalize filter về chuỗi `YYYY-MM-DD` trước khi bind SQL.
- Bind đủ `p_branch_id` nếu chức năng thuộc dữ liệu chi nhánh.
- Dùng `TO_DATE(:p_start_date, 'YYYY-MM-DD')`.
- Dùng `NVL` để tránh null.
- Tránh chia cho 0 bằng `NULLIF`, `CASE WHEN`, hoặc xử lý PHP an toàn.
- Không trả alias SQL thô khó hiểu ra frontend.
- Repository là nơi xử lý SQL Oracle, bind parameter, tính toán, normalize output.

---

## 6. Quy tắc DateRangeFilterRequest

Frontend chỉ gửi:

```json
{
  "start_date": "YYYY-MM-DD",
  "end_date": "YYYY-MM-DD"
}
```

Backend tự xử lý:

```php
[
    'start_date' => ...,
    'end_date' => ...,
    'prev_start_date' => ...,
    'prev_end_date' => ...,
]
```

Logic kỳ trước:

```text
prev_end_date = start_date - 1 ngày
prev_start_date = prev_end_date - độ dài kỳ hiện tại + 1
```

Quy tắc:

- Không tính `prev_start_date`, `prev_end_date` trong JS.
- Không tính `prev_start_date`, `prev_end_date` trong Controller.
- Repository chỉ dùng các giá trị từ `$request->getFiltersArray()`.
- Nếu Repository cần bind Oracle thì normalize từ Carbon/null sang chuỗi `YYYY-MM-DD`.

---

## 7. Quy tắc DashboardEngine

Hệ thống đã có:

```js
DashboardEngine.run(apiConfigs);
```

DashboardEngine hiện phụ trách:

- Tìm `.js-apply-filter`.
- Đọc `.js-start-date`.
- Đọc `.js-end-date`.
- Validate `start_date <= end_date`.
- Tạo query string bằng `URLSearchParams`.
- Gọi từng API bằng `fetch(..., { method: "GET" })`.
- Gọi `config.onBefore(payload)` nếu có.
- Gọi `config.onSuccess(data.data)` nếu API trả `success = true`.
- Gọi `config.onError(...)` nếu lỗi.
- Gỡ event cũ để tránh bind click nhiều lần.
- Tự trigger click lần đầu để load dashboard.

Vì vậy JS riêng của trang Manager chỉ được làm 2 việc:

1. Định nghĩa render/helper function.
2. Gọi `DashboardEngine.run([...])`.

Ví dụ đúng:

```js
(function ($) {
    const root = document.getElementById("managerRevenueReportPage");

    if (!root || !window.DashboardEngine) {
        return;
    }

    /**
     * Mô tả chức năng:
     * Render dữ liệu so sánh doanh thu chi nhánh lên chart/card có sẵn.
     *
     * Input:
     * - data.current
     * - data.previous
     * - data.comparison
     *
     * Output:
     * - Cập nhật DOM/chart theo dữ liệu API.
     *
     * Ghi chú:
     * - Không gọi fetch trong hàm này.
     */
    function renderRevenueComparison(data) {
        // Render DOM/chart here.
    }

    $(document).ready(function () {
        DashboardEngine.run([
            {
                url: root.dataset.revenueComparisonUrl,
                onSuccess: renderRevenueComparison,
            },
        ]);
    });
})(window.jQuery);
```

Không được:

- Không viết `fetch()` trong JS trang.
- Không tự bind `.js-apply-filter`.
- Không tự tạo query string.
- Không tự gọi `.click()`.
- Không tự tính kỳ trước.
- Không hard-code API URL.
- Không hard-code dữ liệu mẫu.

---

## 8. Quy tắc JSON response

Controller trả JSON chuẩn:

```json
{
  "success": true,
  "data": {}
}
```

Repository chỉ trả phần `data`.

### 8.1. KPI có so sánh kỳ

Repository nên trả:

```php
return [
    'current' => [
        'value' => $currentValue,
    ],
    'previous' => [
        'value' => $previousValue,
    ],
    'comparison' => [
        'diff_amount' => $diffAmount,
        'change_percent' => $changePercent,
        'trend' => $trend,
    ],
];
```

Quy ước trend:

```text
change_percent > 0 => up
change_percent < 0 => down
change_percent = 0 => neutral
```

Không dùng lẫn nhiều kiểu key khác nhau nếu không cần.

---

### 8.2. Chart

Repository nên trả array rows đã normalize key rõ nghĩa:

```php
return [
    [
        'report_date' => '2026-05-01',
        'revenue' => 1000000,
        'cost' => 600000,
    ],
    [
        'report_date' => '2026-05-02',
        'revenue' => 1500000,
        'cost' => 700000,
    ],
];
```

Không trả alias SQL thô như `RN`, `REV_AMT`, `DT`, `CNT` nếu JS không dùng.

---

### 8.3. Table/List

Repository nên trả array rows:

```php
return [
    [
        'rank_no' => 1,
        'employee_name' => 'Nguyễn Văn A',
        'revenue' => 12500000,
        'order_count' => 18,
    ],
];
```

Không trả dữ liệu thừa, không trả raw SQL error.

---

## 9. Quy tắc Blade/View

Giao diện Manager đã có sẵn.

Codex chỉ được:

- Bổ sung `data-url`.
- Bổ sung `id`, `class`, `data-*` cần thiết cho render.
- Bổ sung selector vào đúng card/chart/table đã có.

Codex không được:

- Dựng lại UI từ đầu.
- Đổi layout lớn.
- Đổi style lớn nếu không cần.
- Xóa component có sẵn.
- Hard-code dữ liệu mẫu.
- Tự gắn biến database trực tiếp vào UI nếu luồng đúng là API + DashboardEngine.

Ví dụ:

```blade
<div
    id="managerRevenueReportPage"
    data-revenue-comparison-url="{{ route('api.dashboard.manager.branches.revenue-report.revenue-comparison', ['branchId' => $managerBranchId]) }}"
>
    {{-- UI có sẵn --}}
</div>
```

---

## 10. Quy tắc Controller

Khi thêm hoặc sửa method Controller, phải có comment mô tả chức năng.

Comment cần có:

- Mô tả chức năng.
- Input.
- Output.
- Ghi chú nếu có.

Controller chỉ giữ vai trò:

- Nhận `DateRangeFilterRequest`.
- Nhận `$branchId` nếu route có `{branchId}`.
- Gọi `$request->getFiltersArray()`.
- Gọi Repository.
- Trả JSON chuẩn.

Không được:

- Đặt SQL trong Controller.
- Tính toán KPI trong Controller.
- Tính kỳ trước trong Controller.
- Viết try/catch lặp lại nếu project đang có cơ chế xử lý lỗi phù hợp.
- Ép dùng `respondData()` nếu file hiện tại chưa có helper này và việc thêm helper làm đổi kiến trúc.

---

## 11. Quy tắc Repository

Khi thêm hoặc sửa method Repository, phải có comment mô tả chức năng.

Repository xử lý:

- SQL Oracle.
- Bind parameter.
- Normalize filter.
- Tính toán KPI/chart/table.
- So sánh kỳ hiện tại/kỳ trước.
- Chuẩn hóa key JSON trả về.

Repository không được:

- Trả alias SQL thô khó hiểu.
- Trả raw SQL error.
- Hard-code dữ liệu mẫu.
- Hard-code ngày.
- Bỏ lọc `branchId` với dữ liệu Manager chi nhánh.
- Truyền Carbon object trực tiếp vào SQL.
- Chia cho 0 không kiểm soát.

---

## 12. Quy tắc Route API

Codex phải kiểm tra route hiện tại trước khi thêm route.

Quy tắc:

- Route đọc dữ liệu dùng `Route::get`.
- Giữ đúng file module Manager:
  - `routes/api/dashboard/manager/dashboard.php`
  - `routes/api/dashboard/manager/report.php`
  - `routes/api/dashboard/manager/service.php`
  - `routes/api/dashboard/manager/inventory.php`
- Nếu route theo chi nhánh, giữ dạng `/branches/{branchId}/...`.
- Không tạo route trùng.
- Không phá route cũ.
- Không đổi route ghi dữ liệu nếu task chỉ là DashboardEngine đọc dữ liệu.

---

## 13. Quy tắc mô tả hàm trong file

Khi thêm hoặc sửa function, bắt buộc có comment mô tả chức năng.

Áp dụng cho:

- Controller method.
- Repository method.
- JS render function.
- JS helper function nếu thêm mới.

Mẫu comment PHP:

```php
/**
 * Mô tả chức năng:
 * ...
 *
 * Input:
 * - ...
 *
 * Output:
 * - ...
 *
 * Ghi chú:
 * - ...
 */
```

Mẫu comment JS:

```js
/**
 * Mô tả chức năng:
 * ...
 *
 * Input:
 * - ...
 *
 * Output:
 * - ...
 *
 * Ghi chú:
 * - ...
 */
```

---

## 14. Không được làm

Codex không được:

- Viết lại DashboardEngine nếu không thật sự cần.
- Viết `fetch()` riêng trong JS trang.
- Bind lại nút `.js-apply-filter`.
- Tự tạo query string trong JS.
- Tự trigger click trong JS.
- Tự tính `prev_start_date`, `prev_end_date` ở JS.
- Tự tính `prev_start_date`, `prev_end_date` ở Controller.
- Đưa SQL vào Controller.
- Hard-code dữ liệu mẫu.
- Hard-code ngày.
- Hard-code branch ID trong JS.
- Tạo route POST cho API đọc dữ liệu bằng DashboardEngine.
- Đổi kiến trúc repo hiện tại.
- Làm lại giao diện từ đầu.
- Trả alias SQL thô ra frontend.
- Sửa lan man ngoài phạm vi chức năng.

---

## 15. Checklist trước khi báo hoàn thành

Codex phải tự kiểm tra:

### Route

- Route đúng file Manager chưa?
- Route dùng GET chưa?
- Route có `{branchId}` nếu là dữ liệu chi nhánh chưa?
- Route đã được include chưa?
- Route có trùng route cũ không?
- Route gọi đúng Controller chưa?

### Blade

- Root container có data-url đúng chưa?
- Nếu route cần branchId, Blade đã truyền `$managerBranchId` chưa?
- Selector render có tồn tại chưa?
- Không làm vỡ giao diện cũ chưa?

### Controller

- Có dùng `DateRangeFilterRequest` chưa?
- Có gọi `$request->getFiltersArray()` chưa?
- Có nhận và truyền `$branchId` nếu cần chưa?
- Có gọi Repository chưa?
- Có trả JSON chuẩn `{ success: true, data: ... }` chưa?
- Có comment mô tả function chưa?

### Repository

- Có normalize filter trước khi bind SQL chưa?
- Có bind `p_branch_id` nếu là dữ liệu chi nhánh chưa?
- Có bind đúng ngày chưa?
- Có dùng `TO_DATE(..., 'YYYY-MM-DD')` chưa?
- Có xử lý null chưa?
- Có tránh chia cho 0 chưa?
- Có trả data đúng cấu trúc JS cần chưa?
- Có comment mô tả function chưa?

### JavaScript

- Có dùng `DashboardEngine.run([...])` chưa?
- Không có `fetch()` riêng chưa?
- Không bind `.js-apply-filter` riêng chưa?
- Không tự tạo query string chưa?
- Không tự trigger click chưa?
- Hàm render nhận đúng `data` từ API chưa?
- Có xử lý null/undefined an toàn chưa?
- Có comment mô tả function chưa?

### Kiểm tra PHP

Nếu không chạy được:

```bash
php -l
```

Không được báo đã kiểm tra syntax PHP thành công.

Phải ghi rõ:

```text
Chưa chạy được php -l vì máy chưa nhận lệnh php trong PATH.
```

---

## 16. Format trả lời sau khi Codex hoàn thành

Sau khi sửa code, Codex phải tổng hợp:

1. File đã sửa/thêm.
2. Route API đã thêm/cập nhật.
3. Controller method đã thêm/cập nhật.
4. Repository method đã thêm/cập nhật.
5. SQL đã đặt ở đâu.
6. Binding Oracle đã dùng.
7. Cấu trúc data Repository trả về.
8. JSON API cuối cùng.
9. Blade đã gắn `data-url`/selector như thế nào.
10. JS đã dùng `DashboardEngine.run([...])` như thế nào.
11. Các hàm đã thêm mô tả chức năng.
12. Cách test nhanh trên trình duyệt/Postman.
13. Những phần chưa kiểm tra được.

---

## 17. Prompt ngắn dùng mỗi lần giao task cho Codex

```text
Bạn hãy đọc kỹ `docs/CODEX_MANAGER_DASHBOARD_CONTEXT.md` và `docs/MANAGER_DASHBOARD_FILE_MAP.md`.

Nhiệm vụ:
Triển khai chức năng Dashboard Manager: [TÊN CHỨC NĂNG]

Loại trang Manager:
[Manager overview / Manager revenue report / Manager service management / Manager inventory management]

Loại dữ liệu:
[KPI card / Chart / Table / List / Alert]

SQL đầu vào:
```sql
DÁN SQL Ở ĐÂY
```

Yêu cầu:
- Đọc repo hiện tại trước khi sửa.
- Xác định đúng route/controller/repository/Blade/JS theo file map.
- Nếu là dữ liệu chi nhánh, bắt buộc lọc theo `branchId`.
- Blade truyền URL bằng `route(..., ['branchId' => $managerBranchId])` nếu route cần branchId.
- Route đọc dữ liệu dùng GET vì DashboardEngine gọi GET.
- Controller dùng DateRangeFilterRequest, lấy `$request->getFiltersArray()`, truyền branchId nếu có, gọi Repository, trả JSON chuẩn.
- Repository xử lý SQL Oracle, bind ngày, bind branchId nếu cần, normalize output.
- JS không viết fetch riêng, chỉ thêm render function và cấu hình `DashboardEngine.run([...])`.
- Không tạo kiến trúc mới.
- Không sửa lan man.
- Không hard-code dữ liệu mẫu/ngày/API URL.
- Không làm lại UI.
- Sau khi sửa, trả về checklist đầy đủ theo context.
```
