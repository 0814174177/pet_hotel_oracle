Bạn hãy đọc kỹ yêu cầu dưới đây và thực hiện trực tiếp trong repo hiện tại.
Tôi đang có các câu SQL dùng để xây dựng chức năng Dashboard. Trang giao diện cho phần này đã có sẵn. Hệ thống cũng đã có sẵn DashboardEngine để tự lấy dữ liệu từ API khi người dùng bấm lọc thời gian.
Nhiệm vụ của bạn là dựa trên SQL tôi cung cấp để hoàn thiện chức năng Dashboard theo đúng kiến trúc hiện tại, không tạo kiến trúc mới.

---

1. SQL đầu vào
   DÁN SQL CỦA TÔI VÀO ĐÂY

---

2. Mục tiêu chính
   Hoàn thiện chức năng theo luồng:
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
   Trọng tâm:
   • Giao diện đã có sẵn, không thiết kế lại từ đầu.
   • Chỉ bổ sung id, class, data-url, data-\* nếu cần.
   • JS không tự viết fetch() riêng.
   • JS không tự bind lại nút lọc.
   • JS không tự tính kỳ trước.
   • JS chỉ khai báo cấu hình API và hàm render.
   • Repository là nơi xử lý SQL, logic tính toán và cấu trúc dữ liệu trả về.

---

3. Logic DashboardEngine hiện tại
   Hệ thống đã có DashboardEngine.run(apiConfigs).
   DashboardEngine hiện đang làm các việc sau:
   • Tìm nút .js-apply-filter.
   • Lấy ngày từ:
   o .js-start-date
   o .js-end-date
   • Tạo payload:
   {
   start_date: "...",
   end_date: "..."
   }
   • Kiểm tra start_date không được lớn hơn end_date.
   • Chuyển payload thành query string.
   • Gọi từng API bằng fetch() với method GET.
   • Gọi config.onBefore(payload) nếu có.
   • Gọi config.onSuccess(data.data) nếu API trả success = true.
   • Gọi config.onError(...) nếu lỗi.
   • Tự gỡ event cũ để tránh bind click nhiều lần.
   • Tự trigger click lần đầu để load dashboard.
   Vì vậy khi viết JS riêng cho trang dashboard:
   • Không viết lại logic fetch.
   • Không gọi API thủ công bằng fetch.
   • Không tự bind .js-apply-filter.
   • Không tự tạo query string.
   • Không tự gọi .click().
   • Chỉ dùng:
   DashboardEngine.run([
   {
   url: root.dataset.revparUrl,
   onSuccess: renderRevpar,
   onError: handleError,
   },
   ]);

---

Quy tắc khi thêm hoặc sửa hàm trong Controller
Khi thêm một endpoint mới trong Controller, hãy đọc kỹ chức năng của hàm đó trước khi refactor. Không được máy móc rút gọn tất cả các hàm nếu hàm đó đang cần giữ tham số cấu hình riêng.
Cần quyết định dựa trên 2 yếu tố:

1. Hàm đó có cần các giá trị cấu hình dễ chỉnh không?
   Ví dụ:
   • Ngưỡng cảnh báo lợi nhuận âm.
   • Ngưỡng phần trăm margin dịch vụ thấp.
   • Mức cảnh báo nghiêm trọng.
   • Định mức phần trăm nguyên liệu.
   • Tỷ lệ cảnh báo chi phí tăng.
   • Các con số nghiệp vụ mà người quản lý/dev có thể cần chỉnh sau này.
2. Response của hàm đó có thể dùng format JSON chuẩn không?
   Format chuẩn là:
   return response()->json([
   'success' => true,
   'data' => $data,
   ]);
   Trường hợp 1: Hàm chỉ lấy dữ liệu và trả JSON chuẩn
   Nếu endpoint chỉ có nhiệm vụ:
   • Nhận filter từ Request.
   • Gọi Repository.
   • Repository xử lý SQL/logic.
   • Trả dữ liệu về frontend.
   Thì Controller phải viết gọn theo phong cách respondData().
   Ví dụ:
   public function revenueReport(DateRangeFilterRequest $request): JsonResponse
{
    return $this->respondData(
        $this->financeRepository->getRevenueReport($request->getFiltersArray())
   );
   }
   Không được viết lại:
   • try/catch lặp lại.
   • response()->json() riêng trong từng endpoint.
   • SQL trong Controller.
   • Logic tính toán trong Controller.
   • Cấu trúc JSON phức tạp trong Controller nếu Repository đã trả đúng data.
   Trường hợp 2: Hàm cần giá trị cấu hình/ngưỡng nghiệp vụ dễ chỉnh
   Nếu endpoint cần dùng các con số nghiệp vụ như phần trăm, định mức, ngưỡng cảnh báo, thì không được hard-code rải rác trong SQL, JS hoặc trong từng dòng xử lý.
   Cách làm đúng:
   • Giữ các giá trị đó ở đầu Controller bằng private const nếu người dùng muốn dễ chỉnh trong Controller.
   • Controller vẫn không xử lý SQL.
   • Controller chỉ truyền các giá trị cấu hình đó xuống Repository.
   • Nếu response cuối cùng vẫn là JSON chuẩn, vẫn dùng respondData().
   Ví dụ:
   private const LOW_MARGIN_THRESHOLD = 20;
   private const CRITICAL_MARGIN_THRESHOLD = 10;

