# Dashboard File Map

Cập nhật: 2026-05-31

Phạm vi file map này: chỉ các file liên quan đến Dashboard CEO/Manager, DashboardEngine, filter ngày, route dashboard, controller API/web, repository, Blade và JavaScript đang được nối với dashboard hiện tại.

## 1. Tổng quan luồng Dashboard hiện tại

Luồng chung đang đi theo kiến trúc trong `docs/CODEX_DASHBOARD_CONTEXT.md`:

```text
Blade page
-> root container gắn data-*-url bằng route('api.dashboard...')
-> JS riêng của trang đọc root.dataset.*
-> DashboardEngine.run([...])
-> DashboardEngine đọc .js-start-date, .js-end-date, .js-apply-filter
-> fetch GET từng API với query string start_date/end_date
-> routes/api/dashboard/*
-> Controller API
-> DateRangeFilterRequest
-> Repository hoặc model/query
-> JSON { success: true, data: ... }
-> JS render vào DOM/Chart/KPI
```

Ghi chú thực tế trong repo hiện tại:

- `DashboardEngine` gọi API bằng `GET`; vì vậy route dashboard đọc dữ liệu nên dùng `Route::get`.
- `DateRangeFilterRequest` trả về `start_date`, `end_date`, `prev_start_date`, `prev_end_date` dạng `Carbon|null`; repository CEO có hàm normalize để chuyển về chuỗi `Y-m-d`.
- `respondData()` chưa nằm trong base controller. Search hiện tại chỉ thấy `respondData()` là private method trong `app/Http/Controllers/Api/Ceo/DashboardController.php`.
- Một số controller dashboard khác đang trả `response()->json(['success' => true, 'data' => ...])` trực tiếp.

## 2. File DashboardEngine

| Nội dung | File |
|---|---|
| Core engine gọi API dashboard | `public/assets/client/js/core/dashboard-engine.js` |
| Layout CEO load engine | `resources/views/layouts/ceo.blade.php` |
| Layout Manager load engine | `resources/views/layouts/manager.blade.php` |

`DashboardEngine.run(apiConfigs)` hiện xử lý:

- Tìm `.js-apply-filter`.
- Đọc `.js-start-date` và `.js-end-date`.
- Validate `start_date <= end_date`.
- Tạo query string bằng `URLSearchParams`.
- Gọi từng config bằng `fetch(..., { method: "GET" })`.
- Gọi `onBefore`, `onSuccess(data.data)`, `onError` nếu có.
- Gỡ event cũ qua `_dashboardHandler`.
- Tự trigger click lần đầu để load dashboard.

## 3. Global control panel

| Nội dung | File |
|---|---|
| Component chứa `.js-start-date`, `.js-end-date`, `.js-apply-filter` | `resources/views/components/global-control-panel.blade.php` |
| CSS component | `public/assets/client/css/components/global-control-panel.css` |
| JS component rỗng/nhẹ, chỉ init warning | `public/assets/client/js/components/global-control-panel.js` |

Ghi chú:

- `global-control-panel.js` tồn tại nhưng search hiện tại không thấy được load trong Blade/layout.
- `DashboardEngine` mới là nơi bind nút `.js-apply-filter`.
- Các page CEO/Manager hiện đều dùng `<x-global-control-panel ... />`.

## 4. Route Dashboard chính

### API route chính

| Tầng | File | Vai trò |
|---|---|---|
| API root | `routes/api/api.php` | Laravel gắn prefix `/api`; file này `require __DIR__.'/dashboard/index.php'` |
| Dashboard API root | `routes/api/dashboard/index.php` | Prefix `dashboard`, name `api.dashboard.`, middleware `web`, `auth`; include CEO và Manager |

API dashboard thực tế bắt đầu bằng:

```text
/api/dashboard/...
```

### Web route chính

