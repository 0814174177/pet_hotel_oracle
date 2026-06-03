# PROMPT_GENERATE_MANAGER_DASHBOARD_FILE_MAP.md

Cập nhật: 2026-06-02

## 1. Mục đích

File này là prompt dùng để yêu cầu Codex tự đọc repo hiện tại và tạo/cập nhật file map cho Dashboard Manager.

Mục tiêu là tạo file:

```text
docs/MANAGER_DASHBOARD_FILE_MAP.md
```

File map này phải mô tả chính xác các file, route, controller, repository, Blade, JavaScript, request, layout và engine đang phục vụ Dashboard Manager trong repo hiện tại.

Không được đoán theo trí nhớ. Phải search và đọc repo thật.

---

## 2. Prompt gửi cho Codex

```text
Bạn hãy đọc kỹ yêu cầu dưới đây và thực hiện trực tiếp trong repo hiện tại.

Nhiệm vụ:
Tạo hoặc cập nhật file map cho Dashboard Manager.

File cần tạo/cập nhật:
docs/MANAGER_DASHBOARD_FILE_MAP.md

Mục tiêu:
Tôi đang chuyển trọng tâm từ Dashboard CEO sang Dashboard Manager. Tôi cần một file map riêng cho Manager để mỗi lần triển khai chức năng mới, Codex có thể xác định đúng:
- Blade page
- JS page
- DashboardEngine
- route API
- route web
- controller API
- controller web
- request filter ngày
- repository/interface
- provider binding
- root container id
- data-url
- route name
- API path
- branchId
- những file không được phá

Yêu cầu quan trọng:
- Phải đọc repo hiện tại trước khi viết file map.
- Không được chỉ mô tả chung chung.
- Không được copy nguyên file map CEO nếu không đúng Manager.
- Không được tự bịa đường dẫn.
- Không được tạo kiến trúc mới.
- Không sửa code chức năng nếu nhiệm vụ chỉ là viết file map.
- Chỉ tạo/cập nhật file docs/MANAGER_DASHBOARD_FILE_MAP.md.
- Nếu phát hiện file map cũ sai hoặc thiếu, hãy cập nhật cho đúng theo repo thật.

Các khu vực cần search:
- routes/api/dashboard/manager
- routes/web/manager
- app/Http/Controllers/Api/Manager
- app/Http/Controllers/Api/Branch
- app/Http/Controllers/Web/Manager
- app/Repositories/Eloquent/Manager
- app/Repositories/Eloquent/Branch
- app/Repositories/Contracts/Manager
- app/Repositories/Contracts/Branch
- app/Providers/AppServiceProvider.php
- app/Http/Requests/Shared/DateRangeFilterRequest.php
- resources/views/layouts/manager.blade.php
- resources/views/pages/manager
- public/assets/client/js/manager
- public/assets/client/js/core/dashboard-engine.js
- resources/views/components/global-control-panel.blade.php

Từ khóa nên search thêm:
- DashboardEngine
- managerBranchDashboard
- managerRevenueReportPage
- managerBranchServicePage
- managerInventoryPage
- data-overview-url
- data-target-progress-url
- data-revenue-comparison-url
- data-service-mix-url
- data-employee-performance-url
- data-customer-retention-url
- data-kpi-url
- data-materials-url
- branchId
- DateRangeFilterRequest
- getFiltersArray
- response()->json
- respondData
- Route::get
- Route::post
- Route::patch
- BranchRevenueReportController
- BranchServiceManagementController
- BranchInventoryMaterialController
- ManagerDashboardRepository

Nội dung file docs/MANAGER_DASHBOARD_FILE_MAP.md cần có đầy đủ các phần sau:

# Manager Dashboard File Map

## 1. Tổng quan luồng Dashboard Manager hiện tại

Mô tả luồng thực tế trong repo:

Blade page
-> root container gắn data-*-url bằng route(...)
-> JS riêng của trang đọc root.dataset.*
-> DashboardEngine.run([...])
-> DashboardEngine đọc .js-start-date, .js-end-date, .js-apply-filter
-> fetch GET từng API với query string start_date/end_date
-> routes/api/dashboard/manager/*
-> Controller API Manager hoặc Branch
-> DateRangeFilterRequest
-> Repository hoặc model/query
-> JSON { success: true, data: ... }
-> JS render vào DOM/Chart/KPI/Table

Ghi chú rõ:
- DashboardEngine có dùng GET không?
- DashboardEngine có tự bind .js-apply-filter không?
- DashboardEngine có tự trigger lần đầu không?
- DateRangeFilterRequest trả những key nào?
- respondData có global chưa hay chỉ nằm ở controller cụ thể?
- Manager có dùng branchId không?

## 2. File DashboardEngine và filter chung

Tạo bảng:

| Nội dung | File | Ghi chú |
|---|---|---|

Bắt buộc kiểm tra:
- public/assets/client/js/core/dashboard-engine.js
- resources/views/components/global-control-panel.blade.php
- resources/views/layouts/manager.blade.php
- public/assets/client/js/components/global-control-panel.js nếu có

Ghi rõ:
- Engine được load ở đâu.
- Control panel chứa selector nào.
- JS trang có được tự fetch không.

## 3. Route web Manager

Tạo bảng:

| Web path | Route name | Controller | View | Ghi chú |
|---|---|---|---|---|

Bắt buộc đọc:
- routes/web/web.php
- routes/web/manager/index.php
- app/Http/Controllers/Web/Manager/*

Ghi rõ các trang:
- /manager hoặc /manager/dashboard
- /manager/services
- /manager/reports
- /manager/inventory

Nếu có file route rỗng hoặc không được include, ghi chú rõ.

## 4. Route API Manager tổng quan

Tạo bảng:

| File route | Prefix/name | Middleware | Include file con | Ghi chú |
|---|---|---|---|---|

Bắt buộc đọc:
- routes/api/api.php
- routes/api/dashboard/index.php
- routes/api/dashboard/manager/index.php

Ghi rõ:
- API dashboard bắt đầu bằng path nào.
- Middleware nào đang dùng.
- File con nào được include.

## 5. Route API Manager theo từng module

Chia thành các mục:

### 5.1. Dashboard overview

Tạo bảng:

| Method | Path | Route name | Controller method | Có dùng branchId không | JS/Blade đang gọi không |
|---|---|---|---|---|---|

Đọc file:
- routes/api/dashboard/manager/dashboard.php

### 5.2. Revenue report

Đọc file:
- routes/api/dashboard/manager/report.php

Tạo bảng tương tự, chú ý route dạng:
- /branches/{branchId}/revenue-report/...

### 5.3. Service management

Đọc file:
- routes/api/dashboard/manager/service.php

Tạo bảng riêng cho:
- API đọc dữ liệu GET dùng DashboardEngine
- API ghi dữ liệu PATCH/POST/PUT nếu có

### 5.4. Inventory management

Đọc file:
- routes/api/dashboard/manager/inventory.php

Tạo bảng riêng cho:
- API đọc dữ liệu GET dùng DashboardEngine
- API ghi dữ liệu POST/PUT/PATCH nếu có

## 6. Controller Manager và Branch đang dùng

Tạo bảng:

| Vai trò | File | Method chính | Repository/model gọi | Ghi chú |
|---|---|---|---|---|

Bắt buộc kiểm tra:
- app/Http/Controllers/Api/Manager/DashboardController.php
- app/Http/Controllers/Api/Manager/ServiceController.php
- app/Http/Controllers/Api/Manager/InventoryController.php
- app/Http/Controllers/Api/Manager/ReportController.php
- app/Http/Controllers/Api/Branch/BranchServiceManagementController.php
- app/Http/Controllers/Api/Branch/BranchRevenueReportController.php
- app/Http/Controllers/Api/Branch/BranchInventoryMaterialController.php
- app/Http/Controllers/Web/Manager/DashboardController.php

Ghi rõ:
- Controller nào dùng Repository.
- Controller nào dùng model trực tiếp.
- Controller nào dùng DateRangeFilterRequest.
- Controller nào nhận branchId.
- Controller nào có helper respondData.
- Controller nào trả response()->json trực tiếp.

## 7. Repository và Interface Manager/Branch

Tạo bảng:

| Interface | Implementation | Được bind ở đâu | Dùng bởi Controller nào | Ghi chú |
|---|---|---|---|---|

Bắt buộc kiểm tra:
- app/Repositories/Contracts/Manager
- app/Repositories/Eloquent/Manager
- app/Repositories/Contracts/Branch
- app/Repositories/Eloquent/Branch
- app/Providers/AppServiceProvider.php

Ghi rõ:
- Method hiện có trong từng interface.
- Method hiện có trong implementation.
- Repository nào còn TODO/rỗng.
- Repository nào có normalizeFilters.
- Repository nào nhận branchId.
- Repository nào xử lý SQL Oracle.

## 8. DateRangeFilterRequest

Tạo bảng:

| Method | Vai trò | Output |
|---|---|---|

Đọc file:
- app/Http/Requests/Shared/DateRangeFilterRequest.php

Ghi rõ:
- rules validate gì.
- getFiltersArray trả những key nào.
- start_date/end_date là kiểu gì.
- prev_start_date/prev_end_date tính như thế nào.
- Khi bind Oracle thì cần normalize ra chuỗi YYYY-MM-DD.

## 9. Blade Manager pages

Tạo bảng:

| Trang | Blade file | Web route | Root id | Data-url hiện có | BranchId lấy ở đâu | JS file |
|---|---|---|---|---|---|---|

Bắt buộc đọc:
- resources/views/pages/manager/branch-dashboard.blade.php
- resources/views/pages/manager/branch-revenue-reports.blade.php
- resources/views/pages/manager/branch-service-management.blade.php
- resources/views/pages/manager/inventory-management.blade.php

Ghi rõ:
- Root container id.
- Các data-url đang có.
- data-url nào gọi route có branchId.
- `$managerBranchId` đang lấy như thế nào.
- Selector KPI/chart/table quan trọng đang có.
- Có dùng global-control-panel không.

## 10. JavaScript Manager pages

Tạo bảng:

| Trang | JS file | Root id đọc trong JS | DashboardEngine.run configs | Render function chính | Có fetch riêng không | Ghi chú |
|---|---|---|---|---|---|---|

Bắt buộc đọc:
- public/assets/client/js/manager/branch-dashboard.js
- public/assets/client/js/manager/branch-revenue-reports.js
- public/assets/client/js/manager/branch-service-management.js
- public/assets/client/js/manager/inventory-management.js

Ghi rõ:
- JS có dùng DashboardEngine.run không.
- JS có fetch riêng không.
- JS có bind .js-apply-filter không.
- JS đọc root.dataset key nào.
- Render function nào đang dùng.
- Chart/table/card nào được render.
- API nào trong DashboardEngine.run tương ứng data-url nào.

## 11. Quy ước data-url và route name Manager

Tạo bảng:

| Blade root | data-* attribute | JS dataset key | Route name | API path | Có branchId không |
|---|---|---|---|---|---|

Ví dụ cần xác minh từ repo thật:
- data-overview-url -> root.dataset.overviewUrl
- data-target-progress-url -> root.dataset.targetProgressUrl
- data-revenue-comparison-url -> root.dataset.revenueComparisonUrl
- data-service-mix-url -> root.dataset.serviceMixUrl
- data-employee-performance-url -> root.dataset.employeePerformanceUrl
- data-customer-retention-url -> root.dataset.customerRetentionUrl
- data-kpi-url -> root.dataset.kpiUrl
- data-materials-url -> root.dataset.materialsUrl

Không được đoán. Phải ghi đúng theo Blade và JS thật.

## 12. Những file quan trọng không được phá

Tạo bảng:

| Nhóm | File | Lý do không được phá |
|---|---|---|

Ít nhất gồm:
- docs/CODEX_MANAGER_DASHBOARD_CONTEXT.md
- docs/MANAGER_DASHBOARD_FILE_MAP.md
- public/assets/client/js/core/dashboard-engine.js
- resources/views/components/global-control-panel.blade.php
- resources/views/layouts/manager.blade.php
- routes/api/api.php
- routes/api/dashboard/index.php
- routes/api/dashboard/manager/index.php
- routes/api/dashboard/manager/dashboard.php
- routes/api/dashboard/manager/service.php
- routes/api/dashboard/manager/inventory.php
- routes/api/dashboard/manager/report.php
- routes/web/web.php
- routes/web/manager/index.php
- app/Http/Requests/Shared/DateRangeFilterRequest.php
- app/Http/Controllers/Api/Manager/*
- app/Http/Controllers/Api/Branch/*
- app/Repositories/Eloquent/Manager/*
- app/Repositories/Eloquent/Branch/*
- app/Repositories/Contracts/Manager/*
- app/Repositories/Contracts/Branch/*
- resources/views/pages/manager/*.blade.php
- public/assets/client/js/manager/*.js

## 13. Quy tắc khi thêm chức năng Dashboard Manager mới

Viết checklist rõ:

- Đọc docs/CODEX_MANAGER_DASHBOARD_CONTEXT.md.
- Đọc docs/MANAGER_DASHBOARD_FILE_MAP.md.
- Xác định đúng trang Manager.
- Xác định đúng route file.
- Nếu dữ liệu thuộc chi nhánh, bắt buộc dùng branchId.
- Route đọc dữ liệu dùng GET.
- Controller nhận DateRangeFilterRequest và branchId nếu có.
- Repository xử lý SQL Oracle.
- Blade chỉ thêm data-url/selector.
- JS chỉ render và gọi DashboardEngine.run.
- Không fetch riêng.
- Không bind .js-apply-filter.
- Không hard-code URL/ngày/dữ liệu mẫu.
- Không tạo kiến trúc mới.

## 14. Những điểm cần cảnh báo nếu phát hiện

Nếu trong repo có những điểm sau, phải ghi vào file map:

- respondData chưa global.
- Controller Manager nào đang dùng model trực tiếp thay vì repository.
- Repository nào còn TODO/rỗng.
- Route nào là route ghi dữ liệu, không thuộc DashboardEngine.
- File route nào tồn tại nhưng không được include.
- JS nào có fetch riêng nếu phát hiện.
- Blade nào thiếu root container hoặc data-url.
- Route nào cần branchId nhưng Blade chưa truyền branchId.
- Tên route nào có nguy cơ trùng.
- API nào đang trả JSON khác format `{ success: true, data: ... }`.

## 15. Format kết quả sau khi làm

Sau khi tạo/cập nhật file docs/MANAGER_DASHBOARD_FILE_MAP.md, hãy trả lời:

1. File đã tạo/cập nhật.
2. Các nhóm file đã scan.
3. Các route Manager chính đã map.
4. Các Blade Manager chính đã map.
5. Các JS Manager chính đã map.
6. Các Controller/Repository đã map.
7. Các điểm đặc biệt phát hiện trong repo.
8. Những phần chưa kiểm tra được nếu có.

Bắt đầu thực hiện:
Hãy đọc repo hiện tại trước, sau đó tạo/cập nhật `docs/MANAGER_DASHBOARD_FILE_MAP.md`. Không chỉ mô tả. Không sửa chức năng Dashboard trong bước này.
```

---

## 3. Gợi ý cách dùng

Sau khi copy file này vào repo, gửi cho Codex câu ngắn:

```text
Hãy đọc file `PROMPT_GENERATE_MANAGER_DASHBOARD_FILE_MAP.md` và thực hiện đúng nội dung trong đó. Chỉ tạo/cập nhật `docs/MANAGER_DASHBOARD_FILE_MAP.md`, không sửa chức năng khác.
```

---

## 4. Tên file map khuyến nghị

Nên dùng tên riêng cho Manager:

```text
docs/MANAGER_DASHBOARD_FILE_MAP.md
```

Không nên ghi đè `docs/DASHBOARD_FILE_MAP.md` nếu file đó đang dùng chung CEO/Manager, trừ khi bạn muốn hợp nhất toàn bộ map vào một file duy nhất.