public function lowServiceMarginAlert(DateRangeFilterRequest $request): JsonResponse
{
return $this->respondData(
$this->financeRepository->getLowServiceMarginAlert(
$request->getFiltersArray(),
self::LOW_MARGIN_THRESHOLD,
self::CRITICAL_MARGIN_THRESHOLD
)
);
}
Repository sẽ nhận các ngưỡng này và xử lý SQL/logic cảnh báo.
Controller chỉ giữ vai trò:
• Nhận request.
• Lấy filter.
• Truyền filter và constant cần thiết cho Repository.
• Trả response bằng respondData().
Trường hợp 3: Hàm đang có response đặc thù không thể chuẩn hóa
Nếu endpoint cũ đang có cấu trúc response đặc biệt mà frontend hiện tại đang phụ thuộc vào, ví dụ:
• Trả thêm key ngoài success và data.
• Có status code riêng.
• Có message riêng bắt buộc UI đang dùng.
• Có logic lỗi đặc biệt không thể đưa về exception handler chung.
Thì không được ép refactor sang respondData() ngay.
Trong trường hợp này:
• Giữ nguyên response đặc thù nếu cần để không làm hỏng frontend.
• Chỉ refactor phần lặp nếu chắc chắn không đổi nghiệp vụ.
• Phải ghi chú rõ lý do vì sao hàm đó không dùng respondData().
Nguyên tắc chung
Không được hiểu respondData() là hàm gọi tất cả endpoint con.
respondData() chỉ là hàm đóng gói dữ liệu thành JSON chuẩn.
Frontend gọi API nào thì Laravel chỉ chạy endpoint tương ứng với route đó.
Endpoint đó gọi Repository tương ứng, sau đó dùng respondData() để trả kết quả.
Vì vậy khi refactor Controller:
• Endpoint thuần dữ liệu → rút gọn bằng respondData().
• Endpoint cần ngưỡng cấu hình → giữ constant ở đầu Controller, truyền xuống Repository, sau đó vẫn có thể dùng respondData().
• Endpoint có response đặc thù bắt buộc → giữ nguyên hoặc chỉ refactor một phần, không ép chuẩn hóa. 4. Quy tắc filter thời gian
Hiện tại DateRangeFilterRequest đã xử lý và trả về đủ 4 mốc thời gian:
[
'start_date' => ...,
'end_date' => ...,
'prev_start_date' => ...,
'prev_end_date' => ...,
]
Logic kỳ trước:
prev_end_date = start_date - 1 ngày
prev_start_date = prev_end_date - độ dài kỳ hiện tại + 1
Ví dụ:
Current: 2026-05-01 -> 2026-05-31
Previous: 2026-03-31 -> 2026-04-30
Vì vậy:
• Frontend chỉ gửi start_date, end_date.
• Backend tự sinh prev_start_date, prev_end_date.
• Không tính kỳ trước trong JS.
• Không tính kỳ trước trong Controller.
• Không hard-code kỳ trước.

---

5. Controller chỉ gọi Repository
   Controller không được xử lý SQL và không định nghĩa lại cấu trúc JSON phức tạp.
   Controller chỉ cần gọi Repository và trả về bằng respondData().
   Ví dụ đúng:
   /\*\*

- Mô tả chức năng:
- Lấy chỉ số RevPAR theo khoảng thời gian filter.
-
- Input:
-   - DateRangeFilterRequest tự xử lý start_date, end_date
-   - Request tự sinh prev_start_date, prev_end_date
-
- Output:
-   - JSON response thông qua respondData()
      \*/
      public function revpar(DateRangeFilterRequest $request): JsonResponse
{
    return $this->respondData(
        $this->dashboardRepository->getRevpar($request->getFiltersArray())
      );
      }
      Yêu cầu Controller:
      • Dùng DateRangeFilterRequest.
      • Gọi $request->getFiltersArray().
      • Gọi đúng method trong Repository.
      • Trả về bằng $this->respondData(...).
      • Không tự tính toán dữ liệu.
      • Không tự format JSON chi tiết nếu respondData() đã chuẩn hóa.
      • Không đặt SQL trong Controller.
      • Mỗi method mới phải có comment mô tả chức năng.

