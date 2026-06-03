# MANAGER_DASHBOARD_FILE_MAP.md

Cập nhật: 2026-06-03

File này map kiến trúc Dashboard/Report khu vực Manager trong repo hiện tại. Khi thêm KPI card, chart, table, list hoặc alert cho Manager, đọc file này cùng `docs/CODEX_MANAGER_DASHBOARD_CONTEXT.md` trước khi sửa code.

## 1. Tổng quan luồng Dashboard Manager hiện tại

Luồng chuẩn đang có trong repo:

```text
Web route Manager
-> Web Controller trả Blade/View có sẵn
-> Root container của page gắn data-*-url bằng route(...)
-> JS riêng của page đọc root.dataset.*
-> JS riêng chỉ render/helper và DashboardEngine.run([...])
-> DashboardEngine đọc .js-start-date, .js-end-date, .js-apply-filter
-> DashboardEngine gọi API bằng GET với query start_date/end_date
-> Route API Manager
-> Controller nhận DateRangeFilterRequest và branchId nếu route có {branchId}
-> Repository xử lý SQL Oracle, bind branch/date, normalize output
-> Controller trả JSON { success: true, data: ... }
-> JS render dữ liệu động lên UI có sẵn
```

Không tạo fetch riêng trong JS trang Manager. Không tự bind `.js-apply-filter` trong JS trang. Không tự tạo query string trong JS trang.

## 2. DashboardEngine và filter chung

File chính:

```text
public/assets/client/js/core/dashboard-engine.js
resources/views/components/global-control-panel.blade.php
resources/views/layouts/manager.blade.php
```

`resources/views/layouts/manager.blade.php` load:

```text
jquery-3.6.0
public/assets/client/js/core/dashboard-engine.js
@stack('scripts')
```

`resources/views/components/global-control-panel.blade.php` tạo các selector chung:

```text
.js-start-date
.js-end-date
.js-apply-filter
```

`DashboardEngine.run(apiConfigs)` hiện làm các việc sau:

- Tìm `.js-apply-filter` bằng `document.querySelector`.
- Đọc `.js-start-date` và `.js-end-date` bằng `document.querySelector`.
- Tạo payload `{ start_date, end_date }`.
- Validate `start_date <= end_date` nếu cả hai có giá trị.
- Tạo query string bằng `URLSearchParams`.
- Gọi từng API bằng `fetch(requestUrl, { method: "GET", headers: { Accept: "application/json" } })`.
- Nếu response có `success = true`, gọi `config.onSuccess(data.data)`.
- Nếu lỗi, gọi `config.onError(...)` nếu có.
- Gỡ handler click cũ qua `removeEventListener`.
- Gắn handler mới cho `.js-apply-filter`.
- Tự gọi `applyBtn.click()` để load lần đầu.

Lưu ý: DashboardEngine dùng selector global, nên mỗi page Manager nên chỉ có một global control panel chính.

## 3. Route web Manager

Include chain:

```text
routes/web/web.php
-> prefix manager, name manager., middleware auth + role:manager
-> routes/web/manager/index.php
```

Các file `routes/web/manager/dashboard.php`, `reports.php`, `service.php`, `inventory.php` đang trống.

Route web đang dùng:

```text
GET /manager
name: manager.index
controller: App\Http\Controllers\Web\Manager\DashboardController@index
view: pages.manager.branch-dashboard

GET /manager/dashboard
name: manager.dashboard
controller: App\Http\Controllers\Web\Manager\DashboardController@index
view: pages.manager.branch-dashboard

GET /manager/services
name: manager.service
controller: App\Http\Controllers\Web\Manager\ServiceController@index
view: pages.manager.branch-service-management

GET /manager/reports
name: manager.reports
controller: App\Http\Controllers\Web\Manager\ReportController@index
view: pages.manager.branch-revenue-reports

GET /manager/inventory
name: manager.inventory
controller: App\Http\Controllers\Web\Manager\InventoryController@index
view: pages.manager.inventory-management
```

Web Manager controller chỉ trả Blade, không xử lý dữ liệu dashboard động.

## 4. Route API Manager tổng quan

Include chain:

```text
routes/api/api.php
-> routes/api/dashboard/index.php
-> prefix dashboard, name api.dashboard., middleware web + auth
-> routes/api/dashboard/manager/index.php
-> prefix manager, name manager.
```