| Tầng | File | Vai trò |
|---|---|---|
| Web root | `routes/web/web.php` | Include area theo role: customer, manager, ceo |
| Web CEO | `routes/web/ceo/index.php` | Prefix `/ceo`, name `ceo.` từ `routes/web/web.php` |
| Web Manager | `routes/web/manager/index.php` | Prefix `/manager`, name `manager.` từ `routes/web/web.php` |

## 5. Route Dashboard CEO

| Loại | File | Prefix/name | Nội dung |
|---|---|---|---|
| Web CEO | `routes/web/ceo/index.php` | `/ceo`, `ceo.` | `/`, `/dashboard`, `/branches`, `/services`, `/vendors`, `/finance` |
| API CEO index | `routes/api/dashboard/ceo/index.php` | `/api/dashboard/ceo`, `api.dashboard.ceo.` | Include dashboard, branch, finance, service, vendor |
| API CEO overview | `routes/api/dashboard/ceo/dashboard.php` | không thêm prefix | KPI/chart/list/risk của dashboard overview |
| API CEO branch | `routes/api/dashboard/ceo/branch.php` | `/branches`, `branches.` | Quản lý mạng lưới chi nhánh |
| API CEO finance | `routes/api/dashboard/ceo/finance.php` | `/finance...`, `finance...` | Phân tích tài chính |
| API CEO service | `routes/api/dashboard/ceo/service.php` | `/services`, `services.` | Quản trị/phân tích dịch vụ |
| API CEO vendor | `routes/api/dashboard/ceo/vendor.php` | `/vendors`, `vendors` | Đối tác/nhà cung cấp |

Các API CEO overview trong `routes/api/dashboard/ceo/dashboard.php`:

| Method | Path | Route name | Controller method |
|---|---|---|---|
| GET | `/api/dashboard/ceo/current-hotel-occupancy` | `api.dashboard.ceo.current-hotel-occupancy` | `currentHotelOccupancy` |
| GET | `/api/dashboard/ceo/occupancy-rate` | `api.dashboard.ceo.occupancy-rate` | `occupancyRate` |
| GET | `/api/dashboard/ceo/revpar` | `api.dashboard.ceo.revpar` | `revpar` |
| GET | `/api/dashboard/ceo/customer-trend` | `api.dashboard.ceo.customer-trend` | `customerTrend` |
| GET | `/api/dashboard/ceo/revenue` | `api.dashboard.ceo.revenue` | `chainRevenue` |
| GET | `/api/dashboard/ceo/estimated-cogs` | `api.dashboard.ceo.estimated-cogs` | `estimatedCogs` |
| GET | `/api/dashboard/ceo/revenue-mix` | `api.dashboard.ceo.revenue-mix` | `revenueMix` |
| GET | `/api/dashboard/ceo/revenue-and-cogs-trend` | `api.dashboard.ceo.revenue-and-cogs-trend` | `revenueAndCogsTrend` |
| GET | `/api/dashboard/ceo/branch-revenue` | `api.dashboard.ceo.branch-revenue` | `branchRevenue` |
| GET | `/api/dashboard/ceo/branch-ranking` | `api.dashboard.ceo.branch-ranking` | `branchRanking` |
| GET | `/api/dashboard/ceo/top-used-services` | `api.dashboard.ceo.top-used-services` | `topUsedServices` |
| GET | `/api/dashboard/ceo/risk-alerts` | `api.dashboard.ceo.risk-alerts` | `riskAlerts` |

## 6. Route Dashboard Manager

| Loại | File | Prefix/name | Nội dung |
|---|---|---|---|
| Web Manager | `routes/web/manager/index.php` | `/manager`, `manager.` | `/`, `/dashboard`, `/services`, `/reports`, `/inventory` |
| API Manager index | `routes/api/dashboard/manager/index.php` | `/api/dashboard/manager`, `api.dashboard.manager.` | Include dashboard, service, inventory, report |
| API Manager overview | `routes/api/dashboard/manager/dashboard.php` | không thêm prefix | `/overview` cho dashboard chi nhánh |
| API Manager service | `routes/api/dashboard/manager/service.php` | `/services`, `/branches/{branchId}/services` | API tổng và API quản lý dịch vụ chi nhánh |
| API Manager inventory | `routes/api/dashboard/manager/inventory.php` | `/inventory`, `/branches/{branchId}/inventory/materials` | API tổng và API vật tư chi nhánh |
| API Manager report | `routes/api/dashboard/manager/report.php` | `/revenue`, `/branches/{branchId}/revenue-report` | API tổng và API báo cáo doanh thu chi nhánh |