---

6. Repository xử lý SQL, logic và JSON data
   Repository là nơi xử lý:
   • SQL Oracle.
   • Bind parameter.
   • Normalize filter.
   • Tính toán chỉ số.
   • Tính so sánh kỳ hiện tại/kỳ trước.
   • Trả về cấu trúc JSON data đúng yêu cầu của JS.
   Nếu là Dashboard CEO, ưu tiên đặt trong:
   app/Repositories/Eloquent/Ceo/CeoDashboardRepository.php
   Yêu cầu Repository:
   • Dùng lại normalizeFilters($filters). 
•	Không rollback logic normalizeFilters(). 
•	Không truyền Carbon object trực tiếp vào SQL. 
•	Bind đủ 4 mốc thời gian khi SQL cần so sánh kỳ: 
$bindings = [
   'p_start_date' => $filters['start_date'],
    'p_end_date' => $filters['end_date'],
    'p_prev_start_date' => $filters['prev_start_date'],
    'p_prev_end_date' => $filters['prev_end_date'],
];
•	Dùng TO_DATE(:p_start_date, 'YYYY-MM-DD'). 
•	Dùng NVL để tránh null. 
•	Tránh chia cho 0. 
•	Không trả alias SQL thô khó hiểu ra frontend. 
•	Mỗi method mới phải có comment mô tả chức năng. 
Ví dụ cấu trúc trả về đúng cho KPI có so sánh:
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
   Trend thống nhất:
   change_percent > 0 → up
   change_percent < 0 → down
   change_percent = 0 → neutral
   Không dùng lẫn flat.

---

7. Router API
   Kiểm tra route hiện tại trước khi thêm.
   Nếu repo đang chia route như:
   routes/api/dashboard/ceo/
   routes/api/dashboard/manager/
   thì giữ nguyên cấu trúc đó.
   Yêu cầu:
   • Route nằm đúng file module.
   • Route được include đúng.
   • Không tạo route trùng.
   • Không phá route cũ.
   • Method phải đúng với DashboardEngine.
   Vì DashboardEngine hiện gọi API bằng GET, route nên dùng GET, ví dụ:
   Route::get('/finance/revpar', [CeoDashboardController::class, 'revpar']);
   Không dùng POST nếu JS engine hiện tại đang gọi GET.

---

8. Blade/View
Giao diện đã có sẵn.
Yêu cầu:
• Không dựng lại UI.
• Không phá layout.
• Không đổi style lớn nếu không cần.
• Chỉ bổ sung data-url hoặc selector phục vụ JS.
• API URL nên truyền qua data-\* ở root container.
Ví dụ:
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

---

9.  JavaScript riêng của trang
    JS riêng của trang chỉ làm 2 việc:
1.  Định nghĩa hàm render.
1.  Gọi DashboardEngine.run([...]).
    Không được viết lại logic fetch.
    Ví dụ đúng:
    function renderRevpar(data) {
    renderKpi(
    "revparKpi",
    data.current?.revpar,
    data.comparison?.change_percent,
    {
    formatValue: formatCurrency,
    },
    );

        const detailNode = document.querySelector(
            "#revparKpi [data-kpi-detail]",
        );

        if (detailNode) {
            detailNode.textContent = `Doanh thu phòng: ${formatCurrency(data.current?.total_room_revenue)}`;
        }

    }

DashboardEngine.run([
{
url: root.dataset.revparUrl,
onSuccess: renderRevpar,
},
]);
Yêu cầu JS:
• Dùng URL từ root.dataset.
• Không hard-code API URL trong JS nếu Blade đã truyền data-url.
• Không tự tạo payload.
• Không tự tính kỳ trước.
• Không gọi fetch() riêng.
• Không bind .js-apply-filter.
• Không trigger click thủ công.
• Chỉ viết render function.
• Có xử lý null/undefined an toàn.
• Có format tiền, số, phần trăm nếu cần.
• Có comment mô tả chức năng cho hàm render mới.
Ví dụ comment hàm JS:
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

---

10. JSON response mong muốn
    Controller dùng respondData(), nên Repository chỉ cần trả phần data.
    Ví dụ data Repository trả về:
    {
    "current": {
    "total_room_revenue": 50000000,
    "total_room": 25,
    "revpar": 2000000
    },
    "previous": {
    "total_room_revenue": 40000000,
    "total_room": 25,
    "revpar": 1600000
    },
    "comparison": {
    "diff_amount": 400000,
    "change_percent": 25,
    "trend": "up"
    }
    }
    API response cuối cùng do respondData() bọc lại sẽ có dạng:
    {
    "success": true,
    "data": {
    "current": {},
    "previous": {},
    "comparison": {}
    }
    }
    Nếu là chart, Repository trả:
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
    Nếu là ranking/list, Repository trả array rows:
    return [
    [
    'rank_no' => 1,
    'branch_name' => 'Chi nhánh A',
    'revenue_million_vnd' => 125.5,
    ],
    ];
    Quan trọng:
    • JSON phải khớp với hàm render JS.
    • Không trả alias SQL khó hiểu.
    • Không trả dữ liệu thừa.
    • Không trả raw SQL error.