API Manager thực tế nằm dưới:

```text
/api/dashboard/manager/...
```

Các file module:

```text
routes/api/dashboard/manager/dashboard.php
routes/api/dashboard/manager/report.php
routes/api/dashboard/manager/service.php
routes/api/dashboard/manager/inventory.php
```

Route đọc dữ liệu dashboard dùng `GET`. Một số module service/inventory có route ghi `PATCH`, `POST`, `PUT`; không sửa các route này nếu task chỉ là DashboardEngine đọc dữ liệu.

## 5. Route API Manager theo module

### 5.1. Manager Overview

File:

```text
routes/api/dashboard/manager/dashboard.php
```

Route tổng không có branchId:

```text
GET /overview
name: api.dashboard.manager.overview
controller: Api\Manager\DashboardController@overview

GET /inventory-warning
name: api.dashboard.manager.inventory-warning
controller: Api\Manager\DashboardController@inventoryWarning

GET /health-warning
name: api.dashboard.manager.health-warning
controller: Api\Manager\DashboardController@healthWarning

GET /financial-risk-warning
name: api.dashboard.manager.financial-risk-warning
controller: Api\Manager\DashboardController@financialRiskWarning

GET /late-cancelled-bookings
name: api.dashboard.manager.late-cancelled-bookings
controller: Api\Manager\DashboardController@lateCancelledBookings

GET /top-revenue-services
name: api.dashboard.manager.top-revenue-services
controller: Api\Manager\DashboardController@topRevenueServices

GET /revenue-structure
name: api.dashboard.manager.revenue-structure
controller: Api\Manager\DashboardController@revenueStructure

GET /unpaid-invoices
name: api.dashboard.manager.unpaid-invoices
controller: Api\Manager\DashboardController@unpaidInvoices
```

Route theo branchId đang được Blade Manager overview dùng:

```text
GET /branches/{branchId}/overview
name: api.dashboard.manager.branches.overview
controller: Api\Manager\DashboardController@branchOverview

GET /branches/{branchId}/inventory-warning
name: api.dashboard.manager.branches.inventory-warning
controller: Api\Manager\DashboardController@branchInventoryWarning

GET /branches/{branchId}/health-warning
name: api.dashboard.manager.branches.health-warning
controller: Api\Manager\DashboardController@branchHealthWarning

GET /branches/{branchId}/financial-risk-warning
name: api.dashboard.manager.branches.financial-risk-warning
controller: Api\Manager\DashboardController@branchFinancialRiskWarning

GET /branches/{branchId}/late-cancelled-bookings
name: api.dashboard.manager.branches.late-cancelled-bookings
controller: Api\Manager\DashboardController@branchLateCancelledBookings

GET /branches/{branchId}/top-revenue-services
name: api.dashboard.manager.branches.top-revenue-services
controller: Api\Manager\DashboardController@branchTopRevenueServices

GET /branches/{branchId}/revenue-structure
name: api.dashboard.manager.branches.revenue-structure
controller: Api\Manager\DashboardController@branchRevenueStructure

GET /branches/{branchId}/unpaid-invoices
name: api.dashboard.manager.branches.unpaid-invoices
controller: Api\Manager\DashboardController@branchUnpaidInvoices
```

### 5.2. Manager Revenue Report

File:

```text
routes/api/dashboard/manager/report.php
```

Route tổng:

```text
GET /revenue
name: api.dashboard.manager.revenue
controller: Api\Manager\ReportController@index
```

Route theo branchId:

```text
GET /branches/{branchId}/revenue-report
name: api.dashboard.manager.branches.revenue-report.index
controller: Api\Branch\BranchRevenueReportController@index

GET /branches/{branchId}/revenue-report/target-progress
name: api.dashboard.manager.branches.revenue-report.target-progress
controller: Api\Branch\BranchRevenueReportController@targetProgress

GET /branches/{branchId}/revenue-report/revenue-comparison
name: api.dashboard.manager.branches.revenue-report.revenue-comparison
controller: Api\Branch\BranchRevenueReportController@revenueComparison

GET /branches/{branchId}/revenue-report/service-mix
name: api.dashboard.manager.branches.revenue-report.service-mix
controller: Api\Branch\BranchRevenueReportController@serviceMix

GET /branches/{branchId}/revenue-report/employee-performance
name: api.dashboard.manager.branches.revenue-report.employee-performance
controller: Api\Branch\BranchRevenueReportController@employeePerformance

GET /branches/{branchId}/revenue-report/customer-retention
name: api.dashboard.manager.branches.revenue-report.customer-retention
controller: Api\Branch\BranchRevenueReportController@customerRetention
```