Các API Manager đang được Blade/JS dùng:

| Method | Path | Route name | Controller method |
|---|---|---|---|
| GET | `/api/dashboard/manager/overview` | `api.dashboard.manager.overview` | `Api\Manager\DashboardController@overview` |
| GET | `/api/dashboard/manager/branches/{branchId}/services/management` | `api.dashboard.manager.branches.services.management.index` | `Api\Branch\BranchServiceManagementController@index` |
| GET | `/api/dashboard/manager/branches/{branchId}/services/management/kpi` | `api.dashboard.manager.branches.services.management.kpi` | `Api\Branch\BranchServiceManagementController@kpi` |
| GET | `/api/dashboard/manager/branches/{branchId}/services` | `api.dashboard.manager.branches.services.index` | `Api\Branch\BranchServiceManagementController@services` |
| GET | `/api/dashboard/manager/branches/{branchId}/revenue-report/target-progress` | `api.dashboard.manager.branches.revenue-report.target-progress` | `Api\Branch\BranchRevenueReportController@targetProgress` |
| GET | `/api/dashboard/manager/branches/{branchId}/revenue-report/revenue-comparison` | `api.dashboard.manager.branches.revenue-report.revenue-comparison` | `Api\Branch\BranchRevenueReportController@revenueComparison` |
| GET | `/api/dashboard/manager/branches/{branchId}/revenue-report/service-mix` | `api.dashboard.manager.branches.revenue-report.service-mix` | `Api\Branch\BranchRevenueReportController@serviceMix` |
| GET | `/api/dashboard/manager/branches/{branchId}/revenue-report/employee-performance` | `api.dashboard.manager.branches.revenue-report.employee-performance` | `Api\Branch\BranchRevenueReportController@employeePerformance` |
| GET | `/api/dashboard/manager/branches/{branchId}/revenue-report/customer-retention` | `api.dashboard.manager.branches.revenue-report.customer-retention` | `Api\Branch\BranchRevenueReportController@customerRetention` |
| GET | `/api/dashboard/manager/branches/{branchId}/inventory/materials/kpi` | `api.dashboard.manager.branches.inventory.materials.kpi` | `Api\Branch\BranchInventoryMaterialController@kpi` |
| GET | `/api/dashboard/manager/branches/{branchId}/inventory/materials/list` | `api.dashboard.manager.branches.inventory.materials.list` | `Api\Branch\BranchInventoryMaterialController@materials` |

Route Manager có thêm các route ghi dữ liệu, không phải luồng đọc của `DashboardEngine`:

| Method | File | Mục đích |
|---|---|---|
| PATCH | `routes/api/dashboard/manager/service.php` | Cập nhật visibility/lock/price override dịch vụ chi nhánh |
| POST/PUT/PATCH | `routes/api/dashboard/manager/inventory.php` | Tạo/cập nhật/tạm dừng/khôi phục vật tư |

## 7. Route con được include như thế nào

```text
routes/api/api.php
└── routes/api/dashboard/index.php
    ├── routes/api/dashboard/ceo/index.php
    │   ├── dashboard.php
    │   ├── branch.php
    │   ├── finance.php
    │   ├── service.php
    │   └── vendor.php
    └── routes/api/dashboard/manager/index.php
        ├── dashboard.php
        ├── service.php
        ├── inventory.php
        └── report.php

routes/web/web.php
├── routes/web/manager/index.php
└── routes/web/ceo/index.php
```

Ghi chú:

- `routes/web/ceo/dashboard.php` và `routes/web/manager/dashboard.php` tồn tại nhưng hiện rỗng; `routes/web/ceo/index.php` và `routes/web/manager/index.php` khai báo route trực tiếp, không require hai file rỗng này.
- `routes/api/dashboard/index.php` dùng middleware `web`, `auth`.
- `routes/api/dashboard/ceo/index.php` dùng middleware `role:ceo`.
- `routes/api/dashboard/manager/index.php` tự nhóm prefix/name; các file con tự gắn middleware theo route.

## 8. Controller Dashboard CEO đang dùng

| Vai trò | File | Ghi chú |
|---|---|---|
| Web page CEO dashboard | `app/Http/Controllers/Web/Ceo/DashboardController.php` | `index()` trả view `pages.ceo.dashboard-overview` |
| API CEO overview | `app/Http/Controllers/Api/Ceo/DashboardController.php` | Dùng `DateRangeFilterRequest`, gọi `CeoDashboardRepositoryInterface`, trả qua private `respondData()` |
| API CEO branch page | `app/Http/Controllers/Api/Ceo/BranchController.php` | Gọi `BranchNetworkRepositoryInterface`, trả JSON trực tiếp |
| API CEO finance page | `app/Http/Controllers/Api/Ceo/FinanceController.php` | Gọi `CeoFinanceRepositoryInterface`, trả JSON trực tiếp |
| API CEO service page | `app/Http/Controllers/Api/Ceo/ServiceController.php` | Gọi `ServiceRevenueRepositoryInterface`, trả JSON trực tiếp |
| API CEO service analytics | `app/Http/Controllers/Api/Ceo/ServiceAnalyticsController.php` | Route analytics trong `routes/api/dashboard/ceo/service.php` |
| API CEO vendor page | `app/Http/Controllers/Api/Ceo/VendorController.php` | Gọi `CeoVendorRepositoryInterface`, trả JSON trực tiếp |

## 9. Controller Dashboard Manager đang dùng

| Vai trò | File | Ghi chú |
|---|---|---|
| Web page Manager dashboard | `app/Http/Controllers/Web/Manager/DashboardController.php` | `index()` trả view `pages.manager.branch-dashboard` |
| API Manager overview | `app/Http/Controllers/Api/Manager/DashboardController.php` | Dùng `DateRangeFilterRequest`, gọi `ManagerDashboardRepositoryInterface`, trả JSON trực tiếp |
| API Manager service tổng | `app/Http/Controllers/Api/Manager/ServiceController.php` | Dùng model `Service`, không qua repository |
| API Manager inventory tổng | `app/Http/Controllers/Api/Manager/InventoryController.php` | Dùng model `BranchInventory`, không qua repository |
| API Manager revenue tổng | `app/Http/Controllers/Api/Manager/ReportController.php` | Dùng model `Payment`, `Order`, không qua repository |
| API dịch vụ chi nhánh | `app/Http/Controllers/Api/Branch/BranchServiceManagementController.php` | Gọi `BranchServiceManagementRepositoryInterface` |
| API báo cáo doanh thu chi nhánh | `app/Http/Controllers/Api/Branch/BranchRevenueReportController.php` | Gọi `BranchRevenueReportRepositoryInterface` |
| API vật tư chi nhánh | `app/Http/Controllers/Api/Branch/BranchInventoryMaterialController.php` | Gọi `BranchInventoryMaterialRepositoryInterface` |

## 10. Repository CEO đang dùng

| Interface | Implementation | Được bind ở | Dùng bởi |
|---|---|---|---|
| `CeoDashboardRepositoryInterface` | `app/Repositories/Eloquent/Ceo/CeoDashboardRepository.php` | `app/Providers/AppServiceProvider.php` | `Api\Ceo\DashboardController` |
| `BranchNetworkRepositoryInterface` | `app/Repositories/Eloquent/Ceo/BranchNetworkRepository.php` | `app/Providers/AppServiceProvider.php` | `Api\Ceo\BranchController` |
| `CeoFinanceRepositoryInterface` | `app/Repositories/Eloquent/Ceo/CeoFinanceRepository.php` | `app/Providers/AppServiceProvider.php` | `Api\Ceo\FinanceController` |
| `ServiceRevenueRepositoryInterface` | `app/Repositories/Eloquent/Ceo/ServiceRevenueRepository.php` | `app/Providers/AppServiceProvider.php` | `Api\Ceo\ServiceController`, `Api\Ceo\ServiceAnalyticsController` |
| `CeoVendorRepositoryInterface` | `app/Repositories/Eloquent/Ceo/CeoVendorRepository.php` | `app/Providers/AppServiceProvider.php` | `Api\Ceo\VendorController` |