---

11. Quy tắc bắt buộc về mô tả hàm trong file
    Khi thêm hoặc sửa function, phải thêm mô tả chức năng ngay trong file.
    Áp dụng cho:
    • Controller method
    • Repository method
    • JS render function
    • JS helper function nếu thêm mới
    Mỗi mô tả cần có:
    Mô tả chức năng
    Input
    Output
    Ghi chú nếu có
    Không cần viết quá dài, nhưng phải đủ để người khác mở code ra hiểu hàm đó làm gì.

---

12. Không được làm
    Không được:
    • Viết lại DashboardEngine nếu không thật sự cần.
    • Viết fetch() riêng trong JS trang.
    • Bind lại nút .js-apply-filter.
    • Tự tính prev_start_date, prev_end_date ở JS.
    • Tự tính prev_start_date, prev_end_date ở Controller.
    • Đưa SQL vào Controller.
    • Hard-code dữ liệu mẫu.
    • Hard-code ngày.
    • Tạo route POST nếu engine đang gọi GET.
    • Đổi kiến trúc repo hiện tại.
    • Làm lại giao diện từ đầu.
    • Trả alias SQL thô ra frontend.

---

13. Checklist sau khi làm
    Sau khi sửa, hãy tự kiểm tra:
    Route
    • Route đúng file chưa?
    • Route đã được include chưa?
    • Route dùng GET đúng với DashboardEngine chưa?
    • Route gọi đúng Controller chưa?
    Controller
    • Có dùng DateRangeFilterRequest chưa?
    • Có dùng $request->getFiltersArray() chưa?
    • Có gọi Repository chưa?
    • Có trả về respondData() chưa?
    • Có comment mô tả function chưa?
    Repository
    • Có gọi normalizeFilters() chưa?
    • Có bind đúng ngày chưa?
    • Có dùng TO_DATE(..., 'YYYY-MM-DD') chưa?
    • Có xử lý null chưa?
    • Có tránh chia cho 0 chưa?
    • Có trả data đúng cấu trúc JS cần chưa?
    • Có comment mô tả function chưa?
    Blade
    • Root container có data-url đúng chưa?
    • Selector render có tồn tại chưa?
    • Không làm vỡ giao diện cũ chưa?
    JavaScript
    • Có dùng DashboardEngine.run([...]) chưa?
    • Không có fetch() riêng chưa?
    • Không bind .js-apply-filter riêng chưa?
    • Hàm render nhận đúng data từ API chưa?
    • Dữ liệu đã render động lên UI chưa?
    • Có xử lý null an toàn chưa?
    • Có comment mô tả function chưa?

---

14. Lưu ý kiểm tra PHP
    Nếu không chạy được:
    php -l
    thì không được báo đã kiểm tra syntax PHP thành công.
    Hãy ghi rõ:
    Chưa chạy được php -l vì máy chưa nhận lệnh php trong PATH.

---

15. Kết quả cần trả về sau khi hoàn thành
    Sau khi sửa code, hãy tổng hợp:
1. File đã sửa/thêm
1. Route API đã thêm/cập nhật
1. Controller method đã thêm/cập nhật
1. Repository method đã thêm/cập nhật
1. Cấu trúc data Repository trả về
1. JSON API cuối cùng sau respondData()
1. Blade đã gắn data-url/selector như thế nào
1. JS đã dùng DashboardEngine.run() như thế nào
1. Các hàm đã thêm mô tả chức năng
1. Cách test nhanh trên trình duyệt/Postman
1. Những phần chưa kiểm tra được, ví dụ php -l nếu máy chưa có PHP trong PATH

---

16. Bắt đầu thực hiện
    Hãy đọc repo hiện tại trước, xác định đúng cấu trúc đang dùng, rồi mới sửa code.
    Sau đó triển khai chức năng đầy đủ dựa trên SQL tôi cung cấp.
    Không chỉ mô tả. Hãy sửa file trực tiếp trong repo.
    Trọng tâm là: DashboardEngine đã phụ trách lấy dữ liệu. Việc cần làm là thêm route, Controller mỏng, Repository xử lý SQL và JS render dữ liệu động lên giao diện đã có sẵn.