### 5.3. Manager Service Management

File:

```text
routes/api/dashboard/manager/service.php
```

Route tổng:

```text
GET /services
name: api.dashboard.manager.services
controller: Api\Manager\ServiceController@index
```

Route theo branchId:

```text
GET /branches/{branchId}/services/management
name: api.dashboard.manager.branches.services.management.index
controller: Api\Branch\BranchServiceManagementController@index

GET /branches/{branchId}/services/management/kpi
name: api.dashboard.manager.branches.services.management.kpi
controller: Api\Branch\BranchServiceManagementController@kpi

GET /branches/{branchId}/services/revenue-progress
name: api.dashboard.manager.branches.services.revenue-progress
controller: Api\Branch\BranchServiceManagementController@revenueProgress

GET /branches/{branchId}/services/revenue-drop-alerts
name: api.dashboard.manager.branches.services.revenue-drop-alerts
controller: Api\Branch\BranchServiceManagementController@revenueDropAlerts

GET /branches/{branchId}/services/upsell-rate
name: api.dashboard.manager.branches.services.upsell-rate
controller: Api\Branch\BranchServiceManagementController@upsellRate

GET /branches/{branchId}/services/filtered/{search}/{serviceGroup}/{status}
name: api.dashboard.manager.branches.services.filtered-list
controller: Api\Branch\BranchServiceManagementController@services

GET /branches/{branchId}/services
name: api.dashboard.manager.branches.services.index
controller: Api\Branch\BranchServiceManagementController@services
```

Route ghi hiện có:

```text
PATCH /branches/{branchId}/services/{serviceId}/website-visibility
PATCH /branches/{branchId}/services/{serviceId}/emergency-lock
PATCH /branches/{branchId}/services/{serviceId}/price-override
```

### 5.4. Manager Inventory Management

File:

```text
routes/api/dashboard/manager/inventory.php
```

Route tổng:

```text
GET /inventory
name: api.dashboard.manager.inventory
controller: Api\Manager\InventoryController@index
```

Route theo branchId:

```text
GET /branches/{branchId}/inventory/materials
name: api.dashboard.manager.branches.inventory.materials.index
controller: Api\Branch\BranchInventoryMaterialController@index

GET /branches/{branchId}/inventory/materials/kpi
name: api.dashboard.manager.branches.inventory.materials.kpi
controller: Api\Branch\BranchInventoryMaterialController@kpi

GET /branches/{branchId}/inventory/materials/list
name: api.dashboard.manager.branches.inventory.materials.list
controller: Api\Branch\BranchInventoryMaterialController@materials

GET /branches/{branchId}/inventory/materials/{materialId}
name: api.dashboard.manager.branches.inventory.materials.show
controller: Api\Branch\BranchInventoryMaterialController@show
```

Route ghi hiện có:

```text
POST /branches/{branchId}/inventory/materials
PUT /branches/{branchId}/inventory/materials/{materialId}
PATCH /branches/{branchId}/inventory/materials/{materialId}/stop-import
PATCH /branches/{branchId}/inventory/materials/{materialId}/resume-import
```

## 6. Controller Manager/Branch đang dùng

### Web Manager

```text
app/Http/Controllers/Web/Manager/DashboardController.php
app/Http/Controllers/Web/Manager/ReportController.php
app/Http/Controllers/Web/Manager/ServiceController.php
app/Http/Controllers/Web/Manager/InventoryController.php
```

Các controller này chỉ trả Blade page tương ứng. Các method phụ như `filter`, `export`, `store` đang trả `back()->with(...)`, không thuộc luồng DashboardEngine.

### API Manager

```text
app/Http/Controllers/Api/Manager/DashboardController.php
```

Controller này:

- Dùng `DateRangeFilterRequest`.
- Dùng `ManagerDashboardRepositoryInterface`.
- Có helper `currentBranchId(DateRangeFilterRequest $request)` lấy `$request->user()?->employee?->branch_id ?? 0`.
- Các route không có `{branchId}` vẫn lọc theo branch hiện tại của user.
- Các route có `{branchId}` nhận branchId từ route.
- Trả JSON `{ success: true, data: ... }`.
- Không có helper `respondData`.

```text
app/Http/Controllers/Api/Manager/ReportController.php
app/Http/Controllers/Api/Manager/ServiceController.php
app/Http/Controllers/Api/Manager/InventoryController.php
```

Các controller tổng này vẫn dùng model trực tiếp:

- `ReportController@index`: dùng `Payment` và `Order`.
- `ServiceController@index`: dùng `Service`.
- `InventoryController@index`: dùng `BranchInventory`.

Không ưu tiên mở rộng các route tổng này cho chức năng Manager theo chi nhánh nếu page đang dùng route `/branches/{branchId}/...`.

### API Branch

```text
app/Http/Controllers/Api/Branch/BranchRevenueReportController.php
app/Http/Controllers/Api/Branch/BranchServiceManagementController.php
app/Http/Controllers/Api/Branch/BranchInventoryMaterialController.php
```

Các controller này nhận `branchId` từ route, dùng repository contract tương ứng, và route đọc dashboard dùng `DateRangeFilterRequest`.

Một số method ghi trong service/inventory trả `{ message, data }` thay vì `{ success, data }`; không dùng các method đó cho DashboardEngine đọc dữ liệu.

## 7. Repository và Interface Manager/Branch

Bindings trong:

```text
app/Providers/AppServiceProvider.php
```

Binding Manager/Branch liên quan:

```text
ManagerDashboardRepositoryInterface -> ManagerDashboardRepository
BranchServiceManagementRepositoryInterface -> BranchServiceManagementRepository
BranchRevenueReportRepositoryInterface -> BranchRevenueReportRepository
BranchInventoryMaterialRepositoryInterface -> BranchInventoryMaterialRepository
```

### Manager overview repository

```text
app/Repositories/Contracts/Manager/ManagerDashboardRepositoryInterface.php
app/Repositories/Eloquent/Manager/ManagerDashboardRepository.php
```

Method chính:

```text
getOverview
getScheduleKpis
getInventoryWarning
getHealthWarning
getFinancialRiskWarning
getLateCancelledBookings
getTopRevenueServices
getRevenueStructure
getUnpaidInvoices
```

Repository này đã có SQL Oracle cho các khối overview hiện tại. Có `normalizeFilters($branchId, $filters)`, `oracleBindings(...)`, `currentOracleBindings(...)`, `dateString(...)`, `previousPeriodFilters(...)`, và `dashboardDateBounds(...)`.

Binding SQL có dạng `p_branch_id`, `p_start_date`, `p_end_date`, `p_prev_start_date`, `p_prev_end_date` ở một số query; một số query khác dùng key bind `branch_id`, `start_date`, `end_date`. Khi thêm method mới nên ưu tiên style `p_*` như context đã yêu cầu.

### Branch revenue report repository

```text
app/Repositories/Contracts/Branch/BranchRevenueReportRepositoryInterface.php
app/Repositories/Eloquent/Branch/BranchRevenueReportRepository.php
```

Method hiện có:

```text
getDashboard
getTargetProgress
getRevenueComparison
getServiceMixAndAov
getServiceRevenueMix
getAverageOrderValue
getEmployeePerformance
getCustomerRetention
resolvePeriodRange
resolvePreviousPeriodRange
```

Implementation hiện vẫn là TODO và phần lớn trả `[]` hoặc skeleton. Khi triển khai revenue report, cần thêm SQL Oracle thật trong repository này và cập nhật interface nếu thêm method mới.

### Branch service management repository

```text
app/Repositories/Contracts/Branch/BranchServiceManagementRepositoryInterface.php
app/Repositories/Eloquent/Branch/BranchServiceManagementRepository.php
```

Method đã có SQL Oracle:

```text
getRevenueProgress
getRevenueDropAlerts
getUpsellRate
getServiceList
```

Method còn TODO hoặc trả rỗng:

```text
getOverview
getKpiCards
getLocalTopService
updateWebsiteVisibility
updateEmergencyLock
updatePriceOverride
resolvePeriodRange
logServiceAction
```