Repository chính cho CEO overview:

- `app/Repositories/Eloquent/Ceo/CeoDashboardRepository.php`
- Interface: `app/Repositories/Contracts/Ceo/CeoDashboardRepositoryInterface.php`
- Các method đang được route overview dùng: `getCurrentHotelOccupancy`, `getOccupancyRate`, `getRevpar`, `getCustomerTrend`, `getTotalRevenue`, `getEstimatedCogs`, `getRevenueMix`, `getRevenueAndCogsTrend`, `getBranchRevenue`, `getBranchRanking`, `getTopUsedServices`, `getRiskAlerts`.

## 11. Repository Manager đang dùng

| Interface | Implementation | Được bind ở | Dùng bởi |
|---|---|---|---|
| `ManagerDashboardRepositoryInterface` | `app/Repositories/Eloquent/Manager/ManagerDashboardRepository.php` | `app/Providers/AppServiceProvider.php` | `Api\Manager\DashboardController` |
| `BranchServiceManagementRepositoryInterface` | `app/Repositories/Eloquent/Branch/BranchServiceManagementRepository.php` | `app/Providers/AppServiceProvider.php` | `Api\Branch\BranchServiceManagementController` |
| `BranchRevenueReportRepositoryInterface` | `app/Repositories/Eloquent/Branch/BranchRevenueReportRepository.php` | `app/Providers/AppServiceProvider.php` | `Api\Branch\BranchRevenueReportController` |
| `BranchInventoryMaterialRepositoryInterface` | `app/Repositories/Eloquent/Branch/BranchInventoryMaterialRepository.php` | `app/Providers/AppServiceProvider.php` | `Api\Branch\BranchInventoryMaterialController` |

Ghi chú:

- `ManagerDashboardRepository::getOverview()` hiện trả cấu trúc rỗng/TODO cho dashboard overview.
- Các trang Manager chi tiết hiện dùng repository theo namespace `Branch`, vì route là dữ liệu chi nhánh theo `{branchId}`.
- `Api\Manager\ServiceController`, `InventoryController`, `ReportController` hiện dùng model trực tiếp cho route tổng, không qua repository.

## 12. DateRangeFilterRequest

| Nội dung | File |
|---|---|
| Request filter ngày dùng chung | `app/Http/Requests/Shared/DateRangeFilterRequest.php` |

Method quan trọng:

- `rules()` validate `start_date`, `end_date`.
- `getStartDate()` trả `Carbon|null`.
- `getEndDate()` trả `Carbon|null`.
- `getPreviousStartDate()` tính kỳ trước nếu có đủ start/end.
- `getPreviousEndDate()` bằng `start_date - 1 ngày`.
- `getFiltersArray()` trả 4 key: `start_date`, `end_date`, `prev_start_date`, `prev_end_date`.

Ghi chú:

- Frontend chỉ cần gửi `start_date` và `end_date`.
- Không tính `prev_start_date`/`prev_end_date` trong JS.
- Khi repository cần bind Oracle, nên normalize về chuỗi `YYYY-MM-DD` trước khi đưa vào SQL.

## 13. Base Controller hoặc trait có respondData()

Search hiện tại chỉ thấy:

| File | Tình trạng |
|---|---|
| `app/Http/Controllers/Api/Ceo/DashboardController.php` | Có private `respondData(mixed $data): JsonResponse` |
| `app/Http/Controllers/Api/ApiController.php` | Không có `respondData()`, chỉ có helper validate time filter |
| `app/Http/Controllers/Controller.php` | Không có method |

