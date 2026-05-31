CODEX DASHBOARD CONTEXT

1. Kiến trúc Dashboard bắt buộc

Luồng xử lý Dashboard hiện tại:

Blade/View đã có sẵn
→ JS cấu hình DashboardEngine
→ DashboardEngine tự gọi API
→ Router API
→ Controller
→ DateRangeFilterRequest
→ Repository xử lý SQL
→ Repository trả JSON data
→ Controller respondData()
→ JS render dữ liệu động lên UI

Luật cố định:

Không tạo kiến trúc mới.
Không thiết kế lại giao diện từ đầu.
Giao diện đã có sẵn, chỉ làm cho dữ liệu thành động.
Chỉ bổ sung id, class, data-url, data-\* nếu cần.
Repository là nơi xử lý SQL, logic tính toán và cấu trúc dữ liệu trả về.
Controller chỉ gọi Repository.
JavaScript riêng của trang chỉ khai báo cấu hình API và hàm render. 2. Luật về DashboardEngine

Hệ thống đã có:

DashboardEngine.run(apiConfigs)

DashboardEngine hiện tại đã tự xử lý:

Tìm nút .js-apply-filter
Lấy ngày từ .js-start-date
Lấy ngày từ .js-end-date
Tạo payload:
{
"start_date": "...",
"end_date": "..."
}
Kiểm tra start_date không được lớn hơn end_date
Chuyển payload thành query string
Gọi từng API bằng fetch() với method GET
Gọi config.onBefore(payload) nếu có
Gọi config.onSuccess(data.data) nếu API trả success = true
Gọi config.onError(...) nếu lỗi
Tự gỡ event cũ để tránh bind click nhiều lần
Tự trigger click lần đầu để load dashboard

Vì vậy JS riêng của từng trang:

Được làm:

DashboardEngine.run([
{
url: root.dataset.revparUrl,
onSuccess: renderRevpar,
onError: handleError,
},
]);

Không được làm:

Không viết lại logic fetch
Không gọi API thủ công bằng fetch
Không tự bind .js-apply-filter
Không tự tạo query string
Không tự gọi .click()
Không tự tính kỳ trước
Không viết lại DashboardEngine nếu không thật sự cần 3. Luật filter thời gian

DateRangeFilterRequest đã xử lý và trả về đủ 4 mốc thời gian:

[
'start_date' => ...,
'end_date' => ...,
'prev_start_date' => ...,
'prev_end_date' => ...,
]

Logic kỳ trước đã nằm trong DateRangeFilterRequest:

prev_end_date = start_date - 1 ngày
prev_start_date = prev_end_date - độ dài kỳ hiện tại + 1

Ví dụ:

Current: 2026-05-01 -> 2026-05-31
Previous: 2026-03-31 -> 2026-04-30

Luật cố định:

Frontend chỉ gửi start_date, end_date
Backend tự sinh prev_start_date, prev_end_date
Không tính kỳ trước trong JavaScript
Không tính kỳ trước trong Controller
Không hard-code kỳ trước
Không hard-code ngày

Controller chỉ lấy filter bằng:

$request->getFiltersArray() 4. Luật Controller

Controller không được xử lý SQL.

Controller không được định nghĩa lại cấu trúc JSON phức tạp.

Controller chỉ cần:

Dùng DateRangeFilterRequest
Gọi $request->getFiltersArray()
Gọi đúng method trong Repository
Trả về bằng $this->respondData(...)
Không tự tính toán dữ liệu
Không tự format JSON chi tiết nếu respondData() đã chuẩn hóa
Không đặt SQL trong Controller
Mỗi method mới phải có comment mô tả chức năng

Mẫu Controller đúng:

/\*\*

- Mô tả chức năng:
- Lấy dữ liệu dashboard theo khoảng thời gian filter.
-
- Input:
-   - DateRangeFilterRequest tự xử lý start_date, end_date
-   - Request tự sinh prev_start_date, prev_end_date
-
- Output:
-   - JSON response thông qua respondData()
      \*/
      public function methodName(DateRangeFilterRequest $request): JsonResponse
{
    return $this->respondData(
        $this->dashboardRepository->methodName($request->getFiltersArray())
      );
      }

5. Luật Repository

Repository là nơi xử lý:

SQL Oracle
Bind parameter
Normalize filter
Tính toán chỉ số
Tính so sánh kỳ hiện tại và kỳ trước
Trả về cấu trúc JSON data đúng yêu cầu của JS

Nếu là Dashboard CEO, ưu tiên đặt trong:

app/Repositories/Eloquent/Ceo/CeoDashboardRepository.php

Luật cố định:

Dùng lại normalizeFilters($filters)
Không rollback logic normalizeFilters()
Không truyền Carbon object trực tiếp vào SQL
Bind đủ 4 mốc thời gian khi SQL cần so sánh kỳ
Dùng TO_DATE(:p_start_date, 'YYYY-MM-DD')
Dùng NVL để tránh null
Tránh chia cho 0
Không trả alias SQL thô khó hiểu ra frontend
Mỗi method mới phải có comment mô tả chức năng

Mẫu bind ngày:

$filters = $this->normalizeFilters($filters);

$bindings = [
'p_start_date' => $filters['start_date'],
'p_end_date' => $filters['end_date'],
'p_prev_start_date' => $filters['prev_start_date'],
'p_prev_end_date' => $filters['prev_end_date'],
];

Trend thống nhất:

change_percent > 0 → up
change_percent < 0 → down
change_percent = 0 → neutral

Không dùng lẫn flat.

6. Luật cấu trúc data Repository trả về

Với KPI có so sánh kỳ, Repository nên trả dạng:

return [
'current' => [
'total_room_revenue' => $currentRoomRevenue,
'total_room' => $totalRoom,
'revpar' => $currentRevpar,
],
'previous' => [
'total_room_revenue' => $previousRoomRevenue,
'total_room' => $totalRoom,
'revpar' => $previousRevpar,
],
'comparison' => [
'diff_amount' => $diffAmount,
'change_percent' => $changePercent,
'trend' => $this->getTrend($changePercent),
],
];

Với chart, Repository có thể trả array:

return [
[
'report_date' => '2026-05-01',
'revenue' => 1000000,
],
[
'report_date' => '2026-05-02',
'revenue' => 1500000,
],
];

Với ranking/list, Repository có thể trả rows:

return [
[
'rank_no' => 1,
'branch_name' => 'Chi nhánh A',
'revenue_million_vnd' => 125.5,
],
];

Luật cố định:

JSON phải khớp với hàm render JS
Không trả alias SQL khó hiểu
Không trả dữ liệu thừa
Không trả raw SQL error 7. Luật API response

Controller dùng respondData(), nên Repository chỉ cần trả phần data.

Repository trả:

{
"current": {},
"previous": {},
"comparison": {}
}

API cuối cùng do respondData() bọc lại:

{
"success": true,
"data": {
"current": {},
"previous": {},
"comparison": {}
}
} 8. Luật Router API

Trước khi thêm route phải kiểm tra route hiện tại.

Nếu repo đang chia route như:

routes/api/dashboard/ceo/
routes/api/dashboard/manager/

thì giữ nguyên cấu trúc đó.

Luật cố định:

Route nằm đúng file module
Route được include đúng
Không tạo route trùng
Không phá route cũ
Method phải đúng với DashboardEngine
Vì DashboardEngine gọi API bằng GET, route nên dùng GET
Không dùng POST nếu engine hiện tại đang gọi GET

Ví dụ:

Route::get('/finance/revpar', [CeoDashboardController::class, 'revpar']); 9. Luật Blade/View

Giao diện đã có sẵn.

Không được:

Không dựng lại UI
Không phá layout
Không đổi style lớn nếu không cần
Không hard-code dữ liệu mẫu

Được làm:

Chỉ bổ sung data-url
Chỉ bổ sung selector phục vụ JS
Chỉ bổ sung id, class, data-_ nếu cần
API URL nên truyền qua data-_ ở root container

Ví dụ root container:

<div
    id="ceoDashboard"
    data-revpar-url="{{ route('api.dashboard.ceo.finance.revpar') }}"
>
</div>

Nếu card đã có sẵn thì chỉ bổ sung selector cần thiết:

<div id="revparKpi" class="kpi-card">
    <div data-kpi-value>0 đ</div>
    <div data-kpi-trend>
        <span data-kpi-arrow>=</span>
        <span data-kpi-trend-value>0%</span>
    </div>
    <div data-kpi-detail></div>
</div>
10. Luật JavaScript riêng của trang

JS riêng của trang chỉ làm 2 việc:

1. Định nghĩa hàm render
2. Gọi DashboardEngine.run([...])

Không được:

Không viết lại logic fetch
Không gọi fetch() riêng
Không bind .js-apply-filter
Không trigger click thủ công
Không tự tạo payload
Không tự tính kỳ trước
Không hard-code API URL trong JS nếu Blade đã truyền data-url

