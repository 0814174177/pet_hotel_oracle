@extends('layouts.manager')
@section('title', 'Quản trị Danh mục Vật tư')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/manager/inventory-management.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $period = 'tháng';

    $items = [
        [
            'id' => 'VT001',
            'name' => 'Cát vệ sinh Clean Cat',
            'category' => 'hygiene',
            'unit' => 'Bao (5kg)',
            'currentQty' => 8,
            'minQty' => 15,
            'maxQty' => 50,
            'lastUpdated' => '2025-06-12 08:30',
            'updatedBy' => 'Nguyễn Thị Lan',
        ],
        [
            'id' => 'VT002',
            'name' => 'Sữa tắm Spa Pet Pro',
            'category' => 'spa',
            'unit' => 'Chai (500ml)',
            'currentQty' => 24,
            'minQty' => 10,
            'maxQty' => 60,
            'lastUpdated' => '2025-06-11 14:00',
            'updatedBy' => 'Trần Văn Minh',
        ],
        [
            'id' => 'VT003',
            'name' => 'Thức ăn hạt Royal Canin (Chó)',
            'category' => 'food',
            'unit' => 'Bao (2kg)',
            'currentQty' => 6,
            'minQty' => 10,
            'maxQty' => 40,
            'lastUpdated' => '2025-06-12 07:15',
            'updatedBy' => 'Lê Thị Hoa',
        ],
        [
            'id' => 'VT004',
            'name' => 'Thức ăn hạt Whiskas (Mèo)',
            'category' => 'food',
            'unit' => 'Bao (1.5kg)',
            'currentQty' => 18,
            'minQty' => 8,
            'maxQty' => 30,
            'lastUpdated' => '2025-06-10 16:45',
            'updatedBy' => 'Trần Văn Minh',
        ],
        [
            'id' => 'VT005',
            'name' => 'Dung dịch tiệt trùng Anistel',
            'category' => 'medical',
            'unit' => 'Chai (1L)',
            'currentQty' => 3,
            'minQty' => 5,
            'maxQty' => 20,
            'lastUpdated' => '2025-06-09 09:00',
            'updatedBy' => 'Nguyễn Thị Lan',
        ],
    ];

    $categoryLabels = [
        'hygiene' => 'Vệ sinh',
        'spa' => 'Spa & Grooming',
        'food' => 'Thức ăn',
        'medical' => 'Y tế',
    ];

    function inventoryStatus($current, $min, $max) {
        if ($current <= 0) {
            return 'out';
        }

        if ($current < $min) {
            return 'low';
        }

        if ($current >= $max * 0.8) {
            return 'high';
        }

        return 'normal';
    }

    function inventoryStatusLabel($status) {
        return match ($status) {
            'out' => 'Hết hàng',
            'low' => 'Sắp hết',
            'high' => 'Tồn cao',
            default => 'Hoạt động',
        };
    }

    $totalItems = count($items);
    $outOfStockCount = count(array_filter($items, fn ($item) => $item['currentQty'] <= 0));
    $lowStockCount = count(array_filter($items, fn ($item) => $item['currentQty'] > 0 && $item['currentQty'] < $item['minQty']));
@endphp

<div class="inventory-page">

    <x-global-control-panel
        title="Quản trị Danh mục Vật tư"
        period="tháng"
        lastUpdate="14:58 - Cập nhật thành công"
    />

    {{-- KPI SECTION --}}
    <section class="inventory-kpi-row">
        <x-kpi-card
            title="Tổng vật tư"
            :value="$totalItems"
            trend="+2 loại"
            :isPositive="true"
            :period="$period"
            :icon="null"
        />

        <x-kpi-card
            title="Hết hàng"
            :value="$outOfStockCount"
            trend="Cần nhập ngay"
            :isPositive="false"
            :period="$period"
            :icon="null"
        />

        <x-kpi-card
            title="Sắp hết"
            :value="$lowStockCount"
            trend="Trong 30 ngày"
            :isPositive="false"
            :period="$period"
            :icon="null"
        />

        <x-kpi-card
            title="Vốn tồn kho"
            value="125.4M"
            trend="Margin 65%"
            :isPositive="true"
            :period="$period"
            :icon="null"
        />
    </section>

    {{-- MAIN CONTENT --}}
    <section class="inventory-main-card">

        {{-- TOOLBAR --}}
        <div class="inventory-toolbar">
            <div class="inventory-search-group">
                <input type="text" placeholder="Tìm kiếm Mã hoặc Tên vật tư...">

                <select>
                    <option value="">-- Tất cả nhóm --</option>
                    @foreach ($categoryLabels as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button type="button" class="inventory-add-btn">
                + Thêm vật tư mới
            </button>
        </div>

        {{-- TABLE --}}
        <div class="inventory-table-wrapper">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Mã/Tên</th>
                        <th>Trạng thái</th>
                        <th>Tồn kho / Ngưỡng</th>
                        <th>Đơn vị</th>
                        <th>Cập nhật</th>
                        <th class="text-right">Thao tác quản trị</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($items as $item)
                        @php
                            $status = inventoryStatus($item['currentQty'], $item['minQty'], $item['maxQty']);
                            $percent = min(($item['currentQty'] / $item['maxQty']) * 100, 100);
                        @endphp

                        <tr>
                            <td>
                                <div class="inventory-primary-text">{{ $item['name'] }}</div>
                                <div class="inventory-secondary-text">{{ $item['id'] }}</div>
                                <div class="inventory-category-text">
                                    Nhóm: {{ $categoryLabels[$item['category']] ?? $item['category'] }}
                                </div>
                            </td>

                            <td>
                                <span class="inventory-status inventory-status--{{ $status }}">
                                    {{ inventoryStatusLabel($status) }}
                                </span>
                            </td>

                            <td>
                                <div class="inventory-progress-wrap">
                                    <div class="inventory-progress-track">
                                        <div
                                            class="inventory-progress-fill inventory-progress-fill--{{ $status }}"
                                            style="width: {{ $percent }}%;"
                                        ></div>
                                    </div>

                                    <span class="inventory-progress-label">
                                        {{ $item['currentQty'] }}/{{ $item['maxQty'] }}
                                    </span>
                                </div>

                                <div class="inventory-threshold-text">
                                    Min: {{ $item['minQty'] }} • Max: {{ $item['maxQty'] }}
                                </div>
                            </td>

                            <td>
                                {{ $item['unit'] }}
                            </td>

                            <td>
                                <div class="inventory-meta-date">{{ $item['lastUpdated'] }}</div>
                                <div class="inventory-meta-user">{{ $item['updatedBy'] }}</div>
                            </td>

                            <td>
                                <div class="inventory-action-group">
                                    <button type="button" class="inventory-action-btn">Xem</button>
                                    <button type="button" class="inventory-action-btn">Sửa</button>
                                    <button type="button" class="inventory-action-btn inventory-action-btn--danger">
                                        Ngừng nhập
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    @if (count($items) === 0)
                        <tr>
                            <td colspan="6" class="inventory-empty">
                                Không tìm thấy vật tư phù hợp với bộ lọc hiện tại.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

    </section>

</div>

@endsection