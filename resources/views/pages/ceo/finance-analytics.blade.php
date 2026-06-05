@extends('layouts.ceo')

@section('title', 'Phân tích Tài chính Tổng thể')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/client/css/ceo/finance-analytics.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
$period = 'tháng';

/*
|--------------------------------------------------------------------------
| 1. DATA MẪU: P&L DASHBOARD
|--------------------------------------------------------------------------
*/
$plKpis = [
[
'key' => 'finance-total-revenue',
'title' => 'Tổng Doanh thu (Gross Revenue)',
'value' => 'Đang tải...',
'trend' => 'Chưa có dữ liệu kỳ trước',
'isPositive' => true,
'period' => $period,
],
[
'key' => 'finance-estimated-total-cost',
'title' => 'Tổng Chi phí ước tính',
'value' => 'Đang tải...',
'trend' => 'Chưa có dữ liệu kỳ trước',
'isPositive' => false,
'period' => $period,
],
[
'key' => 'finance-estimated-profit',
'title' => 'Lợi Nhuận Ròng (Net Profit)',
'value' => 'Đang tải...',
'trend' => 'Chưa có dữ liệu kỳ trước',
'isPositive' => true,
'period' => $period,
],
[
'key' => 'finance-estimated-margin',
'title' => 'Biên lợi nhuận ước tính',
'value' => 'Đang tải...',
'trend' => 'Chưa có dữ liệu kỳ trước',
'isPositive' => true,
'period' => $period,
],
];

$plChartData = [
['time' => 'T1', 'revenue' => 400, 'cost' => 250],
['time' => 'T2', 'revenue' => 450, 'cost' => 280],
['time' => 'T3', 'revenue' => 600, 'cost' => 320],
['time' => 'T4', 'revenue' => 550, 'cost' => 300],
['time' => 'T5', 'revenue' => 700, 'cost' => 350],
['time' => 'T6', 'revenue' => 850, 'cost' => 400],
];

$plLineConfigs = [
['key' => 'revenue', 'name' => 'Tổng Doanh thu', 'color' => '#0ea5e9'],
['key' => 'cost', 'name' => 'Tổng Chi phí (COGS + Opex)', 'color' => '#ef4444'],
];

/*
|--------------------------------------------------------------------------
| 2. DATA MẪU: UNIT ECONOMICS
|--------------------------------------------------------------------------
*/
$unitPieData = [
['name' => 'Spa & Grooming', 'value' => 45],
['name' => 'Lưu trú (Hotel)', 'value' => 55],
];

$unitPieColors = [
'#0ea5e9',
'#8b5cf6',
];

$lowMarginServices = [
[
'id' => 'SPA-04',
'name' => 'Tắm chó lông dài (Lớn)',
'category' => 'Grooming',
'price' => 350000,
'cogs' => 280000,
'margin' => 20,
],
[
'id' => 'HTL-02',
'name' => 'Lưu chuồng tiêu chuẩn',
'category' => 'Hotel',
'price' => 150000,
'cogs' => 110000,
'margin' => 26,
],
[
'id' => 'SPA-09',
'name' => 'Cắt tỉa tạo kiểu Poodle',
'category' => 'Grooming',
'price' => 400000,
'cogs' => 240000,
'margin' => 40,
],
[
'id' => 'HTL-05',
'name' => 'Lưu chuồng VIP (Có Camera)',
'category' => 'Hotel',
'price' => 500000,
'cogs' => 150000,
'margin' => 70,
],
];

/*
|--------------------------------------------------------------------------
| 3. DATA MẪU: CUSTOMER FINANCIALS
|--------------------------------------------------------------------------
*/
$customerKpis = [
[
'key' => 'finance-cac',
'title' => 'CAC - Chi phí thu hút 1 khách',
'value' => 'Chưa có dữ liệu',
'trend' => 'Chưa bind API',
'isPositive' => true,
'period' => $period,
],
[
'key' => 'finance-clv',
'title' => 'CLV - Giá trị vòng đời khách hàng',
'value' => 'Chưa có dữ liệu',
'trend' => 'Chưa bind API',
'isPositive' => true,
'period' => $period,
],
[
'key' => 'finance-clv-cac-ratio',
'title' => 'Tỷ lệ CLV / CAC',
'value' => 'Chưa có dữ liệu',
'trend' => 'Chưa bind API',
'isPositive' => true,
'period' => $period,
],
];