Repository này có `normalizeFilters(...)` để chuyển Carbon/string/null về chuỗi `YYYY-MM-DD`, tự fallback tháng hiện tại, và tự tính kỳ trước nếu thiếu.

### Branch inventory material repository

```text
app/Repositories/Contracts/Branch/BranchInventoryMaterialRepositoryInterface.php
app/Repositories/Eloquent/Branch/BranchInventoryMaterialRepository.php
```

Method đọc dữ liệu chính:

```text
getDashboard
getKpiCards
getTotalMaterialCount
getOutOfStockCount
getLowStockCount
getInventoryCapitalValue
getMaterialList
```

`getKpiCards`, `getMaterialList`, và `getKpiSnapshot` đã có SQL Oracle bind `p_branch_id`, `p_start_date`, `p_end_date`.

Method còn TODO hoặc chưa có persistence thật:

```text
findMaterialDetail
createMaterial
updateMaterial
stopImport
resumeImport
logMaterialAction
```

Repository này có `normalizeFilters(...)`, fallback tháng hiện tại, và `dateString(...)` để bind Oracle bằng chuỗi `YYYY-MM-DD`.

## 8. DateRangeFilterRequest

File:

```text
app/Http/Requests/Shared/DateRangeFilterRequest.php
```

Rules:

```text
start_date: nullable, date, before_or_equal:end_date
end_date: nullable, date, after_or_equal:start_date
```

Method:

```text
getStartDate(): Carbon|null
getEndDate(): Carbon|null
getPreviousStartDate(): Carbon|null
getPreviousEndDate(): Carbon|null
getFiltersArray(): array
```

`getFiltersArray()` trả:

```text
start_date
end_date
prev_start_date
prev_end_date
```

`prev_end_date = start_date - 1 ngày`. `prev_start_date` có cùng độ dài kỳ với khoảng `start_date/end_date`.

Repository phải normalize các Carbon này về chuỗi `YYYY-MM-DD` trước khi bind Oracle. Không tính kỳ trước trong JS hoặc controller.

## 9. Blade Manager pages

### Manager overview

```text
resources/views/pages/manager/branch-dashboard.blade.php
root id: managerBranchDashboard
js: public/assets/client/js/manager/branch-dashboard.js
```

Data URL:

```text
data-overview-url
data-inventory-warning-url
data-health-warning-url
data-financial-risk-warning-url
data-late-cancelled-bookings-url
data-top-revenue-services-url
data-revenue-structure-url
data-unpaid-invoices-url
```

Route name đang dùng đều là `api.dashboard.manager.branches.*`.

### Manager revenue report

```text
resources/views/pages/manager/branch-revenue-reports.blade.php
root id: managerRevenueReportPage
js: public/assets/client/js/manager/branch-revenue-reports.js
```

Data URL:

```text
data-target-progress-url
data-revenue-comparison-url
data-service-mix-url
data-employee-performance-url
data-customer-retention-url
```

Route name đang dùng đều là `api.dashboard.manager.branches.revenue-report.*`.

### Manager service management

```text
resources/views/pages/manager/branch-service-management.blade.php
root id: managerBranchServicePage
js: public/assets/client/js/manager/branch-service-management.js
```

Data URL:

```text
data-overview-url
data-kpi-url
data-service-revenue-progress-url
data-service-revenue-drop-alerts-url
data-service-upsell-rate-url
data-services-url
```

`data-services-url` dùng route `api.dashboard.manager.branches.services.filtered-list` và truyền `search`, `serviceGroup`, `status` qua path. Giá trị rỗng được thay bằng `_`.

### Manager inventory management

```text
resources/views/pages/manager/inventory-management.blade.php
root id: managerInventoryPage
js: public/assets/client/js/manager/inventory-management.js
```

Data URL:

```text
data-kpi-url
data-materials-url
```

Route name đang dùng:

```text
api.dashboard.manager.branches.inventory.materials.kpi
api.dashboard.manager.branches.inventory.materials.list
```

## 10. JavaScript Manager pages

```text
public/assets/client/js/manager/branch-dashboard.js
```

- Đọc `managerBranchDashboard`.
- Dùng `DashboardKpiAdapter`.
- Có render functions cho overview, inventory warning, health warning, financial risk, late cancelled bookings, top revenue services, revenue structure, unpaid invoices.
- Gọi `DashboardEngine.run([...])` với 8 API.
- Không có `fetch()` riêng.