Kết luận: hiện chưa có base `respondData()` dùng chung. Nếu task sau yêu cầu dùng `respondData()`, cần kiểm tra lại trước khi sửa vì hiện chỉ CEO dashboard overview có private helper này.

## 14. Các Blade chính của CEO Dashboard

| Trang | Blade | Web route | Root id | JS |
|---|---|---|---|---|
| Tổng quan hoạt động | `resources/views/pages/ceo/dashboard-overview.blade.php` | `ceo.dashboard` | `ceoDashboard` | `public/assets/client/js/ceo/dashboard-overview.js` |
| Chi nhánh | `resources/views/pages/ceo/branch-management.blade.php` | `ceo.branches` | `ceoBranchPage` | `public/assets/client/js/ceo/branch-management.js` |
| Dịch vụ | `resources/views/pages/ceo/service-management.blade.php` | `ceo.service` | `ceoServicePage` | `public/assets/client/js/ceo/service-management.js` |
| Tài chính | `resources/views/pages/ceo/finance-analytics.blade.php` | `ceo.finance` | `ceoFinancePage` | `public/assets/client/js/ceo/finance-analytics.js` |
| Đối tác/nhà cung cấp | `resources/views/pages/ceo/partner-vendor-management.blade.php` | `ceo.vendors` | `ceoVendorPage` | `public/assets/client/js/ceo/partner-vendor-management.js` |

## 15. Các Blade chính của Manager Dashboard

| Trang | Blade | Web route | Root id | JS |
|---|---|---|---|---|
| Dashboard chi nhánh | `resources/views/pages/manager/branch-dashboard.blade.php` | `manager.dashboard` | `managerBranchDashboard` | `public/assets/client/js/manager/branch-dashboard.js` |
| Báo cáo doanh thu chi nhánh | `resources/views/pages/manager/branch-revenue-reports.blade.php` | `manager.reports` | `managerRevenueReportPage` | `public/assets/client/js/manager/branch-revenue-reports.js` |
| Quản lý dịch vụ chi nhánh | `resources/views/pages/manager/branch-service-management.blade.php` | `manager.service` | `managerBranchServicePage` | `public/assets/client/js/manager/branch-service-management.js` |
| Quản trị vật tư | `resources/views/pages/manager/inventory-management.blade.php` | `manager.inventory` | `managerInventoryPage` | `public/assets/client/js/manager/inventory-management.js` |

Ghi chú Manager:

- Các Blade Manager chi tiết lấy `$managerBranchId = auth()->user()?->employee?->branch_id ?? 1`.
- Các route API chi nhánh truyền `['branchId' => $managerBranchId]`.

## 16. Các file JS riêng của từng trang Dashboard

| Nhóm | File | Cách gọi engine |
|---|---|---|
| Core | `public/assets/client/js/core/dashboard-engine.js` | Định nghĩa `window.DashboardEngine` |
| CEO overview | `public/assets/client/js/ceo/dashboard-overview.js` | `DashboardEngine.run([...])` với 12 API |
| CEO branch | `public/assets/client/js/ceo/branch-management.js` | `DashboardEngine.run([...])` với active/highest/lowest/list |
| CEO service | `public/assets/client/js/ceo/service-management.js` | `DashboardEngine.run([...])` với summary/highest/lowest/catalog |
| CEO finance | `public/assets/client/js/ceo/finance-analytics.js` | Tạo `financeApis`, rồi `DashboardEngine.run(financeApis)` |
| CEO vendor | `public/assets/client/js/ceo/partner-vendor-management.js` | `DashboardEngine.run([...])` với vendors |
| Manager overview | `public/assets/client/js/manager/branch-dashboard.js` | `DashboardEngine.run([...])` với overview |
| Manager revenue report | `public/assets/client/js/manager/branch-revenue-reports.js` | `DashboardEngine.run([...])` với 5 API |
| Manager branch service | `public/assets/client/js/manager/branch-service-management.js` | `DashboardEngine.run([...])` với KPI/list |
| Manager inventory | `public/assets/client/js/manager/inventory-management.js` | `DashboardEngine.run([...])` với KPI/materials |