$customerTrendData = [
['month' => 'T1', 'newCustomers' => 120, 'returnCustomers' => 300],
['month' => 'T2', 'newCustomers' => 150, 'returnCustomers' => 320],
['month' => 'T3', 'newCustomers' => 90, 'returnCustomers' => 280],
['month' => 'T4', 'newCustomers' => 180, 'returnCustomers' => 360],
['month' => 'T5', 'newCustomers' => 210, 'returnCustomers' => 410],
['month' => 'T6', 'newCustomers' => 190, 'returnCustomers' => 430],
];

$customerLineConfigs = [
['key' => 'newCustomers', 'name' => 'Khách mới', 'color' => '#f59e0b'],
['key' => 'returnCustomers', 'name' => 'Khách quay lại', 'color' => '#10b981'],
];

/*
|--------------------------------------------------------------------------
| 4. DATA MẪU: COST OPTIMIZATION
|--------------------------------------------------------------------------
*/
$supplierDebts = [
[
'id' => 'INV-2026-089',
'supplier' => 'Công ty Cổ phần PetCare VN',
'amount' => 45000000,
'daysOverdue' => 15,
'status' => 'overdue',
],
[
'id' => 'INV-2026-092',
'supplier' => 'Đại lý Cát Vệ Sinh Sài Gòn',
'amount' => 12500000,
'daysOverdue' => 3,
'status' => 'overdue',
],
[
'id' => 'INV-2026-105',
'supplier' => 'Xưởng nệm thú cưng Happy',
'amount' => 8000000,
'daysOverdue' => -2,
'status' => 'duesoon',
],
];

function financeMoney($amount) {
return number_format($amount, 0, ',', '.') . ' ₫';
}