JS phải:

Dùng URL từ root.dataset
Chỉ viết render function
Có xử lý null / undefined an toàn
Có format tiền, số, phần trăm nếu cần
Có comment mô tả chức năng cho hàm render mới

Ví dụ đúng:

function renderRevpar(data) {
renderKpi(
"revparKpi",
data.current?.revpar,
data.comparison?.change_percent,
{
formatValue: formatCurrency,
}
);

    const detailNode = document.querySelector("#revparKpi [data-kpi-detail]");

    if (detailNode) {
        detailNode.textContent = `Doanh thu phòng: ${formatCurrency(data.current?.total_room_revenue)}`;
    }

}

DashboardEngine.run([
{
url: root.dataset.revparUrl,
onSuccess: renderRevpar,
},
]); 11. Luật comment mô tả hàm

Khi thêm hoặc sửa function, phải thêm mô tả chức năng ngay trong file.

Áp dụng cho:

Controller method
Repository method
JS render function
JS helper function nếu thêm mới

Mỗi mô tả cần có:

Mô tả chức năng
Input
Output
Ghi chú nếu có

Không cần viết quá dài, nhưng phải đủ để người khác mở code ra hiểu hàm đó làm gì.

Ví dụ comment JS:

/\*\*

- Mô tả chức năng:
- Render chỉ số RevPAR lên KPI card đã có sẵn.
-
- Input:
-   - data.current.revpar
-   - data.current.total_room_revenue
-   - data.comparison.change_percent
-
- Output:
-   - Cập nhật giá trị RevPAR
-   - Cập nhật phần trăm tăng/giảm
-   - Cập nhật mô tả doanh thu phòng
      \*/
      function renderRevpar(data) {
      //
      }

12. Danh sách tuyệt đối không được làm

Không được:

Viết lại DashboardEngine nếu không thật sự cần
Viết fetch() riêng trong JS trang
Bind lại nút .js-apply-filter
Tự tính prev_start_date, prev_end_date ở JS
Tự tính prev_start_date, prev_end_date ở Controller
Đưa SQL vào Controller
Hard-code dữ liệu mẫu
Hard-code ngày
Tạo route POST nếu engine đang gọi GET
Đổi kiến trúc repo hiện tại
Làm lại giao diện từ đầu
Trả alias SQL thô ra frontend 13. Checklist sau khi sửa
Route
Route đúng file chưa?
Route đã được include chưa?
Route dùng GET đúng với DashboardEngine chưa?
Route gọi đúng Controller chưa?
Controller
Có dùng DateRangeFilterRequest chưa?
Có dùng $request->getFiltersArray() chưa?
Có gọi Repository chưa?
Có trả về respondData() chưa?
Có comment mô tả function chưa?
Repository
Có gọi normalizeFilters() chưa?
Có bind đúng ngày chưa?
Có dùng TO_DATE(..., 'YYYY-MM-DD') chưa?
Có xử lý null chưa?
Có tránh chia cho 0 chưa?
Có trả data đúng cấu trúc JS cần chưa?
Có comment mô tả function chưa?
Blade
Root container có data-url đúng chưa?
Selector render có tồn tại chưa?
Không làm vỡ giao diện cũ chưa?
JavaScript
Có dùng DashboardEngine.run([...]) chưa?
Không có fetch() riêng chưa?
Không bind .js-apply-filter riêng chưa?
Hàm render nhận đúng data từ API chưa?
Dữ liệu đã render động lên UI chưa?
Có xử lý null an toàn chưa?
Có comment mô tả function chưa? 14. Luật kiểm tra PHP

Nếu không chạy được:

php -l

thì không được báo đã kiểm tra syntax PHP thành công.

Phải ghi rõ:

Chưa chạy được php -l vì máy chưa nhận lệnh php trong PATH. 15. Luật báo cáo kết quả sau khi hoàn thành

Sau khi sửa code, phải tổng hợp:

File đã sửa/thêm
Route API đã thêm/cập nhật
Controller method đã thêm/cập nhật
Repository method đã thêm/cập nhật
Cấu trúc data Repository trả về
JSON API cuối cùng sau respondData()
Blade đã gắn data-url / selector như thế nào
JS đã dùng DashboardEngine.run() như thế nào
Các hàm đã thêm mô tả chức năng
Cách test nhanh trên trình duyệt/Postman
Những phần chưa kiểm tra được, ví dụ php -l nếu máy chưa có PHP trong PATH