Search hiện tại trong JS riêng CEO/Manager không thấy `fetch()`, bind `.js-apply-filter`, hoặc tự tạo `URLSearchParams`; phần đó đang nằm trong `DashboardEngine`.

## 17. Quy ước đặt data-url trong Blade

Quy ước hiện tại:

```blade
<div
    id="ceoDashboard"
    data-revpar-url="{{ route('api.dashboard.ceo.revpar') }}"
>
```

Sau đó JS đọc bằng:

```js
const root = document.getElementById("ceoDashboard");
DashboardEngine.run([
    { url: root.dataset.revparUrl, onSuccess: renderRevpar },
]);
```

Bảng data-url chính:

| Blade | Root id | Data URL đang dùng |
|---|---|---|
| `pages/ceo/dashboard-overview.blade.php` | `ceoDashboard` | `data-current-occupancy-url`, `data-occupancy-rate-url`, `data-revpar-url`, `data-customer-trend-url`, `data-total-revenue-url`, `data-estimated-cogs-url`, `data-revenue-mix-url`, `data-revenue-cogs-trend-url`, `data-branch-revenue-url`, `data-branch-ranking-url`, `data-top-used-services-url`, `data-risk-alerts-url` |
| `pages/ceo/branch-management.blade.php` | `ceoBranchPage` | `data-active-count-url`, `data-highest-revenue-url`, `data-lowest-occupancy-url`, `data-branches-url` |
| `pages/ceo/service-management.blade.php` | `ceoServicePage` | `data-summary-url`, `data-highest-revenue-url`, `data-lowest-revenue-url`, `data-catalog-url` |
| `pages/ceo/finance-analytics.blade.php` | `ceoFinancePage` | `data-finance-url`, `data-estimated-cost-url`, `data-estimated-profit-url`, `data-estimated-margin-url`, `data-finance-trend-url`, `data-finance-monthly-trend-url` |
| `pages/ceo/partner-vendor-management.blade.php` | `ceoVendorPage` | `data-vendors-url` |
| `pages/manager/branch-dashboard.blade.php` | `managerBranchDashboard` | `data-overview-url` |
| `pages/manager/branch-revenue-reports.blade.php` | `managerRevenueReportPage` | `data-target-progress-url`, `data-revenue-comparison-url`, `data-service-mix-url`, `data-employee-performance-url`, `data-customer-retention-url` |
| `pages/manager/branch-service-management.blade.php` | `managerBranchServicePage` | `data-overview-url`, `data-kpi-url`, `data-services-url` |
| `pages/manager/inventory-management.blade.php` | `managerInventoryPage` | `data-kpi-url`, `data-materials-url` |

Quy ước cần giữ:

- URL API đặt ở root container của page.
- Dùng `route('api.dashboard...')`, không hard-code URL trong JS.
- `data-abc-url` sẽ thành `root.dataset.abcUrl`.
- Nếu route cần `branchId`, truyền từ Blade bằng biến branch hiện tại.

## 18. Quy ước gọi DashboardEngine.run([...])

Mẫu đang dùng:

```js
(function ($) {
    const root = document.getElementById("pageRootId");

    if (!root || !window.DashboardEngine) {
        return;
    }

    function renderSomething(data) {
        // render DOM/Chart/KPI
    }

    $(document).ready(function () {
        DashboardEngine.run([
            { url: root.dataset.somethingUrl, onSuccess: renderSomething },
        ]);
    });
})(window.jQuery);
```

Quy ước cần giữ:

- JS trang chỉ định nghĩa render/helper và gọi `DashboardEngine.run`.
- Không tự gọi `fetch()` trong JS trang.
- Không bind `.js-apply-filter` trong JS trang.
- Không tự tạo query string.
- Không tự trigger click.
- Không tính kỳ trước trong JS.
- `onBefore`/`onError` có thể dùng khi cần trạng thái loading/lỗi, như `finance-analytics.js`.

