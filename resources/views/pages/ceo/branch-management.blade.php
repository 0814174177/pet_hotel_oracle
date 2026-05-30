@extends('layouts.ceo')

@section('title', 'Quản lý chi nhánh')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/ceo/branch-management.css') }}?v={{ time() }}">
@endpush

@section('content')

@php
    $period = 'tháng';

    $stats = [
        'totalActive' => 4,
        'topRevenueBranch' => [
            'name' => 'Pet Hotel Central',
            'value' => '540.000.000đ',
        ],
        'lowestOccupancyBranch' => [
            'name' => 'Pet Hotel Nam Sài Gòn',
            'value' => '45.2%',
        ],
    ];

    $branches = [
        [
            'id' => 'CN-Q1',
            'name' => 'Pet Hotel Central',
            'district' => 'Quận 1',
            'revenue' => 540000000,
            'occupancyRate' => 92.5,
            'manager' => [
                'name' => 'Nguyễn Văn A',
                'phone' => '0901234567',
            ],
            'status' => 'active',
        ],
        [
            'id' => 'CN-Q3',
            'name' => 'Pet Hotel Premium',
            'district' => 'Quận 3',
            'revenue' => 420000000,
            'occupancyRate' => 85.0,
            'manager' => [
                'name' => 'Trần Thị B',
                'phone' => '0987654321',
            ],
            'status' => 'active',
        ],
        [
            'id' => 'CN-TD',
            'name' => 'Pet Hotel Thủ Đức',
            'district' => 'Thủ Đức',
            'revenue' => 310000000,
            'occupancyRate' => 78.0,
            'manager' => [
                'name' => 'Phạm Văn C',
                'phone' => '0912345678',
            ],
            'status' => 'active',
        ],
        [
            'id' => 'CN-Q7',
            'name' => 'Pet Hotel Nam Sài Gòn',
            'district' => 'Quận 7',
            'revenue' => 120000000,
            'occupancyRate' => 45.2,
            'manager' => [
                'name' => 'Lê Văn D',
                'phone' => '0911222333',
            ],
            'status' => 'maintenance',
        ],
        [
            'id' => 'CN-GV',
            'name' => 'Pet Hotel Gò Vấp',
            'district' => 'Gò Vấp',
            'revenue' => 250000000,
            'occupancyRate' => 60.5,
            'manager' => [
                'name' => 'Hoàng Thị E',
                'phone' => '0933444555',
            ],
            'status' => 'active',
        ],
    ];
@endphp

<div class="branch-management-page">

    <x-global-control-panel
        title="Mạng lưới Chi nhánh"
        period="tháng"
        lastUpdate="14:58 - Cập nhật thành công"
    />

    <section class="branch-stats">
        <x-kpi-card
            title="Tổng chi nhánh đang hoạt động"
            value="{{ $stats['totalActive'] }} cơ sở"
            trend="Hệ thống vận hành ổn định"
            :isPositive="true"
            :period="$period"
            :icon="null"
        />

        <x-kpi-card
            title="Doanh thu cao nhất ({{ $stats['topRevenueBranch']['name'] }})"
            value="{{ $stats['topRevenueBranch']['value'] }}"
            trend="Dẫn đầu toàn chuỗi"
            :isPositive="true"
            :period="$period"
            :icon="null"
        />

        <x-kpi-card
            title="Lấp đầy thấp nhất ({{ $stats['lowestOccupancyBranch']['name'] }})"
            value="{{ $stats['lowestOccupancyBranch']['value'] }}"
            trend="Cảnh báo: Cần thanh tra vận hành"
            :isPositive="false"
            :period="$period"
            :icon="null"
        />
    </section>

    <section class="branch-content-section">
        <div class="branch-toolbar">
            <div class="branch-toolbar__search">
                <input type="text" placeholder="Tìm kiếm chi nhánh, khu vực, quản lý...">
            </div>

            <div class="branch-toolbar__filters">
                <select>
                    <option value="">Tất cả khu vực</option>
                    <option value="Quận 1">Quận 1</option>
                    <option value="Quận 3">Quận 3</option>
                    <option value="Thủ Đức">Thủ Đức</option>
                    <option value="Quận 7">Quận 7</option>
                    <option value="Gò Vấp">Gò Vấp</option>
                </select>

                <select>
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Hoạt động</option>
                    <option value="maintenance">Bảo trì</option>
                </select>
            </div>
        </div>

        <div class="branch-grid-wrapper">
            <table class="branch-table">
                <thead>
                    <tr>
                        <th>Chi nhánh</th>
                        <th>Khu vực</th>
                        <th>Doanh thu (Tháng)</th>
                        <th class="text-center">Tỷ lệ lấp đầy</th>
                        <th>Quản lý cơ sở</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($branches as $branch)
                        <tr>
                            <td>
                                <div class="branch-name">{{ $branch['name'] }}</div>
                                <div class="branch-code">{{ $branch['id'] }}</div>
                            </td>

                            <td>{{ $branch['district'] }}</td>

                            <td>
                                <span class="branch-revenue">
                                    {{ number_format($branch['revenue'], 0, ',', '.') }}đ
                                </span>
                            </td>

                            <td class="text-center">
                                <span class="{{ $branch['occupancyRate'] >= 50 ? 'occupancy-high' : 'occupancy-low' }}">
                                    {{ $branch['occupancyRate'] }}%
                                </span>
                            </td>

                            <td>
                                <div class="manager-name">{{ $branch['manager']['name'] }}</div>
                                <div class="manager-phone">{{ $branch['manager']['phone'] }}</div>
                            </td>

                            <td>
                                @if ($branch['status'] === 'active')
                                    <span class="status-badge status-active">Hoạt động</span>
                                @else
                                    <span class="status-badge status-maintenance">Bảo trì</span>
                                @endif
                            </td>

                            <td>
                                <button type="button" class="branch-action-btn">
                                    👁️ Xem chi tiết
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="branch-empty">
                                Không tìm thấy chi nhánh phù hợp với bộ lọc hiện tại.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</div>
@endsection