```text
public/assets/client/js/manager/branch-revenue-reports.js
```

- Đọc `managerRevenueReportPage`.
- Gọi `DashboardEngine.run([...])` với 5 API.
- Hiện mới render `targetProgress` và `serviceMix`.
- `revenueComparison`, `employeePerformance`, `customerRetention` đang có `onSuccess: function () {}`.
- Không có `fetch()` riêng.

```text
public/assets/client/js/manager/branch-service-management.js
```

- Đọc `managerBranchServicePage`.
- Dùng `DashboardKpiAdapter`.
- Render KPI, revenue progress, revenue drop alerts, upsell rate, service list.
- Gọi `DashboardEngine.run([...])`.
- Không có `fetch()` riêng.

```text
public/assets/client/js/manager/inventory-management.js
```

- Đọc `managerInventoryPage`.
- Dùng `DashboardKpiAdapter`.
- Render KPI và material list.
- Gọi `DashboardEngine.run([...])`.
- Có bind filter cục bộ cho `.js-inventory-search-filter` và `.js-inventory-group-filter` để lọc danh sách đã tải trong bộ nhớ.
- Không bind `.js-apply-filter`, không tự fetch, không tự tạo query string.

## 11. Quy ước data-url và route name

Quy ước hiện tại:

- Blade là nơi tạo URL API bằng `route(...)`.
- Nếu route có `{branchId}`, Blade truyền `['branchId' => $managerBranchId]`.
- JS chỉ đọc `root.dataset.*`.
- JS không hard-code URL API.
- JS không hard-code branchId.
- Route đọc dữ liệu dùng `GET`.
- Controller trả `{ success: true, data: ... }` cho API DashboardEngine.

Khi thêm data-url mới, thêm vào root container đúng page đang dùng, không tạo root mới nếu không cần.

## 12. Quy tắc branchId trong repo hiện tại

Manager page theo chi nhánh nên dùng route dạng:

```text
/branches/{branchId}/...
```

Blade hiện lấy branch theo các kiểu:

```text
branch-dashboard.blade.php: auth()->user()?->employee?->branch_id ?? 1
branch-revenue-reports.blade.php: auth()->user()?->employee?->branch_id ?? 1
branch-service-management.blade.php: auth()->user()?->employee?->branch_id ?? 1
inventory-management.blade.php: auth()->user()?->employee?->branch_id, abort_if null
```

Cảnh báo: ba trang overview/revenue/service đang fallback `?? 1`; inventory nghiêm ngặt hơn và chặn nếu Manager không có branch. Khi triển khai chức năng mới, không hard-code branchId trong JS hoặc SQL. Nên cân nhắc giữ hướng nghiêm ngặt như inventory nếu task yêu cầu sửa branch resolution.

`Api\Manager\DashboardController` route không có branchId dùng `$request->user()?->employee?->branch_id ?? 0` để tránh lấy toàn hệ thống.

Repository phải bind branch, thường là `p_branch_id`, và SQL phải lọc theo chi nhánh.

## 13. Những file quan trọng không được phá

```text
docs/CODEX_MANAGER_DASHBOARD_CONTEXT.md
docs/MANAGER_DASHBOARD_FILE_MAP.md
routes/api/dashboard/manager/*.php
routes/web/manager/index.php
app/Http/Requests/Shared/DateRangeFilterRequest.php
app/Providers/AppServiceProvider.php
app/Http/Controllers/Api/Manager/DashboardController.php
app/Http/Controllers/Api/Branch/*Controller.php
app/Repositories/Contracts/Manager/ManagerDashboardRepositoryInterface.php
app/Repositories/Contracts/Branch/*RepositoryInterface.php
app/Repositories/Eloquent/Manager/ManagerDashboardRepository.php
app/Repositories/Eloquent/Branch/*Repository.php
resources/views/layouts/manager.blade.php
resources/views/components/global-control-panel.blade.php
resources/views/pages/manager/*.blade.php
public/assets/client/js/core/dashboard-engine.js
public/assets/client/js/manager/*.js
```

Không viết lại DashboardEngine. Không đổi layout lớn. Không xóa UI placeholder đang có nếu task chỉ yêu cầu nối dữ liệu động.