function marginClass($margin) {
if ($margin < 30) { return 'finance-margin finance-margin--bad' ; } if ($margin <=50) {
  return 'finance-margin finance-margin--ok' ; } return 'finance-margin finance-margin--good' ; } @endphp <div
  class="finance-page" id="ceoFinancePage" data-finance-url="{{ route('api.dashboard.ceo.finance') }}"
  data-estimated-cost-url="{{ route('api.dashboard.ceo.finance.estimated-total-cost') }}"
  data-estimated-profit-url="{{ route('api.dashboard.ceo.finance.estimated-profit') }}"
  data-estimated-margin-url="{{ route('api.dashboard.ceo.finance.estimated-margin') }}"
  data-finance-trend-url="{{ route('api.dashboard.ceo.finance.trend') }}"
  data-finance-monthly-trend-url="{{ route('api.dashboard.ceo.finance.monthly-trend') }}"
  data-cost-structure-url="{{ route('api.dashboard.ceo.finance.cost-structure') }}"
  data-branch-estimated-profit-url="{{ route('api.dashboard.ceo.finance.branch-estimated-profit') }}"
  data-service-estimated-profit-url="{{ route('api.dashboard.ceo.finance.service-estimated-profit') }}"
  data-lowest-margin-services-url="{{ route('api.dashboard.ceo.finance.lowest-margin-services') }}"
  data-negative-branch-profit-alerts-url="{{ route('api.dashboard.ceo.finance.negative-branch-profit-alerts') }}"
  data-low-service-margin-alerts-url="{{ route('api.dashboard.ceo.finance.low-service-margin-alerts') }}"
  data-cost-growth-alerts-url="{{ route('api.dashboard.ceo.finance.cost-growth-alerts') }}">

  <x-global-control-panel title="Phân tích Tài chính Tổng thể" />

  {{-- 1. P&L DASHBOARD --}}
  <section class="finance-section">
    <div class="finance-section__header">
      <h2>1. Báo cáo Lãi/Lỗ Tổng thể</h2>
      <p>Theo dõi doanh thu, chi phí và lợi nhuận ròng của toàn chuỗi.</p>
    </div>

    <div class="finance-kpi-grid finance-kpi-grid--three finance-kpi-grid--four">
      @foreach ($plKpis as $item)
      <x-kpi-card data-kpi="{{ $item['key'] }}" :title="$item['title']" :value="$item['value']" :trend="$item['trend']"
        :isPositive="$item['isPositive']" :period="$item['period']" :icon="null" />
      @endforeach
    </div>

    <div class="finance-card">
      <h3>Xu hướng Doanh thu - Chi phí - Lợi nhuận theo ngày</h3>

      <div class="finance-chart-area">
        <x-chart.line id="financeRevenueCostChart" :data="$plChartData" xAxisKey="time" :lineConfigs="$plLineConfigs"
          yAxisFormatter="raw" height="320px" />
      </div>
    </div>

    <div class="finance-card">
      <h3>Xu hướng Doanh thu - Chi phí - Lợi nhuận theo tháng</h3>

      <div class="finance-chart-area">
        <x-chart.line id="financeMonthlyTrendChart" :data="$plChartData" xAxisKey="time" :lineConfigs="$plLineConfigs"
          yAxisFormatter="raw" height="320px" />
      </div>
    </div>

    <div class="finance-card">
      <h3>Cơ cấu chi phí ước tính</h3>

      <div class="finance-chart-area">
        <canvas id="financeCostStructureChart" aria-label="Biểu đồ cơ cấu chi phí ước tính"></canvas>
      </div>
    </div>

    <div class="finance-card">
      <h3>Lợi nhuận ước tính theo chi nhánh</h3>

      <div class="finance-table-wrapper">
        <table class="finance-table finance-table--branch-profit">
          <thead>
            <tr>
              <th>Hạng</th>
              <th>Chi nhánh</th>
              <th>Doanh thu</th>
              <th>Chi phí lương</th>
              <th>Chi phí vật tư</th>
              <th>Tổng chi phí</th>
              <th>Lợi nhuận ước tính</th>
              <th class="text-center">Margin</th>
            </tr>
          </thead>

          <tbody id="financeBranchEstimatedProfitTableBody">
            <tr>
              <td class="finance-table__placeholder" colspan="8">Đang tải dữ liệu...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>


  {{-- 2. UNIT ECONOMICS --}}
  <section class="finance-section">
    <div class="finance-section__header">
      <h2>2. Phân tích Biên lợi nhuận Dịch vụ</h2>
      <p>Đánh giá tỷ trọng doanh thu và biên lợi nhuận của từng nhóm dịch vụ.</p>
    </div>

    <div class="finance-two-column">
      <div class="finance-card">
        <h3>Tỷ trọng Doanh thu (Grooming vs Hotel)</h3>

        <div class="finance-chart-area">
          <x-chart.pie id="financeRevenueMixChart" :data="$unitPieData" nameKey="name" dataKey="value"
            :colors="$unitPieColors" height="320px" />
        </div>
      </div>

      <div class="finance-card">
        <h3>Lợi nhuận ước tính theo dịch vụ</h3>

        <div class="finance-table-wrapper">
          <table class="finance-table finance-table--service-profit">
            <thead>
              <tr>
                <th>Hạng</th>
                <th>Dịch vụ</th>
                <th>Lượt dùng</th>
                <th>Doanh thu</th>
                <th>Chi phí vật tư</th>
                <th>Chi phí nhân công</th>
                <th>Tổng chi phí</th>
                <th>Lợi nhuận ước tính</th>
                <th class="text-center">Margin</th>
              </tr>
            </thead>

            <tbody id="financeServiceEstimatedProfitTableBody">
              <tr>
                <td class="finance-table__placeholder" colspan="9">Đang tải dữ liệu...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="finance-card">
      <h3>Top 5 dịch vụ có biên lợi nhuận thấp nhất</h3>

      <div class="finance-table-wrapper">
        <table class="finance-table finance-table--service-profit">
          <thead>
            <tr>
              <th>STT</th>
              <th>Dịch vụ</th>
              <th>Lượt dùng</th>
              <th>Doanh thu</th>
              <th>Chi phí vật tư</th>
              <th>Chi phí nhân công</th>
              <th>Tổng chi phí</th>
              <th>Lợi nhuận ước tính</th>
              <th class="text-center">Margin</th>
            </tr>
          </thead>

          <tbody id="financeLowestMarginServicesTableBody">
            <tr>
              <td class="finance-table__placeholder" colspan="9">Đang tải dữ liệu...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="finance-card">
      <div class="finance-card-title-row">
        <h3>Cảnh báo dịch vụ biên lợi nhuận thấp</h3>
        <span id="financeLowServiceMarginAlertCount">Đang tải...</span>
      </div>

      <div class="finance-alert-list" id="financeLowServiceMarginAlerts">
        <div class="finance-loss-alert finance-loss-alert--warning">
          <div class="finance-loss-alert__content">
            <p>Đang tải cảnh báo...</p>
          </div>
        </div>
      </div>
    </div>

  </section>


  {{-- 3. CUSTOMER FINANCIALS --}}
  <section class="finance-section">
    <div class="finance-section__header">
      <h2>3. Phân tích Dòng tiền Khách hàng</h2>
      <p>Đo lường hiệu quả thu hút khách hàng và giá trị vòng đời khách hàng.</p>
    </div>

    <div class="finance-kpi-grid finance-kpi-grid--three">
      @foreach ($customerKpis as $item)
      <x-kpi-card data-kpi="{{ $item['key'] }}" :title="$item['title']" :value="$item['value']" :trend="$item['trend']"
        :isPositive="$item['isPositive']" :period="$item['period']" :icon="null" />
      @endforeach
    </div>

    <div class="finance-card">
      <h3>Xu hướng Khách mới và Khách quay lại</h3>

      <div class="finance-chart-area">
        <x-chart.line :data="$customerTrendData" xAxisKey="month" :lineConfigs="$customerLineConfigs"
          yAxisFormatter="raw" height="320px" />
      </div>
    </div>

  </section>


  {{-- 4. COST OPTIMIZATION --}}
  <section class="finance-section">
    <div class="finance-section__header">
      <h2>4. Báo cáo Thất thoát & Tối ưu Chi phí</h2>
      <p>Phát hiện hao hụt vật tư, gian lận nội bộ và công nợ nhà cung cấp.</p>
    </div>

    <div class="finance-two-column">
      <div class="finance-card">
        <div class="finance-card-title-row">
          <h3>Cảnh báo chi nhánh lợi nhuận âm</h3>
          <span id="financeNegativeBranchProfitAlertCount">Đang tải...</span>
        </div>

        <div class="finance-alert-list" id="financeNegativeBranchProfitAlerts">
          <div class="finance-loss-alert finance-loss-alert--warning">
            <div class="finance-loss-alert__content">
              <p>Đang tải cảnh báo...</p>
            </div>
          </div>
        </div>
      </div>

      <div class="finance-card">
        <h3>💸 Cảnh báo Công nợ Nhà cung cấp</h3>

        <div class="finance-table-wrapper">
          <table class="finance-table">
            <thead>
              <tr>
                <th>Nhà cung cấp / Mã phiếu</th>
                <th>Số tiền nợ</th>
                <th>Tình trạng</th>
                <th>Thao tác</th>
              </tr>
            </thead>

            <tbody>
              @foreach ($supplierDebts as $debt)
              <tr>
                <td>
                  <div class="finance-service-name">{{ $debt['supplier'] }}</div>
                  <div class="finance-service-meta">{{ $debt['id'] }}</div>
                </td>

                <td>
                  <span class="finance-money">{{ financeMoney($debt['amount']) }}</span>
                </td>

                <td>
                  @if ($debt['status'] === 'overdue')
                  <span class="finance-debt-badge finance-debt-badge--overdue">
                    Quá hạn {{ $debt['daysOverdue'] }} ngày
                  </span>
                  @else
                  <span class="finance-debt-badge finance-debt-badge--duesoon">
                    Đến hạn sau {{ abs($debt['daysOverdue']) }} ngày
                  </span>
                  @endif
                </td>

                <td>
                  <button type="button" class="finance-pay-btn">Thanh toán</button>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="finance-card">
      <div class="finance-card-title-row">
        <h3>Cảnh báo chi phí tăng mạnh</h3>
        <span id="financeCostGrowthAlertCount">Đang tải...</span>
      </div>

      <div class="finance-alert-list" id="financeCostGrowthAlerts">
        <div class="finance-loss-alert finance-loss-alert--warning">
          <div class="finance-loss-alert__content">
            <p>Đang tải cảnh báo...</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  </div>
  @endsection

  @push('scripts')
  <script src="{{ asset('assets/client/js/ceo/finance-analytics.js') }}?v={{ time() }}"></script>
  @endpush