## 19. Những file quan trọng không được phá

| Nhóm | File |
|---|---|
| Context luật dashboard | `docs/CODEX_DASHBOARD_CONTEXT.md` |
| File map này | `docs/DASHBOARD_FILE_MAP.md` |
| Engine | `public/assets/client/js/core/dashboard-engine.js` |
| Control panel | `resources/views/components/global-control-panel.blade.php` |
| Layout load engine | `resources/views/layouts/ceo.blade.php`, `resources/views/layouts/manager.blade.php` |
| API route root | `routes/api/api.php`, `routes/api/dashboard/index.php` |
| API route CEO | `routes/api/dashboard/ceo/index.php`, `dashboard.php`, `branch.php`, `finance.php`, `service.php`, `vendor.php` |
| API route Manager | `routes/api/dashboard/manager/index.php`, `dashboard.php`, `service.php`, `inventory.php`, `report.php` |
| Web route role | `routes/web/web.php`, `routes/web/ceo/index.php`, `routes/web/manager/index.php` |
| Date filter | `app/Http/Requests/Shared/DateRangeFilterRequest.php` |
| API controller CEO/Manager dashboard | `app/Http/Controllers/Api/Ceo/DashboardController.php`, `app/Http/Controllers/Api/Manager/DashboardController.php` |
| Repository chính | `app/Repositories/Eloquent/Ceo/CeoDashboardRepository.php`, `app/Repositories/Eloquent/Manager/ManagerDashboardRepository.php` |
| Interface/binding | `app/Repositories/Contracts/**`, `app/Providers/AppServiceProvider.php` |
| Blade CEO/Manager pages | `resources/views/pages/ceo/*.blade.php`, `resources/views/pages/manager/*.blade.php` |
| JS CEO/Manager pages | `public/assets/client/js/ceo/*.js`, `public/assets/client/js/manager/*.js` |

## 20. Ghi chú cho Codex khi làm chức năng Dashboard mới

Checklist trước khi sửa:

- Đọc lại `docs/CODEX_DASHBOARD_CONTEXT.md`.
- Đọc file map này để xác định đúng route/controller/repository/Blade/JS.
- Search lại nếu chưa chắc: `DashboardEngine`, `dashboard`, `ceo`, `manager`, `DateRangeFilterRequest`, `respondData`, `CeoDashboardRepository`, `Route::get`, `Route::post`.

Khi thêm một API dashboard mới:

1. Chọn đúng route file con theo page hiện tại, ví dụ CEO overview vào `routes/api/dashboard/ceo/dashboard.php`.
2. Vì `DashboardEngine` dùng GET, route đọc dữ liệu nên là `Route::get`.
3. Controller chỉ nhận `DateRangeFilterRequest`, gọi `$request->getFiltersArray()`, gọi repository, trả JSON chuẩn `{ success: true, data: ... }`.
4. SQL/Oracle/bind/tính toán đặt trong repository.
5. Nếu tạo repository/interface mới, bind ở `app/Providers/AppServiceProvider.php`.
6. Blade chỉ thêm `data-*-url` và selector render cần thiết.
7. JS chỉ thêm render function và entry trong `DashboardEngine.run([...])`.
8. Không hard-code URL, ngày, dữ liệu mẫu, hoặc kỳ trước ở frontend.

Những điểm cần kiểm tra lại khi task sau đụng vào:

- `respondData()` chưa global, nên không giả định controller nào cũng có.
- `DateRangeFilterRequest::getFiltersArray()` trả Carbon/null; repository nên normalize trước khi bind SQL.
- `ManagerDashboardRepository` overview còn TODO; nếu làm dashboard Manager overview thật, cần bổ sung logic ở repo này.
- Một số route Manager tổng (`/services`, `/inventory`, `/revenue`) dùng model trực tiếp và hiện không phải route chính của các Blade manager chi tiết.