## 14. Checklist khi thêm chức năng Dashboard Manager mới

1. Xác định page: overview, revenue report, service management, hoặc inventory management.
2. Xác định route API đúng module trong `routes/api/dashboard/manager`.
3. Nếu là dữ liệu chi nhánh, route phải có `/branches/{branchId}/...`.
4. Blade phải có `$managerBranchId` và truyền URL bằng `route(..., ['branchId' => $managerBranchId])`.
5. JS page chỉ thêm render/helper và cấu hình `DashboardEngine.run([...])`.
6. Controller dùng `DateRangeFilterRequest`, lấy `$request->getFiltersArray()`, nhận `$branchId` nếu route có `{branchId}`.
7. Repository xử lý SQL Oracle, không đặt SQL trong controller.
8. Repository normalize Carbon/date về `YYYY-MM-DD` trước khi bind.
9. Repository bind `p_branch_id` hoặc branch bind tương ứng.
10. Interface tương ứng phải có method nếu thêm method repository mới.
11. Controller trả JSON `{ success: true, data: ... }`.
12. Không đụng route ghi `PATCH/POST/PUT` nếu task chỉ là dashboard đọc dữ liệu.

## 15. Những điểm bất thường hoặc chưa thống nhất

- `docs/MANAGER_DASHBOARD_FILE_MAP.md` trước đó chưa tồn tại.
- `routes/web/manager/dashboard.php`, `reports.php`, `service.php`, `inventory.php` đang trống; web Manager hiện gom route trong `routes/web/manager/index.php`.
- `BranchRevenueReportRepository` hiện chủ yếu là TODO và trả rỗng; revenue report Blade/JS có UI nhưng API chưa có SQL thật.
- `branch-revenue-reports.js` đang gọi đủ 5 API nhưng 3 handler còn rỗng.
- `branch-dashboard.blade.php`, `branch-revenue-reports.blade.php`, `branch-service-management.blade.php` fallback `$managerBranchId ?? 1`; điều này không lý tưởng cho dữ liệu Manager theo chi nhánh.
- `inventory-management.blade.php` đã nghiêm ngặt hơn: nếu không có branch thì abort 403.
- `Api\Manager\ReportController`, `ServiceController`, `InventoryController` là route tổng và đang dùng model trực tiếp; không phải luồng repository chuẩn cho page Manager theo chi nhánh.
- Một số repository dùng bind key `branch_id/start_date/end_date` thay vì `p_branch_id/p_start_date/p_end_date`; khi thêm mới nên dùng style `p_*`.
- `DashboardEngine` dùng selector global, không scoped theo page root.
- Service management có một số route ghi và repository write methods còn TODO.
- Inventory material write/detail methods còn TODO.
- Không chạy được `php artisan route:list` trong môi trường hiện tại vì `php` chưa có trong PATH; route map này dựa trên việc đọc file route trực tiếp.

## 16. Mẫu prompt SQL cho task tiếp theo

~~~text
Bạn hãy đọc kỹ docs/CODEX_MANAGER_DASHBOARD_CONTEXT.md và docs/MANAGER_DASHBOARD_FILE_MAP.md.

Nhiệm vụ:
Triển khai chức năng Dashboard Manager: [TÊN CHỨC NĂNG]

Loại trang Manager:
[Manager overview / Manager revenue report / Manager service management / Manager inventory management]

Loại dữ liệu:
[KPI card / Chart / Table / List / Alert]

SQL Oracle đầu vào:
```sql
DÁN SQL Ở ĐÂY
```

Yêu cầu:
- Xác định đúng route/controller/repository/Blade/JS theo file map.
- Nếu là dữ liệu chi nhánh, bắt buộc lọc theo branchId.
- Blade truyền URL bằng route(..., ['branchId' => $managerBranchId]) nếu route cần branchId.
- Route đọc dữ liệu dùng GET.
- Controller dùng DateRangeFilterRequest và trả JSON { success: true, data: ... }.
- Repository xử lý SQL Oracle, bind ngày, bind branchId, normalize output.
- JS không viết fetch riêng; chỉ thêm render/helper và DashboardEngine.run([...]).
- Không tạo kiến trúc mới, không sửa lan man, không hard-code dữ liệu mẫu/ngày/API URL/branchId.
~~~
