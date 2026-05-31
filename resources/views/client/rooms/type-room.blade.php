@extends('layouts.client')

@section('title', $typeRoom['name'])

@php
    $mainImage = $typeRoom['images'][0] ?? asset('assets/client/images/type-room/normal/dog/1.jpg');
    $firstRoom = collect($roomRows)->first();
    $bookingUrl = $firstRoom['booking_url'] ?? url('/booking');
    $money = fn ($amount) => number_format((float) $amount, 0, ',', '.').'đ';
@endphp

@section('content')
<section class="rd-page" data-rd-booking-url="{{ $bookingUrl }}">
  <div class="rd-container">
    <div class="rd-layout rd-layout--single">
      <div class="rd-main">
        <section class="rd-hero">
          <div class="rd-hero__content">
            <h1>{{ $typeRoom['name'] }}</h1>
            <p>{{ $typeRoom['description'] }}</p>

            <div class="rd-quick-grid">
              <div class="rd-quick-item">
                <span>Giá phòng</span>
                <strong>{{ $typeRoom['price'] }}</strong>
              </div>
              <div class="rd-quick-item">
                <span>Diện tích</span>
                <strong>{{ $typeRoom['area'] }}</strong>
              </div>
              <div class="rd-quick-item">
                <span>Sức chứa</span>
                <strong>{{ $typeRoom['capacity'] }}</strong>
              </div>
              <div class="rd-quick-item">
                <span>Cân nặng</span>
                <strong>{{ $typeRoom['weight'] }}</strong>
              </div>
            </div>

            <div class="rd-actions">
              <button class="rd-btn rd-btn--primary" type="button" onclick="rdBookNow()">Đặt phòng ngay</button>
            </div>
          </div>

          <div class="rd-gallery" aria-label="Thư viện ảnh phòng">
            <div class="rd-gallery-main">
              <img class="rd-gallery-main__img" id="rdMainImage" src="{{ $mainImage }}" alt="{{ $typeRoom['name'] }}">
            </div>

            @if (! empty($typeRoom['images']))
              <div class="rd-gallery-thumbs">
                @foreach ($typeRoom['images'] as $index => $image)
                  <button class="rd-gallery-thumb {{ $index === 0 ? 'rd-gallery-thumb--active' : '' }}" type="button"
                    onclick="rdSwitchImage('{{ $image }}', this)" aria-label="Xem ảnh {{ $index + 1 }}">
                    <img src="{{ $image }}" alt="{{ $typeRoom['name'] }} {{ $index + 1 }}">
                  </button>
                @endforeach
              </div>
            @endif
          </div>
        </section>

        <div class="rd-info-grid">
          <section class="rd-card">
            <div class="rd-section-heading">
              <span>Tiện ích phòng</span>
              <h2>Không gian lưu trú</h2>
            </div>
            <ul class="rd-feature-list">
              @foreach ($typeRoom['features'] as $feature)
                <li><i class="fa-solid fa-check"></i>{{ $feature }}</li>
              @endforeach
            </ul>
          </section>

          <section class="rd-card">
            <div class="rd-section-heading">
              <span>Chăm sóc</span>
              <h2>Thông tin theo dõi</h2>
            </div>
            <p class="rd-muted">{{ $typeRoom['care'] }}</p>
          </section>
        </div>

        <section class="rd-card">
          <div class="rd-section-heading">
            <span>Dịch vụ đi kèm</span>
            <h2>Các dịch vụ có thể chọn thêm</h2>
          </div>

          <div class="rd-service-grid">
            @forelse (($services ?? collect()) as $service)
              <div class="rd-service-item">
                <strong>{{ $service->service_name }}</strong>
                <span>{{ $money($service->base_price) }}{{ $service->duration_minutes ? ' / '.$service->duration_minutes.' phút' : '' }}</span>
                <p>{{ $service->description_sv ?: 'Đang cập nhật' }}</p>
              </div>
            @empty
              <p class="rd-muted">Dịch vụ đi kèm đang được cập nhật.</p>
            @endforelse
          </div>
        </section>

        <section class="rd-card">
          <div class="rd-section-heading">
            <span>Điều kiện phòng</span>
            <h2>Thông tin bắt buộc</h2>
          </div>

          <div class="rd-attribute-grid">
            <div class="rd-attribute">
              <span>Mã loại phòng</span>
              <strong>{{ $typeRoom['id'] ?? 'Đang cập nhật' }}</strong>
            </div>
            <div class="rd-attribute">
              <span>Tên loại phòng</span>
              <strong>{{ $typeRoom['label'] ?? $typeRoom['name'] }}</strong>
            </div>
            <div class="rd-attribute">
              <span>Giá cơ bản/ngày</span>
              <strong>{{ $typeRoom['price'] }}</strong>
            </div>
            <div class="rd-attribute">
              <span>Số thú cưng tối đa</span>
              <strong>{{ $typeRoom['capacity'] }}</strong>
            </div>
            <div class="rd-attribute">
              <span>Cân nặng phù hợp</span>
              <strong>{{ $typeRoom['weight'] }}</strong>
            </div>
            <div class="rd-attribute">
              <span>Ghi chú</span>
              <strong>{{ $typeRoom['description'] ?: 'Đang cập nhật' }}</strong>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('assets/client/js/client/type-room.js') }}"></script>
@endpush
