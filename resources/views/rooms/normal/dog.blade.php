@extends('layouts.client')

@section('title', 'Phòng nhỏ cho chó')

@php
  $money = fn ($amount) => number_format((float) $amount, 0, ',', '.').'đ';
  $images = $typeRoom['images'] ?? [];
  $mainImage = $images[0] ?? asset('assets/client/images/type-room/normal/dog/1.jpg');
  $firstAvailableRoom = collect($roomRows ?? [])->firstWhere('is_available', true);
  $bookingUrl = $firstAvailableRoom['booking_url'] ?? url('/booking');
  $availabilityText = ($availableCount ?? 0) > 0 ? 'Còn phòng' : 'Hết phòng';
  $availabilityClass = ($availableCount ?? 0) > 0 ? 'rd-status--available' : 'rd-status--busy';
  $description = $typeRoom['description'] ?? 'Thông tin loại phòng đang được cập nhật.';
  $area = $typeRoom['area'] ?? '3 m²';
  $conditionLines = [
      'Ngày trả phòng phải sau ngày nhận phòng.',
      'Thú cưng cần có thông tin cân nặng phù hợp với loại phòng.',
      'Phòng chỉ được giữ sau khi hệ thống tạo booking thành công.',
  ];
@endphp

@section('content')

<div class="rd-page" data-rd-price="{{ $price ?? $typeRoom['price_raw'] ?? 0 }}" data-rd-booking-url="{{ $bookingUrl }}">
<section class="dog-hero" style="background-image: url('{{ $mainImage }}')">
  <div class="dog-hero-overlay"></div>

  <div class="dog-hero-content">
    <h1>Phòng nhỏ cho chó</h1>
    <p>{{ $description }}</p>

    <a href="{{ $bookingUrl }}" class="dog-primary-btn">
      Đặt phòng ngay
    </a>
  </div>
</section>

<section class="dog-section dog-standards">
  <div class="dog-container">
    <h2 class="dog-section-title">Thông tin phòng</h2>

    <div class="standard-grid">
      <div class="standard-card">
        <div class="standard-icon">
          <i class="fa-solid fa-dog"></i>
        </div>
        <h3>Loài thú cưng</h3>
        <p>Chó</p>
      </div>

      <div class="standard-card">
        <div class="standard-icon">
          <i class="fa-solid fa-door-open"></i>
        </div>
        <h3>Loại phòng</h3>
        <p>Phòng nhỏ</p>
      </div>

      <div class="standard-card">
        <div class="standard-icon">
          <i class="fa-solid fa-paw"></i>
        </div>
        <h3>Sức chứa tối đa</h3>
        <p>{{ $typeRoom['capacity'] ?? (($maxPets ?? null) ? $maxPets.' bé' : 'Đang cập nhật') }}</p>
      </div>

      <div class="standard-card">
        <div class="standard-icon">
          <i class="fa-solid fa-weight-scale"></i>
        </div>
        <h3>Cân nặng phù hợp</h3>
        <p>{{ $typeRoom['weight'] ?? 'Đang cập nhật' }}</p>
      </div>
    </div>
  </div>
</section>

<section class="dog-section dog-rooms">
  <div class="dog-container">
    <h2 class="dog-section-title">Phòng nhỏ cho chó</h2>

    <div class="dog-room-card">
      <div class="dog-room-image">
        <img src="{{ $mainImage }}" alt="Phòng nhỏ cho chó">
      </div>

      <div class="dog-room-content">
        <div class="dog-room-header">
          <div>
            <h3>{{ $typeRoom['name'] ?? 'Phòng nhỏ cho chó' }}</h3>
            <p class="room-area">Diện tích: {{ $area }}</p>
          </div>

          <div class="room-price">
            <strong>{{ $money($price ?? $typeRoom['price_raw'] ?? 0) }}</strong>
            <span>/ngày</span>
          </div>
        </div>

        <div class="room-feature-grid">
          <ul>
            <li><i class="fa-solid fa-check"></i> Loại phòng: {{ $typeRoom['label'] ?? 'Phòng nhỏ' }}</li>
            <li><i class="fa-solid fa-check"></i> Sức chứa: {{ $typeRoom['capacity'] ?? 'Đang cập nhật' }}</li>
            <li><i class="fa-solid fa-check"></i> Cân nặng: {{ $typeRoom['weight'] ?? 'Đang cập nhật' }}</li>
          </ul>

          <ul>
            <li><i class="fa-solid fa-check"></i> Giá: {{ $typeRoom['price'] ?? $money($price ?? 0).'/ngày' }}</li>
            <li><i class="fa-solid fa-check"></i> Diện tích: {{ $area }}</li>
            <li><i class="fa-solid fa-check"></i> Trạng thái: <span class="rd-status {{ $availabilityClass }}">{{ $availabilityText }}</span></li>
          </ul>
        </div>

        <div class="room-description">
          <h4>Mô tả phòng</h4>
          <p>{{ $description }}</p>
        </div>

        <div class="room-health-note">
          <h4>Điều kiện đặt phòng</h4>
          <ul>
            @foreach ($conditionLines as $line)
              <li>{{ $line }}</li>
            @endforeach
          </ul>
        </div>

        <a href="{{ $bookingUrl }}" class="dog-room-btn">Đặt phòng</a>
      </div>
    </div>
  </div>
</section>

<section class="dog-section">
  <div class="dog-container">
    <h2 class="dog-section-title">Hình ảnh phòng</h2>

    <div class="rd-gallery">
      <div class="rd-gallery-main">
        <img class="rd-gallery-main__img" id="rdMainImage" src="{{ $mainImage }}" alt="Phòng nhỏ cho chó">
      </div>

      @if (! empty($images))
        <div class="rd-gallery-thumbs">
          @foreach ($images as $index => $image)
            <button class="rd-gallery-thumb {{ $index === 0 ? 'rd-gallery-thumb--active' : '' }}" type="button"
              onclick="rdSwitchImage('{{ $image }}', this)" aria-label="Xem ảnh {{ $index + 1 }}">
              <img src="{{ $image }}" alt="Phòng nhỏ cho chó {{ $index + 1 }}">
            </button>
          @endforeach
        </div>
      @endif
    </div>
  </div>
</section>

<section class="dog-section dog-daily-process">
  <div class="dog-container">
    <h2 class="dog-section-title">Dịch vụ đi kèm</h2>

    <div class="daily-process-grid">
      @forelse ($services as $service)
        <div class="daily-step">
          <span>{{ $loop->iteration }}</span>
          <h3>{{ $service->service_name }}</h3>
          <p>{{ $money($service->base_price) }}{{ $service->duration_minutes ? ' | '.$service->duration_minutes.' phút' : '' }}</p>
        </div>
      @empty
        <div class="daily-step">
          <span>!</span>
          <h3>Đang cập nhật</h3>
          <p>Chưa có dịch vụ đi kèm cho loại phòng này.</p>
        </div>
      @endforelse
    </div>
  </div>
</section>

</div>

@endsection

@push('scripts')
<script src="{{ asset('assets/client/js/client/type-room.js') }}"></script>
@endpush
